# Thiết lập local

Để chạy lại trên máy đang có dữ liệu, dùng [CHAY-WEB-VA-LARAVEL](CHAY-WEB-VA-LARAVEL.md). Hướng dẫn dưới đây dành cho máy mới; không cài lại database hoặc đổi APP_KEY trên máy hiện tại.

## Yêu cầu

- PHP 8.4 với các extension theo Composer, gồm `pdo_mysql`; Composer 2.
- MySQL 8.4; Node.js 22 và npm; Git.
- Không cần XAMPP, MongoDB hoặc extension VS Code để chạy ứng dụng.

## Backend

1. Clone repo Payment; trong `backend/`, chạy `composer install` theo lockfile.
2. Sao chép `.env.example` thành `.env`, điền credentials MySQL riêng. Tạo database `thu_thanh` và user chỉ có quyền trên database tương ứng.
3. Sao chép `.env.testing.example` thành `.env.testing`; dùng database **thu_thanh_test** và credentials riêng. Không trỏ test vào database chính/demo.
4. Chạy `php artisan key:generate` chỉ khi env mới chưa có APP_KEY. Giữ nguyên khóa của môi trường có dữ liệu.
5. Review migration rồi chạy `php artisan migrate`; chạy `php artisan db:seed --class=CategorySeeder` để tạo danh mục nếu cần. Seeder không tạo mật khẩu admin hay sản phẩm giả.
6. Trên database mới chưa có admin, chạy `php artisan admin:create` và tự nhập thông tin/mật khẩu. Database hiện có dùng `admin:account`/`admin:password` theo hướng dẫn chạy.
7. `php artisan storage:link`, `php artisan app:environment-check`, rồi chạy server/worker/scheduler theo hướng dẫn.

Máy hiện tại dùng MySQL portable `127.0.0.1:3307`; máy khác điền port MySQL thực tế, thường là3306. Không sao chép nguyên `.env`, database hoặc APP_KEY của người khác vào repo. Script `mysql-local.ps1` và `prepare-local-db.php` chỉ dành cho provisioning portable local có guard, không dùng cho hosting production.

## Frontend

Trong `frontend/`, chạy `npm ci`, cấu hình từ `templates/frontend.env.example` nếu cần, rồi `npm run dev`. Mặc định Next.js localhost3000 proxy API/cookie/storage tới Laravel localhost8000. `LARAVEL_INTERNAL_URL` là cấu hình server, không phải secret database trong NEXT_PUBLIC.

Trên máy mới không tự có 136 sản phẩm hoặc 1.000 đơn demo chỉ nhờ clone source. Khôi phục database/upload từ bản sao được cấp hoặc thêm sản phẩm qua admin. Các script tải ảnh/import workbook một lần đã được lưu ngoài repo; workbook nguồn và ảnh giao diện hiện có vẫn được giữ.

## Kiểm tra

Trong backend: `composer check-platform-reqs`, `php artisan test`, `php vendor/bin/pint --test`.
Trong frontend: `npm run test:api`, `npm run build`, `npm run typecheck`.
Từ gốc repo: `python scripts/check_pack.py`; CI còn chạy `php scripts/auth-cookie-smoke.php` và `php scripts/ops-smoke.php`, chỉ cho phép database test riêng.

## Chính sách và triển khai

Delivery25.000đ tại checkout, pickup0đ; không đổi trả/hoàn tiền. Doanh thu tiền hàng sau giảm, ship riêng đã được chốt. `VNPAY_DEFERRED=true` giữ payment thật hoãn, không tự paid. SMTP thật cần cấu hình riêng cho recovery; không dùng log/array trong production.

Chưa có staging/hosting và chưa nghiệm thu10k. Xem [OPERATIONS](OPERATIONS.md), [cấu hình Docker](../deploy/README.md) và [PERFORMANCE](PERFORMANCE.md) trước khi triển khai; việc dọn source không thay nghiệm thu thực tế.
