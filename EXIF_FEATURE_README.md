# EXIF数据解析功能说明

## 功能概述

本功能实现了照片和视频拍摄后自动解析EXIF数据，包括：
- 经纬度（GPS信息）
- 相机参数（品牌、型号、镜头等）
- 拍摄参数（焦距、光圈、快门、ISO等）
- 地理位置（地址信息）
- 照片尺寸

解析后的数据会显示在用户页面的照片详情内，管理后台也会同步显示。

## 安装步骤

### 1. 执行数据库迁移

运行以下SQL脚本添加EXIF相关字段：

```bash
mysql -u root -p xiaochuo < database/migration_add_photo_exif.sql
```

或者直接在数据库中执行 `database/migration_add_photo_exif.sql` 文件。

### 2. 确保PHP EXIF扩展已启用

检查PHP是否启用了EXIF扩展：

```bash
php -m | grep exif
```

如果没有启用，需要在 `php.ini` 中启用：

```ini
extension=exif
```

然后重启Web服务器。

### 3. 验证功能

上传一张包含EXIF数据的照片（建议使用手机拍摄的照片，通常包含GPS信息），然后：

1. 在用户中心查看照片详情，应该能看到拍摄信息
2. 在管理后台查看照片列表，应该能看到EXIF信息

## 功能说明

### 解析的EXIF数据

#### 地理位置信息
- **纬度** (latitude): 照片拍摄地点的纬度
- **经度** (longitude): 照片拍摄地点的经度
- **海拔** (altitude): 拍摄地点的海拔高度（米）
- **地址** (location_address): 地理位置对应的地址（需要配置地理编码API）

#### 相机信息
- **品牌** (camera_make): 相机品牌（如：Canon、Nikon等）
- **型号** (camera_model): 相机型号
- **镜头** (lens_model): 镜头型号

#### 拍摄参数
- **焦距** (focal_length): 拍摄时使用的焦距（如：50mm）
- **光圈** (aperture): 光圈值（如：f/2.8）
- **快门速度** (shutter_speed): 快门速度（如：1/125s）
- **ISO** (iso): ISO感光度
- **曝光模式** (exposure_mode): 曝光模式（自动/手动等）
- **白平衡** (white_balance): 白平衡设置
- **闪光灯** (flash): 闪光灯使用状态

#### 照片信息
- **宽度** (width): 照片宽度（像素）
- **高度** (height): 照片高度（像素）
- **方向** (orientation): 照片方向（1-8）

### 用户端显示

在用户中心的照片详情页面，EXIF信息会以分组形式显示：

1. **📍 地理位置**：显示经纬度、海拔、地址和地图链接
2. **📷 相机信息**：显示相机品牌、型号和镜头
3. **⚙️ 拍摄参数**：显示焦距、光圈、快门、ISO等参数
4. **📐 照片尺寸**：显示照片的宽度和高度

### 管理后台显示

在管理后台的照片列表中，EXIF信息会以紧凑格式显示在照片信息卡片中，包括：
- 地理位置（带地图链接）
- 相机信息
- 拍摄参数
- 照片尺寸

## 配置地理编码服务（可选）

如果需要获取地理位置对应的地址信息，可以配置地理编码API。目前代码中预留了接口，可以集成以下服务：

- 高德地图API
- 百度地图API
- Google Maps API
- OpenStreetMap Nominatim API

配置方法：在 `config/config.php` 中添加地理编码配置：

```php
'geocoding' => [
    'provider' => 'amap', // 或 'baidu', 'google', 'nominatim'
    'api_key' => 'your_api_key',
    'api_url' => 'https://restapi.amap.com/v3/geocode/regeo'
]
```

然后在 `core/ExifParser.php` 的 `getLocationAddress` 方法中实现具体的API调用。

## 注意事项

1. **EXIF数据依赖**：只有JPEG格式的照片才包含EXIF数据，PNG格式通常不包含
2. **GPS权限**：手机拍摄的照片需要用户授权位置权限才能包含GPS信息
3. **隐私保护**：GPS信息可能包含敏感的位置数据，请注意隐私保护
4. **性能影响**：EXIF解析会增加上传处理时间，但影响很小（通常<100ms）

## 数据库字段说明

新增的字段都允许为NULL，因为：
- 不是所有照片都包含EXIF数据
- 旧照片上传时没有EXIF数据
- 某些格式（如PNG）不支持EXIF

## 故障排查

### EXIF数据未解析

1. 检查PHP EXIF扩展是否启用：`php -m | grep exif`
2. 检查照片格式是否为JPEG（PNG不支持EXIF）
3. 检查照片是否包含EXIF数据（某些编辑软件会删除EXIF）
4. 查看PHP错误日志：`tail -f /var/log/php_errors.log`

### 地理位置未显示

1. 检查照片是否包含GPS信息（GPS标签）
2. 检查是否配置了地理编码API（地址信息需要API）
3. 查看错误日志确认是否有API调用失败

## 技术实现

- **EXIF解析**：使用PHP内置的 `exif_read_data()` 函数
- **数据存储**：存储在 `photos` 表的EXIF相关字段中
- **前端显示**：通过JavaScript动态生成HTML显示EXIF信息
- **地图链接**：使用OpenStreetMap显示地理位置

## 更新日志

- 2024-XX-XX: 初始版本，支持基本EXIF数据解析和显示

