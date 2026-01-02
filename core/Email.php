<?php
/**
 * 邮件发送类
 */
class Email {
    private $config;
    
    public function __construct() {
        require_once __DIR__ . '/Helper.php';
        // 从数据库读取邮件配置，如果不存在则从配置文件读取（兼容）
        $this->config = Helper::getConfigGroup('email');
        
        // 如果数据库没有配置，从配置文件读取（兼容旧配置）
        if (empty($this->config)) {
            $configFile = __DIR__ . '/../config/config.php';
            if (file_exists($configFile)) {
                $fileConfig = require $configFile;
                $this->config = $fileConfig['email'] ?? [];
            }
        }
    }
    
    /**
     * 发送邮件
     */
    public function send($to, $subject, $body, $isHtml = true) {
        $emailConfig = $this->config;
        
        // 如果没有配置邮件，使用简单的mail函数
        if (empty($emailConfig) || !isset($emailConfig['enabled']) || !$emailConfig['enabled']) {
            return $this->sendSimpleMail($to, $subject, $body, $isHtml);
        }
        
        // 使用SMTP发送
        return $this->sendSmtpMail($to, $subject, $body, $isHtml, $emailConfig);
    }
    
    /**
     * 使用PHP mail函数发送（简单方式）
     */
    private function sendSimpleMail($to, $subject, $body, $isHtml) {
        $emailConfig = $this->config;
        $headers = [];
        $headers[] = 'From: ' . ($emailConfig['from'] ?? 'noreply@photo-upload.com');
        $headers[] = 'Reply-To: ' . ($emailConfig['from'] ?? 'noreply@photo-upload.com');
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
        } else {
            $headers[] = 'Content-type: text/plain; charset=utf-8';
        }
        
        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$result) {
            error_log("邮件发送失败：to={$to}, subject={$subject}");
        }
        
        return $result;
    }
    
    /**
     * 使用SMTP发送邮件
     */
    private function sendSmtpMail($to, $subject, $body, $isHtml, $config) {
        try {
            $smtpHost = $config['smtp_host'] ?? 'localhost';
            $smtpPort = $config['smtp_port'] ?? 25;
            $smtpUser = $config['smtp_user'] ?? '';
            $smtpPass = $config['smtp_pass'] ?? '';
            $fromEmail = $config['from'] ?? 'noreply@photo-upload.com';
            $fromName = $config['from_name'] ?? '1724464998';
            $smtpSecure = $config['smtp_secure'] ?? 'tls';
            
            error_log("开始SMTP连接：{$smtpHost}:{$smtpPort}, 加密方式：{$smtpSecure}");
            
            // 如果是SSL，使用ssl://协议
            $protocol = ($smtpSecure === 'ssl' && $smtpPort == 465) ? 'ssl://' : '';
            $connectString = "{$protocol}{$smtpHost}:{$smtpPort}";
            
            // 构建连接地址
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $socket = @stream_socket_client(
                $connectString,
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$socket) {
                error_log("SMTP连接失败：{$errstr} ({$errno}), 连接地址：{$connectString}");
                return false;
            }
            
            error_log("SMTP连接成功");
            
            // 设置超时
            stream_set_timeout($socket, 30);
            
            // 读取欢迎消息（所有连接都需要）
            $response = $this->readSmtpResponse($socket);
            if (!$this->isSmtpSuccess($response)) {
                error_log("SMTP连接响应错误：{$response}");
                fclose($socket);
                return false;
            }
            error_log("SMTP欢迎消息：{$response}");
            
            // EHLO
            fwrite($socket, "EHLO " . $smtpHost . "\r\n");
            $response = $this->readSmtpResponse($socket);
            if (!$this->isSmtpSuccess($response)) {
                error_log("SMTP EHLO错误：{$response}");
                fclose($socket);
                return false;
            }
            error_log("SMTP EHLO成功：{$response}");
            
            // 如果需要TLS加密（587端口）
            if ($smtpSecure === 'tls' && $smtpPort == 587) {
                fwrite($socket, "STARTTLS\r\n");
                $response = $this->readSmtpResponse($socket);
                if (!$this->isSmtpSuccess($response)) {
                    error_log("SMTP STARTTLS错误：{$response}");
                    fclose($socket);
                    return false;
                }
                error_log("SMTP STARTTLS响应：{$response}");
                
                // 启用加密
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    error_log("SMTP TLS加密启用失败");
                    fclose($socket);
                    return false;
                }
                error_log("TLS加密已启用");
                
                // TLS后需要重新EHLO
                fwrite($socket, "EHLO " . $smtpHost . "\r\n");
                $response = $this->readSmtpResponse($socket);
                if (!$this->isSmtpSuccess($response)) {
                    error_log("SMTP EHLO(TLS)错误：{$response}");
                    fclose($socket);
                    return false;
                }
                error_log("SMTP EHLO(TLS)成功：{$response}");
            }
            
            // 如果需要认证
            if (!empty($smtpUser) && !empty($smtpPass)) {
                fwrite($socket, "AUTH LOGIN\r\n");
                $response = $this->readSmtpResponse($socket);
                if (!$this->isSmtpSuccess($response)) {
                    error_log("SMTP AUTH LOGIN错误：{$response}");
                    fclose($socket);
                    return false;
                }
                error_log("SMTP AUTH LOGIN响应：{$response}");
                
                fwrite($socket, base64_encode($smtpUser) . "\r\n");
                $response = $this->readSmtpResponse($socket);
                if (!$this->isSmtpSuccess($response)) {
                    error_log("SMTP 用户名认证错误：{$response}, 用户名：{$smtpUser}");
                    fclose($socket);
                    return false;
                }
                error_log("SMTP 用户名认证成功");
                
                fwrite($socket, base64_encode($smtpPass) . "\r\n");
                $response = $this->readSmtpResponse($socket);
                if (!$this->isSmtpSuccess($response)) {
                    error_log("SMTP 密码认证错误：{$response}");
                    fclose($socket);
                    return false;
                }
                error_log("SMTP 密码认证成功");
            }
            
            // MAIL FROM
            fwrite($socket, "MAIL FROM: <{$fromEmail}>\r\n");
            $response = $this->readSmtpResponse($socket);
            if (!$this->isSmtpSuccess($response)) {
                error_log("SMTP MAIL FROM错误：{$response}, 发件人：{$fromEmail}");
                fclose($socket);
                return false;
            }
            error_log("SMTP MAIL FROM成功");
            
            // RCPT TO
            fwrite($socket, "RCPT TO: <{$to}>\r\n");
            $response = $this->readSmtpResponse($socket);
            if (!$this->isSmtpSuccess($response)) {
                error_log("SMTP RCPT TO错误：{$response}, 收件人：{$to}");
                fclose($socket);
                return false;
            }
            error_log("SMTP RCPT TO成功");
            
            // DATA
            fwrite($socket, "DATA\r\n");
            $response = $this->readSmtpResponse($socket);
            if (!$this->isSmtpSuccess($response)) {
                error_log("SMTP DATA错误：{$response}");
                fclose($socket);
                return false;
            }
            error_log("SMTP DATA成功");
            
            // 邮件内容
            $message = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $message .= "To: <{$to}>\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Date: " . date('r') . "\r\n";
            if ($isHtml) {
                $message .= "Content-Type: text/html; charset=utf-8\r\n";
            } else {
                $message .= "Content-Type: text/plain; charset=utf-8\r\n";
            }
            $message .= "\r\n";
            $message .= $body;
            $message .= "\r\n.\r\n";
            
            fwrite($socket, $message);
            $response = $this->readSmtpResponse($socket);
            if (!$this->isSmtpSuccess($response)) {
                error_log("SMTP 邮件发送错误：{$response}");
                fclose($socket);
                return false;
            }
            error_log("SMTP 邮件发送成功：{$response}");
            
            // QUIT
            fwrite($socket, "QUIT\r\n");
            $this->readSmtpResponse($socket);
            fclose($socket);
            
            error_log("SMTP邮件发送完成，收件人：{$to}");
            return true;
        } catch (Exception $e) {
            error_log("SMTP发送异常：" . $e->getMessage());
            error_log("堆栈：" . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log("SMTP发送致命错误：" . $e->getMessage());
            error_log("堆栈：" . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * 读取SMTP响应
     */
    private function readSmtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // SMTP响应以空格+数字开头表示多行响应，以数字+空格开头表示单行响应
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return trim($response);
    }
    
    /**
     * 检查SMTP响应是否成功（2xx或3xx）
     */
    private function isSmtpSuccess($response) {
        if (empty($response)) {
            return false;
        }
        $code = substr($response, 0, 3);
        return $code >= 200 && $code < 400;
    }
    
    /**
     * 获取站点URL
     */
    private function getSiteUrl() {
        $siteUrl = $this->config['site_url'] ?? '';
        
        // 如果配置了非空的URL，使用配置的URL
        if (!empty($siteUrl) && $siteUrl !== 'http://localhost' && $siteUrl !== 'http://127.0.0.1') {
            return rtrim($siteUrl, '/');
        }
        
        // 否则使用Helper类获取
        return Helper::getSiteUrl();
    }
    
    /**
     * 发送邮箱验证码
     */
    public function sendVerificationCode($to, $code) {
        // 从系统配置读取邮件模板
        $subjectTemplate = Helper::getSystemConfig('email_template_verification_subject') ?: '邮箱验证码';
        $bodyTemplate = Helper::getSystemConfig('email_template_verification_body') ?: '
        <html>
        <head>
            <meta charset="utf-8">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #667eea;">邮箱验证码</h2>
                <p>您的验证码是：</p>
                <div style="background: #f0f4ff; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; margin: 20px 0; border-radius: 8px;">
                    {code}
                </div>
                <p style="color: #999; font-size: 12px;">验证码有效期为10分钟，请勿泄露给他人。</p>
            </div>
        </body>
        </html>
        ';
        
        // 替换变量
        $subject = str_replace('{code}', $code, $subjectTemplate);
        $body = str_replace('{code}', $code, $bodyTemplate);
        
        return $this->send($to, $subject, $body, true);
    }
    
    /**
     * 发送密码重置邮件
     */
    public function sendPasswordReset($to, $resetUrl) {
        // 从系统配置读取邮件模板
        $subjectTemplate = Helper::getSystemConfig('email_template_reset_subject') ?: '密码重置';
        $bodyTemplate = Helper::getSystemConfig('email_template_reset_body') ?: '
        <html>
        <head>
            <meta charset="utf-8">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #667eea;">密码重置</h2>
                <p>您申请了密码重置，请点击下面的链接重置密码：</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{resetUrl}" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">重置密码</a>
                </div>
                <p style="color: #999; font-size: 12px;">如果无法点击链接，请复制以下地址到浏览器：</p>
                <p style="color: #999; font-size: 12px; word-break: break-all;">{resetUrl}</p>
                <p style="color: #999; font-size: 12px;">链接有效期为1小时，请勿泄露给他人。</p>
            </div>
        </body>
        </html>
        ';
        
        // 替换变量
        $subject = str_replace('{resetUrl}', $resetUrl, $subjectTemplate);
        $body = str_replace('{resetUrl}', $resetUrl, $bodyTemplate);
        
        return $this->send($to, $subject, $body, true);
    }
    
    /**
     * 发送照片提醒邮件
     */
    public function sendPhotoNotification($to, $username, $photoCount) {
        // 从系统配置读取邮件模板
        $subjectTemplate = Helper::getSystemConfig('email_template_photo_subject') ?: '您收到了新照片';
        $bodyTemplate = Helper::getSystemConfig('email_template_photo_body') ?: '
        <html>
        <head>
            <meta charset="utf-8">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #667eea;">您收到了新照片</h2>
                <p>亲爱的 {username}，</p>
                <p>您收到了 <strong>{photoCount}</strong> 张新照片，请登录查看。</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{siteUrl}/dashboard.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">查看照片</a>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $siteUrl = $this->getSiteUrl();
        
        // 替换变量
        $subject = str_replace(['{username}', '{photoCount}'], [$username, $photoCount], $subjectTemplate);
        $body = str_replace(['{username}', '{photoCount}', '{siteUrl}'], [$username, $photoCount, $siteUrl], $bodyTemplate);
        
        return $this->send($to, $subject, $body, true);
    }
    
    /**
     * 发送异常登录提醒
     */
    public function sendUnusualLoginAlert($to, $username, $ip, $location = '') {
        $subject = '异常登录提醒';
        $body = "
        <html>
        <head>
            <meta charset='utf-8'>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc3545;'>异常登录提醒</h2>
                <p>亲爱的 {$username}，</p>
                <p>检测到您的账号在以下位置登录：</p>
                <div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <p><strong>登录IP：</strong>{$ip}</p>
                    " . ($location ? "<p><strong>登录位置：</strong>{$location}</p>" : "") . "
                    <p><strong>登录时间：</strong>" . date('Y-m-d H:i:s') . "</p>
                </div>
                <p>如果这不是您的操作，请立即修改密码并联系管理员。</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$this->getSiteUrl()}/dashboard.php' style='display: inline-block; padding: 12px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 6px;'>立即查看</a>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $body, true);
    }
    
    /**
     * 发送密码修改提醒
     */
    public function sendPasswordChangeNotification($to, $username, $ip = '') {
        $subject = '密码修改提醒';
        $changeTime = date('Y-m-d H:i:s');
        $body = "
        <html>
        <head>
            <meta charset='utf-8'>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>密码修改提醒</h2>
                <p>亲爱的 {$username}，</p>
                <p>您的账号密码已成功修改。</p>
                <div style='background: #f0f4ff; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <p><strong>修改时间：</strong>{$changeTime}</p>
                    " . ($ip ? "<p><strong>修改IP：</strong>{$ip}</p>" : "") . "
                </div>
                <p>如果这不是您的操作，请立即登录账号修改密码并联系管理员。</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$this->getSiteUrl()}/dashboard.php' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;'>查看账号</a>
                </div>
                <p style='color: #999; font-size: 12px;'>此邮件由系统自动发送，请勿回复。</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $body, true);
    }
}

