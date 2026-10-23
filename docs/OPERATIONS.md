# Local, CI và triển khai
## Local backend trước
Repo nhà sách riêng với coffee-shop. Root đọc AGENTS/checkpoint, kiểm source.
Cần PHP compatible, Composer, MySQL local, extension pdo_mysql, Node chỉ khi bắt đầu FE; kiểm php -m, composer check-platform-reqs.
Tạo thu_thanh và thu_thanh_test với user riêng. Cấu hình backend/.env từ template và secret thật ở local. Không mặc định root/blank password.
Cài composer install theo lock; key:generate chỉ nếu APP_KEY chưa có; migrate; categories seeder; admin:create nhập tương tác hoặc env bí mật.
Admin command idempotent, không tự reset password admin đang có. Kiểm unique một admin ở flow bootstrap.

## Lệnh kiểm chứng dự kiến (AI tạo script tương ứng khi bootstrap)
Trong backend: composer validate --strict; composer check-platform-reqs; ./vendor/bin/pint --test; php artisan test; php artisan route:list --path=api/v1; composer audit.
PowerShell Pint có thể dùng .\vendor\bin\pint --test.
Integration suite trên MySQL thật riêng, ít nhất hai connection/process độc lập cho concurrency. Không coi test SQLite hoặc hai request tuần tự là race test.
API docs: OpenAPI draft trong docs, B10 phải cập nhật từ source thực và validate. API smoke dùng session cookie+CSRF.
CI chạy lint/test MySQL, không cần Figma/merchant secrets cho unit/domain; integration provider ở môi trường sandbox riêng được bảo vệ.
Thiếu dependency/tool: log blocked, không bịa pass hoặc skip rồi count as pass.

## Runtime
Laravel local: php artisan serve; scheduler local: php artisan schedule:work; queue: php artisan queue:work nếu có async jobs.
ExpireOrders chạy lịch ngắn theo yêu cầu 5 phút, mặc định scan mỗi phút + check ngay tại endpoints. Exact deadline enforcement ở backend request, không chờ tick.
ReconcilePayments có bounded retry/backoff, alert/case cho uncertain. Không release uncertain stock bằng timeout tùy tiện.
Use database queue mặc định cho MVP; Redis không bắt buộc nếu không có nhu cầu đo được. Notifications afterCommit, không ảnh hưởng transaction tiền.
Storage ảnh public approved qua storage:link nếu dùng local disk; evidence/refund private disk riêng.

## Cấu hình đề xuất
backend .env chứa DB/session/cache/queue/mail/VNPAY; config/store.php hold/quote/qty/shipping/revenue policy. Secrets không ghi settings public.
Không expose .env, vendor hoặc root repo qua web server; public root Laravel là backend/public.
Frontend môi trường có public API base và internal server API allowlisted; public vars không secret.
Local đồng nhất host, allowlist ports, session/CSRF domain; production same origin reverse proxy đơn giản.
Log structured request_id, order_code, attempt reference, event disposition; redact PII/secret, correlation qua task/test.

## Deploy (chỉ sau yêu cầu triển khai)
Staging trước, production sau approval cụ thể. Single VPS Nginx + Node Next + PHP-FPM Laravel + MySQL, ảnh/evidence bền vững. Không publish MySQL.
Route /api/v1, /sanctum, /up tới Laravel; /admin và storefront tới Next. VNPAY callbacks public HTTPS không auth cookie; signature là validation riêng.
Build/test → backup → deploy code+assets → install production deps → migrations reviewed → optimize/cache phù hợp → restart workers/processes → smoke.
Queue/scheduler là service thật khi user đóng browser. Callback có health monitoring; overdue unknown cases cảnh báo để người bán xử lý.
Tắt debug, Telescope local only, log retention được cấu hình. CSP/headers áp dụng sau test payment redirect/assets.
Backup database+uploads/private evidence, test restore staging và ghi ngày/checksum/commands sanitized. Không chỉ có backup command rồi gọi đã verify.
Rollback: phiên bản code trước, tương thích schema, pause workers khi cần; không migrate:rollback dữ liệu tiền chưa đánh giá.
Runbook outage: kiểm health/API/DB/provider, giữ dữ liệu, tạm chặn checkout/payment nếu không xác minh được; không mark đơn unpaid/paid hàng loạt.

## Definition of release
B10 và FE gates đạt, provider sandbox thật đã verify, secrets/production quyền đủ, shipping/report/refund policy được duyệt, user testing thật có bằng chứng, backup restore đạt.
Không hứa zero-bug, uptime hoặc độ trễ khi chưa có số đo.
