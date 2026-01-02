<?php
/**
 * 管理员管理类
 */
class Admin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 管理员登录
     */
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? AND is_admin = 1 AND status = 1",
            [$username]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => '用户名或密码错误，或该账号不是管理员'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }
        
        // 更新登录信息
        $loginIp = $this->getClientIp();
        $loginTime = date('Y-m-d H:i:s');
        $this->db->execute(
            "UPDATE users SET last_login_time = ?, last_login_ip = ? WHERE id = ?",
            [$loginTime, $loginIp, $user['id']]
        );
        
        // 重新生成Session ID以防止会话固定攻击
        session_regenerate_id(true);
        
        // 设置Session
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        
        return ['success' => true, 'message' => '登录成功', 'user' => $user];
    }
    
    /**
     * 检查是否已登录管理员
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        // 验证管理员身份是否仍然有效
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE id = ? AND is_admin = 1 AND status = 1",
            [$_SESSION['admin_id']]
        );
        
        return $user !== false;
    }
    
    /**
     * 获取用户列表（分页）
     */
    public function getUserList($page = 1, $pageSize = 20, $search = '') {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            // 支持搜索用户名、昵称、邮箱、IP、注册码（6位）、拍摄链接码（8位）
            $searchLen = strlen($search);
            if ($searchLen === 6 || $searchLen === 8) {
                // 如果是6位或8位，可能是注册码或拍摄链接码
                // 6位：搜索注册码
                // 8位：搜索拍摄链接码（通过invites表关联）
                if ($searchLen === 6) {
                    $where .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ? OR register_ip LIKE ? OR register_code = ?)";
                    $searchParam = "%{$search}%";
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $search; // 精确匹配注册码
                } else {
                    // 8位：搜索拍摄链接码（通过子查询查找拥有该拍摄链接码的用户）
                    $where .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ? OR register_ip LIKE ? OR id IN (SELECT user_id FROM invites WHERE invite_code = ?))";
                    $searchParam = "%{$search}%";
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $search; // 精确匹配拍摄链接码
                }
            } else {
                $where .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ? OR register_ip LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
        }
        
        $sql = "SELECT id, username, nickname, email, email_verified, email_notify_photo, 
                       register_ip, register_ua, register_time, 
                       last_login_time, last_login_ip, status, points, is_admin, 
                       is_vip, vip_expire_time
                FROM users WHERE {$where} ORDER BY register_time DESC LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $users = $this->db->fetchAll($sql, $params);
        
        $countSql = "SELECT COUNT(*) as total FROM users WHERE {$where}";
        $countParams = [];
        if (!empty($search)) {
            $searchLen = strlen($search);
            if ($searchLen === 6 || $searchLen === 8) {
                if ($searchLen === 6) {
                    $searchParam = "%{$search}%";
                    $countParams[] = $searchParam;
                    $countParams[] = $searchParam;
                    $countParams[] = $searchParam;
                    $countParams[] = $searchParam;
                    $countParams[] = $search; // 精确匹配注册码
                } else {
                    $searchParam = "%{$search}%";
                    $countParams[] = $searchParam;
                    $countParams[] = $searchParam;
                    $countParams[] = $searchParam;
                    $countParams[] = $searchParam;
                    $countParams[] = $search; // 精确匹配拍摄链接码
                }
            } else {
                $searchParam = "%{$search}%";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
            }
        }
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $users,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取用户详情（包括邀请用户、邀请链接）
     */
    public function getUserDetail($userId) {
        // 获取用户基本信息
        $user = $this->db->fetchOne(
            "SELECT id, username, nickname, email, email_verified, email_notify_photo,
                    register_ip, register_ua, register_time, 
                    last_login_time, last_login_ip, status, points, is_admin,
                    is_vip, vip_expire_time, register_code
             FROM users WHERE id = ?",
            [$userId]
        );
        
        // 如果用户没有注册码，生成一个
        if ($user && empty($user['register_code'])) {
            require_once __DIR__ . '/User.php';
            $userModel = new User();
            $registerCode = $userModel->getOrGenerateRegisterCode($userId);
            $user['register_code'] = $registerCode;
        }
        
        if (!$user) {
            return null;
        }
        
        // 获取用户的邀请链接
        $invites = $this->db->fetchAll(
            "SELECT * FROM invites WHERE user_id = ? ORDER BY create_time DESC",
            [$userId]
        );
        
        // 获取通过邀请码注册的用户（邀请的用户）
        // 注意：邀请人记录的 invite_code 可能带有 '_inviter' 后缀，需要处理
        $invitedUsers = $this->db->fetchAll(
            "SELECT u.id, u.username, u.nickname, u.register_time, u.register_ip, 
                    CASE 
                        WHEN pl.invite_code LIKE '%_inviter' THEN REPLACE(pl.invite_code, '_inviter', '')
                        ELSE pl.invite_code
                    END as invite_code,
                    pl.create_time as reward_time
             FROM points_log pl
             LEFT JOIN users u ON pl.new_user_id = u.id
             WHERE pl.user_id = ? AND pl.type = 'invite_reward' AND pl.remark = '邀请新用户注册奖励'
             ORDER BY pl.create_time DESC",
            [$userId]
        );
        
        // 获取用户上传的照片数量
        $photoCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE user_id = ?",
            [$userId]
        );
        
        // 获取用户登录日志（最近20条）
        $loginLogs = $this->db->fetchAll(
            "SELECT * FROM login_logs WHERE user_id = ? 
             ORDER BY login_time DESC LIMIT 20",
            [$userId]
        );
        
        // 获取用户签到记录（最近30条）
        $checkins = $this->db->fetchAll(
            "SELECT * FROM checkins WHERE user_id = ? 
             ORDER BY checkin_date DESC LIMIT 30",
            [$userId]
        );
        
        // 获取用户积分变动明细（最近50条）
        require_once __DIR__ . '/Points.php';
        $pointsModel = new Points();
        $pointsLog = $pointsModel->getPointsLog($userId, 1, 50);
        
        $user['invites'] = $invites;
        $user['invited_users'] = $invitedUsers;
        $user['photo_count'] = $photoCount['total'] ?? 0;
        $user['login_logs'] = $loginLogs;
        $user['checkins'] = $checkins;
        $user['points_log'] = $pointsLog;
        
        return $user;
    }
    
    /**
     * 调整用户积分（管理员操作）
     */
    public function adjustUserPoints($userId, $points, $remark = '') {
        if (empty($remark)) {
            $remark = $points > 0 ? '管理员增加积分' : '管理员减少积分';
        }
        
        // 获取当前积分
        $user = $this->db->fetchOne(
            "SELECT points FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
        // 更新用户积分
        $newPoints = $user['points'] + $points;
        if ($newPoints < 0) {
            return ['success' => false, 'message' => '积分不能为负数'];
        }
        
        $this->db->execute(
            "UPDATE users SET points = ? WHERE id = ?",
            [$newPoints, $userId]
        );
        
        // 记录积分变动
        $this->db->execute(
            "INSERT INTO points_log (user_id, type, points, remark, create_time) 
             VALUES (?, 'admin_adjust', ?, ?, NOW())",
            [$userId, $points, $remark]
        );
        
        // 记录操作日志
        $this->logOperation(
            'points_adjust',
            'user',
            $userId,
            "调整用户积分：用户ID {$userId}，调整 {$points} 积分，原积分 {$user['points']}，新积分 {$newPoints}"
        );
        
        return [
            'success' => true, 
            'message' => '积分调整成功',
            'old_points' => $user['points'],
            'new_points' => $newPoints,
            'adjust_points' => $points
        ];
    }
    
    /**
     * 封禁/解封用户
     */
    public function banUser($userId, $status) {
        $status = $status == 1 ? 1 : 0;
        
        // 不能封禁管理员
        $user = $this->db->fetchOne(
            "SELECT is_admin FROM users WHERE id = ?",
            [$userId]
        );
        
        if ($user && $user['is_admin'] == 1 && $status == 0) {
            return ['success' => false, 'message' => '不能封禁管理员账号'];
        }
        
        $this->db->execute(
            "UPDATE users SET status = ? WHERE id = ?",
            [$status, $userId]
        );
        
        // 记录操作日志
        $this->logOperation(
            $status == 0 ? 'user_ban' : 'user_unban',
            'user',
            $userId,
            $status == 0 ? "封禁用户 ID: {$userId}" : "解封用户 ID: {$userId}"
        );
        
        return ['success' => true, 'message' => $status == 0 ? '用户已封禁' : '用户已解封'];
    }
    
    /**
     * 获取所有照片列表（分页）
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $userId 用户ID筛选（可选）
     * @param string|null $username 用户名筛选（可选）
     * @param string|null $inviteCode 邀请码筛选（可选）
     */
    public function getAllPhotos($page = 1, $pageSize = 20, $userId = null, $username = null, $inviteCode = null) {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        $userIds = null;
        
        // 如果提供了用户名，查找匹配的用户ID列表
        if ($username && !$userId) {
            $users = $this->db->fetchAll("SELECT id FROM users WHERE username LIKE ?", ["%{$username}%"]);
            if (empty($users)) {
                // 如果没有找到用户，返回空结果
                return [
                    'list' => [],
                    'total' => 0,
                    'page' => $page,
                    'page_size' => $pageSize
                ];
            }
            // 提取用户ID列表
            $userIds = array_column($users, 'id');
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $where .= " AND p.user_id IN ({$placeholders})";
            $params = array_merge($params, $userIds);
        } elseif ($userId) {
            $where .= " AND p.user_id = ?";
            $params[] = $userId;
        }
        
        // 按邀请码筛选
        if ($inviteCode) {
            $where .= " AND p.invite_code = ?";
            $params[] = $inviteCode;
        }
        
        // 管理员可以看到所有照片，包括用户已软删除的（不排除软删除）
        
        // 构建计数SQL
        $countWhere = "1=1";
        $countParams = [];
        
        if ($userIds !== null) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $countWhere .= " AND p.user_id IN ({$placeholders})";
            $countParams = $userIds;
        } elseif ($userId) {
            $countWhere .= " AND p.user_id = ?";
            $countParams[] = $userId;
        }
        
        // 按邀请码筛选
        if ($inviteCode) {
            $countWhere .= " AND p.invite_code = ?";
            $countParams[] = $inviteCode;
        }
        
        // 管理员可以看到所有照片，包括用户已软删除的（不排除软删除）
        
        $countSql = "SELECT COUNT(*) as total FROM photos p WHERE {$countWhere}";
        $total = $this->db->fetchOne($countSql, $countParams);
        
        // 构建查询SQL（包含邀请码标签）
        $sql = "SELECT p.*, u.username as user_name, i.label as invite_label
                FROM photos p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN invites i ON p.invite_code = i.invite_code
                WHERE {$where}
                ORDER BY 
                    CASE WHEN i.label IS NOT NULL AND i.label != '' THEN 0 ELSE 1 END,
                    i.label ASC,
                    p.upload_time DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $photos = $this->db->fetchAll($sql, $params);
        
        return [
            'list' => $photos,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取统计数据
     */
    public function getStatistics() {
        $cache = Cache::getInstance();
        
        // 使用缓存，缓存5分钟
        $cachedData = $cache->remember('admin_statistics', function() {
            return $this->fetchStatistics();
        }, 300);
        
        // 文件大小统计不缓存，每次都重新计算以确保准确性（文件可能随时变化）
        $cachedData['total_photo_size'] = $this->calculateTotalPhotoSize();
        
        // 缓存统计不缓存，每次都重新计算
        $cachedData['cache_stats'] = $this->getCacheStatistics();
        
        return $cachedData;
    }
    
    /**
     * 获取缓存统计信息
     */
    private function getCacheStatistics() {
        $cache = Cache::getInstance();
        $cacheDir = __DIR__ . '/../cache/';
        
        $stats = [
            'file_count' => 0,
            'total_size' => 0,
            'memory_count' => 0
        ];
        
        // 统计文件缓存
        if (is_dir($cacheDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'cache') {
                    $stats['file_count']++;
                    $stats['total_size'] += $file->getSize();
                }
            }
        }
        
        // 统计内存缓存（通过反射获取私有属性）
        try {
            $reflection = new \ReflectionClass($cache);
            $memoryCacheProperty = $reflection->getProperty('memoryCache');
            $memoryCacheProperty->setAccessible(true);
            $memoryCache = $memoryCacheProperty->getValue($cache);
            $stats['memory_count'] = count($memoryCache);
        } catch (\Exception $e) {
            // 如果无法获取内存缓存，忽略
        }
        
        return $stats;
    }
    
    /**
     * 获取统计数据（实际查询）
     */
    private function fetchStatistics() {
        // 总用户数
        $totalUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users");
        
        // 正常用户数
        $activeUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 1");
        
        // 封禁用户数
        $bannedUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 0");
        
        // VIP用户数（包括永久VIP和未过期的VIP）
        // 注意：MySQL中TINYINT(1)可能在PDO中返回字符串'1'或整数1，需要兼容处理
        // 使用CAST确保类型一致，或者使用更宽松的条件
        // 使用更宽松的条件匹配，兼容不同MySQL版本和PDO返回类型
        $vipUsers = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM users 
             WHERE (is_vip = 1 OR is_vip = '1' OR CAST(is_vip AS UNSIGNED) = 1) 
             AND (vip_expire_time IS NULL OR vip_expire_time > NOW())"
        );
        
        // 调试：如果查询结果为0，检查是否确实有VIP用户
        if (($vipUsers['total'] ?? 0) == 0) {
            $allVipCheck = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM users WHERE (is_vip = 1 OR is_vip = '1' OR CAST(is_vip AS UNSIGNED) = 1)"
            );
            if (($allVipCheck['total'] ?? 0) > 0) {
                error_log("VIP统计调试：数据库中有 " . $allVipCheck['total'] . " 个标记为VIP的用户，但有效VIP为0（可能都已过期或时间格式问题）");
            }
        }
        
        // 总照片数
        $totalPhotos = $this->db->fetchOne("SELECT COUNT(*) as total FROM photos");
        
        // 总拍摄链接数
        $totalInvites = $this->db->fetchOne("SELECT COUNT(*) as total FROM invites");
        
        // 有效拍摄链接数
        $activeInvites = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM invites WHERE status = 1 AND expire_time > NOW()"
        );
        
        // 总积分
        $totalPoints = $this->db->fetchOne("SELECT SUM(points) as total FROM users");
        
        // 今日注册用户数
        $todayRegisters = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM users WHERE DATE(register_time) = CURDATE()"
        );
        
        // 今日上传照片数
        $todayPhotos = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE DATE(upload_time) = CURDATE()"
        );
        
        // 最近7天注册趋势
        $registerTrend = $this->db->fetchAll(
            "SELECT DATE(register_time) as date, COUNT(*) as count 
             FROM users 
             WHERE register_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(register_time)
             ORDER BY date ASC"
        );
        
        // 浏览器统计（根据upload_ua字段统计）
        // 注意：统计顺序很重要，优先统计特殊标识的浏览器
        $browserStats = [];
        
        // 微信浏览器统计（优先，因为它是特殊标识）
        $wechatCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%MicroMessenger%'"
        );
        $browserStats['微信浏览器'] = $wechatCount['total'] ?? 0;
        
        // iOS浏览器统计（iPhone/iPad，排除微信）
        $iosBrowserCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE (upload_ua LIKE '%iPhone%' OR upload_ua LIKE '%iPad%') AND upload_ua LIKE '%Mobile%' AND upload_ua NOT LIKE '%MicroMessenger%'"
        );
        $browserStats['iOS浏览器'] = $iosBrowserCount['total'] ?? 0;
        
        // Android浏览器统计（移动设备，排除微信）
        $androidBrowserCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%Android%' AND upload_ua LIKE '%Mobile%' AND upload_ua NOT LIKE '%MicroMessenger%'"
        );
        $browserStats['Android浏览器'] = $androidBrowserCount['total'] ?? 0;
        
        // Edge浏览器统计（排除移动设备）
        $edgeCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%Edg%' AND upload_ua NOT LIKE '%Mobile%'"
        );
        $browserStats['Edge'] = $edgeCount['total'] ?? 0;
        
        // Opera浏览器统计（排除移动设备）
        $operaCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%OPR%' AND upload_ua NOT LIKE '%Mobile%'"
        );
        $browserStats['Opera'] = $operaCount['total'] ?? 0;
        
        // Chrome浏览器统计（排除Edge、Opera和移动设备）
        $chromeCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%Chrome%' AND upload_ua NOT LIKE '%Edg%' AND upload_ua NOT LIKE '%OPR%' AND upload_ua NOT LIKE '%Mobile%'"
        );
        $browserStats['Chrome'] = $chromeCount['total'] ?? 0;
        
        // Firefox浏览器统计（排除移动设备）
        $firefoxCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%Firefox%' AND upload_ua NOT LIKE '%Mobile%'"
        );
        $browserStats['Firefox'] = $firefoxCount['total'] ?? 0;
        
        // Safari浏览器统计（桌面版，排除Chrome和移动设备）
        $safariCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua LIKE '%Safari%' AND upload_ua NOT LIKE '%Chrome%' AND upload_ua NOT LIKE '%Mobile%'"
        );
        $browserStats['Safari'] = $safariCount['total'] ?? 0;
        
        // 其他浏览器统计（计算总数减去已统计的）
        $totalWithUa = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM photos WHERE upload_ua IS NOT NULL AND upload_ua != ''"
        );
        $totalWithUaCount = $totalWithUa['total'] ?? 0;
        $sumCounted = $browserStats['Chrome'] + $browserStats['Edge'] + $browserStats['Firefox'] + 
                      $browserStats['Safari'] + $browserStats['Opera'] + $browserStats['微信浏览器'] + 
                      $browserStats['Android浏览器'] + $browserStats['iOS浏览器'];
        $browserStats['其他'] = max(0, $totalWithUaCount - $sumCounted);
        
        // 计算所有照片的总文件大小
        $totalPhotoSize = $this->calculateTotalPhotoSize();
        
        return [
            'total_users' => $totalUsers['total'] ?? 0,
            'active_users' => $activeUsers['total'] ?? 0,
            'banned_users' => $bannedUsers['total'] ?? 0,
            'vip_users' => $vipUsers['total'] ?? 0,
            'total_photos' => $totalPhotos['total'] ?? 0,
            'total_invites' => $totalInvites['total'] ?? 0,
            'active_invites' => $activeInvites['total'] ?? 0,
            'total_points' => $totalPoints['total'] ?? 0,
            'today_registers' => $todayRegisters['total'] ?? 0,
            'today_photos' => $todayPhotos['total'] ?? 0,
            'total_photo_size' => $totalPhotoSize,
            'register_trend' => $registerTrend,
            'browser_stats' => $browserStats
        ];
    }
    
    /**
     * 计算所有照片的总文件大小
     * @return int 总文件大小（字节）
     */
    private function calculateTotalPhotoSize() {
        try {
            $totalSize = 0;
            $basePath = realpath(__DIR__ . '/../');
            
            if (!$basePath) {
                error_log('无法获取项目根路径');
                return 0;
            }
            
            // 直接统计uploads目录下所有文件的大小（最准确的方法）
            $uploadsDirs = [
                $basePath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'original',
                $basePath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'result'
            ];
            
            foreach ($uploadsDirs as $dir) {
                $normalizedDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
                if (is_dir($normalizedDir)) {
                    $dirSize = $this->getDirectorySize($normalizedDir);
                    $totalSize += $dirSize;
                    error_log("统计目录大小：{$normalizedDir} = " . number_format($dirSize) . " 字节");
                } else {
                    error_log("目录不存在：{$normalizedDir}");
                }
            }
            
            error_log("照片总大小统计结果：" . number_format($totalSize) . " 字节 (" . round($totalSize / 1024 / 1024, 2) . " MB)");
            
            return $totalSize;
        } catch (Exception $e) {
            error_log('计算照片文件大小失败：' . $e->getMessage());
            error_log('堆栈：' . $e->getTraceAsString());
            return 0;
        }
    }
    
    /**
     * 递归计算目录大小
     * @param string $directory 目录路径
     * @return int 目录总大小（字节）
     */
    private function getDirectorySize($directory) {
        $size = 0;
        
        if (!is_dir($directory)) {
            return 0;
        }
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $fileSize = $file->getSize();
                    if ($fileSize !== false) {
                        $size += $fileSize;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('计算目录大小失败：' . $directory . ' - ' . $e->getMessage());
            // 如果RecursiveIteratorIterator失败，使用备用方法
            try {
                $files = scandir($directory);
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    
                    $filePath = $directory . DIRECTORY_SEPARATOR . $file;
                    
                    if (is_file($filePath)) {
                        $fileSize = filesize($filePath);
                        if ($fileSize !== false) {
                            $size += $fileSize;
                        }
                    } elseif (is_dir($filePath)) {
                        $size += $this->getDirectorySize($filePath);
                    }
                }
            } catch (Exception $e2) {
                error_log('备用方法也失败：' . $e2->getMessage());
            }
        }
        
        return $size;
    }
    
    /**
     * 清除统计数据缓存
     */
    public function clearStatisticsCache() {
        $cache = Cache::getInstance();
        $cache->delete('admin_statistics');
    }
    
    /**
     * 获取系统配置（带缓存）
     */
    public function getSystemConfig($key = null) {
        $cache = Cache::getInstance();
        
        if ($key === null) {
            // 获取所有配置（缓存1小时）
            return $cache->remember('system_config_all', function() {
                $configs = $this->db->fetchAll("SELECT config_key, config_value FROM system_config");
                $result = [];
                foreach ($configs as $config) {
                    $result[$config['config_key']] = $config['config_value'];
                }
                return $result;
            }, 3600);
        } else {
            // 获取单个配置（缓存1小时）
            return $cache->remember('system_config_' . $key, function() use ($key) {
                $config = $this->db->fetchOne(
                    "SELECT config_value FROM system_config WHERE config_key = ?",
                    [$key]
                );
                return $config ? $config['config_value'] : null;
            }, 3600);
        }
    }
    
    /**
     * 设置系统配置（清除缓存）
     */
    public function setSystemConfig($key, $value, $description = null) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM system_config WHERE config_key = ?",
            [$key]
        );
        
        if ($existing) {
            // 更新
            $this->db->execute(
                "UPDATE system_config SET config_value = ?, description = COALESCE(?, description) WHERE config_key = ?",
                [$value, $description, $key]
            );
        } else {
            // 插入
            $this->db->execute(
                "INSERT INTO system_config (config_key, config_value, description) VALUES (?, ?, ?)",
                [$key, $value, $description]
            );
        }
        
        // 清除配置缓存
        $cache = Cache::getInstance();
        $cache->delete('system_config_all');
        $cache->delete('system_config_' . $key);
        
        return ['success' => true, 'message' => '配置已保存'];
    }
    
    /**
     * 设置配置组（保存到数据库）
     */
    public function setConfigGroup($groupName, $configData) {
        require_once __DIR__ . '/Helper.php';
        Helper::setConfigGroup($groupName, $configData);
        return ['success' => true, 'message' => '配置已保存'];
    }
    
    /**
     * 获取配置组（从数据库读取）
     */
    public function getConfigGroup($groupName) {
        require_once __DIR__ . '/Helper.php';
        return Helper::getConfigGroup($groupName);
    }
    
    /**
     * 获取客户端IP（兼容CDN和反向代理）
     */
    private function getClientIp() {
        return Security::getClientIp();
    }
    
    /**
     * 获取用户登录日志（管理员查看所有用户）
     */
    public function getUserLoginLogs($page = 1, $pageSize = 50, $search = '') {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            // 支持搜索用户ID、用户名、IP
            if (is_numeric($search)) {
                $where .= " AND (ll.user_id = ? OR ll.login_ip LIKE ?)";
                $params[] = (int)$search;
                $params[] = "%{$search}%";
            } else {
                $where .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR ll.login_ip LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
        }
        
        $sql = "SELECT ll.*, u.username, u.nickname
                FROM login_logs ll
                LEFT JOIN users u ON ll.user_id = u.id
                WHERE {$where}
                ORDER BY ll.login_time DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // 计算总数
        $countWhere = "1=1";
        $countParams = [];
        
        if (!empty($search)) {
            if (is_numeric($search)) {
                $countWhere .= " AND (ll.user_id = ? OR ll.login_ip LIKE ?)";
                $countParams[] = (int)$search;
                $countParams[] = "%{$search}%";
            } else {
                $countWhere .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR ll.login_ip LIKE ?)";
                $searchParam = "%{$search}%";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
            }
        }
        
        $countSql = "SELECT COUNT(*) as total
                     FROM login_logs ll
                     LEFT JOIN users u ON ll.user_id = u.id
                     WHERE {$countWhere}";
        
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取用户积分变动日志（管理员查看所有用户）
     */
    public function getUserPointsLogs($page = 1, $pageSize = 50, $search = '') {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            // 支持搜索用户ID、用户名、注册码（6位）、拍摄链接码（8位）
            $searchLen = strlen($search);
            if (is_numeric($search) && $searchLen !== 6 && $searchLen !== 8) {
                $where .= " AND pl.user_id = ?";
                $params[] = (int)$search;
            } else if ($searchLen === 6) {
                // 6位：搜索注册码（通过users表的register_code字段）
                $where .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR pl.invite_code = ? OR pl.user_id IN (SELECT id FROM users WHERE register_code = ?))";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $search; // 精确匹配积分日志中的invite_code字段（可能是注册码）
                $params[] = $search; // 精确匹配用户注册码
            } else if ($searchLen === 8) {
                // 8位：搜索拍摄链接码
                $where .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR pl.invite_code = ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $search; // 精确匹配拍摄链接码
            } else {
                $where .= " AND (u.username LIKE ? OR u.nickname LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
        }
        
        $sql = "SELECT pl.*, 
                       u.username, u.nickname,
                       CASE 
                           WHEN pl.type = 'invite_reward' AND pl.remark = '通过注册码注册奖励' THEN 
                               -- 新用户：通过注册码（users表的register_code字段）查找邀请人
                               (SELECT u2.username FROM users u2 
                                WHERE u2.register_code = pl.invite_code AND u2.status = 1 LIMIT 1)
                           WHEN pl.type = 'invite_reward' AND pl.remark = '通过邀请码注册奖励' THEN 
                               -- 新用户：通过邀请码（invites表）查找邀请人
                               (SELECT u2.username FROM invites i 
                                LEFT JOIN users u2 ON i.user_id = u2.id 
                                WHERE i.invite_code = CASE 
                                    WHEN pl.invite_code LIKE '%_inviter' THEN REPLACE(pl.invite_code, '_inviter', '')
                                    ELSE pl.invite_code
                                END LIMIT 1)
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
                                WHERE i.invite_code = CASE 
                                    WHEN pl.invite_code LIKE '%_inviter' THEN REPLACE(pl.invite_code, '_inviter', '')
                                    ELSE pl.invite_code
                                END LIMIT 1)
                           WHEN pl.type = 'invite_reward' AND pl.remark = '邀请新用户注册奖励' THEN 
                               -- 邀请人：显示被邀请人昵称
                               (SELECT u3.nickname FROM users u3 WHERE u3.id = pl.new_user_id)
                           ELSE 
                               -- 其他类型：显示新用户昵称（如果有）
                               (SELECT u4.nickname FROM users u4 WHERE u4.id = pl.new_user_id)
                       END as related_user_nickname
                FROM points_log pl
                LEFT JOIN users u ON pl.user_id = u.id
                WHERE {$where}
                ORDER BY pl.create_time DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // 计算总数
        $countWhere = "1=1";
        $countParams = [];
        
        if (!empty($search)) {
            $searchLen = strlen($search);
            if (is_numeric($search) && $searchLen !== 6 && $searchLen !== 8) {
                $countWhere .= " AND pl.user_id = ?";
                $countParams[] = (int)$search;
            } else if ($searchLen === 6) {
                // 6位：搜索注册码
                $searchParam = "%{$search}%";
                $countWhere .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR pl.invite_code = ? OR pl.user_id IN (SELECT id FROM users WHERE register_code = ?))";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $search;
                $countParams[] = $search;
            } else if ($searchLen === 8) {
                // 8位：搜索拍摄链接码
                $searchParam = "%{$search}%";
                $countWhere .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR pl.invite_code = ?)";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $search;
            } else {
                $countWhere .= " AND (u.username LIKE ? OR u.nickname LIKE ?)";
                $searchParam = "%{$search}%";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
            }
        }
        
        $countSql = "SELECT COUNT(*) as total
                     FROM points_log pl
                     LEFT JOIN users u ON pl.user_id = u.id
                     WHERE {$countWhere}";
        
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取用户照片上传日志（管理员查看所有用户）
     */
    public function getUserPhotoLogs($page = 1, $pageSize = 50, $search = '') {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            // 支持搜索用户ID、用户名、拍摄链接码（8位）
            $searchLen = strlen($search);
            if (is_numeric($search) && $searchLen !== 8) {
                $where .= " AND p.user_id = ?";
                $params[] = (int)$search;
            } else if ($searchLen === 8) {
                // 8位：精确搜索拍摄链接码
                $where .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR p.invite_code = ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $search; // 精确匹配拍摄链接码
            } else {
                $where .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR p.invite_code LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
        }
        
        $sql = "SELECT p.id, p.invite_code, p.user_id, p.upload_ip, p.upload_ua, p.upload_time,
                       u.username, u.nickname
                FROM photos p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE {$where}
                ORDER BY p.upload_time DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // 计算总数
        $countWhere = "1=1";
        $countParams = [];
        
        if (!empty($search)) {
            $searchLen = strlen($search);
            if (is_numeric($search) && $searchLen !== 8) {
                $countWhere .= " AND p.user_id = ?";
                $countParams[] = (int)$search;
            } else if ($searchLen === 8) {
                // 8位：精确搜索拍摄链接码
                $searchParam = "%{$search}%";
                $countWhere .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR p.invite_code = ?)";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $search; // 精确匹配拍摄链接码
            } else {
                $countWhere .= " AND (u.username LIKE ? OR u.nickname LIKE ? OR p.invite_code LIKE ?)";
                $searchParam = "%{$search}%";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
            }
        }
        
        $countSql = "SELECT COUNT(*) as total
                     FROM photos p
                     LEFT JOIN users u ON p.user_id = u.id
                     WHERE {$countWhere}";
        
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 记录管理员操作日志
     */
    public function logOperation($operationType, $targetType = null, $targetId = null, $description = null) {
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
            return;
        }
        
        $adminId = $_SESSION['admin_id'];
        $adminUsername = $_SESSION['admin_username'];
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            $this->db->execute(
                "INSERT INTO admin_operation_logs (admin_id, admin_username, operation_type, target_type, target_id, description, ip_address, user_agent) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$adminId, $adminUsername, $operationType, $targetType, $targetId, $description, $ip, $userAgent]
            );
        } catch (Exception $e) {
            error_log('记录管理员操作日志失败：' . $e->getMessage());
        }
    }
    
    /**
     * 记录异常行为
     */
    public function logAbnormalBehavior($behaviorType, $userId = null, $description = null, $severity = 1, $ip = null, $userAgent = null, $requestUrl = null, $requestData = null) {
        if ($ip === null) {
            $ip = $this->getClientIp();
        }
        if ($userAgent === null) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        try {
            $requestDataJson = $requestData ? json_encode($requestData, JSON_UNESCAPED_UNICODE) : null;
            
            $this->db->execute(
                "INSERT INTO abnormal_behavior_logs (user_id, behavior_type, description, ip_address, user_agent, request_url, request_data, severity) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $behaviorType, $description, $ip, $userAgent, $requestUrl, $requestDataJson, $severity]
            );
        } catch (Exception $e) {
            error_log('记录异常行为失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取异常行为记录列表
     */
    public function getAbnormalBehaviorLogs($page = 1, $pageSize = 50, $search = '', $severity = null, $isHandled = null) {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            if (is_numeric($search)) {
                $where .= " AND (abl.user_id = ? OR abl.id = ?)";
                $params[] = (int)$search;
                $params[] = (int)$search;
            } else {
                $where .= " AND (abl.description LIKE ? OR abl.behavior_type LIKE ? OR u.username LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
        }
        
        if ($severity !== null) {
            $where .= " AND abl.severity = ?";
            $params[] = (int)$severity;
        }
        
        if ($isHandled !== null) {
            $where .= " AND abl.is_handled = ?";
            $params[] = (int)$isHandled;
        }
        
        $sql = "SELECT abl.*, u.username, u.nickname, 
                       handler.username as handler_username
                FROM abnormal_behavior_logs abl
                LEFT JOIN users u ON abl.user_id = u.id
                LEFT JOIN users handler ON abl.handled_by = handler.id
                WHERE {$where}
                ORDER BY abl.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // 计算总数
        $countSql = "SELECT COUNT(*) as total
                     FROM abnormal_behavior_logs abl
                     LEFT JOIN users u ON abl.user_id = u.id
                     WHERE {$where}";
        $countParams = array_slice($params, 0, -2);
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 标记异常行为为已处理
     */
    public function handleAbnormalBehavior($logId, $note = null) {
        if (!isset($_SESSION['admin_id'])) {
            return ['success' => false, 'message' => '未登录'];
        }
        
        $adminId = $_SESSION['admin_id'];
        $handleTime = date('Y-m-d H:i:s');
        
        $this->db->execute(
            "UPDATE abnormal_behavior_logs 
             SET is_handled = 1, handled_by = ?, handled_time = ?, handled_note = ?
             WHERE id = ?",
            [$adminId, $handleTime, $note, $logId]
        );
        
        return ['success' => true, 'message' => '已标记为已处理'];
    }
    
    /**
     * 获取管理员操作日志列表
     */
    public function getAdminOperationLogs($page = 1, $pageSize = 50, $search = '', $operationType = null) {
        $offset = ($page - 1) * $pageSize;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($search)) {
            if (is_numeric($search)) {
                $where .= " AND (aol.admin_id = ? OR aol.target_id = ? OR aol.id = ?)";
                $params[] = (int)$search;
                $params[] = (int)$search;
                $params[] = (int)$search;
            } else {
                $where .= " AND (aol.admin_username LIKE ? OR aol.description LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
        }
        
        if ($operationType) {
            $where .= " AND aol.operation_type = ?";
            $params[] = $operationType;
        }
        
        $sql = "SELECT aol.*
                FROM admin_operation_logs aol
                WHERE {$where}
                ORDER BY aol.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // 计算总数
        $countSql = "SELECT COUNT(*) as total FROM admin_operation_logs aol WHERE {$where}";
        $countParams = array_slice($params, 0, -2);
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 创建数据库备份
     */
    public function createDatabaseBackup() {
        $config = require __DIR__ . '/../config/database.php';
        
        // 备份文件存储目录
        $backupDir = __DIR__ . '/../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // 生成备份文件名
        $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupFilePath = $backupDir . $backupFileName;
        
        // 构建mysqldump命令
        $host = escapeshellarg($config['host']);
        $dbname = escapeshellarg($config['dbname']);
        $username = escapeshellarg($config['username']);
        $password = escapeshellarg($config['password']);
        
        // 使用mysqldump命令备份数据库
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
            $host,
            $username,
            $password,
            $dbname,
            escapeshellarg($backupFilePath)
        );
        
        // 执行备份命令
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0 || !file_exists($backupFilePath)) {
            return [
                'success' => false,
                'message' => '备份失败：' . implode("\n", $output)
            ];
        }
        
        // 记录操作日志
        $this->logOperation('backup_create', 'system', null, "创建数据库备份：{$backupFileName}");
        
        return [
            'success' => true,
            'message' => '备份成功',
            'file_name' => $backupFileName,
            'file_path' => $backupFilePath,
            'file_size' => filesize($backupFilePath)
        ];
    }
    
    /**
     * 获取备份文件列表
     */
    public function getBackupList() {
        $backupDir = __DIR__ . '/../backups/';
        if (!is_dir($backupDir)) {
            return ['list' => [], 'total' => 0];
        }
        
        $files = [];
        $handle = opendir($backupDir);
        
        while (($file = readdir($handle)) !== false) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $filePath = $backupDir . $file;
                $files[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'created_time' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'path' => $filePath
                ];
            }
        }
        
        closedir($handle);
        
        // 按创建时间倒序排序
        usort($files, function($a, $b) {
            return strtotime($b['created_time']) - strtotime($a['created_time']);
        });
        
        return [
            'list' => $files,
            'total' => count($files)
        ];
    }
    
    /**
     * 恢复数据库备份
     */
    public function restoreDatabaseBackup($backupFileName) {
        $config = require __DIR__ . '/../config/database.php';
        $backupDir = __DIR__ . '/../backups/';
        $backupFilePath = $backupDir . $backupFileName;
        
        // 验证备份文件是否存在
        if (!file_exists($backupFilePath)) {
            return [
                'success' => false,
                'message' => '备份文件不存在'
            ];
        }
        
        // 构建mysql命令
        $host = escapeshellarg($config['host']);
        $dbname = escapeshellarg($config['dbname']);
        $username = escapeshellarg($config['username']);
        $password = escapeshellarg($config['password']);
        
        // 使用mysql命令恢复数据库
        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s 2>&1',
            $host,
            $username,
            $password,
            $dbname,
            escapeshellarg($backupFilePath)
        );
        
        // 执行恢复命令
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            return [
                'success' => false,
                'message' => '恢复失败：' . implode("\n", $output)
            ];
        }
        
        // 记录操作日志
        $this->logOperation('backup_restore', 'system', null, "恢复数据库备份：{$backupFileName}");
        
        return [
            'success' => true,
            'message' => '恢复成功'
        ];
    }
    
    /**
     * 删除备份文件
     */
    public function deleteBackup($backupFileName) {
        $backupDir = __DIR__ . '/../backups/';
        $backupFilePath = $backupDir . $backupFileName;
        
        if (!file_exists($backupFilePath)) {
            return [
                'success' => false,
                'message' => '备份文件不存在'
            ];
        }
        
        if (!unlink($backupFilePath)) {
            return [
                'success' => false,
                'message' => '删除失败'
            ];
        }
        
        // 记录操作日志
        $this->logOperation('backup_delete', 'system', null, "删除数据库备份：{$backupFileName}");
        
        return [
            'success' => true,
            'message' => '删除成功'
        ];
    }
    
    /**
     * 获取系统错误日志（PHP错误日志）
     */
    public function getSystemErrorLogs($lines = 100) {
        $logFile = ini_get('error_log');
        if (empty($logFile) || !file_exists($logFile)) {
            // 尝试常见的日志位置
            $logFile = __DIR__ . '/../logs/error.log';
            if (!file_exists($logFile)) {
                return ['list' => [], 'total' => 0];
            }
        }
        
        // 读取最后N行日志
        $content = file_get_contents($logFile);
        $logLines = explode("\n", $content);
        $logLines = array_filter($logLines, function($line) {
            return !empty(trim($line));
        });
        $logLines = array_slice($logLines, -$lines);
        $logLines = array_reverse($logLines);
        
        $logs = [];
        foreach ($logLines as $line) {
            $logs[] = [
                'content' => $line,
                'time' => $this->extractLogTime($line)
            ];
        }
        
        return [
            'list' => $logs,
            'total' => count($logs)
        ];
    }
    
    /**
     * 从日志行中提取时间
     */
    private function extractLogTime($logLine) {
        // 尝试匹配常见的时间格式
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $logLine, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/', $logLine, $matches)) {
            return str_replace('T', ' ', $matches[1]);
        }
        return '';
    }
}

