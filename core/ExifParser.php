<?php
/**
 * EXIF数据解析类
 * 用于解析照片的EXIF数据，包括经纬度、相机参数、地理位置等
 */
class ExifParser {
    
    /**
     * 解析照片的EXIF数据
     * @param string $imagePath 图片文件路径
     * @param string $imageData 图片二进制数据（可选，如果提供则优先使用）
     * @return array EXIF数据数组
     */
    public function parseExif($imagePath = null, $imageData = null) {
        $exifData = [];
        
        // 优先使用二进制数据解析
        if ($imageData !== null) {
            $exifData = $this->parseExifFromData($imageData);
        } elseif ($imagePath !== null && file_exists($imagePath)) {
            $exifData = $this->parseExifFromFile($imagePath);
        }
        
        return $exifData;
    }
    
    /**
     * 从文件解析EXIF数据
     */
    private function parseExifFromFile($imagePath) {
        // 检查是否支持EXIF扩展
        if (!function_exists('exif_read_data')) {
            Logger::warning('EXIF扩展未启用，无法解析EXIF数据');
            return [];
        }
        
        // 只支持JPEG格式
        $imageInfo = @getimagesize($imagePath);
        if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_JPEG) {
            return [];
        }
        
        // 读取EXIF数据
        $exif = @exif_read_data($imagePath, 'EXIF', true);
        if (!$exif) {
            return [];
        }
        
        return $this->extractExifData($exif);
    }
    
    /**
     * 从二进制数据解析EXIF数据
     */
    private function parseExifFromData($imageData) {
        // 检查是否支持EXIF扩展
        if (!function_exists('exif_read_data')) {
            Logger::warning('EXIF扩展未启用，无法解析EXIF数据');
            return [];
        }
        
        // 只支持JPEG格式
        $imageInfo = @getimagesizefromstring($imageData);
        if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_JPEG) {
            return [];
        }
        
        // 将二进制数据保存到临时文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'exif_');
        if ($tmpFile === false) {
            return [];
        }
        
        try {
            file_put_contents($tmpFile, $imageData);
            $exif = @exif_read_data($tmpFile, 'EXIF', true);
            
            if ($exif) {
                $result = $this->extractExifData($exif);
            } else {
                $result = [];
            }
        } catch (Exception $e) {
            Logger::error('解析EXIF数据失败：' . $e->getMessage());
            $result = [];
        } finally {
            // 删除临时文件
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
        
        return $result;
    }
    
    /**
     * 提取EXIF数据中的关键信息
     */
    private function extractExifData($exif) {
        $data = [];
        
        // 解析GPS信息（经纬度）
        if (isset($exif['GPS'])) {
            $gps = $this->parseGpsData($exif['GPS']);
            if ($gps) {
                $data['latitude'] = $gps['latitude'];
                $data['longitude'] = $gps['longitude'];
                if (isset($gps['altitude'])) {
                    $data['altitude'] = $gps['altitude'];
                }
            }
        }
        
        // 解析相机信息
        if (isset($exif['IFD0'])) {
            $ifd0 = $exif['IFD0'];
            if (isset($ifd0['Make'])) {
                $data['camera_make'] = trim($ifd0['Make']);
            }
            if (isset($ifd0['Model'])) {
                $data['camera_model'] = trim($ifd0['Model']);
            }
            if (isset($ifd0['Orientation'])) {
                $data['orientation'] = (int)$ifd0['Orientation'];
            }
        }
        
        // 解析照片尺寸
        if (isset($exif['COMPUTED'])) {
            $computed = $exif['COMPUTED'];
            if (isset($computed['Width'])) {
                $data['width'] = (int)$computed['Width'];
            }
            if (isset($computed['Height'])) {
                $data['height'] = (int)$computed['Height'];
            }
        }
        
        // 解析拍摄参数
        if (isset($exif['EXIF'])) {
            $exifData = $exif['EXIF'];
            
            // 焦距
            if (isset($exifData['FocalLength'])) {
                $focalLength = $this->parseRational($exifData['FocalLength']);
                $data['focal_length'] = $focalLength ? round($focalLength, 1) . 'mm' : null;
            }
            
            // 光圈值
            if (isset($exifData['FNumber'])) {
                $aperture = $this->parseRational($exifData['FNumber']);
                $data['aperture'] = $aperture ? 'f/' . round($aperture, 1) : null;
            }
            
            // 快门速度
            if (isset($exifData['ExposureTime'])) {
                $shutterSpeed = $this->parseRational($exifData['ExposureTime']);
                if ($shutterSpeed) {
                    if ($shutterSpeed >= 1) {
                        $data['shutter_speed'] = round($shutterSpeed, 0) . 's';
                    } else {
                        $data['shutter_speed'] = '1/' . round(1 / $shutterSpeed, 0) . 's';
                    }
                }
            }
            
            // ISO感光度
            if (isset($exifData['ISOSpeedRatings'])) {
                $data['iso'] = (int)$exifData['ISOSpeedRatings'];
            }
            
            // 曝光模式
            if (isset($exifData['ExposureMode'])) {
                $exposureModes = [
                    0 => '自动',
                    1 => '手动',
                    2 => '自动包围'
                ];
                $data['exposure_mode'] = $exposureModes[$exifData['ExposureMode']] ?? '未知';
            }
            
            // 白平衡
            if (isset($exifData['WhiteBalance'])) {
                $data['white_balance'] = $exifData['WhiteBalance'] == 0 ? '自动' : '手动';
            }
            
            // 闪光灯
            if (isset($exifData['Flash'])) {
                $flash = (int)$exifData['Flash'];
                $flashModes = [
                    0 => '未使用',
                    1 => '使用',
                    5 => '使用（未检测到返回光）',
                    7 => '使用（检测到返回光）',
                    9 => '使用（强制）',
                    13 => '使用（强制，未检测到返回光）',
                    15 => '使用（强制，检测到返回光）',
                    16 => '未使用（强制关闭）',
                    24 => '自动',
                    25 => '自动（未检测到返回光）',
                    29 => '自动（检测到返回光）'
                ];
                $data['flash'] = $flashModes[$flash] ?? '未知';
            }
            
            // 镜头型号
            if (isset($exifData['UndefinedTag:0xA434'])) {
                $data['lens_model'] = trim($exifData['UndefinedTag:0xA434']);
            }
        }
        
        // 保存完整EXIF数据（JSON格式）
        $data['exif_data'] = json_encode($exif, JSON_UNESCAPED_UNICODE);
        
        return $data;
    }
    
    /**
     * 解析GPS数据
     */
    private function parseGpsData($gps) {
        if (!isset($gps['GPSLatitude']) || !isset($gps['GPSLongitude'])) {
            return null;
        }
        
        $latitude = $this->parseGpsCoordinate($gps['GPSLatitude'], $gps['GPSLatitudeRef'] ?? 'N');
        $longitude = $this->parseGpsCoordinate($gps['GPSLongitude'], $gps['GPSLongitudeRef'] ?? 'E');
        
        if ($latitude === null || $longitude === null) {
            return null;
        }
        
        $result = [
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
        
        // 解析海拔高度
        if (isset($gps['GPSAltitude'])) {
            $altitude = $this->parseRational($gps['GPSAltitude']);
            if ($altitude !== null) {
                $result['altitude'] = round($altitude, 2);
            }
        }
        
        return $result;
    }
    
    /**
     * 解析GPS坐标
     */
    private function parseGpsCoordinate($coordinate, $ref) {
        if (!is_array($coordinate) || count($coordinate) !== 3) {
            return null;
        }
        
        $degrees = $this->parseRational($coordinate[0]);
        $minutes = $this->parseRational($coordinate[1]);
        $seconds = $this->parseRational($coordinate[2]);
        
        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }
        
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        
        // 如果是南纬或西经，取负值
        if ($ref === 'S' || $ref === 'W') {
            $decimal = -$decimal;
        }
        
        return round($decimal, 8);
    }
    
    /**
     * 解析分数值（如 "123/456"）
     */
    private function parseRational($value) {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_string($value) && strpos($value, '/') !== false) {
            $parts = explode('/', $value);
            if (count($parts) === 2) {
                $numerator = (float)$parts[0];
                $denominator = (float)$parts[1];
                if ($denominator != 0) {
                    return $numerator / $denominator;
                }
            }
        }
        
        if (is_array($value) && count($value) === 2) {
            $numerator = (float)$value[0];
            $denominator = (float)$value[1];
            if ($denominator != 0) {
                return $numerator / $denominator;
            }
        }
        
        return null;
    }
    
    /**
     * 根据经纬度获取地理位置（地址）
     * 注意：此功能需要调用第三方地理编码API，如高德地图、百度地图等
     * 这里提供一个基础框架，实际使用时需要配置API密钥
     */
    public function getLocationAddress($latitude, $longitude) {
        // 这里可以集成第三方地理编码服务
        // 例如：高德地图、百度地图、Google Maps等
        // 由于需要API密钥，这里暂时返回null，可以在配置文件中配置
        
        $config = require __DIR__ . '/../config/config.php';
        
        // 如果配置了地理编码服务，可以在这里调用
        // 例如：
        // if (isset($config['geocoding']['api_key'])) {
        //     return $this->callGeocodingApi($latitude, $longitude, $config['geocoding']);
        // }
        
        return null;
    }
}

