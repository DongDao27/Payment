# Đối chiếu yêu cầu — snapshot21/10/2026

**Hiện trạng22/10/2026:** đã nhập136 sản phẩm/ảnh và triển khai Next.js local theo D34/D35. Các nhận xét “chưa có UI” bên dưới thuộc thời điểm audit cũ; xem VERIFICATION.md và HANDOFF.md cho source/kiểm chứng mới nhất. VNPAY/report/deploy vẫn deferred/chưa đạt, không yêu cầu nhập lại thiết kế cloud đã được chủ dự án chọn dùng plugin local.

Đã đối chiếu PRD, domain/API, source backend, kiểm thử MySQL và source plugin Figma trong repo. Backend local có kiểm chứng; chưa hoàn thành toàn bộ website hoặc đủ điều kiện deploy production. Quyết định mới nhất: D29–D32 trong DECISIONS.md.

| Phạm vi | Bằng chứng và kết luận |
|---|---|
| F01–F04, F18–F20: catalog, tìm kiếm, admin sản phẩm/ảnh | Có API và CatalogTest, CatalogContractTest, MediaTest; chưa có UI. Sửa thêm bộ lọc admin `is_active=true/false` đúng hợp đồng. |
| F06–F07: tài khoản/quyền | Có AuthTest, owner/admin policies và kiểm tra HTTP cookie/CSRF thật; một hệ thống đăng nhập chung, không tự đăng ký admin. |
| F05, F08–F10, F12: giỏ, quote, tạo đơn | Có CartQuoteTest, CheckoutTest và MySQL concurrency. Server tính giá/tồn/phí; quote đổi giá phải xác nhận lại; không lấy tổng tiền từ client. |
| Phí giao | Mặc định 25.000đ/đơn giao hàng, pickup 0đ. Config bỏ trống dùng mặc định; config sai trả lỗi. OwnerPolicyTest kiểm tra quote, order snapshot và thay đổi phí sau quote. |
| Đổi trả/hoàn tiền | Không hỗ trợ. Ghi refund mới trả 403 `REFUNDS_NOT_SUPPORTED`, kể cả bật biến legacy. Không xóa lịch sử/evidence hoặc cơ chế đối soát tiền bất thường. Hủy đơn chưa trả tiền vẫn theo quy tắc đơn. |
| F11, F13, phần thanh toán F16 | Theo yêu cầu để sau: `VNPAY_DEFERRED=true`, tạo payment attempt mới trả 503 `PAYMENT_INTEGRATION_DEFERRED`; không tạo thanh toán giả, không tự paid. Adapter/callback/query có fixture tests; Sandbox và pickup QR thật chưa nghiệm thu. |
| F14–F16, F22: đơn, hết hạn, fulfillment | Có CheckoutTest, OperationsTest, PaymentExpiryConcurrencyTest; worker/scheduler smoke. Nhận hàng vẫn phải thỏa điều kiện thanh toán; việc hoãn VNPAY không tạo kênh COD mới. |
| F21: tồn kho | Có ledger/transaction/row lock, InventoryTest và kiểm thử tranh chấp MySQL; không âm tồn hoặc giữ tồn hai lần. |
| F17: doanh thu | Có test fixture/ngày Việt Nam; report vẫn khóa do công thức doanh thu D15/D27 chưa được chốt. Không coi việc chốt phí/không hoàn tiền là duyệt toàn bộ công thức. |
| Giao diện | Chưa có Next.js. Đã tìm thấy source prototype Figma, chi tiết tại FIGMA-HANDOFF.md. Chưa render hoặc so khớp canvas thật, chưa E2E/visual QA. |
| Deploy | Có template và runbook; Docker build, staging, backup/restore, GitHub CI remote và production chưa chạy. |

## Kết quả kiểm tra trong phiên này

- PHP 8.4.25, Laravel 12.70.0, MySQL 8.4.13; database test riêng `thu_thanh_test`.
- `php artisan test --log-junit=storage/logs/phpunit.xml`: **81 tests, 1.329 assertions passed**, 17,75 giây. Bao gồm 6 test chính sách mới, route parity, schema response và concurrency.
- `composer validate --strict` và `composer check-platform-reqs`: passed. Pint đã sửa định dạng và import thừa ở test mới.
- `node --check thu-thanh-store-v12-free/code.js`: passed; chỉ kiểm cú pháp, không chứng minh plugin render đúng.

## Extension và công cụ

Máy đã có các extension PHP cần cho backend hiện tại: `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `openssl`, `dom`, `xml` và các extension nền tảng; Composer xác nhận yêu cầu platform đạt. Không cần cài thêm extension PHP bắt buộc cho source hiện tại.

VS Code đã có Laravel, Tailwind CSS IntelliSense, PHPUnit và PHP Debug. Đây là công cụ hỗ trợ soạn code. PHP Debug không có nghĩa PHP đã nạp Xdebug; Xdebug chỉ cần khi muốn debug từng bước, không bắt buộc chạy ứng dụng. Không cần cài thêm extension VS Code để tiếp tục backend.

Figma Desktop dùng để import `thu-thanh-store-v12-free/manifest.json` nếu muốn mở prototype local; xem HUONG-DAN.md trong thư mục đó. Máy chưa có lệnh Docker; chỉ cần bổ sung khi chọn kiểm thử staging theo template Docker. Không cần Docker để chạy MySQL/PHP local hiện có.

## Việc tiếp theo

1. Xem prototype trong Figma và chốt các sai khác đã liệt kê ở FIGMA-HANDOFF.md; chuẩn bị ảnh sách/banner thật. Không áp giới hạn demo chỉ mua Toán 1/quantity 1–2 vào ứng dụng.
2. Cấp admin bằng `php artisan admin:create` trong backend, rồi nhập sản phẩm/giá/tồn thật qua API khi cần; không dùng tài khoản hoặc mật khẩu mẫu.
3. Chốt công thức doanh thu còn mở. VNPAY được hoãn theo yêu cầu, chưa cần merchant credentials lúc này.
4. Khi chuyển sang giai đoạn UI cần xử lý phạm vi gate: BACKEND_VERIFIED nghiêm ngặt hiện vẫn chưa đạt do payment thật được hoãn và policy còn mở. Phiên này không xây UI hoặc tự cho gate passed.
5. Trước deploy: chọn hosting/domain, kiểm staging/HTTPS/backup restore; tích hợp thanh toán thật trong giai đoạn riêng. Chưa đủ điều kiện nhận tiền thật.
