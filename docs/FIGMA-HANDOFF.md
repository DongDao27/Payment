# Thiết kế theo Figma

**Hiện hành D43 / V22:** Landing page riêng2frame duyệt,2page prototype với7hàng flow/page,frame top-level,không section lớn bao.124frame tổng/122prototype/2758links/58SVG. Gray panels,logo Constantia/bookmark riêng,đúng2banner nguồn đã chọn,footer8brands/newsletter/address295TônĐứcThắng. Newsletter backend verified trước UI;schema+API mới riêng,full92tests1417assertions. Browser và gói theo MD-CLEANUP-MANIFEST.json. Các mục có ngày/version cũ bên dưới là lịch sử.


**Hiện hành D42 / DUI06, 23/10/2026:** plugin V21 + web theo ảnh Phương Nam, logo riêng NHÀ SÁCH THU THANH, thẻ XEM NHANH, popup guest/auth giữ quantity, spacing; 136 ảnh gốc HD trên app,24 ảnh PNG lossless Figma. V21 có120màn/14flow/2642links/56SVG, 4SKU/category/firstSKU demo. Web dùng đủ136SKU. Xem [hướng dẫn import](../thu-thanh-figma-clicon/HUONG-DAN.md), [hướng dẫn chạy](CHAY-WEB-VA-LARAVEL.md), [nguồn asset](ASSET-SOURCES.md) và [browser QA](qa/DUI06-browser.json). Mock/local preview đã kiểm; canvas của chủ dự án chưa kiểm. Các mục có ngày cũ bên dưới là lịch sử.

Trạng thái 21/10/2026: đã tìm thấy source plugin prototype ở `thu-thanh-store-v12-free/` (code.js, ui.html, manifest.json, HUONG-DAN.md). Manifest ghi Store V15.1; tên thư mục vẫn v12. Chưa có URL/file_key/node_id hoặc export canvas thật; frontend readiness chưa đạt.

## Nguồn giao diện
Figma do người dùng cung cấp là chuẩn về bố cục, font, spacing, màu, asset và responsive.
Không dùng ảnh overview/AI mockup làm bản thiết kế cuối. Không dựng trước Next.js để đoán Figma khi backend chưa đạt gate.
Nếu Figma thiếu trạng thái lỗi/loading/empty/keyboard, bổ sung tối thiểu theo component hiện có và ghi danh sách, không thay toàn thiết kế.
Không yêu cầu dữ liệu API thực chỉ tồn tại cho sản phẩm đầu: giới hạn demo Figma không áp vào app.

## Mapping API và frame cần xác nhận trên canvas
| Screen | Function | Route/overlay | Figma node | API | Trạng thái |
|---|---|---|---|---|---|
| Home | F01 | / | PENDING | /home,/categories | source_found_canvas_unverified |
| Catalog | F02–F04 | /products | PENDING | /products | source_found_canvas_unverified |
| Add cart | F05 | AddToCartDialog | PENDING | product detail,/cart/items | source_found_canvas_unverified |
| Auth | F06–F07 | AuthDialog + fallback /login,/register | PENDING | /auth/* | source_found_canvas_unverified |
| Cart | F08 | /cart | PENDING | /cart | source_found_canvas_unverified |
| Checkout | F09–F10 | /checkout | PENDING | /checkout/quotes,/orders | source_found_canvas_unverified |
| Payment | F11–F13 | order payment + result | PENDING | payment-attempts/status | source_found_canvas_unverified |
| My orders | F14–F16 | /orders,/orders/{code} | PENDING | orders | source_found_canvas_unverified |
| Dashboard | F17 | /admin | PENDING | /admin/revenue | source_found_canvas_unverified |
| Product CRUD | F18–F20 | /admin/products | PENDING | admin products/media | source_found_canvas_unverified |
| Inventory | F21 | /admin/inventory | PENDING | /admin/inventory | source_found_canvas_unverified |
| Orders/QR | F22,F13 | /admin/orders/{code} | PENDING | admin orders/payment | source_found_canvas_unverified |

## Quy tắc UI cụ thể
- Một form login chung; admin không register. Admin redirect dashboard, customer tiếp tục thao tác.
- Card có icon giỏ, giá/giảm khi có; popup quantity/Back/Confirm, nền tối. Không tự sinh route UI detail riêng nếu Figma không có.
- Sau login giữ intent product/quantity an toàn, không tự add hai lần.
- Hiển thị success/fail bằng icon và chữ, pending khác fail. Nút “Về danh sách đơn” theo thiết kế.
- Inventory category → list stock; frontend có thể group data server, không tự đoán stock_reserved.
- Timer từ expires_at/server_time, không reset khi reload; UI hết hạn không thay server verification.
- Order list/detail private, payment-return là trang kết quả chứ không nguồn payment truth.
- Responsive Web/Mobile cùng nghiệp vụ; ảnh thật approved, alt text, bàn phím/focus, không duplicate chữ thừa.
- Không thêm Google login/rating/COD/voucher/theme/lang switch chỉ vì xuất hiện trong ảnh minh họa hoặc lab.

## Khi bắt đầu frontend
1. BACKEND_VERIFIED phải pass; đọc OpenAPI thực đã kiểm, không chỉ draft.
2. Lấy Figma frame Web/Mobile, xác định token/components/assets, điền node mapping.
3. Nối mỗi screen với endpoint và error code; yêu cầu missing contract sửa backend/test trước.
4. Đối chiếu render screenshot desktop/mobile với frame thật; lưu sai khác cụ thể.
5. E2E dùng backend thật MySQL test + gateway test adapter chỉ trong test; sandbox flow kiểm riêng.

## Audit source prototype local (D32)

HUONG-DAN.md mô tả 87 frame Web / 85 Mobile, 48 card sách thuộc 6 danh mục; code.js dùng khổ Web 1440 và Mobile 390. Đây là thông tin source, chưa đếm node trên canvas thật. `node --check` passed. Không có file `.fig`/ảnh export được tìm thấy trong workspace; chưa chạy builder hoặc thay đổi thiết kế của người dùng.

- Home/catalog/auth/cart/checkout/order/payment có flow prototype để đối chiếu mapping ở bảng trên. Từng node_id vẫn cần xác nhận khi mở Figma.
- Flow demo mua Toán 1, quantity 1–2, form/search/QR/payment giả lập không phải giới hạn nghiệp vụ hoặc API thật.
- Có `adminLogin` riêng, khác yêu cầu một form login chung. Khi triển khai dùng auth chung theo PRD; cần đồng bộ prototype.
- Có màn `detail` và `account` ngoài mapping tối thiểu; chưa tự suy ra phạm vi chức năng mới từ hình vẽ.
- Footer có “Chính sách đổi trả”: nội dung cần cập nhật rõ không hỗ trợ đổi trả/hoàn tiền. Checkout phải hiển thị phí giao mặc định 25.000đ, pickup 0đ. Màn VNPAY chưa được thể hiện như thanh toán thật đã hoạt động.
- Admin prototype thiên về dashboard/đơn/pickup QR; chưa xác nhận đủ UI CRUD sản phẩm, ảnh, tồn và báo cáo theo F17–F22.
- Banner/ảnh sách còn placeholder; cần asset thật. Chưa có bằng chứng visual QA, accessibility hoặc responsive render trên canvas.
- Builder có thể thay thế section do plugin quản lý; phải giữ bản thiết kế đang sửa tay trước khi chạy. Phiên này chỉ đọc source, không chạy plugin.

Để xem local: mở Figma Desktop → Plugins → Development → Import plugin from manifest, chọn manifest.json trong thư mục trên; làm theo HUONG-DAN.md. Việc import/xem prototype chưa thay cho API integration hoặc BACKEND_VERIFIED.


Cập nhật D34 (22/10/2026): chủ dự án xác nhận yêu cầu giảng viên Next.js frontend + Laravel backend, cho phép bắt đầu frontend dựa backend local B00–B06 trong lúc VNPAY/revenue deferred. Quy tắc khóa frontend trước B10 ở trên được thay bằng ngoại lệ phạm vi này; gate backend nghiêm ngặt/release không passed. D33 stack conflict đã giải quyết, các thiếu sót rubric khác vẫn giữ.

## Mapping triển khai theo source local D35 — 22/10/2026

Chủ dự án xác nhận chỉ có plugin và chọn dùng source local, không tiếp tục chặn vì thiếu link cloud. Header code.js515/footer542/landing559/editorial914 là references của bản V15.1. Palette cuối cùng #FFFDFC/#1A1C2A/#D5E4E7/#ECEBF4, không dùng màu đỏ legacy trước editorial pass. Phosphor paths được tái sử dụng và giữ license.

| Source flow | Next route/component | Kiểm chứng |
|---|---|---|
| Landing Web/Mobile | app/page.tsx; StoreShell; CSS hero/grid | Screenshot1440/390; no-overflow1440/768/390/360 |
| Category/search/grade | /products; URL query filters | Search Toán18 results, ascending sort, empty state |
| Auth + quantity popup | StoreProvider/AuthDialog/BuyDialog; /login,/register | Intent restore, no double add; one login/admin redirect |
| Cart/checkout | /cart,/checkout | Real server quote/delivery25000/pickup0, idempotent order |
| My orders/detail | /orders,/orders/[code] | Cancel/reorder/owner-role checks; unpaid remains unpaid |
| Admin products/stock/orders | /admin/* | CRUD/hide/upload/stock/detail local QA; report/QR deferred |

Không chạy hoặc sửa builder. Không có node_id/canvas screenshot thật, nên không tuyên bố pixel-perfect. Banner placeholder có copy giới thiệu/ảnh sách động để bản web dùng được; asset banner cuối vẫn chưa được cấp. Không mock48 products/giá31k/giới hạnquantity1–2 từ prototype vào nghiệp vụ. Xem VERIFICATION.md và docs/qa.


## D36 — cập nhật thiết kế Clicon 22/10/2026
Bộ plugin mới `thu-thanh-figma-clicon` để import duyệt, giữ bản V15.1. Chưa áp dụng Next.js và chưa nghiệm thu canvas thật. Nguồn reference Clicon và bằng chứng syntax/mock/preview trong [MD-CLEANUP-MANIFEST.json](MD-CLEANUP-MANIFEST.json). Tái kiểm: `node --check thu-thanh-figma-clicon/code.js`, `node scripts/test-figma-clicon.cjs`; chủ dự án cần import kiểm font/bounds/Present trên Figma thật. Không thay business hoặc gate backend/release.


## D37 — V17 thay toàn bộ bố cục (22/10/2026)
Nguồn hiện hành `thu-thanh-figma-clicon/manifest.json` tên V17, source layout được viết lại;640 khung/17302 links trên14 section,136 SKU/ảnh/giá/tồn, đúng dải chào header. Nội dung V16 phía trên là lịch sử. Xem [MD-CLEANUP-MANIFEST.json](MD-CLEANUP-MANIFEST.json) và HUONG-DAN.md trong plugin. Syntax/mock/data/filter/pagination/rollback và browser preview local passed; live canvas/font/pixel comparison/owner approval còn pending. Không đổi Laravel/Next.js hoặc gate provider/release.


## D38 — V18 compact,23/10/2026
V17 ở trên là lịch sử. V18 hiện24 mẫu (4/category), chỉSKU đầu nối mua;80frames1260links,7sections mỗi platform,không index/detail/paging lặp. `node scripts/test-figma-clicon.cjs` passed; live Figma pending. App vẫn136SKU/multi-item:85backendtests1366assertions,6frontend clienttests/build/typecheck passed; xem [MD-CLEANUP-MANIFEST.json](MD-CLEANUP-MANIFEST.json) cho source gaps và việc tiếp theo. Không áp giới hạn Figma vào app.


## Hiện hành D45 / V24

Source/pluginV24 giữ126frames/3page/7flows mỗi platform/2804links. Footer bỏ heading nhãn hàng, home có lửa, card thể hiện discount từ metadata demo riêng; giá workbook gốc trong dataset và SKU mua TT-SGK-002 vẫn14k để giữ luồng tổng/QR. Fieldadmin thêm phần trăm giảm. Nativegeometry/priceframes/footer/hotspots/money/rollback/manualpreserve được mockverify, canvas thật cònpending. PreviewSVG/output được generate, không là source business hoặc dữ liệu bán thật.
