<?php
session_start();
require_once __DIR__ . '/core/autoload.php';

$adminModel = new Admin();
if (!$adminModel->isLoggedIn()) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员后台 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="header">
        <h1>管理员后台</h1>
    </div>

    <div class="container">
        <div class="nav">
            <button class="nav-btn active" onclick="showSection('statistics')">数据统计</button>
            <button class="nav-btn" onclick="showSection('users')">用户管理</button>
            <button class="nav-btn" onclick="showSection('photos')">照片管理</button>
            <button class="nav-btn" onclick="showSection('user_logs')">用户历史日志</button>
            <button class="nav-btn" onclick="showSection('announcements')">公告管理</button>
            <button class="nav-btn" onclick="showSection('shop')">积分商城管理</button>
            <button class="nav-btn" onclick="showSection('maintenance')">系统维护</button>
            <button class="nav-btn" onclick="showSection('settings')">系统设置</button>
            <button class="nav-btn nav-btn-logout" onclick="logout()">退出登录</button>
        </div>
        
        <div class="content">
            <!-- 数据统计 -->
            <div id="statistics" class="section active">
                <h2>数据统计</h2>
                <div class="statistics" id="statCards"></div>
            </div>
            
            <!-- 用户管理 -->
            <div id="users" class="section">
                <h2>用户管理</h2>
                <div class="search-box">
                    <input type="text" id="userSearch" placeholder="搜索用户名、昵称、邮箱、IP、注册码（6位）或拍摄链接码（8位）">
                    <button onclick="searchUsers()">搜索</button>
                    <button onclick="resetSearch()">重置</button>
                </div>
                <div id="userList"></div>
            </div>
            
            <!-- 照片管理 -->
            <div id="photos" class="section">
                <h2>照片管理</h2>
                <div class="search-box">
                    <input type="text" id="photoUserSearch" placeholder="搜索用户名">
                    <button onclick="searchPhotos()">搜索</button>
                    <button onclick="resetPhotoSearch()">重置</button>
                </div>
                <div id="photoList"></div>
                <div class="pagination" id="photoPagination"></div>
            </div>
            
            <!-- 用户历史日志 -->
            <div id="user_logs" class="section">
                <h2>用户历史日志</h2>
                <div class="log-tabs">
                    <button class="log-tab active" onclick="showLogTab('login_logs')">登录日志</button>
                    <button class="log-tab" onclick="showLogTab('points_logs')">积分变动日志</button>
                    <button class="log-tab" onclick="showLogTab('photo_logs')">照片上传日志</button>
                </div>
                
                <!-- 登录日志 -->
                <div id="login_logs" class="log-section active">
                    <div class="search-box">
                        <input type="text" id="loginLogSearch" placeholder="搜索用户ID、用户名、IP">
                        <button onclick="searchLoginLogs()">搜索</button>
                        <button onclick="resetLoginLogSearch()">重置</button>
                    </div>
                    <div id="loginLogList"></div>
                    <div class="pagination" id="loginLogPagination"></div>
                </div>
                
                <!-- 积分变动日志 -->
                <div id="points_logs" class="log-section">
                    <div class="search-box">
                        <input type="text" id="pointsLogSearch" placeholder="搜索用户ID、用户名">
                        <button onclick="searchPointsLogs()">搜索</button>
                        <button onclick="resetPointsLogSearch()">重置</button>
                    </div>
                    <div id="pointsLogList"></div>
                    <div class="pagination" id="pointsLogPagination"></div>
                </div>
                
                <!-- 照片上传日志 -->
                <div id="photo_logs" class="log-section">
                    <div class="search-box">
                        <input type="text" id="photoLogSearch" placeholder="搜索用户ID、用户名、拍摄链接码（8位）">
                        <button onclick="searchPhotoLogs()">搜索</button>
                        <button onclick="resetPhotoLogSearch()">重置</button>
                    </div>
                    <div id="photoLogList"></div>
                    <div class="pagination" id="photoLogPagination"></div>
                </div>
            </div>
            
            <!-- 系统维护 -->
            <div id="maintenance" class="section">
                <h2>系统维护</h2>
                <div class="maintenance-tabs">
                    <button class="maintenance-tab active" onclick="showMaintenanceTab('backup')">数据库备份/恢复</button>
                    <button class="maintenance-tab" onclick="showMaintenanceTab('system_logs')">系统日志</button>
                    <button class="maintenance-tab" onclick="showMaintenanceTab('admin_logs')">管理员操作日志</button>
                    <button class="maintenance-tab" onclick="showMaintenanceTab('abnormal_behavior')">异常行为记录</button>
                </div>
                
                <!-- 数据库备份/恢复 -->
                <div id="backup" class="maintenance-section active">
                    <div class="maintenance-card">
                        <h3>数据库备份</h3>
                        <div style="margin-bottom: 20px;">
                            <button class="btn btn-primary" onclick="createBackup()">创建备份</button>
                            <p style="margin-top: 10px; color: #666; font-size: 13px;">备份文件将保存在 backups/ 目录下</p>
                        </div>
                        <div id="backupList"></div>
                    </div>
                </div>
                
                <!-- 系统日志 -->
                <div id="system_logs" class="maintenance-section">
                    <div class="maintenance-card">
                        <h3>系统错误日志</h3>
                        <div style="margin-bottom: 15px;">
                            <label>显示行数：</label>
                            <select id="errorLogLines" onchange="loadSystemErrorLogs()" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="50">最近50行</option>
                                <option value="100" selected>最近100行</option>
                                <option value="200">最近200行</option>
                                <option value="500">最近500行</option>
                            </select>
                        </div>
                        <div id="systemErrorLogList" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; font-family: 'Consolas', 'Monaco', monospace; font-size: 12px; max-height: 600px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;"></div>
                    </div>
                </div>
                
                <!-- 管理员操作日志 -->
                <div id="admin_logs" class="maintenance-section">
                    <div class="maintenance-card">
                        <h3>管理员操作日志</h3>
                        <div class="search-box">
                            <input type="text" id="adminLogSearch" placeholder="搜索管理员、操作类型、描述">
                            <button onclick="searchAdminLogs()">搜索</button>
                            <button onclick="resetAdminLogSearch()">重置</button>
                        </div>
                        <div id="adminLogList"></div>
                        <div class="pagination" id="adminLogPagination"></div>
                    </div>
                </div>
                
                <!-- 异常行为记录 -->
                <div id="abnormal_behavior" class="maintenance-section">
                    <div class="maintenance-card">
                        <h3>异常行为记录</h3>
                        <div class="search-box">
                            <input type="text" id="abnormalLogSearch" placeholder="搜索用户ID、用户名、描述">
                            <select id="abnormalLogSeverity" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">全部严重程度</option>
                                <option value="1">低</option>
                                <option value="2">中</option>
                                <option value="3">高</option>
                            </select>
                            <select id="abnormalLogHandled" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">全部状态</option>
                                <option value="0">未处理</option>
                                <option value="1">已处理</option>
                            </select>
                            <button onclick="searchAbnormalLogs()">搜索</button>
                            <button onclick="resetAbnormalLogSearch()">重置</button>
                        </div>
                        <div id="abnormalLogList"></div>
                        <div class="pagination" id="abnormalLogPagination"></div>
                    </div>
                </div>
            </div>
            
            <!-- 公告管理 -->
            <div id="announcements" class="section">
                <h2>公告管理</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="showAnnouncementModal()">发布新公告</button>
                </div>
                <div id="announcementList"></div>
                <div class="pagination" id="announcementPagination"></div>
            </div>
            
            <!-- 积分商城管理 -->
            <div id="shop" class="section">
                <h2>积分商城管理</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="showShopProductModal()">添加商品</button>
                    <button class="btn btn-secondary" onclick="loadShopProducts()" style="margin-left: 10px;">刷新列表</button>
                </div>
                <div class="search-box" style="margin-bottom: 15px;">
                    <select id="shopStatusFilter" onchange="loadShopProducts()" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">全部状态</option>
                        <option value="1">已上架</option>
                        <option value="0">已下架</option>
                    </select>
                </div>
                <div id="shopProductList"></div>
                <div class="pagination" id="shopProductPagination"></div>
            </div>
            
            <!-- 系统设置 -->
            <div id="settings" class="section">
                <h2>系统设置</h2>
                <div id="settingsContent"></div>
            </div>
        </div>
    </div>

    <!-- 用户详情模态框 -->
    <div id="userDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>用户详情</h2>
                <span class="close" onclick="closeUserDetail()">&times;</span>
            </div>
            <div id="userDetailContent"></div>
        </div>
    </div>

    <!-- 照片详情模态框 -->
    <div id="photoDetailModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>照片详情</h2>
                <span class="close" onclick="closePhotoDetail()">&times;</span>
            </div>
            <div id="photoDetailContent"></div>
        </div>
    </div>

    <!-- 设置VIP模态框 -->
    <div id="setVipModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>设置VIP会员</h2>
                <span class="close" onclick="closeSetVipModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="vipUserId">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="vipCheckbox" onchange="updateVipExpireInput()">
                        设置为VIP会员
                    </label>
                </div>
                <div class="form-group" id="vipExpireGroup" style="display: none;">
                    <label>
                        <input type="checkbox" id="vipUnlimitedCheckbox" onchange="updateVipExpireInput()">
                        永久VIP
                    </label>
                </div>
                <div class="form-group" id="vipExpireTimeGroup" style="display: none;">
                    <label for="vipExpireTimeInput">VIP到期时间：</label>
                    <input type="datetime-local" id="vipExpireTimeInput" class="form-control">
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn" onclick="closeSetVipModal()" style="margin-right: 10px; background: #999;">取消</button>
                    <button class="btn btn-warning" onclick="submitVipForm()">确定</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 公告编辑模态框 -->
    <div id="announcementModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 id="announcementModalTitle">发布新公告</h2>
                <span class="close" onclick="closeAnnouncementModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="announcementId">
                <div class="form-group">
                    <label for="announcementTitle">标题：</label>
                    <input type="text" id="announcementTitle" class="form-control" placeholder="请输入公告标题">
                </div>
                <div class="form-group">
                    <label for="announcementContentType">内容类型：</label>
                    <select id="announcementContentType" class="form-control" onchange="updateContentTypeHint()">
                        <option value="plain">纯文本</option>
                        <option value="html">HTML</option>
                        <option value="markdown">Markdown</option>
                        <option value="auto" selected>自动检测</option>
                    </select>
                    <small id="contentTypeHint" style="color: #666; display: block; margin-top: 5px;">系统将自动检测内容格式</small>
                </div>
                <div class="form-group">
                    <label for="announcementContent">内容：</label>
                    <textarea id="announcementContent" class="form-control" rows="8" placeholder="请输入公告内容"></textarea>
                </div>
                <div class="form-group">
                    <label for="announcementLevel">级别：</label>
                    <select id="announcementLevel" class="form-control">
                        <option value="important">重要</option>
                        <option value="normal" selected>一般</option>
                        <option value="notice">通知</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="announcementRequireRead">
                        强制消息已读
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="announcementIsVisible" checked>
                        显示公告
                    </label>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn" onclick="closeAnnouncementModal()" style="margin-right: 10px; background: #999;">取消</button>
                    <button class="btn btn-primary" onclick="submitAnnouncement()">确定</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 公告已读状态模态框 -->
    <div id="announcementReadStatusModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>公告已读状态</h2>
                <span class="close" onclick="closeAnnouncementReadStatusModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="announcementReadStatusList"></div>
                <div class="pagination" id="announcementReadStatusPagination"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/content-renderer.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>

