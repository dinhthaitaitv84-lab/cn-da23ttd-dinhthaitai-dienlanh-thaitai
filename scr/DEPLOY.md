# Triển khai InfinityFree

1. Tạo database MySQL trong Control Panel InfinityFree, rồi import `setup/database_hosting.sql` vào database đó bằng phpMyAdmin. Tệp này xóa các bảng hiện có cùng tên trước khi tạo lại.
2. Tải toàn bộ nội dung thư mục `scr/` lên `htdocs/` của tên miền.
3. Tạo `config/config.local.php` trên hosting (không đưa tệp này lên Git):

```php
<?php
return [
    'APP_ENV' => 'production',
    'DB_HOST' => 'MySQL hostname trong Control Panel',
    'DB_NAME' => 'Tên database đầy đủ do InfinityFree cấp',
    'DB_USER' => 'Username database do InfinityFree cấp',
    'DB_PASS' => 'Mật khẩu database',
    'DB_PORT' => 3306,
    'BASE_URL' => '',
    'TIMEZONE' => 'Asia/Ho_Chi_Minh',
];
```

`BASE_URL` để trống để ứng dụng tự nhận diện domain và thư mục cài đặt. Nếu cần, có thể đặt URL đầy đủ, ví dụ `https://tenmien.rf.gd`.

4. Đảm bảo `public/uploads/` tồn tại và có quyền ghi; không gỡ `.htaccess` trong thư mục này. Cấu hình PHP nên cho phép `post_max_size` đủ nhận tối đa năm ảnh.
5. Đăng nhập lần đầu bằng `admin@dienlanh.com` / `ChangeMe@2026`, rồi đổi mật khẩu ngay. Mở trang chủ, kiểm tra điều hướng, gửi yêu cầu có ảnh và trang quản trị.
6. Bật HTTPS trong Control Panel; lỗi ứng dụng được ghi vào `logs/error.log` nếu hosting cho phép ghi.
