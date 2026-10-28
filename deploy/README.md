# Triển khai và vận hành

Cấu hình tham chiếu gồm PHP-FPM 8.4, MySQL 8.4, Nginx, worker/scheduler; overlay scale thêm Next standalone, edge và Redis. **Chưa nghiệm thu Docker runtime, staging, backup/restore hoặc tải 10k trên máy chủ thật.** Máy Windows hiện chưa có Docker/k6.

## Chuẩn bị staging

1. Cài Docker Engine/Compose v2 trên hạ tầng được cấp. Copy deploy/.env.example thành deploy/.env cho Compose; deploy/.env.backend.example thành deploy/.env.backend cho Laravel. Hai file khác vai trò env local; app không dùng tài khoản root DB.
2. Đặt APP_KEY ổn định riêng, mật khẩu DB/root/Redis riêng, APP_DEBUG=false; cấu hình APP_URL, FRONTEND_URL, Sanctum, cookie và trusted proxy theo domain HTTPS thực tế. Không copy env hoặc dữ liệu demo sang production.
3. Review migration, backup nếu đã có dữ liệu, kiểm cấu hình/build rồi chạy database và migration như thao tác operator riêng. Entrypoint không tự migrate/seed.

```sh
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml config --quiet
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml build
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml up -d db
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml run --rm app php artisan migrate --force
```

Chỉ seed category/admin khi môi trường mới cần. Khởi động các service theo Compose đã review; edge loopback8081 cần TLS reverse proxy thực. Giữ MySQL/Redis/FPM khỏi Internet. Kiểm nginx-t, health, cookie/CSRF, login/role, catalog/cart/checkout, ảnh, worker và scheduler trên HTTPS; pin image digest đã nghiệm thu trước release.

VNPAY dùng merchant Sandbox riêng. RETURN_URL trỏ frontend/payment/vnpay-return; IPN HTTPS trỏ backend/api/v1/payments/vnpay/ipn. Server xác minh tiền, không có fake-paid/manual-paid. Production và QR merchant cần nghiệm thu riêng. Email recovery cần SMTP/worker thật và FRONTEND_URL đúng.

## Bảo mật và quan sát

Web root Laravel chỉ backend/public; không expose env, repo root, vendor hoặc private evidence. Cookie HttpOnly/Secure/SameSite, CSRF cho session mutation, proxy/CORS allowlist và chữ ký callback phải được kiểm. Admin middleware/policy và resource whitelist bảo vệ dữ liệu. Không cache chung private/cart/checkout/order/admin/CSRF hoặc Set-Cookie.

Theo dõi health, queue:failed, worker/scheduler, case đối soát, latency, CPU/RAM, DB slow query/deadlock/connection, Redis memory và FPM queue. Log request_id/correlation, che secret/PII/reset link và đặt retention. Khi provider không xác định được kết quả, giữ nguyên quy tắc đối soát/tồn; không mark paid/unpaid hàng loạt hoặc giải phóng tồn tùy tiện.

## Backup và cập nhật

Volumes mysql-data/backend-storage giữ database/ảnh/evidence; không dùng down-v khi cập nhật. Backup DB, uploads và private evidence; giữ APP_KEY/env private, kiểm restore trên staging với thời gian/hash/log đã che bí mật. Có lệnh backup chưa chứng minh đã restore thành công.

Quy trình: build/test → backup → deploy code/assets → migration đã review → config/route cache → restart worker → smoke. Rollback code/image phải tương thích schema; không rollback dữ liệu tiền khi chưa xem tác động. Nhiều host cần shared storage/object store đã kiểm; Docker local volume chỉ chung một host.

## Cache và nghiệm thu tải

Public /api/v1/storefront không khởi tạo session; cache20giây/lock/revision đổi sau commit. API riêng no-store. Query gợi ý được giới hạn và eager-load; quote/order luôn kiểm lại giá/tồn trong transaction. Redis/CDN/FPM/OPcache không thay row lock hoặc idempotency.

Phép đo local D45:20 đồng thời/200request, p95 khoảng601ms,0lỗi; không gồm toàn bộ ảnh/provider và không chứng minh10k. Kết quả gốc nằm trong archive cleanup24/10.10k người mở web khác10kRPS/checkout; cần đo mixed workload, DB/cache/worker và cạnh tranh SKU cuối riêng.

Profile `scripts/load/public-storefront.k6.js` mặc định20VU. Chỉ chạy trên staging được phép; profile10k cần BASE_URL, PROFILE=10k, ALLOW_HIGH_LOAD=1 và load generator đủ tài nguyên. Ngưỡng dự kiến:error<1%, checks>99%, p95<1s, p99<2s, chưa được chứng minh ở10k. Bắt đầu nhỏ, theo dõi tài nguyên và dừng khi vượt ngưỡng; không gửi thanh toán thật ở tải lớn.

[Chạy local](../docs/CHAY-WEB-VA-LARAVEL.md) · [Nghiệp vụ](../docs/DOMAIN-RULES.md) · [OpenAPI](../docs/openapi.json). `php scripts/validate-deploy.php` chỉ parse YAML, không thay Docker build/nginx runtime/production acceptance.
