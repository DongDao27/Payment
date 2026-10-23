# Function → task → API → test
Các Function ID F01–F22 giữ tương thích Trello. Task ID Bxx/Fxx/Rxx là mã kế hoạch triển khai, khác Function ID dù trùng prefix F.

| Function | Backend tasks | Frontend task | Test modules |
|---|---|---|---|
| F01 | B03 | F01 | catalog, media |
| F02 | B03 | F01 | catalog, media |
| F03 | B03 | F01 | catalog, media |
| F04 | B03 | F01 | catalog, media |
| F05 | B05 | F01 | cart, quote |
| F06 | B02 | F01 | auth |
| F07 | B02 | F01 | auth |
| F08 | B05 | F01 | cart, quote |
| F09 | B05 | F02 | cart, quote |
| F10 | B06 | F02 | checkout |
| F11 | B07 | F02 | payment, provider |
| F12 | B06 | F02 | checkout |
| F13 | B07 | F02 | payment, provider |
| F14 | B06 | F02 | checkout |
| F15 | B06, B08 | F02 | checkout, expiry, fulfillment, ops, reconcile |
| F16 | B07, B08 | F02 | payment, provider, expiry, fulfillment, ops, reconcile |
| F17 | B09 | F03 | report, refund |
| F18 | B03 | F03 | catalog, media |
| F19 | B03 | F03 | catalog, media |
| F20 | B03 | F03 | catalog, media |
| F21 | B04 | F03 | stock |
| F22 | B08 | F03 | expiry, fulfillment, ops, reconcile |

API cụ thể xem docs/API.md và docs/openapi.json. Khi implement, bổ sung tên class/test path thực vào TASKS evidence; không bịa path của test chưa được tạo.

Đối chiếu thực tế ngày21/10/2026: [REQUIREMENTS-AUDIT](REQUIREMENTS-AUDIT.md). D29–D32 được kiểm trong `backend/tests/Feature/OwnerPolicyTest.php`; source prototype tại `thu-thanh-store-v12-free/`, canvas mapping chưa kiểm. Các bảng trên là mapping chức năng, không tuyên bố frontend đã hoàn thành.

Ngày22/10: D34/D35 authorise Next.js; source frontend/src/app và components đã triển khai F01–F10/F12/F14/F15, admin catalog/stock/orders cơ bản; không coi phần VNPAY/report/F02/F03 chưa đủ là done. Mapping browser QA/API ở VERIFICATION.md.


## D41 / FUI01 — web Clicon và thống kê vận hành

F01–F10/F14/F18–F22 giữ API/logic cũ, cập nhật shared visual system/home/card/form/admin. F17 mới thêm dashboard counts/chart đơn thật với GET /admin/dashboard, không tính doanh thu; revenue gate vẫn OPEN nên chưa gọi F17/F03 hoàn tất. DashboardTest2cases20assertions, fullsuite87/1386passed; UI dùng full136SKU và quyền server, không24mẫu/1SKU như Figma.


## D42 / DUI06 — F01/F04/F05 và asset hiển thị

Home/logo/card/quickview/shared modal cập nhật Next + pluginV21. Guest đọc public, mutation vẫn customer/session-CSRF; giữ SKU/quantity qua auth và Mua ngay tới checkout bằng API thật. HD136map chỉ bộ import hiện hành, ảnh admin upload được giữ. PRD F05 đồng bộ D42; API/schema không đổi. Tiền/tồn/quyền vẫn Laravel. Acceptance/docs ở qa/DUI06-browser.json, ASSET-SOURCES.md, MD-CLEANUP-MANIFEST.json.


## D43 / DUI07 — landing/layout/footer/newsletter

F01/F04/F05 visual và Figma-flat-layout; NewsletterController/SubscribeNewsletterRequest/NewsletterSubscription/new migration + NewsletterTest,POST /newsletter/subscriptions,40APIpaths. Consent/normalization/duplicate/CSRF/throttle không đổi auth/cart/order/payment.92backendtests1417assertions passed. Docs FIGMA-V22-REVIEW + API/DATABASE/PRD/ASSET-SOURCES và browser QA chỉ rõ scope/canvas chưa kiểm/provider vẫnhoãn.


## D44 / DUI08 — home theo bán hàng và admin

F01/F04/F05: HomeStorefront ba feed, CatalogService paid ranking/recommendations, ProductResource.sold_count, ProductPicture/display bounds và quick view. F17/F22: RevenueService zero-fill và RevenuePanel lọc/chart/CSV với công thức chủ dự án chốt. F18/F19: product archive/restore và CategoriesPage. F20/F21: giữ inventory/order rules, thêm ReconciliationPage. AdminAccount/AdminPassword giúp chủ dự án đăng nhập local mà không thay credential tự động. Tests SalesHomeArchiveTest/AdminAccessCommandTest cùng các suite tiền/tồn/quyền trước đây; bằng chứng trình duyệt tại docs/qa/DUI08-*.json. VNPAY thật/release vẫn chưa đạt; không coi tiền test là doanh số thật.


## D45 / DUI09

F01/F04/F05: footer/flame/ProductPrice, publicStorefront coalescing và guest bootstrap; F19/F20: ProductPricingFields/ProductPricing backend+strict validation/version; F17:111backendtests+demo1000sales cho chart/units; publiccache privacy/invalidation/rollback và boundedhydration có tests. DemoSalesSeeder và prepare/start-demo có guard/tách dữ liệu. TrustStoreProxies bảo toàn IP/rate limit khi có LB. PERFORMANCE.md và deploy/compose.scale.yml là cấu hình/testplan, không phải10kverified. CLEANUP-MANIFEST giữ149file cũ đã kiểmbytes; docsarchive giữ lịch sử. Backend/release gate vẫn chưapassed.
