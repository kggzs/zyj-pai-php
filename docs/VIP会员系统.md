# 🌟 VIP会员系统模块

VIP会员系统模块负责VIP会员的管理和特权功能。

## ✨ 功能概述

### 🔖 1. VIP会员标识

- 用户表中`is_vip`字段标识是否为VIP
- `vip_expire_time`字段记录VIP到期时间（NULL表示永久VIP）

### 🎁 2. VIP特权

#### 拍摄链接特权
- **无限制生成**：不受数量限制
- **永久有效**：默认生成永久有效的链接（expire_time为NULL）
- **标签管理**：可以为链接添加标签
- **启用/禁用**：可以启用或禁用链接

#### 签到特权
- **额外奖励**：签到基础积分额外+3分
- **连续签到奖励加成**：
  - 连续3天：额外+8分（普通用户+5分）
  - 连续7天：额外+15分（普通用户+10分）
  - 连续15天：额外+30分（普通用户+20分）
  - 连续30天：额外+80分（普通用户+50分）

#### 批量操作特权
- **批量下载照片**：支持一次性下载最多100张照片
- **批量删除照片**：支持批量删除照片

### 📋 3. VIP类型

- **临时VIP**：设置到期时间的VIP
- **永久VIP**：到期时间为NULL的VIP

### 👑 4. VIP管理

#### 设置VIP
- 管理员可以在后台为用户设置VIP
- 可以设置临时VIP（指定天数）或永久VIP

#### VIP过期
- 系统会自动检查VIP是否过期
- 过期后自动更新VIP状态

## 🔌 API接口

### 获取VIP信息

通过用户信息接口获取，VIP状态包含在用户信息中。

## 🗄️ 数据库结构

### users表相关字段

- `is_vip`：是否为VIP（0否，1是）
- `vip_expire_time`：VIP到期时间（NULL表示永久VIP）

## 🧰 核心类

VIP功能集成在各个模块中：
- `core/User.php`：用户管理
- `core/Invite.php`：拍摄链接管理（VIP特权）
- `core/Points.php`：积分管理（VIP签到奖励）
- `core/Photo.php`：照片管理（VIP批量操作）
- `core/Admin.php`：管理员后台（设置VIP）

## ⚙️ 配置说明

VIP相关配置在积分系统配置中：
- `points_checkin_vip_bonus`：VIP签到额外奖励
- `points_checkin_vip_consecutive_bonus`：VIP连续签到奖励

## 📚 使用示例

### 设置VIP（管理员）

```php
// 管理员在后台设置VIP
// 可以设置临时VIP（指定天数）或永久VIP
```

### VIP用户使用特权

```php
// 1. 生成拍摄链接（无限制）
// POST /api/generate_invite.php

// 2. 签到（额外奖励）
// POST /api/do_checkin.php

// 3. 批量下载照片（最多100张）
// POST /api/batch_download_photos.php

// 4. 更新拍摄链接（标签、状态）
// POST /api/update_invite.php
```

## ⚠️ 注意事项

1. **VIP过期**：系统会自动检查并更新VIP状态
2. **永久VIP**：到期时间为NULL表示永久VIP
3. **特权验证**：各功能模块会检查用户VIP状态
4. **VIP获取**：用户可以通过积分商城兑换VIP

