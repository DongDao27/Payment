# Thu Thanh Backend

Laravel12.70.0 / PHP8.4 / MySQL8.4 / Sanctum cookie sessions. Xem [README gốc](../README.md), [thiết lập local](../docs/LOCAL-SETUP.md) và [kiểm chứng](../docs/VERIFICATION.md).

```powershell
composer install
php artisan app:environment-check
php artisan migrate
php artisan db:seed
php artisan admin:create
php artisan serve --host=localhost --port=8000
```

Admin được cấp tương tác, không mật khẩu mặc định. Migration/seed không tự reset data. Database phát triển và thu_thanh_test tách biệt, tests dùng user riêng. Kiểm tra: php artisan test; php vendor/bin/pint --test; composer audit. HTTP/ops smoke scripts nằm trong ../scripts và chỉ chạy trên thu_thanh_test.

VNPAY Sandbox/QR và chính sách chưa nghiệm thu nên backend gate chưa đạt. Frontend theo Figma sẽ làm sau gate. Docker staging template ở ../deploy, chưa chạy build container trên máy hiện tại.
