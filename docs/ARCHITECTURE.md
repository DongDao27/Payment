# Kiến trúc và cấu trúc file
## Ranh giới
Next.js giao diện khách/admin; Laravel REST API duy nhất sở hữu auth, validation, money, stock, order/payment. MySQL 8.0.16+ với InnoDB/utf8mb4 cho dữ liệu và check constraints được enforce.
Laravel migration/Eloquent không phải database engine. Không dùng SQLite thay bằng chứng khóa hàng trên MySQL.
Đề xuất single repo, single backend, không microservices. Không chép Livewire/Blade storefront của lab cùng với Next.js.
PHP 8.4/Laravel 12 baseline để bám lab, nhưng audit phiên bản source đang có. Node/Next pin compatible stable/supported sau kiểm tra nguồn chính thức và môi trường, không tự dùng canary.

## Cấu trúc đích (chỉ tạo khi đến task)
- backend/app/Models/{User,Category,Product,ProductImage,Cart,CartItem,Quote,Order,OrderItem,StockReservation,PaymentAttempt,PaymentReceipt,PaymentEvent,InventoryMovement,OrderStatusHistory,ReconciliationCase,IdempotencyKey}.php
- backend/app/Http/Controllers/Api/V1/{Auth,Category,Product,Cart,Quote,Order,Payment}Controller.php
- backend/app/Http/Controllers/Api/V1/Admin/{Product,Category,Inventory,Order,Payment,Revenue,Reconciliation}Controller.php
- backend/app/Http/Controllers/Api/V1/VnpayController.php
- backend/app/Http/Requests/: validation theo action, không nhận role/total từ client.
- backend/app/Http/Resources/: product/order/payment có whitelist field.
- backend/app/Policies/: OrderPolicy, CartPolicy, ProductPolicy, ReportPolicy.
- backend/app/Actions/Auth/: registration/session normalization; không hai hệ thống auth.
- backend/app/Services/{Pricing,Cart,Checkout,Inventory,Order,Payment,Reconciliation,Revenue}Service.php
- backend/app/Payments/PaymentGateway.php, VnpayGateway.php, FakeGateway.php (Fake chỉ test).
- backend/app/Enums/: state/value objects đơn giản.
- backend/app/Console/Commands/{ExpireOrders,ReconcilePayments,CreateAdmin}.php
- backend/app/Jobs/: payment query/notification chỉ khi cần, retry idempotent.
- backend/config/{store,payments}.php
- backend/routes/{api,web,console}.php; backend/bootstrap/app.php.
- backend/database/{migrations,factories,seeders}/.
- backend/tests/{Unit,Feature,Integration}/ và support providers/test fixtures.
- backend/storage/: upload/log; persistent mount khi deploy.
- frontend/src/app/layout.tsx, globals.css, error.tsx, loading.tsx, not-found.tsx.
- frontend/src/app/(store)/{page.tsx,products/page.tsx,cart/page.tsx,checkout/page.tsx,orders/page.tsx,orders/[id]/page.tsx,orders/[id]/payment/page.tsx,payment/result/page.tsx}.
- frontend/src/app/(auth)/{login,register}/page.tsx (fallback, cùng auth logic với dialog).
- frontend/src/app/admin/{layout.tsx,page.tsx,products/page.tsx,products/new/page.tsx,products/[id]/edit/page.tsx,inventory/page.tsx,inventory/[categoryId]/page.tsx,orders/page.tsx,orders/[id]/page.tsx}.
- frontend/src/components/{ui,layout}/.
- frontend/src/features/{auth,catalog,cart,checkout,orders,payments,admin}/: components/hooks/api/types theo feature.
- frontend/src/lib/{api-client,money,date}.ts; frontend/public/; frontend/tests/.
- docs/: bộ tài liệu và handoff; docs/figma/ chỉ lưu export được cấp, không secret.
- deploy/{nginx,services,scripts}/; .github/workflows/; README.md, AGENTS.md.
Giữ component UI gọn; không xây abstract repository/service framework nhiều tầng không có nhu cầu.

## Topology đề xuất
Cùng origin public: / → Next.js; /admin → Next.js; /api/v1/* và /sanctum/* → Laravel; /storage/* → Laravel public disk được cho phép; /up → Laravel health.
IPN/return nằm dưới /api/v1/payments/vnpay/*. Không có Next route /api xử lý business trùng.
Local: Laravel 8000 + frontend 3000, dùng thống nhất localhost hoặc 127.0.0.1 ở cả hai, stateful domain bao gồm port, CORS allowlist và credentials. Không dùng CORS * với credential.
Server-render request riêng tư phải forward đúng session cookie đến URL Laravel allowlisted, no shared cache; không dùng Origin giả tùy ý từ client.


Cập nhật D34 (22/10/2026): chủ dự án xác nhận yêu cầu giảng viên Next.js frontend + Laravel backend, cho phép bắt đầu frontend dựa backend local B00–B06 trong lúc VNPAY/revenue deferred. Quy tắc khóa frontend trước B10 ở trên được thay bằng ngoại lệ phạm vi này; gate backend nghiêm ngặt/release không passed. D33 stack conflict đã giải quyết, các thiếu sót rubric khác vẫn giữ.
