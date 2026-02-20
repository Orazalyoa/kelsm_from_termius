# 上传限制已移除

## 已修改的配置

### 1. Apache .htaccess (public/.htaccess)
添加了以下配置：
```apache
php_value upload_max_filesize 100M
php_value post_max_size 100M
php_value max_execution_time 300
php_value max_input_time 300
```

### 2. PHP配置文件
创建了以下配置文件：

**php.ini** 和 **.user.ini**:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
max_file_uploads = 20
```

### 3. 后台表单
移除了图片上传的 `accept` 限制，允许上传任何图片格式。

## 当前限制

- **最大文件大小**: 100MB
- **POST数据大小**: 100MB
- **执行时间**: 300秒（5分钟）
- **内存限制**: 256MB
- **最多上传文件数**: 20个

## 注意事项

### 服务器配置
根据你的服务器环境，可能需要在以下位置额外配置：

1. **PHP-FPM** (如果使用Nginx):
   - 编辑 `/etc/php/8.x/fpm/php.ini`
   - 修改相同的参数
   - 重启PHP-FPM: `sudo systemctl restart php8.x-fpm`

2. **Nginx**:
   - 编辑 `/etc/nginx/nginx.conf` 或站点配置
   - 添加: `client_max_body_size 100M;`
   - 重启Nginx: `sudo systemctl restart nginx`

3. **Apache**:
   - 编辑 `/etc/php/8.x/apache2/php.ini`
   - 修改相同的参数
   - 重启Apache: `sudo systemctl restart apache2`

### 验证配置
创建一个测试文件查看当前配置：

**test-upload.php**:
```php
<?php
phpinfo();
```

访问这个文件，搜索：
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`

确认值已经更新。

### 如果限制仍然存在

1. **检查主PHP配置**:
   ```bash
   php -i | grep upload_max_filesize
   php -i | grep post_max_size
   ```

2. **检查服务器日志**:
   - Apache: `/var/log/apache2/error.log`
   - Nginx: `/var/log/nginx/error.log`
   - PHP: `/var/log/php-fpm/error.log`

3. **临时提高限制** (仅用于测试):
   在 `public/index.php` 开头添加：
   ```php
   ini_set('upload_max_filesize', '100M');
   ini_set('post_max_size', '100M');
   ini_set('max_execution_time', '300');
   ini_set('memory_limit', '256M');
   ```

## 安全建议

虽然已经移除了上传限制，但建议：

1. **验证文件类型**: 在应用层验证上传的文件确实是图片
2. **病毒扫描**: 对上传的文件进行病毒扫描
3. **存储优化**: 考虑使用CDN或对象存储来处理大文件
4. **压缩图片**: 自动压缩上传的图片以节省空间和带宽

## 数据库字段

`announcements` 表支持以下字段：
- `thumbnail` - 缩略图路径
- `image` - 完整图片路径

两者都可以是任意大小的图片文件。

