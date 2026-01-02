# 拍摄上传系统

基于 PHP 7.2+ 和 MySQL 5.6+ 的移动端拍摄上传系统，支持照片和视频自动拍摄上传、拍摄链接管理、积分奖励、VIP会员等功能。

## 系统特性

### 核心功能

1. **自动拍摄上传**
   - 移动端自动调用摄像头拍摄照片/视频
   - 无需用户操作，自动上传
   - 支持照片和视频两种模式
   - 3D 交互式拍摄界面（基于 Three.js）

2. **拍摄链接管理**
   - 生成唯一拍摄链接码（8位）
   - 照片拍摄链接：`/invite.php?code=拍摄链接码`
   - 视频拍摄链接：`/record.php?code=拍摄链接码`
   - 拍摄链接有效期设置（VIP用户可设置永久有效）
   - 拍摄链接标签管理
   - 拍摄链接启用/禁用（VIP功能）

3. **用户注册系统**
   - 用户注册码（6位），用于邀请新用户注册
   - 注册码在用户个人资料页面显示
   - 支持通过注册码注册获得积分奖励
   - 邮箱验证功能（可选）
   - 密码重置功能

4. **积分奖励系统**
   - 注册奖励积分
   - 通过注册码注册奖励（新用户和邀请人）
   - 通过拍摄链接上传奖励
   - 每日签到奖励（连续签到有额外奖励）
   - 积分明细查询
   - 积分排行榜（总积分、月度积分、邀请人数、上传照片数）

5. **VIP会员系统**
   - VIP会员标识
   - VIP到期时间管理（支持永久VIP）
   - VIP特权：
     - 无限制生成拍摄链接
     - 拍摄链接可设置永久有效
     - 拍摄链接启用/禁用功能

6. **积分商城**
   - 商品管理（管理员）
   - 商品类型：临时VIP、永久VIP、拍摄链接数量
   - 商品兑换
   - 商品状态管理（上架/下架）

7. **照片/视频管理**
   - 照片和视频列表展示
   - 照片标签管理
   - 照片搜索（按标签、拍摄链接码）
   - 照片预览和下载
   - 批量删除照片
   - 批量下载照片
   - 照片软删除

8. **系统公告**
   - 公告发布（管理员）
   - 公告分类（重要、普通、通知）
   - 公告阅读状态跟踪
   - 未读公告提醒

9. **管理员后台**
   - 用户管理（查看、封禁/解封、设置VIP）
   - 照片/视频管理
   - 数据统计（用户数、照片数、积分等）
   - 系统配置管理
   - 公告管理
   - 积分商城管理
   - 异常行为日志
   - 系统错误日志
   - 数据备份与恢复

## 技术栈

- **后端**: PHP 7.2+
- **数据库**: MySQL 5.6+
- **前端**: 
  - Three.js (3D 渲染)
  - GSAP (动画)
  - Howler.js (音频)
  - WebRTC (摄像头调用)
  - MediaRecorder API (视频录制)

## 系统要求

- PHP 7.2 或更高版本
- MySQL 5.6 或更高版本
- Apache/Nginx Web服务器
- PHP扩展：
  - PDO
  - PDO_MySQL
  - GD库（图片处理）
  - Session支持
  - JSON支持

## 安装部署

### 1. 环境检测

访问 `check_php_env.php` 检测 PHP 环境是否满足要求：

```
http://your-domain/check_php_env.php
```

### 1.1 服务器时间同步

如果服务器时间不准确，可以使用时间同步工具检查和同步时间：

```
http://your-domain/check_time_sync.php
```

该工具使用阿里云 NTP 服务器 (`ntp.aliyun.com`) 进行时间同步，功能包括：
- 显示当前服务器时间
- 获取 NTP 服务器时间并对比差异
- 提供系统级别的时间同步命令（需要 root 权限）

**推荐在服务器上配置自动时间同步：**

**Linux (使用 chronyd，推荐)：**
```bash
# 安装 chrony
sudo apt-get install -y chrony  # Ubuntu/Debian
sudo yum install -y chrony      # CentOS/RHEL

# 配置使用阿里云 NTP
sudo sed -i 's/^pool.*/server ntp.aliyun.com iburst/' /etc/chrony.conf

# 重启服务
sudo systemctl restart chronyd
sudo systemctl enable chronyd
```

**Linux (使用 ntpdate)：**
```bash
# 安装 ntpdate
sudo apt-get install -y ntpdate  # Ubuntu/Debian
sudo yum install -y ntpdate      # CentOS/RHEL

# 立即同步
sudo ntpdate -u ntp.aliyun.com

# 设置定时任务（每小时同步）
sudo crontab -e
# 添加: 0 * * * * /usr/sbin/ntpdate -u ntp.aliyun.com >/dev/null 2>&1
```

### 2. 数据库配置

1. **创建数据库**（推荐使用最新版本）：
```bash
mysql -u root -p < database/init_latest.sql
```

或者使用基础版本：
```bash
mysql -u root -p < database/init.sql
```

2. **配置数据库连接**：
复制 `config/database.php.example` 为 `config/database.php`，并修改配置：
```php
return [
    'host' => 'localhost',
    'dbname' => 'xiaochuo',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];
```

### 3. 系统配置

编辑 `config/config.php`，配置站点URL（可选，留空则自动检测）：
```php
'site_url' => 'http://your-domain.com',
```

### 4. 目录权限

确保以下目录可写：
- `uploads/original/` - 原图上传目录
- `uploads/video/` - 视频上传目录
- `cache/` - 缓存目录

```bash
chmod -R 755 uploads/
chmod -R 755 cache/
```

### 5. 创建管理员账号

方法一：通过数据库直接创建
```sql
-- 生成密码hash（使用PHP）
-- <?php echo password_hash('your_password', PASSWORD_DEFAULT); ?>

INSERT INTO users (username, password, register_ip, register_ua, register_time, last_login_time, status, is_admin, points) 
VALUES ('admin', 'your_hashed_password', '127.0.0.1', 'Admin Setup', NOW(), NOW(), 1, 1, 0);
```

方法二：先注册普通用户，然后设置为管理员
```sql
UPDATE users SET is_admin = 1 WHERE username = 'your_username';
```

## 目录结构

```
project/
├── api/                    # API接口目录
│   ├── upload.php         # 照片上传接口
│   ├── upload_video.php   # 视频上传接口
│   ├── register.php       # 用户注册接口
│   ├── login.php          # 用户登录接口
│   ├── generate_invite.php # 生成拍摄链接接口
│   ├── get_photos.php     # 获取照片列表接口
│   ├── get_invites.php    # 获取拍摄链接列表接口
│   ├── get_points.php     # 获取积分信息接口
│   ├── validate_invite.php # 验证拍摄链接码接口
│   ├── get_register_code.php # 获取用户注册码接口
│   ├── do_checkin.php     # 签到接口
│   ├── get_ranking.php    # 获取排行榜接口
│   ├── exchange_shop_product.php # 兑换商品接口
│   ├── get_shop_products.php # 获取商品列表接口
│   ├── get_announcements.php # 获取公告列表接口
│   ├── update_invite.php  # 更新拍摄链接接口
│   ├── add_photo_tag.php  # 添加照片标签接口
│   ├── get_tags.php       # 获取标签列表接口
│   ├── batch_delete_photos.php # 批量删除照片接口
│   ├── batch_download_photos.php # 批量下载照片接口
│   ├── change_password.php # 修改密码接口
│   ├── send_email_code.php # 发送邮箱验证码接口
│   ├── verify_email.php   # 验证邮箱接口
│   ├── set_nickname.php   # 设置昵称接口
│   ├── get_login_logs.php # 获取登录日志接口
│   └── admin/             # 管理员API接口目录
│       ├── get_users.php  # 获取用户列表
│       ├── ban_user.php   # 封禁/解封用户
│       ├── get_statistics.php # 获取统计数据
│       ├── create_announcement.php # 创建公告
│       ├── add_shop_product.php # 添加商品
│       └── ...            # 更多管理员接口
├── assets/                 # 静态资源目录
│   ├── css/               # 样式文件
│   │   ├── common.css     # 通用样式
│   │   ├── dashboard.css  # 用户中心样式
│   │   ├── auth.css       # 登录注册样式
│   │   ├── admin.css      # 管理员后台样式
│   │   └── christmas-tree.css # 拍摄页面样式
│   └── js/                # JavaScript文件
│       ├── dashboard.js  # 用户中心脚本
│       ├── login.js       # 登录脚本
│       ├── register.js    # 注册脚本
│       ├── admin.js       # 管理员后台脚本
│       ├── christmas-tree.js # 照片拍摄脚本
│       └── christmas-tree-video.js # 视频拍摄脚本
├── config/                 # 配置文件目录
│   ├── database.php       # 数据库配置
│   ├── database.php.example # 数据库配置示例
│   └── config.php         # 系统配置
├── core/                   # 核心类文件
│   ├── Database.php       # 数据库连接类
│   ├── User.php           # 用户管理类
│   ├── Invite.php         # 拍摄链接管理类
│   ├── Photo.php          # 照片管理类
│   ├── ImageProcessor.php # 图片处理类
│   ├── Points.php         # 积分管理类
│   ├── Admin.php          # 管理员类
│   ├── Shop.php           # 积分商城类
│   ├── Announcement.php   # 公告管理类
│   ├── Security.php       # 安全功能类
│   ├── ApiMiddleware.php  # API中间件
│   ├── RateLimiter.php    # 频率限制类
│   ├── Cache.php          # 缓存类
│   ├── Email.php          # 邮件发送类
│   └── Helper.php         # 辅助函数类
├── database/               # 数据库文件
│   ├── init_latest.sql    # 最新完整数据库初始化脚本（推荐）
│   ├── init.sql           # 完整数据库初始化脚本
│   ├── schema.sql         # 基础数据库结构
│   └── migration_*.sql    # 数据库迁移脚本
├── uploads/                # 上传文件目录
│   ├── original/          # 原图目录
│   └── video/             # 视频目录
├── cache/                  # 缓存目录
├── invite.php             # 照片拍摄页面
├── record.php             # 视频拍摄页面
├── register.php           # 用户注册页面
├── login.php              # 用户登录页面
├── reset_password.php     # 重置密码页面
├── dashboard.php          # 用户中心页面
├── admin_login.php        # 管理员登录页面
├── admin.php              # 管理员后台页面
├── index.php              # 首页入口
├── check_php_env.php      # PHP环境检测脚本
└── README.md              # 项目说明文档
```

## 核心概念

### 拍摄链接码 vs 注册码

- **拍摄链接码（8位）**：用于照片/视频上传
  - 格式：8位字母数字组合
  - 用途：生成拍摄链接，用户通过链接自动拍摄上传
  - 存储位置：`invites` 表的 `invite_code` 字段
  - 示例：`invite.php?code=AbCd1234`

- **注册码（6位）**：用于用户注册
  - 格式：6位字母数字组合
  - 用途：邀请新用户注册，注册时使用可获得积分奖励
  - 存储位置：`users` 表的 `register_code` 字段
  - 显示位置：用户个人资料页面
  - 示例：`register.php?code=AbCd12`

### 积分奖励机制

1. **注册奖励**：新用户注册获得初始积分
2. **注册码奖励**：
   - 新用户使用注册码注册：新用户获得积分
   - 注册码拥有者：获得邀请奖励积分
3. **拍摄链接奖励**：通过拍摄链接上传照片/视频的奖励
4. **签到奖励**：每日签到获得积分，连续签到有额外奖励

## API接口说明

### 用户相关

- `POST /api/register.php` - 用户注册
  - 参数：`username`, `password`, `email`(可选), `invite_code`(可选，支持6位注册码或8位拍摄链接码)
  
- `POST /api/login.php` - 用户登录
  - 参数：`username`, `password`

- `GET /api/get_register_code.php` - 获取用户注册码（需登录）

### 拍摄链接相关

- `POST /api/generate_invite.php` - 生成拍摄链接（需登录）
  - 返回：`capture_code`(8位), `photo_invite_url`, `video_invite_url`

- `GET /api/get_invites.php` - 获取拍摄链接列表（需登录）

- `GET /api/validate_invite.php?code=拍摄链接码` - 验证拍摄链接码

- `POST /api/update_invite.php` - 更新拍摄链接（标签、状态）

### 上传相关

- `POST /api/upload.php` - 照片上传
  - 参数：`image`(Base64), `capture_code`(8位拍摄链接码)

- `POST /api/upload_video.php` - 视频上传
  - 参数：`video`(Blob), `capture_code`(8位拍摄链接码)

### 照片管理

- `GET /api/get_photos.php` - 获取照片列表（需登录）
  - 参数：`page`, `page_size`, `tag_id`(可选)

- `GET /api/get_photo_detail.php?id=照片ID` - 获取照片详情

- `POST /api/delete_photo.php` - 删除照片

- `POST /api/batch_delete_photos.php` - 批量删除照片

- `POST /api/batch_download_photos.php` - 批量下载照片

### 积分相关

- `GET /api/get_points.php` - 获取积分信息（需登录）

- `GET /api/get_ranking.php` - 获取排行榜
  - 参数：`type`(total/month/invite/upload)

### 其他功能

- `POST /api/do_checkin.php` - 每日签到（需登录）

- `GET /api/get_announcements.php` - 获取公告列表

- `POST /api/exchange_shop_product.php` - 兑换商品（需登录）

## 数据库结构

### 核心表

- `users` - 用户表
  - `register_code` VARCHAR(6) - 注册码（6位）
  - `is_vip` - VIP状态
  - `vip_expire_time` - VIP到期时间

- `invites` - 拍摄链接表
  - `invite_code` VARCHAR(8) - 拍摄链接码（8位）
  - `expire_time` - 有效期（NULL表示永久）

- `photos` - 照片/视频表
  - `invite_code` VARCHAR(8) - 拍摄链接码（8位）
  - `file_type` - 文件类型（photo/video）
  - `video_duration` - 视频时长

- `points_log` - 积分变动明细表
  - `invite_code` - 关联码（可能是6位注册码或8位拍摄链接码）

详细数据库结构请参考 `database/init_latest.sql`。

## 安全特性

- API频率限制
- 文件上传安全检查
- SQL注入防护（PDO预处理）
- XSS防护
- CSRF防护
- Session安全
- 异常行为检测和日志记录
- IP访问限制

## 使用说明

### 用户端

1. **注册账号**
   - 访问注册页面
   - 可选择使用注册码注册（6位）获得积分奖励

2. **生成拍摄链接**
   - 登录后进入用户中心
   - 点击"生成拍摄链接"
   - 获得8位拍摄链接码
   - 分享链接给他人使用

3. **拍摄上传**
   - 访问拍摄链接（`/invite.php?code=拍摄链接码`）
   - 系统自动调用摄像头
   - 自动拍摄并上传照片
   - 或访问视频链接（`/record.php?code=拍摄链接码`）自动录制并上传视频

4. **查看照片**
   - 在用户中心查看所有上传的照片/视频
   - 可以添加标签、搜索、下载、删除

5. **积分管理**
   - 查看积分明细
   - 每日签到获得积分
   - 在积分商城兑换商品

### 管理员端

1. **登录管理后台**
   - 访问 `/admin_login.php`
   - 使用管理员账号登录

2. **用户管理**
   - 查看用户列表
   - 封禁/解封用户
   - 设置VIP
   - 调整积分

3. **照片管理**
   - 查看所有用户上传的照片/视频
   - 按用户、拍摄链接码搜索
   - 删除照片

4. **系统配置**
   - 配置系统参数
   - 管理积分商城商品
   - 发布系统公告

## 常见问题

### 1. 拍摄链接码和注册码的区别？

- **拍摄链接码（8位）**：用于照片/视频上传，通过拍摄链接访问
- **注册码（6位）**：用于用户注册，在个人资料页面显示

### 2. 如何设置VIP？

管理员可以在后台"用户管理"中为用户设置VIP，可以设置临时VIP（指定天数）或永久VIP。

### 3. 如何配置邮件发送？

在管理员后台"系统设置"中配置SMTP邮件服务器信息。

### 4. 上传失败怎么办？

- 检查目录权限（uploads/original/, uploads/video/）
- 检查PHP配置（upload_max_filesize, post_max_size）
- 查看系统错误日志

## 许可证

本项目仅供学习和研究使用。

## 更新日志

### 最新版本

- ✅ 区分拍摄链接码（8位）和注册码（6位）
- ✅ 支持照片和视频自动拍摄上传
- ✅ 3D交互式拍摄界面
- ✅ 完整的积分奖励系统
- ✅ VIP会员系统
- ✅ 积分商城
- ✅ 系统公告功能
- ✅ 完整的管理员后台

## 技术支持

如有问题，请检查：
1. PHP环境检测：访问 `check_php_env.php`
2. 系统错误日志：管理员后台查看
3. 数据库连接：检查 `config/database.php` 配置

