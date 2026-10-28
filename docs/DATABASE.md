# Database contract 2.0
Database: MySQL InnoDB, utf8mb4, UTC; timestamps datetime(6) khi có race/time logic. ID bigint unsigned. Enum dùng PHP backed enum + VARCHAR có validation/check phù hợp, tránh DB ENUM khó migrate.
Mọi bảng có created_at/updated_at nếu không phải append-only event; event có created_at và processed_at.
Money: unsigned bigint VND integer, không float. JSON API giữ dưới ngưỡng safe integer đã validate; giới hạn phát triển đề xuất 1 tỷ VND/order.
Các giới hạn là engineering defaults, xem DECISIONS.

## Bảng và trường
| Bảng | Trường nghiệp vụ |
|---|---|
| users | id, name varchar120, email nullable unique varchar254, phone nullable unique varchar20, password, role(customer/admin), email_verified_at nullable, remember_token |
| categories | id, slug unique, name varchar120, sort_order int, is_active bool |
| products | id, category_id FK restrict, sku unique, slug unique, name varchar255, description text nullable, grade tinyint nullable, list_price, sale_price nullable, stock_on_hand int, stock_reserved int default0, is_active bool, is_hot bool, is_bestseller bool, catalog_version bigint |
| product_images | id, product_id FK restrict, storage_disk, path unique, mime, byte_size, sort_order, alt_text nullable |
| carts | id, user_id unique FK users restrict; một giỏ lâu dài cho customer |
| cart_items | id, cart_id FK cascade, product_id FK restrict, quantity; unique(cart_id,product_id) |
| quotes | id UUID, user_id FK, cart_id FK, cart_fingerprint, immutable canonical payload JSON, totals, pricing_version, expires_at, consumed_at nullable |
| orders | id, order_code unique public opaque, user_id FK restrict, quote_id unique FK, fulfillment_method delivery/pickup, recipient_name/phone, address nullable JSON/text snapshot, items_subtotal, discount_total, shipping_fee, payable_total, lifecycle, payment_status, fulfillment_status, expires_at, paid_at nullable, completed_at nullable, cancelled_at nullable, version bigint |
| order_items | id, order_id FK restrict, product_id FK restrict, sku/name snapshot, list_unit_price, charged_unit_price, quantity, discount_line_total, line_total |
| stock_reservations | id, order_item_id unique FK, product_id FK, quantity, status(active/consumed/released), expires_at, released_at nullable |
| payment_attempts | id UUID, order_id FK, provider, provider_reference unique varchar100, requested_amount, status(created/pending/succeeded/failed/cancelled/expired/unknown), expires_at, provider_transaction_id nullable, query_requested_at nullable, verified_at nullable |
| payment_receipts | id, provider, provider_transaction_id, order_id, attempt_id, received_amount, provider_paid_at, verified_at, allocation(accepted/duplicate/late/mismatch), unique(provider,provider_transaction_id) |
| payment_events | id UUID, source(ipn/query/return), provider_reference nullable, fingerprint unique, signature_valid bool, safe_payload JSON, disposition, receipt_id nullable, processed_at; retention policy |
| reconciliation_cases | id, order_id FK, receipt_id nullable FK, reason, status(open/resolved), resolution nullable, resolved_by nullable FK, resolved_at nullable, note, version bigint |
| inventory_movements | id, product_id FK restrict, order_id nullable, actor_id nullable, kind, delta_on_hand signed, delta_reserved signed, resulting_on_hand/reserved, reason, business_key unique |
| order_status_histories | id, order_id, actor_id nullable, event, before/after JSON, reason, request_id |
| idempotency_keys | id, user_id FK, operation, key varchar100, request_hash, status(processing/succeeded), order_id nullable, cached_response JSON nullable, unique(user_id,operation,key) |
| refunds | id UUID, order_id FK, receipt_id FK, provider, external_reference, amount, merchandise_amount, shipping_amount, refunded_at, verified_by FK, evidence_path private, reason, unique(provider,external_reference) |
| settings | key unique, value JSON, version; chỉ admin/CLI cập nhật, không public secrets |
| framework | sessions, cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens theo nhu cầu và scaffold; không tạo migration trùng |

Refund table chỉ ghi bằng chứng hoàn tiền đã thực hiện/kiểm tra thủ công, không khởi tạo giao dịch hoàn tiền tự động. Nếu policy refund chưa được chốt: route write chưa bật ngoài test, B09 report vẫn phải test adjustment qua fixture.
Quote là báo giá ngắn hạn, chưa giữ tồn. expires_at quote mặc định đề xuất 5 phút; không thay expires_at đơn.
Ở order API dùng order_code thay raw id, nhưng vẫn bắt buộc ownership.

## Constraints
- users phải có email hoặc phone; chuẩn hóa trước unique: email lowercase/trim, phone Việt Nam canonical E.164 có validation và quy tắc trong API.
- stock_on_hand >= 0, stock_reserved >= 0, stock_reserved <= stock_on_hand. Đây là tồn sổ sách CHƯA bán, không phải chứng minh số thực đếm tại kệ. Sau trả tiền chuyển thành sold dù chưa giao; snapshot physical count là nghiệp vụ khác.
- quantity 1..99 (engineering default); totals không âm; sale_price nullable hoặc 0..list_price.
- grade nullable hoặc 1..12, chỉ hợp lệ khi thuộc sách giáo khoa.
- delivery yêu cầu address và shipping_fee được tính server; pickup address null, shipping_fee = 0.
- payable_total = items_subtotal - discount_total + shipping_fee; line_total = charged_unit_price * quantity. Có assert/test hoặc CHECK trong migration phù hợp; không dựa vào FE.
- user/cart/product FK không được làm mất snapshot/payment history; product dùng is_active/soft delete nếu cần, không hard-delete đang có order.
- unique accepted allocation per order: đề xuất generated nullable column accepted_order_id = IF(allocation='accepted',order_id,NULL) + unique index; mọi receipt khác không cấp phát đơn lần nữa.
- active attempt mỗi order được serialize bằng khóa order và check states; không chỉ kiểm bằng client. Provider reference unique là bắt buộc.
- refund tổng verified <= receipt received_amount; không restock tự động khi refund.

## Index
products(category_id,is_active,id), products(is_hot,is_active), products(is_bestseller,is_active); search name có thể LIKE cho MVP, đánh giá query plan trước fulltext.
orders(user_id,created_at,id), orders(lifecycle,expires_at), orders(fulfillment_method,fulfillment_status,created_at).
attempts(order_id,status), attempts(status,expires_at); reservations(status,expires_at); receipts(order_id,allocation,verified_at).
inventory_movements(product_id,created_at,id), histories(order_id,created_at), reconciliation(status,created_at), quotes(user_id,expires_at).
Pagination whitelist, stable tie-break id; không kéo toàn bộ hàng nghìn bản ghi vào RAM để paginate.

## Migration/seed
B01 tạo users/auth/framework → categories/products/images/cart → quotes/orders/items/reservations → payments/events/receipts/reconciliation → movements/history/idempotency/settings/refunds.
Schema change sau merge dùng migration mới. Rollback có dữ liệu không mặc định an toàn; review riêng.
Seed sáu danh mục idempotent theo slug; demo products riêng, tạo có stock. Admin CLI riêng, không tự tạo admin mỗi lần db:seed.
Production không chạy DatabaseSeeder chứa Faker. Test factory tách dataset và không gửi email/provider thật.


D41: không thêm/sửa schema. Dashboard aggregate đọc products/categories/orders/order_items; ngày báo cáo Asia/Ho_Chi_Minh, timestamps và connection vẫn UTC. MySQL engine portable local3307, persistentfiles .runtime/mysql-data; Laravel/Eloquent không thay database engine. Hướng dẫn giải thích ở CHAY-WEB-VA-LARAVEL.md.


## D43 — newsletter_subscriptions

Migration `2026_10_23_023306_create_newsletter_subscriptions_table.php` chỉ thêm bảng mới: id BIGINT PK,email VARCHAR(254) UNIQUE,consented_at TIMESTAMP,created_at/updated_at. Eloquent NewsletterSubscription fillable email/consented_at, hidden email khi serialize; casts immutable_datetime. Không FK hoặc thay bảng user/product/stock/order/payment. API chuẩn hóa email; unique key xử lý gửi lặp, consent ban đầu giữ nguyên. Không lưu password/provider/token và không tự gửi thư.

Local migration đã áp dụng riêng bằng --path sau migrate --pretend và targeted test. Trước/sau:136products6categories1admin,136giá/tồn khớp workbook. Không reset/import development. Bảng mới có dữ liệu đăng ký do người dùng submit; QA ghi vào thu_thanh_test riêng.


## D44 — không có migration mới

Archive sản phẩm dùng products.is_active=false và tăng catalog_version dưới row lock; giữ FK/order_items snapshots/inventory và ảnh. Bestseller tổng order_items.quantity theo orders.payment_status=paid và EXISTS payment_receipts.allocation=accepted; EXISTS tránh nhân số lượng do receipt khác. Revenue dùng snapshot đơn sau giảm giá và paid_at UTC, chuyển ranh giới ngày Việt Nam; shipping riêng. Chỉ cập nhật policy config đã được chủ dự án chốt. Không nhập lại hoặc thay 136 giá/tồn/admin trong database phát triển. Các dòng paid/receipt/case tạo trong QA chỉ ở thu_thanh_test, không phải giao dịch thật.


## D45 — dữ liệu demo tách riêng, không migration dữ liệu thật

Không thêm cột discount_percent: chỉ persist list_price/sale_price/catalog_version. MySQL8 window function giới hạn ứng viên gợi ý mỗi category. Không thêm index chỉ vì đoán hiệu năng; workload hiện1korders/136SKU đã có FK/index nguồn, public snapshot được cache.

`thu_thanh_demo` được tạo local riêng với user DB/private env/APP_KEY/cookie/storage riêng; clone read-only catalog từ thu_thanh, không clone khách/đơn thật. Seed1000paidorders/accepted demo receipts và stock movements qua reserve/consume, totals/quantity đúng, business keys chống trùng. Tạo giá giảm/tồn mô phỏng chỉ trong demo. Dùng provider=demo, không fake chữ ký VNPAY. Prepare-demo/seed từ chối production và DB thật; chạy lại cùngseed bỏ qua1000đơn. Các bản ghi QA local không thuộc doanh số thực.

Production overlay chuyển cache/session/queue sang Redis chia sẻ; MySQL vẫn quyết định tiền/tồn và unique/locks. Cache invalidation sau commit, không giữ global cache-row lock trong transaction tồn kho. Xem deploy/README.md cho phần chưa đo trên production.

## D51 — Password reset, không thêm migration

Dùng bảng password_reset_tokens đã có từ migration users: email là khóa, token lưu hash, created_at dùng hết hạn60phút. Không thêm cột mãOTP hoặc lưu token plain. Reset khóa users tương ứng trong transaction, cập nhật passwordhash/remember_token, xóa token và sessions theo user_id. Notification queue payload mã hóa; worker và APP_KEY phải giữ ổn định khi triển khai. Scheduler auth:clear-resets dọn token hết hạn mỗi giờ. Không đổi schema/giá/tồn/order/payment trong lần này.

D51 session bổ sung: login/register lưu HMAC mật khẩu vào session; Sanctum AuthenticateSession áp dụng toàn web API, gồm home cá nhân, để từ chối phiên cũ sau reset kể cả với session ngoài database. Test chạy driver array chứng minh không phụ thuộc xóa bảng sessions; Redis runtime production chưa chạy. Token không được đưa vào session/API response.
