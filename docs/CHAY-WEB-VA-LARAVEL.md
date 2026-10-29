# Chạy web, admin và VNPAY — 29/10/2026

## Mở lại máy để thử VNPAY Sandbox

Không đăng ký merchant, nhập khóa hoặc tạo database/APP_KEY lại. Thông tin đã lưu trong `.runtime/sandbox-backend/.env`, dữ liệu ở MySQL. Chỉ cần bật lại dịch vụ. Backend bắt buộc chạy, nhưng launcher sau chạy backend/queue/scheduler ở nền nên không cần mở thêm terminal backend:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/sandbox.ps1 -Action start
```

Lệnh kiểm đúng database Sandbox, chuẩn bị CA tin cậy nếu thiếu, kiểm cấu hình rồi chạy MySQL3307/backend8000/queue/scheduler; gọi lại không mở trùng dịch vụ. Không reset/import dữ liệu. Terminal frontend:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npm run dev
```

Mở **http://localhost:3000**. Nếu frontend đang chạy thì giữ nguyên, không mở npm lần hai. Không chạy `scripts/start-backend.ps1` cùng lúc vì đó là backend cửa hàng, dùng cùng port8000.

```powershell
# Xem các dịch vụ và port
powershell -ExecutionPolicy Bypass -File D:\ThuThanh-WebDesign\scripts\sandbox.ps1 -Action status
# Dừng riêng backend/queue/scheduler Sandbox; giữ dữ liệu/MySQL/frontend/tunnel
powershell -ExecutionPolicy Bypass -File D:\ThuThanh-WebDesign\scripts\sandbox.ps1 -Action stop
```

Nếu đổi private env: stop rồi start lại. Launcher hiện dùng workspace private trên máy này; máy khác cần chuẩn bị workspace/database/env riêng trước, không có secret đi kèm Git.

Nếu cần IPN public, chạy Cloudflare trong terminal riêng:

```powershell
& "D:\cloudflared-windows-amd64.exe" tunnel --url http://localhost:3000
```

Quick Tunnel có thể đổi URL sau khi tắt/mở. URL IPN là **URL_HTTPS_MOI/api/v1/payments/vnpay/ipn**, cần gửi/lưu lại ở VNPAY khi thay đổi; không phải đăng ký merchant mới. Giữ tunnel trong lúc test. Return URL local vẫn localhost3000, đăng nhập và thanh toán bằng localhost.

## Chạy dữ liệu cửa hàng trên máy Windows hiện tại

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

D65: cấu hình merchant private và9/9readiness checks đạt; đã thực hiện NCB/OTP trên cổng Sandbox và nhận signed Return00, đã thử hủy24. Chưa có receipt hợp lệ do QueryDr trả99 và IPN chưa được người dùng đăng ký. Không gọi đây là toàn luồng thành công. Đã chuẩn bị **`.runtime/sandbox-backend`**, database **thu_thanh_sandbox**, key/cookie/storage riêng,137sản phẩm và admin; Ban đầu0đơn/0receipt; D65 có3đơn Sandbox (1owner,2đơn kiểm giao thức),0receipt. Không sao chép lịch sử cửa hàng; đây là giao dịch thử Sandbox thực, không tạo doanh số giả. Database cửa hàng **thu_thanh** không đổi khi thử Sandbox. Workspace và env private không đi theo Git; máy mới dùng phần Máy mới với database thử riêng, không trỏ vào DB cửa hàng.

### 1. Merchant đã đăng ký trên máy hiện tại

Máy hiện tại đã hoàn thành bước đăng ký, không cần đăng ký lại. Khi thiết lập merchant mới, mở [đăng ký Sandbox của VNPAY](https://sandbox.vnpayment.vn/devreg/). Điền tên website, URL website chạy thử và email bạn truy cập được; hoàn tất form/captcha. Nếu portal yêu cầu URL public, dùng địa chỉ HTTPS bạn quản lý. Kiểm tra hộp thư và spam để nhận thông tin kết nối. Giữ TMN code và hash secret ở file private, không gửi chat hoặc commit. [Tài liệu kết nối chính thức](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html) mô tả URL và thông tin merchant.

### 2. Điền đúng env của Sandbox

Máy hiện tại đã điền credentials trong **D:\ThuThanh-WebDesign\.runtime\sandbox-backend\.env**; không cần dán lại hoặc sửa env của backend chính. Khi đổi khóa, chỉ sửa file private này. Mẫu dưới đây là cấu trúc tham khảo, không chép placeholder đè lên giá trị đã cấu hình:

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

D63 đã khởi động backend8000, queue và scheduler của bản Sandbox ở nền, frontend3000 của người dùng giữ nguyên. Có thể thử ngay tại **http://localhost:3000**. Không chạy thêm backend trùng port. Khi muốn dừng đúng ba dịch vụ Sandbox do phiên này mở:

```powershell
powershell -ExecutionPolicy Bypass -File D:\ThuThanh-WebDesign\scripts\sandbox.ps1 -Action stop
```

Sau khi dừng, có thể mở các terminal và chạy thủ công theo lệnh phía trên. Script và workspace private chỉ có trên máy hiện tại.

### 4. Thử thành công và hủy

Mở web bằng **localhost3000** để cookie/phiên trùng Return URL, chưa dùng URL Cloudflare để đăng nhập/thanh toán ở lượt local này. Tunnel đăng ký không tự đăng ký IPN.

1. Đăng ký một tài khoản **khách** trong Sandbox; admin không dùng giỏ mua hàng. Admin Sandbox dùng thông tin đăng nhập admin đã có của cửa hàng, không có mật khẩu mặc định mới.
2. Chọn một sản phẩm còn tồn, ví dụ Bữa Tối; thêm1món → checkout giao hàng → nhập thông tin → tạo đơn. Phí giao25.000đ; đơn giao giữ5phút, nên chuẩn bị sẵn thẻ test trước khi đặt.
3. Trong đơn nhấn **Thanh toán qua VNPAY**, nhập **thẻ thử nghiệm** từ [danh sách test chính thức](https://sandbox.vnpayment.vn/apis/vnpay-demo/) hoặc tài liệu VNPAY cấp. Không dùng thẻ thật. Hoàn tất rồi quay lại website.
4. Chỉ khi Laravel kiểm chữ ký và query trực tiếp VNPAY xác nhận đúng giao dịch mới hiện **Đã thanh toán**. Kiểm đơn và báo cáo trong database Sandbox; tải lại trang không được thu/ghi doanh thu lần hai.
5. Tạo một đơn Sandbox khác, sang VNPAY rồi chọn hủy: phải trở về **Chưa thanh toán/không thành công**, không tăng doanh thu; có thể thử lại khi còn hạn. Retry không gia hạn5phút. Scheduler xử lý hết hạn; kết quả chưa rõ giữ tồn và chờ đối soát.

D63 đã kiểm tra URL IPN public qua tunnel frontend tại thời điểm đó (địa chỉ lịch sử, không dùng lại nếu tunnel đã đổi) (Next proxy tới bản Sandbox8000):

```text
https://airport-midlands-edmonton-pull.trycloudflare.com/api/v1/payments/vnpay/ipn
```

Gửi URL này cho VNPAY theo email họ yêu cầu hoặc nhập vào hệ thống SIT khi có trường cấu hình IPN. Phiên này chưa đăng ký thay bạn hoặc gửi email. Mở URL trực tiếp không có chữ ký sẽ trả `97 / Invalid signature`; đây chỉ là kiểm kết nối và từ chối callback giả, không phải giao dịch đã thành công. Giữ terminal Cloudflare/frontend/backend chạy khi test. URL Quick Tunnel tạm thời; nếu đổi URL phải báo lại VNPAY. Có thể dùng tunnel frontend vì Next đã proxy /api/v1 tới backend; không cần tunnel thứ hai cho lượt này. Return URL vẫn localhost3000, hãy dùng localhost khi đăng nhập/thanh toán.

IPN là thông báo server-to-server: localhost không nhận được từ VNPAY. Lần thử local dùng browser Return URL và server query. Để nghiệm thu IPN, cần HTTPS public và đăng ký URL **/api/v1/payments/vnpay/ipn** ở phía merchant; chỉ điền VNPAY_IPN_URL trong env không tự đăng ký endpoint với VNPAY. Xem [hướng dẫn IPN/Return](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html). Chưa cấu hình tunnel hoặc deploy trong bước này.

Luồng này thử VNPAY online; QR thu tại quầy cần adapter merchant riêng. Không có nút manual-paid. Nếu thấy lỗi chữ ký, kiểm lại đúng TMN/secret/URL, config:clear và restart; không tắt kiểm chữ ký/TLS hoặc sửa DB thành paid. Sandbox không phải doanh thu tiền thật.

Kết thúc test: dừng server/worker/scheduler Sandbox, chạy lại scripts/start-backend.ps1 từ repo gốc để trở về DB cửa hàng. Không chép env/key/database Sandbox đè lên backend chính.

### Kết quả kiểm thực tế D65 và việc cần làm với VNPAY

- Đơn `01M62V4HG81MWVQ7D9WN8JMFA9`: nhận cửa hàng,10.560đ, thẻ NCB test → đồng ý điều khoản → OTP test → VNPAY Return00/TransactionStatus00, giao dịch VNPAY `15718352`, tham chiếu `c1990952bf0045c384d0f54f5269bbd2`. Backend kiểm chữ ký nhưng không lấy Return làm bằng chứng thu tiền. QueryDr vẫn99, chưa IPN,0receipt; hệ thống chuyển chờ đối soát, không trả lại đơn này.
- Đơn `01M62VPSVVGVCEDME3XN76J64W`: hủy trên cổng trả24/status02, web hiển thị Chưa thanh toán và lời nhắc hủy,0receipt. Không tăng doanh thu.
- QueryDr đã đối chiếu field với tài liệu VNPAY và thử bằng Laravel/curl độc lập:99/Unexpected error. Probe có transaction-no đúng, request-id số, IP công khai vẫn99; đổi tên date sang field khác trả Parameter required nên giữ nguyên field đúng spec. Không sửa adapter theo phỏng đoán, không hạ kiểm chữ ký/TLS.
- User xác nhận **chưa gửi/chưa lưu IPN**. Endpoint public qua Next proxy trả97 khi không có chữ ký (đúng kiểm kết nối), chưa có callback server hợp lệ. Đây là bước chưa hoàn thành, không phải thiếu terminal khibackend8000 đang chạy.

Trả lời email VNPAY hoặc cấu hình ô IPN trong Merchant Admin nếu có; không cần cung cấp password/hash secret. Có thể sao chép nội dung sau (thay URL nếu tunnel đã đổi):

```text
Nhờ VNPAY cấu hình IPN Sandbox:
[URL_HTTPS_MOI_LAY_TU_TERMINAL]/api/v1/payments/vnpay/ipn

Đã thực hiện thẻ NCB/OTP trên Sandbox, ReturnCode=00, TransactionStatus=00.
Mã giao dịch VNPAY: 15718352
Mã tham chiếu: c1990952bf0045c384d0f54f5269bbd2
Số tiền: 10.560 VND
Thời gian trả kết quả: 29/10/2026 10:28:33 (giờ Việt Nam).
API QueryDr đang trả HTTP200, vnp_ResponseCode=99, vnp_Message=Unexpected error.
Nhờ kiểm tra quyền/cấu hình truy vấn của merchant, giao dịch trên Merchant Admin
và hỗ trợ gửi lại IPN hợp lệ. TMN code có trong email kết nối của tôi.
```

Chưa gửi email thay người dùng. Sau khi VNPAY xác nhận, giữ các dịch vụ/tunnel chạy để nhận IPN. Đơn chỉ paid khi xác minh hợp lệ và thỏa luật tiền/tồn/thời điểm; không thao tác manual-paid. Không giả callback bằng cách lấy querystring Return gửi vào IPN. Không tái thanh toán đơn đang đối soát. Khởi động lại máy không làm mất cấu hình; chỉ tunnel URL tạm có thể đổi.

### Khi kết quả vẫn “Đang xử lý” — kiểm tra D64

Trang kết quả chỉ lấy trạng thái một lần khi mở, không tự chuyển chữ sau khi backend cập nhật. Bấm **Xem chi tiết đơn** hoặc tải lại trang để xem trạng thái mới. Chỉ IPN hợp lệ hoặc server query có kết quả xác minh được mới ghi paid; không dựa vào ảnh chụp hoặc chữ thành công trên cổng để sửa DB.

Ngày29/10 đã thấy PHP local thiếu CA gây `cURL error60`. Đã tải [Mozilla CA bundle do curl cung cấp](https://curl.se/docs/caextract.html), đối chiếu SHA256 và cấu hình private `.runtime/sandbox-backend/docker/php/cacert.ini`:

```ini
curl.cainfo="D:/ThuThanh-WebDesign/.runtime/certs/cacert.pem"
openssl.cafile="D:/ThuThanh-WebDesign/.runtime/certs/cacert.pem"
```

Ba dịch vụ Sandbox đã restart với `PHP_INI_SCAN_DIR` theo phần chạy phía trên. Máy mới phải chuẩn bị CA tin cậy phù hợp PHP/OS; không chép đường dẫn Windows này sang Docker/Linux. Không dùng verify=false hoặc tắt kiểm chữ ký. [Giải thích TLS của curl](https://curl.se/docs/sslcerts.html).

Sau sửa CA, VNPAY query đang trả HTTP200/mã99/Unexpected error cho giao dịch được hỏi; đây không phải xác nhận thành công. Đơn hết hạn chuyển chờ đối soát và giữ tồn theo luật sẵn có. Không thanh toán lại đơn đó. Kiểm trạng thái giao dịch trong Merchant Admin VNPAY; gửi URL IPN cho VNPAY và yêu cầu kiểm cấu hình/kết quả query99 với mã tham chiếu giao dịch. Không gửi hash secret/mật khẩu hoặc tự sửa đơn paid. IPN/query hợp lệ còn phải qua luật amount/merchant/reference/thời điểm/tồn; giao dịch trả muộn có thể cần đối soát thay vì tự paid. Mã99 không đủ để kết luận ngân hàng đã trừ tiền hoặc giao dịch đã thất bại. [Đặc tả QueryDr](https://sandbox.vnpayment.vn/apis/docs/truy-van-hoan-tien/querydr%26refund.html).

## Email recovery

`/forgot-password` nhận email khách, `/reset-password` mở từ link riêng dùng một lần/60 phút. Token được hash; có CSRF, throttle, generic response và thu hồi phiên cũ sau đổi mật khẩu. Phone-only liên hệ nhà sách, chưa có SMS; admin dùng CLI riêng.

Trong backend/.env private, cấu hình MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME và FRONTEND_URL đúng HTTPS khi deploy. Chạy worker; config:clear ở local hoặc rebuild config cache/restart worker khi deploy. Log/array chỉ dành phát triển, production từ chối dùng chúng để giả gửi mail. SMTP thật chưa nghiệm thu; chưa mở gửi bản tin hàng loạt.

## Sao lưu, Figma và deploy

Giữ env/APP_KEY, MySQL data, workbook và uploads. Ảnh thật ở backend/storage/app/public, database giữ đường dẫn. Không sửa trực tiếp file mysql-data.

[Import plugin Figma V26](../thu-thanh-figma-clicon/HUONG-DAN.md) đã đồng bộ D60–D61; flow tối đa12màn/hàng, landing riêng; canvas không tự cập nhật khi sửa web, cần chạy lại builder. [README](../README.md) ghi tổng quan/giới hạn; [deploy](../deploy/README.md) ghi cấu hình, backup, cache và tải. Chưa nghiệm thu production hoặc 10k người đồng thời.
# Cập nhật D66 — giao diện và kiểm thử FE–BE (29/10/2026)

Bán chạy nhất hiển thị danh sách xếp hạng bên trái và sản phẩm được chọn bên phải, có ngọn lửa, giá giảm và nút thêm giỏ. Số đã bán chỉ lấy dữ liệu thật; không tạo số bán trong tuần. Nút số lượng được căn đều, kiểm tra giới hạn tồn. Khách không còn thấy “Cập nhật trạng thái”; trang đơn và kết quả thanh toán tự đọc lại trạng thái từ Laravel, không tự xác nhận thanh toán.

## Chạy kiểm thử tự động

Sau khi cài dependencies frontend/backend, chuẩn bị `backend/.env.testing` theo cấu hình MySQL test trong hướng dẫn này. Chỉ dùng database `thu_thanh_test`, không dùng database cửa hàng hoặc Sandbox. Chạy:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npx playwright install chromium
npm run test:e2e
```

Lệnh đầu cài trình duyệt một lần. Lệnh test chuẩn bị sản phẩm/tài khoản kiểm thử trong MySQL test, chạy Laravel cổng 18082 và Next.js cổng 3100 trong thư mục riêng rồi đóng hai server khi xong. Nếu cổng đang có chương trình khác, lệnh dừng để tránh dùng nhầm server. Không chạy đồng thời PHPUnit và E2E vì cùng dùng database test. Giữ backend/Sandbox và frontend đang dùng của bạn ở cổng 8000/3000.

Báo cáo HTML: `output/e2e/report/index.html`. Mở bằng `npx playwright show-report ../output/e2e/report` từ frontend. Bộ test bao gồm đăng ký/tìm kiếm/giỏ/hồ sơ, checkout giao hàng và nhận tại quầy, giá/phí giao, callback thành công/hủy/chờ, chống callback lặp/sai chữ ký, phân quyền khách/admin, sản phẩm có ảnh/giảm giá/xóa/khôi phục, tồn kho, trạng thái giao hàng, trang admin và responsive. Cổng thanh toán được giả lập trong tiến trình testing riêng; không gửi giao dịch đến merchant thật, không chứng minh VNPAY đã đăng ký IPN. SMTP và tải production cần nghiệm thu riêng.

## IPN có phải đăng ký lại mỗi lần test?

Không cần tạo merchant lại. Theo email VNPAY đã gửi, bạn trả lời email đó, cung cấp URL HTTPS công khai kết thúc bằng `/api/v1/payments/vnpay/ipn`, nhờ xác nhận đã gắn đúng merchant; gửi kèm mã tham chiếu/mã giao dịch và lỗi QueryDr 99 trong mẫu hỗ trợ bên dưới. Không gửi hash secret. IPN phải truy cập trực tiếp được từ máy chủ VNPAY, không có trang đăng nhập, CAPTCHA hoặc Cloudflare email gate.

Quick Tunnel tạo hostname mới khi khởi chạy lại; khi hostname đổi bạn phải cập nhật URL IPN với VNPAY, không phải đăng ký merchant mới. Giải pháp ổn định là domain HTTPS cố định trên staging hoặc Cloudflare Tunnel có hostname cố định. Khi tắt máy cần chạy lại backend/queue/scheduler, frontend và tunnel; env/merchant/APP_KEY/database đã lưu vẫn giữ nguyên.

Cách xác minh khác là QueryDr từ server. Dự án đã có nhưng lần kiểm thật gần nhất trả 99, nên chưa có xác nhận thanh toán. Return 00 trên trình duyệt không đủ để bỏ qua xác minh server. Có thể deploy staging rồi test bằng Sandbox; thanh toán tiền thật cần merchant production và nghiệm thu riêng, không dùng khóa Sandbox. Adapter hiện chỉ cho môi trường Sandbox. Chưa deploy hay mở thanh toán thật trong lần sửa này.

Tài liệu: [VNPAY IPN và Return](https://sandbox.vnpayment.vn/apis/docs/faqs/), [Quick Tunnel](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/).

## Figma đồng bộ V27

Gói local: `output/figma/thuthanh-figma-clicon-v27-2026-10-29.zip`. Giải nén, import development plugin bằng `manifest.json`, chạy trong file thiết kế của bạn rồi kiểm tra Present. Giữ landing riêng, các flow ngoài khung bao, trái sang phải tối đa 12 màn/hàng, 4 sản phẩm/danh mục và chỉ một sản phẩm đi hết luồng mua. V27 cập nhật bảng xếp hạng và nút số lượng; bộ kiểm tra native đã chạy, chưa xác nhận canvas Figma thật trên tài khoản của bạn.
