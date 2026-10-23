# Hiệu năng và mục tiêu 10.000 truy cập đồng thời — D45

## Trạng thái nghiệm thu

Đã tối ưu và kiểm thử source/local. **Chưa xác nhận chịu được 10.000 người đồng thời trên production.** Chưa được cung cấp máy chủ/URL staging và máy hiện tại chưa có Docker/k6. Không có số CPU/RAM production hoặc kết quả test Linux/PHP-FPM/Redis/Nginx thật để làm cam kết.

10.000 người cùng mở website khác 10.000 request/giây hoặc 10.000 checkout đồng thời. Profile đã chuẩn bị là người dùng đọc home, có thời gian nghỉ 3 giây giữa các lượt. Tải tìm kiếm, đăng nhập và checkout phải nghiệm thu riêng; cache công khai không giải quyết tranh chấp tồn kho cùng một SKU.

## Thay đổi đã thực hiện

- `GET /api/v1/storefront` không khởi tạo session, không đọc tài khoản, không trả Set-Cookie. Cache snapshot công khai 20 giây, lock chống nhiều lượt cùng dựng cache, revision đổi sau commit; một reader cũ không thể ghi đè key hiện hành. Meta request ID được tạo riêng mỗi request tại origin.
- Giỏ/checkout/order/admin/home cá nhân giữ `private, no-store`. Không đưa gợi ý riêng của khách vào cache chung. Giá/tồn hiển thị có thể trễ trong cửa sổ cache; tạo quote/order luôn kiểm lại từ MySQL và catalog version.
- Gợi ý dùng `ROW_NUMBER()` để chỉ hydrate tối đa 12 ứng viên mỗi danh mục, eager-load quan hệ; không tải toàn bộ catalog vào PHP. Test 150 sản phẩm/3 danh mục chỉ hydrate tối đa 48 sản phẩm cho toàn snapshot.
- Khách chưa có cookie XSRF không gọi `/auth/me` để tạo session không cần thiết. Home/header gộp lượt đọc public cùng lúc. Cookie chỉ là gợi ý có thể có phiên; backend vẫn quyết định quyền.
- Next production có standalone output, static assets cache một ngày; Next image cache tối thiểu một ngày. File ảnh admin có tên riêng và giữ đường dẫn mới khi thay ảnh. Không cache trang HTML/RSC bằng quy tắc chung tại Nginx.
- Cấu hình Redis dùng chung cho cache/session/queue; PHP-FPM/OPcache; Nginx giữ kết nối upstream, DNS theo replica, cache public snapshot và bỏ qua khi có cookie/Authorization/Set-Cookie. Request auth không bị cache.
- Trusted proxy dùng CIDR cấu hình, mặc định không tin X-Forwarded-For. Docker mẫu dùng mạng riêng `172.28.0.0/16`, phải đổi theo hạ tầng thực; không dùng wildcard `*`. Điều này tránh gom mọi khách sau proxy vào cùng IP và sai rate limit.

Nguồn đối chiếu: [Laravel 12 deployment](https://laravel.com/docs/12.x/deployment), [Next.js self-hosting](https://nextjs.org/docs/app/guides/self-hosting), [Nginx proxy cache/lock](https://nginx.org/en/docs/http/ngx_http_proxy_module.html), [Nginx upstream DNS/keepalive](https://nginx.org/en/docs/http/ngx_http_upstream_module.html). Extension Redis 6.2.0 được pin trong Dockerfile; bản phát hành có PHP 8.4 trong CI theo [release notes chính thức](https://pecl.php.net/package/redis/6.2.0). Docker image này chưa được build trên máy hiện tại.

## Đo thực tế local

Nguồn: `docs/qa/DUI09-load-smoke-baseline.json` và `DUI09-load-smoke.json`. Cùng endpoint `/api/v1/storefront`, database demo khoảng 1.000 đơn, warm-up riêng, 20 request đồng thời, 200 request đo. Máy Windows, PHP 8.4 development server một worker, cache database; không phải Redis/FPM/Nginx/CDN production.

| Chỉ số | Chưa bật extension OPcache | Đã bật OPcache CLI |
|---|---:|---:|
| Lỗi HTTP | 0/200 | 0/200 |
| Set-Cookie public | 0 | 0 |
| Throughput | 5,45 request/s | 35,77 request/s |
| p95 | 3.710,22 ms | 601,13 ms |
| Thời gian đo | 36.668 ms | 5.592 ms |

Đây là phép đo thành phần trên máy hiện tại, không suy rộng tuyến tính thành năng lực 10k. Test cache array/Redis-like warm hit có 0 query DB; cache database local vẫn cần đọc bảng cache. CPU/RAM/IO production, CDN hit rate và checkout throughput chưa đo. Các con số trên không bao gồm download toàn bộ ảnh/banner hoặc provider thanh toán.

Lặp smoke chỉ trên local:

```powershell
node scripts/load/smoke.mjs http://localhost:18081/api/v1/storefront 20 200
```

## Cấu hình triển khai mẫu

`deploy/compose.yml` + `deploy/compose.scale.yml` thêm frontend production, edge Nginx, Redis và FPM/OPcache. Đây là bản tham chiếu một host, có thể tăng replica app/frontend sau khi đo. Volume Docker chỉ chia sẻ trong cùng host; nếu chạy nhiều host, cần shared persistent storage hoặc tích hợp object storage đã kiểm thử. Không dùng cache file/session file riêng từng máy cho cluster.

Các bước operator cần hoàn thành trước khi mở website thật:

1. Chọn máy chủ, CDN/TLS, domain, secrets, ngân sách và workload cần nghiệm thu. Bật secure session cookie; cấu hình APP_URL/frontend/Sanctum/proxy đúng domain và TLS termination thực tế.
2. Điền env private từ template, mật khẩu DB/root/Redis riêng, APP_KEY production ổn định. Không copy `.env.demo` hay database demo sang production.
3. Build image; chạy `nginx -t` trong cả edge/web container, kiểm health và DNS service. Entrypoint tạo config/route cache trong mỗi container chạy thật. Migrate/import dữ liệu thật là thao tác operator riêng, không tự chạy trong entrypoint.
4. Kiểm thử cookie/CSRF/login/role/cart/order/ảnh trên HTTPS, worker/scheduler, backup và restore. Pin digest image đã nghiệm thu. FPM mẫu 16 children/replica và giới hạn RAM chỉ là điểm bắt đầu; đo RSS/DB connection budget trước khi tăng.
5. Warm public cache/CDN, theo dõi cache hit, FPM busy/queue, Redis memory/noeviction errors, DB CPU/slow queries/deadlocks/connection count. Cache không thay transaction và stock locks.

Tham khảo lệnh sau sau khi có Docker/env/hạ tầng; **chưa thực hiện trong phiên này**:

```powershell
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml config --quiet
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml build
docker compose --env-file deploy/.env -f deploy/compose.yml -f deploy/compose.scale.yml up -d
```

Hiện chỉ đã parse YAML bằng `php scripts/validate-deploy.php`; đó không phải Docker build hay kiểm Nginx runtime. Redis không mở port public. Edge mẫu bind localhost:8081; phải đặt TLS/reverse proxy tin cậy phía trước hoặc cấu hình TLS tại edge, không tự coi port local này là website public.

## Nghiệm thu tải

Profile: `scripts/load/public-storefront.k6.js`. Chỉ chạy trên hệ thống bạn sở hữu/được phép kiểm thử, có theo dõi và khả năng dừng tải. Mặc định smoke 20 VU. Profile lớn cần chọn rõ:

```powershell
$env:BASE_URL='https://staging-ten-mien-cua-ban'
$env:PROFILE='10k'
$env:ALLOW_HIGH_LOAD='1'
k6 run scripts/load/public-storefront.k6.js
```

Profile tăng dần tới 10.000 VU, giữ 5 phút; ngưỡng đặt trước: HTTP lỗi dưới 1%, checks trên 99%, p95 dưới 1 giây, p99 dưới 2 giây. Các ngưỡng chưa được chứng minh ở 10k. Cần load generator đủ tài nguyên, kiểm cold/warm cache và nhiều lượt độc lập. Không dùng số lượng user ngẫu nhiên trong DB làm bằng chứng concurrent capacity.

Sau đọc công khai, cần test mixed workload với tài khoản test, CSRF và idempotency; không gửi checkout/payment thật ở tải lớn. Nghiệm thu đồng thời phải kiểm không double-sell/double-charge, không rò cookie/cache giữa người dùng, và không mất job khi restart. Merchant VNPAY thật vẫn đang hoãn theo yêu cầu riêng.
