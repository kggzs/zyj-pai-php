<?php
/**
 * 用户管理类
 */
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 用户注册
     */
    public function register($username, $password, $inviteCode = null, $email = '') {
        // 获取系统配置
        require_once __DIR__ . '/Helper.php';
        $requireVerification = Helper::getSystemConfig('register_require_email_verification') == '1';
        
        // 如果开启了强制邮箱验证，邮箱自动变为必填
        $email = trim($email);
        if ($requireVerification && empty($email)) {
            return ['success' => false, 'message' => '开启邮箱验证后，邮箱为必填项，请填写邮箱地址'];
        }
        
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => '邮箱格式不正确'];
            }
            
            // 检查邮箱是否已被使用
            $emailExists = $this->db->fetchOne("SELECT id FROM users WHERE email = ? AND email != ''", [$email]);
            if ($emailExists) {
                return ['success' => false, 'message' => '该邮箱已被使用'];
            }
        }
        
        // 验证用户名
        $username = trim($username);
        if (empty($username)) {
            return ['success' => false, 'message' => '用户名不能为空'];
        }
        
        // 用户名长度限制：3-20个字符
        if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
            return ['success' => false, 'message' => '用户名长度必须在3-20个字符之间'];
        }
        
        // 用户名格式验证：只允许字母、数字、下划线
        if (!preg_match('/^[a-zA-Z0-9_]+$/u', $username)) {
            return ['success' => false, 'message' => '用户名只能包含字母、数字和下划线'];
        }
        
        // 检查用户名是否已存在
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            return ['success' => false, 'message' => '用户名已存在'];
        }
        
        // 验证密码强度
        if (empty($password) || mb_strlen($password) < 6) {
            return ['success' => false, 'message' => '密码长度至少为6个字符'];
        }
        
        // 验证注册码或拍摄链接码格式（如果提供）
        if ($inviteCode) {
            $inviteCode = trim($inviteCode);
            // 6位=注册码，8位=拍摄链接码
            if (!preg_match('/^[a-zA-Z0-9]{6}$/', $inviteCode) && !preg_match('/^[a-zA-Z0-9]{8}$/', $inviteCode)) {
                return ['success' => false, 'message' => '注册码或拍摄链接码格式错误（注册码6位，拍摄链接码8位）'];
            }
        }
        
        // 加密密码
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 获取注册IP和浏览器信息
        $registerIp = $this->getClientIp();
        $registerUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $registerTime = date('Y-m-d H:i:s');
        
        // 插入用户数据
        // 如果开启了强制邮箱验证，email_verified默认为0，否则为1（如果提供了邮箱）
        $emailVerified = 0;
        if (!empty($email)) {
            // 如果开启了强制验证，需要验证；否则自动验证
            $emailVerified = $requireVerification ? 0 : 1;
        }
        
        $sql = "INSERT INTO users (username, password, email, email_verified, register_ip, register_ua, register_time, last_login_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $username,
            $hashedPassword,
            $email ?: null,
            $emailVerified,
            $registerIp,
            $registerUa,
            $registerTime,
            $registerTime
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // 如果存在注册码或拍摄链接码，处理积分奖励（使用try-catch确保不影响注册流程）
        if ($inviteCode) {
            try {
                // 根据长度自动判断：6位=注册码，8位=拍摄链接码
                // handleInviteReward会自动判断类型
                $this->handleInviteReward($inviteCode, $userId, null);
            } catch (Exception $e) {
                // 积分奖励失败不影响注册，只记录错误日志
                error_log('处理注册奖励失败：' . $e->getMessage());
                error_log('堆栈：' . $e->getTraceAsString());
            }
        }
        
        // 如果开启了强制邮箱验证且提供了邮箱，发送验证码
        if ($requireVerification && !empty($email)) {
            $sendResult = $this->sendEmailVerificationCode($userId, $email, 'email');
            // 如果发送失败，记录错误但不阻止注册（用户可以通过重新发送验证码）
            if (!$sendResult['success']) {
                error_log('注册时发送验证码失败：' . $sendResult['message']);
            }
        }
        
        // 清除统计数据缓存
        $cache = Cache::getInstance();
        $cache->delete('admin_statistics');
        
        return [
            'success' => true, 
            'message' => $requireVerification && !empty($email) ? '注册成功，请验证邮箱后登录' : '注册成功',
            'user_id' => $userId,
            'require_verification' => $requireVerification && !empty($email)
        ];
    }
    
    /**
     * 用户登录
     */
    public function login($username, $password) {
        // 支持邮箱登录
        $user = null;
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            // 如果是邮箱格式，尝试用邮箱登录
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE email = ? AND status = 1",
                [$username]
            );
        }
        
        // 如果不是邮箱或邮箱登录失败，尝试用户名登录
        if (!$user) {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = ? AND status = 1",
                [$username]
            );
        }
        
        $loginIp = $this->getClientIp();
        $loginUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $loginTime = date('Y-m-d H:i:s');
        
        if (!$user) {
            // 记录失败的登录尝试
            $this->logLogin(null, $loginIp, $loginUa, false, '用户名不存在');
            
            // 检查是否多次失败登录（异常行为）
            $this->checkMultipleFailedLogins(null, $loginIp);
            
            return ['success' => false, 'message' => '用户名或密码错误'];
        }
        
        if (!password_verify($password, $user['password'])) {
            // 记录失败的登录尝试
            $this->logLogin($user['id'], $loginIp, $loginUa, false, '密码错误');
            
            // 检查是否多次失败登录（异常行为）
            $this->checkMultipleFailedLogins($user['id'], $loginIp);
            
            return ['success' => false, 'message' => '用户名或密码错误'];
        }
        
        // 检查是否需要邮箱验证
        require_once __DIR__ . '/Helper.php';
        $requireVerification = Helper::getSystemConfig('register_require_email_verification') == '1';
        if ($requireVerification) {
            // 如果开启了强制验证，检查邮箱验证状态
            if (empty($user['email'])) {
                // 没有邮箱，跳转到验证页面让用户添加邮箱
                $this->logLogin($user['id'], $loginIp, $loginUa, false, '未绑定邮箱');
                // 生成安全token用于验证页面访问
                $verifyToken = $this->generateVerifyToken($user['id'], $username);
                return ['success' => false, 'message' => '您的账号未绑定邮箱，请先添加并验证邮箱', 'require_verification' => true, 'verify_token' => $verifyToken];
            } elseif ($user['email_verified'] != 1) {
                // 有邮箱但未验证，跳转到验证页面
                $this->logLogin($user['id'], $loginIp, $loginUa, false, '邮箱未验证');
                // 生成安全token用于验证页面访问
                $verifyToken = $this->generateVerifyToken($user['id'], $username);
                return ['success' => false, 'message' => '请先验证邮箱后再登录', 'require_verification' => true, 'verify_token' => $verifyToken];
            }
        }
        
        // 检查异常登录
        $isUnusual = $this->checkUnusualLogin($user['id'], $loginIp);
        
        // 更新登录信息
        $this->db->execute(
            "UPDATE users SET last_login_time = ?, last_login_ip = ? WHERE id = ?",
            [$loginTime, $loginIp, $user['id']]
        );
        
        // 记录登录日志
        $this->logLogin($user['id'], $loginIp, $loginUa, true, null);
        
        // 如果异常登录，记录异常行为并发送提醒邮件
        if ($isUnusual) {
            try {
                require_once __DIR__ . '/Admin.php';
                $adminModel = new Admin();
                $adminModel->logAbnormalBehavior(
                    'unusual_login',
                    $user['id'],
                    "用户从新IP地址登录：{$loginIp}",
                    2, // 中等严重程度
                    $loginIp,
                    $loginUa,
                    $_SERVER['REQUEST_URI'] ?? null
                );
            } catch (Exception $e) {
                error_log('记录异常行为失败：' . $e->getMessage());
            }
            
            if (!empty($user['email']) && $user['email_verified'] == 1) {
                try {
                    $email = new Email();
                    $email->sendUnusualLoginAlert($user['email'], $user['username'] ?? $user['nickname'] ?? $user['username'], $loginIp);
                } catch (Exception $e) {
                    error_log('发送异常登录提醒失败：' . $e->getMessage());
                }
            }
        }
        
        // 重新生成Session ID以防止会话固定攻击
        session_regenerate_id(true);
        
        // 设置Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        return ['success' => true, 'message' => '登录成功', 'user' => $user];
    }
    
    /**
     * 获取当前登录用户信息
     */
    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT id, username, nickname, email, email_verified, email_notify_photo, register_time, last_login_time, last_login_ip, points FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * 检查是否已登录
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * 处理邀请奖励（给被邀请人和邀请人都发放积分）
     * 注意：此方法不应抛出异常，所有错误都应该被捕获并记录日志
     * @param string $code 注册码或拍摄链接码
     * @param int $newUserId 新用户ID
     * @param bool|null $isRegisterCode 是否为注册码（true=注册码，false=拍摄链接码，null=自动判断：6位=注册码，8位=拍摄链接码）
     */
    private function handleInviteReward($code, $newUserId, $isRegisterCode = null) {
        try {
            // 如果未指定类型，根据代码长度自动判断：6位=注册码，8位=拍摄链接码
            if ($isRegisterCode === null) {
                $codeLength = strlen($code);
                if ($codeLength === 6) {
                    $isRegisterCode = true; // 6位=注册码
                } elseif ($codeLength === 8) {
                    $isRegisterCode = false; // 8位=拍摄链接码
                } else {
                    // 长度不符合，无法判断，记录错误并返回
                    error_log("邀请码长度错误：code={$code}, length={$codeLength}");
                    return;
                }
            }
            
            if ($isRegisterCode) {
                // 处理注册码
                $registerUser = $this->getUserByRegisterCode($code);
                if (!$registerUser) {
                    // 注册码无效，不影响注册流程
                    error_log("注册码无效：code={$code}, new_user_id={$newUserId}");
                    return;
                }
                
                // 检查新用户是否已经获得过这个注册码的奖励（避免重复）
                $newUserExisting = $this->db->fetchOne(
                    "SELECT id FROM points_log WHERE user_id = ? AND invite_code = ? AND new_user_id = ? AND remark = '通过注册码注册奖励'",
                    [$newUserId, $code, $newUserId]
                );
                
                // 检查邀请人是否已经获得过这个新用户的奖励（避免重复）
                $inviterExisting = $this->db->fetchOne(
                    "SELECT id FROM points_log WHERE user_id = ? AND invite_code = ? AND new_user_id = ? AND remark = '邀请新用户注册奖励'",
                    [$registerUser['id'], $code, $newUserId]
                );
                
                if ($newUserExisting || $inviterExisting) {
                    error_log("注册码奖励已发放过：register_code={$code}, new_user_id={$newUserId}");
                    return; // 已经奖励过，避免重复
                }
                
                $inviterId = $registerUser['id'];
                $inviteCode = $code; // 使用注册码作为记录
                error_log("开始处理注册码奖励：code={$code}, inviter_id={$inviterId}, new_user_id={$newUserId}");
            } else {
                // 处理拍摄链接码（向后兼容）
                $invite = $this->db->fetchOne(
                    "SELECT * FROM invites WHERE invite_code = ? AND status = 1 AND (expire_time IS NULL OR expire_time > NOW())",
                    [$code]
                );
                
                if (!$invite) {
                    // 拍摄链接码无效或已过期，不影响注册流程
                    return;
                }
                
                // 检查新用户是否已经获得过这个邀请码的奖励（避免重复）
                $newUserExisting = $this->db->fetchOne(
                    "SELECT id FROM points_log WHERE user_id = ? AND invite_code = ? AND new_user_id = ? AND remark = '通过邀请码注册奖励'",
                    [$newUserId, $code, $newUserId]
                );
                
                // 检查邀请人是否已经获得过这个新用户的奖励（避免重复）
                $inviterExisting = $this->db->fetchOne(
                    "SELECT id FROM points_log WHERE user_id = ? AND invite_code = ? AND new_user_id = ? AND remark = '邀请新用户注册奖励'",
                    [$invite['user_id'], $code, $newUserId]
                );
                
                if ($newUserExisting || $inviterExisting) {
                    error_log("邀请奖励已发放过：invite_code={$code}, new_user_id={$newUserId}");
                    return; // 已经奖励过，避免重复
                }
                
                $inviterId = $invite['user_id'];
                $inviteCode = $code;
            }
            
            // 获取积分配置
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
            
            // 新用户（被邀请人）获得的积分
            $newUserPoints = $pointsConfig['invite_reward_new_user'] ?? 10;
            // 邀请人获得的积分
            $inviterPoints = $pointsConfig['invite_reward_inviter'] ?? 10;
            
            // 给新用户（被邀请人）发放积分
            if ($newUserPoints > 0) {
                try {
                    $this->db->execute(
                        "UPDATE users SET points = points + ? WHERE id = ?",
                        [$newUserPoints, $newUserId]
                    );
                    
                    // 记录新用户的积分变动
                    $remark = $isRegisterCode ? '通过注册码注册奖励' : '通过邀请码注册奖励';
                    $this->db->execute(
                        "INSERT INTO points_log (user_id, type, points, invite_code, new_user_id, remark, create_time) 
                         VALUES (?, 'invite_reward', ?, ?, ?, ?, NOW())",
                        [$newUserId, $newUserPoints, $inviteCode, $newUserId, $remark]
                    );
                    error_log("新用户积分发放成功：user_id={$newUserId}, points={$newUserPoints}, code={$inviteCode}");
                } catch (Exception $e) {
                    error_log("新用户积分发放失败：user_id={$newUserId}, points={$newUserPoints}, error=" . $e->getMessage());
                }
            }
            
            // 给邀请人发放积分
            if ($inviterPoints > 0) {
                try {
                    // 更新邀请人积分
                    $updateResult = $this->db->execute(
                        "UPDATE users SET points = points + ? WHERE id = ?",
                        [$inviterPoints, $inviterId]
                    );
                    
                    if ($updateResult === false) {
                        error_log("更新邀请人积分失败：user_id={$inviterId}, points={$inviterPoints}");
                        return;
                    }
                    
                    // 记录邀请人的积分变动
                    // 注意：由于唯一索引 unique_reward (invite_code, new_user_id) 的限制，
                    // 新用户和邀请人的记录不能同时使用相同的 invite_code 和 new_user_id
                    // 解决方案：邀请人记录使用 invite_code + '_inviter' 后缀，避免唯一索引冲突
                    try {
                        // 检查是否已存在邀请人记录（避免重复）
                        // 注意：邀请人记录可能使用 invite_code 或 invite_code + '_inviter'
                        $existingInviterLog = $this->db->fetchOne(
                            "SELECT id FROM points_log WHERE user_id = ? AND (invite_code = ? OR invite_code = ?) AND new_user_id = ? AND remark = '邀请新用户注册奖励'",
                            [$inviterId, $inviteCode, $inviteCode . '_inviter', $newUserId]
                        );
                        
                        if ($existingInviterLog) {
                            error_log("邀请人积分记录已存在：user_id={$inviterId}, code={$inviteCode}, new_user_id={$newUserId}");
                        } else {
                            // 使用 invite_code + '_inviter' 作为邀请人记录的 invite_code，避免与新用户记录的唯一索引冲突
                            $inviterInviteCode = $inviteCode . '_inviter';
                            $insertResult = $this->db->execute(
                                "INSERT INTO points_log (user_id, type, points, invite_code, new_user_id, remark, create_time) 
                                 VALUES (?, 'invite_reward', ?, ?, ?, ?, NOW())",
                                [$inviterId, $inviterPoints, $inviterInviteCode, $newUserId, '邀请新用户注册奖励']
                            );
                            
                            if ($insertResult > 0) {
                                error_log("邀请人积分发放成功：user_id={$inviterId}, points={$inviterPoints}, code={$inviteCode}, new_user_id={$newUserId}");
                            } else {
                                error_log("邀请人积分记录插入失败（返回0）：user_id={$inviterId}, code={$inviteCode}, new_user_id={$newUserId}");
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("邀请人积分记录插入异常：user_id={$inviterId}, code={$inviteCode}, new_user_id={$newUserId}, error=" . $e->getMessage());
                        // 不抛出异常，避免影响积分更新
                    }
                } catch (Exception $e) {
                    error_log("邀请人积分发放失败：user_id={$inviterId}, points={$inviterPoints}, code={$inviteCode}, new_user_id={$newUserId}, error=" . $e->getMessage());
                    error_log("堆栈：" . $e->getTraceAsString());
                }
            }
        } catch (Exception $e) {
            // 捕获所有异常，记录日志但不抛出，确保不影响注册流程
            error_log('处理邀请奖励异常：' . $e->getMessage());
            error_log('堆栈：' . $e->getTraceAsString());
        }
    }
    
    /**
     * 获取客户端IP（兼容CDN和反向代理）
     */
    private function getClientIp() {
        return Security::getClientIp();
    }
    
    /**
     * 生成邮箱验证页面的安全token
     */
    private function generateVerifyToken($userId, $username) {
        // 生成随机token
        $token = bin2hex(random_bytes(32));
        $expireTime = time() + 600; // 10分钟有效期
        
        // 存储在session中
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['email_verify_token'] = $token;
        $_SESSION['email_verify_user_id'] = $userId;
        $_SESSION['email_verify_username'] = $username;
        $_SESSION['email_verify_expire'] = $expireTime;
        
        return $token;
    }
    
    /**
     * 验证邮箱验证页面的token
     */
    public function verifyEmailVerifyToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 检查token是否存在
        if (empty($_SESSION['email_verify_token']) || 
            empty($_SESSION['email_verify_user_id']) ||
            empty($_SESSION['email_verify_expire'])) {
            return ['valid' => false, 'message' => '验证token不存在，请重新登录'];
        }
        
        // 检查token是否匹配
        if ($_SESSION['email_verify_token'] !== $token) {
            return ['valid' => false, 'message' => '验证token无效'];
        }
        
        // 检查是否过期
        if (time() > $_SESSION['email_verify_expire']) {
            // 清除过期的token
            unset($_SESSION['email_verify_token']);
            unset($_SESSION['email_verify_user_id']);
            unset($_SESSION['email_verify_username']);
            unset($_SESSION['email_verify_expire']);
            return ['valid' => false, 'message' => '验证token已过期，请重新登录'];
        }
        
        return [
            'valid' => true,
            'user_id' => $_SESSION['email_verify_user_id'],
            'username' => $_SESSION['email_verify_username'] ?? ''
        ];
    }
    
    /**
     * 清除邮箱验证token
     */
    public function clearEmailVerifyToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['email_verify_token']);
        unset($_SESSION['email_verify_user_id']);
        unset($_SESSION['email_verify_username']);
        unset($_SESSION['email_verify_expire']);
    }
    
    /**
     * 修改密码
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // 验证新密码强度
        if (empty($newPassword) || mb_strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '新密码长度至少为6个字符'];
        }
        
        // 获取用户信息（包括邮箱相关字段）
        $user = $this->db->fetchOne(
            "SELECT password, email, email_verified, email_notify_photo, nickname, username FROM users WHERE id = ?", 
            [$userId]
        );
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
        // 验证旧密码
        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => '原密码错误'];
        }
        
        // 加密新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // 更新密码
        $this->db->execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashedPassword, $userId]
        );
        
        // 发送邮件提醒（如果用户开启了邮箱通知）
        if ($user['email_verified'] == 1 && $user['email_notify_photo'] == 1 && !empty($user['email'])) {
            try {
                $changeIp = $this->getClientIp();
                $displayName = $user['nickname'] ?? $user['username'];
                
                $email = new Email();
                $email->sendPasswordChangeNotification($user['email'], $displayName, $changeIp);
            } catch (Exception $e) {
                // 邮件发送失败不影响密码修改流程，只记录错误日志
                error_log('发送密码修改提醒邮件失败：' . $e->getMessage());
            }
        }
        
        return ['success' => true, 'message' => '密码修改成功'];
    }
    
    /**
     * 设置昵称
     */
    public function setNickname($userId, $nickname) {
        $nickname = trim($nickname);
        
        // 验证昵称长度
        if (mb_strlen($nickname) > 18) {
            return ['success' => false, 'message' => '昵称长度不能超过18个字符'];
        }
        
        // 更新昵称
        $this->db->execute(
            "UPDATE users SET nickname = ? WHERE id = ?",
            [$nickname, $userId]
        );
        
        return ['success' => true, 'message' => '昵称设置成功'];
    }
    
    /**
     * 发送邮箱验证码
     */
    public function sendEmailVerificationCode($userId, $email, $type = 'verify') {
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }
        
        // 检查邮箱是否已被其他用户使用
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$email, $userId]
        );
        if ($existing) {
            return ['success' => false, 'message' => '该邮箱已被其他用户使用'];
        }
        
        // 生成6位随机验证码
        $code = sprintf('%06d', mt_rand(0, 999999));
        
        // 设置过期时间（10分钟）
        $expireTime = date('Y-m-d H:i:s', time() + 600);
        
        // 删除该用户之前的验证码
        $this->db->execute(
            "DELETE FROM email_verification_codes WHERE user_id = ? AND type = ? AND used = 0",
            [$userId, $type]
        );
        
        // 保存验证码
        $this->db->execute(
            "INSERT INTO email_verification_codes (user_id, email, code, type, expire_time) VALUES (?, ?, ?, ?, ?)",
            [$userId, $email, $code, $type, $expireTime]
        );
        
        // 发送邮件
        try {
            $emailObj = new Email();
            $sendResult = $emailObj->sendVerificationCode($email, $code);
            
            if ($sendResult) {
                return ['success' => true, 'message' => '验证码已发送到您的邮箱，请查收'];
            } else {
                error_log("邮件发送返回false，邮箱：{$email}");
                return ['success' => false, 'message' => '邮件发送失败，请检查邮件配置或稍后重试'];
            }
        } catch (Exception $e) {
            error_log('发送验证码异常：' . $e->getMessage());
            error_log('堆栈：' . $e->getTraceAsString());
            return ['success' => false, 'message' => '发送验证码失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 验证邮箱验证码并绑定邮箱
     */
    public function verifyEmail($userId, $email, $code, $type = 'verify') {
        // 验证验证码（支持 'verify' 和 'email' 两种类型）
        $verification = $this->db->fetchOne(
            "SELECT * FROM email_verification_codes 
             WHERE user_id = ? AND email = ? AND code = ? AND type = ? AND used = 0 AND expire_time > NOW()",
            [$userId, $email, $code, $type]
        );
        
        if (!$verification) {
            return ['success' => false, 'message' => '验证码错误或已过期'];
        }
        
        // 标记验证码为已使用
        $this->db->execute(
            "UPDATE email_verification_codes SET used = 1 WHERE id = ?",
            [$verification['id']]
        );
        
        // 更新用户邮箱和验证状态
        $this->db->execute(
            "UPDATE users SET email = ?, email_verified = 1 WHERE id = ?",
            [$email, $userId]
        );
        
        return ['success' => true, 'message' => '邮箱验证成功'];
    }
    
    /**
     * 设置邮箱提醒
     */
    public function setEmailNotify($userId, $notifyPhoto) {
        $this->db->execute(
            "UPDATE users SET email_notify_photo = ? WHERE id = ?",
            [$notifyPhoto ? 1 : 0, $userId]
        );
        
        return ['success' => true, 'message' => '设置成功'];
    }
    
    /**
     * 记录登录日志
     */
    private function logLogin($userId, $ip, $ua, $isSuccess, $failReason = null) {
        try {
            $this->db->execute(
                "INSERT INTO login_logs (user_id, login_ip, login_ua, login_time, is_success, fail_reason) 
                 VALUES (?, ?, ?, NOW(), ?, ?)",
                [$userId, $ip, $ua, $isSuccess ? 1 : 0, $failReason]
            );
        } catch (Exception $e) {
            error_log('记录登录日志失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取登录日志
     */
    public function getLoginLogs($userId, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        $logs = $this->db->fetchAll(
            "SELECT * FROM login_logs WHERE user_id = ? 
             ORDER BY login_time DESC 
             LIMIT ? OFFSET ?",
            [$userId, $pageSize, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM login_logs WHERE user_id = ?",
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
     * 检查异常登录（IP地址变化）
     */
    private function checkUnusualLogin($userId, $currentIp) {
        // 获取用户最近一次登录的IP
        $lastLogin = $this->db->fetchOne(
            "SELECT last_login_ip FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$lastLogin || empty($lastLogin['last_login_ip'])) {
            return false; // 首次登录不算异常
        }
        
        // 如果IP地址不同，认为是异常登录
        return $lastLogin['last_login_ip'] !== $currentIp;
    }
    
    /**
     * 检查多次失败登录（异常行为）
     */
    private function checkMultipleFailedLogins($userId, $ip) {
        try {
            // 检查最近5分钟内同一IP或同一用户的失败登录次数
            $recentFailed = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM login_logs 
                 WHERE is_success = 0 
                 AND login_time >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 AND ((? IS NULL AND login_ip = ?) OR (user_id = ?))",
                [$userId, $ip, $userId]
            );
            
            $failedCount = $recentFailed['count'] ?? 0;
            
            // 如果5分钟内失败登录超过5次，记录为异常行为
            if ($failedCount >= 5) {
                require_once __DIR__ . '/Admin.php';
                $adminModel = new Admin();
                $description = $userId ? 
                    "用户ID {$userId} 在5分钟内失败登录 {$failedCount} 次" : 
                    "IP {$ip} 在5分钟内失败登录 {$failedCount} 次";
                
                $adminModel->logAbnormalBehavior(
                    'multiple_failed_login',
                    $userId,
                    $description,
                    3, // 高严重程度
                    $ip,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $_SERVER['REQUEST_URI'] ?? null
                );
            }
        } catch (Exception $e) {
            error_log('检查多次失败登录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 通过邮箱找回密码
     */
    public function resetPasswordByEmail($email, $code, $newPassword) {
        // 验证新密码强度
        if (empty($newPassword) || mb_strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '新密码长度至少为6个字符'];
        }
        
        // 查找用户
        $user = $this->db->fetchOne("SELECT id FROM users WHERE email = ? AND email_verified = 1", [$email]);
        if (!$user) {
            return ['success' => false, 'message' => '该邮箱未绑定或未验证'];
        }
        
        // 验证验证码
        $verification = $this->db->fetchOne(
            "SELECT * FROM email_verification_codes 
             WHERE user_id = ? AND email = ? AND code = ? AND type = 'reset' AND used = 0 AND expire_time > NOW()",
            [$user['id'], $email, $code]
        );
        
        if (!$verification) {
            return ['success' => false, 'message' => '验证码错误或已过期'];
        }
        
        // 标记验证码为已使用
        $this->db->execute(
            "UPDATE email_verification_codes SET used = 1 WHERE id = ?",
            [$verification['id']]
        );
        
        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashedPassword, $user['id']]
        );
        
        return ['success' => true, 'message' => '密码重置成功'];
    }
    
    /**
     * 生成或获取用户的注册码
     * @param int $userId 用户ID
     * @return string 注册码
     */
    public function getOrGenerateRegisterCode($userId) {
        // 先检查用户是否已有注册码
        $user = $this->db->fetchOne(
            "SELECT register_code FROM users WHERE id = ?",
            [$userId]
        );
        
        if ($user && !empty($user['register_code'])) {
            return $user['register_code'];
        }
        
        // 如果没有，生成新的注册码
        $registerCode = $this->generateRegisterCode();
        
        // 保存到数据库
        $this->db->execute(
            "UPDATE users SET register_code = ? WHERE id = ?",
            [$registerCode, $userId]
        );
        
        return $registerCode;
    }
    
    /**
     * 生成唯一的注册码（6位数字字母）
     */
    private function generateRegisterCode() {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxAttempts = 10; // 最多尝试10次
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            // 生成6位随机字符串
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // 检查是否已存在
            $existing = $this->db->fetchOne(
                "SELECT id FROM users WHERE register_code = ?",
                [$code]
            );
            
            if (!$existing) {
                return $code;
            }
        }
        
        // 如果10次都冲突，使用时间戳+随机数作为后备方案
        $code = '';
        for ($j = 0; $j < 6; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * 根据注册码获取用户信息（用于注册时验证）
     * @param string $registerCode 注册码（6位）
     * @return array|null 用户信息或null
     */
    public function getUserByRegisterCode($registerCode) {
        // 验证注册码长度（必须是6位）
        if (strlen($registerCode) !== 6) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT id, username, register_code FROM users WHERE register_code = ? AND status = 1",
            [$registerCode]
        );
    }
}
