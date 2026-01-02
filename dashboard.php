<?php
session_start();
require_once __DIR__ . '/core/autoload.php';

$userModel = new User();
if (!$userModel->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $userModel->getCurrentUser();

// 获取用户VIP状态
$db = Database::getInstance();
$userInfo = $db->fetchOne(
    "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
    [$currentUser['id']]
);
$isVip = false;
$vipExpireTime = null;
$vipDaysRemaining = null;
if (($userInfo['is_vip'] ?? 0) == 1) {
    if ($userInfo['vip_expire_time'] === null) {
        $isVip = true;
        $vipExpireTime = '永久';
    } else {
        $expireTime = strtotime($userInfo['vip_expire_time']);
        $isVip = $expireTime > time();
        if ($isVip) {
            $vipExpireTime = $userInfo['vip_expire_time'];
            // 计算剩余天数
            $currentTime = time();
            $daysRemaining = floor(($expireTime - $currentTime) / 86400);
            $vipDaysRemaining = $daysRemaining;
        }
    }
}

// 获取用户统计数据
$inviteCount = $db->fetchOne("SELECT COUNT(*) as total FROM invites WHERE user_id = ?", [$currentUser['id']]);
$photoCount = $db->fetchOne("SELECT COUNT(*) as total FROM photos WHERE user_id = ?", [$currentUser['id']]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="user-info">
                <h1>
                    欢迎回来，<?php echo htmlspecialchars(!empty($currentUser['nickname']) ? $currentUser['nickname'] : $currentUser['username']); ?>
                    <?php if ($isVip): ?>
                        <span class="vip-badge-inline">
                            VIP<?php 
                            if ($vipExpireTime === '永久') {
                                echo '（永久）';
                            } elseif ($vipDaysRemaining !== null) {
                                if ($vipDaysRemaining > 0) {
                                    echo '（剩余' . $vipDaysRemaining . '天）';
                                } else {
                                    echo '（今日到期）';
                                }
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="28" height="28">
                                <path d="M975.385859 447.353535V225.228283c0-48.484848-37.624242-88.177778-83.523233-88.177778H132.137374C86.109091 137.050505 48.614141 176.743434 48.614141 225.228283v222.254545c36.072727 4.913131 62.707071 23.660606 62.707071 64.775758s-26.634343 59.733333-62.707071 64.775757v222.254546c0 48.484848 37.624242 88.177778 83.523233 88.177778H891.60404c46.028283 0 83.523232-39.692929 83.523233-88.177778V576.775758c-36.072727-4.913131-62.707071-23.660606-62.707071-64.775758 0.258586-40.985859 26.892929-59.60404 62.965657-64.646465z m-44.218182 162.779798v188.767677c0 23.919192-18.10101 44.218182-39.563637 44.218182h-81.842424v-44.218182c0-12.153535-9.826263-21.979798-21.979798-21.979798s-21.979798 9.826263-21.979798 21.979798v44.218182H258.19798v-44.218182c0-12.153535-9.826263-21.979798-21.979798-21.979798s-21.979798 9.826263-21.979798 21.979798v44.218182H132.266667c-21.462626 0-39.563636-20.169697-39.563637-44.218182v-188.767677C132.654545 592.808081 155.410101 558.157576 155.410101 512c0-46.028283-22.884848-80.808081-62.707071-98.133333v-188.767677c0-23.919192 18.10101-44.218182 39.563637-44.218182H214.109091v44.218182c0 12.153535 9.826263 21.979798 21.979798 21.979798s21.979798-9.826263 21.979798-21.979798v-44.218182h507.60404v44.218182c0 12.153535 9.826263 21.979798 21.979798 21.979798s21.979798-9.826263 21.979798-21.979798v-44.218182h81.842424c21.462626 0 39.563636 20.169697 39.563637 44.218182v188.767677C891.086869 431.191919 868.331313 465.842424 868.331313 512c0.129293 46.157576 23.014141 80.937374 62.836364 98.133333z m0 0" fill="currentColor"></path>
                                <path d="M456.533333 347.927273c-9.179798 2.327273-20.040404 4.525253-32.581818 6.852525v78.99798c27.539394 0 49.260606-0.517172 65.292929-1.680808v32.581818c-16.032323-1.163636-37.753535-1.680808-65.292929-1.680808v53.268687c9.179798-6.852525 16.549495-13.187879 22.367677-18.876768 18.359596 26.375758 32.581818 46.933333 42.925252 61.80202-5.688889 3.490909-15.515152 10.343434-29.220202 20.557576-9.179798-19.393939-21.20404-38.917172-36.072727-58.440404v116.751515c0 17.19596 0.517172 39.434343 1.680808 66.973737h-36.072727c2.327273-27.539394 3.490909-49.777778 3.490909-66.973737v-111.579798c-9.179798 20.557576-17.19596 36.589899-24.048485 48.09697-6.852525 11.507071-17.713131 27.539394-32.581818 48.096969-9.179798-8.016162-19.523232-14.868687-30.90101-20.557575 17.19596-17.19596 33.228283-38.4 48.09697-63.482829 14.868687-25.212121 25.729293-50.424242 32.581818-75.50707-16.032323 0-40.59798 0.646465-73.826263 1.680808v-32.581818c33.228283 1.163636 60.121212 1.680808 80.678788 1.680808v-72.145455c-22.884848 3.490909-44.606061 6.852525-65.292929 10.343435-3.490909-10.343434-7.49899-20.557576-12.024243-30.901011 14.868687 1.163636 40.339394-1.680808 76.412122-8.533333s60.89697-14.351515 74.731313-22.367677c8.016162 13.705051 14.868687 24.048485 20.557575 30.90101-11.507071 2.19798-21.721212 4.525253-30.90101 6.723233z m76.412122 286.771717c-16.549495 22.884848-35.167677 45.769697-55.854546 68.654545-8.016162-8.016162-17.19596-16.032323-27.539394-24.048484 14.868687-11.507071 29.737374-26.892929 44.606061-46.416162 14.868687-19.393939 29.220202-42.925253 42.925252-70.464647 11.377778 9.179798 21.721212 16.549495 30.90101 22.367677-6.723232 10.472727-18.488889 27.022222-35.038383 49.907071z m146.876767-221.478788v56.630303c0 24.048485 0.517172 46.933333 1.680808 68.654546H504.630303c1.163636-21.721212 1.680808-44.606061 1.680808-68.654546v-58.440404c0-30.90101-0.646465-56.113131-1.680808-75.507071h176.872727c-1.163636 19.523232-1.680808 45.252525-1.680808 77.317172zM647.111111 509.414141V363.442424h-109.89899V509.414141h109.89899z m28.315152 106.408081c14.351515 18.359596 28.315152 37.753535 42.020202 58.440404-13.705051 8.016162-24.694949 15.515152-32.581819 22.367677-8.016162-14.868687-20.040404-34.133333-36.072727-57.535354-16.032323-23.40202-28.573737-40.339394-37.753535-50.682828 9.179798-5.688889 18.359596-12.541414 27.539394-20.557576 10.343434 13.705051 22.626263 29.737374 36.848485 47.967677z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $currentUser['points']; ?></div>
                            <div class="stat-label">积分</div>
                        </div>
                    </div>
                    <div class="stat-item stat-item-invite">
                        <div class="stat-icon">
                            <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="28" height="28">
                                <path d="M514.2 659.1c-44.8 42.1-84.6 85-127.6 126.7-46.3 45.6-120.6 45.6-167 0-22.7-23-34.9-54.3-33.7-86.6 1.2-32.3 15.6-62.6 39.9-83.9 41.6-42.5 84.2-83.7 125.8-125.8 10.5-7.1 17.1-18.6 18.1-31.3 1-12.6-3.9-25-13.2-33.6-9-8.2-21-12.4-33.2-11.5-12.2 0.8-23.5 6.6-31.3 16-44.8 44.8-89.5 86.8-131.2 131.2-46.9 47.1-67.7 114.2-55.9 179.6 11.9 65.4 55 120.9 115.4 148.5 69.8 34.8 153.8 22.6 210.8-30.4 50.6-44.8 97.1-94 143.7-142.8 8.9-11.1 12.8-25.3 10.7-39.4-3.4-15.3-15.3-27.3-30.6-30.9-15-3.7-30.9 1.9-40.7 14.2zM873.2 240.1c-28.2-71.4-96.5-119-173.2-120.9-47.3-4.1-94.3 10-131.6 39.4-53.7 48.3-104.3 99.8-154.4 151.7a39.953 39.953 0 0 0-7.5 42c5.8 14.3 19.4 23.9 34.8 24.7 14.2-0.5 27.8-6.2 38-16.1 44.8-44.8 89.5-89.5 134.3-130.7 27.2-25.4 66-34.3 101.6-23.3 41.2 10 73.6 41.6 84.8 82.4 11.2 40.9-0.7 84.6-31.1 114.1L637.8 534.6c-18.6 15.9-21 43.8-5.4 62.7 18.6 18.3 48.5 18.3 67.1 0 44.8-44.8 89.5-86.8 134.3-134.3 59.2-58.4 75-147.8 39.4-222.9z" fill="currentColor"></path>
                                <path d="M338.3 668c8 8.6 19.2 13.5 30.9 13.5s22.9-4.9 30.9-13.5c86.2-85.9 172-172.2 257.4-258.7 5.5-7 9.9-14.9 13-23.3 1.6-19-9-37-26.4-44.8-16.9-7.7-36.9-3.1-48.8 11.2L340.5 607.2c-9 7.4-14.4 18.3-14.8 29.9-0.4 11.6 4.2 22.9 12.6 30.9z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $inviteCount['total'] ?? 0; ?></div>
                            <div class="stat-label">拍摄链接</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <svg class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="28" height="28">
                                <path d="M835.955206 889.082194 183.583179 889.082194c-65.937758 0-119.601982-53.6632-119.601982-119.601982L63.981197 247.582795c0-65.937758 53.6632-119.601982 119.601982-119.601982l652.372028 0c65.937758 0 119.601982 53.6632 119.601982 119.601982l0 521.897418C955.557188 835.418994 901.892965 889.082194 835.955206 889.082194zM183.583179 193.21863c-29.984918 0-54.364165 24.379247-54.364165 54.364165l0 521.897418c0 29.984918 24.379247 54.364165 54.364165 54.364165l652.372028 0c29.984918 0 54.364165-24.379247 54.364165-54.364165L890.319371 247.582795c0-29.984918-24.379247-54.364165-54.364165-54.364165L183.583179 193.21863z" fill="currentColor"></path>
                                <path d="M477.29457 767.588119c-8.346085 0-16.691147-3.185552-23.062252-9.556657L346.81996 650.619104l-99.236142 99.257631c-12.741185 12.741185-33.383318 12.741185-46.124504 0-12.741185-12.741185-12.741185-33.383318 0-46.124504L323.757708 581.432349c12.231579-12.231579 33.892925-12.231579 46.124504 0L500.356822 711.906959c12.741185 12.741185 12.741185 33.383318 0 46.124504C493.985718 764.402567 485.640656 767.588119 477.29457 767.588119z" fill="currentColor"></path>
                                <path d="M798.890033 717.834972c-8.346085 0-16.691147-3.185552-23.062252-9.556657l-184.583972-184.583972-84.60491 84.60491c-12.741185 12.741185-33.383318 12.741185-46.124504 0-12.741185-12.741185-12.741185-33.383318 0-46.124504l107.667162-107.667162c12.741185-12.741185 33.383318-12.741185 46.124504 0L821.953308 662.153812c12.741185 12.741185 12.741185 33.383318 0 46.124504C815.582203 714.64942 807.236118 717.834972 798.890033 717.834972z" fill="currentColor"></path>
                                <path d="M375.46537 465.040479c-65.937758 0-119.601982-53.6632-119.601982-119.601982s53.6632-119.601982 119.601982-119.601982 119.601982 53.6632 119.601982 119.601982S441.403129 465.040479 375.46537 465.040479zM375.46537 291.074332c-29.984918 0-54.364165 24.379247-54.364165 54.364165 0 29.984918 24.379247 54.364165 54.364165 54.364165s54.364165-24.379247 54.364165-54.364165C429.829536 315.453578 405.450289 291.074332 375.46537 291.074332z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $photoCount['total'] ?? 0; ?></div>
                            <div class="stat-label">照片</div>
                        </div>
                    </div>
                </div>
                <!-- 系统公告 -->
                <div id="announcementsContainer" style="margin-top: 15px;"></div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="nav">
            <button class="nav-btn active" onclick="showSection('invites')">拍摄链接</button>
            <button class="nav-btn" onclick="showSection('photos')">照片列表</button>
            <button class="nav-btn" onclick="showSection('shop')">积分商城</button>
            <button class="nav-btn" onclick="showSection('points')">积分明细</button>
            <button class="nav-btn" onclick="showSection('ranking')">排行榜</button>
            <button class="nav-btn" onclick="showSection('profile')">个人资料</button>
            <button class="nav-btn" onclick="showSection('security')">账号安全</button>
            <button class="nav-btn nav-btn-logout" onclick="window.location.href='api/logout.php'">退出登录</button>
        </div>
        
        <div class="content">
            <!-- 拍摄链接 -->
            <div id="invites" class="section active">
                <h2>拍摄链接</h2>
                <div style="margin-bottom: 20px;">
                    <button id="generateInviteBtn" class="btn" onclick="generateInvite()">生成新拍摄链接</button>
                    <span id="inviteCountInfo" style="margin-left: 15px; color: #666;"></span>
                </div>
                <div id="inviteList"></div>
            </div>
            
            <!-- 照片列表 -->
            <div id="photos" class="section">
                <h2>照片列表</h2>
                <div style="margin-bottom: 20px; display: flex; gap: 8px; align-items: center;">
                    <input type="text" id="tagSearchInput" placeholder="搜索标签..." style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; width: 180px; font-size: 13px;">
                    <button class="btn" onclick="searchByTag()" style="padding: 6px 15px; font-size: 13px;">搜索</button>
                    <button class="btn" onclick="resetPhotoFilter()" style="background: #999; padding: 6px 15px; font-size: 13px;">重置</button>
                </div>
                <div id="batchActions" class="batch-actions" style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px; gap: 10px; align-items: center;">
                    <span id="selectedCount" style="font-weight: bold; color: #667eea;">已选择 0 张</span>
                    <button class="btn" onclick="batchDeletePhotos()" style="background: #dc3545; padding: 6px 15px; font-size: 13px;">批量删除</button>
                    <button class="btn" onclick="batchDownloadPhotos()" style="background: #28a745; padding: 6px 15px; font-size: 13px;">批量下载</button>
                    <button class="btn" onclick="clearSelection()" style="background: #999; padding: 6px 15px; font-size: 13px;">取消选择</button>
                </div>
                <div id="photoList"></div>
            </div>
            
            <!-- 积分商城 -->
            <div id="shop" class="section">
                <h2>积分商城</h2>
                <div style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white;">
                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 5px;">
                        当前积分：<span id="currentShopPoints">0</span>
                    </div>
                    <div style="font-size: 14px; opacity: 0.9;">使用积分兑换VIP会员、拍摄链接等商品</div>
                </div>
                <div id="shopProductList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;"></div>
                <div id="shopProductEmpty" style="text-align: center; padding: 40px; color: #999; display: none;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🛒</div>
                    <div>暂无商品</div>
                </div>
            </div>
            
            <!-- 积分明细 -->
            <div id="points" class="section">
                <h2>积分明细</h2>
                <!-- 签到功能 -->
                <div class="checkin-section" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: 18px;">每日签到</h3>
                            <div id="checkinStatus" style="color: #666; font-size: 14px;">加载中...</div>
                        </div>
                        <button id="checkinBtn" class="btn" onclick="doCheckin()" style="padding: 10px 25px; font-size: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <span id="checkinBtnText">签到</span>
                        </button>
                    </div>
                    <div id="checkinInfo" style="display: none; padding: 15px; background: white; border-radius: 6px; margin-top: 15px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; font-size: 14px;">
                            <div>
                                <div style="color: #999; margin-bottom: 5px;">连续签到</div>
                                <div style="font-size: 20px; font-weight: bold; color: #667eea;" id="consecutiveDays">0 天</div>
                            </div>
                            <div>
                                <div style="color: #999; margin-bottom: 5px;">今日奖励</div>
                                <div style="font-size: 20px; font-weight: bold; color: #28a745;" id="todayReward">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="pointsInfo"></div>
            </div>
            
            <!-- 排行榜 -->
            <div id="ranking" class="section">
                <h2>排行榜</h2>
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                        <button class="btn ranking-tab-btn active" onclick="loadRanking('total')" data-type="total">总积分</button>
                        <button class="btn ranking-tab-btn" onclick="loadRanking('monthly')" data-type="monthly">月度积分</button>
                        <button class="btn ranking-tab-btn" onclick="loadRanking('invite')" data-type="invite">邀请人数</button>
                        <button class="btn ranking-tab-btn" onclick="loadRanking('photo')" data-type="photo">上传照片</button>
                    </div>
                </div>
                <div id="rankingInfo"></div>
                <div id="rankingList"></div>
            </div>
            
            <!-- 个人资料 -->
            <div id="profile" class="section">
                <h2>个人资料</h2>
                <div id="profileContent">
                    <div style="max-width: 600px;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>用户名</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled style="background: #f5f5f5;">
                            <small style="color: #999;">用户名不可修改</small>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>昵称</label>
                            <div>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <div style="flex: 1;">
                                        <input type="text" id="nicknameInput" class="form-control" value="<?php echo htmlspecialchars($currentUser['nickname'] ?? ''); ?>" placeholder="请输入昵称（最多18个字符）" maxlength="18" disabled style="background: #f5f5f5; width: 100%;">
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center; flex-shrink: 0; align-self: flex-start; padding-top: 0;">
                                        <button class="btn" id="nicknameEditBtn" onclick="editNickname()" style="white-space: nowrap;">修改</button>
                                        <button class="btn" id="nicknameSaveBtn" onclick="saveNickname()" style="white-space: nowrap; display: none;">保存</button>
                                        <button class="btn" id="nicknameCancelBtn" onclick="cancelEditNickname()" style="white-space: nowrap; display: none; background: #999;">取消</button>
                                    </div>
                                </div>
                                <p style="font-size: 12px; color: #999; margin-top: 5px; margin-bottom: 0;">昵称最多18个字符</p>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>邮箱</label>
                            <div id="emailSection">
                                <?php if (!empty($currentUser['email'])): ?>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                        <span><?php echo htmlspecialchars($currentUser['email']); ?></span>
                                        <?php if ($currentUser['email_verified'] == 1): ?>
                                            <span style="color: #28a745; font-size: 12px;">✓ 已验证</span>
                                        <?php else: ?>
                                            <span style="color: #dc3545; font-size: 12px;">未验证</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: flex; gap: 10px;">
                                            <input type="email" id="emailInput" class="form-control" placeholder="请输入邮箱地址">
                                            <button class="btn" onclick="sendEmailCode()" style="white-space: nowrap;">发送验证码</button>
                                        </div>
                                        <div id="emailCodeSection" style="display: none; margin-top: 10px;">
                                            <div style="display: flex; gap: 10px;">
                                                <input type="text" id="emailCodeInput" class="form-control" placeholder="请输入验证码" maxlength="6">
                                                <button class="btn" onclick="verifyEmail()" style="white-space: nowrap;">验证</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>
                                <input type="checkbox" id="emailNotifyCheckbox" <?php echo ($currentUser['email_notify_photo'] ?? 0) == 1 ? 'checked' : ''; ?> onchange="saveEmailNotify()">
                                收到照片时邮件提醒
                            </label>
                            <small style="color: #999; display: block; margin-top: 5px;">开启后，当有人通过您的拍摄链接上传照片时，您将收到邮件提醒</small>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>注册码</label>
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <input type="text" id="registerCodeInput" class="form-control" value="" readonly style="background: #f5f5f5; font-family: monospace;">
                                </div>
                                <button class="btn" onclick="copyRegisterCode()" style="white-space: nowrap;">复制</button>
                            </div>
                            <small style="color: #999; display: block; margin-top: 5px;">分享此注册码给他人，他人可使用此码注册账号</small>
                            <div id="registerUrlSection" style="margin-top: 10px; display: none;">
                                <label>注册链接</label>
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <input type="text" id="registerUrlInput" class="form-control" value="" readonly style="background: #f5f5f5; font-size: 12px;">
                                    </div>
                                    <button class="btn" onclick="copyRegisterUrl()" style="white-space: nowrap;">复制链接</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 账号安全 -->
            <div id="security" class="section">
                <h2>账号安全</h2>
                <div id="securityContent">
                    <div style="max-width: 600px;">
                        <!-- 修改密码 -->
                        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin-top: 0;">修改密码</h3>
                            <div class="form-group">
                                <label>原密码</label>
                                <input type="password" id="oldPasswordInput" class="form-control" placeholder="请输入原密码">
                            </div>
                            <div class="form-group">
                                <label>新密码</label>
                                <input type="password" id="newPasswordInput" class="form-control" placeholder="请输入新密码（至少6位）">
                            </div>
                            <div class="form-group">
                                <label>确认新密码</label>
                                <input type="password" id="confirmPasswordInput" class="form-control" placeholder="请再次输入新密码">
                            </div>
                            <button class="btn" onclick="changePassword()">修改密码</button>
                        </div>
                        
                        <!-- 登录日志 -->
                        <div style="background: white; padding: 20px; border-radius: 8px;">
                            <h3 style="margin-top: 0;">登录日志</h3>
                            <div id="loginLogsList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 设置拍摄链接到期时间模态框 -->
    <div id="inviteExpireModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>设置拍摄链接到期时间</h2>
                <span class="close" onclick="closeInviteExpireModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="unlimitedCheckbox" onchange="updateExpireTimeInput()">
                        无限制（VIP用户可用）
                    </label>
                </div>
                <div class="form-group">
                    <label for="expireTimeInput">到期时间：</label>
                    <input type="datetime-local" id="expireTimeInput" class="form-control">
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn" onclick="closeInviteExpireModal()" style="margin-right: 10px; background: #999;">取消</button>
                    <button class="btn" onclick="submitInviteForm()">生成拍摄链接</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 确认生成拍摄链接对话框 -->
    <div id="confirmGenerateInviteModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2>提示</h2>
                <span class="close" onclick="closeConfirmGenerateInviteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin: 20px 0; line-height: 1.6;">上一条拍摄链接未到期，不建议再次生成链接</p>
                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn" onclick="closeConfirmGenerateInviteModal()" style="margin-right: 10px; background: #999;">取消</button>
                    <button class="btn" id="confirmGenerateBtn" onclick="confirmGenerateInvite()">生成</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑拍摄链接模态框 -->
    <div id="editInviteModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>编辑拍摄链接</h2>
                <span class="close" onclick="closeEditInviteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editInviteId">
                <div class="form-group">
                    <label>拍摄链接码：</label>
                    <input type="text" id="editInviteCode" class="form-control" readonly style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>标签：</label>
                    <input type="text" id="editInviteLabel" class="form-control" placeholder="输入标签（可选，最多10个字符）" maxlength="10">
                    <p style="font-size: 12px; color: #999; margin-top: 5px;">标签用于在照片列表中标识和排序拍摄链接码</p>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editInviteStatus">
                        启用拍摄链接
                    </label>
                    <p style="font-size: 12px; color: #999; margin-top: 5px;">禁用后，该拍摄链接将无法使用（仅VIP用户可用）</p>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn" onclick="closeEditInviteModal()" style="margin-right: 10px; background: #999;">取消</button>
                    <button class="btn" onclick="submitEditInvite()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 照片详情模态框 -->
    <div id="photoDetailModal" class="modal" style="display: none;">
        <div class="modal-content photo-detail-modal" style="max-width: 90%; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2>照片详情</h2>
                <span class="close" onclick="closePhotoDetail()">&times;</span>
            </div>
            <div class="modal-body" id="photoDetailContent">
                <div style="text-align: center; padding: 20px;">
                    <div class="loading">加载中...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 传递VIP状态到前端
        window.userIsVip = <?php echo $isVip ? 'true' : 'false'; ?>;
    </script>
    <script src="assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
