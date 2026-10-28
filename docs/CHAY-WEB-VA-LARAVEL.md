# Chạy web, admin và VNPAY — 28/10/2026

## Máy Windows hiện tại

Không nhập lại sản phẩm hoặc tạo lại APP_KEY. MySQL portable dùng `127.0.0.1:3307`, dữ liệu ở `.runtime/mysql-data`. `thu_thanh` là dữ liệu cửa hàng, `thu_thanh_demo` có 1.000 đơn giả lập riêng, `thu_thanh_test` dành kiểm thử. Không cần XAMPP, MongoDB hoặc extension VS Code.

Terminal 1:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start
cd backend
php artisan serve --host=localhost --port=8000
```

Terminal 2:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npm run dev
```

Mở http://localhost:3000. Dùng localhost nhất quán cho cookie/CSRF. Sau cập nhật source, restart server rồi Ctrl+F5. Dừng server bằng Ctrl+C; tắt MySQL an toàn bằng `scripts/mysql-local.ps1 -Action stop`, không xóa mysql-data.

## Vào admin

Mở http://localhost:3000/admin. Trong backend: `php artisan admin:account` xem định danh, `php artisan admin:password` đổi mật khẩu tương tác khi cần. Máy này có admin nên không tạo thêm. `admin:create` chỉ dùng với database mới chưa có admin; đăng ký public chỉ tạo khách hàng.

| URL | Chức năng |
|---|---|
| /admin | Tổng quan và doanh thu |
| /admin/products | Sản phẩm, ảnh, phần trăm giảm, archive/restore |
| /admin/categories | Danh mục, ẩn/hiện |
| /admin/inventory | Giá, tồn vật lý/giữ/có thể bán, điều chỉnh và lịch sử phân trang |
| /admin/orders | Tìm/lọc, xử lý đơn, lịch sử thanh toán/receipt/trạng thái |
| /admin/revenue | Ngày/tuần/tháng, chart và CSV |
| /admin/reconciliation | Khoản tiền chưa rõ, trùng, sai hoặc muộn cần đối soát |

35.000đ giảm 5% thành 33.250đ; Laravel tính số nguyên VND. Sửa giá sản phẩm không làm đổi snapshot đơn cũ. Không hoàn tất đơn chưa được xác minh thanh toán.

## Máy mới

1. Clone Payment; cài PHP 8.4, Composer 2, MySQL 8.4, Node 22 và Git; bật pdo_mysql cùng extension theo Composer.
2. Trong backend chạy `composer install`, copy `.env.example` thành `.env`, tạo database/user riêng và điền credentials. Dùng port MySQL thực tế của máy mới.
3. Copy `.env.testing.example` thành `.env.testing`, trỏ database **thu_thanh_test** riêng; không dùng database chính hoặc demo.
4. Chỉ chạy `php artisan key:generate` khi env mới chưa có khóa. Review migration rồi chạy `php artisan migrate`, `php artisan db:seed --class=CategorySeeder`, `php artisan storage:link`, `php artisan app:environment-check`.
5. Tạo admin tương tác nếu cần. Khôi phục database/uploads được cấp hoặc thêm sản phẩm qua admin; clone không tự có 136 SKU/1.000 đơn. Không migrate:fresh database đang dùng.
6. Trong frontend chạy `npm ci`; `.env.example` là mẫu tùy chọn cho `.env.local`, mặc định Laravel ở localhost8000. `LARAVEL_INTERNAL_URL` là cấu hình server; không đưa secret vào NEXT_PUBLIC.
7. Chạy server và kiểm backend test/Pint, frontend test/build/typecheck, docs checker từ gốc repo.

## Worker và scheduler

Mở hai terminal riêng trong backend:

```powershell
php artisan queue:work database --tries=5 --timeout=60
```

```powershell
php artisan schedule:work
```

Worker xử lý email/xóa ảnh; scheduler xử lý hết hạn, đối soát và dọn token. Đóng trình duyệt không làm mất dữ liệu MySQL. Chỉ mở web server chưa có nghĩa là công việc nền đã chạy.

## Thanh toán VNPAY Sandbox

Chủ dự án mới hoàn tất code/cấu hình, chưa giao dịch merchant thực tế. Máy Windows này chưa có TMN/hash secret và vẫn deferred.

1. Đăng ký merchant Sandbox tại https://sandbox.vnpayment.vn/devreg/.
2. Điền **VNPAY_TMN_CODE** và **VNPAY_HASH_SECRET** vào backend/.env private. Giữ VNPAY_ENV=sandbox; đặt VNPAY_DEFERRED=false khi cấu hình đầy đủ. Không gửi secret qua chat hoặc commit Git.
3. Giữ URL Sandbox theo env mẫu; local VNPAY_RETURN_URL=http://localhost:3000/payment/vnpay-return. Chạy `php artisan config:clear` sau đổi env.
4. Đăng nhập khách, tạo đơn giao/nhận tại cửa hàng, nhấn **Thanh toán qua VNPAY**, dùng thẻ test do VNPAY cấp. Kiểm success, hủy, retry, query, chậm/lặp/muộn bằng dữ liệu Sandbox riêng.
5. IPN cần endpoint HTTPS public khi có staging/tunnel được cấp. Khi khách quay về, Laravel kiểm chữ ký rồi query trực tiếp VNPAY; chỉ kết quả đã xác minh mới ghi paid. Frontend không tự xác nhận tiền.

Đơn giao giữ 300 giây, nhận tại cửa hàng giữ 86.400 giây; retry không gia hạn. Nếu khách đóng tab, scheduler query khi hết hạn; kết quả chưa rõ giữ tồn và chờ đối soát. QR thật tại quầy cần adapter merchant riêng; QR mẫu Figma không thay giao dịch thật. Không có thao tác manual-paid. Production cần phạm vi/cấu hình/nghiệm thu riêng.

## Email recovery

`/forgot-password` nhận email khách, `/reset-password` mở từ link riêng dùng một lần/60 phút. Token được hash; có CSRF, throttle, generic response và thu hồi phiên cũ sau đổi mật khẩu. Phone-only liên hệ nhà sách, chưa có SMS; admin dùng CLI riêng.

Trong backend/.env private, cấu hình MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME và FRONTEND_URL đúng HTTPS khi deploy. Chạy worker; config:clear ở local hoặc rebuild config cache/restart worker khi deploy. Log/array chỉ dành phát triển, production từ chối dùng chúng để giả gửi mail. SMTP thật chưa nghiệm thu; không gửi campaign cho tài khoản demo.

## Demo riêng

Nếu demo đã được chuẩn bị, dừng Next cũ rồi chạy hai terminal từ gốc repo:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-demo.ps1 -Component backend
```

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-demo.ps1 -Component frontend
```

Laravel demo dùng port18081, Next dùng3000; DB/cookie/key/storage riêng. `php scripts/prepare-demo.php` chỉ chuẩn bị portable local có guard/seed idempotent, không dùng trên hosting production. Tự chọn mật khẩu bằng `php artisan admin:password --env=demo`; worker/scheduler thêm --env=demo. Không chạy hai Next cùng thư mục/port hoặc copy env demo đè env thật.

## Sao lưu, Figma và deploy

Giữ env/APP_KEY, MySQL data, workbook và uploads. Ảnh thật ở backend/storage/app/public, ảnh demo ở .runtime/demo-storage/app/public; database giữ đường dẫn. Không sửa trực tiếp file mysql-data.

[Import plugin Figma V25](../thu-thanh-figma-clicon/HUONG-DAN.md) đã đồng bộ footer/banner D58; canvas không tự cập nhật khi sửa web, cần chạy lại builder. [README](../README.md) ghi tổng quan/giới hạn; [deploy](../deploy/README.md) ghi cấu hình, backup, cache và tải. Chưa nghiệm thu production hoặc 10k người đồng thời.
