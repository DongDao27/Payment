> Hướng dẫn hiện hành sau cập nhật web D41: [CHAY-WEB-VA-LARAVEL](CHAY-WEB-VA-LARAVEL.md), gồm lệnh chạy trên máy hiện tại, MySQL portable, giải thích Laravel và kiến trúc deploy. Không reset/import dữ liệu để cập nhật giao diện.

# Thiết lập local và hướng đến triển khai

## Môi trường

Windows PowerShell, PHP 8.4, Composer 2, MySQL 8.4 LTS. Node22 đang dùng cho frontend Next.js trong thư mục frontend/. `pdo_mysql` đã bật trong PHP hiện tại, bản sao php.ini trước sửa có hậu tố `.thu-thanh-backup` cạnh php.ini.

MySQL portable nằm trong `.runtime/` (Git ignore), không cài service hệ thống và không thay database dự án khác. Database `thu_thanh` dùng phát triển; `thu_thanh_test` dành riêng tests. Tài khoản ứng dụng chỉ có quyền trên database tương ứng, mật khẩu ngẫu nhiên được lưu trong env riêng, không có tài khoản admin nhà sách mặc định.

## Chạy lại sau khi khởi động máy

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start
cd backend
php artisan app:environment-check
php artisan migrate
php artisan serve --host=localhost --port=8000
```

MySQL: `127.0.0.1:3307`; Laravel: `http://localhost:8000`. Dùng hostname `localhost` nhất quán khi mở API và frontend để session/CSRF hoạt động đúng.

Tắt database an toàn bằng `scripts/mysql-local.ps1 -Action stop`. Không xóa `.runtime/mysql-data`, `.env`, `.env.testing` hoặc dữ liệu upload khi nâng cấp source.

## Cấp tài khoản admin và vận hành local

Trong `backend/`, chạy `php artisan admin:create`, nhập tên và email/phone, rồi mật khẩu bí mật12–128 ký tự. Password không hiển thị và không lưu vào tài liệu. Chưa có tài khoản admin thật được AI cấp trong phiên build; lệnh không reset mật khẩu khi admin đã tồn tại.

Mở thêm terminal khi cần xử lý nền:

```powershell
cd D:\ThuThanh-WebDesign\backend
php artisan queue:work database --tries=5 --timeout=60
```

```powershell
cd D:\ThuThanh-WebDesign\backend
php artisan schedule:work
```

Giữ các tiến trình chạy trong lúc sử dụng local. Worker xử lý xóa ảnh sau commit; scheduler quét hạn và đối soát. Khi deploy, Compose có service worker/scheduler riêng.

Có thể thử `GET http://localhost:8000/api/v1/categories` (sáu danh mục) và `/products` (đã có136 sản phẩm thật, có phân trang). Đã có frontend Next.js tại localhost:3000; xem mục mới bên dưới. Không tạo dữ liệu/giá/tồn giả vào database phát triển.

Lệnh kiểm tra bổ sung từ gốc repo: `php scripts/auth-cookie-smoke.php`, `php scripts/ops-smoke.php`, `python scripts/check_pack.py`. Hai smoke scripts từ chối chạy nếu database không phải thu_thanh_test.

## VNPAY và chính sách hiện tại

Theo D29–D32: giao hàng mặc định 25.000đ/đơn, pickup 0đ. `STORE_DELIVERY_FEE_VND=25000`; bỏ trống dùng mặc định. Không hỗ trợ đổi trả/hoàn tiền; API refund mới trả403, giữ nguyên lịch sử/evidence.

VNPAY để sau: `VNPAY_DEFERRED=true`, tạo payment attempt mới trả503 `PAYMENT_INTEGRATION_DEFERRED`. Không cần nhập merchant credentials lúc này. Callback/query lịch sử vẫn phải kiểm chứng; không tự paid, không thêm COD. Khi mở lại giai đoạn thanh toán phải xác minh Sandbox/callback và kênh QR riêng.

Báo cáo doanh thu vẫn policy-gated503 cho tới khi công thức được chốt. Không tự bật bằng cấu hình mẫu. Xem [REQUIREMENTS-AUDIT](REQUIREMENTS-AUDIT.md) cho phạm vi đã kiểm, thiết kế local và extension.

## Thiết lập lại trên máy khác

1. Cài PHP 8.4 và Composer, bật extensions cần thiết; cài MySQL 8.4 từ nguồn chính thức hoặc dùng instance riêng.
2. `composer install` trong `backend/` theo lock file.
3. Copy `.env.example` → `.env`, nhập credentials MySQL; tạo database và user riêng. Copy `.env.testing.example` → `.env.testing`, cấu hình database `thu_thanh_test` và user chỉ có quyền trên database test.
4. `php artisan key:generate` chỉ khi APP_KEY chưa có. Không đổi khóa của môi trường đang có dữ liệu.
5. Review migrations rồi `php artisan migrate`. Không dùng `migrate:fresh` trên dữ liệu phát triển/thật.
6. Chạy environment-check, tests, Pint và audit. Đọc checkpoint trước khi làm task tiếp theo.

## Chuẩn bị deploy

Hệ thống đích đề xuất: Linux VPS, Nginx, PHP-FPM 8.4, MySQL 8.4, supervisor/systemd cho worker, cron scheduler; Node cho frontend sau gate. Cần domain, HTTPS, SMTP, backup DB và uploads/evidence. Web root chỉ trỏ `backend/public`, không trỏ gốc repository.

Build và kiểm chứng backend trước; staging tách credentials/data với production. Chốt công thức doanh thu còn OPEN; VNPAY thực được hoãn theo yêu cầu và phải kiểm riêng trước khi nhận thanh toán thật. Sau đó chọn hosting, cấu hình HTTPS/env bảo mật, chạy deploy staging và kiểm restore backup. Production/thanh toán thật cần yêu cầu riêng. Xem runbook [OPERATIONS](OPERATIONS.md) và gate [TEST-PLAN](TEST-PLAN.md).

## Frontend đã triển khai local — D34/D35, 22/10/2026

Giữ Laravel8000 chạy rồi mở terminal khác: `cd D:\ThuThanh-WebDesign\frontend` → `npm run dev`. Mở http://localhost:3000. Next proxy /api/v1, /sanctum/csrf-cookie và /storage về Laravel; không cần cấu hình CORS wildcard hoặc bearer token cho browser. Nếu chuyển máy, `npm ci` trong frontend trước.

136 sản phẩm/ảnh đã có trong thu_thanh; cột O/P của workbook cập nhật là giá bán/tồn. Không cần nhập lại để xem web. Admin:create vẫn tương tác để chủ dự án chọn secret; không có admin/mật khẩu mẫu.

Để nhập workbook mới: từ gốc chạy `python scripts/audit-products.py <workbook.xlsx>`, xem staged_directory/sha và docs/PRODUCT-IMPORT-AUDIT.md. Sau đó `php backend/artisan catalog:import <staged catalog.json>` chạy dry-run; thêm `--apply` mới ghi. Import chỉ thêm SKU mới, không ghi đè SKU đã có, không reset tồn/giá sau bán. Cập nhật dữ liệu đã có qua API/admin UI và adjustment có reason. 18 nhóm con được giữ trong mô tả/audit, không dùng source category IDs thay FK MySQL.

Để kiểm app: trong frontend chạy `npm run test:api`, `npm run build`, `npm run typecheck`; trong backend chạy `php artisan test --log-junit=storage/logs/phpunit.xml` (phải ở cwd backend). Xem VERIFICATION.md cho browser QA, giới hạn ảnh preview và phần deferred.


Cập nhật kiểm tra23/10/2026: development DB có136 sản phẩm/6 danh mục/1 admin, giá và tồn khớp136 dòng nguồn. Không cần import lại hoặc tạo admin thứ hai. Nếu MySQL đang tắt, dùng lệnh start ở trên; không install/reset lại dữ liệu. Để chạy frontend, mở terminal tại `frontend/` rồi `npm run dev`, truy cập `http://localhost:3000`. Chi tiết phần code còn thiếu xem MD-CLEANUP-MANIFEST.json.


## Cập nhật D39 — footer và QR prototype

Hướng dẫn chạy an toàn cho máy hiện tại và kết quả review thanh toán ở [RUN-AND-PAYMENT-REVIEW-2026-10-23](MD-CLEANUP-MANIFEST.json). Figma V19 có đầy đủ QR/kết quả mẫu; QR thật chưa có adapter, VNPAY tiếp tục deferred. Phí25000/0 không đổi nhưng chỉ hiển thị tại checkout/chứng từ đơn đã đặt. Không cần migrate/import/admin mới cho thay đổi giao diện này.
