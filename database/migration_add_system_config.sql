-- 创建系统配置表
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value TEXT COMMENT '配置值',
    description VARCHAR(255) COMMENT '配置说明',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 插入默认配置
INSERT INTO system_config (config_key, config_value, description) VALUES
('register_require_email', '0', '注册时是否要求填写邮箱：1-必填，0-可选'),
('register_require_email_verification', '0', '注册时是否强制邮箱验证：1-必须验证后才能登录，0-不需要验证')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

