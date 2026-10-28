# Chạy web, admin và VNPAY — 29/10/2026

## Máy Windows hiện tại

Không nhập lại sản phẩm hoặc tạo lại APP_KEY. MySQL portable dùng `127.0.0.1:3307`, dữ liệu ở `.runtime/mysql-data`. `thu_thanh` là dữ liệu cửa hàng, `thu_thanh_test` dành kiểm thử. Không cần XAMPP, MongoDB hoặc extension VS Code.

Terminal 1:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start
powershell -ExecutionPolicy Bypass -File scripts/start-backend.ps1
```

Terminal 2:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npm run dev
```

Mở http://localhost:3000. Dùng localhost nhất quán cho cookie/CSRF. Sau cập nhật source, restart server rồi Ctrl+F5. Dừng server bằng Ctrl+C; tắt MySQL an toàn bằng `scripts/mysql-local.ps1 -Action stop`, không xóa mysql-data.

Script backend nạp cấu hình upload 5 MiB của dự án, giữ nguyên php.ini/extension hệ thống và khôi phục biến môi trường khi dừng. Đổi `.env` thì dừng/chạy lại script; `--no-reload` giữ cấu hình ini cho tiến trình HTTP con. Nếu chạy `php artisan serve` trực tiếp, PHP mặc định trên máy có thể chỉ nhận ảnh 2 MiB. Docker backend cũng nạp cùng file `backend/docker/php/uploads.ini`; chưa nghiệm thu deploy thực tế.

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

**Thêm sản phẩm:** `/admin/products` → **+ Thêm sản phẩm** → nhập tên, SKU riêng, danh mục, giá/giảm giá, tồn ban đầu; chỉ nhập lớp cho Sách giáo khoa. Chọn ảnh JPEG/PNG/WebP tối đa 5 MiB, xem trước rồi **Lưu sản phẩm**. Slug là mã đường dẫn nội bộ, hệ thống tự tạo; không cần nhập. Nếu ảnh tải lỗi, sản phẩm đã tạo vẫn được giữ: kiểm tra khối **Ảnh sản phẩm**, chọn ảnh và **Tải ảnh lên**; không tạo lại sản phẩm. SKU trùng sẽ có thông báo ngay ở trường SKU. Tick **Hiển thị trong Bán chạy nhất** để trưng bày; khi sửa có thể bỏ tick. Doanh số thật vẫn xếp trước sản phẩm chưa có doanh số, cờ không tạo đơn hoặc doanh thu.

**Tìm kiếm:** gõ trên header để xem tối đa 6 gợi ý từ catalog; nhấn sản phẩm để xem nhanh. Phím mũi tên/Enter chọn gợi ý, Escape đóng; Enter khi chưa chọn gợi ý hoặc **Xem tất cả kết quả** mở trang **Kết quả tìm kiếm**. Header không còn banner quảng cáo; banner trong trang chủ vẫn giữ.

## Chỉnh sửa thông tin cá nhân

Đăng nhập → Tài khoản → Thông tin cá nhân (`/account`). Sửa tên, email, số điện thoại rồi Lưu thay đổi. Đổi email/phone cần mật khẩu hiện tại; Laravel kiểm unique và cập nhật MySQL, header nhận thông tin mới. Email mới chưa được coi là đã xác minh; token khôi phục cũ bị hủy.

## Máy mới

1. Clone Payment; cài PHP 8.4, Composer 2, MySQL 8.4, Node 22 và Git; bật pdo_mysql cùng extension theo Composer.
2. Trong backend chạy `composer install`, copy `.env.example` thành `.env`, tạo database/user riêng và điền credentials. Dùng port MySQL thực tế của máy mới.
3. Copy `.env.testing.example` thành `.env.testing`, trỏ database **thu_thanh_test** riêng; không dùng database chính .
4. Chỉ chạy `php artisan key:generate` khi env mới chưa có khóa. Review migration rồi chạy `php artisan migrate`, `php artisan db:seed --class=CategorySeeder`, `php artisan storage:link`, `php artisan app:environment-check`.
5. Tạo admin tương tác nếu cần. Khôi phục database/uploads được cấp hoặc thêm sản phẩm qua admin; clone không tự có dữ liệu catalog. Không migrate:fresh database đang dùng.
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

## Thanh toán VNPAY Sandbox — máy hiện tại

Chưa có merchant nên chưa chạy giao dịch VNPAY thực tế. Đã chuẩn bị **`.runtime/sandbox-backend`**, database **thu_thanh_sandbox**, key/cookie/storage riêng,137sản phẩm và admin; **0 đơn, 0 receipt**, không sao chép khách hoặc lịch sử bán hàng. Database cửa hàng **thu_thanh** không đổi khi thử Sandbox. Workspace và env private không đi theo Git; máy mới dùng phần Máy mới với database thử riêng, không trỏ vào DB cửa hàng.

### 1. Đăng ký merchant

Mở [đăng ký Sandbox của VNPAY](https://sandbox.vnpayment.vn/devreg/). Điền tên website, URL website chạy thử và email bạn truy cập được; hoàn tất form/captcha. Nếu portal yêu cầu URL public, dùng địa chỉ HTTPS bạn quản lý. Kiểm tra hộp thư và spam để nhận thông tin kết nối. Giữ TMN code và hash secret ở file private, không gửi chat hoặc commit. [Tài liệu kết nối chính thức](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html) mô tả URL và thông tin merchant.

### 2. Điền đúng env của Sandbox

Mở **D:\ThuThanh-WebDesign\.runtime\sandbox-backend\.env**, không sửa env của backend chính. Chỉ thay hai giá trị được cấp và mở deferred:

```dotenv
VNPAY_ENV=sandbox
VNPAY_TMN_CODE=MA_MERCHANT_DUOC_CAP
VNPAY_HASH_SECRET=KHOA_BI_MAT_DUOC_CAP
VNPAY_DEFERRED=false
VNPAY_PAYMENT_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_QUERY_URL=https://sandbox.vnpayment.vn/merchant_webapi/api/transaction
VNPAY_RETURN_URL=http://localhost:3000/payment/vnpay-return
FRONTEND_URL=http://localhost:3000
```

Các chữ MA_MERCHANT_DUOC_CAP/KHOA_BI_MAT_DUOC_CAP là chỗ cần thay, không phải credentials dùng được. URL đã chuẩn bị sẵn theo [VNPAY PAY2.1.0](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html). Giữ DB_DATABASE=thu_thanh_sandbox, SESSION_COOKIE=thu_thanh_sandbox và APP_KEY đã tạo. Không key:generate lại; không chép đơn/receipt từ Sandbox sang database thật.

### 3. Kiểm cấu hình và chạy

```powershell
cd D:\ThuThanh-WebDesign\.runtime\sandbox-backend
php artisan config:clear
php artisan app:environment-check
php artisan payments:check-sandbox
```

Environment-check phải báo database **thu_thanh_sandbox**. Check-sandbox không in secret, không gọi provider: cần toàn bộ dòng [OK] trước khi test. Khi chưa điền merchant sẽ có [THIẾU/SAI] và exit1, đây là trạng thái chưa cấu hình. Kết quả [OK] chỉ xác nhận cấu hình, không khẳng định VNPAY đã chấp nhận merchant.

Tắt backend chính nếu đang giữ port8000 (Ctrl+C), rồi chạy **từ thư mục Sandbox**:

```powershell
$env:PHP_INI_SCAN_DIR=';D:\ThuThanh-WebDesign\.runtime\sandbox-backend\docker\php'
php artisan serve --host=localhost --port=8000 --no-reload
```

Hai terminal khác trong cùng thư mục Sandbox:

```powershell
php artisan queue:work database --tries=5 --timeout=60
```

```powershell
php artisan schedule:work
```

Frontend giữ cách chạy cũ (`cd D:\ThuThanh-WebDesign\frontend`, `npm run dev`), proxy mặc định tới localhost8000. Mở localhost3000 và Ctrl+F5 sau khi đổi backend. Không chạy đồng thời hai Next từ cùng thư mục. Không cần extension hoặc XAMPP. Mail Sandbox dùng array, không gửi email thật.

### 4. Thử thành công và hủy

1. Đăng ký một tài khoản **khách** trong Sandbox; admin không dùng giỏ mua hàng. Admin Sandbox dùng thông tin đăng nhập admin đã có của cửa hàng, không có mật khẩu mặc định mới.
2. Chọn một sản phẩm còn tồn, ví dụ Bữa Tối; thêm1món → checkout giao hàng → nhập thông tin → tạo đơn. Phí giao25.000đ; đơn giao giữ5phút, nên chuẩn bị sẵn thẻ test trước khi đặt.
3. Trong đơn nhấn **Thanh toán qua VNPAY**, nhập **thẻ thử nghiệm** từ [danh sách test chính thức](https://sandbox.vnpayment.vn/apis/vnpay-demo/) hoặc tài liệu VNPAY cấp. Không dùng thẻ thật. Hoàn tất rồi quay lại website.
4. Chỉ khi Laravel kiểm chữ ký và query trực tiếp VNPAY xác nhận đúng giao dịch mới hiện **Đã thanh toán**. Kiểm đơn và báo cáo trong database Sandbox; tải lại trang không được thu/ghi doanh thu lần hai.
5. Tạo một đơn Sandbox khác, sang VNPAY rồi chọn hủy: phải trở về **Chưa thanh toán/không thành công**, không tăng doanh thu; có thể thử lại khi còn hạn. Retry không gia hạn5phút. Scheduler xử lý hết hạn; kết quả chưa rõ giữ tồn và chờ đối soát.

IPN là thông báo server-to-server: localhost không nhận được từ VNPAY. Lần thử local dùng browser Return URL và server query. Để nghiệm thu IPN, cần HTTPS public và đăng ký URL **/api/v1/payments/vnpay/ipn** ở phía merchant; chỉ điền VNPAY_IPN_URL trong env không tự đăng ký endpoint với VNPAY. Xem [hướng dẫn IPN/Return](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html). Chưa cấu hình tunnel hoặc deploy trong bước này.

Luồng này thử VNPAY online; QR thu tại quầy cần adapter merchant riêng. Không có nút manual-paid. Nếu thấy lỗi chữ ký, kiểm lại đúng TMN/secret/URL, config:clear và restart; không tắt kiểm chữ ký/TLS hoặc sửa DB thành paid. Sandbox không phải doanh thu tiền thật.

Kết thúc test: dừng server/worker/scheduler Sandbox, chạy lại scripts/start-backend.ps1 từ repo gốc để trở về DB cửa hàng. Không chép env/key/database Sandbox đè lên backend chính.

## Email recovery

`/forgot-password` nhận email khách, `/reset-password` mở từ link riêng dùng một lần/60 phút. Token được hash; có CSRF, throttle, generic response và thu hồi phiên cũ sau đổi mật khẩu. Phone-only liên hệ nhà sách, chưa có SMS; admin dùng CLI riêng.

Trong backend/.env private, cấu hình MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME và FRONTEND_URL đúng HTTPS khi deploy. Chạy worker; config:clear ở local hoặc rebuild config cache/restart worker khi deploy. Log/array chỉ dành phát triển, production từ chối dùng chúng để giả gửi mail. SMTP thật chưa nghiệm thu; chưa mở gửi bản tin hàng loạt.

## Sao lưu, Figma và deploy

Giữ env/APP_KEY, MySQL data, workbook và uploads. Ảnh thật ở backend/storage/app/public, database giữ đường dẫn. Không sửa trực tiếp file mysql-data.

[Import plugin Figma V26](../thu-thanh-figma-clicon/HUONG-DAN.md) đã đồng bộ D60–D61; flow tối đa12màn/hàng, landing riêng; canvas không tự cập nhật khi sửa web, cần chạy lại builder. [README](../README.md) ghi tổng quan/giới hạn; [deploy](../deploy/README.md) ghi cấu hình, backup, cache và tải. Chưa nghiệm thu production hoặc 10k người đồng thời.
