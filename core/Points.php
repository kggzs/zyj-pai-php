<?php
/**
 * 积分管理类
 */
class Points {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 获取用户积分总额
     */
    public function getUserPoints($userId) {
        $user = $this->db->fetchOne(
            "SELECT points FROM users WHERE id = ?",
            [$userId]
        );
        
        return $user['points'] ?? 0;
    }
    
    /**
     * 获取用户积分变动明细
     */
    public function getPointsLog($userId, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        // 对于邀请奖励类型的记录，需要特殊处理：
        // - 如果是"通过注册码注册奖励"（新用户），需要通过 users 表的 register_code 查找邀请人
        // - 如果是"通过邀请码注册奖励"（新用户），需要通过 invites 表查找邀请人
        // - 如果是"邀请新用户注册奖励"（邀请人），需要显示被邀请人信息
        $logs = $this->db->fetchAll(
            "SELECT pl.*, 
                    CASE 
                        WHEN pl.type = 'invite_reward' AND pl.remark = '通过注册码注册奖励' THEN 
                            -- 新用户：通过注册码（users表的register_code字段）查找邀请人
                            (SELECT u2.username FROM users u2 
                             WHERE u2.register_code = pl.invite_code AND u2.status = 1 LIMIT 1)
                        WHEN pl.type = 'invite_reward' AND pl.remark = '通过邀请码注册奖励' THEN 
                            -- 新用户：通过邀请码（invites表）查找邀请人
                            (SELECT u2.username FROM invites i 
                             LEFT JOIN users u2 ON i.user_id = u2.id 
                             WHERE i.invite_code = pl.invite_code LIMIT 1)
                        WHEN pl.type = 'invite_reward' AND pl.remark = '邀请新用户注册奖励' THEN 
                            -- 邀请人：显示被邀请人
                            (SELECT u3.username FROM users u3 WHERE u3.id = pl.new_user_id)
                        ELSE 
                            -- 其他类型：显示新用户（如果有）
                            (SELECT u4.username FROM users u4 WHERE u4.id = pl.new_user_id)
                    END as related_user_name,
                    CASE 
                        WHEN pl.type = 'invite_reward' AND pl.remark = '通过注册码注册奖励' THEN 
                            -- 新用户：通过注册码查找邀请人昵称
                            (SELECT u2.nickname FROM users u2 
                             WHERE u2.register_code = pl.invite_code AND u2.status = 1 LIMIT 1)
                        WHEN pl.type = 'invite_reward' AND pl.remark = '通过邀请码注册奖励' THEN 
                            -- 新用户：通过邀请码查找邀请人昵称
                            (SELECT u2.nickname FROM invites i 
                             LEFT JOIN users u2 ON i.user_id = u2.id 
                             WHERE i.invite_code = pl.invite_code LIMIT 1)
                        WHEN pl.type = 'invite_reward' AND pl.remark = '邀请新用户注册奖励' THEN 
                            -- 邀请人：显示被邀请人昵称
                            (SELECT u3.nickname FROM users u3 WHERE u3.id = pl.new_user_id)
                        ELSE 
                            -- 其他类型：显示新用户昵称（如果有）
                            (SELECT u4.nickname FROM users u4 WHERE u4.id = pl.new_user_id)
                    END as related_user_nickname
             FROM points_log pl 
             WHERE pl.user_id = ? 
             ORDER BY pl.create_time DESC 
             LIMIT ? OFFSET ?",
            [$userId, $pageSize, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM points_log WHERE user_id = ?",
            [$userId]
        );
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 检查今日是否已签到
     */
    public function checkCheckinStatus($userId) {
        $today = date('Y-m-d');
        $checkin = $this->db->fetchOne(
            "SELECT * FROM checkins WHERE user_id = ? AND checkin_date = ?",
            [$userId, $today]
        );
        
        return $checkin ? true : false;
    }
    
    /**
     * 获取用户连续签到天数
     */
    public function getConsecutiveDays($userId) {
        // 获取最近的签到记录，按日期倒序
        $latestCheckin = $this->db->fetchOne(
            "SELECT * FROM checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 1",
            [$userId]
        );
        
        if (!$latestCheckin) {
            return 0;
        }
        
        $latestDate = strtotime($latestCheckin['checkin_date']);
        $today = strtotime(date('Y-m-d'));
        $yesterday = strtotime(date('Y-m-d', strtotime('-1 day')));
        
        // 如果最近一次签到不是今天或昨天，连续签到中断
        if ($latestDate != $today && $latestDate != $yesterday) {
            return 0;
        }
        
        // 如果最近一次签到是昨天，今天还没签到，返回昨天的连续天数
        if ($latestDate == $yesterday) {
            return $latestCheckin['consecutive_days'];
        }
        
        // 如果最近一次签到是今天，返回今天的连续天数
        return $latestCheckin['consecutive_days'];
    }
    
    /**
     * 执行签到
     */
    public function doCheckin($userId, $isVip = false) {
        require_once __DIR__ . '/Helper.php';
        $pointsConfig = Helper::getConfigGroup('points');
        
        // 如果数据库没有配置，从配置文件读取（兼容旧配置）
        if (empty($pointsConfig)) {
            $configFile = __DIR__ . '/../config/config.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                $pointsConfig = $config['points'] ?? [];
            }
        }
        
        // 检查今日是否已签到
        if ($this->checkCheckinStatus($userId)) {
            return [
                'success' => false,
                'message' => '今日已签到，请明天再来'
            ];
        }
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // 获取昨天的签到记录
        $lastCheckin = $this->db->fetchOne(
            "SELECT * FROM checkins WHERE user_id = ? AND checkin_date = ?",
            [$userId, $yesterday]
        );
        
        // 计算连续签到天数
        $consecutiveDays = 1;
        if ($lastCheckin) {
            $consecutiveDays = $lastCheckin['consecutive_days'] + 1;
        } else {
            // 检查是否有更早的签到记录
            $latestCheckin = $this->db->fetchOne(
                "SELECT * FROM checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 1",
                [$userId]
            );
            if ($latestCheckin) {
                $latestDate = strtotime($latestCheckin['checkin_date']);
                $yesterdayTime = strtotime($yesterday);
                // 如果最近一次签到不是昨天，连续签到中断，重新开始
                if ($latestDate != $yesterdayTime) {
                    $consecutiveDays = 1;
                }
            }
        }
        
        // 计算基础积分（设置默认值防止null）
        $basePoints = isset($pointsConfig['checkin_reward']) ? (int)$pointsConfig['checkin_reward'] : 5;
        $totalPoints = $basePoints;
        
        // VIP额外奖励
        $vipBonus = 0;
        if ($isVip) {
            $vipBonus = isset($pointsConfig['checkin_vip_bonus']) ? (int)$pointsConfig['checkin_vip_bonus'] : 3;
            $totalPoints += $vipBonus;
        }
        
        // 连续签到额外奖励
        $consecutiveBonus = 0;
        $consecutiveBonusConfig = $isVip 
            ? ($pointsConfig['checkin_vip_consecutive_bonus'] ?? ['3' => 8, '7' => 15, '15' => 30, '30' => 80])
            : ($pointsConfig['checkin_consecutive_bonus'] ?? ['3' => 5, '7' => 10, '15' => 20, '30' => 50]);
        
        // 确保连续签到奖励配置是数组
        if (!is_array($consecutiveBonusConfig)) {
            $consecutiveBonusConfig = [];
        }
        
        // 检查是否达到连续签到奖励条件
        foreach ($consecutiveBonusConfig as $days => $bonus) {
            if ($consecutiveDays >= (int)$days) {
                $consecutiveBonus = (int)$bonus;
            }
        }
        
        $totalPoints += $consecutiveBonus;
        
        // 开始事务
        $this->db->beginTransaction();
        
        try {
            // 更新用户积分
            $this->db->execute(
                "UPDATE users SET points = points + ? WHERE id = ?",
                [$totalPoints, $userId]
            );
            
            // 记录签到
            $this->db->execute(
                "INSERT INTO checkins (user_id, checkin_date, consecutive_days, points_earned, created_at) 
                 VALUES (?, ?, ?, ?, NOW())",
                [$userId, $today, $consecutiveDays, $totalPoints]
            );
            
            // 记录积分变动
            $remark = "每日签到奖励";
            if ($vipBonus > 0) {
                $remark .= "（VIP额外奖励+{$vipBonus}）";
            }
            if ($consecutiveBonus > 0) {
                $remark .= "（连续签到{$consecutiveDays}天额外奖励+{$consecutiveBonus}）";
            }
            
            $this->db->execute(
                "INSERT INTO points_log (user_id, type, points, remark, create_time) 
                 VALUES (?, 'checkin_reward', ?, ?, NOW())",
                [$userId, $totalPoints, $remark]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => '签到成功',
                'data' => [
                    'points' => $totalPoints,
                    'base_points' => $basePoints,
                    'vip_bonus' => $vipBonus,
                    'consecutive_bonus' => $consecutiveBonus,
                    'consecutive_days' => $consecutiveDays,
                    'total_points' => $this->getUserPoints($userId)
                ]
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error('签到失败：' . $e->getMessage());
            return [
                'success' => false,
                'message' => '签到失败，请稍后重试'
            ];
        }
    }
    
    /**
     * 获取用户签到记录
     */
    public function getCheckinHistory($userId, $page = 1, $pageSize = 30) {
        $offset = ($page - 1) * $pageSize;
        
        $checkins = $this->db->fetchAll(
            "SELECT * FROM checkins WHERE user_id = ? 
             ORDER BY checkin_date DESC 
             LIMIT ? OFFSET ?",
            [$userId, $pageSize, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM checkins WHERE user_id = ?",
            [$userId]
        );
        
        return [
            'list' => $checkins,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取总积分排行榜
     */
    public function getTotalPointsRanking($limit = 100) {
        $ranking = $this->db->fetchAll(
            "SELECT u.id, u.nickname, u.username, u.points,
                    u.is_vip, u.vip_expire_time,
                    (SELECT COUNT(*) FROM invites WHERE user_id = u.id) as invite_count,
                    (SELECT COUNT(*) FROM photos WHERE user_id = u.id) as photo_count
             FROM users u
             WHERE u.status = 1
             ORDER BY u.points DESC, u.id ASC
             LIMIT ?",
            [$limit]
        );
        
        // 处理VIP状态（检查是否过期）
        foreach ($ranking as &$item) {
            $item['is_vip_active'] = false;
            if (($item['is_vip'] ?? 0) == 1) {
                if ($item['vip_expire_time'] === null) {
                    // 永久VIP
                    $item['is_vip_active'] = true;
                } else {
                    // 检查是否过期
                    $expireTime = strtotime($item['vip_expire_time']);
                    $item['is_vip_active'] = $expireTime > time();
                }
            }
        }
        
        return $ranking;
    }
    
    /**
     * 获取月度积分排行榜（本月获得的积分）
     */
    public function getMonthlyPointsRanking($year = null, $month = null, $limit = 100) {
        if ($year === null) {
            $year = date('Y');
        }
        if ($month === null) {
            $month = date('m');
        }
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $ranking = $this->db->fetchAll(
            "SELECT u.id, u.nickname, u.username,
                    u.is_vip, u.vip_expire_time,
                    COALESCE(SUM(pl.points), 0) as monthly_points,
                    COUNT(pl.id) as points_count
             FROM users u
             LEFT JOIN points_log pl ON pl.user_id = u.id 
                 AND pl.points > 0 
                 AND DATE(pl.create_time) >= ? 
                 AND DATE(pl.create_time) <= ?
             WHERE u.status = 1
             GROUP BY u.id, u.nickname, u.username, u.is_vip, u.vip_expire_time
             HAVING monthly_points > 0
             ORDER BY monthly_points DESC, u.id ASC
             LIMIT ?",
            [$startDate, $endDate, $limit]
        );
        
        // 处理VIP状态（检查是否过期）
        foreach ($ranking as &$item) {
            $item['is_vip_active'] = false;
            if (($item['is_vip'] ?? 0) == 1) {
                if ($item['vip_expire_time'] === null) {
                    // 永久VIP
                    $item['is_vip_active'] = true;
                } else {
                    // 检查是否过期
                    $expireTime = strtotime($item['vip_expire_time']);
                    $item['is_vip_active'] = $expireTime > time();
                }
            }
        }
        
        return $ranking;
    }
    
    /**
     * 获取邀请人数排行榜
     */
    public function getInviteRanking($limit = 100) {
        $ranking = $this->db->fetchAll(
            "SELECT u.id, u.nickname, u.username,
                    u.is_vip, u.vip_expire_time,
                    COUNT(DISTINCT pl.new_user_id) as invite_count,
                    SUM(pl.points) as total_reward_points
             FROM users u
             LEFT JOIN points_log pl ON pl.user_id = u.id 
                 AND pl.type = 'invite_reward' 
                 AND pl.new_user_id IS NOT NULL
                 AND pl.remark = '邀请新用户注册奖励'
             WHERE u.status = 1
             GROUP BY u.id, u.nickname, u.username, u.is_vip, u.vip_expire_time
             HAVING invite_count > 0
             ORDER BY invite_count DESC, total_reward_points DESC, u.id ASC
             LIMIT ?",
            [$limit]
        );
        
        // 处理VIP状态（检查是否过期）
        foreach ($ranking as &$item) {
            $item['is_vip_active'] = false;
            if (($item['is_vip'] ?? 0) == 1) {
                if ($item['vip_expire_time'] === null) {
                    // 永久VIP
                    $item['is_vip_active'] = true;
                } else {
                    // 检查是否过期
                    $expireTime = strtotime($item['vip_expire_time']);
                    $item['is_vip_active'] = $expireTime > time();
                }
            }
        }
        
        return $ranking;
    }
    
    /**
     * 获取上传照片数量排行榜（包含已删除的照片）
     */
    public function getPhotoCountRanking($limit = 100) {
        $ranking = $this->db->fetchAll(
            "SELECT u.id, u.nickname, u.username,
                    u.is_vip, u.vip_expire_time,
                    COUNT(p.id) as photo_count,
                    MAX(p.upload_time) as last_upload_time
             FROM users u
             LEFT JOIN photos p ON p.user_id = u.id
             WHERE u.status = 1
             GROUP BY u.id, u.nickname, u.username, u.is_vip, u.vip_expire_time
             HAVING photo_count > 0
             ORDER BY photo_count DESC, last_upload_time DESC, u.id ASC
             LIMIT ?",
            [$limit]
        );
        
        // 处理VIP状态（检查是否过期）
        foreach ($ranking as &$item) {
            $item['is_vip_active'] = false;
            if (($item['is_vip'] ?? 0) == 1) {
                if ($item['vip_expire_time'] === null) {
                    // 永久VIP
                    $item['is_vip_active'] = true;
                } else {
                    // 检查是否过期
                    $expireTime = strtotime($item['vip_expire_time']);
                    $item['is_vip_active'] = $expireTime > time();
                }
            }
        }
        
        return $ranking;
    }
    
    /**
     * 获取用户在排行榜中的排名
     */
    public function getUserRanking($userId, $type = 'total') {
        $user = $this->db->fetchOne(
            "SELECT id, username, points FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return null;
        }
        
        switch ($type) {
            case 'total':
                $rank = $this->db->fetchOne(
                    "SELECT COUNT(*) + 1 as rank
                     FROM users u
                     WHERE u.status = 1 
                     AND (u.points > ? OR (u.points = ? AND u.id < ?))",
                    [$user['points'], $user['points'], $userId]
                );
                return [
                    'rank' => $rank['rank'] ?? 0,
                    'points' => $user['points']
                ];
                
            case 'monthly':
                $year = date('Y');
                $month = date('m');
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate = date('Y-m-t', strtotime($startDate));
                
                $userMonthly = $this->db->fetchOne(
                    "SELECT COALESCE(SUM(points), 0) as monthly_points
                     FROM points_log
                     WHERE user_id = ? AND points > 0 
                     AND DATE(create_time) >= ? AND DATE(create_time) <= ?",
                    [$userId, $startDate, $endDate]
                );
                
                $rank = $this->db->fetchOne(
                    "SELECT COUNT(*) + 1 as rank
                     FROM (
                         SELECT u.id, COALESCE(SUM(pl.points), 0) as monthly_points
                         FROM users u
                         LEFT JOIN points_log pl ON pl.user_id = u.id 
                             AND pl.points > 0 
                             AND DATE(pl.create_time) >= ? 
                             AND DATE(pl.create_time) <= ?
                         WHERE u.status = 1
                         GROUP BY u.id
                         HAVING monthly_points > ?
                     ) as ranking",
                    [$startDate, $endDate, $userMonthly['monthly_points'] ?? 0]
                );
                
                if (!$rank || ($rank['rank'] ?? 0) == 0) {
                    // 如果没有排名，说明用户本月没有积分，需要计算总排名
                    $totalUsers = $this->db->fetchOne(
                        "SELECT COUNT(*) as total
                         FROM (
                             SELECT u.id, COALESCE(SUM(pl.points), 0) as monthly_points
                             FROM users u
                             LEFT JOIN points_log pl ON pl.user_id = u.id 
                                 AND pl.points > 0 
                                 AND DATE(pl.create_time) >= ? 
                                 AND DATE(pl.create_time) <= ?
                             WHERE u.status = 1
                             GROUP BY u.id
                             HAVING monthly_points >= 0
                         ) as all_users",
                        [$startDate, $endDate]
                    );
                    $rank = ['rank' => ($totalUsers['total'] ?? 0) + 1];
                }
                
                return [
                    'rank' => $rank['rank'] ?? 0,
                    'points' => $userMonthly['monthly_points'] ?? 0
                ];
                
            case 'invite':
                $userInvite = $this->db->fetchOne(
                    "SELECT COUNT(DISTINCT new_user_id) as invite_count
                     FROM points_log
                     WHERE user_id = ? AND type = 'invite_reward' AND new_user_id IS NOT NULL AND remark = '邀请新用户注册奖励'",
                    [$userId]
                );
                
                $rank = $this->db->fetchOne(
                    "SELECT COUNT(*) + 1 as rank
                     FROM (
                         SELECT u.id, COUNT(DISTINCT pl.new_user_id) as invite_count
                         FROM users u
                         LEFT JOIN points_log pl ON pl.user_id = u.id 
                             AND pl.type = 'invite_reward' 
                             AND pl.new_user_id IS NOT NULL
                             AND pl.remark = '邀请新用户注册奖励'
                         WHERE u.status = 1
                         GROUP BY u.id
                         HAVING invite_count > ?
                     ) as ranking",
                    [$userInvite['invite_count'] ?? 0]
                );
                
                return [
                    'rank' => $rank['rank'] ?? 0,
                    'count' => $userInvite['invite_count'] ?? 0
                ];
                
            case 'photo':
                $userPhoto = $this->db->fetchOne(
                    "SELECT COUNT(*) as photo_count
                     FROM photos
                     WHERE user_id = ?",
                    [$userId]
                );
                
                $rank = $this->db->fetchOne(
                    "SELECT COUNT(*) + 1 as rank
                     FROM (
                         SELECT u.id, COUNT(p.id) as photo_count
                         FROM users u
                         LEFT JOIN photos p ON p.user_id = u.id
                         WHERE u.status = 1
                         GROUP BY u.id
                         HAVING photo_count > ?
                     ) as ranking",
                    [$userPhoto['photo_count'] ?? 0]
                );
                
                return [
                    'rank' => $rank['rank'] ?? 0,
                    'count' => $userPhoto['photo_count'] ?? 0
                ];
        }
        
        return null;
    }
}
