# 📸 拍摄上传系统

> 基于 PHP 7.2+ 和 MySQL 5.6+ 的移动端拍摄上传系统，支持照片和视频自动拍摄上传、拍摄链接管理、积分奖励、VIP会员等功能。

---

<div align="center">

[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

</div>

---

## 📚 文档导航

> 📖 详细的模块文档和安装教程请参考 `docs/` 目录

### 🚀 快速开始

- 📘 [安装教程](docs/安装教程.md) - 完整的安装部署指南

### 📖 功能文档

- 📘 [用户系统](docs/用户系统.md) - 用户注册、登录、资料管理等功能
- 🔗 [拍摄链接管理](docs/拍摄链接管理.md) - 拍摄链接的生成、管理、使用
- 🖼️ [照片视频管理](docs/照片视频管理.md) - 照片/视频的上传、管理、EXIF数据
- 🎁 [积分系统](docs/积分系统.md) - 积分获取、消费、排行榜等
- 👑 [VIP会员系统](docs/VIP会员系统.md) - VIP特权、会员管理
- 🛒 [积分商城](docs/积分商城.md) - 商品管理、兑换流程
- 📢 [系统公告](docs/系统公告.md) - 公告发布、阅读状态跟踪

### 🔧 管理文档

- 🔧 [管理员后台](docs/管理员后台.md) - 后台管理功能详解
- 🔐 [创建管理员账号](docs/创建管理员账号.md) - 管理员账号创建脚本使用说明
- 🔒 [安全功能](docs/安全功能.md) - 安全特性说明
- 📋 [公告商城信息](docs/公告商城信息.md) - VIP和商城相关信息

---

## ✨ 系统特性

### 🎯 核心功能

#### 1. 📷 自动拍摄上传
- 移动端自动调用摄像头拍摄照片/视频
- 无需用户操作，自动上传
- 支持照片和视频两种模式
- 3D 交互式拍摄界面（基于 Three.js）

#### 2. 🔗 拍摄链接管理
- 生成唯一拍摄链接码（8位）
- 照片拍摄链接：`/invite.php?code=拍摄链接码`
- 视频拍摄链接：`/record.php?code=拍摄链接码`
- 拍摄链接有效期设置（VIP用户可设置永久有效）
- 拍摄链接标签管理
- 拍摄链接启用/禁用（VIP功能）

#### 3. 👤 用户注册系统
- 用户注册码（6位），用于邀请新用户注册
- 注册码在用户个人资料页面显示
- 支持通过注册码注册获得积分奖励
- 邮箱验证功能（可选，支持强制邮箱验证模式）
- 密码重置功能（通过邮箱验证）
- 密码强度验证
- 邮箱通知设置（新照片上传通知）
- 用户昵称设置
- 登录日志记录

#### 4. 🎁 积分奖励系统
- 注册奖励积分
- 通过注册码注册奖励（新用户和邀请人）
- 通过拍摄链接上传奖励
- 每日签到奖励（连续签到有额外奖励）
- 积分明细查询
- 积分排行榜（总积分、月度积分、邀请人数、上传照片数）

#### 5. 👑 VIP会员系统
- VIP会员标识
- VIP到期时间管理（支持永久VIP）
- **VIP特权**：
  - 无限制生成拍摄链接
  - 拍摄链接可设置永久有效
  - 拍摄链接启用/禁用功能
  - 签到额外积分奖励（基础+3分）
  - 连续签到额外奖励加成（3/7/15/30天：+8/15/30/80分）
  - 批量下载照片（最多100张）

#### 6. 🛒 积分商城
- 商品管理（管理员）
- 商品类型：临时VIP、永久VIP、拍摄链接数量
- 商品兑换
- 商品状态管理（上架/下架）

#### 7. 🖼️ 照片/视频管理
- 照片和视频列表展示
- 照片EXIF数据解析（GPS位置、相机参数、拍摄参数等）
- 照片标签管理
- 照片搜索（按标签、拍摄链接码）
- 照片预览和下载
- 批量删除照片
- 批量下载照片（VIP用户支持最多100张）
- 照片软删除
- 视频时长记录

#### 8. 📢 系统公告
- 公告发布（管理员）
- 公告分类（重要、普通、通知）
- 公告阅读状态跟踪
- 未读公告提醒

#### 9. 🔧 管理员后台
- 用户管理（查看、封禁/解封、设置VIP、调整积分、查看用户详情）
- 照片/视频管理（查看、搜索、删除，支持EXIF数据查看）
- 数据统计（用户数、照片数、积分、注册趋势、上传趋势、浏览器统计等）
- 系统配置管理（积分配置、拍摄链接配置、邮件配置等）
- 公告管理（发布、编辑、删除公告，查看阅读状态）
- 积分商城管理（商品管理、上架/下架）
- 异常行为日志（检测和记录异常行为）
- 系统错误日志
- 用户登录日志
- 管理员操作日志
- 缓存管理

---

## 🛠️ 技术栈

| 类型 | 技术 |
|:---:|:---|
| **后端** | PHP 7.2+ |
| **数据库** | MySQL 5.6+ |
| **前端框架** | Three.js (3D 渲染) |
| **动画库** | GSAP (动画) |
| **音频库** | Howler.js (音频) |
| **摄像头** | WebRTC (摄像头调用) |
| **视频录制** | MediaRecorder API |

---

## 📋 系统要求

### 基础要求
- ✅ PHP 7.2 或更高版本
- ✅ MySQL 5.6 或更高版本
- ✅ Apache/Nginx Web服务器

### PHP扩展
- ✅ PDO
- ✅ PDO_MySQL
- ✅ GD库（图片处理）
- ✅ EXIF扩展（EXIF数据解析，可选）
- ✅ Session支持
- ✅ JSON支持
- ✅ mbstring（多字节字符串处理）

---

## 🚀 快速开始

### 安装部署

详细的安装教程请参考：[📘 安装教程](docs/安装教程.md)

**简要步骤：**

1. **环境检测** - 访问 `check_php_env.php` 检查环境
2. **数据库配置** - 导入数据库并配置连接
3. **系统配置** - 配置站点URL（可选）
4. **目录权限** - 设置上传和缓存目录权限
5. **创建管理员** - 使用 `create_admin.php` 创建管理员账号

> 💡 完整的安装步骤、常见问题、安全建议等请查看 [安装教程](docs/安装教程.md)

---

## 📁 目录结构

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
│       ├── dashboard.js   # 用户中心脚本
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
├── docs/                   # 文档目录
├── invite.php             # 照片拍摄页面
├── record.php             # 视频拍摄页面
├── register.php           # 用户注册页面
├── login.php              # 用户登录页面
├── reset_password.php     # 重置密码页面
├── dashboard.php          # 用户中心页面
├── admin_login.php        # 管理员登录页面
├── admin.php              # 管理员后台页面
├── create_admin.php       # 创建管理员账号脚本
├── index.php              # 首页入口
├── check_php_env.php      # PHP环境检测脚本
└── README.md              # 项目说明文档
```

---

## 💡 核心概念

### 🔑 拍摄链接码 vs 注册码

| 类型 | 长度 | 用途 | 存储位置 | 示例 |
|:---:|:---:|:---|:---|:---|
| **拍摄链接码** | 8位 | 用于照片/视频上传 | `invites.invite_code` | `invite.php?code=AbCd1234` |
| **注册码** | 6位 | 用于用户注册 | `users.register_code` | `register.php?code=AbCd12` |

### 🎁 积分奖励机制

1. **注册奖励**：新用户注册获得初始积分
2. **注册码奖励**：
   - 新用户使用注册码注册：新用户获得积分
   - 注册码拥有者：获得邀请奖励积分
3. **拍摄链接奖励**：通过拍摄链接上传照片/视频的奖励
4. **签到奖励**：每日签到获得积分，连续签到有额外奖励

---

## 🔌 API接口说明

### 👤 用户相关

| 接口 | 方法 | 说明 | 参数 |
|:---|:---:|:---|:---|
| `/api/register.php` | POST | 用户注册 | `username`, `password`, `email`(可选), `invite_code`(可选) |
| `/api/login.php` | POST | 用户登录 | `username`, `password` |
| `/api/get_register_code.php` | GET | 获取用户注册码 | 需登录 |

### 🔗 拍摄链接相关

| 接口 | 方法 | 说明 | 参数 |
|:---|:---:|:---|:---|
| `/api/generate_invite.php` | POST | 生成拍摄链接 | 需登录 |
| `/api/get_invites.php` | GET | 获取拍摄链接列表 | 需登录 |
| `/api/validate_invite.php` | GET | 验证拍摄链接码 | `code` |
| `/api/update_invite.php` | POST | 更新拍摄链接 | `invite_id`, `label`, `status` |

### 📤 上传相关

| 接口 | 方法 | 说明 | 参数 |
|:---|:---:|:---|:---|
| `/api/upload.php` | POST | 照片上传 | `image`(Base64), `capture_code` |
| `/api/upload_video.php` | POST | 视频上传 | `video`(Blob), `capture_code` |

### 🖼️ 照片管理

| 接口 | 方法 | 说明 | 参数 |
|:---|:---:|:---|:---|
| `/api/get_photos.php` | GET | 获取照片列表 | `page`, `page_size`, `tag_id`(可选) |
| `/api/get_photo_detail.php` | GET | 获取照片详情 | `id` |
| `/api/delete_photo.php` | POST | 删除照片 | `photo_id` |
| `/api/batch_delete_photos.php` | POST | 批量删除照片 | `photo_ids[]` |
| `/api/batch_download_photos.php` | POST | 批量下载照片 | `photo_ids[]` (VIP) |

### 🎁 积分相关

| 接口 | 方法 | 说明 | 参数 |
|:---|:---:|:---|:---|
| `/api/get_points.php` | GET | 获取积分信息 | 需登录 |
| `/api/get_ranking.php` | GET | 获取排行榜 | `type`(total/month/invite/upload) |
| `/api/do_checkin.php` | POST | 每日签到 | 需登录 |
| `/api/get_checkin_history.php` | GET | 获取签到历史 | `page`, `page_size` |

### 🛒 其他功能

| 接口 | 方法 | 说明 | 参数 |
|:---|:---:|:---|:---|
| `/api/get_announcements.php` | GET | 获取公告列表 | `page`, `page_size` |
| `/api/exchange_shop_product.php` | POST | 兑换商品 | `product_id` |
| `/api/get_shop_products.php` | GET | 获取商品列表 | - |

> 💡 更多API接口详情请参考各模块文档

---

## 💾 数据库结构

### 核心表

| 表名 | 说明 | 关键字段 |
|:---|:---|:---|
| `users` | 用户表 | `register_code`(6位), `is_vip`, `vip_expire_time` |
| `invites` | 拍摄链接表 | `invite_code`(8位), `expire_time` |
| `photos` | 照片/视频表 | `invite_code`, `file_type`, `video_duration` |
| `points_log` | 积分变动明细表 | `invite_code`(6位或8位) |

### 其他重要表

- `points_shop` - 积分商城商品表
- `points_exchange_log` - 积分兑换记录表
- `checkins` - 签到记录表
- `announcements` - 系统公告表
- `user_announcements` - 用户公告阅读记录表
- `photo_tags` - 照片标签表
- `photo_tag_relations` - 照片标签关联表
- `login_logs` - 登录日志表
- `admin_operation_logs` - 管理员操作日志表
- `abnormal_behavior_logs` - 异常行为日志表
- `system_config` - 系统配置表
- `system_error_logs` - 系统错误日志表

> 📖 详细数据库结构请参考 `database/init_latest.sql`

---

## 🔒 安全特性

| 安全功能 | 说明 |
|:---|:---|
| 🔄 API频率限制 | RateLimiter，防止恶意请求 |
| 📤 文件上传安全检查 | 文件类型、大小验证 |
| 💉 SQL注入防护 | PDO预处理语句 |
| 🛡️ XSS防护 | 输出转义 |
| 🔐 CSRF防护 | Token验证 |
| 🔑 Session安全 | Session固定攻击防护、安全配置 |
| 👁️ 异常行为检测 | 检测和记录异常行为 |
| 🌐 IP访问限制 | 基于IP的限制 |
| 🔒 密码强度验证 | 密码复杂度要求 |
| 🔑 密码哈希 | 使用password_hash |
| ✍️ 请求签名验证 | 可选的API请求签名 |
| 📝 登录日志记录 | 记录所有登录尝试 |

---

## 📖 使用说明

### 👤 用户端

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

### 🔧 管理员端

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

---

## ❓ 常见问题

<details>
<summary><strong>1. 拍摄链接码和注册码的区别？</strong></summary>

- **拍摄链接码（8位）**：用于照片/视频上传，通过拍摄链接访问
- **注册码（6位）**：用于用户注册，在个人资料页面显示
</details>

<details>
<summary><strong>2. 如何设置VIP？</strong></summary>

管理员可以在后台"用户管理"中为用户设置VIP，可以设置临时VIP（指定天数）或永久VIP。
</details>

<details>
<summary><strong>3. 如何配置邮件发送？</strong></summary>

在管理员后台"系统设置"中配置SMTP邮件服务器信息。
</details>

<details>
<summary><strong>4. 上传失败怎么办？</strong></summary>

- 检查目录权限（`uploads/original/`, `uploads/video/`）
- 检查PHP配置（`upload_max_filesize`, `post_max_size`）
- 查看系统错误日志
</details>

<details>
<summary><strong>5. 如何安装部署系统？</strong></summary>

请参考详细的 [安装教程](docs/安装教程.md)，包含完整的安装步骤、常见问题、安全建议等。
</details>

---

## 📝 更新日志

### 最新版本

- ✅ 区分拍摄链接码（8位）和注册码（6位）
- ✅ 支持照片和视频自动拍摄上传
- ✅ 3D交互式拍摄界面
- ✅ 完整的积分奖励系统（注册、邀请、上传、签到）
- ✅ VIP会员系统（特权、连续签到加成）
- ✅ 积分商城（VIP、拍摄链接配额兑换）
- ✅ 系统公告功能（分类、阅读状态跟踪）
- ✅ 完整的管理员后台（用户管理、数据统计、系统配置）
- ✅ 照片EXIF数据解析（GPS、相机参数等）
- ✅ 邮箱验证和通知功能
- ✅ 异常行为检测和日志
- ✅ 完整的API接口体系

---

## 🆘 技术支持

如有问题，请检查：

1. **PHP环境检测**：访问 `check_php_env.php`
2. **系统错误日志**：管理员后台查看
3. **数据库连接**：检查 `config/database.php` 配置
4. **查看文档**：参考 `docs/` 目录下的详细文档

---

## 📄 许可证

本项目仅供学习和研究使用。

---

<div align="center">

**⭐ 如果这个项目对你有帮助，欢迎 Star！**

</div>
