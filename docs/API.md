# API Nhà sách Thu Thanh

Prefix **/api/v1**, đặc tả máy đọc ở [openapi.json](openapi.json). ApiRouteContractTest đối chiếu route/method thực tế; các test resource/validation/quyền kiểm hành vi. Kết quả này không thay nghiệm thu merchant VNPAY thật. Giao diện khách/admin do Next.js, nghiệp vụ và dữ liệu do Laravel.

## Quy ước chung

- Thành công: `data`, `meta` gồm server_time UTC, request_id; danh sách có meta.pagination(page,per_page,total,last_page).
- Lỗi: `error` gồm code/message/fields và meta; không lộ stack, SQL hoặc secret. 401 chưa login,403 sai quyền,404 không tồn tại/không được xem,409 xung đột nghiệp vụ,419 CSRF,422 validation,429 throttle,503 thiếu cấu hình/provider.
- Phân trang page≥1, per_page theo giới hạn từng endpoint; filter/sort whitelist, thứ tự ổn định. Lịch sử admin tối đa50/mặc định15; frontend lấy10 để đọc gọn trên mobile.
- Session cookie và CSRF cho first-party; không bearer token trong localStorage. Mutation không tự retry khi lỗi mạng mơ hồ. Idempotency-Key bắt buộc cho tạo đơn/payment attempt; payload khác cùng key trả409.
- VND là số nguyên trong API; chỉ adapter VNPAY đổi sang đơn vịx100. API riêng private/no-store; IPN dùng ACK của VNPAY, không envelope thường.

## Auth

GET /sanctum/csrf-cookie nằm ngoài prefix; browser nhận XSRF-TOKEN, gửi X-XSRF-TOKEN và cookie. Giữ cùng hostname và port allowlist.

| Method | Endpoint | Ghi chú |
|---|---|---|
| POST | /auth/register | name,email hoặc phone,password,password_confirmation; cấm role/admin fields |
| POST | /auth/login | identifier/password; regenerate session |
| POST | /auth/logout | Hủy session và tái tạo CSRF |
| GET | /auth/me | Chỉ trường user an toàn |
| POST | /auth/forgot-password | Generic202; email khách, CSRF/throttle, không lộ tài khoản tồn tại |
| POST | /auth/reset-password | Token hash/hết hạn/một lần; đổi mật khẩu và thu hồi phiên cũ |

Mật khẩu12–128ký tự, không trim/log; email/phone normalize server, phoneVN vềE.164. Recovery email đã có local, cần SMTP thật/worker khi mở dùng thật. Phone-only chưa có SMS; admin không dùng public reset.

## Catalog, giỏ và đơn

| Method | Endpoint | Quyền và dữ liệu |
|---|---|---|
| GET | /categories | Public, danh mục active |
| GET | /products, /products/{product} | Public; lọc/tìm/sort/page, dữ liệu xem nhanh |
| GET | /storefront | Snapshot công khai, không session/Set-Cookie, cache20giây |
| GET | /home | Feed có thể cá nhân hóa; không cache chung |
| GET | /cart | Customer, giỏ của mình |
| POST/PATCH/DELETE | /cart/items, /cart/items/{item} | Thêm/cập nhật/xóa; thêm giỏ chưa giữ tồn |
| POST | /checkout/quotes | Customer; cách nhận,tên,phone,địa chỉ nếu giao |
| POST | /orders | quote_id và Idempotency-Key; lần đầu201,replay200/meta.replayed |
| GET | /orders, /orders/{order} | Customer owner; order là order_code |
| POST | /orders/{order}/cancel | Chỉ đơn chưa thanh toán và đủ điều kiện |
| POST | /orders/{order}/reorder | Kiểm giá/tồn mới, cập nhật giỏ; không tự tạo đơn |

Quote trả snapshot giá,cart_fingerprint,pricing_version,expires_at và totals. Checkout khóa/kiểm lại dữ liệu; giá/giỏ đổi hoặc hết hạn trả409 để khách xác nhận lại. Delivery25.000đ,pickup0đ; hàng/giá/giảm/ship do server quyết định. Tạo đơn và giữ tồn cùng transaction; clear giỏ sau tạo thành công. Không đổi trả/hoàn tiền.

## Thanh toán VNPAY

| Method | Endpoint | Hành vi |
|---|---|---|
| POST | /orders/{order}/payment-attempts | Customer owner; delivery/pickup còn hạn; Idempotency-Key |
| GET | /orders/{order}/payment-status | Owner; trạng thái server, can_retry và reconciliation_required |
| GET | /payments/vnpay/return | Public nhưng kiểm chữ ký, throttle; trả kết quả xác minh an toàn |
| GET | /payments/vnpay/ipn | Callback có chữ ký/merchant/reference/amount, ACK nhà cung cấp |

PaymentAttempt trả id,order_code,status,expires_at,payment_url,qr_content,provider. Chưa có adapter QR merchant nên qr_content=null và yêu cầu QR thực trả503. Không tạo QR từ payment_url rồi gọi là bank QR đã xác minh.

Return frontend tại `/payment/vnpay-return` gửi vnp_* về API. Chữ ký sai422; signed success chỉ kích hoạt query server→VNPAY ngoài khóa DB. Chỉ query/IPN đã xác minh mới phân bổ receipt/paid và trừ tồn một lần. Query chưa rõ giữ pending; signed failure cho retry khi còn hạn. Reload/retry không gia hạn. Duplicate/late/mismatch không ghi nhận doanh số hoặc trừ tồn hai lần; các ca cần đối soát giữ luật backend.

D55 cho phép Sandbox trong env private với merchant của chủ dự án, code/env mẫu vẫn deferred mặc định. Chủ dự án xác nhận chưa có giao dịch merchant thực tế. Production, QR tại quầy và IPN public vẫn cần cấu hình/nghiệm thu riêng; xem [hướng dẫn](CHAY-WEB-VA-LARAVEL.md#thanh-toán-vnpay-sandbox).

## Admin

Mọi endpoint /admin yêu cầu auth + role admin; khách/guest không đọc được lịch sử.

| Method | Endpoint | Dữ liệu |
|---|---|---|
| GET/POST/PATCH | /admin/categories, /admin/categories/{category} | Danh mục |
| GET/POST/PATCH/DELETE | /admin/products, /admin/products/{product} | Sản phẩm; DELETE archive, PATCH restore, giữ snapshot cũ |
| POST/DELETE | /admin/products/{product}/images, /admin/products/{product}/images/{image} | Ảnh đúng product, validate upload |
| GET | /admin/inventory | Lọc category/q/page; giá bìa/giá bán, tồn vật lý/giữ/có thể bán |
| POST | /admin/inventory/{product}/adjustments | delta,reason,request_key; ledger/idempotency |
| GET | /admin/inventory/{product}/movements | Ledger đúng product, phân trang, actor/order eager-load |
| GET | /admin/orders, /admin/orders/{order} | Tìm/lọc/chi tiết đơn |
| PATCH | /admin/orders/{order}/fulfillment | target_status/version; chặn sai luồng/unpaid |
| GET | /admin/orders/{order}/history | type=attempts/receipts/statuses; mỗi loại phân trang đầy đủ |
| POST/GET | /admin/orders/{order}/payment-attempts, /admin/orders/{order}/payment-status | Admin QR vẫn chặn khi chưa có adapter merchant |
| GET | /admin/dashboard, /admin/revenue | Tổng quan/doanh thu sau giảm; ship riêng, from/to/granularity/CSV |
| GET/PATCH | /admin/reconciliation-cases, /admin/reconciliation-cases/{case} | Đối soát, version/evidence; không manual-paid |
| POST | /admin/orders/{order}/refund-records | Cấm ghi mới theo chính sách không hoàn tiền, trả403 |

History chỉ trả trường whitelist: hoạt động/trạng thái,số tiền,mốc giờ,mã giao dịch hoặc transition. Không trả payload/chữ ký/payment URL/provider_reference/session/business_key. Mỗi truy vấn giới hạn số bản ghi và sắp created_at/iddesc; API lịch sử không thay schema hoặc ghi dữ liệu.
