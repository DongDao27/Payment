# Backend staging template

Đã chuẩn bị source cho PHP-FPM8.4, Nginx, MySQL8.4, database worker và scheduler. Máy Windows hiện chưa có Docker: **chưa chạy build hoặc deploy container**, chưa kiểm backup/restore. Đây là cấu hình staging cần kiểm chứng, chưa phải bằng chứng sẵn sàng production. Frontend chưa được xây vì gate backend còn chặn bởi merchant/QR/policies.

Chạy tại gốc repo trên máy staging có Docker Engine + Compose v2. Cấu hình này chỉ mở HTTP trên loopback8080; MySQL/FPM không publish cổng host.

1. Copy `deploy/.env.example` thành `deploy/.env`, đặt hai mật khẩu ngẫu nhiên khác nhau cho ứng dụng và root MySQL. File này chỉ dùng Compose, không cấp root password vào app.
2. Copy `deploy/.env.backend.example` thành `deploy/.env.backend`; tạo APP_KEY riêng cho staging, không lấy khóa của local hoặc môi trường khác. Giữ lại khóa khi redeploy. Điền URL/cookies và merchant Sandbox của staging nếu được cấp. Không bật chính sách thu tiền/report/refund khi chưa được duyệt.
3. Review migrations, chuẩn bị backup nếu database đã có dữ liệu, rồi chạy:

```sh
docker compose --env-file deploy/.env -f deploy/compose.yml build
docker compose --env-file deploy/.env -f deploy/compose.yml up -d db
docker compose --env-file deploy/.env -f deploy/compose.yml run --rm app php artisan migrate --force
docker compose --env-file deploy/.env -f deploy/compose.yml run --rm app php artisan db:seed --force
docker compose --env-file deploy/.env -f deploy/compose.yml run --rm app php artisan admin:create
docker compose --env-file deploy/.env -f deploy/compose.yml up -d app web worker scheduler
curl --fail http://localhost:8080/up
curl --fail http://localhost:8080/api/v1/categories
```

`db:seed` chỉ tạo danh mục idempotent. `admin:create` hỏi bí mật tương tác và không đổi mật khẩu admin đã tồn tại. Có thể tạo APP_KEY bằng PHP local `php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'`, rồi nhập trực tiếp vào file env riêng; không gửi khóa qua chat/log.

Trước khi mở staging ra Internet: gắn domain/HTTPS qua reverse proxy, đặt APP_URL/FRONTEND_URL, SESSION_SECURE_COOKIE=true và cấu hình trusted proxy đúng hạ tầng; thử cookie/CSRF, upload, callback thực tế. PAY callback dùng `/api/v1/payments/vnpay/ipn` và `/return`; QR tại quầy cần hợp đồng/adapter merchant riêng. Không đưa khóa/DB ra public.

Data nằm trong volumes `mysql-data` và `backend-storage`, gồm ảnh và evidence private. Giữ volumes khi cập nhật, không dùng `down -v`. Xác minh quy trình backup/restore trên staging trước release. Theo dõi `queue:failed`, log và case đối soát, đặc biệt khi provider không trả trạng thái chắc chắn.

Production cần BACKEND_VERIFIED, frontend theo Figma, Sandbox thật, chính sách được duyệt, hosting/domain, test restore và yêu cầu triển khai riêng theo [OPERATIONS](../docs/OPERATIONS.md). Pin image digest của bản đã kiểm trên máy triển khai trước release.

Nguồn cấu hình: [PHP official image](https://hub.docker.com/_/php), [Compose startup order](https://docs.docker.com/compose/how-tos/startup-order/).


## D45 — cấu hình scale tham chiếu

Đọc [PERFORMANCE.md](../docs/PERFORMANCE.md) trước khi dùng overlay compose.scale.yml. Có frontendstandalone,edgecache,Redis,FPM/OPcache vàtrustedproxy,nhưng máy hiện tại chưaDockerbuild/nginx-t/k6production. EntryPointcacheconfig/routestrongtừngcontainer; khôngautomigrate/seed. CầnHTTPS/secrets/domain/sharedstorage/backuprestore vàloadtesthạ tầng trướcnghiệmthu10k. KhôngcopydemoDB/env. Publicsnapshotmớiđượcedgecache,privateAPIsvàHTML/RSCkhôngcachechung.
