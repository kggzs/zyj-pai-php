let currentUserPage = 1;
let currentPhotoPage = 1;
let currentSearch = '';
let currentPhotoSearch = '';

// HTML转义函数，防止XSS攻击
function escapeHtml(text) {
    if (text == null || text === undefined) {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSection(section) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(section).classList.add('active');
    event.target.classList.add('active');
    
    if (section === 'statistics') loadStatistics();
    if (section === 'users') loadUsers();
    if (section === 'photos') loadPhotos();
    if (section === 'user_logs') {
        showLogTab('login_logs');
        loadLoginLogs();
    }
    if (section === 'announcements') loadAnnouncements();
    if (section === 'maintenance') {
        showMaintenanceTab('system_logs');
    }
    if (section === 'settings') loadSettings();
}

// 格式化数字（添加千分位分隔符）
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 计算百分比
function calculatePercentage(part, total) {
    if (total === 0) return 0;
    return ((part / total) * 100).toFixed(1);
}

// 格式化文件大小
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
}

function loadStatistics() {
    fetch('api/admin/get_statistics.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                const activeRate = calculatePercentage(stats.active_users, stats.total_users);
                const inviteActiveRate = calculatePercentage(stats.active_invites, stats.total_invites);
                const vipRate = calculatePercentage(stats.vip_users || 0, stats.total_users);
                const photoPerUser = stats.total_users > 0 ? (stats.total_photos / stats.total_users).toFixed(1) : 0;
                
                let html = `
                    <div class="stat-group">
                        <div class="stat-group-title">用户统计</div>
                        <div class="statistics">
                            <div class="stat-card card-primary">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.total_users)}</div>
                                        <div class="stat-card-label">总用户数</div>
                                    </div>
                                    <div class="stat-card-icon">👥</div>
                                </div>
                                <div class="stat-card-footer">
                                    正常用户: ${formatNumber(stats.active_users)} (${activeRate}%) | 封禁: ${formatNumber(stats.banned_users)} | VIP: ${formatNumber(stats.vip_users || 0)}
                                </div>
                            </div>
                            <div class="stat-card card-success">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.active_users)}</div>
                                        <div class="stat-card-label">正常用户</div>
                                    </div>
                                    <div class="stat-card-icon">✓</div>
                                </div>
                                <div class="stat-card-footer">
                                    占比: ${activeRate}%
                                </div>
                            </div>
                            <div class="stat-card card-danger">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.banned_users)}</div>
                                        <div class="stat-card-label">封禁用户</div>
                                    </div>
                                    <div class="stat-card-icon">⛔</div>
                                </div>
                                <div class="stat-card-footer">
                                    今日新增: ${formatNumber(stats.today_registers)}
                                </div>
                            </div>
                            <div class="stat-card card-warning">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.today_registers)}</div>
                                        <div class="stat-card-label">今日注册</div>
                                    </div>
                                    <div class="stat-card-icon">📅</div>
                                </div>
                            </div>
                            <div class="stat-card card-gold">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.vip_users || 0)}</div>
                                        <div class="stat-card-label">VIP用户</div>
                                    </div>
                                    <div class="stat-card-icon">👑</div>
                                </div>
                                <div class="stat-card-footer">
                                    占比: ${vipRate}%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-group">
                        <div class="stat-group-title">内容统计</div>
                        <div class="statistics">
                            <div class="stat-card card-info">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.total_photos)}</div>
                                        <div class="stat-card-label">总照片数</div>
                                    </div>
                                    <div class="stat-card-icon">📷</div>
                                </div>
                                <div class="stat-card-footer">
                                    人均照片: ${photoPerUser} 张 | 今日上传: ${formatNumber(stats.today_photos)}
                                </div>
                            </div>
                            <div class="stat-card card-purple">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.total_invites)}</div>
                                        <div class="stat-card-label">总拍摄链接</div>
                                    </div>
                                    <div class="stat-card-icon">🔗</div>
                                </div>
                                <div class="stat-card-footer">
                                    有效链接: ${formatNumber(stats.active_invites)} (${inviteActiveRate}%)
                                </div>
                            </div>
                            <div class="stat-card card-orange">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.total_points)}</div>
                                        <div class="stat-card-label">总积分</div>
                                    </div>
                                    <div class="stat-card-icon">⭐</div>
                                </div>
                                <div class="stat-card-footer">
                                    人均积分: ${stats.total_users > 0 ? formatNumber((stats.total_points / stats.total_users).toFixed(0)) : 0}
                                </div>
                            </div>
                            <div class="stat-card card-teal">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.today_photos)}</div>
                                        <div class="stat-card-label">今日上传</div>
                                    </div>
                                    <div class="stat-card-icon">⬆️</div>
                                </div>
                            </div>
                            <div class="stat-card card-indigo">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatFileSize(stats.total_photo_size || 0)}</div>
                                        <div class="stat-card-label">照片总大小</div>
                                    </div>
                                    <div class="stat-card-icon">💾</div>
                                </div>
                                <div class="stat-card-footer">
                                    平均每张: ${stats.total_photos > 0 ? formatFileSize(Math.floor((stats.total_photo_size || 0) / stats.total_photos)) : '0 B'}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-group">
                        <div class="stat-group-title" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>缓存统计</span>
                            <button class="btn-primary btn-sm" onclick="clearCache()" style="padding: 6px 16px; font-size: 13px;">清理缓存</button>
                        </div>
                        <div class="statistics">
                            <div class="stat-card card-cyan">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatNumber(stats.cache_stats?.file_count || 0)}</div>
                                        <div class="stat-card-label">缓存文件数</div>
                                    </div>
                                    <div class="stat-card-icon">📁</div>
                                </div>
                                <div class="stat-card-footer">
                                    内存缓存: ${formatNumber(stats.cache_stats?.memory_count || 0)} 个
                                </div>
                            </div>
                            <div class="stat-card card-lime">
                                <div class="stat-card-header">
                                    <div>
                                        <div class="stat-card-value">${formatFileSize(stats.cache_stats?.total_size || 0)}</div>
                                        <div class="stat-card-label">缓存总大小</div>
                                    </div>
                                    <div class="stat-card-icon">💾</div>
                                </div>
                                <div class="stat-card-footer">
                                    平均每个: ${stats.cache_stats?.file_count > 0 ? formatFileSize(Math.floor((stats.cache_stats?.total_size || 0) / stats.cache_stats.file_count)) : '0 B'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('statCards').innerHTML = html;
                
                // 添加浏览器统计
                if (stats.browser_stats) {
                    const browserColors = {
                        'Chrome': '#4285f4',
                        'Edge': '#0078d4',
                        'Firefox': '#ff7139',
                        'Safari': '#000000',
                        'Opera': '#ff1b2d',
                        '微信浏览器': '#07c160',
                        'Android浏览器': '#3ddc84',
                        'iOS浏览器': '#007aff',
                        '其他': '#9e9e9e'
                    };
                    const browserIcons = {
                        'Chrome': '🌐',
                        'Edge': '🔷',
                        'Firefox': '🦊',
                        'Safari': '🧭',
                        'Opera': '🎭',
                        '微信浏览器': '💬',
                        'Android浏览器': '🤖',
                        'iOS浏览器': '📱',
                        '其他': '❓'
                    };
                    
                    const totalPhotos = stats.total_photos;
                    let browserHtml = `
                        <div class="browser-stats-container">
                            <div class="stat-group-title">浏览器统计</div>
                            <div class="browser-stats-grid">
                    `;
                    const browserOrder = ['Chrome', 'Edge', 'Firefox', 'Safari', 'Opera', '微信浏览器', 'Android浏览器', 'iOS浏览器', '其他'];
                    browserOrder.forEach(browser => {
                        const count = stats.browser_stats[browser] || 0;
                        if (count > 0) {
                            const percentage = calculatePercentage(count, totalPhotos);
                            const color = browserColors[browser] || '#5B9BD5';
                            const icon = browserIcons[browser] || '🌐';
                            browserHtml += `
                                <div class="browser-stat-item">
                                    <div class="browser-stat-icon" style="background: ${color}">
                                        ${icon}
                                    </div>
                                    <div class="browser-stat-info">
                                        <div class="browser-stat-name">${browser}</div>
                                        <div class="browser-stat-value">${formatNumber(count)}</div>
                                        <div style="font-size: 12px; color: #999; margin-top: 2px;">${percentage}%</div>
                                    </div>
                                </div>
                            `;
                        }
                    });
                    browserHtml += '</div></div>';
                    document.getElementById('statCards').insertAdjacentHTML('beforeend', browserHtml);
                }
            }
        });
}

function clearCache() {
    if (!confirm('确定要清理所有缓存吗？')) {
        return;
    }
    
    fetch('api/admin/clear_cache.php?type=all')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message || '缓存已清理');
                // 重新加载统计数据
                loadStatistics();
            } else {
                alert(data.message || '清理失败');
            }
        })
        .catch(err => {
            alert('清理失败，请重试');
            console.error('清理缓存错误:', err);
        });
}

function loadUsers(page = 1) {
    currentUserPage = page;
    const searchParam = currentSearch ? `&search=${encodeURIComponent(currentSearch)}` : '';
    fetch(`api/admin/get_users.php?page=${page}&page_size=20${searchParam}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const users = data.data.list;
                let html = '<table><thead><tr><th>ID</th><th>用户信息</th><th>邮箱</th><th>注册IP</th><th>注册时间</th><th>上次登录</th><th>积分</th><th>VIP</th><th>状态</th><th>操作</th></tr></thead><tbody>';
                users.forEach(user => {
                    const statusBadge = user.is_admin == 1 ? 
                        '<span class="status-badge status-admin">管理员</span>' :
                        (user.status == 1 ? 
                            '<span class="status-badge status-active">正常</span>' : 
                            '<span class="status-badge status-banned">封禁</span>');
                    
                    // VIP状态显示
                    let vipBadge = '';
                    if (user.is_vip == 1) {
                        const vipExpireTime = user.vip_expire_time || '';
                        if (vipExpireTime) {
                            const expireDate = new Date(vipExpireTime);
                            const now = new Date();
                            if (expireDate > now) {
                                vipBadge = `<span class="status-badge status-vip">VIP<br><small>${vipExpireTime.split(' ')[0]}</small></span>`;
                            } else {
                                vipBadge = '<span class="status-badge status-vip-expired">VIP已过期</span>';
                            }
                        } else {
                            vipBadge = '<span class="status-badge status-vip">永久VIP</span>';
                        }
                    } else {
                        vipBadge = '<span class="status-badge">普通</span>';
                    }
                    
                    const banBtn = user.is_admin == 1 ? '' :
                        (user.status == 1 ? 
                            `<button class="btn btn-danger" onclick="banUser(${user.id}, 0)">封禁</button>` :
                            `<button class="btn btn-success" onclick="banUser(${user.id}, 1)">解封</button>`);
                    const vipBtn = user.is_admin == 1 ? '' :
                        `<button class="btn btn-warning" onclick="showSetVipModal(${user.id}, ${user.is_vip || 0}, '${escapeHtml(user.vip_expire_time || '')}')">设置VIP</button>`;
                    const displayName = (user.nickname && user.nickname.trim()) ? user.nickname : user.username;
                    const emailInfo = user.email ? 
                        `${escapeHtml(user.email)} ${user.email_verified == 1 ? '<span style="color: #28a745; font-size: 12px;">✓</span>' : '<span style="color: #dc3545; font-size: 12px;">未验证</span>'}` : 
                        '<span style="color: #999;">未绑定</span>';
                    
                    html += `
                        <tr>
                            <td style="width: 60px;">${user.id}</td>
                            <td style="min-width: 150px; max-width: 200px;">
                                <div style="font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(displayName)}">${escapeHtml(displayName)}</div>
                                <div style="font-size: 11px; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(user.username)}">${escapeHtml(user.username)}</div>
                            </td>
                            <td style="min-width: 180px; max-width: 250px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(user.email || '未绑定')}">${emailInfo}</div>
                            </td>
                            <td style="width: 120px; white-space: nowrap;">${escapeHtml(user.register_ip || '未知')}</td>
                            <td style="width: 160px; white-space: nowrap;">${user.register_time ? escapeHtml(user.register_time.replace(/:\d{2}$/, '')) : '未知'}</td>
                            <td style="width: 160px; white-space: nowrap;">${user.last_login_time ? escapeHtml(user.last_login_time.replace(/:\d{2}$/, '')) : '从未登录'}</td>
                            <td style="width: 80px; text-align: center; font-weight: bold; color: #5B9BD5;">${user.points || 0}</td>
                            <td style="width: 100px; text-align: center;">${vipBadge}</td>
                            <td style="width: 100px; text-align: center;">${statusBadge}</td>
                            <td style="width: 280px; white-space: nowrap;">
                                <button class="btn btn-info" onclick="showUserDetail(${user.id})">查看详情</button>
                                ${banBtn}
                                ${vipBtn}
                            </td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div>';
                
                // 分页
                const total = data.data.total;
                const pageSize = data.data.page_size;
                const totalPages = Math.ceil(total / pageSize);
                if (totalPages > 1) {
                    html += '<div class="pagination">';
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<button class="${i == page ? 'active' : ''}" onclick="loadUsers(${i})">${i}</button>`;
                    }
                    html += '</div>';
                }
                
                document.getElementById('userList').innerHTML = html;
            }
        });
}

function searchUsers() {
    currentSearch = document.getElementById('userSearch').value.trim();
    loadUsers(1);
}

function resetSearch() {
    document.getElementById('userSearch').value = '';
    currentSearch = '';
    loadUsers(1);
}

function banUser(userId, status) {
    if (!confirm(status == 0 ? '确定要封禁该用户吗？' : '确定要解封该用户吗？')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('status', status);
    
    fetch('api/admin/ban_user.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadUsers(currentUserPage);
        } else {
            alert(data.message || '操作失败');
        }
    });
}

function showUserDetail(userId) {
    fetch(`api/admin/get_user_detail.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const user = data.data;
                const displayName = (user.nickname && user.nickname.trim()) ? user.nickname : user.username;
                const emailStatus = user.email ? 
                    `${escapeHtml(user.email)} ${user.email_verified == 1 ? '<span style="color: #28a745;">✓ 已验证</span>' : '<span style="color: #dc3545;">未验证</span>'}` : 
                    '<span style="color: #999;">未绑定</span>';
                const emailNotifyStatus = user.email_notify_photo == 1 ? '<span style="color: #28a745;">已开启</span>' : '<span style="color: #999;">未开启</span>';
                
                let html = `
                    <div class="detail-section">
                        <h3>基本信息</h3>
                        <div class="detail-item"><strong>用户ID：</strong>${user.id}</div>
                        <div class="detail-item"><strong>用户名：</strong>${escapeHtml(user.username)}</div>
                        <div class="detail-item"><strong>昵称：</strong>${user.nickname ? escapeHtml(user.nickname) : '<span style="color: #999;">未设置</span>'}</div>
                        <div class="detail-item"><strong>邮箱：</strong>${emailStatus}</div>
                        <div class="detail-item"><strong>邮箱提醒：</strong>${emailNotifyStatus}</div>
                        <div class="detail-item"><strong>注册IP：</strong>${escapeHtml(user.register_ip || '未知')}</div>
                        <div class="detail-item"><strong>注册浏览器：</strong><div class="ua-text">${escapeHtml(user.register_ua || '未知')}</div></div>
                        <div class="detail-item"><strong>注册时间：</strong>${escapeHtml(user.register_time || '未知')}</div>
                        <div class="detail-item"><strong>上次登录时间：</strong>${escapeHtml(user.last_login_time || '从未登录')}</div>
                        <div class="detail-item"><strong>上次登录IP：</strong>${escapeHtml(user.last_login_ip || '未知')}</div>
                        <div class="detail-item">
                            <strong>积分：</strong>${user.points || 0}
                            <button class="btn btn-sm btn-warning" onclick="showAdjustPointsModal(${user.id}, ${user.points || 0})" style="margin-left: 10px; padding: 4px 12px; font-size: 12px;">调整积分</button>
                        </div>
                        <div class="detail-item"><strong>照片数量：</strong>${user.photo_count || 0}</div>
                        <div class="detail-item"><strong>状态：</strong>${user.status == 1 ? '正常' : '封禁'}</div>
                        <div class="detail-item"><strong>VIP状态：</strong>${
                            user.is_vip == 1 ? 
                                (user.vip_expire_time ? `VIP（到期：${escapeHtml(user.vip_expire_time)}）` : '永久VIP') : 
                                '普通用户'
                        }</div>
                        <div class="detail-item">
                            <strong>注册码：</strong>
                            <span style="font-family: monospace; font-weight: bold; color: #5B9BD5;" title="注册码（6位）">${escapeHtml(user.register_code || '未生成')}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3>拍摄链接 (${user.invites ? user.invites.length : 0})</h3>
                        ${user.invites && user.invites.length > 0 ? `
                            <table class="info-table">
                                <thead>
                                    <tr><th>拍摄链接码</th><th>生成时间</th><th>有效期</th><th>状态</th><th>上传数</th></tr>
                                </thead>
                                <tbody>
                                    ${user.invites.map(invite => `
                                        <tr>
                                            <td style="font-family: monospace;" title="拍摄链接码（8位）">
                                                <a href="javascript:void(0)" onclick="goToPhotoManagement('${invite.invite_code}')" style="color: #5B9BD5; text-decoration: underline; cursor: pointer;">${invite.invite_code}</a>
                                            </td>
                                            <td>${invite.create_time}</td>
                                            <td>${invite.expire_time || '无限制'}</td>
                                            <td>${invite.status == 1 ? '有效' : '失效'}</td>
                                            <td>${invite.upload_count}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<p>暂无拍摄链接</p>'}
                    </div>
                    
                    <div class="detail-section">
                        <h3>邀请的用户 (${user.invited_users ? user.invited_users.length : 0})</h3>
                        ${user.invited_users && user.invited_users.length > 0 ? `
                            <table class="info-table">
                                <thead>
                                    <tr><th>用户ID</th><th>用户名</th><th>注册时间</th><th>注册IP</th><th>关联拍摄链接码</th></tr>
                                </thead>
                                <tbody>
                                    ${user.invited_users.map(invited => {
                                        const invitedDisplayName = (invited.nickname && invited.nickname.trim()) ? invited.nickname : invited.username;
                                        return `
                                        <tr>
                                            <td>${invited.id}</td>
                                            <td>${invitedDisplayName}</td>
                                            <td>${invited.register_time}</td>
                                            <td>${invited.register_ip || '未知'}</td>
                                            <td style="font-family: monospace;" title="${invited.invite_code ? (invited.invite_code.length === 6 ? '注册码（6位）' : invited.invite_code.length === 8 ? '拍摄链接码（8位）' : '') : ''}">${invited.invite_code || '未知'}</td>
                                        </tr>
                                    `;
                                    }).join('')}
                                </tbody>
                            </table>
                        ` : '<p>暂无邀请的用户</p>'}
                    </div>
                    
                    <div class="detail-section">
                        <h3>登录日志 (最近20条)</h3>
                        ${user.login_logs && user.login_logs.length > 0 ? `
                            <table class="info-table">
                                <thead>
                                    <tr><th>登录时间</th><th>登录IP</th><th>状态</th><th>失败原因</th></tr>
                                </thead>
                                <tbody>
                                    ${user.login_logs.map(log => `
                                        <tr>
                                            <td>${log.login_time || ''}</td>
                                            <td>${log.login_ip || '未知'}</td>
                                            <td>${log.is_success == 1 ? '<span style="color: #28a745;">成功</span>' : '<span style="color: #dc3545;">失败</span>'}</td>
                                            <td>${log.fail_reason || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<p>暂无登录记录</p>'}
                    </div>
                    
                    <div class="detail-section">
                        <h3>签到记录 (最近30条)</h3>
                        ${user.checkins && user.checkins.length > 0 ? `
                            <table class="info-table">
                                <thead>
                                    <tr><th>签到日期</th><th>连续天数</th><th>获得积分</th></tr>
                                </thead>
                                <tbody>
                                    ${user.checkins.map(checkin => `
                                        <tr>
                                            <td>${checkin.checkin_date || ''}</td>
                                            <td>${checkin.consecutive_days || 0} 天</td>
                                            <td>${checkin.points_earned || 0}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<p>暂无签到记录</p>'}
                    </div>
                    
                    <div class="detail-section">
                        <h3>积分明细 (最近50条)</h3>
                        ${user.points_log && user.points_log.list && user.points_log.list.length > 0 ? `
                            <table class="info-table">
                                <thead>
                                    <tr><th>时间</th><th>类型</th><th>积分变动</th><th>备注</th><th>关联信息</th></tr>
                                </thead>
                                <tbody>
                                    ${user.points_log.list.map(log => {
                                        const typeName = log.remark || getPointsTypeName(log.type);
                                        const pointsText = log.points > 0 ? `+${log.points}` : `${log.points}`;
                                        const pointsClass = log.points > 0 ? 'color: #28a745;' : 'color: #dc3545;';
                                        const formatTime = log.create_time ? log.create_time.replace(/:\d{2}$/, '') : '';
                                        
                                        let relatedInfo = '';
                                        if (log.type === 'invite_reward') {
                                            if (log.remark === '通过邀请码注册奖励' || log.remark === '通过注册码注册奖励') {
                                                const inviterName = (log.related_user_nickname && log.related_user_nickname.trim()) 
                                                    ? log.related_user_nickname 
                                                    : (log.related_user_name || '未知用户');
                                                relatedInfo = `邀请人：${inviterName}`;
                                            } else if (log.remark === '邀请新用户注册奖励') {
                                                const invitedName = (log.related_user_nickname && log.related_user_nickname.trim()) 
                                                    ? log.related_user_nickname 
                                                    : (log.related_user_name || '未知用户');
                                                relatedInfo = `被邀请人：${invitedName}`;
                                            }
                                        } else if (log.related_user_name) {
                                            const userName = (log.related_user_nickname && log.related_user_nickname.trim()) 
                                                ? log.related_user_nickname 
                                                : log.related_user_name;
                                            relatedInfo = `关联用户：${userName}`;
                                        }
                                        
                                        return `
                                        <tr>
                                            <td>${formatTime}</td>
                                            <td>${typeName}</td>
                                            <td style="${pointsClass} font-weight: bold;">${pointsText}</td>
                                            <td>${log.remark || '-'}</td>
                                            <td>${relatedInfo || '-'}</td>
                                        </tr>
                                    `;
                                    }).join('')}
                                </tbody>
                            </table>
                        ` : '<p>暂无积分记录</p>'}
                    </div>
                `;
                document.getElementById('userDetailContent').innerHTML = html;
                document.getElementById('userDetailModal').classList.add('active');
            }
        });
    }

function closeUserDetail() {
    document.getElementById('userDetailModal').classList.remove('active');
}

// 点击模态框外部（遮罩层）关闭模态框
document.addEventListener('DOMContentLoaded', function() {
    // 用户详情模态框
    const userDetailModal = document.getElementById('userDetailModal');
    if (userDetailModal) {
        userDetailModal.addEventListener('click', function(e) {
            // 如果点击的是遮罩层本身（不是modal-content），则关闭
            if (e.target === userDetailModal) {
                closeUserDetail();
            }
        });
    }
    
    // 照片详情模态框
    const photoDetailModal = document.getElementById('photoDetailModal');
    if (photoDetailModal) {
        photoDetailModal.addEventListener('click', function(e) {
            // 如果点击的是遮罩层本身（不是modal-content），则关闭
            if (e.target === photoDetailModal) {
                closePhotoDetail();
            }
        });
    }
});

// 解析User-Agent获取浏览器信息
function parseUserAgent(ua) {
    if (!ua) return '未知';
    
    // Chrome
    if (ua.indexOf('Chrome') > -1 && ua.indexOf('Edg') === -1 && ua.indexOf('OPR') === -1) {
        const match = ua.match(/Chrome\/(\d+)/);
        return match ? 'Chrome ' + match[1] : 'Chrome';
    }
    // Edge
    if (ua.indexOf('Edg') > -1) {
        const match = ua.match(/Edg\/(\d+)/);
        return match ? 'Edge ' + match[1] : 'Edge';
    }
    // Firefox
    if (ua.indexOf('Firefox') > -1) {
        const match = ua.match(/Firefox\/(\d+)/);
        return match ? 'Firefox ' + match[1] : 'Firefox';
    }
    // Safari
    if (ua.indexOf('Safari') > -1 && ua.indexOf('Chrome') === -1) {
        const match = ua.match(/Version\/(\d+)/);
        return match ? 'Safari ' + match[1] : 'Safari';
    }
    // Opera
    if (ua.indexOf('OPR') > -1) {
        const match = ua.match(/OPR\/(\d+)/);
        return match ? 'Opera ' + match[1] : 'Opera';
    }
    // 微信内置浏览器
    if (ua.indexOf('MicroMessenger') > -1) {
        return '微信浏览器';
    }
    // 移动设备
    if (ua.indexOf('Mobile') > -1) {
        if (ua.indexOf('Android') > -1) {
            return 'Android浏览器';
        } else if (ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) {
            return 'iOS浏览器';
        }
    }
    
    // 默认返回截取的UA（前30个字符）
    return ua.length > 30 ? ua.substring(0, 30) + '...' : ua;
}

// 解析设备型号
function parseDeviceModel(ua) {
    if (!ua) return '未知设备';
    
    // iPhone - 尝试匹配更详细的型号信息
    if (ua.indexOf('iPhone') > -1) {
        // 匹配iOS版本
        const iosMatch = ua.match(/OS\s+(\d+)[._](\d+)/);
        const iosVersion = iosMatch ? iosMatch[1] + '.' + iosMatch[2] : '';
        
        // 尝试匹配具体型号（如iPhone13,2）
        const modelMatch = ua.match(/iPhone(\d+,\d+)/);
        if (modelMatch) {
            const model = modelMatch[1];
            // 常见iPhone型号映射
            const modelMap = {
                '14,2': 'iPhone 13 Pro',
                '14,3': 'iPhone 13 Pro Max',
                '14,4': 'iPhone 13 mini',
                '14,5': 'iPhone 13',
                '15,2': 'iPhone 14 Pro',
                '15,3': 'iPhone 14 Pro Max',
                '15,4': 'iPhone 14',
                '15,5': 'iPhone 14 Plus',
                '16,1': 'iPhone 15',
                '16,2': 'iPhone 15 Plus',
                '16,3': 'iPhone 15 Pro',
                '16,4': 'iPhone 15 Pro Max'
            };
            const modelName = modelMap[model] || 'iPhone ' + model;
            return iosVersion ? modelName + ' (iOS ' + iosVersion + ')' : modelName;
        }
        return iosVersion ? 'iPhone (iOS ' + iosVersion + ')' : 'iPhone';
    }
    
    // iPad
    if (ua.indexOf('iPad') > -1) {
        const iosMatch = ua.match(/OS\s+(\d+)[._](\d+)/);
        const iosVersion = iosMatch ? iosMatch[1] + '.' + iosMatch[2] : '';
        
        // 尝试匹配iPad型号
        const modelMatch = ua.match(/iPad(\d+,\d+)/);
        if (modelMatch) {
            const model = modelMatch[1];
            const modelMap = {
                '13,1': 'iPad Air 4',
                '13,2': 'iPad Air 4',
                '13,16': 'iPad Air 5',
                '13,17': 'iPad Air 5',
                '14,1': 'iPad mini 6',
                '14,2': 'iPad mini 6'
            };
            const modelName = modelMap[model] || 'iPad ' + model;
            return iosVersion ? modelName + ' (iOS ' + iosVersion + ')' : modelName;
        }
        return iosVersion ? 'iPad (iOS ' + iosVersion + ')' : 'iPad';
    }
    
    // Android设备
    if (ua.indexOf('Android') > -1) {
        const versionMatch = ua.match(/Android\s+(\d+(?:\.\d+)?)/);
        const androidVersion = versionMatch ? versionMatch[1] : '';
        
        // 尝试匹配设备型号（在括号内，Build之前）
        let deviceModel = '';
        const buildMatch = ua.match(/;\s*([^;\)]+?)\s*(?:Build|\))/);
        if (buildMatch) {
            deviceModel = buildMatch[1].trim();
            // 过滤掉一些无用的信息
            if (deviceModel.match(/Linux|Mobile|wv|Version|Linux\s+armv/i)) {
                deviceModel = '';
            } else if (deviceModel.length > 30) {
                deviceModel = deviceModel.substring(0, 30) + '...';
            }
        }
        
        // 如果找到了设备型号
        if (deviceModel) {
            return androidVersion ? deviceModel + ' (Android ' + androidVersion + ')' : deviceModel;
        }
        return androidVersion ? 'Android ' + androidVersion : 'Android设备';
    }
    
    // Windows
    if (ua.indexOf('Windows') > -1) {
        if (ua.indexOf('Windows NT 10.0') > -1) return 'Windows 10/11';
        if (ua.indexOf('Windows NT 6.3') > -1) return 'Windows 8.1';
        if (ua.indexOf('Windows NT 6.2') > -1) return 'Windows 8';
        if (ua.indexOf('Windows NT 6.1') > -1) return 'Windows 7';
        return 'Windows';
    }
    
    // Mac
    if (ua.indexOf('Macintosh') > -1 || ua.indexOf('Mac OS X') > -1) {
        const match = ua.match(/Mac OS X\s+(\d+)[._](\d+)/);
        if (match) {
            return 'Mac OS X ' + match[1] + '.' + match[2];
        }
        return 'Mac';
    }
    
    return '未知设备';
}

// 生成管理后台EXIF信息HTML
function generateAdminExifInfo(photo) {
    let exifHtml = '';
    const hasExif = photo.latitude || photo.camera_make || photo.width;
    
    if (!hasExif) {
        return '';
    }
    
    exifHtml += '<div class="info-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">';
    exifHtml += '<span class="info-label" style="color: #5B9BD5; font-weight: 600;">📷 拍摄信息:</span>';
    exifHtml += '<div style="margin-top: 6px; font-size: 11px; color: #666;">';
    
    // 地理位置
    if (photo.latitude && photo.longitude) {
        exifHtml += `<div style="margin-bottom: 4px;">📍 位置: ${photo.latitude.toFixed(6)}, ${photo.longitude.toFixed(6)}`;
        if (photo.altitude) {
            exifHtml += ` (海拔: ${photo.altitude}m)`;
        }
        exifHtml += ` <a href="https://www.openstreetmap.org/?mlat=${photo.latitude}&mlon=${photo.longitude}&zoom=15" target="_blank" style="color: #5B9BD5; text-decoration: none; margin-left: 4px;">地图</a>`;
        exifHtml += '</div>';
        if (photo.location_address) {
            exifHtml += `<div style="margin-bottom: 4px; color: #999;">${photo.location_address}</div>`;
        }
    }
    
    // 相机信息
    if (photo.camera_make || photo.camera_model) {
        const cameraInfo = [];
        if (photo.camera_make) cameraInfo.push(photo.camera_make);
        if (photo.camera_model) cameraInfo.push(photo.camera_model);
        exifHtml += `<div style="margin-bottom: 4px;">📷 相机: ${cameraInfo.join(' ')}</div>`;
        if (photo.lens_model) {
            exifHtml += `<div style="margin-bottom: 4px;">🔍 镜头: ${photo.lens_model}</div>`;
        }
    }
    
    // 拍摄参数
    const params = [];
    if (photo.focal_length) params.push(`焦距: ${photo.focal_length}`);
    if (photo.aperture) params.push(`光圈: ${photo.aperture}`);
    if (photo.shutter_speed) params.push(`快门: ${photo.shutter_speed}`);
    if (photo.iso) params.push(`ISO: ${photo.iso}`);
    if (params.length > 0) {
        exifHtml += `<div style="margin-bottom: 4px;">⚙️ ${params.join(' | ')}</div>`;
    }
    
    // 照片尺寸
    if (photo.width && photo.height) {
        exifHtml += `<div>📐 尺寸: ${photo.width} × ${photo.height} 像素</div>`;
    }
    
    exifHtml += '</div></div>';
    return exifHtml;
}

function searchPhotos() {
    const searchValue = document.getElementById('photoUserSearch').value.trim();
    currentPhotoSearch = searchValue;
    // 清除URL hash
    window.location.hash = '';
    loadPhotos(1);
}

function resetPhotoSearch() {
    document.getElementById('photoUserSearch').value = '';
    currentPhotoSearch = '';
    // 清除URL hash
    window.location.hash = '';
    loadPhotos(1);
}

// 跳转到照片管理页面并筛选拍摄码
function goToPhotoManagement(inviteCode) {
    // 先关闭用户详情模态框
    closeUserDetail();
    // 切换到照片管理页面
    showSection('photos');
    // 设置搜索参数（将拍摄码填入搜索框）
    currentPhotoSearch = inviteCode;
    document.getElementById('photoUserSearch').value = inviteCode;
    // 清除URL hash
    window.location.hash = '';
    // 延迟加载，确保页面切换完成
    setTimeout(() => {
        loadPhotos(1);
    }, 100);
}

// 根据拍摄码加载照片
function loadPhotosByInviteCode(inviteCode) {
    fetch(`api/admin/get_all_photos.php?page=1&page_size=10000&invite_code=${encodeURIComponent(inviteCode)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.list.length > 0) {
                // 显示筛选结果
                displayPhotos(data.data.list, `筛选结果：拍摄链接码 ${inviteCode}`);
            } else {
                document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#999;">该拍摄链接码下暂无照片</div>';
            }
        })
        .catch(err => {
            console.error('加载照片失败:', err);
            document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#f00;">加载失败，请重试</div>';
        });
}

function loadPhotos(page = 1) {
    currentPhotoPage = page;
    
    // 检查是否有搜索条件
    const searchValue = currentPhotoSearch ? currentPhotoSearch.trim() : '';
    
    // 如果有搜索条件
    if (searchValue) {
        // 如果搜索值是8位字母数字，按拍摄码搜索
        if (/^[a-zA-Z0-9]{8}$/.test(searchValue)) {
            // 按拍摄码搜索
            fetch(`api/admin/get_all_photos.php?page=1&page_size=10000&invite_code=${encodeURIComponent(searchValue)}`)
        .then(res => res.json())
        .then(data => {
                    if (data.success && data.data.list.length > 0) {
                        displayPhotos(data.data.list, `搜索结果：拍摄链接码 ${searchValue}`);
                    } else {
                        document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#999;">未找到该拍摄链接码的照片</div>';
                    }
                })
                .catch(err => {
                    console.error('搜索照片失败:', err);
                    document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#f00;">搜索失败，请重试</div>';
                });
            return;
        } else {
            // 按用户名搜索
            fetch(`api/admin/get_all_photos.php?page=1&page_size=10000&username=${encodeURIComponent(searchValue)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.list.length > 0) {
                        displayPhotos(data.data.list, `搜索结果：用户名 "${searchValue}"`);
                    } else {
                        document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#999;">未找到该用户的照片</div>';
                    }
                })
                .catch(err => {
                    console.error('搜索照片失败:', err);
                    document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#f00;">搜索失败，请重试</div>';
                });
            return;
        }
    }
    
    // 没有搜索条件时，先加载用户列表（懒加载）
    fetch('api/admin/get_users_with_photos.php', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
        .then(res => {
            // 检查响应状态
            if (!res.ok) {
                throw new Error(`HTTP错误: ${res.status} ${res.statusText}`);
            }
            // 先获取文本，以便调试
            return res.text();
        })
        .then(text => {
            // 尝试解析JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON解析失败，响应内容:', text.substring(0, 500));
                throw new Error('服务器返回的不是有效的JSON格式: ' + e.message);
            }
            
            if (data.success) {
                if (!data.data || data.data.length === 0) {
                    document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#999;">暂无用户照片</div>';
                    return;
                }
                
                // 生成用户列表HTML（使用类似用户端的样式）
                let html = '';
                data.data.forEach((user, index) => {
                    const userGroupId = `user-group-${index}`;
                    // 检查用户是否被封禁（status = 0 表示封禁）
                    const isUserBanned = user.status !== undefined && user.status === 0;
                    const bannedBadge = isUserBanned ? '<span class="deleted-badge" style="margin-left: 10px; padding: 2px 6px; background: #dc3545; color: white; border-radius: 3px; font-size: 11px;">用户已封禁</span>' : '';
                    
                    html += `
                        <div class="invite-group" style="margin-bottom: 15px; width: 100%;">
                            <div class="invite-group-header" onclick="toggleUserGroupAndLoad('${userGroupId}', ${user.user_id}, '${escapeHtml(user.user_name || '未知用户')}')" style="padding: 12px 20px;">
                                <span style="display: flex; align-items: center; gap: 12px; flex: 1; width: 100%;">
                                    <span style="font-weight: bold; font-size: 16px; color: #333; flex: 1; min-width: 0;">
                                        👤 <span style="font-size: 18px;">${escapeHtml(user.user_name || '未知用户')}</span>${bannedBadge}
                                        <span style="color: #999; font-weight: normal; font-size: 13px; margin-left: 12px;">
                                            用户ID: <span style="font-family: monospace; color: #5B9BD5;">${user.user_id}</span> | 
                                            照片数量: <span style="color: #5B9BD5; font-weight: 600;">${user.photo_count}</span> 张
                                        </span>
                                    </span>
                                </span>
                                <span class="expand-icon" id="${userGroupId}-icon" style="font-size: 16px; flex-shrink: 0;">▼</span>
                            </div>
                            <div class="invite-group-content" id="${userGroupId}" style="display: none;">
                                <div style="text-align: center; padding: 20px; color: #999;">加载中...</div>
                            </div>
                        </div>
                    `;
                });
                
                document.getElementById('photoList').innerHTML = html;
                document.getElementById('photoPagination').innerHTML = '';
            } else {
                document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#f00;">加载用户列表失败: ' + (data.message || '未知错误') + '</div>';
            }
        })
        .catch(err => {
            console.error('加载用户列表失败:', err);
            document.getElementById('photoList').innerHTML = '<div style="text-align:center; padding:40px; color:#f00;">加载失败: ' + escapeHtml(err.message) + '<br><small>请检查控制台获取详细信息</small></div>';
        });
}

// 切换用户组并加载照片
function toggleUserGroupAndLoad(groupId, userId, userName) {
    const content = document.getElementById(groupId);
    const icon = document.getElementById(groupId + '-icon');
    
    // 如果已经展开，直接切换
    if (content.style.display !== 'none') {
        content.style.display = 'none';
        icon.textContent = '▼';
        return;
    }
    
    // 展开并加载照片
    content.style.display = 'block';
    icon.textContent = '▲';
    
    // 检查是否已经加载过
    if (content.innerHTML.includes('加载中...') || content.innerHTML.trim() === '') {
        // 加载该用户的照片
        fetch(`api/admin/get_all_photos.php?page=1&page_size=10000&user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.list.length > 0) {
                    displayUserPhotos(content, data.data.list, userName, groupId);
                } else {
                    content.innerHTML = '<div style="text-align:center; padding:20px; color:#999;">该用户暂无照片</div>';
                }
            })
            .catch(err => {
                console.error('加载照片失败:', err);
                content.innerHTML = '<div style="text-align:center; padding:20px; color:#f00;">加载失败，请重试</div>';
            });
    }
}

// 显示用户照片
function displayUserPhotos(container, photos, userName, userGroupId) {
    // 按拍摄码分组
    const groupedByInvite = {};
    photos.forEach(photo => {
        const inviteCode = photo.invite_code || '未知';
        if (!groupedByInvite[inviteCode]) {
            groupedByInvite[inviteCode] = [];
        }
        groupedByInvite[inviteCode].push(photo);
    });
    
    let html = '';
    let inviteIndex = 0;
    
    // 按拍摄码排序
    const sortedInviteCodes = Object.keys(groupedByInvite).sort();
    
    for (const inviteCode of sortedInviteCodes) {
        const photos = groupedByInvite[inviteCode];
        // 使用用户组ID作为前缀，确保每个用户的拍摄码组ID唯一
        const inviteGroupId = `${userGroupId}-invite-${inviteIndex}`;
        const inviteLabel = photos[0].invite_label || '';
        
        html += `
            <div class="invite-group" style="margin-bottom: 12px;">
                <div class="invite-group-header" onclick="toggleInviteGroup('${inviteGroupId}')">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1;">
                        <span style="font-weight: bold; font-size: 14px; color: #333;">
                            ${inviteLabel ? `<span style="padding: 2px 8px; background: #5B9BD5; color: white; border-radius: 4px; font-size: 12px; margin-right: 8px;">${inviteLabel}</span>` : ''}
                            拍摄链接码: <span style="color: #5B9BD5;">${inviteCode}</span> <span style="color: #999; font-weight: normal; font-size: 12px;">(${photos.length} 张)</span>
                        </span>
                    </span>
                    <span class="expand-icon" id="${inviteGroupId}-icon">▼</span>
                </div>
                <div class="invite-group-content" id="${inviteGroupId}" style="display: none;">
                    <div class="photo-grid">
        `;
        
        // 显示照片（使用用户端样式）
        photos.forEach(photo => {
                    const thumbnailUrl = photo.thumbnail_url || '';
                    const photoId = photo.photo_id || photo.id;
                    const fileType = photo.file_type || 'photo';
                    const videoDuration = photo.video_duration || null;
                    const uploadTime = photo.upload_time || '';
                    const formatTime = uploadTime ? uploadTime.replace(/:\d{2}$/, '').replace(' ', ' ') : '未知';
            
                    const isVideo = fileType === 'video';
                    const durationText = isVideo && videoDuration ? ` ${Math.floor(videoDuration)}秒` : '';
                    
                    // 检查照片是否已被删除
                    const isPhotoDeleted = photo.deleted_at && photo.deleted_at !== null;
                    const deletedBadge = isPhotoDeleted ? '<div style="position:absolute; top:8px; left:8px; background:#dc3545; color:white; padding:2px 6px; border-radius:3px; font-size:11px; font-weight:bold; z-index:10; white-space:nowrap;">已删除</div>' : '';
                    
                    let mediaHtml = '';
                    if (thumbnailUrl) {
                        mediaHtml = `
                            <img src="${thumbnailUrl}" alt="${isVideo ? '视频缩略图' : '照片'}"
                                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; display: block;"
                                 onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div style=\'position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f0f0f0; color:#999;\'>图片加载失败</div>';">
                            ${isVideo ? `<div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.8); color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:bold; z-index:10; white-space:nowrap;">🎥${durationText}</div>` : ''}
                            ${deletedBadge}
                        `;
                    } else {
                        mediaHtml = `<div style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f0f0f0; color:#999;">加载中...</div>${deletedBadge}`;
                    }
                    
            html += `
                        <div class="photo-item">
                <div class="photo-image-wrapper" onclick="showPhotoDetail(${photoId})" style="cursor: pointer;">
                                ${mediaHtml}
                            </div>
                            <div class="photo-info">
                    <div class="photo-info-item">时间: ${formatTime}</div>
                                </div>
                <div class="photo-actions">
                    <a href="javascript:void(0)" onclick="showPhotoDetail(${photoId})">详情</a>
                    <a href="api/download_photo.php?id=${photoId}&type=original" download>下载</a>
                    <a href="javascript:void(0)" onclick="adminDeletePhoto(${photoId})" class="delete-btn">删除</a>
                                </div>
                                </div>
            `;
        });
        
        html += `
                                </div>
                                </div>
                                </div>
        `;
        inviteIndex++;
    }
    
    container.innerHTML = html;
}

// 显示照片（用于筛选结果）
function displayPhotos(photos, title) {
    // 按用户名和拍摄码分组
    const groupedByUser = {};
    photos.forEach(photo => {
        const userName = photo.user_name || '未知用户';
        const inviteCode = photo.invite_code || '未知';
        
        if (!groupedByUser[userName]) {
            groupedByUser[userName] = {};
        }
        if (!groupedByUser[userName][inviteCode]) {
            groupedByUser[userName][inviteCode] = [];
        }
        groupedByUser[userName][inviteCode].push(photo);
    });
    
    let html = `<div style="margin-bottom: 20px; padding: 12px; background: #e3f2fd; border-radius: 6px; color: #1976d2; font-weight: bold;">${title}</div>`;
    
    const sortedUserNames = Object.keys(groupedByUser).sort();
    let userIndex = 0;
    
    for (const userName of sortedUserNames) {
        const userGroupId = `user-group-${userIndex}`;
        let totalPhotosForUser = 0;
        Object.values(groupedByUser[userName]).forEach(photos => {
            totalPhotosForUser += photos.length;
        });
        
        html += `
            <div class="user-group" style="margin-bottom: 20px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                <div class="user-group-header" onclick="toggleUserGroup('${userGroupId}')" style="padding: 12px 16px; background: #f5f5f5; cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: bold; font-size: 16px; color: #333;">
                        👤 ${userName} <span style="color: #999; font-weight: normal; font-size: 14px; margin-left: 10px;">(${totalPhotosForUser} 张)</span>
                    </span>
                    <span class="expand-icon" id="${userGroupId}-icon">▼</span>
                </div>
                <div class="user-group-content" id="${userGroupId}" style="display: none; padding: 16px;">
        `;
        
        const sortedInviteCodes = Object.keys(groupedByUser[userName]).sort();
        let inviteIndex = 0;
        
        for (const inviteCode of sortedInviteCodes) {
            const photos = groupedByUser[userName][inviteCode];
            const inviteGroupId = `invite-group-${userIndex}-${inviteIndex}`;
            const inviteLabel = photos[0].invite_label || '';
            
            html += `
                <div class="invite-group" style="margin-bottom: 12px;">
                    <div class="invite-group-header" onclick="toggleInviteGroup('${inviteGroupId}')">
                        <span style="display: flex; align-items: center; gap: 10px; flex: 1;">
                            <span style="font-weight: bold; font-size: 14px; color: #333;">
                                ${inviteLabel ? `<span style="padding: 2px 8px; background: #5B9BD5; color: white; border-radius: 4px; font-size: 12px; margin-right: 8px;">${inviteLabel}</span>` : ''}
                                拍摄链接码: <span style="color: #5B9BD5;">${inviteCode}</span> <span style="color: #999; font-weight: normal; font-size: 12px;">(${photos.length} 张)</span>
                            </span>
                        </span>
                        <span class="expand-icon" id="${inviteGroupId}-icon">▼</span>
                    </div>
                    <div class="invite-group-content" id="${inviteGroupId}" style="display: none;">
                        <div class="photo-grid">
            `;
            
            photos.forEach(photo => {
                const thumbnailUrl = photo.thumbnail_url || '';
                const photoId = photo.photo_id || photo.id;
                const fileType = photo.file_type || 'photo';
                const videoDuration = photo.video_duration || null;
                const uploadTime = photo.upload_time || '';
                const formatTime = uploadTime ? uploadTime.replace(/:\d{2}$/, '').replace(' ', ' ') : '未知';
                
                const isVideo = fileType === 'video';
                const durationText = isVideo && videoDuration ? ` ${Math.floor(videoDuration)}秒` : '';
                
                // 检查照片是否已被删除
                const isPhotoDeleted = photo.deleted_at && photo.deleted_at !== null;
                const deletedBadge = isPhotoDeleted ? '<div style="position:absolute; top:8px; left:8px; background:#dc3545; color:white; padding:2px 6px; border-radius:3px; font-size:11px; font-weight:bold; z-index:10; white-space:nowrap;">已删除</div>' : '';
                
                let mediaHtml = '';
                if (thumbnailUrl) {
                    mediaHtml = `
                        <img src="${thumbnailUrl}" alt="${isVideo ? '视频缩略图' : '照片'}"
                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; display: block;"
                             onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div style=\'position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f0f0f0; color:#999;\'>图片加载失败</div>';">
                        ${isVideo ? `<div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.8); color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:bold; z-index:10; white-space:nowrap;">🎥${durationText}</div>` : ''}
                        ${deletedBadge}
                    `;
                } else {
                    mediaHtml = `<div style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f0f0f0; color:#999;">加载中...</div>${deletedBadge}`;
                }
                
                html += `
                <div class="photo-item">
                    <div class="photo-image-wrapper" onclick="showPhotoDetail(${photoId})" style="cursor: pointer;">
                        ${mediaHtml}
                    </div>
                    <div class="photo-info">
                        <div class="photo-info-item">时间: ${formatTime}</div>
                            </div>
                            <div class="photo-actions">
                        <a href="javascript:void(0)" onclick="showPhotoDetail(${photoId})">详情</a>
                                <a href="api/download_photo.php?id=${photoId}&type=original" download>下载</a>
                                <a href="javascript:void(0)" onclick="adminDeletePhoto(${photoId})" class="delete-btn">删除</a>
                            </div>
                        </div>
                    `;
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
            inviteIndex++;
        }
        
        html += `
                </div>
            </div>
        `;
        userIndex++;
    }
    
                document.getElementById('photoList').innerHTML = html;
    document.getElementById('photoPagination').innerHTML = '';
}

// 切换用户组展开/收起
function toggleUserGroup(groupId) {
    const content = document.getElementById(groupId);
    const icon = document.getElementById(groupId + '-icon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.textContent = '▲';
    } else {
        content.style.display = 'none';
        icon.textContent = '▼';
    }
}

// 切换拍摄码组展开/收起
function toggleInviteGroup(groupId) {
    const content = document.getElementById(groupId);
    const icon = document.getElementById(groupId + '-icon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.textContent = '▲';
    } else {
        content.style.display = 'none';
        icon.textContent = '▼';
            }
}

// 管理员删除照片（硬删除，删除文件）
function adminDeletePhoto(photoId) {
    if (!confirm('确定要删除这张照片吗？此操作将永久删除照片和服务器上的文件，无法恢复！')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', photoId);
    
    fetch('api/admin/delete_photo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('照片已删除' + (data.files_deleted ? `（已删除 ${data.files_deleted} 个文件）` : ''));
            loadPhotos(currentPhotoPage); // 重新加载当前页的照片列表
        } else {
            alert('删除失败：' + (data.message || '未知错误'));
        }
    })
    .catch(err => {
        console.error('删除照片错误:', err);
        alert('删除失败，请重试');
    });
}

// 显示照片详情
function showPhotoDetail(photoId) {
    fetch(`api/admin/get_photo_detail.php?id=${photoId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayPhotoDetail(data.data);
            } else {
                alert(data.message || '获取照片信息失败');
            }
        })
        .catch(err => {
            console.error('获取照片详情错误:', err);
            alert('获取照片信息失败');
        });
}

// 显示照片详情内容
function displayPhotoDetail(photo) {
    const modal = document.getElementById('photoDetailModal');
    const content = document.getElementById('photoDetailContent');
    
    if (!modal || !content) {
        alert('模态框未找到');
        return;
    }
    
    const photoId = photo.id || photo.photo_id;
    const fileType = photo.file_type || 'photo';
    const isVideo = fileType === 'video';
    const thumbnailUrl = photo.thumbnail_url || `api/view_photo.php?id=${photoId}&type=original&size=thumbnail`;
    const uploadTime = photo.upload_time || '';
    const formatTime = uploadTime ? uploadTime.replace(/:\d{2}$/, '').replace(' ', ' ') : '未知';
    const uploadIp = photo.upload_ip || '未知';
    const uploadUa = photo.upload_ua || '';
    const browserInfo = parseUserAgent(uploadUa);
    const deviceInfo = parseDeviceModel(uploadUa);
    const inviteCode = photo.invite_code || '未知';
    const inviteLabel = photo.invite_label || '';
    const userName = photo.user_name || '未知用户';
    const tags = photo.tags || [];
    const videoDuration = photo.video_duration || null;
    
    // 检查照片是否已被删除
    const isPhotoDeleted = photo.deleted_at && photo.deleted_at !== null;
    const deletedBadge = isPhotoDeleted ? '<span class="deleted-badge" style="margin-left: 10px; padding: 2px 6px; background: #dc3545; color: white; border-radius: 3px; font-size: 11px;">已删除</span>' : '';
    
    let html = `
        <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 0 0 300px;">
                    <div style="position: relative; width: 100%; padding-bottom: 100%; background: #f0f0f0; border-radius: 8px; overflow: hidden;">
                        ${isVideo ? 
                            `<img src="${thumbnailUrl}" alt="视频缩略图" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                             <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">🎥 ${videoDuration ? Math.floor(videoDuration) + '秒' : ''}</div>
                             ${isPhotoDeleted ? '<div style="position: absolute; top: 8px; left: 8px; background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; z-index: 10;">已删除</div>' : ''}` :
                            `<img src="${thumbnailUrl}" alt="照片" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; background: #f0f0f0;">
                             ${isPhotoDeleted ? '<div style="position: absolute; top: 8px; left: 8px; background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; z-index: 10;">已删除</div>' : ''}`
                        }
                    </div>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">${isVideo ? '🎥 录像详情' : '📷 照片详情'}${deletedBadge}</h3>
                    
                    <table class="info-table" style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; width: 120px; color: #5B9BD5;">照片ID</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">${photoId}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">用户</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">${userName}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">文件类型</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">${isVideo ? '🎥 录像' : '📷 照片'}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">拍摄链接码</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                ${inviteLabel ? `<span style="margin-right: 6px; padding: 2px 6px; background: #5B9BD5; color: white; border-radius: 3px; font-size: 11px;">${inviteLabel}</span>` : ''}
                                <span style="font-family: monospace;">${inviteCode}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">上传时间</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">${formatTime}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">上传IP</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-family: monospace;">${uploadIp}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">浏览器</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;" title="${uploadUa}">${browserInfo}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">设备信息</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;" title="${uploadUa}">${deviceInfo}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">User-Agent</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; color: #666; word-break: break-all;">${uploadUa || '未知'}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">标签</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                ${tags.length > 0 ? 
                                    tags.map(tag => `<span class="photo-tag-admin" style="margin-right: 6px;">${tag.name}</span>`).join('') : 
                                    '<span style="color: #999; font-size: 12px;">无标签</span>'
                                }
                            </td>
                        </tr>
                        ${generateFullExifInfo(photo)}
                    </table>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <a href="api/download_photo.php?id=${photoId}&type=original" download class="btn btn-primary" style="margin-right: 10px;">下载${isVideo ? '录像' : '照片'}</a>
                        <button class="btn" onclick="adminDeletePhoto(${photoId}); closePhotoDetail();" style="background: #dc3545; color: white;">删除</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    // 统一使用classList方式，与用户详情模态框保持一致
    modal.classList.add('active');
    modal.style.display = 'block'; // 兼容旧代码
}

// 生成完整的EXIF信息
function generateFullExifInfo(photo) {
    let exifHtml = '';
    const hasExif = photo.latitude || photo.camera_make || photo.width || photo.longitude || photo.altitude || 
                    photo.camera_model || photo.lens_model || photo.focal_length || photo.aperture || 
                    photo.shutter_speed || photo.iso || photo.exposure_mode || photo.white_balance || 
                    photo.flash || photo.orientation || photo.location_address;
    
    if (!hasExif) {
        return '<tr><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">EXIF信息</td><td style="padding: 8px; border-bottom: 1px solid #eee; color: #999;">无EXIF数据</td></tr>';
    }
    
    exifHtml += '<tr><td colspan="2" style="padding: 12px 8px; border-bottom: 1px solid #eee; background: #f8f9fa;"><strong style="color: #5B9BD5;">📷 拍摄信息</strong></td></tr>';
    
    // 地理位置
    if (photo.latitude && photo.longitude) {
        exifHtml += `<tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">📍 位置坐标</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                ${photo.latitude.toFixed(6)}, ${photo.longitude.toFixed(6)}
                ${photo.altitude ? ` (海拔: ${photo.altitude}m)` : ''}
                <a href="https://www.openstreetmap.org/?mlat=${photo.latitude}&mlon=${photo.longitude}&zoom=15" target="_blank" style="color: #5B9BD5; text-decoration: none; margin-left: 8px;">查看地图</a>
            </td>
        </tr>`;
        if (photo.location_address) {
            exifHtml += `<tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">📍 地址</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">${photo.location_address}</td>
            </tr>`;
        }
    }
    
    // 相机信息
    if (photo.camera_make || photo.camera_model) {
        const cameraInfo = [];
        if (photo.camera_make) cameraInfo.push(photo.camera_make);
        if (photo.camera_model) cameraInfo.push(photo.camera_model);
        exifHtml += `<tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">📷 相机</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">${cameraInfo.join(' ')}</td>
        </tr>`;
        if (photo.lens_model) {
            exifHtml += `<tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">🔍 镜头</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">${photo.lens_model}</td>
            </tr>`;
        }
    }
    
    // 拍摄参数
    if (photo.focal_length || photo.aperture || photo.shutter_speed || photo.iso) {
        exifHtml += '<tr><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">⚙️ 拍摄参数</td><td style="padding: 8px; border-bottom: 1px solid #eee;">';
        const params = [];
        if (photo.focal_length) params.push(`焦距: ${photo.focal_length}`);
        if (photo.aperture) params.push(`光圈: ${photo.aperture}`);
        if (photo.shutter_speed) params.push(`快门: ${photo.shutter_speed}`);
        if (photo.iso) params.push(`ISO: ${photo.iso}`);
        exifHtml += params.join(' | ') + '</td></tr>';
    }
    
    // 其他参数
    if (photo.exposure_mode || photo.white_balance || photo.flash) {
        exifHtml += '<tr><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">其他参数</td><td style="padding: 8px; border-bottom: 1px solid #eee;">';
        const otherParams = [];
        if (photo.exposure_mode) otherParams.push(`曝光模式: ${photo.exposure_mode}`);
        if (photo.white_balance) otherParams.push(`白平衡: ${photo.white_balance}`);
        if (photo.flash) otherParams.push(`闪光灯: ${photo.flash}`);
        exifHtml += otherParams.join(' | ') + '</td></tr>';
    }
    
    // 照片尺寸
    if (photo.width && photo.height) {
        exifHtml += `<tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">📐 尺寸</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">${photo.width} × ${photo.height} 像素</td>
        </tr>`;
    }
    
    // 方向
    if (photo.orientation) {
        exifHtml += `<tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: 600; color: #5B9BD5;">方向</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">${photo.orientation}</td>
        </tr>`;
    }
    
    return exifHtml;
}

// 关闭照片详情
function closePhotoDetail() {
    const modal = document.getElementById('photoDetailModal');
    if (modal) {
        // 统一使用classList方式，与用户详情模态框保持一致
        modal.classList.remove('active');
        modal.style.display = 'none'; // 兼容旧代码，确保关闭
    }
}

function logout() {
    if (confirm('确定要退出登录吗？')) {
        fetch('api/admin/logout.php')
            .then(res => res.json())
            .then(data => {
                window.location.href = 'admin_login.php';
            });
    }
}

// 点击模态框外部关闭
window.onclick = function(event) {
    const userModal = document.getElementById('userDetailModal');
    if (event.target == userModal) {
        closeUserDetail();
    }
    const photoModal = document.getElementById('photoDetailModal');
    if (event.target == photoModal) {
        closePhotoDetail();
    }
}

function showSetVipModal(userId, isVip, vipExpireTime) {
    const modal = document.getElementById('setVipModal');
    if (!modal) {
        alert('模态框未找到');
        return;
    }
    
    // 设置用户ID
    document.getElementById('vipUserId').value = userId;
    
    // 设置VIP状态
    document.getElementById('vipCheckbox').checked = isVip == 1;
    
    // 设置到期时间
    const expireTimeInput = document.getElementById('vipExpireTimeInput');
    if (isVip == 1 && vipExpireTime) {
        // 转换为datetime-local格式
        expireTimeInput.value = vipExpireTime.replace(' ', 'T').substring(0, 16);
    } else {
        expireTimeInput.value = '';
    }
    
    updateVipExpireInput();
    modal.style.display = 'block';
}

function closeSetVipModal() {
    const modal = document.getElementById('setVipModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function updateVipExpireInput() {
    const vipCheckbox = document.getElementById('vipCheckbox');
    const expireTimeInput = document.getElementById('vipExpireTimeInput');
    const unlimitedCheckbox = document.getElementById('vipUnlimitedCheckbox');
    const vipExpireGroup = document.getElementById('vipExpireGroup');
    const vipExpireTimeGroup = document.getElementById('vipExpireTimeGroup');
    
    if (!vipCheckbox.checked) {
        vipExpireGroup.style.display = 'none';
        vipExpireTimeGroup.style.display = 'none';
        expireTimeInput.disabled = true;
        unlimitedCheckbox.disabled = true;
        expireTimeInput.value = '';
        unlimitedCheckbox.checked = false;
    } else {
        vipExpireGroup.style.display = 'block';
        unlimitedCheckbox.disabled = false;
        if (unlimitedCheckbox.checked) {
            vipExpireTimeGroup.style.display = 'none';
            expireTimeInput.disabled = true;
            expireTimeInput.value = '';
        } else {
            vipExpireTimeGroup.style.display = 'block';
            expireTimeInput.disabled = false;
            // 如果为空，设置默认值为30天后
            if (!expireTimeInput.value) {
                const defaultDate = new Date();
                defaultDate.setDate(defaultDate.getDate() + 30);
                expireTimeInput.value = defaultDate.toISOString().slice(0, 16);
            }
        }
    }
}

function submitVipForm() {
    const userId = document.getElementById('vipUserId').value;
    const isVip = document.getElementById('vipCheckbox').checked ? 1 : 0;
    const unlimitedCheckbox = document.getElementById('vipUnlimitedCheckbox');
    const expireTimeInput = document.getElementById('vipExpireTimeInput');
    
    let vipExpireTime = null;
    if (isVip == 1) {
        if (unlimitedCheckbox.checked) {
            vipExpireTime = 'unlimited';
        } else if (expireTimeInput.value) {
            vipExpireTime = expireTimeInput.value.replace('T', ' ');
        } else {
            alert('请设置VIP到期时间或选择永久VIP');
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('is_vip', isVip);
    if (vipExpireTime) {
        formData.append('vip_expire_time', vipExpireTime);
    }
    
    fetch('api/admin/set_vip.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeSetVipModal();
            loadUsers(currentUserPage);
            // 如果当前显示的是该用户的详情，刷新详情
            const detailModal = document.getElementById('userDetailModal');
            if (detailModal && detailModal.classList.contains('active')) {
                const currentUserId = document.getElementById('vipUserId').value;
                showUserDetail(currentUserId);
            }
        } else {
            alert(data.message || '操作失败');
        }
    });
}

// 加载系统设置
function loadSettings() {
    // 加载系统配置
    fetch('api/admin/get_system_config.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const systemConfigs = data.data;
                const requireVerification = systemConfigs['register_require_email_verification'] == '1';
                
                // 加载其他配置
                fetch('api/admin/get_config.php')
                    .then(res => res.json())
                    .then(configData => {
                        if (configData.success) {
                            const configs = configData.data;
                            const emailConfig = configs.email || {};
                            const pointsConfig = configs.points || {};
                            const inviteConfig = configs.invite || {};
                            
                            const projectName = systemConfigs['project_name'] || '拍摄上传系统';
                            const videoMaxDuration = systemConfigs['video_max_duration'] || '15';
                            
                            // 获取邮件模板配置
                            const emailTemplateVerificationSubject = systemConfigs['email_template_verification_subject'] || '邮箱验证码';
                            const emailTemplateVerificationBody = systemConfigs['email_template_verification_body'] || '<html>\n<head>\n    <meta charset="utf-8">\n</head>\n<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">\n    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">\n        <h2 style="color: #5B9BD5;">邮箱验证码</h2>\n        <p>您的验证码是：</p>\n        <div style="background: #f0f4ff; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; color: #5B9BD5; letter-spacing: 5px; margin: 20px 0; border-radius: 8px;">\n            {code}\n        </div>\n        <p style="color: #999; font-size: 12px;">验证码有效期为10分钟，请勿泄露给他人。</p>\n    </div>\n</body>\n</html>';
                            const emailTemplateResetSubject = systemConfigs['email_template_reset_subject'] || '密码重置';
                            const emailTemplateResetBody = systemConfigs['email_template_reset_body'] || '<html>\n<head>\n    <meta charset="utf-8">\n</head>\n<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">\n    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">\n        <h2 style="color: #5B9BD5;">密码重置</h2>\n        <p>您申请了密码重置，请点击下面的链接重置密码：</p>\n        <div style="text-align: center; margin: 30px 0;">\n            <a href="{resetUrl}" style="display: inline-block; padding: 12px 30px; background: #5B9BD5; color: white; text-decoration: none; border-radius: 6px;">重置密码</a>\n        </div>\n        <p style="color: #999; font-size: 12px;">如果无法点击链接，请复制以下地址到浏览器：</p>\n        <p style="color: #999; font-size: 12px; word-break: break-all;">{resetUrl}</p>\n        <p style="color: #999; font-size: 12px;">链接有效期为1小时，请勿泄露给他人。</p>\n    </div>\n</body>\n</html>';
                            const emailTemplatePhotoSubject = systemConfigs['email_template_photo_subject'] || '您收到了新照片';
                            const emailTemplatePhotoBody = systemConfigs['email_template_photo_body'] || '<html>\n<head>\n    <meta charset="utf-8">\n</head>\n<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">\n    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">\n        <h2 style="color: #5B9BD5;">您收到了新照片</h2>\n        <p>亲爱的 {username}，</p>\n        <p>您收到了 <strong>{photoCount}</strong> 张新照片，请登录查看。</p>\n        <div style="text-align: center; margin: 30px 0;">\n            <a href="{siteUrl}/dashboard.php" style="display: inline-block; padding: 12px 30px; background: #5B9BD5; color: white; text-decoration: none; border-radius: 6px;">查看照片</a>\n        </div>\n    </div>\n</body>\n</html>';
                            
                            let html = `
                                <div class="settings-tabs">
                                    <button class="settings-tab active" onclick="showConfigTab('basic')">⚙️ 基础设置</button>
                                    <button class="settings-tab" onclick="showConfigTab('register')">📝 注册设置</button>
                                    <button class="settings-tab" onclick="showConfigTab('email')">📧 邮件配置</button>
                                    <button class="settings-tab" onclick="showConfigTab('points')">⭐ 积分配置</button>
                                    <button class="settings-tab" onclick="showConfigTab('invite')">🔗 拍摄链接配置</button>
                                </div>
                                
                                <!-- 基础设置 -->
                                <div id="config-basic" class="config-section active">
                                    <div class="config-card">
                                        <h3>基础设置</h3>
                                        <div class="form-group">
                                            <label>项目名称</label>
                                            <input type="text" id="projectName" class="form-control" value="${projectName}" placeholder="例如: 拍摄上传系统">
                                            <p class="form-desc">项目名称将显示在页面标题和邮件中</p>
                                        </div>
                                        <div class="form-group">
                                            <label>录像最大时长（秒）</label>
                                            <input type="number" id="videoMaxDuration" class="form-control" value="${videoMaxDuration}" min="10" max="300" placeholder="例如: 60">
                                            <p class="form-desc">录像的最大时长限制，范围10-300秒</p>
                                        </div>
                                        <div class="form-actions">
                                            <button class="btn-save" onclick="saveBasicSettings()">💾 保存设置</button>
                                            <span class="save-status" id="basicSaveStatus"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 注册设置 -->
                                <div id="config-register" class="config-section">
                                    <div class="config-card">
                                        <h3>注册设置</h3>
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="requireVerification" ${requireVerification ? 'checked' : ''}>
                                                <span>注册时强制邮箱验证</span>
                                            </label>
                                            <p class="form-desc">开启后，用户注册时必须填写邮箱并验证后才能登录（邮箱自动变为必填项）</p>
                                        </div>
                                        <div class="form-actions">
                                            <button class="btn-save" onclick="saveRegisterSettings()">💾 保存设置</button>
                                            <span class="save-status" id="registerSaveStatus"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 邮件配置 -->
                                <div id="config-email" class="config-section">
                                    <div class="config-card">
                                        <h3>邮件配置</h3>
                                        <div class="config-group-title">基础设置</div>
                                        <div class="form-group">
                                            <label>启用邮件功能</label>
                                            <select id="emailEnabled" class="form-control">
                                                <option value="1" ${emailConfig.enabled ? 'selected' : ''}>✅ 启用</option>
                                                <option value="0" ${!emailConfig.enabled ? 'selected' : ''}>❌ 禁用</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>发件人邮箱</label>
                                            <input type="email" id="emailFrom" class="form-control" value="${emailConfig.from || ''}" placeholder="例如: noreply@example.com">
                                        </div>
                                        <div class="form-group">
                                            <label>发件人名称</label>
                                            <input type="text" id="emailFromName" class="form-control" value="${emailConfig.from_name || ''}" placeholder="例如: 拍摄上传系统">
                                        </div>
                                        
                                        <div class="config-group-title" style="margin-top: 30px;">SMTP服务器设置</div>
                                        <div class="form-group">
                                            <label>SMTP服务器</label>
                                            <input type="text" id="emailSmtpHost" class="form-control" value="${emailConfig.smtp_host || ''}" placeholder="例如: smtp.163.com">
                                        </div>
                                        <div class="form-group">
                                            <label>SMTP端口</label>
                                            <input type="number" id="emailSmtpPort" class="form-control" value="${emailConfig.smtp_port || 465}" placeholder="例如: 465">
                                        </div>
                                        <div class="form-group">
                                            <label>SMTP用户名</label>
                                            <input type="text" id="emailSmtpUser" class="form-control" value="${emailConfig.smtp_user || ''}" placeholder="SMTP登录用户名">
                                        </div>
                                        <div class="form-group">
                                            <label>SMTP密码/授权码</label>
                                            <input type="password" id="emailSmtpPass" class="form-control" value="${emailConfig.smtp_pass || ''}" placeholder="SMTP密码或授权码">
                                        </div>
                                        <div class="form-group">
                                            <label>加密方式</label>
                                            <select id="emailSmtpSecure" class="form-control">
                                                <option value="ssl" ${emailConfig.smtp_secure == 'ssl' ? 'selected' : ''}>SSL (推荐，端口465)</option>
                                                <option value="tls" ${emailConfig.smtp_secure == 'tls' ? 'selected' : ''}>TLS (端口587)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="config-group-title" style="margin-top: 30px;">邮件模板设置</div>
                                        
                                        <div class="form-group">
                                            <label>邮箱验证码邮件标题</label>
                                            <input type="text" id="emailTemplateVerificationSubject" class="form-control" value="${emailTemplateVerificationSubject}" placeholder="例如: 邮箱验证码">
                                            <p class="form-desc">可用变量：{code} - 验证码</p>
                                        </div>
                                        <div class="form-group">
                                            <label>邮箱验证码邮件内容</label>
                                            <textarea id="emailTemplateVerificationBody" class="form-control" rows="10" placeholder="HTML格式的邮件内容">${emailTemplateVerificationBody}</textarea>
                                            <p class="form-desc">可用变量：{code} - 验证码</p>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>密码重置邮件标题</label>
                                            <input type="text" id="emailTemplateResetSubject" class="form-control" value="${emailTemplateResetSubject}" placeholder="例如: 密码重置">
                                            <p class="form-desc">可用变量：{resetUrl} - 重置链接</p>
                                        </div>
                                        <div class="form-group">
                                            <label>密码重置邮件内容</label>
                                            <textarea id="emailTemplateResetBody" class="form-control" rows="12" placeholder="HTML格式的邮件内容">${emailTemplateResetBody}</textarea>
                                            <p class="form-desc">可用变量：{resetUrl} - 重置链接</p>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>照片提醒邮件标题</label>
                                            <input type="text" id="emailTemplatePhotoSubject" class="form-control" value="${emailTemplatePhotoSubject}" placeholder="例如: 您收到了新照片">
                                            <p class="form-desc">可用变量：{username} - 用户名, {photoCount} - 照片数量</p>
                                        </div>
                                        <div class="form-group">
                                            <label>照片提醒邮件内容</label>
                                            <textarea id="emailTemplatePhotoBody" class="form-control" rows="12" placeholder="HTML格式的邮件内容">${emailTemplatePhotoBody}</textarea>
                                            <p class="form-desc">可用变量：{username} - 用户名, {photoCount} - 照片数量, {siteUrl} - 站点URL</p>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button class="btn-save" onclick="saveEmailConfig()">💾 保存配置</button>
                                            <span class="save-status" id="emailSaveStatus"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 积分配置 -->
                                <div id="config-points" class="config-section">
                                    <div class="config-card">
                                        <h3>积分配置</h3>
                                        <div class="config-group-title">邀请奖励</div>
                                        <div class="form-group">
                                            <label>新用户通过注册码注册获得的积分</label>
                                            <input type="number" id="pointsInviteRewardNewUser" class="form-control" value="${pointsConfig.invite_reward_new_user ?? 10}" min="0">
                                            <p class="form-desc">用户通过注册码注册时，新用户获得的积分奖励（不使用注册码注册不获得积分）</p>
                                        </div>
                                        <div class="form-group">
                                            <label>邀请人获得的积分</label>
                                            <input type="number" id="pointsInviteRewardInviter" class="form-control" value="${pointsConfig.invite_reward_inviter ?? 10}" min="0">
                                            <p class="form-desc">当有人通过您的注册码注册时，您获得的积分奖励</p>
                                        </div>
                                        
                                        <div class="config-group-title" style="margin-top: 30px;">签到奖励</div>
                                        <div class="form-group">
                                            <label>每日签到基础奖励积分</label>
                                            <input type="number" id="pointsCheckinReward" class="form-control" value="${pointsConfig.checkin_reward || 5}" min="0">
                                            <p class="form-desc">用户每日签到获得的基础积分</p>
                                        </div>
                                        <div class="form-group">
                                            <label>VIP会员签到额外奖励积分</label>
                                            <input type="number" id="pointsCheckinVipBonus" class="form-control" value="${pointsConfig.checkin_vip_bonus || 3}" min="0">
                                            <p class="form-desc">VIP会员签到时的额外积分奖励</p>
                                        </div>
                                        
                                        <div class="config-group-title" style="margin-top: 30px;">连续签到奖励</div>
                                        <div class="form-group">
                                            <label>连续签到额外奖励（JSON格式）</label>
                                            <textarea id="pointsCheckinConsecutiveBonus" class="form-control" rows="6" placeholder='{"3": 5, "7": 10, "15": 20, "30": 50}'>${JSON.stringify(pointsConfig.checkin_consecutive_bonus || {}, null, 2)}</textarea>
                                            <p class="form-desc">格式：{"天数": 积分, ...}，例如：{"3": 5, "7": 10, "15": 20, "30": 50}</p>
                                        </div>
                                        <div class="form-group">
                                            <label>VIP会员连续签到额外奖励（JSON格式）</label>
                                            <textarea id="pointsCheckinVipConsecutiveBonus" class="form-control" rows="6" placeholder='{"3": 8, "7": 15, "15": 30, "30": 80}'>${JSON.stringify(pointsConfig.checkin_vip_consecutive_bonus || {}, null, 2)}</textarea>
                                            <p class="form-desc">格式：{"天数": 积分, ...}，例如：{"3": 8, "7": 15, "15": 30, "30": 80}</p>
                                        </div>
                                        <div class="form-actions">
                                            <button class="btn-save" onclick="savePointsConfig()">💾 保存配置</button>
                                            <span class="save-status" id="pointsSaveStatus"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 邀请链接配置 -->
                                <div id="config-invite" class="config-section">
                                    <div class="config-card">
                                        <h3>拍摄链接配置</h3>
                                        <div class="form-group">
                                            <label>默认有效期（天）</label>
                                            <input type="number" id="inviteDefaultExpireDays" class="form-control" value="${inviteConfig.default_expire_days || 7}" min="1">
                                            <p class="form-desc">新生成的拍摄链接默认有效期（VIP用户可设置永久有效）</p>
                                        </div>
                                        <div class="form-group">
                                            <label>每个用户最多可生成的拍摄链接数量</label>
                                            <input type="number" id="inviteMaxCount" class="form-control" value="${inviteConfig.max_count || 7}" min="1">
                                            <p class="form-desc">普通用户最多可生成的拍摄链接数量（VIP用户不受限制）</p>
                                        </div>
                                        <div class="form-actions">
                                            <button class="btn-save" onclick="saveInviteConfig()">💾 保存配置</button>
                                            <span class="save-status" id="inviteSaveStatus"></span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            document.getElementById('settingsContent').innerHTML = html;
                        }
                    })
                    .catch(err => {
                        console.error('加载配置失败:', err);
                    });
            }
        })
        .catch(err => {
            console.error('加载设置失败:', err);
            document.getElementById('settingsContent').innerHTML = '<p style="color: #dc3545;">加载设置失败，请刷新重试</p>';
        });
}

// 显示配置标签页
function showConfigTab(tab) {
    document.querySelectorAll('.config-section').forEach(section => {
        section.classList.remove('active');
    });
    document.querySelectorAll('.settings-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById('config-' + tab).classList.add('active');
    event.target.classList.add('active');
}

// 保存基础设置
function saveBasicSettings() {
    const projectName = document.getElementById('projectName').value.trim();
    const videoMaxDuration = document.getElementById('videoMaxDuration').value.trim();
    const statusEl = document.getElementById('basicSaveStatus');
    
    if (!projectName) {
        statusEl.textContent = '✗ 项目名称不能为空';
        statusEl.style.color = '#dc3545';
        statusEl.classList.add('show');
        setTimeout(() => {
            statusEl.classList.remove('show');
            statusEl.style.color = '#28a745';
        }, 3000);
        return;
    }
    
    if (!videoMaxDuration || parseInt(videoMaxDuration) < 10 || parseInt(videoMaxDuration) > 300) {
        statusEl.textContent = '✗ 录像时长必须在10-300秒之间';
        statusEl.style.color = '#dc3545';
        statusEl.classList.add('show');
        setTimeout(() => {
            statusEl.classList.remove('show');
            statusEl.style.color = '#28a745';
        }, 3000);
        return;
    }
    
    const configs = [
        { key: 'project_name', value: projectName, desc: '项目名称' },
        { key: 'video_max_duration', value: videoMaxDuration, desc: '录像最大时长（秒）' }
    ];
    
    let savePromises = configs.map(config => {
        const formData = new FormData();
        formData.append('key', config.key);
        formData.append('value', config.value);
        formData.append('description', config.desc);
        
        return fetch('api/admin/set_system_config.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json());
    });
    
    Promise.all(savePromises)
        .then(results => {
            const allSuccess = results.every(r => r.success);
            if (allSuccess) {
                statusEl.textContent = '✓ 保存成功';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                }, 2000);
                // 更新页面标题
                document.title = '管理员后台 - ' + projectName;
            } else {
                statusEl.textContent = '✗ 保存失败';
                statusEl.style.color = '#dc3545';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                    statusEl.style.color = '#28a745';
                }, 3000);
            }
        })
        .catch(err => {
            console.error('保存设置失败:', err);
            statusEl.textContent = '✗ 保存失败，请重试';
            statusEl.style.color = '#dc3545';
            statusEl.classList.add('show');
            setTimeout(() => {
                statusEl.classList.remove('show');
                statusEl.style.color = '#28a745';
            }, 3000);
        });
}

// 保存注册设置
function saveRegisterSettings() {
    const requireVerification = document.getElementById('requireVerification').checked ? '1' : '0';
    const statusEl = document.getElementById('registerSaveStatus');
    
    const configs = [
        { key: 'register_require_email_verification', value: requireVerification }
    ];
    
    let savePromises = configs.map(config => {
        const formData = new FormData();
        formData.append('key', config.key);
        formData.append('value', config.value);
        
        return fetch('api/admin/set_system_config.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json());
    });
    
    Promise.all(savePromises)
        .then(results => {
            const allSuccess = results.every(r => r.success);
            if (allSuccess) {
                statusEl.textContent = '✓ 保存成功';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                }, 2000);
            } else {
                statusEl.textContent = '✗ 保存失败';
                statusEl.style.color = '#dc3545';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                    statusEl.style.color = '#28a745';
                }, 3000);
            }
        })
        .catch(err => {
            console.error('保存设置失败:', err);
            statusEl.textContent = '✗ 保存失败，请重试';
            statusEl.style.color = '#dc3545';
            statusEl.classList.add('show');
            setTimeout(() => {
                statusEl.classList.remove('show');
                statusEl.style.color = '#28a745';
            }, 3000);
        });
}

// 保存邮件配置
function saveEmailConfig() {
    const configData = {
        enabled: document.getElementById('emailEnabled').value == '1',
        from: document.getElementById('emailFrom').value.trim(),
        from_name: document.getElementById('emailFromName').value.trim(),
        smtp_host: document.getElementById('emailSmtpHost').value.trim(),
        smtp_port: parseInt(document.getElementById('emailSmtpPort').value) || 465,
        smtp_user: document.getElementById('emailSmtpUser').value.trim(),
        smtp_pass: document.getElementById('emailSmtpPass').value.trim(),
        smtp_secure: document.getElementById('emailSmtpSecure').value
    };
    
    // 邮件模板配置
    const emailTemplates = [
        { key: 'email_template_verification_subject', value: document.getElementById('emailTemplateVerificationSubject').value.trim(), desc: '邮箱验证码邮件标题' },
        { key: 'email_template_verification_body', value: document.getElementById('emailTemplateVerificationBody').value.trim(), desc: '邮箱验证码邮件内容' },
        { key: 'email_template_reset_subject', value: document.getElementById('emailTemplateResetSubject').value.trim(), desc: '密码重置邮件标题' },
        { key: 'email_template_reset_body', value: document.getElementById('emailTemplateResetBody').value.trim(), desc: '密码重置邮件内容' },
        { key: 'email_template_photo_subject', value: document.getElementById('emailTemplatePhotoSubject').value.trim(), desc: '照片提醒邮件标题' },
        { key: 'email_template_photo_body', value: document.getElementById('emailTemplatePhotoBody').value.trim(), desc: '照片提醒邮件内容' }
    ];
    
    const statusEl = document.getElementById('emailSaveStatus');
    
    // 先保存SMTP配置
    const formData = new FormData();
    formData.append('type', 'email');
    formData.append('data', JSON.stringify(configData));
    
    fetch('api/admin/save_config.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // 保存邮件模板配置
                const templatePromises = emailTemplates.map(template => {
                    const templateFormData = new FormData();
                    templateFormData.append('key', template.key);
                    templateFormData.append('value', template.value);
                    templateFormData.append('description', template.desc);
                    
                    return fetch('api/admin/set_system_config.php', {
                        method: 'POST',
                        body: templateFormData
                    }).then(res => res.json());
                });
                
                return Promise.all(templatePromises);
            } else {
                throw new Error(data.message || '保存失败');
            }
        })
        .then(results => {
            const allSuccess = results.every(r => r.success);
            if (allSuccess) {
                statusEl.textContent = '✓ 保存成功';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                }, 2000);
            } else {
                throw new Error('部分配置保存失败');
            }
        })
        .catch(err => {
            console.error('保存邮件配置失败:', err);
            statusEl.textContent = '✗ ' + (err.message || '保存失败，请重试');
            statusEl.style.color = '#dc3545';
            statusEl.classList.add('show');
            setTimeout(() => {
                statusEl.classList.remove('show');
                statusEl.style.color = '#28a745';
            }, 3000);
        });
}

// 保存积分配置
function savePointsConfig() {
    const statusEl = document.getElementById('pointsSaveStatus');
    let consecutiveBonus = {};
    let vipConsecutiveBonus = {};
    
    try {
        consecutiveBonus = JSON.parse(document.getElementById('pointsCheckinConsecutiveBonus').value);
    } catch (e) {
        statusEl.textContent = '✗ 连续签到额外奖励格式错误，请检查JSON格式';
        statusEl.style.color = '#dc3545';
        statusEl.classList.add('show');
        setTimeout(() => {
            statusEl.classList.remove('show');
            statusEl.style.color = '#28a745';
        }, 3000);
        return;
    }
    
    try {
        vipConsecutiveBonus = JSON.parse(document.getElementById('pointsCheckinVipConsecutiveBonus').value);
    } catch (e) {
        statusEl.textContent = '✗ VIP会员连续签到额外奖励格式错误，请检查JSON格式';
        statusEl.style.color = '#dc3545';
        statusEl.classList.add('show');
        setTimeout(() => {
            statusEl.classList.remove('show');
            statusEl.style.color = '#28a745';
        }, 3000);
        return;
    }
    
    const configData = {
        invite_reward_new_user: parseInt(document.getElementById('pointsInviteRewardNewUser').value) || 10,
        invite_reward_inviter: parseInt(document.getElementById('pointsInviteRewardInviter').value) || 10,
        checkin_reward: parseInt(document.getElementById('pointsCheckinReward').value) || 5,
        checkin_vip_bonus: parseInt(document.getElementById('pointsCheckinVipBonus').value) || 3,
        checkin_consecutive_bonus: consecutiveBonus,
        checkin_vip_consecutive_bonus: vipConsecutiveBonus
    };
    
    const formData = new FormData();
    formData.append('type', 'points');
    formData.append('data', JSON.stringify(configData));
    
    fetch('api/admin/save_config.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = '✓ 保存成功';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                }, 2000);
            } else {
                statusEl.textContent = '✗ ' + (data.message || '保存失败');
                statusEl.style.color = '#dc3545';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                    statusEl.style.color = '#28a745';
                }, 3000);
            }
        })
        .catch(err => {
            console.error('保存积分配置失败:', err);
            statusEl.textContent = '✗ 保存失败，请重试';
            statusEl.style.color = '#dc3545';
            statusEl.classList.add('show');
            setTimeout(() => {
                statusEl.classList.remove('show');
                statusEl.style.color = '#28a745';
            }, 3000);
        });
}

// 保存拍摄链接配置
function saveInviteConfig() {
    const statusEl = document.getElementById('inviteSaveStatus');
    const configData = {
        default_expire_days: parseInt(document.getElementById('inviteDefaultExpireDays').value) || 7,
        max_count: parseInt(document.getElementById('inviteMaxCount').value) || 7
    };
    
    const formData = new FormData();
    formData.append('type', 'invite');
    formData.append('data', JSON.stringify(configData));
    
    fetch('api/admin/save_config.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = '✓ 保存成功';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                }, 2000);
            } else {
                statusEl.textContent = '✗ ' + (data.message || '保存失败');
                statusEl.style.color = '#dc3545';
                statusEl.classList.add('show');
                setTimeout(() => {
                    statusEl.classList.remove('show');
                    statusEl.style.color = '#28a745';
                }, 3000);
            }
        })
        .catch(err => {
            console.error('保存拍摄链接配置失败:', err);
            statusEl.textContent = '✗ 保存失败，请重试';
            statusEl.style.color = '#dc3545';
            statusEl.classList.add('show');
            setTimeout(() => {
                statusEl.classList.remove('show');
                statusEl.style.color = '#28a745';
            }, 3000);
        });
}

// 积分类型名称映射
function getPointsTypeName(type) {
    const typeMap = {
        'invite_reward': '邀请奖励',
        'checkin_reward': '签到奖励',
        'admin_adjust': '管理员调整',
        'register_reward': '注册奖励'
    };
    return typeMap[type] || type;
}

// 显示调整积分模态框
function showAdjustPointsModal(userId, currentPoints) {
    const points = prompt(`当前积分：${currentPoints}\n\n请输入要调整的积分（正数为增加，负数为减少）：`, '0');
    
    if (points === null) {
        return; // 用户取消
    }
    
    const adjustPoints = parseInt(points);
    if (isNaN(adjustPoints) || adjustPoints === 0) {
        alert('请输入有效的积分值（不能为0）');
        return;
    }
    
    const remark = prompt('请输入备注（可选）：', adjustPoints > 0 ? '管理员增加积分' : '管理员减少积分');
    if (remark === null) {
        return; // 用户取消
    }
    
    if (!confirm(`确定要${adjustPoints > 0 ? '增加' : '减少'} ${Math.abs(adjustPoints)} 积分吗？\n调整后积分：${currentPoints + adjustPoints}`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('points', adjustPoints);
    formData.append('remark', remark || '');
    
    fetch('api/admin/adjust_points.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`积分调整成功！\n原积分：${data.old_points}\n调整：${data.adjust_points > 0 ? '+' : ''}${data.adjust_points}\n新积分：${data.new_points}`);
                // 重新加载用户详情
                showUserDetail(userId);
            } else {
                alert(data.message || '调整失败');
            }
        })
        .catch(err => {
            console.error('调整积分失败:', err);
            alert('调整失败，请重试');
        });
}

// ==================== 用户历史日志功能 ====================

let currentLoginLogPage = 1;
let currentPointsLogPage = 1;
let currentPhotoLogPage = 1;
let currentLoginLogSearch = '';
let currentPointsLogSearch = '';
let currentPhotoLogSearch = '';

// 显示日志标签页
function showLogTab(tab) {
    document.querySelectorAll('.log-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.log-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tab).classList.add('active');
    
    // 激活对应的标签按钮
    const tabs = document.querySelectorAll('.log-tab');
    tabs.forEach((btn, index) => {
        const tabNames = ['login_logs', 'points_logs', 'photo_logs'];
        if (tabNames[index] === tab) {
            btn.classList.add('active');
        }
    });
    
    if (tab === 'login_logs') {
        loadLoginLogs();
    } else if (tab === 'points_logs') {
        loadPointsLogs();
    } else if (tab === 'photo_logs') {
        loadPhotoLogs();
    }
}

// 加载登录日志
function loadLoginLogs(page = 1) {
    currentLoginLogPage = page;
    const searchParam = currentLoginLogSearch ? `&search=${encodeURIComponent(currentLoginLogSearch)}` : '';
    fetch(`api/admin/get_user_login_logs.php?page=${page}&page_size=50${searchParam}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.data.list;
                let html = '<table><thead><tr><th>时间</th><th>用户</th><th>登录IP</th><th>状态</th><th>失败原因</th><th>浏览器信息</th></tr></thead><tbody>';
                
                if (logs.length === 0) {
                    html += '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">暂无登录记录</td></tr>';
                } else {
                    logs.forEach(log => {
                        const displayName = log.user_id ? 
                            (log.nickname && log.nickname.trim() ? log.nickname : log.username) : 
                            '未登录';
                        const statusBadge = log.is_success == 1 ? 
                            '<span class="status-badge status-active">成功</span>' : 
                            '<span class="status-badge status-banned">失败</span>';
                        const formatTime = log.login_time ? log.login_time.replace(/:\d{2}$/, '') : '';
                        const browserInfo = parseUserAgent(log.login_ua || '');
                        
                        html += `
                            <tr>
                                <td style="width: 160px; white-space: nowrap;">${escapeHtml(formatTime)}</td>
                                <td style="min-width: 150px;">
                                    ${log.user_id ? `<a href="javascript:void(0)" onclick="showUserDetail(${log.user_id})" style="color: #5B9BD5;">${escapeHtml(displayName)}</a> (ID: ${log.user_id})` : '未登录'}
                                </td>
                                <td style="width: 120px; white-space: nowrap;">${escapeHtml(log.login_ip || '未知')}</td>
                                <td style="width: 80px; text-align: center;">${statusBadge}</td>
                                <td style="width: 150px;">${escapeHtml(log.fail_reason || '-')}</td>
                                <td style="min-width: 200px;" title="${escapeHtml(log.login_ua || '')}">${escapeHtml(browserInfo)}</td>
                            </tr>
                        `;
                    });
                }
                
                html += '</tbody></table>';
                
                // 分页
                const total = data.data.total;
                const pageSize = data.data.page_size;
                const totalPages = Math.ceil(total / pageSize);
                if (totalPages > 1) {
                    html += '<div class="pagination">';
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<button class="${i == page ? 'active' : ''}" onclick="loadLoginLogs(${i})">${i}</button>`;
                    }
                    html += '</div>';
                }
                
                document.getElementById('loginLogList').innerHTML = html;
            }
        })
        .catch(err => {
            console.error('加载登录日志失败:', err);
            document.getElementById('loginLogList').innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">加载失败，请刷新重试</div>';
        });
}

// 搜索登录日志
function searchLoginLogs() {
    currentLoginLogSearch = document.getElementById('loginLogSearch').value.trim();
    loadLoginLogs(1);
}

// 重置登录日志搜索
function resetLoginLogSearch() {
    document.getElementById('loginLogSearch').value = '';
    currentLoginLogSearch = '';
    loadLoginLogs(1);
}

// 加载积分变动日志
function loadPointsLogs(page = 1) {
    currentPointsLogPage = page;
    const searchParam = currentPointsLogSearch ? `&search=${encodeURIComponent(currentPointsLogSearch)}` : '';
    fetch(`api/admin/get_user_points_logs.php?page=${page}&page_size=50${searchParam}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.data.list;
                let html = '<table><thead><tr><th>时间</th><th>用户</th><th>类型</th><th>积分变动</th><th>备注</th><th>关联信息</th></tr></thead><tbody>';
                
                if (logs.length === 0) {
                    html += '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">暂无积分变动记录</td></tr>';
                } else {
                    logs.forEach(log => {
                        const displayName = (log.nickname && log.nickname.trim()) ? log.nickname : log.username;
                        const typeName = log.remark || getPointsTypeName(log.type);
                        const pointsText = log.points > 0 ? `+${log.points}` : `${log.points}`;
                        const pointsClass = log.points > 0 ? 'color: #28a745; font-weight: bold;' : 'color: #dc3545; font-weight: bold;';
                        const formatTime = log.create_time ? log.create_time.replace(/:\d{2}$/, '') : '';
                        
                        let relatedInfo = '';
                        if (log.type === 'invite_reward') {
                            if (log.remark === '通过邀请码注册奖励' || log.remark === '通过注册码注册奖励') {
                                const inviterName = (log.related_user_nickname && log.related_user_nickname.trim()) 
                                    ? log.related_user_nickname 
                                    : (log.related_user_name || '未知用户');
                                relatedInfo = `邀请人：${escapeHtml(inviterName)}`;
                            } else if (log.remark === '邀请新用户注册奖励') {
                                const invitedName = (log.related_user_nickname && log.related_user_nickname.trim()) 
                                    ? log.related_user_nickname 
                                    : (log.related_user_name || '未知用户');
                                relatedInfo = `被邀请人：${escapeHtml(invitedName)}`;
                            }
                        } else if (log.related_user_name) {
                            const userName = (log.related_user_nickname && log.related_user_nickname.trim()) 
                                ? log.related_user_nickname 
                                : log.related_user_name;
                            relatedInfo = `关联用户：${escapeHtml(userName)}`;
                        }
                        
                        html += `
                            <tr>
                                <td style="width: 160px; white-space: nowrap;">${escapeHtml(formatTime)}</td>
                                <td style="min-width: 150px;">
                                    <a href="javascript:void(0)" onclick="showUserDetail(${log.user_id})" style="color: #5B9BD5;">${escapeHtml(displayName)}</a> (ID: ${log.user_id})
                                </td>
                                <td style="width: 120px;">${escapeHtml(typeName)}</td>
                                <td style="width: 100px; text-align: center; ${pointsClass}">${pointsText}</td>
                                <td style="min-width: 200px;">${escapeHtml(log.remark || '-')}</td>
                                <td style="min-width: 150px;">${relatedInfo || '-'}</td>
                            </tr>
                        `;
                    });
                }
                
                html += '</tbody></table>';
                
                // 分页
                const total = data.data.total;
                const pageSize = data.data.page_size;
                const totalPages = Math.ceil(total / pageSize);
                if (totalPages > 1) {
                    html += '<div class="pagination">';
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<button class="${i == page ? 'active' : ''}" onclick="loadPointsLogs(${i})">${i}</button>`;
                    }
                    html += '</div>';
                }
                
                document.getElementById('pointsLogList').innerHTML = html;
            }
        })
        .catch(err => {
            console.error('加载积分变动日志失败:', err);
            document.getElementById('pointsLogList').innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">加载失败，请刷新重试</div>';
        });
}

// 搜索积分变动日志
function searchPointsLogs() {
    currentPointsLogSearch = document.getElementById('pointsLogSearch').value.trim();
    loadPointsLogs(1);
}

// 重置积分变动日志搜索
function resetPointsLogSearch() {
    document.getElementById('pointsLogSearch').value = '';
    currentPointsLogSearch = '';
    loadPointsLogs(1);
}

// 加载照片上传日志
function loadPhotoLogs(page = 1) {
    currentPhotoLogPage = page;
    const searchParam = currentPhotoLogSearch ? `&search=${encodeURIComponent(currentPhotoLogSearch)}` : '';
    fetch(`api/admin/get_user_photo_logs.php?page=${page}&page_size=50${searchParam}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.data.list;
                let html = '<table><thead><tr><th>时间</th><th>用户</th><th>拍摄链接码</th><th>上传IP</th><th>浏览器</th><th>设备</th></tr></thead><tbody>';
                
                if (logs.length === 0) {
                    html += '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">暂无照片上传记录</td></tr>';
                } else {
                    logs.forEach(log => {
                        const displayName = (log.nickname && log.nickname.trim()) ? log.nickname : log.username;
                        const formatTime = log.upload_time ? log.upload_time.replace(/:\d{2}$/, '') : '';
                        const browserInfo = parseUserAgent(log.upload_ua || '');
                        const deviceInfo = parseDeviceModel(log.upload_ua || '');
                        
                        html += `
                            <tr>
                                <td style="width: 160px; white-space: nowrap;">${escapeHtml(formatTime)}</td>
                                <td style="min-width: 150px;">
                                    <a href="javascript:void(0)" onclick="showUserDetail(${log.user_id})" style="color: #5B9BD5;">${escapeHtml(displayName)}</a> (ID: ${log.user_id})
                                </td>
                                <td style="width: 120px; font-family: monospace;" title="${log.invite_code ? (log.invite_code.length === 6 ? '注册码（6位）' : log.invite_code.length === 8 ? '拍摄链接码（8位）' : '') : ''}">${escapeHtml(log.invite_code || '未知')}</td>
                                <td style="width: 120px; white-space: nowrap;">${escapeHtml(log.upload_ip || '未知')}</td>
                                <td style="min-width: 200px;" title="${escapeHtml(log.upload_ua || '')}">${escapeHtml(browserInfo)}</td>
                                <td style="min-width: 200px;" title="${escapeHtml(log.upload_ua || '')}">${escapeHtml(deviceInfo)}</td>
                            </tr>
                        `;
                    });
                }
                
                html += '</tbody></table>';
                
                // 分页
                const total = data.data.total;
                const pageSize = data.data.page_size;
                const totalPages = Math.ceil(total / pageSize);
                if (totalPages > 1) {
                    html += '<div class="pagination">';
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<button class="${i == page ? 'active' : ''}" onclick="loadPhotoLogs(${i})">${i}</button>`;
                    }
                    html += '</div>';
                }
                
                document.getElementById('photoLogList').innerHTML = html;
            }
        })
        .catch(err => {
            console.error('加载照片上传日志失败:', err);
            document.getElementById('photoLogList').innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">加载失败，请刷新重试</div>';
        });
}

// 搜索照片上传日志
function searchPhotoLogs() {
    currentPhotoLogSearch = document.getElementById('photoLogSearch').value.trim();
    loadPhotoLogs(1);
}

// 重置照片上传日志搜索
function resetPhotoLogSearch() {
    document.getElementById('photoLogSearch').value = '';
    currentPhotoLogSearch = '';
    loadPhotoLogs(1);
}

// ==================== 系统维护功能 ====================

let currentAdminLogPage = 1;
let currentAbnormalLogPage = 1;
let currentAdminLogSearch = '';
let currentAbnormalLogSearch = '';
let currentAbnormalLogSeverity = '';
let currentAbnormalLogHandled = '';

// 显示维护标签页
function showMaintenanceTab(tab) {
    document.querySelectorAll('.maintenance-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.maintenance-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tab).classList.add('active');
    
    // 激活对应的标签按钮
    const tabs = document.querySelectorAll('.maintenance-tab');
    const tabNames = ['system_logs', 'admin_logs', 'abnormal_behavior'];
    tabs.forEach((btn, index) => {
        if (tabNames[index] === tab) {
            btn.classList.add('active');
        }
    });
    
    if (tab === 'system_logs') {
        loadSystemErrorLogs();
    } else if (tab === 'admin_logs') {
        loadAdminLogs();
    } else if (tab === 'abnormal_behavior') {
        loadAbnormalLogs();
    }
}

// 加载系统错误日志
function loadSystemErrorLogs() {
    const lines = document.getElementById('errorLogLines').value;
    fetch(`api/admin/get_system_error_logs.php?lines=${lines}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.data.list;
                if (logs.length === 0) {
                    document.getElementById('systemErrorLogList').textContent = '暂无错误日志';
                    return;
                }
                
                let html = logs.map(log => {
                    const time = log.time ? `[${log.time}] ` : '';
                    return time + log.content;
                }).join('\n');
                
                document.getElementById('systemErrorLogList').textContent = html;
            }
        })
        .catch(err => {
            console.error('加载系统错误日志失败:', err);
            document.getElementById('systemErrorLogList').textContent = '加载失败，请刷新重试';
        });
}

// 加载管理员操作日志
function loadAdminLogs(page = 1) {
    currentAdminLogPage = page;
    const searchParam = currentAdminLogSearch ? `&search=${encodeURIComponent(currentAdminLogSearch)}` : '';
    fetch(`api/admin/get_admin_operation_logs.php?page=${page}&page_size=50${searchParam}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.data.list;
                let html = '<table><thead><tr><th>时间</th><th>管理员</th><th>操作类型</th><th>目标</th><th>描述</th><th>IP</th></tr></thead><tbody>';
                
                if (logs.length === 0) {
                    html += '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">暂无操作记录</td></tr>';
                } else {
                    logs.forEach(log => {
                        const formatTime = log.created_at ? log.created_at.replace(/:\d{2}$/, '') : '';
                        const operationTypeMap = {
                            'user_ban': '封禁用户',
                            'user_unban': '解封用户',
                            'points_adjust': '调整积分',
                            'vip_set': '设置VIP',
                            'config_update': '更新配置',
                        };
                        const operationTypeName = operationTypeMap[log.operation_type] || log.operation_type;
                        
                        html += `
                            <tr>
                                <td style="width: 160px; white-space: nowrap;">${formatTime}</td>
                                <td style="width: 120px;">${log.admin_username}</td>
                                <td style="width: 120px;">${operationTypeName}</td>
                                <td style="width: 100px;">${log.target_type || '-'} ${log.target_id ? `(${log.target_id})` : ''}</td>
                                <td style="min-width: 200px;">${log.description || '-'}</td>
                                <td style="width: 120px; white-space: nowrap;">${log.ip_address || '未知'}</td>
                            </tr>
                        `;
                    });
                }
                
                html += '</tbody></table>';
                
                // 分页
                const total = data.data.total;
                const pageSize = data.data.page_size;
                const totalPages = Math.ceil(total / pageSize);
                if (totalPages > 1) {
                    html += '<div class="pagination">';
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<button class="${i == page ? 'active' : ''}" onclick="loadAdminLogs(${i})">${i}</button>`;
                    }
                    html += '</div>';
                }
                
                document.getElementById('adminLogList').innerHTML = html;
            }
        })
        .catch(err => {
            console.error('加载管理员操作日志失败:', err);
            document.getElementById('adminLogList').innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">加载失败，请刷新重试</div>';
        });
}

// 搜索管理员操作日志
function searchAdminLogs() {
    currentAdminLogSearch = document.getElementById('adminLogSearch').value.trim();
    loadAdminLogs(1);
}

// 重置管理员操作日志搜索
function resetAdminLogSearch() {
    document.getElementById('adminLogSearch').value = '';
    currentAdminLogSearch = '';
    loadAdminLogs(1);
}

// 加载异常行为记录
function loadAbnormalLogs(page = 1) {
    currentAbnormalLogPage = page;
    const searchParam = currentAbnormalLogSearch ? `&search=${encodeURIComponent(currentAbnormalLogSearch)}` : '';
    const severityParam = currentAbnormalLogSeverity ? `&severity=${currentAbnormalLogSeverity}` : '';
    const handledParam = currentAbnormalLogHandled !== '' ? `&is_handled=${currentAbnormalLogHandled}` : '';
    
    fetch(`api/admin/get_abnormal_behavior_logs.php?page=${page}&page_size=50${searchParam}${severityParam}${handledParam}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logs = data.data.list;
                let html = '<table><thead><tr><th>时间</th><th>用户</th><th>行为类型</th><th>严重程度</th><th>描述</th><th>IP</th><th>状态</th><th>操作</th></tr></thead><tbody>';
                
                if (logs.length === 0) {
                    html += '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #999;">暂无异常行为记录</td></tr>';
                } else {
                    logs.forEach(log => {
                        const formatTime = log.created_at ? log.created_at.replace(/:\d{2}$/, '') : '';
                        const displayName = log.user_id ? 
                            (log.nickname && log.nickname.trim() ? log.nickname : log.username) : 
                            '未登录';
                        const behaviorTypeMap = {
                            'unusual_login': '异常登录',
                            'multiple_failed_login': '多次失败登录',
                            'suspicious_activity': '可疑活动'
                        };
                        const behaviorTypeName = behaviorTypeMap[log.behavior_type] || log.behavior_type;
                        const severityMap = {1: '低', 2: '中', 3: '高'};
                        const severityName = severityMap[log.severity] || '未知';
                        const severityColor = log.severity == 3 ? '#dc3545' : (log.severity == 2 ? '#ffc107' : '#28a745');
                        const handledBadge = log.is_handled == 1 ? 
                            `<span class="status-badge status-active">已处理</span>` : 
                            `<span class="status-badge status-banned">未处理</span>`;
                        
                        html += `
                            <tr>
                                <td style="width: 160px; white-space: nowrap;">${escapeHtml(formatTime)}</td>
                                <td style="min-width: 120px;">
                                    ${log.user_id ? `<a href="javascript:void(0)" onclick="showUserDetail(${log.user_id})" style="color: #5B9BD5;">${escapeHtml(displayName)}</a> (ID: ${log.user_id})` : '未登录'}
                                </td>
                                <td style="width: 120px;">${escapeHtml(behaviorTypeName)}</td>
                                <td style="width: 80px; text-align: center; color: ${severityColor}; font-weight: bold;">${escapeHtml(severityName)}</td>
                                <td style="min-width: 200px;">${escapeHtml(log.description || '-')}</td>
                                <td style="width: 120px; white-space: nowrap;">${escapeHtml(log.ip_address || '未知')}</td>
                                <td style="width: 100px; text-align: center;">${handledBadge}</td>
                                <td style="width: 100px; white-space: nowrap;">
                                    ${log.is_handled == 0 ? `<button class="btn btn-sm btn-success" onclick="handleAbnormalBehavior(${log.id})" style="padding: 4px 12px; font-size: 12px;">标记已处理</button>` : '-'}
                                </td>
                            </tr>
                        `;
                    });
                }
                
                html += '</tbody></table>';
                
                // 分页
                const total = data.data.total;
                const pageSize = data.data.page_size;
                const totalPages = Math.ceil(total / pageSize);
                if (totalPages > 1) {
                    html += '<div class="pagination">';
                    for (let i = 1; i <= totalPages; i++) {
                        html += `<button class="${i == page ? 'active' : ''}" onclick="loadAbnormalLogs(${i})">${i}</button>`;
                    }
                    html += '</div>';
                }
                
                document.getElementById('abnormalLogList').innerHTML = html;
            }
        })
        .catch(err => {
            console.error('加载异常行为记录失败:', err);
            document.getElementById('abnormalLogList').innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">加载失败，请刷新重试</div>';
        });
}

// 搜索异常行为记录
function searchAbnormalLogs() {
    currentAbnormalLogSearch = document.getElementById('abnormalLogSearch').value.trim();
    currentAbnormalLogSeverity = document.getElementById('abnormalLogSeverity').value;
    currentAbnormalLogHandled = document.getElementById('abnormalLogHandled').value;
    loadAbnormalLogs(1);
}

// 重置异常行为记录搜索
function resetAbnormalLogSearch() {
    document.getElementById('abnormalLogSearch').value = '';
    document.getElementById('abnormalLogSeverity').value = '';
    document.getElementById('abnormalLogHandled').value = '';
    currentAbnormalLogSearch = '';
    currentAbnormalLogSeverity = '';
    currentAbnormalLogHandled = '';
    loadAbnormalLogs(1);
}

// 标记异常行为为已处理
function handleAbnormalBehavior(logId) {
    const note = prompt('请输入处理备注（可选）：', '');
    if (note === null) {
        return; // 用户取消
    }
    
    const formData = new FormData();
    formData.append('log_id', logId);
    if (note) {
        formData.append('note', note);
    }
    
    fetch('api/admin/handle_abnormal_behavior.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('已标记为已处理');
                loadAbnormalLogs(currentAbnormalLogPage);
            } else {
                alert('操作失败：' + (data.message || '未知错误'));
            }
        })
        .catch(err => {
            console.error('处理异常行为失败:', err);
            alert('操作失败，请重试');
        });
}

let currentAnnouncementPage = 1;
let currentReadStatusPage = 1;
let currentReadStatusAnnouncementId = 0;

// 加载公告列表
function loadAnnouncements(page = 1) {
    currentAnnouncementPage = page;
    fetch(`api/admin/get_announcements.php?page=${page}&page_size=20`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderAnnouncements(data.data);
            } else {
                document.getElementById('announcementList').innerHTML = '<p>加载失败</p>';
            }
        })
        .catch(err => {
            console.error('加载公告列表错误:', err);
            document.getElementById('announcementList').innerHTML = '<p>加载失败</p>';
        });
}

// 渲染公告列表
function renderAnnouncements(data) {
    const { list, total, page, page_size } = data;
    
    if (!list || list.length === 0) {
        document.getElementById('announcementList').innerHTML = '<p>暂无公告</p>';
        document.getElementById('announcementPagination').innerHTML = '';
        return;
    }
    
    const levelMap = {
        'important': { text: '重要', class: 'level-important', color: '#dc3545' },
        'normal': { text: '一般', class: 'level-normal', color: '#007bff' },
        'notice': { text: '通知', class: 'level-notice', color: '#28a745' }
    };
    
    const html = list.map(announcement => {
        const level = levelMap[announcement.level] || levelMap['normal'];
        const formatTime = announcement.create_time ? announcement.create_time.replace(/:\d{2}$/, '') : '';
        
        return `
            <div class="announcement-item" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                            <span class="${level.class}" style="padding: 4px 12px; background: ${level.color}; color: white; border-radius: 4px; font-size: 12px; font-weight: bold;">${level.text}</span>
                            <strong style="font-size: 16px; color: #333;">${announcement.title}</strong>
                        </div>
                        <div style="font-size: 12px; color: #999;">
                            发布者：${announcement.admin_username || '未知'} | 
                            发布时间：${formatTime}
                            ${announcement.require_read ? ' | <span style="color: #dc3545;">强制已读</span>' : ''}
                            ${announcement.is_visible ? '' : ' | <span style="color: #999;">已隐藏</span>'}
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn" onclick="showAnnouncementReadStatus(${announcement.id})" style="padding: 4px 12px; font-size: 12px; background: #17a2b8;">已读状态</button>
                        <button class="btn" onclick="editAnnouncement(${announcement.id})" style="padding: 4px 12px; font-size: 12px;">编辑</button>
                        <button class="btn" onclick="deleteAnnouncement(${announcement.id})" style="padding: 4px 12px; font-size: 12px; background: #dc3545;">删除</button>
                    </div>
                </div>
                <div class="admin-announcement-content" style="color: #666; line-height: 1.6; word-break: break-word;" data-content-type="${announcement.content_type || 'auto'}"></div>
            </div>
        `;
    }).join('');
    
    document.getElementById('announcementList').innerHTML = html;
    
    // 渲染内容（支持HTML和Markdown）
    document.querySelectorAll('.admin-announcement-content').forEach(async (el, index) => {
        const announcement = list[index];
        if (announcement && announcement.content) {
            const content = announcement.content;
            const contentType = announcement.content_type || 'auto';
            el.innerHTML = await renderContent(content, contentType);
        }
    });
    
    // 分页
    const totalPages = Math.ceil(total / page_size);
    let paginationHtml = '';
    if (totalPages > 1) {
        paginationHtml = '<div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">';
        if (page > 1) {
            paginationHtml += `<button class="btn" onclick="loadAnnouncements(${page - 1})">上一页</button>`;
        }
        paginationHtml += `<span style="line-height: 38px;">第 ${page} / ${totalPages} 页，共 ${total} 条</span>`;
        if (page < totalPages) {
            paginationHtml += `<button class="btn" onclick="loadAnnouncements(${page + 1})">下一页</button>`;
        }
        paginationHtml += '</div>';
    }
    document.getElementById('announcementPagination').innerHTML = paginationHtml;
}

// 显示公告编辑模态框
function showAnnouncementModal() {
    document.getElementById('announcementModalTitle').textContent = '发布新公告';
    document.getElementById('announcementId').value = '';
    document.getElementById('announcementTitle').value = '';
    document.getElementById('announcementContent').value = '';
    document.getElementById('announcementContentType').value = 'auto';
    document.getElementById('announcementLevel').value = 'normal';
    document.getElementById('announcementRequireRead').checked = false;
    document.getElementById('announcementIsVisible').checked = true;
    updateContentTypeHint();
    document.getElementById('announcementModal').style.display = 'block';
}

// 编辑公告
function editAnnouncement(id) {
    fetch(`api/admin/get_announcement_detail.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                const announcement = data.data;
                document.getElementById('announcementModalTitle').textContent = '编辑公告';
                document.getElementById('announcementId').value = announcement.id;
                document.getElementById('announcementTitle').value = announcement.title;
                document.getElementById('announcementContent').value = announcement.content;
                document.getElementById('announcementContentType').value = announcement.content_type || 'auto';
                document.getElementById('announcementLevel').value = announcement.level;
                updateContentTypeHint();
                document.getElementById('announcementRequireRead').checked = announcement.require_read == 1;
                document.getElementById('announcementIsVisible').checked = announcement.is_visible == 1;
                document.getElementById('announcementModal').style.display = 'block';
            } else {
                alert('获取公告详情失败');
            }
        })
        .catch(err => {
            console.error('获取公告详情错误:', err);
            alert('获取公告详情失败');
        });
}

// 关闭公告模态框
function closeAnnouncementModal() {
    document.getElementById('announcementModal').style.display = 'none';
}

// 提交公告
// 更新内容类型提示
function updateContentTypeHint() {
    const type = document.getElementById('announcementContentType').value;
    const hint = document.getElementById('contentTypeHint');
    const hints = {
        'plain': '纯文本格式，换行将被保留',
        'html': 'HTML格式，支持HTML标签',
        'markdown': 'Markdown格式，支持Markdown语法',
        'auto': '系统将自动检测内容格式'
    };
    if (hint) {
        hint.textContent = hints[type] || hints['auto'];
    }
}

function submitAnnouncement() {
    const id = document.getElementById('announcementId').value;
    const title = document.getElementById('announcementTitle').value.trim();
    const content = document.getElementById('announcementContent').value.trim();
    const contentType = document.getElementById('announcementContentType').value;
    const level = document.getElementById('announcementLevel').value;
    const requireRead = document.getElementById('announcementRequireRead').checked;
    const isVisible = document.getElementById('announcementIsVisible').checked;
    
    if (!title || !content) {
        alert('标题和内容不能为空');
        return;
    }
    
    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('title', title);
    formData.append('content', content);
    formData.append('content_type', contentType);
    formData.append('level', level);
    formData.append('require_read', requireRead ? '1' : '0');
    formData.append('is_visible', isVisible ? '1' : '0');
    
    const url = id ? 'api/admin/update_announcement.php' : 'api/admin/create_announcement.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(id ? '更新成功' : '发布成功');
                closeAnnouncementModal();
                loadAnnouncements(currentAnnouncementPage);
            } else {
                alert(data.message || '操作失败');
            }
        })
        .catch(err => {
            console.error('提交公告错误:', err);
            alert('操作失败，请重试');
        });
}

// 删除公告
function deleteAnnouncement(id) {
    if (!confirm('确定要删除这条公告吗？')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('api/admin/delete_announcement.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('删除成功');
                loadAnnouncements(currentAnnouncementPage);
            } else {
                alert(data.message || '删除失败');
            }
        })
        .catch(err => {
            console.error('删除公告错误:', err);
            alert('删除失败，请重试');
        });
}

// 显示公告已读状态
function showAnnouncementReadStatus(announcementId, page = 1) {
    currentReadStatusPage = page;
    currentReadStatusAnnouncementId = announcementId;
    
    fetch(`api/admin/get_announcement_read_status.php?announcement_id=${announcementId}&page=${page}&page_size=20`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderAnnouncementReadStatus(data.data);
                document.getElementById('announcementReadStatusModal').style.display = 'block';
            } else {
                alert(data.message || '加载失败');
            }
        })
        .catch(err => {
            console.error('加载已读状态错误:', err);
            alert('加载失败，请重试');
        });
}

// 渲染公告已读状态
function renderAnnouncementReadStatus(data) {
    const { list, total, page, page_size } = data;
    
    if (!list || list.length === 0) {
        document.getElementById('announcementReadStatusList').innerHTML = '<p>暂无数据</p>';
        document.getElementById('announcementReadStatusPagination').innerHTML = '';
        return;
    }
    
    const html = list.map(user => {
        const isRead = user.is_read == 1;
        const readTime = user.read_time ? user.read_time.replace(/:\d{2}$/, '') : '';
        const displayName = (user.nickname && user.nickname.trim()) ? user.nickname : user.username;
        
        return `
            <div style="padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: bold; margin-bottom: 4px;">${escapeHtml(displayName)}</div>
                    <div style="font-size: 12px; color: #999;">ID: ${user.id} | 用户名: ${escapeHtml(user.username)}</div>
                </div>
                <div style="text-align: right;">
                    ${isRead ? 
                        `<span style="color: #28a745; font-weight: bold;">✓ 已读</span><br><span style="font-size: 12px; color: #999;">${escapeHtml(readTime)}</span>` : 
                        `<span style="color: #dc3545; font-weight: bold;">✗ 未读</span>`
                    }
                </div>
            </div>
        `;
    }).join('');
    
    document.getElementById('announcementReadStatusList').innerHTML = html;
    
    // 分页
    const totalPages = Math.ceil(total / page_size);
    let paginationHtml = '';
    if (totalPages > 1) {
        paginationHtml = '<div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">';
        if (page > 1) {
            paginationHtml += `<button class="btn" onclick="showAnnouncementReadStatus(${currentReadStatusAnnouncementId}, ${page - 1})">上一页</button>`;
        }
        paginationHtml += `<span style="line-height: 38px;">第 ${page} / ${totalPages} 页，共 ${total} 条</span>`;
        if (page < totalPages) {
            paginationHtml += `<button class="btn" onclick="showAnnouncementReadStatus(${currentReadStatusAnnouncementId}, ${page + 1})">下一页</button>`;
        }
        paginationHtml += '</div>';
    }
    document.getElementById('announcementReadStatusPagination').innerHTML = paginationHtml;
}

// 关闭公告已读状态模态框
function closeAnnouncementReadStatusModal() {
    document.getElementById('announcementReadStatusModal').style.display = 'none';
}

// 点击模态框外部关闭
window.onclick = function(event) {
    const announcementModal = document.getElementById('announcementModal');
    const readStatusModal = document.getElementById('announcementReadStatusModal');
    const shopProductModal = document.getElementById('shopProductModal');
    if (event.target == announcementModal) {
        closeAnnouncementModal();
    }
    if (event.target == readStatusModal) {
        closeAnnouncementReadStatusModal();
    }
    if (event.target == shopProductModal) {
        closeShopProductModal();
    }
}

// ==================== 积分商城管理 ====================

let currentShopProductPage = 1;
let currentEditProductId = null;

// 加载商品列表
function loadShopProducts(page = 1) {
    currentShopProductPage = page;
    const status = document.getElementById('shopStatusFilter')?.value || '';
    
    let url = `api/admin/get_shop_products.php?page=${page}&page_size=20`;
    if (status !== '') {
        url += `&status=${status}`;
    }
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayShopProducts(data.data);
            } else {
                alert('加载商品列表失败：' + data.message);
            }
        })
        .catch(err => {
            console.error('加载商品列表错误：', err);
            alert('加载失败，请稍后重试');
        });
}

// 显示商品列表
function displayShopProducts(data) {
    const { list, total, page, page_size } = data;
    
    if (list.length === 0) {
        document.getElementById('shopProductList').innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">暂无商品</div>';
        document.getElementById('shopProductPagination').innerHTML = '';
        return;
    }
    
    const html = list.map(product => {
        const typeMap = {
            'vip_temporary': '临时VIP',
            'vip_permanent': '永久VIP',
            'invite_limit': '拍摄链接数量',
        };
        
        const typeText = typeMap[product.type] || product.type;
        const statusText = product.status == 1 ? '<span style="color: #28a745;">已上架</span>' : '<span style="color: #999;">已下架</span>';
        const stockInfo = product.total_stock !== null 
            ? `库存：${product.total_stock - product.sold_count} / ${product.total_stock}` 
            : '库存：不限';
        const valueInfo = product.value !== null ? ` | 数值：${product.value}` : '';
        const maxPerUserInfo = product.max_per_user !== null ? ` | 每人限兑：${product.max_per_user}次` : '';
        
        return `
            <div class="data-row" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; border-bottom: 1px solid #eee;">
                <div style="flex: 1;">
                    <div style="font-weight: bold; margin-bottom: 5px;">${product.name} ${statusText}</div>
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                        类型：${typeText} | 积分：${product.points_price}${valueInfo}${maxPerUserInfo}
                    </div>
                    <div style="font-size: 12px; color: #999;">
                        ${stockInfo} | 已售：${product.sold_count} | 排序：${product.sort_order}
                    </div>
                    ${product.description ? `<div class="admin-shop-product-description" style="font-size: 12px; color: #666; margin-top: 5px;" data-content-type="${product.description_type || 'auto'}"></div>` : ''}
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-sm" onclick="editShopProduct(${product.id})">编辑</button>
                    ${product.status == 1 
                        ? `<button class="btn btn-sm btn-warning" onclick="toggleShopProductStatus(${product.id}, 0)">下架</button>`
                        : `<button class="btn btn-sm btn-success" onclick="toggleShopProductStatus(${product.id}, 1)">上架</button>`
                    }
                    <button class="btn btn-sm btn-danger" onclick="deleteShopProduct(${product.id})">删除</button>
                </div>
            </div>
        `;
    }).join('');
    
    document.getElementById('shopProductList').innerHTML = html;
    
    // 渲染商品描述（支持HTML和Markdown）
    document.querySelectorAll('.admin-shop-product-description').forEach(async (el) => {
        // 通过父元素找到商品ID
        const dataRow = el.closest('.data-row');
        if (!dataRow) return;
        
        // 从按钮的onclick中提取商品ID
        const editBtn = dataRow.querySelector('button[onclick*="editShopProduct"]');
        if (!editBtn) return;
        
        const onclickAttr = editBtn.getAttribute('onclick');
        const match = onclickAttr.match(/editShopProduct\((\d+)\)/);
        if (!match) return;
        
        const productId = parseInt(match[1]);
        const product = list.find(p => p.id == productId);
        
        if (product && product.description) {
            const content = product.description;
            const contentType = product.description_type || 'auto';
            el.innerHTML = await renderContent(content, contentType);
        }
    });
    
    // 分页
    const totalPages = Math.ceil(total / page_size);
    let paginationHtml = '';
    if (totalPages > 1) {
        paginationHtml = '<div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">';
        if (page > 1) {
            paginationHtml += `<button class="btn" onclick="loadShopProducts(${page - 1})">上一页</button>`;
        }
        paginationHtml += `<span style="line-height: 38px;">第 ${page} / ${totalPages} 页，共 ${total} 条</span>`;
        if (page < totalPages) {
            paginationHtml += `<button class="btn" onclick="loadShopProducts(${page + 1})">下一页</button>`;
        }
        paginationHtml += '</div>';
    }
    document.getElementById('shopProductPagination').innerHTML = paginationHtml;
}

// 显示添加/编辑商品模态框
function showShopProductModal(productId = null) {
    currentEditProductId = productId;
    const modal = document.getElementById('shopProductModal');
    if (!modal) {
        createShopProductModal();
    }
    
    if (productId) {
        // 编辑模式，加载商品信息
        fetch(`api/admin/get_shop_product.php?id=${productId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fillShopProductForm(data.data);
                } else {
                    alert('加载商品信息失败：' + data.message);
                }
            })
            .catch(err => {
                console.error('加载商品信息错误：', err);
                alert('加载失败，请稍后重试');
            });
    } else {
        // 添加模式，清空表单
        document.getElementById('shopProductForm').reset();
        document.getElementById('shopProductModalTitle').textContent = '添加商品';
    }
    
    document.getElementById('shopProductModal').style.display = 'block';
}

// 创建商品表单模态框
function createShopProductModal() {
    const modal = document.createElement('div');
    modal.id = 'shopProductModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="shopProductModalTitle">添加商品</h3>
                <span class="close" onclick="closeShopProductModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="shopProductForm" onsubmit="saveShopProduct(event)">
                    <input type="hidden" id="shopProductId">
                    <div style="margin-bottom: 15px;">
                        <label>商品名称 *</label>
                        <input type="text" id="shopProductName" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>描述类型</label>
                        <select id="shopProductDescriptionType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;">
                            <option value="plain">纯文本</option>
                            <option value="html">HTML</option>
                            <option value="markdown">Markdown</option>
                            <option value="auto" selected>自动检测</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>商品描述</label>
                        <textarea id="shopProductDescription" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>商品类型 *</label>
                        <select id="shopProductType" required onchange="updateShopProductTypeFields()" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">请选择</option>
                            <option value="vip_temporary">临时VIP</option>
                            <option value="vip_permanent">永久VIP</option>
                            <option value="invite_limit">拍摄链接数量</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>所需积分 *</label>
                        <input type="number" id="shopProductPointsPrice" required min="1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div id="shopProductValueDiv" style="margin-bottom: 15px; display: none;">
                        <label>数值（天数/数量） *</label>
                        <input type="number" id="shopProductValue" min="1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666;">临时VIP需要填写天数，拍摄链接数量需要填写增加的数量</small>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>总库存（留空表示不限）</label>
                        <input type="number" id="shopProductTotalStock" min="1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>每人限兑次数（留空表示不限）</label>
                        <input type="number" id="shopProductMaxPerUser" min="1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>排序顺序（数字越大越靠前）</label>
                        <input type="number" id="shopProductSortOrder" value="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>
                            <input type="checkbox" id="shopProductStatus" checked> 上架
                        </label>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" class="btn" onclick="closeShopProductModal()">取消</button>
                        <button type="submit" class="btn btn-primary" style="margin-left: 10px;">保存</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// 更新商品类型字段显示
function updateShopProductTypeFields() {
    const type = document.getElementById('shopProductType').value;
    const valueDiv = document.getElementById('shopProductValueDiv');
    const valueInput = document.getElementById('shopProductValue');
    const valueLabel = valueDiv.querySelector('label');
    const valueSmall = valueDiv.querySelector('small');
    
    if (type === 'vip_temporary') {
        valueDiv.style.display = 'block';
        valueInput.required = true;
        if (valueLabel) valueLabel.textContent = 'VIP天数 *';
        if (valueSmall) valueSmall.textContent = '临时VIP需要填写天数';
    } else if (type === 'invite_limit') {
        valueDiv.style.display = 'block';
        valueInput.required = true;
        if (valueLabel) valueLabel.textContent = '增加数量 *';
        if (valueSmall) valueSmall.textContent = '拍摄链接数量需要填写增加的数量';
    } else {
        valueDiv.style.display = 'none';
        valueInput.required = false;
        valueInput.value = '';
    }
}

// 填充商品表单
function fillShopProductForm(product) {
    document.getElementById('shopProductModalTitle').textContent = '编辑商品';
    document.getElementById('shopProductId').value = product.id;
    document.getElementById('shopProductName').value = product.name;
    document.getElementById('shopProductDescription').value = product.description || '';
    document.getElementById('shopProductDescriptionType').value = product.description_type || 'auto';
    document.getElementById('shopProductType').value = product.type;
    document.getElementById('shopProductPointsPrice').value = product.points_price;
    document.getElementById('shopProductValue').value = product.value || '';
    document.getElementById('shopProductTotalStock').value = product.total_stock || '';
    document.getElementById('shopProductMaxPerUser').value = product.max_per_user || '';
    document.getElementById('shopProductSortOrder').value = product.sort_order || 0;
    document.getElementById('shopProductStatus').checked = product.status == 1;
    updateShopProductTypeFields();
}

// 保存商品
function saveShopProduct(event) {
    event.preventDefault();
    
    const id = document.getElementById('shopProductId').value;
    const data = {
        name: document.getElementById('shopProductName').value,
        description: document.getElementById('shopProductDescription').value,
        description_type: document.getElementById('shopProductDescriptionType').value,
        type: document.getElementById('shopProductType').value,
        points_price: parseInt(document.getElementById('shopProductPointsPrice').value),
        value: document.getElementById('shopProductValue').value || null,
        total_stock: document.getElementById('shopProductTotalStock').value || null,
        max_per_user: document.getElementById('shopProductMaxPerUser').value || null,
        sort_order: parseInt(document.getElementById('shopProductSortOrder').value) || 0,
        status: document.getElementById('shopProductStatus').checked ? 1 : 0
    };
    
    const url = id ? 'api/admin/update_shop_product.php' : 'api/admin/add_shop_product.php';
    if (id) {
        data.id = parseInt(id);
    }
    
    const formData = new FormData();
    Object.keys(data).forEach(key => {
        if (data[key] !== null) {
            formData.append(key, data[key]);
        }
    });
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert(result.message);
                closeShopProductModal();
                loadShopProducts(currentShopProductPage);
            } else {
                alert('保存失败：' + result.message);
            }
        })
        .catch(err => {
            console.error('保存商品错误：', err);
            alert('保存失败，请稍后重试');
        });
}

// 编辑商品
function editShopProduct(id) {
    showShopProductModal(id);
}

// 删除商品
function deleteShopProduct(id) {
    if (!confirm('确定要删除这个商品吗？')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('api/admin/delete_shop_product.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(result => {
            alert(result.message);
            loadShopProducts(currentShopProductPage);
        })
        .catch(err => {
            console.error('删除商品错误：', err);
            alert('删除失败，请稍后重试');
        });
}

// 上架/下架商品
function toggleShopProductStatus(id, status) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', status);
    
    fetch('api/admin/toggle_shop_product_status.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(result => {
            alert(result.message);
            loadShopProducts(currentShopProductPage);
        })
        .catch(err => {
            console.error('更新商品状态错误：', err);
            alert('操作失败，请稍后重试');
        });
}

// 关闭商品模态框
function closeShopProductModal() {
    document.getElementById('shopProductModal').style.display = 'none';
    currentEditProductId = null;
}

// 页面加载时加载统计数据
loadStatistics();
