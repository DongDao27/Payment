# THUTHANH — Next.js storefront/admin

Giao diện Clicon V20 triển khai trên dữ liệu Laravel/MySQL thật, đủ136SKU; không dùng giới hạn prototype. Next16.4.2/React19.3.1/TypeScript/Tailwind4.3.3 theo lockfile. Shared visual override clicon.css dùng sau globals.css, giữ các control nghiệp vụ/accessibility cũ. Không rebuild bootstrap.

Máy hiện tại: chạy MySQL bằng scripts/mysql-local.ps1 -Action start, Laravel serve localhost8000; terminal khác tại frontend chạy npm run dev, mở http://localhost:3000. Dùng cùng hostname localhost cho cookie/CSRF. Hướng dẫn đầy đủ và giải thích database/deploy: ../docs/CHAY-WEB-VA-LARAVEL.md.

API proxy /api/v1, /sanctum/csrf-cookie, /storage về LARAVEL_INTERNAL_URL (server config, defaulthttp://localhost:8000). Không dùng localStorage bearer token hoặc NEXT_PUBLIC database secrets. Đăng nhập chung, server xác định admin/customer; admin tự vào /admin. Dashboard gọi endpoint /admin/dashboard thật, không fake fixtures/revenue. Nút mã đơn gần đây mở tìm kiếm trong adminorders.

Kiểm tra: npm run test:api; npm run build; npm run typecheck. Laravel tests chỉ chạy trên thu_thanh_test. Browser QA dùng scripts/frontend-browser-fixture.php và server riêng18080/Nextdev3100, không owner credentials/dev data. Payment/revenue thật và production deploy chưa nghiệm thu; Compose hiện chưa có Nextservice. Khi thay máy mới dùng npm ci theo lockfile; không cần cài extension VSCode để chạy.
