<?php
/**
 * 照片管理类
 */
class Photo {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 保存照片信息
     */
    public function savePhoto($inviteId, $inviteCode, $userId, $originalPath, $uploadIp, $uploadUa = null, $exifData = []) {
        $uploadTime = date('Y-m-d H:i:s');
        
        // 构建SQL语句和参数（包含EXIF数据）
        $sql = "INSERT INTO photos (invite_id, invite_code, user_id, original_path, result_path, file_type, upload_ip, upload_ua, upload_time";
        $values = "VALUES (?, ?, ?, ?, '', 'photo', ?, ?, ?";
        $params = [
            $inviteId,
            $inviteCode,
            $userId,
            $originalPath,
            $uploadIp,
            $uploadUa,
            $uploadTime
        ];
        
        // 添加EXIF字段
        $exifFields = [
            'latitude', 'longitude', 'altitude',
            'camera_make', 'camera_model', 'lens_model',
            'focal_length', 'aperture', 'shutter_speed', 'iso',
            'exposure_mode', 'white_balance', 'flash',
            'orientation', 'width', 'height',
            'location_address', 'exif_data'
        ];
        
        foreach ($exifFields as $field) {
            if (isset($exifData[$field])) {
                $sql .= ", `{$field}`";
                $values .= ", ?";
                $params[] = $exifData[$field];
            }
        }
        
        $sql .= ") " . $values . ")";
        
        $this->db->execute($sql, $params);
        
        // 异步清除统计数据缓存（不阻塞响应）
        // 使用register_shutdown_function在响应后执行
        register_shutdown_function(function() {
            try {
                $cache = Cache::getInstance();
                $cache->delete('admin_statistics');
            } catch (Exception $e) {
                // 静默失败，不影响主流程
            }
        });
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 保存录像信息
     */
    public function saveVideo($inviteId, $inviteCode, $userId, $originalPath, $duration, $uploadIp, $uploadUa = null) {
        $uploadTime = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO photos (invite_id, invite_code, user_id, original_path, result_path, file_type, video_duration, upload_ip, upload_ua, upload_time) 
                VALUES (?, ?, ?, ?, '', 'video', ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $inviteId,
            $inviteCode,
            $userId,
            $originalPath,
            $duration,
            $uploadIp,
            $uploadUa,
            $uploadTime
        ]);
        
        // 异步清除统计数据缓存（不阻塞响应）
        register_shutdown_function(function() {
            try {
                $cache = Cache::getInstance();
                $cache->delete('admin_statistics');
            } catch (Exception $e) {
                // 静默失败，不影响主流程
            }
        });
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 获取用户的照片列表（排除软删除的照片）
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string|null $inviteCode 邀请码筛选（可选）
     * @param string|null $tagName 标签筛选（可选）
     */
    public function getUserPhotos($userId, $page = 1, $pageSize = 20, $inviteCode = null, $tagName = null) {
        $offset = ($page - 1) * $pageSize;
        
        $where = "p.user_id = ? AND p.deleted_at IS NULL";
        $params = [$userId];
        
        // 按邀请码筛选
        if ($inviteCode) {
            $where .= " AND p.invite_code = ?";
            $params[] = $inviteCode;
        }
        
        // 按标签筛选
        if ($tagName) {
            $where .= " AND EXISTS (
                SELECT 1 FROM photo_tags pt 
                INNER JOIN tags t ON pt.tag_id = t.id 
                WHERE pt.photo_id = p.id AND t.name = ? AND t.user_id = ?
            )";
            $params[] = $tagName;
            $params[] = $userId;
        }
        
        // 按邀请码标签优先排序，然后按上传时间倒序
        $sql = "SELECT DISTINCT p.*, 
                       COALESCE(i.label, '') as invite_label
                FROM photos p
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
        
        // 获取总数
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM photos p
                     LEFT JOIN invites i ON p.invite_code = i.invite_code
                     WHERE {$where}";
        $countParams = array_slice($params, 0, -2); // 移除 LIMIT 和 OFFSET 参数
        $total = $this->db->fetchOne($countSql, $countParams);
        
        return [
            'list' => $photos,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取用户的所有邀请码列表（用于分组显示）
     */
    public function getUserInviteCodes($userId) {
        return $this->db->fetchAll(
            "SELECT DISTINCT invite_code, invite_id, COUNT(*) as photo_count 
             FROM photos 
             WHERE user_id = ? AND deleted_at IS NULL 
             GROUP BY invite_code, invite_id 
             ORDER BY invite_code",
            [$userId]
        );
    }
    
    /**
     * 根据ID获取照片信息（管理员可查看已删除的照片）
     */
    public function getPhotoById($photoId, $userId = null, $includeDeleted = false) {
        $sql = "SELECT * FROM photos WHERE id = ?";
        $params = [$photoId];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        // 如果不是管理员查询，排除已软删除的照片
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * 用户软删除照片（只标记删除，不删除文件）
     */
    public function softDeletePhoto($photoId, $userId) {
        // 验证照片是否存在且属于该用户
        $photo = $this->getPhotoById($photoId, $userId);
        if (!$photo) {
            return ['success' => false, 'message' => '照片不存在或无权限'];
        }
        
        // 检查是否已经删除
        if ($photo['deleted_at'] !== null) {
            return ['success' => false, 'message' => '照片已被删除'];
        }
        
        // 执行软删除
        $deletedAt = date('Y-m-d H:i:s');
        $this->db->execute(
            "UPDATE photos SET deleted_at = ? WHERE id = ? AND user_id = ?",
            [$deletedAt, $photoId, $userId]
        );
        
        return ['success' => true, 'message' => '照片已删除'];
    }
    
    /**
     * 管理员硬删除照片（删除数据库记录和服务器文件）
     */
    public function hardDeletePhoto($photoId) {
        // 获取照片信息（包括已软删除的）
        $photo = $this->getPhotoById($photoId, null, true);
        if (!$photo) {
            return ['success' => false, 'message' => '照片不存在'];
        }
        
        // 删除服务器上的文件
        $originalPath = __DIR__ . '/../' . ltrim($photo['original_path'], '/');
        $resultPath = __DIR__ . '/../' . ltrim($photo['result_path'], '/');
        
        $filesDeleted = 0;
        if (file_exists($originalPath)) {
            @unlink($originalPath);
            $filesDeleted++;
        }
        if (file_exists($resultPath)) {
            @unlink($resultPath);
            $filesDeleted++;
        }
        
        // 删除数据库记录
        $this->db->execute("DELETE FROM photos WHERE id = ?", [$photoId]);
        
        return [
            'success' => true, 
            'message' => '照片已删除',
            'files_deleted' => $filesDeleted
        ];
    }
    
    /**
     * 为照片添加标签
     */
    public function addTagToPhoto($photoId, $tagName, $userId) {
        // 验证照片是否存在且属于该用户
        $photo = $this->getPhotoById($photoId, $userId);
        if (!$photo) {
            return ['success' => false, 'message' => '照片不存在或无权限'];
        }
        
        // 清理标签名称（去除空格，限制长度）
        $tagName = trim($tagName);
        if (empty($tagName) || mb_strlen($tagName) > 10) {
            return ['success' => false, 'message' => '标签名称无效（最多10个字符）'];
        }
        
        // 获取或创建标签
        $tag = $this->db->fetchOne(
            "SELECT id FROM tags WHERE user_id = ? AND name = ?",
            [$userId, $tagName]
        );
        
        if (!$tag) {
            // 创建新标签
            $this->db->execute(
                "INSERT INTO tags (name, user_id) VALUES (?, ?)",
                [$tagName, $userId]
            );
            $tagId = $this->db->lastInsertId();
        } else {
            $tagId = $tag['id'];
        }
        
        // 检查照片是否已有该标签
        $existing = $this->db->fetchOne(
            "SELECT id FROM photo_tags WHERE photo_id = ? AND tag_id = ?",
            [$photoId, $tagId]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => '照片已有该标签'];
        }
        
        // 添加标签关联
        $this->db->execute(
            "INSERT INTO photo_tags (photo_id, tag_id) VALUES (?, ?)",
            [$photoId, $tagId]
        );
        
        return ['success' => true, 'message' => '标签添加成功', 'tag_id' => $tagId];
    }
    
    /**
     * 移除照片的标签
     */
    public function removeTagFromPhoto($photoId, $tagId, $userId) {
        // 验证照片是否存在且属于该用户
        $photo = $this->getPhotoById($photoId, $userId);
        if (!$photo) {
            return ['success' => false, 'message' => '照片不存在或无权限'];
        }
        
        // 验证标签是否属于该用户
        $tag = $this->db->fetchOne(
            "SELECT id FROM tags WHERE id = ? AND user_id = ?",
            [$tagId, $userId]
        );
        
        if (!$tag) {
            return ['success' => false, 'message' => '标签不存在或无权限'];
        }
        
        // 删除标签关联
        $this->db->execute(
            "DELETE FROM photo_tags WHERE photo_id = ? AND tag_id = ?",
            [$photoId, $tagId]
        );
        
        return ['success' => true, 'message' => '标签已移除'];
    }
    
    /**
     * 获取照片的所有标签
     */
    public function getPhotoTags($photoId, $userId = null) {
        $sql = "SELECT t.id, t.name 
                FROM tags t 
                INNER JOIN photo_tags pt ON t.id = pt.tag_id 
                WHERE pt.photo_id = ?";
        $params = [$photoId];
        
        if ($userId) {
            $sql .= " AND t.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY t.name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 获取用户的所有标签（用于搜索）
     */
    public function getUserTags($userId, $search = null) {
        $sql = "SELECT DISTINCT t.id, t.name, COUNT(pt.photo_id) as photo_count 
                FROM tags t 
                LEFT JOIN photo_tags pt ON t.id = pt.tag_id 
                LEFT JOIN photos p ON pt.photo_id = p.id AND p.deleted_at IS NULL
                WHERE t.user_id = ?";
        $params = [$userId];
        
        if ($search) {
            $sql .= " AND t.name LIKE ?";
            $params[] = "%{$search}%";
        }
        
        $sql .= " GROUP BY t.id, t.name ORDER BY t.name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 批量获取照片的标签（用于列表显示）
     */
    public function getPhotosTags($photoIds) {
        if (empty($photoIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($photoIds), '?'));
        $sql = "SELECT pt.photo_id, t.id as tag_id, t.name as tag_name 
                FROM photo_tags pt 
                INNER JOIN tags t ON pt.tag_id = t.id 
                WHERE pt.photo_id IN ({$placeholders})
                ORDER BY pt.photo_id, t.name";
        
        $tags = $this->db->fetchAll($sql, $photoIds);
        
        // 按照片ID分组
        $result = [];
        foreach ($tags as $tag) {
            $photoId = $tag['photo_id'];
            if (!isset($result[$photoId])) {
                $result[$photoId] = [];
            }
            $result[$photoId][] = [
                'id' => $tag['tag_id'],
                'name' => $tag['tag_name']
            ];
        }
        
        return $result;
    }
    
    /**
     * 批量软删除照片（VIP功能）
     */
    public function batchSoftDeletePhotos($photoIds, $userId) {
        if (empty($photoIds) || !is_array($photoIds)) {
            return ['success' => false, 'message' => '无效的照片ID列表'];
        }
        
        // 验证所有照片是否属于该用户
        $placeholders = implode(',', array_fill(0, count($photoIds), '?'));
        $photos = $this->db->fetchAll(
            "SELECT id, deleted_at FROM photos WHERE id IN ({$placeholders}) AND user_id = ?",
            array_merge($photoIds, [$userId])
        );
        
        if (count($photos) !== count($photoIds)) {
            return ['success' => false, 'message' => '部分照片不存在或无权限'];
        }
        
        // 检查是否有已删除的照片
        $alreadyDeleted = array_filter($photos, function($photo) {
            return $photo['deleted_at'] !== null;
        });
        
        if (!empty($alreadyDeleted)) {
            return ['success' => false, 'message' => '部分照片已被删除'];
        }
        
        // 执行批量软删除
        $deletedAt = date('Y-m-d H:i:s');
        $this->db->execute(
            "UPDATE photos SET deleted_at = ? WHERE id IN ({$placeholders}) AND user_id = ?",
            array_merge([$deletedAt], $photoIds, [$userId])
        );
        
        return ['success' => true, 'message' => '照片已批量删除', 'count' => count($photoIds)];
    }
    
    /**
     * 批量获取照片信息（用于下载）
     */
    public function getPhotosByIds($photoIds, $userId) {
        if (empty($photoIds) || !is_array($photoIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($photoIds), '?'));
        return $this->db->fetchAll(
            "SELECT * FROM photos WHERE id IN ({$placeholders}) AND user_id = ? AND deleted_at IS NULL",
            array_merge($photoIds, [$userId])
        );
    }
}
