# API contract v2 — nguồn máy đọc: openapi.json
Prefix /api/v1. Web storefront/admin do Next.js, API do Laravel. Chỉ một version hợp đồng trong gói. Sửa schema + docs + test + FE consumers cùng PR.
OpenAPI là đặc tả thiết kế đầy đủ request chính và response lõi, chưa phải file export từ source đã chạy. Callback VNPAY phải được xác thực lại từ merchant docs khi B07, không coi generic string response là mô tả đủ mã ACK provider.

## Envelope
Success: data (object/list), meta(server_time ISO UTC, request_id, pagination nếu list).
Error: error(code,message,fields theo validation), meta(server_time,request_id). Không stack/SQL.
Pagination: page >=1, per_page 1..100 default20, stable order id. Filter/sort whitelist, không nhận tên cột tùy ý.
HTTP 201 create; 200 read/update; 204 logout/delete không body; 401 unauth, 403 sai role/CSRF response riêng Laravel có thể419, 404 không tồn tại hoặc không được xem private, 409 business conflict, 422 validation, 429 throttle, 503 config/provider unavailable.
CSRF không hợp lệ: giữ419 hoặc normalize thành error contract có test; phải ghi lựa chọn triển khai. FE không retry mutation không idempotent mù.
VNPAY callback dùng ACK nguyên mẫu nhà cung cấp, không Envelope.

## Auth
GET /sanctum/csrf-cookie ngoài /api/v1; cookie credentials. Client lấy XSRF-TOKEN/ gửi X-XSRF-TOKEN theo cấu hình Sanctum, Accept JSON, origin/port allowlisted.
POST /auth/register: name, email hoặc phone, password, password_confirmation; reject role/admin fields. Email/phone được normalize server; phone chỉ hỗ trợ VN trong MVP, E.164 (+84) và chuyển 0 nội địa sau validate.
POST /auth/login: identifier và password; regenerate session sau thành công.
POST /auth/logout: invalidate session/regenerate CSRF.
GET /auth/me: current user safe fields; không password/token.
Proposed password policy min12, max128, tránh log/trim password; engineering default có test.
Không giả phone_verified_at; không gửi OTP giả. Reset email là extension, số phone-only chưa có quy trình recovery phải nêu rõ trước live.

## Route catalogue
| Method | Path sau /api/v1 | Quyền |
|---|---|---|
| GET | /categories | Public active |
| GET | /products | Public active; q/category_id/grade/section/sort/page/per_page |
| GET | /products/{product} | Public theo id, dữ liệu popup; không yêu cầu trang detail |
| GET | /home | Public sections curated, giới hạn số lượng |
| GET/POST/PATCH/DELETE | /cart, /cart/items, /cart/items/{item} | Customer owner, chỉ GET trên /cart |
| POST | /checkout/quotes | Customer; fulfillment_method, recipient, address khi giao |
| POST | /orders | Customer; quote_id, Idempotency-Key bắt buộc |
| GET | /orders, /orders/{order} | Customer owner; order = order_code |
| POST | /orders/{order}/cancel | Customer owner, còn hợp lệ |
| POST | /orders/{order}/reorder | Customer owner, copy vào giỏ theo dữ liệu mới, không tạo đơn/giữ tồn |
| POST | /orders/{order}/payment-attempts | Owner delivery còn hạn; Idempotency-Key |
| GET | /orders/{order}/payment-status | Owner; polling bounded |
| GET | /payments/vnpay/return, /payments/vnpay/ipn | Provider/public, chữ ký và validation riêng |
| GET/POST/PATCH | /admin/categories, /admin/categories/{category} | Admin |
| GET/POST/PATCH | /admin/products, /admin/products/{product} | Admin |
| POST | /admin/products/{product}/images | Admin multipart |
| DELETE | /admin/products/{product}/images/{image} | Admin, owned by product |
| GET | /admin/inventory | Admin; category_id/q/page/per_page |
| POST | /admin/inventory/{product}/adjustments | Admin; delta + reason + request_key |
| GET | /admin/orders, /admin/orders/{order} | Admin |
| PATCH | /admin/orders/{order}/fulfillment | Admin; target_status + version |
| POST | /admin/orders/{order}/payment-attempts | Admin pickup còn hạn; Idempotency-Key |
| GET | /admin/orders/{order}/payment-status | Admin |
| GET | /admin/revenue | Admin, from/to/granularity(day/week/month) |
| GET | /admin/reconciliation-cases | Admin |
| PATCH | /admin/reconciliation-cases/{case} | Admin, resolution/evidence/note/version |
| POST | /admin/orders/{order}/refund-records | Admin, verified evidence; policy-gated, không automatic refund |
Auth endpoints là /auth/register,/auth/login,/auth/logout,/auth/me theo phần trên.

## Snapshot và quote handshake
Cart response: id, items[{id,product,quantity,line_total,available_quantity,is_available}], totals estimated, warnings.
Quote input recipient yêu cầu name/phone cho cả hai cách nhận. Address có address_line, ward/locality, province; không hard-code danh mục địa giới cũ khi chưa chọn address provider. Dùng free text validated cho MVP.
Quote output id, expires_at, cart_fingerprint, pricing_version, items với snapshot, subtotal/discount/shipping/total.
Create order body chỉ quote_id. Quote đã hết hạn/cart/pricing đổi trả409 với code rõ, FE quote lại và yêu cầu xác nhận.
Idempotency replay trả200 data order cũ và meta.replayed=true; lần đầu201. Cùng key payload khác trả409.
Reorder trả cart mới/đã cập nhật và warnings nếu sản phẩm unavailable; không ghi đè giỏ đang có âm thầm. Mặc định merge quantities có cap và warnings; user confirm UI trước call.

## Payment contract
PaymentAttempt response: id, order_code, status, expires_at, payment_url nullable, qr_content nullable, provider.
qr_content chỉ có nếu merchant product cấp đúng tính năng; nếu không trả capability-unavailable, không tự chế QR từ payment URL và gọi là verified bank QR.
PaymentStatus: payment_status, latest_attempt_status, expires_at, can_retry, reconciliation_required, server_time.
Tiền provider x100 chỉ ở adapter, API mọi amount là VND nguyên.
Unknown/pending trả dữ liệu rõ, không trả paid từ return params.
ADMIN có quyền mở pickup QR, không tự sửa paid. Callback xử lý cả khi user logout.

## Validation/media
name <=255, q <=100; quantity 1..99; per_page<=100; sort whitelist price_asc/price_desc/newest.
Upload đề xuất jpeg/png/webp max5MiB, server inspect MIME/dimensions, random path; không chấp nhận SVG executable hoặc arbitrary URL fetch.
Product POST nhận opening_stock; PATCH cấm stock_reserved/stock_on_hand. Thay tồn bằng adjustment API duy nhất.
Nếu không có configured shipping/gateway, trả503 cụ thể; backend tests vẫn kiểm nhánh này.

## Doanh thu
from/to ISO date, timezone report Asia/Ho_Chi_Minh; to là ngày exclusive, UI giải thích khi chọn khoảng inclusive cần chuyển ngày kết thúc+1.
Return sales/refunds/net_sales/shipping/unallocated + series/totals. Chỉ sum accepted allocation, không sum mỗi callback.

## Implementation notes B02/B03 (2026-10-21)
Cookie auth uses explicit web middleware on first-party API, including CSRF without Origin;419 returns CSRF_TOKEN_MISMATCH envelope. Product read includes is_hot/is_bestseller; PATCH version matches catalog_version. Home includes active categories. Textbook slug sach-giao-khoa cannot be renamed. Images max8192px per dimension; deletes return204 after DB deletion and schedule retryable file cleanup after commit (worker required). Implemented subset: auth, catalog/home and admin category/product/media; other draft endpoints remain planned.

B09 proposal D27 adds unallocated_refunds to revenue totals/series; only accepted-receipt refunds reduce net_sales. Reports are policy-gated503 by default. Private refund evidence is supplied by operator as a filename under storage/app/private/evidence; endpoint verifies the file exists and never accepts an arbitrary path or transfers money.

## Current owner policy (D29–D32)
Delivery defaults25000 VND, pickup0; client totals remain prohibited. Refund writes return403 REFUNDS_NOT_SUPPORTED under no_returns_no_refunds, including when legacy env toggle is true. Historical private evidence remains admin-only. New payment attempt creation returns503 PAYMENT_INTEGRATION_DEFERRED without inserting attempts; provider fixture tests remain separate from real integration. Figma local source is available, canvas not yet visually verified.

## Frontend/import local 22/10/2026

Next.js gọi cùng origin qua rewrites, giữ cookie/CSRF; không có Next API nghiệp vụ riêng. Quote/create-order chỉ dùng totals từ Laravel và Idempotency-Key cho cùng quote. CLI catalog:import không thêm HTTP endpoint hoặc đổi schema; chỉ tạo SKU mới, opening ledger system actor nullable được schema hiện có cho phép và ảnh đã kiểm từ staging. Admin HTTP vẫn cần role/policy, không nhận actor/role/totals từ client. Catalog subcategory metadata nằm trong description, FK vẫn là6 category gốc.


## D41 — GET /admin/dashboard

Chỉ auth session roleadmin; guest401/customer403, không cấp quyền bằng query/body. Không có input query thay đổi phạm vi. Response theo Dashboard schema trong openapi.json: timezone=Asia/Ho_Chi_Minh; counts(active_products,active_categories,orders_today,awaiting_processing); daily_orders7 phần tử {date,count} tăng dần, zero-fill; recent_orders tối đa5 OrderResource có eager-loadeditems, mới nhất theo created_at,id. StoredtimestampsUTC; ranh giới ngày VN17:00UTC ngày trước. active_products loại inactive product/category; awaiting_processing là lifecycleopen + fulfillmentunprocessed (mọi trạng thái tiền). daily_orders đếm mọi trạng thái, không phải doanh thu/paid receipts. Read-only, không schema/money/provider mutation. Frontend /admin sử dụng endpoint thật; recentorder link tới /admin/orders?q=code.


## D43 — Đăng ký nhận bản tin

`POST /api/v1/newsletter/subscriptions` là public, vẫn có web/session CSRF và throttle10request/phút/IP. Body `{ "email": "reader@example.com", "consent": true }`; email normalize trim/lowercase, tối đa254 ký tự, validation email RFC; consent bắt buộc accepted; reject field ngoài whitelist.

Trả202 `{data:{message:"Đã ghi nhận đăng ký nhận bản tin."},meta:{server_time,request_id}}`, giống nhau cho email mới/đã có. Unique email + firstOrCreate đảm bảo gửi lặp chỉ một row và không sửa thời điểm đồng ý ban đầu. Không trả email/id/trạng thái membership, không tạo user/role/cart/order và không gửi email campaign.419CSRF,422validation,429throttle dùng envelope chung. Không có API public liệt kê email. `NewsletterTest` và `ApiRouteContractTest` kiểm7test37assertions; fullsuite92/1417. OpenAPI hiện40paths.


## D44 — hợp đồng hiện hành 23/10/2026

- `GET /home`: thêm `recommended`; `bestseller` chỉ sản phẩm đang bán/danh mục đang bán, xếp theo tổng quantity của đơn paid có receipt accepted, tie ID tăng dần. Có `sold_count` nguyên dương trên kết quả bán chạy. Không tạo doanh số từ cờ `is_bestseller`, đơn unpaid hay receipt duplicate/late. `new` theo created_at/ID giảm dần. Gợi ý chỉ dùng giỏ/đơn paid/accepted của chính khách, guest đa dạng danh mục và còn tồn; không có UUID giỏ guest mới.
- `DELETE /admin/products/{product}`, JSON `{ "version": 1 }`: session admin + CSRF, row lock/version; 204 khi ngừng bán hoặc đã ngừng bán, 409 khi phiên bản active đã đổi. Không hard-delete, không đổi tiền/tồn/snapshot đơn. Mở bán lại qua PATCH is_active=true với version hiện hành.
- `GET /admin/products?is_active=false`: lọc hàng ngừng bán, vẫn phân trang/tìm kiếm.
- `GET /admin/revenue?from=YYYY-MM-DD&to=YYYY-MM-DD&granularity=day|week|month`: D15 đã chốt qua D44; mặc định approved_v1, các cấu hình chưa duyệt khác vẫn 503. Cận từ bao gồm/cận đến loại trừ theo giờ Việt Nam, tối đa 366 ngày. accepted_sales là items_subtotal-discount_total; shipping_collected riêng. Ngày không phát sinh bằng 0, nhóm tuần/tháng vẫn đúng tổng. Duplicate/late/mismatch là unallocated, không cộng sales. Refund writes vẫn bị cấm; các trường ledger lịch sử giữ tương thích.
- CSV xuất từ chính series báo cáo đã lọc trên frontend, không có endpoint ghi hoặc tự tạo receipt.

Các route quản lý category/reconciliation giữ nguyên hợp đồng. Không có route đổi role hoặc admin đánh dấu paid bằng tay. `admin:account` và `admin:password` là tiện ích CLI local, không phải API.


## D45 — giá giảm và cache public

Product create/patch thêm `discount_percent` integer0..100, không nhận đồng thời sale_price khác nguồn. Backend tính sale_price = round(list_price*(100-percent)/100) bằng số nguyên;0% về null,100% về0 theo miền giá hiện có. Resource luôn trả discount_percent suy từ giá đang lưu; trường này không lưu thành nguồn tiền thứ hai. Snapshot/quote/order tiếp tục dùng giá backend.

GET `/storefront` mới, không session/auth/cookie, snapshot công khai cache20s, header public,max-age0,s-maxage20. Không nhận đầu vào cá nhân. GET `/home` giữ session và `private,no-store`, gợi ý riêng của chính khách. GET categories/products/show bỏ middleware session nhưng vẫn no-store để giá/tồn đọc trực tiếp. API khác/CSRF/auth/admin không đổi sang cache public. Meta requestid origin vẫn mỗi request riêng. Cache revision đổi sau commit; rollback không publish giá.

Không có API fake-paid/seed-sales. Demo là CLI có guard environment+DB. OpenAPI hiện41paths; private/API error tiếp tục no-store. Trusted proxy chỉ nhận CIDR cấu hình để rate limit dùng đúng IP khách.

## D51 — Khôi phục mật khẩu khách hàng

`POST /api/v1/auth/forgot-password`: JSON email chuẩn hóa; trả202 và cùng message cho email không tồn tại/admin/đã được gửi gần đây. Chỉ gửi cho customer có email; không có SMS hoặc public admin recovery. Hai endpoint dùng session+CSRF, limit10/IP/phút và5/email+IP/phút, không trả token. Production dùng mailer log/array trả503 RECOVERY_UNAVAILABLE.

`POST /api/v1/auth/reset-password`: email, token64hex, password12–128 và password_confirmation. Token hash/60phút/mộtlần bằng Laravel broker. Reset trong transaction/khóa user, tiêu thụ token và thu hồi sessions của đúng khách, đổi remember_token; không tự login. Sai/hết hạn/dùng lại trả422, cùng lỗi token; admin không được reset qua endpoint công khai. Email đưa vào encrypted queued notification sau commit; FRONTEND_URL do server cấu hình, không từ Host/request. Frontend /reset-password không referrer/noindex và xóa token khỏi URL khi thành công. Xem openapi.json (43paths).

D51 session bổ sung: login/register lưu HMAC mật khẩu vào session; Sanctum AuthenticateSession áp dụng toàn web API, gồm home cá nhân, để từ chối phiên cũ sau reset kể cả với session ngoài database. Test chạy driver array chứng minh không phụ thuộc xóa bảng sessions; Redis runtime production chưa chạy. Token không được đưa vào session/API response.
