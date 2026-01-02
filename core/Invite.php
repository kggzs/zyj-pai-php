<?php
/**
 * 拍摄链接管理类
 */
class Invite {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 生成拍摄链接
     * @param int $userId 用户ID
     * @param string|null $customExpireTime 自定义到期时间（格式：Y-m-d H:i:s），null表示使用默认，'unlimited'表示无限制
     * @return array
     */
    public function generateInvite($userId, $customExpireTime = null) {
        require_once __DIR__ . '/Helper.php';
        $inviteConfig = Helper::getConfigGroup('invite');
        
        // 如果数据库没有配置，从配置文件读取（兼容旧配置）
        if (empty($inviteConfig)) {
            $configFile = __DIR__ . '/../config/config.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                $inviteConfig = $config['invite'] ?? [];
            }
        }
        
        // 检查用户是否是VIP（包括检查是否过期）
        $user = $this->db->fetchOne(
            "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
            [$userId]
        );
        $isVip = false;
        if (($user['is_vip'] ?? 0) == 1) {
            // 检查VIP是否过期
            if ($user['vip_expire_time'] === null) {
                // 永久VIP
                $isVip = true;
            } else {
                // 检查是否过期
                $expireTime = strtotime($user['vip_expire_time']);
                $isVip = $expireTime > time();
                
                // 如果过期，自动更新VIP状态
                if (!$isVip) {
                    $this->db->execute(
                        "UPDATE users SET is_vip = 0, vip_expire_time = NULL WHERE id = ?",
                        [$userId]
                    );
                }
            }
        }
        
        // 检查用户已有的拍摄链接总数（VIP用户不受限制）
        if (!$isVip) {
            $totalCount = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM invites WHERE user_id = ?",
                [$userId]
            );
            
            $currentCount = $totalCount['total'] ?? 0;
            $baseMaxInvites = $inviteConfig['max_count'] ?? 7;
            
            // 获取用户的额外配额（通过积分兑换获得）
            $user = $this->db->fetchOne(
                "SELECT invite_quota_bonus FROM users WHERE id = ?",
                [$userId]
            );
            $quotaBonus = $user['invite_quota_bonus'] ?? 0;
            
            // 总配额 = 基础配额 + 额外配额
            $maxInvites = $baseMaxInvites + $quotaBonus;
            
            if ($currentCount >= $maxInvites) {
                return [
                    'success' => false,
                    'message' => "已达到拍摄链接生成上限（最多{$maxInvites}个，基础{$baseMaxInvites}个" . ($quotaBonus > 0 ? "，额外{$quotaBonus}个" : "") . "），无法继续生成"
                ];
            }
            
            // 检查普通用户是否有未过期的链接（用于提示，但不阻止生成）
            $activeInvite = $this->db->fetchOne(
                "SELECT expire_time FROM invites WHERE user_id = ? AND status = 1 AND (expire_time IS NULL OR expire_time > NOW()) ORDER BY create_time DESC LIMIT 1",
                [$userId]
            );
            $hasActiveInvite = !empty($activeInvite);
        } else {
            $hasActiveInvite = false;
        }
        
        // 生成唯一拍摄链接码
        $inviteCode = $this->generateInviteCode($userId);
        
        // 计算有效期
        $createTime = date('Y-m-d H:i:s');
        
        // 处理到期时间
        // 如果customExpireTime为null，VIP用户自动使用永久，普通用户使用默认有效期
        if ($customExpireTime === null) {
            if ($isVip) {
                // VIP用户默认生成永久链接
                $expireTime = null; // NULL表示无限制
            } else {
                // 普通用户使用默认有效期
                $expireDays = $inviteConfig['default_expire_days'] ?? 7;
                $expireTime = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
            }
        } elseif ($customExpireTime === 'unlimited') {
            // 只有VIP用户才能设置无限制
            if ($isVip) {
                $expireTime = null; // NULL表示无限制
            } else {
                // 非VIP用户尝试设置无限制，使用默认有效期
                $expireDays = $inviteConfig['default_expire_days'] ?? 7;
                $expireTime = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
            }
        } else {
            // 自定义时间
            $expireTime = $customExpireTime;
        }
        
        // 生成拍摄链接（拍照和录像两种）
        require_once __DIR__ . '/Helper.php';
        $siteUrl = Helper::getSiteUrl();
        $photoInviteUrl = $siteUrl . '/invite.php?code=' . $inviteCode;
        $videoInviteUrl = $siteUrl . '/record.php?code=' . $inviteCode;
        
        // 保存到数据库（使用拍照链接作为主链接）
        $sql = "INSERT INTO invites (invite_code, user_id, invite_url, create_time, expire_time, status, upload_count, label) 
                VALUES (?, ?, ?, ?, ?, 1, 0, NULL)";
        $this->db->execute($sql, [
            $inviteCode,
            $userId,
            $photoInviteUrl,
            $createTime,
            $expireTime
        ]);
        
        $inviteId = $this->db->lastInsertId();
        
        $result = [
            'success' => true,
            'invite_code' => $inviteCode,
            'invite_url' => $photoInviteUrl,
            'video_invite_url' => $videoInviteUrl,
            'invite_id' => $inviteId,
            'expire_time' => $expireTime,
            'is_unlimited' => $expireTime === null
        ];
        
        // 普通用户如果有未过期的链接，添加提示信息（但不阻止生成）
        if (!$isVip && $hasActiveInvite) {
            $result['warning'] = '上一条链接未到期，不建议再次生成链接';
        }
        
        return $result;
    }
    
    /**
     * 验证拍摄链接码有效性
     */
    public function validateInvite($inviteCode) {
        // 验证拍摄链接码长度（必须是8位）
        if (strlen($inviteCode) !== 8) {
            return ['valid' => false, 'message' => '拍摄链接码格式错误（应为8位）'];
        }
        
        // 查询拍摄链接码，expire_time为NULL表示无限制，否则需要检查是否过期
        $invite = $this->db->fetchOne(
            "SELECT * FROM invites WHERE invite_code = ? AND status = 1 AND (expire_time IS NULL OR expire_time > NOW())",
            [$inviteCode]
        );
        
        if (!$invite) {
            return ['valid' => false, 'message' => '拍摄链接无效或已过期'];
        }
        
        return ['valid' => true, 'invite' => $invite];
    }
    
    /**
     * 获取用户的拍摄链接列表
     */
    public function getUserInvites($userId, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        $invites = $this->db->fetchAll(
            "SELECT * FROM invites WHERE user_id = ? ORDER BY create_time DESC LIMIT ? OFFSET ?",
            [$userId, $pageSize, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM invites WHERE user_id = ?",
            [$userId]
        );
        
        // 从数据库读取最大邀请链接数
        require_once __DIR__ . '/Helper.php';
        $inviteConfig = Helper::getConfigGroup('invite');
        
        // 如果数据库没有配置，从配置文件读取（兼容旧配置）
        if (empty($inviteConfig)) {
            $configFile = __DIR__ . '/../config/config.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                $inviteConfig = $config['invite'] ?? [];
            }
        }
        
        $baseMaxInvites = $inviteConfig['max_count'] ?? 7;
        
        // 获取用户的额外配额
        $user = $this->db->fetchOne(
            "SELECT invite_quota_bonus FROM users WHERE id = ?",
            [$userId]
        );
        $quotaBonus = $user['invite_quota_bonus'] ?? 0;
        $maxInvites = $baseMaxInvites + $quotaBonus;
        
        $totalCount = $total['total'] ?? 0;
        
        // 为每个拍摄链接添加录像链接
        require_once __DIR__ . '/Helper.php';
        $siteUrl = Helper::getSiteUrl();
        foreach ($invites as &$invite) {
            $invite['video_invite_url'] = $siteUrl . '/record.php?code=' . $invite['invite_code'];
        }
        
        return [
            'list' => $invites,
            'total' => $totalCount,
            'page' => $page,
            'page_size' => $pageSize,
            'max_invites' => $maxInvites, // 最大拍摄链接数（基础 + 额外配额）
            'base_max_invites' => $baseMaxInvites, // 基础最大拍摄链接数
            'quota_bonus' => $quotaBonus, // 额外配额
            'can_generate' => $totalCount < $maxInvites // 是否可以继续生成
        ];
    }
    
    /**
     * 增加拍摄链接上传数量
     */
    public function incrementUploadCount($inviteId) {
        $this->db->execute(
            "UPDATE invites SET upload_count = upload_count + 1 WHERE id = ?",
            [$inviteId]
        );
    }
    
    /**
     * 生成唯一拍摄链接码（8位数字字母）
     */
    private function generateInviteCode($userId) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxAttempts = 10; // 最多尝试10次
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            // 生成8位随机字符串
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // 检查是否已存在
            $existing = $this->db->fetchOne(
                "SELECT id FROM invites WHERE invite_code = ?",
                [$code]
            );
            
            if (!$existing) {
                return $code;
            }
        }
        
        // 如果10次都冲突，使用时间戳+随机数作为后备方案
        $code = '';
        for ($j = 0; $j < 8; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * 更新拍摄链接标签和状态（VIP用户功能）
     * @param int $inviteId 拍摄链接ID
     * @param int $userId 用户ID（验证权限）
     * @param string|null $label 标签（可选，null表示不更新）
     * @param int|null $status 状态（可选，1-启用，0-禁用，null表示不更新）
     * @return array
     */
    public function updateInvite($inviteId, $userId, $label = null, $status = null) {
        // 验证拍摄链接是否属于该用户
        $invite = $this->db->fetchOne(
            "SELECT * FROM invites WHERE id = ? AND user_id = ?",
            [$inviteId, $userId]
        );
        
        if (!$invite) {
            return ['success' => false, 'message' => '拍摄链接不存在或无权限'];
        }
        
        // 检查用户是否是VIP（包括检查是否过期）
        $user = $this->db->fetchOne(
            "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
            [$userId]
        );
        $isVip = false;
        if (($user['is_vip'] ?? 0) == 1) {
            // 检查VIP是否过期
            if ($user['vip_expire_time'] === null) {
                // 永久VIP
                $isVip = true;
            } else {
                // 检查是否过期
                $expireTime = strtotime($user['vip_expire_time']);
                $isVip = $expireTime > time();
                
                // 如果过期，自动更新VIP状态
                if (!$isVip) {
                    $this->db->execute(
                        "UPDATE users SET is_vip = 0, vip_expire_time = NULL WHERE id = ?",
                        [$userId]
                    );
                }
            }
        }
        
        // 只有VIP用户才能禁用/启用拍摄链接
        if ($status !== null && !$isVip) {
            return ['success' => false, 'message' => '只有VIP用户才能禁用/启用拍摄链接'];
        }
        
        // 构建更新SQL
        $updates = [];
        $params = [];
        
        if ($label !== null) {
            $label = trim($label);
            // 标签可以为空（清空标签）
            if (mb_strlen($label) > 10) {
                return ['success' => false, 'message' => '标签长度不能超过10个字符'];
            }
            $updates[] = "label = ?";
            $params[] = $label ?: null;
        }
        
        if ($status !== null) {
            $status = $status == 1 ? 1 : 0;
            $updates[] = "status = ?";
            $params[] = $status;
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => '没有需要更新的内容'];
        }
        
        $params[] = $inviteId;
        $params[] = $userId;
        
        $sql = "UPDATE invites SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
        $this->db->execute($sql, $params);
        
        return [
            'success' => true,
            'message' => '更新成功',
            'label' => $label !== null ? ($label ?: '') : $invite['label'],
            'status' => $status !== null ? $status : $invite['status']
        ];
    }
    
    /**
     * 获取拍摄链接码的标签（用于照片列表显示）
     */
    public function getInviteLabel($inviteCode) {
        $invite = $this->db->fetchOne(
            "SELECT label FROM invites WHERE invite_code = ?",
            [$inviteCode]
        );
        
        return $invite ? ($invite['label'] ?? '') : '';
    }
}
