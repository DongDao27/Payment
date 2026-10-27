# Chạy web, admin và demo — cập nhật24/10/2026

## Web với dữ liệu thật hiện tại

Giữ database `thu_thanh`: 136 sản phẩm, giá/tồn nguồn và tài khoản quản trị hiện có. Không import lại, không tạo APP_KEY hoặc reset database. Nếu giữ terminal cũ, Ctrl+C rồi chạy lại; trình duyệt Ctrl+F5.

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

Mở **http://localhost:3000**. Dùng hostname localhost nhất quán. Mục mới lấy 12 sản phẩm đang bán có ngày tạo/ID mới nhất; sửa giá không làm sản phẩm cũ thành mới. Bán chạy lấy tổng số lượng từ đơn đã paid có receipt accepted, không lấy giá trị tiền hoặc cờ gắn tay. Database thật chưa có paid receipt nên mục bán chạy trống và báo cáo doanh thu bằng 0.

## Mở bộ 1.000 đơn ngẫu nhiên

Đã chuẩn bị riêng database **thu_thanh_demo**, 1.000 đơn paid và 1.000 receipt provider `demo` trong khoảng 90 ngày. Có giảm giá/stock mô phỏng riêng; không thay 136 giá/tồn của database thật. Lượt kiểm thử có thể tạo thêm sản phẩm đã ngừng bán/đơn chưa trả tiền trong demo, không cộng chúng vào doanh số paid.

Nếu cần chuẩn bị lại sau khi chuyển máy đã có MySQL portable và dữ liệu thật, chạy từ gốc repo:

```powershell
php scripts/prepare-demo.php
```

Lệnh chạy lại sẽ bỏ qua 1.000 đơn cùng seed, không nhân dữ liệu. Nếu database demo tồn tại nhưng mất private env, lệnh từ chối thay mật khẩu/ghi đè. Không chạy script chuẩn bị local này trên hosting production.

Để xem demo, dừng Next.js cũ trên port 3000 trước. Hai terminal từ gốc repo:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/start-demo.ps1 -Component backend
```

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/start-demo.ps1 -Component frontend
```

Mở **http://localhost:3000**. Banner môi trường ở trên cùng cho biết đang xem dữ liệu giả lập. Laravel demo chạy port 18081; DB, session cookie, APP_KEY và storage ảnh đều riêng. OPcache được bật cho server demo để tránh chi phí biên dịch PHP lặp. Không chạy đồng thời hai Next dev trong cùng thư mục/port.

Tài khoản demo: **admin-demo@example.test**. Không có mật khẩu mặc định công khai. Chọn mật khẩu demo của bạn trong terminal:

```powershell
cd D:\ThuThanh-WebDesign\backend
php artisan admin:password --env=demo
```

Xác nhận rồi nhập mật khẩu mới hai lần, 12–128 ký tự, không hiển thị khi gõ. Lệnh này chỉ đổi admin trong database demo khi dùng `--env=demo`, không đổi tài khoản thật. Sau đó mở **http://localhost:3000/admin** để xem biểu đồ và quản lý.

Quay về web dữ liệu thật: dừng hai terminal demo và chạy hai terminal ở phần đầu. Không chép `.env.demo` đè `.env`, không đổi DB_DATABASE của dữ liệu thật.

## Vào admin dữ liệu thật

Mở **http://localhost:3000/admin**, đăng nhập tài khoản quản trị hiện có. Footer không còn link quản trị theo yêu cầu; có thể lưu bookmark URL này. Login chung, Laravel quyết định quyền; đăng ký trên web chỉ tạo khách hàng.

Nếu quên tên đăng nhập hoặc mật khẩu local:

```powershell
cd D:\ThuThanh-WebDesign\backend
php artisan admin:account
# Chỉ khi quên mật khẩu và muốn tự thay:
php artisan admin:password
```

Không có `--env=demo` thì đây là database thật local; lệnh thay mật khẩu cần bạn xác nhận. Phiên làm việc này không đổi mật khẩu admin thật. Máy hiện tại đã có một admin; không cần tạo lại bằng admin:create.

| Trang | Chức năng |
|---|---|
| `/admin` | Thống kê đơn/catalog và doanh thu tháng |
| `/admin/products` | Thêm/sửa sản phẩm, ảnh, lọc trạng thái, ngừng bán và mở bán lại |
| `/admin/categories` | Thêm/sửa, ẩn/hiện danh mục |
| `/admin/inventory` | Tăng/giảm tồn có lý do và lịch sử |
| `/admin/orders` | Chi tiết, chuẩn bị, sẵn sàng nhận/giao, hoàn tất theo điều kiện thanh toán |
| `/admin/revenue` | Tiền hàng sau giảm giá; phí giao riêng; lọc ngày/tuần/tháng, CSV |
| `/admin/reconciliation` | Ca cần xác minh, ghi nhận xử lý; đóng khi đủ bằng chứng backend |

Trong form sản phẩm: nhập **giá bìa**, chọn **Nhập phần trăm giảm giá**, nhập số nguyên 0–100. Ví dụ 35.000đ giảm 5% thành 33.250đ. Laravel tính giá bán bằng số nguyên VND, làm tròn gần nhất; không nhận đồng thời hai nguồn sale_price và discount_percent. Giá gốc gạch ngang/badge chỉ hiện khi có giảm thật. Chế độ Nhập giá bán giữ khả năng nhập giá chính xác. Thay giá không làm đổi snapshot các đơn cũ.

## Thanh toán VNPAY Sandbox

D55 mở thanh toán VNPAY **Sandbox** cho khách, áp dụng cho cả đơn giao hàng và nhận tại cửa hàng. Production và QR tại quầy chưa mở. Không dùng TMN code/hash secret lấy từ bài viết hay repo công khai; mỗi người đăng ký merchant Sandbox riêng.

1. Đăng ký tại https://sandbox.vnpayment.vn/devreg/ và lấy `vnp_TmnCode`, `vnp_HashSecret` trong email VNPAY gửi.
2. Trong `backend/.env` (không commit), điền:

```dotenv
VNPAY_ENV=sandbox
VNPAY_TMN_CODE=<mã của bạn>
VNPAY_HASH_SECRET=<secret của bạn>
VNPAY_PAYMENT_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_QUERY_URL=https://sandbox.vnpayment.vn/merchant_webapi/api/transaction
VNPAY_RETURN_URL=http://localhost:3000/payment/vnpay-return
VNPAY_DEFERRED=false
```

3. Trong `backend/` chạy `php artisan config:clear`; không cần khởi động lại Next.js.
4. Đặt hàng → **Chi tiết đơn** → **Thanh toán qua VNPAY** → trả bằng thẻ test trong tài liệu/email Sandbox của VNPAY.
5. VNPAY chuyển về `/payment/vnpay-return`. Laravel kiểm chữ ký rồi tự hỏi VNPAY (querydr); chỉ kết quả query hoặc IPN đã ký mới chuyển đơn sang **Đã thanh toán** và trừ tồn.

Thiếu TMN code/secret: nút thanh toán báo "chưa được cấu hình", không tạo giao dịch. Hủy trên trang VNPAY: đơn về chưa thanh toán và có thể trả lại khi còn hạn. Đơn giao hàng chỉ giữ 5 phút, nên hoàn tất thanh toán trong thời gian đếm ngược.

IPN: VNPAY không gọi được `localhost`. Khi có URL public (hosting hoặc tunnel), khai báo `https://<backend>/api/v1/payments/vnpay/ipn` trong trang merchant Sandbox. Nếu khách đóng tab VNPAY mà không quay về, `php artisan schedule:work` sẽ query khi đơn đến hạn; kết quả không xác định được chuyển vào **/admin/reconciliation** để đối soát.

## Scheduler và queue

Muốn kiểm tra hết hạn/đối soát nền, mở thêm hai terminal trong backend:

```powershell
php artisan schedule:work
```

```powershell
php artisan queue:work database --tries=5 --timeout=60
```

Với demo, thêm `--env=demo` cho cả hai lệnh. Ctrl+C để dừng. Không đánh giá xử lý nền nếu chưa chạy worker/scheduler.

## Laravel lưu dữ liệu ở đâu?

Trình duyệt → Next.js → Laravel API → MySQL → JSON trả lại giao diện. Laravel dùng Eloquent/migration để quản lý bảng, validation/quyền/transaction để xử lý giá, tồn, giỏ và đơn. MySQL là tiến trình database thật; migration không phải database engine.

Máy hiện tại chạy MySQL portable tại `127.0.0.1:3307`, dữ liệu trên đĩa trong `.runtime/mysql-data`. Không cần XAMPP hoặc MongoDB. Tắt trình duyệt không làm mất bản ghi đã commit. Sau reboot bật lại MySQL rồi các server. `.env` chỉ chứa cấu hình/secret; không chứa danh sách đơn hàng.

Ảnh thật ở `backend/storage/app/public`, ảnh demo ở `.runtime/demo-storage/app/public`; database lưu metadata/đường dẫn. Giữ MySQL data, private env/APP_KEY, workbook, upload/evidence khi sao lưu hoặc chuyển máy. Không sửa trực tiếp file trong mysql-data.

## Deploy và Figma

Chưa deploy, chưa nghiệm thu 10.000 truy cập đồng thời. Đã có cấu hình và load profile; xem [PERFORMANCE.md](PERFORMANCE.md) để biết kết quả đo, ngưỡng và phần cần máy chủ thật. VNPAY/QR thật vẫn hoãn; đơn mới không tự paid và không được bàn giao nếu chưa xác nhận tiền. Không đổi trả/hoàn tiền. Phí ship chỉ ở checkout/đơn: delivery 25.000đ, pickup 0đ.

Figma hiện hành V24: giải nén `output/figma/thuthanh-figma-clicon-v24-2026-10-23.zip`, import manifest ở gốc, chạy lại builder. Xem `thu-thanh-figma-clicon/HUONG-DAN.md`. Canvas không tự cập nhật và chưa được kiểm trực tiếp bằng tài khoản Figma của bạn. Không cần extension VS Code để chạy web/import plugin.

[Bản đồ source và khôi phục archive](REPOSITORY-MAP.md) · [Trạng thái kiểm thử](VERIFICATION.md).

## Bản đồ, giờ mở cửa và quên mật khẩu

Footer đã có map đúng link chủ nhà sách, hotline0915466951 và giờ07:30–21:30hằngngày. `/forgot-password` nhận email khách; `/reset-password` mở từ liên kết riêng trong email. Liên kết chỉ dùng một lần/60phút; đổi thành công đăng nhập lại, các phiên cũ bị thu hồi. Tài khoản chỉ dùng số điện thoại có hướng dẫn liên hệ nhà sách; chưa tích hợp SMS, không có OTP mặc định. Admin vẫn dùng tiện ích local riêng và không được reset công khai bằng chức năng này.

Email thật cần cấu hình riêng trong backend .env: MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_SCHEME theo nhà cung cấp, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME; FRONTEND_URL phải là URL frontend thật/HTTPS khi deploy. Không gửi mật khẩu SMTP trong chat hoặc commit env. Chạy `php artisan config:clear` ở local sau đổi env; production rebuild config cache và restart worker theo quy trình deploy. Không dùng log/array làm mailer production: API sẽ trả503 để tránh giả gửi mail. SMTP delivery chưa được nghiệm thu vì chưa có tài khoản dịch vụ.

Notification được xếp hàng mã hóa. Chạy worker database theo mục bên trên (thêm --env=demo khi dùng DBdemo), scheduler dọn token hết hạn mỗi giờ. APP_KEY/env/DB cần được giữ, không key:generate lại env đang dùng. Mailer log chỉ dành cho phát triển, nội dung riêng nằm trong log bị Git ignore; không chia sẻ log có reset link. Không chạy worker demo với SMTP thật cho những tài khoản demo ngẫu nhiên.

Admin đã tách header/menu khỏi footer mua sắm, icon theo từng chức năng, biểu đồ doanh thu dạng đường/diện tích và đơn dạng cột. Trục bắt đầu0, đọc giá trị bằng hover/touch hoặc Tab/mũi tên/Home/End; mobile cuộn ngang vùng chart khi cần. Bộ lọc7/30/90ngày, nhómngày/tuần/tháng và CSV giữ số liệu API. Không cần cài thêm thư viện biểu đồ hoặc extension.
