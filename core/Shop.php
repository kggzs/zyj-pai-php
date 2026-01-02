<?php
/**
 * 积分商城管理类
 */
class Shop {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 添加商品
     */
    public function addProduct($name, $description, $type, $pointsPrice, $value = null, $totalStock = null, $maxPerUser = null, $sortOrder = 0, $descriptionType = 'auto') {
        // 验证商品类型
        $validTypes = ['vip_temporary', 'vip_permanent', 'invite_limit'];
        if (!in_array($type, $validTypes)) {
            return ['success' => false, 'message' => '无效的商品类型'];
        }
        
        // 验证必填字段
        if (empty($name) || $pointsPrice <= 0) {
            return ['success' => false, 'message' => '商品名称和积分价格不能为空'];
        }
        
        // 对于有数值的商品类型，验证value
        if (in_array($type, ['vip_temporary', 'invite_limit']) && ($value === null || $value <= 0)) {
            return ['success' => false, 'message' => '该商品类型需要设置数值'];
        }
        
        // 验证描述类型
        if (!in_array($descriptionType, ['plain', 'html', 'markdown', 'auto'])) {
            $descriptionType = 'auto';
        }
        
        try {
            $this->db->execute(
                "INSERT INTO points_shop (name, description, description_type, type, points_price, value, total_stock, max_per_user, sort_order, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$name, $description, $descriptionType, $type, $pointsPrice, $value, $totalStock, $maxPerUser, $sortOrder]
            );
            
            return ['success' => true, 'message' => '商品添加成功', 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            error_log('添加商品失败：' . $e->getMessage());
            return ['success' => false, 'message' => '添加失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 更新商品
     */
    public function updateProduct($id, $name, $description, $type, $pointsPrice, $value = null, $totalStock = null, $maxPerUser = null, $sortOrder = 0, $status = 1, $descriptionType = 'auto') {
        // 验证商品是否存在
        $product = $this->db->fetchOne("SELECT id FROM points_shop WHERE id = ?", [$id]);
        if (!$product) {
            return ['success' => false, 'message' => '商品不存在'];
        }
        
        // 验证商品类型
        $validTypes = ['vip_temporary', 'vip_permanent', 'invite_limit'];
        if (!in_array($type, $validTypes)) {
            return ['success' => false, 'message' => '无效的商品类型'];
        }
        
        // 验证描述类型
        if (!in_array($descriptionType, ['plain', 'html', 'markdown', 'auto'])) {
            $descriptionType = 'auto';
        }
        
        try {
            $this->db->execute(
                "UPDATE points_shop SET name = ?, description = ?, description_type = ?, type = ?, points_price = ?, value = ?, 
                 total_stock = ?, max_per_user = ?, sort_order = ?, status = ? WHERE id = ?",
                [$name, $description, $descriptionType, $type, $pointsPrice, $value, $totalStock, $maxPerUser, $sortOrder, $status, $id]
            );
            
            return ['success' => true, 'message' => '商品更新成功'];
        } catch (Exception $e) {
            error_log('更新商品失败：' . $e->getMessage());
            return ['success' => false, 'message' => '更新失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 删除商品
     */
    public function deleteProduct($id) {
        try {
            // 检查是否有兑换记录
            $exchangeCount = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM points_exchange_log WHERE shop_id = ?",
                [$id]
            );
            
            if (($exchangeCount['total'] ?? 0) > 0) {
                // 如果有兑换记录，只下架商品
                $this->db->execute("UPDATE points_shop SET status = 0 WHERE id = ?", [$id]);
                return ['success' => true, 'message' => '商品已下架（存在兑换记录，无法删除）'];
            } else {
                // 没有兑换记录，可以删除
                $this->db->execute("DELETE FROM points_shop WHERE id = ?", [$id]);
                return ['success' => true, 'message' => '商品删除成功'];
            }
        } catch (Exception $e) {
            error_log('删除商品失败：' . $e->getMessage());
            return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 上架/下架商品
     */
    public function toggleStatus($id, $status) {
        try {
            $this->db->execute("UPDATE points_shop SET status = ? WHERE id = ?", [$status, $id]);
            return ['success' => true, 'message' => $status == 1 ? '商品已上架' : '商品已下架'];
        } catch (Exception $e) {
            error_log('更新商品状态失败：' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败'];
        }
    }
    
    /**
     * 获取商品列表（管理员）
     */
    public function getProductList($page = 1, $pageSize = 20, $status = null) {
        $offset = ($page - 1) * $pageSize;
        $where = "1=1";
        $params = [];
        
        if ($status !== null) {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        $products = $this->db->fetchAll(
            "SELECT * FROM points_shop WHERE {$where} ORDER BY sort_order DESC, id DESC LIMIT ? OFFSET ?",
            $params
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM points_shop WHERE {$where}",
            array_slice($params, 0, -2)
        );
        
        return [
            'list' => $products,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取商品详情
     */
    public function getProduct($id) {
        return $this->db->fetchOne("SELECT * FROM points_shop WHERE id = ?", [$id]);
    }
    
    /**
     * 获取上架商品列表（用户端）
     */
    public function getAvailableProducts() {
        return $this->db->fetchAll(
            "SELECT * FROM points_shop WHERE status = 1 ORDER BY sort_order DESC, id DESC"
        );
    }
    
    /**
     * 检查用户是否可以兑换商品
     */
    private function canExchange($userId, $productId) {
        $product = $this->getProduct($productId);
        if (!$product) {
            return ['can' => false, 'message' => '商品不存在'];
        }
        
        if ($product['status'] != 1) {
            return ['can' => false, 'message' => '商品已下架'];
        }
        
        // 检查库存
        if ($product['total_stock'] !== null) {
            $remainingStock = $product['total_stock'] - $product['sold_count'];
            if ($remainingStock <= 0) {
                return ['can' => false, 'message' => '商品已售罄'];
            }
        }
        
        // 检查用户积分
        $pointsModel = new Points();
        $userPoints = $pointsModel->getUserPoints($userId);
        if ($userPoints < $product['points_price']) {
            return ['can' => false, 'message' => '积分不足'];
        }
        
        // 检查用户兑换次数限制
        if ($product['max_per_user'] !== null) {
            $userExchangeCount = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM points_exchange_log WHERE user_id = ? AND shop_id = ? AND status = 'completed'",
                [$userId, $productId]
            );
            
            if (($userExchangeCount['total'] ?? 0) >= $product['max_per_user']) {
                return ['can' => false, 'message' => '您已达到该商品的兑换上限'];
            }
        }
        
        return ['can' => true];
    }
    
    /**
     * 兑换商品
     */
    public function exchangeProduct($userId, $productId) {
        try {
            // 开始事务
            $this->db->beginTransaction();
            
            // 检查是否可以兑换
            $checkResult = $this->canExchange($userId, $productId);
            if (!$checkResult['can']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => $checkResult['message']];
            }
            
            $product = $this->getProduct($productId);
            $pointsModel = new Points();
            
            // 扣除积分
            $this->db->execute(
                "UPDATE users SET points = points - ? WHERE id = ?",
                [$product['points_price'], $userId]
            );
            
            // 记录积分变动
            $this->db->execute(
                "INSERT INTO points_log (user_id, type, points, remark, create_time) 
                 VALUES (?, 'shop_exchange', ?, ?, NOW())",
                [$userId, -$product['points_price'], '兑换商品：' . $product['name']]
            );
            
            // 处理兑换结果
            $result = $this->processExchangeResult($userId, $product);
            
            // 更新商品销量
            $this->db->execute(
                "UPDATE points_shop SET sold_count = sold_count + 1 WHERE id = ?",
                [$productId]
            );
            
            // 记录兑换日志
            $this->db->execute(
                "INSERT INTO points_exchange_log (user_id, shop_id, shop_name, points_cost, status, result, exchange_time) 
                 VALUES (?, ?, ?, ?, 'completed', ?, NOW())",
                [$userId, $productId, $product['name'], $product['points_price'], json_encode($result, JSON_UNESCAPED_UNICODE)]
            );
            
            $this->db->commit();
            
            return ['success' => true, 'message' => '兑换成功', 'result' => $result];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('兑换商品失败：' . $e->getMessage());
            return ['success' => false, 'message' => '兑换失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 处理兑换结果（根据商品类型执行相应操作）
     */
    private function processExchangeResult($userId, $product) {
        $result = ['type' => $product['type']];
        
        switch ($product['type']) {
            case 'vip_temporary':
                // 临时VIP
                $days = $product['value'];
                $expireTime = date('Y-m-d H:i:s', strtotime("+{$days} days"));
                
                // 检查用户是否已有VIP
                $user = $this->db->fetchOne("SELECT is_vip, vip_expire_time FROM users WHERE id = ?", [$userId]);
                
                if (($user['is_vip'] ?? 0) == 1 && $user['vip_expire_time'] !== null) {
                    // 如果已有VIP且未过期，延长到期时间
                    $currentExpire = strtotime($user['vip_expire_time']);
                    if ($currentExpire > time()) {
                        $expireTime = date('Y-m-d H:i:s', $currentExpire + ($days * 86400));
                    }
                }
                
                $this->db->execute(
                    "UPDATE users SET is_vip = 1, vip_expire_time = ? WHERE id = ?",
                    [$expireTime, $userId]
                );
                
                $result['message'] = "VIP会员已激活，有效期至 {$expireTime}";
                $result['expire_time'] = $expireTime;
                break;
                
            case 'vip_permanent':
                // 永久VIP
                $this->db->execute(
                    "UPDATE users SET is_vip = 1, vip_expire_time = NULL WHERE id = ?",
                    [$userId]
                );
                
                $result['message'] = "永久VIP会员已激活";
                break;
                
            case 'invite_limit':
                // 增加邀请链接数量配额
                $bonusCount = $product['value'] ?? 1;
                $this->db->execute(
                    "UPDATE users SET invite_quota_bonus = invite_quota_bonus + ? WHERE id = ?",
                    [$bonusCount, $userId]
                );
                
                // 获取更新后的配额
                $user = $this->db->fetchOne(
                    "SELECT invite_quota_bonus FROM users WHERE id = ?",
                    [$userId]
                );
                $totalBonus = $user['invite_quota_bonus'] ?? 0;
                
                $result['message'] = "邀请链接数量配额已增加 {$bonusCount} 个，当前总配额：{$totalBonus} 个";
                $result['bonus_count'] = $bonusCount;
                $result['total_bonus'] = $totalBonus;
                break;
        }
        
        return $result;
    }
    
    /**
     * 获取用户对某个商品的兑换次数
     */
    public function getUserExchangeCount($userId, $productId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM points_exchange_log WHERE user_id = ? AND shop_id = ? AND status = 'completed'",
            [$userId, $productId]
        );
        return $result['total'] ?? 0;
    }
    
    /**
     * 获取用户兑换记录
     */
    public function getUserExchangeLog($userId, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        $logs = $this->db->fetchAll(
            "SELECT * FROM points_exchange_log WHERE user_id = ? ORDER BY exchange_time DESC LIMIT ? OFFSET ?",
            [$userId, $pageSize, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM points_exchange_log WHERE user_id = ?",
            [$userId]
        );
        
        // 解析result JSON
        foreach ($logs as &$log) {
            if (!empty($log['result'])) {
                $log['result_data'] = json_decode($log['result'], true);
            }
        }
        
        return [
            'list' => $logs,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取兑换统计（管理员）
     */
    public function getExchangeStatistics() {
        $totalExchanges = $this->db->fetchOne("SELECT COUNT(*) as total FROM points_exchange_log");
        $totalPointsSpent = $this->db->fetchOne("SELECT SUM(points_cost) as total FROM points_exchange_log");
        
        return [
            'total_exchanges' => $totalExchanges['total'] ?? 0,
            'total_points_spent' => $totalPointsSpent['total'] ?? 0
        ];
    }
}

