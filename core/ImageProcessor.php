<?php
/**
 * 图片处理类
 */
class ImageProcessor {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }
    
    /**
     * 处理图片（二进制数据）
     */
    public function processImageBinary($imageData, $inviteCode) {
        try {
            // 验证图片数据
            $imageData = $this->validateImageData($imageData);
            if (!$imageData) {
                return ['success' => false, 'message' => '图片格式错误'];
            }
            
            // 解析EXIF数据
            $exifData = [];
            try {
                $exifParser = new ExifParser();
                $exifData = $exifParser->parseExif(null, $imageData);
                
                // 如果有经纬度，尝试获取地理位置
                if (isset($exifData['latitude']) && isset($exifData['longitude'])) {
                    $address = $exifParser->getLocationAddress($exifData['latitude'], $exifData['longitude']);
                    if ($address) {
                        $exifData['location_address'] = $address;
                    }
                }
            } catch (Exception $e) {
                // EXIF解析失败不影响上传流程
                Logger::error('EXIF解析失败：' . $e->getMessage());
            }
            
            // 保存原图
            $originalPath = $this->saveOriginalImage($imageData, $inviteCode);
            
            return [
                'success' => true,
                'message' => '上传成功',
                'original_path' => $originalPath,
                'exif_data' => $exifData
            ];
            
        } catch (Exception $e) {
            Logger::error('图片处理错误：' . $e->getMessage());
            Logger::error('图片处理错误堆栈：' . $e->getTraceAsString());
            return ['success' => false, 'message' => '图片处理失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 处理图片（Base64，向后兼容）
     */
    public function processImage($base64Image, $inviteCode) {
        try {
            // 解码Base64图片
            $imageData = $this->decodeBase64Image($base64Image);
            if (!$imageData) {
                return ['success' => false, 'message' => '图片格式错误'];
            }
            
            // 保存原图
            $originalPath = $this->saveOriginalImage($imageData, $inviteCode);
            
            return [
                'success' => true,
                'message' => '上传成功',
                'original_path' => $originalPath
            ];
            
        } catch (Exception $e) {
            Logger::error('图片处理错误：' . $e->getMessage());
            Logger::error('图片处理错误堆栈：' . $e->getTraceAsString());
            return ['success' => false, 'message' => '图片处理失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 验证图片数据（二进制）- 优化版本，减少重复操作
     */
    private function validateImageData($imageData) {
        if (empty($imageData)) {
            throw new Exception('图片数据为空');
        }
        
        // 验证数据大小（快速检查）
        $dataSize = strlen($imageData);
        if ($dataSize > $this->config['upload']['max_size']) {
            throw new Exception('图片文件过大，超过' . ($this->config['upload']['max_size'] / 1024 / 1024) . 'MB限制');
        }
        
        // 快速文件头验证（不扫描整个文件）
        $fileHeader = substr($imageData, 0, 12);
        $isJpeg = (substr($fileHeader, 0, 3) === "\xFF\xD8\xFF");
        $isPng = (substr($fileHeader, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A");
        
        if (!$isJpeg && !$isPng) {
            throw new Exception('不支持的图片格式，仅支持JPEG和PNG');
        }
        
        // 只调用一次getimagesizefromstring，同时获取类型和尺寸信息
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            throw new Exception('无效的图片文件');
        }
        
        // 验证图片类型（使用getimagesize的结果，更准确）
        $imageType = $imageInfo[2];
        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            throw new Exception('不支持的图片格式，仅支持JPEG和PNG');
        }
        
        // 验证图片尺寸（防止超大图片攻击）
        $maxWidth = $this->config['image']['max_width'] ?? 1920;
        $maxHeight = $this->config['image']['max_height'] ?? 1920;
        
        if ($imageInfo[0] > $maxWidth * 2 || $imageInfo[1] > $maxHeight * 2) {
            throw new Exception('图片尺寸过大，最大支持 ' . $maxWidth . 'x' . $maxHeight . ' 像素');
        }
        
        // 轻量级内容安全检查（只检查文件头，不扫描整个文件）
        $config = require __DIR__ . '/../config/config.php';
        if ($config['upload_security']['content_scan'] ?? true) {
            // 只检查文件前1KB，不扫描整个文件（大幅提升性能）
            $scanData = substr($imageData, 0, 1024);
            $dangerousPatterns = [
                '/<\?php/i',
                '/<script/i',
                '/eval\s*\(/i',
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $scanData)) {
                    Logger::error('检测到可疑文件内容：' . $pattern);
                    throw new Exception('文件内容安全检查失败');
                }
            }
        }
        
        return $imageData;
    }
    
    /**
     * 解码Base64图片
     */
    private function decodeBase64Image($base64String) {
        // 移除data:image/jpeg;base64,前缀
        if (preg_match('/data:image\/(\w+);base64,/', $base64String, $matches)) {
            $base64String = preg_replace('/data:image\/\w+;base64,/', '', $base64String);
        }
        
        // 验证Base64字符串大小（Base64编码后的数据约为原数据的1.33倍）
        $base64Length = strlen($base64String);
        $maxBase64Size = $this->config['upload']['max_size'] * 4 / 3; // 转换为Base64大小限制
        if ($base64Length > $maxBase64Size) {
            throw new Exception('图片文件过大，超过' . ($this->config['upload']['max_size'] / 1024 / 1024) . 'MB限制');
        }
        
        $imageData = base64_decode($base64String, true);
        if ($imageData === false) {
            throw new Exception('Base64解码失败，图片格式错误');
        }
        
        // 验证解码后的数据大小
        if (strlen($imageData) > $this->config['upload']['max_size']) {
            throw new Exception('图片文件过大，超过' . ($this->config['upload']['max_size'] / 1024 / 1024) . 'MB限制');
        }
        
        // 严格文件类型验证（通过Security类）
        $security = new Security();
        $fileTypeCheck = $security->verifyFileType($imageData, [IMAGETYPE_JPEG, IMAGETYPE_PNG]);
        
        if (!$fileTypeCheck['valid']) {
            throw new Exception('不支持的图片格式，仅支持JPEG和PNG');
        }
        
        // 内容安全检查
        $config = require __DIR__ . '/../config/config.php';
        if ($config['upload_security']['content_scan'] ?? true) {
            $malwareCheck = $security->detectMaliciousContent($imageData);
            if (!$malwareCheck['safe']) {
                Logger::error('检测到可疑文件内容：' . implode(', ', $malwareCheck['threats']));
                throw new Exception('文件内容安全检查失败：' . implode(', ', $malwareCheck['threats']));
            }
        }
        
        // 验证图片尺寸（防止超大图片攻击）
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            throw new Exception('无效的图片文件');
        }
        
        $maxWidth = $this->config['image']['max_width'] ?? 1920;
        $maxHeight = $this->config['image']['max_height'] ?? 1920;
        
        if ($imageInfo[0] > $maxWidth * 2 || $imageInfo[1] > $maxHeight * 2) {
            throw new Exception('图片尺寸过大，最大支持 ' . $maxWidth . 'x' . $maxHeight . ' 像素');
        }
        
        return $imageData;
    }
    
    /**
     * 保存原图（优化：使用更快的文件写入方式）
     */
    private function saveOriginalImage($imageData, $inviteCode) {
        $dir = $this->config['upload']['original_path'];
        $this->ensureDirectory($dir);
        
        $filename = date('YmdHis') . '_' . substr(md5($inviteCode . time()), 0, 8) . '.jpg';
        $filepath = $dir . $filename;
        
        // 使用二进制模式写入，提升性能
        file_put_contents($filepath, $imageData, LOCK_EX);
        
        return 'uploads/original/' . $filename;
    }
    
    /**
     * 确保目录存在（优化：使用@抑制错误，避免重复检查）
     */
    private function ensureDirectory($dir) {
        // 使用@抑制错误，如果目录已存在则静默失败
        @mkdir($dir, 0755, true);
    }
    
    /**
     * 压缩图片（调整尺寸）
     */
    private function resizeImage($image, $maxWidth, $maxHeight) {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // 如果图片尺寸小于限制，直接返回
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $image;
        }
        
        // 计算新尺寸（保持宽高比）
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // 创建新图片
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // 保持透明度（PNG/GIF）
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        imagealphablending($newImage, true);
        
        // 缩放图片
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // 释放原图资源
        imagedestroy($image);
        
        return $newImage;
    }
    
    /**
     * 处理录像（二进制数据）
     */
    public function processVideoBinary($videoData, $inviteCode) {
        try {
            // 验证录像数据
            $videoData = $this->validateVideoData($videoData);
            if (!$videoData) {
                return ['success' => false, 'message' => '录像格式错误'];
            }
            
            // 保存录像文件
            $originalPath = $this->saveVideo($videoData, $inviteCode);
            
            // 获取录像时长（秒）
            $duration = $this->getVideoDuration($originalPath);
            
            return [
                'success' => true,
                'message' => '上传成功',
                'original_path' => $originalPath,
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            Logger::error('录像处理错误：' . $e->getMessage());
            Logger::error('录像处理错误堆栈：' . $e->getTraceAsString());
            return ['success' => false, 'message' => '录像处理失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 处理录像（Base64，向后兼容）
     */
    public function processVideo($base64Video, $inviteCode) {
        try {
            // 解码Base64录像
            $videoData = $this->decodeBase64Video($base64Video);
            if (!$videoData) {
                return ['success' => false, 'message' => '录像格式错误'];
            }
            
            // 保存录像文件
            $originalPath = $this->saveVideo($videoData, $inviteCode);
            
            // 获取录像时长（秒）
            $duration = $this->getVideoDuration($originalPath);
            
            return [
                'success' => true,
                'message' => '上传成功',
                'original_path' => $originalPath,
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            Logger::error('录像处理错误：' . $e->getMessage());
            Logger::error('录像处理错误堆栈：' . $e->getTraceAsString());
            return ['success' => false, 'message' => '录像处理失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 验证录像数据（二进制）
     */
    private function validateVideoData($videoData) {
        if (empty($videoData)) {
            throw new Exception('录像数据为空');
        }
        
        // 验证数据大小
        if (strlen($videoData) > $this->config['upload']['video_max_size']) {
            throw new Exception('录像文件过大，超过' . ($this->config['upload']['video_max_size'] / 1024 / 1024) . 'MB限制');
        }
        
        // 基本文件头验证（WebM文件以0x1A 0x45 0xDF 0xA3开头，MP4以ftyp开头）
        $header = substr($videoData, 0, 12);
        $isWebM = (substr($header, 0, 4) === "\x1a\x45\xdf\xa3");
        $isMP4 = (strpos($header, 'ftyp') !== false);
        
        if (!$isWebM && !$isMP4) {
            throw new Exception('不支持的录像格式，仅支持WebM和MP4');
        }
        
        return $videoData;
    }
    
    /**
     * 解码Base64录像
     */
    private function decodeBase64Video($base64String) {
        // 移除可能的data:video/webm;base64,前缀
        if (preg_match('/data:video\/(\w+);base64,/', $base64String, $matches)) {
            $base64String = preg_replace('/data:video\/\w+;base64,/', '', $base64String);
        }
        
        // 移除可能的URL编码（FormData可能会自动处理，但为了安全还是处理一下）
        // Base64字符串可能包含空格或换行符，需要清理
        $base64String = trim($base64String);
        
        // 移除所有空白字符（空格、换行、制表符等）
        $base64String = preg_replace('/\s+/', '', $base64String);
        
        // 记录原始字符串的前100个字符用于调试
        $originalPreview = substr($base64String, 0, 100);
        Logger::error('Base64字符串预览（前100字符）: ' . $originalPreview);
        
        // 查找非法字符
        $invalidChars = preg_replace('/[A-Za-z0-9+\/]/', '', $base64String);
        $invalidChars = str_replace('=', '', $invalidChars); // 移除等号，因为等号是合法的
        
        if (!empty($invalidChars)) {
            // 获取唯一非法字符
            $uniqueInvalidChars = array_unique(str_split($invalidChars));
            $invalidCharsStr = implode('', $uniqueInvalidChars);
            Logger::error('发现非法字符: ' . bin2hex($invalidCharsStr) . ' (hex)');
            Logger::error('非法字符ASCII码: ' . implode(',', array_map('ord', $uniqueInvalidChars)));
            
            // 尝试清理常见的非法字符（可能是编码问题）
            // 移除所有非Base64字符（除了等号）
            $cleanedString = preg_replace('/[^A-Za-z0-9+\/=]/', '', $base64String);
            
            if (strlen($cleanedString) !== strlen($base64String)) {
                Logger::error('已清理非法字符，原始长度: ' . strlen($base64String) . ', 清理后长度: ' . strlen($cleanedString));
                $base64String = $cleanedString;
            } else {
                throw new Exception('Base64格式错误，包含非法字符: ' . bin2hex($invalidCharsStr));
            }
        }
        
        // 验证Base64字符串格式（Base64只包含A-Z, a-z, 0-9, +, /, =）
        // 等号只能出现在末尾，最多2个
        if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $base64String)) {
            Logger::error('Base64格式验证失败，字符串长度: ' . strlen($base64String));
            Logger::error('Base64前200字符: ' . substr($base64String, 0, 200));
            throw new Exception('Base64格式错误，字符串格式不符合Base64规范');
        }
        
        // 验证Base64字符串大小
        $base64Length = strlen($base64String);
        $maxBase64Size = $this->config['upload']['video_max_size'] * 4 / 3;
        if ($base64Length > $maxBase64Size) {
            throw new Exception('录像文件过大，超过' . ($this->config['upload']['video_max_size'] / 1024 / 1024) . 'MB限制');
        }
        
        // Base64解码（strict模式）
        $videoData = base64_decode($base64String, true);
        if ($videoData === false || empty($videoData)) {
            // 记录更多调试信息
            Logger::error('Base64解码失败，字符串长度: ' . strlen($base64String));
            Logger::error('Base64前100字符: ' . substr($base64String, 0, 100));
            throw new Exception('Base64解码失败，录像格式错误。请检查录像数据是否完整');
        }
        
        // 验证解码后的数据大小
        if (strlen($videoData) > $this->config['upload']['video_max_size']) {
            throw new Exception('录像文件过大，超过' . ($this->config['upload']['video_max_size'] / 1024 / 1024) . 'MB限制');
        }
        
        // 基本文件头验证（WebM文件以0x1A 0x45 0xDF 0xA3开头，MP4以ftyp开头）
        $header = substr($videoData, 0, 12);
        $isWebM = (substr($header, 0, 4) === "\x1a\x45\xdf\xa3");
        $isMP4 = (strpos($header, 'ftyp') !== false);
        
        if (!$isWebM && !$isMP4) {
            throw new Exception('不支持的录像格式，仅支持WebM和MP4');
        }
        
        return $videoData;
    }
    
    /**
     * 保存录像文件
     */
    private function saveVideo($videoData, $inviteCode) {
        $dir = $this->config['upload']['video_path'];
        $this->ensureDirectory($dir);
        
        // 检测文件类型
        $header = substr($videoData, 0, 12);
        $extension = 'webm';
        if (strpos($header, 'ftyp') !== false) {
            $extension = 'mp4';
        }
        
        $filename = date('YmdHis') . '_' . substr(md5($inviteCode . time()), 0, 8) . '.' . $extension;
        $filepath = $dir . $filename;
        
        file_put_contents($filepath, $videoData);
        
        return 'uploads/video/' . $filename;
    }
    
    /**
     * 获取录像时长（秒）
     */
    private function getVideoDuration($videoPath) {
        $fullPath = __DIR__ . '/../' . $videoPath;
        
        if (!file_exists($fullPath)) {
            return 0;
        }
        
        // 尝试使用getid3库（如果可用）
        // 如果没有，返回0（前端已经限制了时长）
        // 这里简化处理，返回0，实际时长由前端控制
        return 0;
    }
    
    /**
     * 压缩图片文件（减小文件大小）
     */
    public function compressImage($imagePath, $quality = 85) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = @getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // 根据MIME类型创建图片资源
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = imagecreatefromwebp($imagePath);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // 如果图片尺寸过大，先压缩尺寸
        $maxWidth = $this->config['image']['max_width'] ?? 1920;
        $maxHeight = $this->config['image']['max_height'] ?? 1920;
        if ($width > $maxWidth || $height > $maxHeight) {
            $image = $this->resizeImage($image, $maxWidth, $maxHeight);
        }
        
        // 保存压缩后的图片
        $useWebP = $this->config['image']['use_webp'] ?? false;
        $webpQuality = $this->config['image']['webp_quality'] ?? 85;
        
        if ($useWebP && function_exists('imagewebp')) {
            // 尝试保存为WebP
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $imagePath);
            if (imagewebp($image, $webpPath, $webpQuality)) {
                imagedestroy($image);
                return $webpPath;
            }
        }
        
        // 保存为JPEG（压缩）
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/png') {
            $jpegPath = preg_replace('/\.png$/i', '.jpg', $imagePath);
            imagejpeg($image, $jpegPath, $quality);
            imagedestroy($image);
            return $jpegPath;
        }
        
        imagedestroy($image);
        return false;
    }
}
