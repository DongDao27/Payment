# Decision register
Ngày tạo 21/10/2026. Nhãn: CONFIRMED từ trao đổi; ENGINEERING_DEFAULT đề xuất để code; OPEN cần chính sách/nguồn thật.
Không được tự chuyển OPEN/ENGINEERING_DEFAULT thành CONFIRMED bằng việc code xong.

| ID | Trạng thái | Quyết định/ảnh hưởng |
|---|---|---|
| D01 | CONFIRMED | Backend Laravel đầy đủ trước, frontend sau gate |
| D02 | CONFIRMED | Next.js frontend, Laravel backend/migration, MySQL database |
| D03 | CONFIRMED | Giao diện theo Figma người dùng, không infographic |
| D04 | CONFIRMED | Một admin cấp sẵn, login chung, chỉ customer register |
| D05 | CONFIRMED | Guest browse, login để add cart/checkout; hai cách nhận |
| D06 | CONFIRMED | Delivery giữ300s, pickup giữ86400s tính từ tạo đơn |
| D07 | CONFIRMED | VNPAY Sandbox trước, Production khi có quyền |
| D08 | ENGINEERING_DEFAULT | PHP8.4/Laravel12 bám lab nếu repo chưa có version khác; pin runtime được hỗ trợ |
| D09 | ENGINEERING_DEFAULT | TypeScript/Tailwind frontend nếu repo chưa chốt tương đương |
| D10 | ENGINEERING_DEFAULT | Sanctum cookie SPA/CSRF, không personal token cho browser chính |
| D11 | ENGINEERING_DEFAULT | Card→popup, không phát sinh product detail UI; xác nhận lại mapping khi có Figma |
| D12 | ENGINEERING_DEFAULT | Quote5m không reserve; qty1..99; order cap1 tỷVND; image5MiB jpeg/png/webp |
| D13 | ENGINEERING_DEFAULT | Curated hot/bestseller flags; mới theo created_at, chưa quảng cáo ranking doanh số thật |
| D14 | CONFIRMED 2026-10-21 | Phí giao mặc định 25.000đ/đơn delivery theo yêu cầu mới nhất; pickup 0đ. Địa chỉ free text MVP; chưa có giới hạn vùng giao được chỉ định. |
| D15 | CONFIRMED qua D44, 23/10/2026 | Doanh thu tiền hàng sau giảm giá của đơn paid có receipt accepted; phí giao hàng thống kê riêng. Không hỗ trợ đổi trả/hoàn tiền. |
| D16 | OPEN | Provider QR/capabilities/merchant creds; không fake integration success |
| D17 | OPEN | Recovery phone-only, verify email/phone policy; chưa có OTP service |
| D18 | OPEN (local source found) | Có plugin Figma thu-thanh-store-v12-free V15.1 trong workspace; chưa có file key/node/export hoặc nghiệm thu canvas thực tế. Source local được dùng để audit; không còn báo thiếu toàn bộ thiết kế. |
| D19 | OPEN | Hosting/domain/budget, retention, người phụ trách đối soát/hoàn tiền |
| D20 | ENGINEERING_DEFAULT | Không POS khách vãng lai; revenue chỉ giao dịch hệ thống, nếu muốn toàn cửa hàng phải mở scope |
| D21 | SUPERSEDED BY D30 | Đề xuất refund ledger trước đây được giữ làm lịch sử. Chính sách hiện tại không đổi trả/hoàn tiền; không bật route ghi refund. Giữ dữ liệu tài chính cũ để đối soát. |
| D22 | ENGINEERING_DEFAULT | Không tự copy profile/2FA/token manager/TALL frontend từ lab vào scope |

## Xử lý thiếu thông tin
- Không chặn B00–B06 chỉ vì D14/15/16/18 mở; có thể triển khai config và failure path với fixtures.
- Không giả lập để đánh dấu integration pass. B10 nghiêm ngặt ghi blocker nếu thiếu sandbox/QR.
- Không dùng tên user/email thật trong seed công khai. Giá trị demo có nhãn demo.
- UI yêu cầu mới đổi scope phải ghi ngày/lý do và sửa PRD/API/tests liên quan.

## Lịch sử mới
Chưa có quyết định mới sau khi phát hành gói. AI thêm bản ghi có nguồn và ngày thực tế.

| D23 | ENGINEERING_DEFAULT 2026-10-21 | Workspace root is application repository; root docs are canonical checkpoints, original build pack preserved as reference. PHP8.4.25/Laravel12.70.0/MySQL8.4.13 selected after audit. MySQL loopback3307 separates lab. |
| D24 | ENGINEERING_DEFAULT 2026-10-21 | First-party API explicitly uses web middleware for cookie+CSRF even without Origin; Sanctum session guard, no token issuance. CSRF419 normalized to CSRF_TOKEN_MISMATCH envelope; HTTP verified. |

| D25 | ENGINEERING_DEFAULT 2026-10-21 | Product read schema includes is_hot/is_bestseller so admin can read curated settings. Textbook slug sach-giao-khoa reserved against renaming; category edits increment affected product versions. Images max8192px each dimension; deletion uses retryable after-commit cleanup job and requires worker. |

| D26 | ENGINEERING_DEFAULT 2026-10-21 | Continue independent B08/B09 code while B07 merchant/QR verification remains external-blocked, as AGENTS permits; dependency-complete/done and backend gate remain unverified. PAY redirect adapter does not fabricate pickup QR; QR stays503 until merchant contract and dedicated adapter are available. |

| D27 | ENGINEERING_DEFAULT 2026-10-21 | Proposed report clarification: only refunds of accepted receipts reduce merchandise/shipping sales; refunds of duplicate/late/mismatch receipts are reported separately as unallocated_refunds. unallocated_receipts is gross received in period. Revenue stays503 until STORE_REVENUE_POLICY=approved_v1 reflects actual owner approval; refund write stays disabled by default. Private evidence references point to operator-supplied files under private evidence/. D15 đã được chủ dự án chốt tại D44; approved_v1 được bật, refund writes vẫn tắt. |

| D28 | ENGINEERING_DEFAULT 2026-10-21 | Argon2id default hashing preserves proposed12?128 character passwords without bcrypt72-byte truncation. Private local disk generic URL serving disabled; evidence download uses admin route. Return endpoint responds JSON validation-only200, not draft302. Exact provider ACK schema and private evidence route documented. |

## Yêu cầu mới của chủ dự án — 21/10/2026

| ID | Trạng thái | Quyết định và phạm vi |
|---|---|---|
| D29 | CONFIRMED | Delivery mặc định 25.000đ; pickup vẫn miễn phí giao. Server tính phí, client không được ghi đè. Cấu hình phí trống dùng mặc định 25.000đ; cấu hình sai không tự thành 0đ. |
| D30 | CONFIRMED | Không đổi trả/hoàn tiền sau bán. Chặn ghi refund ở backend kể cả khi biến env cũ bật; giữ ledger/evidence lịch sử và xử lý tiền bất thường bằng đối soát. Hủy đơn chưa thanh toán và giải phóng tồn theo luật cũ vẫn được phép. |
| D31 | CONFIRMED | Tích hợp VNPAY thật để sau. Chặn tạo yêu cầu thanh toán mới ở chế độ deferred; vẫn giữ adapter/tests và callback/đối soát cho bằng chứng đã có. Không yêu cầu merchant credentials ở lượt audit này và không đánh dấu Sandbox passed. |
| D32 | CONFIRMED source location | Thiết kế local ở thu-thanh-store-v12-free, manifest ghi V15.1 dù tên thư mục V12. Audit source/prototype trước; chưa có yêu cầu build Next.js trong lượt kiểm tra này. Canvas/visual chưa được xác minh. |

Tiêu chí trước thay đổi B10 audit: phí delivery mặc định25000/pickup0 và không nhận tổng từ client; refund403 theo chính sách dù env cũ true; deferred payment503 không sinh attempt; source Figma được kiểm syntax/manifest và ghi rõ lệch PRD; Composer/PHP extensions được kiểm thực tế; chạy regression trên MySQL riêng. Gate BACKEND_VERIFIED không được tự nâng thành passed do dời VNPAY.


## D33 — Đối chiếu rubric CSB35050 (OPEN, 21/10/2026)

Nguồn: hai DOCX Prep/Project Brief chủ dự án cung cấp; yêu cầu hiện tại chỉ kiểm tra đáp ứng và patterns. Rubric yêu cầu Blade/Livewire/Tailwind, Sanctum token/abilities, Scramble, Telescope, coverage >70%, slow-query/server alerts và GitHub topic branches/PR/issues. Kế hoạch Next.js, cookie-only API và payment thực deferred chưa tự đáp ứng các tiêu chí tương ứng. Chưa có bằng chứng giảng viên chấp thuận stack khác.

Ghi conflict để giải quyết trước code phần liên quan: chủ dự án chốt hướng UI theo rubric hoặc cung cấp chấp thuận Next.js; phạm vi API client token có thể bổ sung độc lập với cookie browser; external API thật còn cần nghiệm thu theo scope đã chọn. Chưa thay đổi PRD/ARCHITECTURE quyết định stack, chưa cài package hoặc tự bỏ deferral VNPAY. Báo cáo đầy đủ: COURSE-RUBRIC-AUDIT.md. Đây là phát hiện audit, không phải quyết định đổi nghiệp vụ/kiến trúc đã được chủ dự án chấp thuận.


## D34 — CONFIRMED 22/10/2026: Next.js + Laravel, bắt đầu frontend có phạm vi

Chủ dự án xác nhận giảng viên yêu cầu frontend Next.js/backend Laravel và yêu cầu bắt đầu build theo Figma + workbook Nha_sach_Thu_Thanh_San_pham_2026-10-21.xlsx. Stack UI conflict D33 được giải quyết theo thông tin mới này; không chuyển sang Blade/Livewire. Các yêu cầu môn khác chưa tự được miễn.

Yêu cầu bắt đầu frontend là ngoại lệ có phạm vi cho dependency B10: cho phép F00/F01/F02/F03 triển khai tăng dần trên backend local đã kiểm chứng B00–B06, dù gate payment/revenue thật chưa đạt. BACKEND_VERIFIED không chuyển passed; release/thanh toán thật vẫn khóa. VNPAY tiếp tục deferred, không fake paid/COD; report vẫn theo policy gate. Một admin/login chung, server quyết định quyền/tiền/tồn, session cookie/CSRF, không token localStorage.

Workbook đã có trong workspace không đồng nghĩa đã nhập MySQL:136 sản phẩm/ảnh,6 danh mục chính/18 con; giá bán và tồn cửa hàng chưa có. Chỉ audit/stage dữ liệu chưa biết; chờ câu trả lời về giá/tồn trước ghi dữ liệu giao dịch hoặc seed demo. Chưa tự mở rộng database danh mục con hoặc thay giá tham khảo thành giá bán. Cần link/node hoặc thiết kế được xác nhận để nghiệm thu Figma; source local không thay chứng cứ pixel match.

Acceptance trước triển khai: Next App Router/TypeScript/Tailwind build/typecheck đạt; API client cùng origin có cookie/CSRF và xử lý lỗi; dữ liệu Excel audit có kiểm duplicate/ảnh/giá/tồn; ảnh/source được bảo toàn; responsive, keyboard/focus/error/loading và hành vi auth/cart đúng API; chỉ gọi phần đạt đã kiểm thực tế. Không deploy hoặc sửa prototype gốc.


## D35 — CONFIRMED 22/10/2026: workbook cập nhật và thiết kế local
Chủ dự án trả lời đã thay workbook có giá/tồn và chỉ có plugin Figma, chưa có link. Đọc lại source sha256 ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb:136/136 có giá/tồn/ảnh. Tiếp tục dựng theo source plugin local người dùng chọn (header và editorial mapping cuối file), không chặn vì thiếu cloud link và không tuyên bố visual canvas đã verified.

Nhập dữ liệu cửa hàng từ cột O/P, không dùng giá tham khảo M; cover price N chỉ dùng khi hợp lệ, không tự tạo khuyến mãi. Map tên 6 danh mục về slug đã có; 18 nhóm con giữ trong metadata báo cáo/mô tả, không tự đổi FK/schema hoặc tạo thêm category ngoài PRD. Trusted CLI import có dry-run, atomic database transaction, skip existing SKU (không reset giá/tồn sau bán), ảnh nội bộ đã kiểm MIME/hash, opening ledger ghi nguồn workbook và actor hệ thống null được schema cho phép; không tự tạo admin/mật khẩu. Acceptance: invalid/missing price/stock reject trước ghi, idempotent rerun, ledger đúng, rollback toàn bộ khi lỗi, API read hiển thị ảnh/giá thật.


## D36 — CONFIRMED 22/10/2026: thiết kế thuthanh theo Clicon

Nguồn: chủ dự án cung cấp file e5iYNlmq8NbdCyNlhUXFer, trang 279:19819. Chỉ sửa bộ plugin local để import và duyệt; logo duy nhất `thuthanh`, nội dung tiếng Việt, giữ nghiệp vụ. Tạo edition thu-thanh-figma-clicon và trang riêng, giữ nguyên plugin V15.1 và canvas cũ. Tham khảo bố cục/màu Clicon, thay điện tử bằng sách; không thêm wishlist, coupon, so sánh, đổi tiền/ngôn ngữ hoặc đổi trả. Shipping25000/pickup0, không đổi trả/hoàn tiền; VNPAY vẫn deferred. Không sửa Laravel/Next.js, không ghi vào file Clicon tham khảo.

Acceptance trước thay đổi: JavaScript/manifest hợp lệ; kiểm tra mock Figma xây cả máy tính/điện thoại, route tồn tại, nhãn/logo/màu đúng, giá/ảnh mẫu từ workbook; rebuild/cancel không mất bản trước. Kiểm tra preview bố cục, đóng gói ZIP tự chứa và hướng dẫn import. Mock/preview không được gọi là chạy thành công trong Figma thật; cần chủ dự án import duyệt canvas.


## D37 — CONFIRMED 22/10/2026: thay toàn bộ layout và chia flow theo Clicon
Chủ dự án yêu cầu bỏ giao diện cũ, làm lại khung sản phẩm/trang chủ/các trang theo Clicon, nội dung nhà sách, chia từng flow và có UX đủ. Dòng nhỏ header chỉ đúng “Chào mừng đến với nhà sách Thu Thanh”; không ghép phí giao, cảnh báo hay trạng thái vào dòng này. Logo vẫn thuthanh, toàn bộ UI tiếng Việt. D37 thay phạm vi chỉnh màu/giữ layout D36 bằng viết lại layout plugin; Next.js/Laravel chưa đổi trong lượt duyệt Figma. Màn thông tin sách/bản mẫu chi tiết Clicon được phép thêm để duyệt, không đổi nghiệp vụ/API.

Giữ phí giao25000/pickup0, no returns/refunds, VNPAY deferred; trạng thái payment tương lai ghi nhãn trong nội dung màn. Không thêm rating/wishlist/coupon/COD/đổi tiền/ngôn ngữ/Google login/newsletter. Reuse ảnh thật136 sản phẩm; các nội dung marketing không bịa khuyến mãi. Tách section theo flow khám phá/tài khoản/giỏ-đặt hàng/đơn/thanh toán/quản trị/hỗ trợ trên mỗi nền tảng, liên kết prototype giữa các flow. Bản source V16 được backup trước khi thay; plugin chỉ thay các vùng có marker do plugin tạo, không xóa lớp thủ công ngoài vùng đó.

Acceptance trước code: desktop1920/content1320/card234×320 theo source; mobile390 riêng; header exact tất cả màn; các màn chính có breadcrumb/sidebar/table/form theo mẫu thay vì layout cũ; đường đi bình thường và loading/empty/validation/error/out-of-stock/requote/permission/cancel/expiry/deferred; route đích tồn tại, overlay quay lại không đổi giỏ, total25000/pickup0; dataset136 ảnh/giá/tồn hash đúng; cancel/failure rollback; docs/ZIP/preview tự chứa. Figma thực và pixel comparison cần chủ dự án import, không nâng gate từ mock.


## D38 — CONFIRMED 23/10/2026: Figma gọn, code ứng dụng đầy đủ
Chủ dự án yêu cầu Figma chỉ4–6 sản phẩm mỗi danh mục, chỉ nối demo sản phẩm đầu, bỏ màn lặp, đủ chức năng demo. Chọn4 SKU/danh mục (24 tổng), Toán1 TT-SGK-002 là sản phẩm đầu và duy nhất có nhánh mua/giỏ/đặt hàng. Giữ layout Clicon, logo thuthanh, welcome exact và7 section/nền tảng; bỏ các bản đồ frame, dùng màn đầu từng section làm điểm mở. Giữ ví dụ quantity1/2 để chứng minh tổng tiền và delivery/pickup; gom lỗi/loading/disabled/focus vào boardUX.

Đây là giới hạn prototype, không giới hạn Laravel/Next.js: app vẫn phải đọc mọi SKU, tìm/lọc/phân trang, mua nhiều sản phẩm hợp lệ theo API. Không xóa/seed lại136 sản phẩm. Lượt này audit code thực và kiểm thử local để xác định việc còn thiếu; không suy audit là đã build xong toàn bộ UI/payment/deploy.

Acceptance trước sửa:24 SKU/hash/giá/tồn đúngnguồn, mỗi category4; tối đa40 màn/nền tảng (không tính vùng section), chỉSKU đầu có click mua/chỉnh sửa/tồn; giữ đủ7 flow và UXboard; không tạo chi tiết riêng/phân trang cho từngSKU; đích link hợp lệ, số lượng/tổng tiền đúng, rebuild/cancel giữ an toàn. Kiểm code nguồn, API/backend tests, Next build/typecheck/client tests khi môi trường cho phép; báo trung thực thiếu VNPAY/report/giao diện Clicon/admin chuyên biệt/deploy/course.

## D39 — CONFIRMED 23/10/2026: footer, phí tại checkout và prototype thanh toán đầy đủ

Theo yêu cầu mới: bỏ dải cam kết bốn ô; footer trắng chia cột Dịch vụ/Hỗ trợ/Tài khoản của tôi tham khảo ảnh Fahasa, giữ thương hiệu thuthanh và chính sách Thu Thanh, không thêm chứng nhận/liên hệ/đối tác giả. Phí giao giữ 25.000đ, pickup 0đ ở server; chỉ công bố số phí tại checkout và chứng từ đơn đã đặt. Trang duyệt, giỏ, footer và chính sách không niêm yết số phí. Đồng bộ phần chung này trên Next.js hiện hành; không triển khai provider thanh toán thật.

Figma hoàn thiện QR MẪU, chờ xác minh, thành công, thất bại, hết hạn, cần đối soát; QR chỉ chứa văn bản demo không phải thông tin chuyển tiền. Tách điều hướng mô phỏng khỏi thao tác khách xác nhận thanh toán. Cùng một luồng dùng cho giao hàng/nhận tại quầy, số lượng 1/2; thêm các trạng thái thanh toán có mục đích được phép vượt giới hạn40 màn D38, vẫn chỉ4 sản phẩm/danh mục và1 SKU demo. Không đổi đơn đã hết hạn thành paid, không tự tạo lần thử khi unknown, không thu tiền hoặc fake paid trên code thật. VNPAY tiếp tục deferred; backend/release gate không đổi.

Acceptance trước sửa: thay thế section V18 khi rebuild không nhân bản; kiểm tra containment và các cột footer/QR không chồng; QR mẫu hợp lệ offline; prototype có đủ nhánh và tổng tiền đúng số lượng; giữ rollback/manual layers. Chạy regression backend và Next build/typecheck/API tests; ghi rõ kiểm tra canvas thật/provider thật còn pending. Cập nhật gói import và hướng dẫn chạy không reset dữ liệu.

## D40 — CONFIRMED 23/10/2026: bản trình bày sạch, admin login và THUTHANH

Yêu cầu mới thay wordmark lowercase D36–D39 thành THUTHANH với serif đậm nghiêng đặc trưng; welcome không đổi. Figma thêm lựa chọn khách/quản trị khi đăng nhập và dashboard thống kê đơn/catalog/tồn (không tự chốt công thức doanh thu). Web thật vẫn login chung và server quyết định role; không tạo nút nâng quyền. Bỏ ghi chú kỹ thuật/demo/minh họa khỏi màn trình bày, giữ giải thích trong plugin/docs/metadata. QR vẫn payload vô hại; không kích hoạt provider thật.

Thanh toán prototype: chuyển chọn kết quả sang UI plugin, mặc định thành công; QR→chờ xác minh→kết quả thành công/thất bại/hết hạn/đối soát. Có receipt paid đúng số lượng/cách nhận; retry chỉ trạng thái thất bại còn hạn, unknown không retry; không hủy paid. Landing chỉ banner đầu có CTA Khám phá ngay, các banner khác thuần nội dung; breadcrumb chữ Trang chủ và logo đều về home. Dọn luồng deferred trong prototype, không thay deferral backend/app thật.

Acceptance trước sửa: label sạch, role login→dashboard clickable, chart/counts nhất quán fixtures, home duy nhất1 CTA khám phá, breadcrumb text link đúnghome, logoTHUTHANH/font fit; tiền/quantity/status/paid-actions đúng ở cả2platform; scenario selector không xuất hiện trong canvas. Rebuild thay V19 không chồng, preservation/rollback giữ; build/typecheck và mock/browser/package kiểm thực tế. Không schema/deploy/reset dữ liệu hoặc đánh dấu provider/gate passed.

## D41 — CONFIRMED 23/10/2026: cập nhật web thật theo V20 và giải thích vận hành

Chủ dự án yêu cầu cập nhật web và hướng dẫn local/Laravel/database/deploy. Cho phép áp V20 vào các route hiện có: header blue/nav white, CTA orange, card/lưới/home/category/footer, auth/cart/checkout/orders/admin cùng visual system; website giữ136SKU và multi-item, không mang giới hạn demo sang code. Thêm admin dashboard đọc dữ liệu thật: sản phẩm/danh mục đang bán, đơn hôm nay/chờ xử lý,7ngày và đơn gần nhất; không tính doanh thu hoặc bật VNPAY/QR mẫu trên web. Dùng múi giờ Asia/Ho_Chi_Minh cho ngày thống kê, stored timestamps UTC, cùng quy ước filter admin orders hiện tại.

Acceptance trước sửa: SSR/build/typecheck/client tests; role guest401/customer403/admin200 với dashboard; ngày VN tại ranh giới UTC, zero-fill7ngày, exclude inactive catalog and closed processing, eager-load recent orders, no money/revenue fields; contract docs đồng bộ. Browser storefront responsive/card/search/modal/home link/admin read-only QA khi có phiên test hợp lệ, không lấy mật khẩu owner hoặc tạo admin mới trên dev. Giữ tiền/tồn/quote/idempotency/session-CSRF. Hướng dẫn đúng MySQL portable3307/mysql-data, Laravel API/Eloquent và kiến trúc deploy có persistent storage; không deploy hoặc thay schema/env/dữ liệu. Gate provider/release không thay.


D41 dependency clarification: FUI01 là visual update + dashboard chỉ đọc trên storefront F01 đã done và design DUI05 đã done; dependency F02 ban đầu quá rộng vì chứa payment thật đang deferred. Sửa FUI01.depends_on thành DUI05/F01; không sửa F02/F03 thành done, không nâng backend/release gate. Đây không phải nghiệm thu chức năng checkout/payment còn thiếu.

## D42 — CONFIRMED 23/10/2026: thẻ Xem nhanh, logo Nhà sách Thu Thanh, ảnh gốc và banner Phương Nam

Yêu cầu mới thay wordmark THUTHANH serif D40 bằng logo riêng NHÀ SÁCH THU THANH tham khảo bố cục/màu chữ trong ảnh; không dùng tên Phương Nam làm thương hiệu cửa hàng. Thẻ sản phẩm hình lớn/tên/giá/giá gốc và giảm giá khi dữ liệu thật có/XEM NHANH. Không thêm rating/review/sales-counter giả. Tăng khoảng cách giữa các khối web và Figma.

D42 thay UX F05 login-before-preview: guest được mở Xem nhanh để xem ảnh/thông tin, chỉ yêu cầu auth khi thêm giỏ/Mua ngay; giữ quantity và sản phẩm khi quay lại auth, không chồng nhiều dialog. Mua ngay thêm giỏ bằng API thật rồi tới checkout lấy báo giá; server vẫn quyết định tiền/tồn/quyền, admin không mua nhưcustomer. Một SKU demo/4mẫu mỗi category chỉ áp Figma, app vẫn136SKU.

Ảnh gốc lấy theo source_image_url của chính136SKU workbook, kiểm MIME/kích thước/same-image similarity so thumbnail, không đoán bìa khác hoặc upscale giả. Bản HD được lưu riêng và map SKU trên frontend/Figma, giữ nguyên workbook, ảnhDB/import cũ, giá/tồn/ledger. Banner lấy từ homepage Phương Nam theo yêu cầu, chọn hình biên tập không mang mức giảm giá/quyền lợi sai với Thu Thanh; lưu nguồn/hash trong ASSET-SOURCES. Logo tự tạo bằng vector riêng. Prototype/cloud publication không tự suy từ task plugin local.

Acceptance trước sửa: nguyên dữ liệu136/6/1admin/tiền/tồn; asset manifest/hash/dimensions và bìa mẫu review; nguồn banner truy vết; gallery/web có spacing rõ; guest→quickview không auth trước; add→auth→resume quantity,1dialog,Escape/close/backdrop/keyboard; buyNow checkout đúng API; no fake discount. Figma V21 native card/quick overlays,4category samples/firstSKU,link targets và quantity/payment continuity/replaceV20/rollback; browser320/390/1440,build/typecheck/client tests; docs/gói plugin self-contained. Provider/revenue/release gates giữ nguyên, không mutation schema/deploy/dev database.


## D43 — CONFIRMED 23/10/2026: Landing page riêng, flow mở ngang, footer và bản tin

Yêu cầu mới bỏ CTA Khám phá ngay (thay D40–D42), nền xám làm ngăn giữa các panel trắng; logo riêng khác chữ/màu Phương Nam. Địa chỉ cửa hàng:295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng; cập nhật web/Figma/copy hiện hành, không sửa snapshot lịch sử đơn.

Figma V22: page tên Landing page để duyệt landing desktop/mobile riêng. Các luồng trải nghiệm trên hai page prototype được trải thành hàng từ trái sang phải, từng flow có nhãn/khoảng cách,frame top-level,không còn section/bảng lớn bao tất cả. Có một bản home entry trong mỗi prototype page để link Trang chủ hoạt động vì Figma prototype không điều hướng chéo page; page Landing page là bản duyệt tĩnh, chỉ2bản thêm có mục đích. Rebuild giữ node ngoài plugin và ảnh sửa tay,không chồng bản cũ;stage/rollback toàn bộ3page. Không có link canvas thực,scope vẫn localplugin.

Footer bổ sung dải nhãn hàng tham khảo ảnh chủ dự án,logo nguồn công khai truy vết; là dải thương hiệu,không tự tuyên bố đại lý/đối tác. Không thêm badge Bộ Công Thương hoặc logo phương thức thanh toán chưa tích hợp. Hai banner bổ sung theo bố cục2ô ảnh mẫu: ưu tiên thiết kế riêng tiếng Việt bằng sách đang bán nếu chưa có lựa chọn khác từ chủ dự án. Banner hiện hành và nguồn ghi ASSET-SOURCES.

Đăng ký nhận bản tin phải lưu email thật qua Laravel;publicPOST,CSRF,throttle,email normalize,explicit consent,duplicate-idempotent và không lộ trạng thái emailđãcó. Chỉ lưu đăng ký và xác nhận đúng việc đã ghi nhận;không giả gửi mail/campaign. Không tạo tài khoản/quyền/order/payment. Backend migration thêm bảng riêng trước,không sửa bảng nghiệp vụ/dữ liệu cũ;run targetedtest/APIcontract trước khi nối UI. Scope mới user cho phép frontend này khi endpoint riêng đã verified,không nâng backend/release gate toàn dự án. Production/emailcampaign/unsubscribe delivery cần giai đoạn riêng trước khi gửi thư thực.

Acceptance trước sửa:newsletters create/normalize/duplicate/consent/unknownfield/throttle/CSRF tested;schema/docsAPI đồng bộ;add-only migration bảo toàn136/6/1admin/giá/tồn. Web có separator xám/panel trắng,không còn Khám phá ngay,logo riêng,địa chỉ footer/dải nhãn hàng/form đăng ký/banners2,guest/customer/admin/checkout unchanged;browser320/390/1440/build/typecheck/tests. Figma page Landing page,flow top-levelhorizontal/non-overlap,route/quantity/payment continuity giữ;mock/pages/manualpreserve/rollback/gallery/ZIP verified. No fake certification/provider;no deploy/reset/devimports.

D43 banner clarification confirmed by owner: dùng nguyên hai banner Phương Nam như ảnh mẫu. Thay phương án tự dựng trước đó; giữ nguyên pixel/chữ/logo trong ảnh nguồn và chữ tiếng Anh vốn có của banner New Books theo yêu cầu cụ thể này. Logo header/footer vẫn là thiết kế riêng Thu Thanh. Không tự nhập sách/giá/tồn xuất hiện trong banner.


## D44 — CONFIRMED 23/10/2026: trang chủ theo bán hàng và quản trị/doanh thu

Chủ dự án yêu cầu bỏ2link Sách giáo khoa/Văn phòng phẩm trong nav và toàn bộ khối danh mục trên landing;không xóa category hoặc sản phẩm trong database/menuDanhmục. Home chỉ các khối Bán chạy nhất,Sản phẩm mới,Gợi ý cho bạn cùng banner/footer đã chốt. Ảnh lớn hơn,nút Xem nhanh typography/căn đều. Bestseller phải xếp theo tổng quantity của order_items thuộc đơn payment_status paid có accepted receipt,không từ cờ admin/đơn unpaid/duplicate/later money. Không tự tạo số bán hoặc paidorders trên development. New theo created_at/id;recommended deterministic diversity khi guest và theo categories mua/cart riêng khi customer,không dựa data người khác hoặc tracking guest.

Owner đã chọn rõ: DOANH THU TIỀN HÀNG SAU GIẢM GIÁ;PHÍ GIAO HÀNG THỐNG KÊ RIÊNG;chỉ đơn xác nhận paid/accepted. D15 được chốt,D27 receipt allocation separation giữ. Cho phép STORE_REVENUE_POLICY=approved_v1 trên local/config/template;không mở refund writes (no_returns_no_refunds vẫn giữ),không gộp duplicate/late/mismatch receipts thành sales và không bật provider thật. Báo cáo có khoảng ngày/nhóm ngày-tuần-tháng và CSV;zero periods không bịa sales. Thống kê là giao dịch online của hệ thống,không toàn bộ doanh thu cửa hàng/POS.

Hoàn thiện admin trong phạm vi hiện có:entry /admin và hướng dẫn login chung/serverrole;không resetpassword hoặc tạo admin mới trêndev. Create/update/images có kiểm tra và báo lỗi;thêm DELETE logical archive có version/lock/idempotence,giữ record/snapshot/stock history,không harddelete. Statusfilter/restore giúp quản lý hàng ngừng bán. Thêm quản lý category,revenue và đối soát trên API hiện có;không làm fakepaid/manualbankapproval hoặc newrefund. Backendranking/archive/report tests phải đạt trước frontend chức năng mới. No schema reset/import/deploy/owner credential dump.

Acceptance:paid ranking đúngquantity/ẩninactive/no doublecount;new/recommended đúngfilter/permission;archiveadmin-only/conflict/repeat/historypreserve;revenue ranges/zero-fill/dateVN/fees separate and legacy tests;roleauth/catalog CRUD/image/category/stock/order/revenue/reconcile browser QA isolatedDB. Finalbuild/typecheck/tests/docs synced;136prices/stocks/devadmin unchanged. Figmahome/nav/card/report source phù hợpscope,localpluginmock/ZIP verified if changed;provider/release gate còn chưa passed.

D44 triển khai tiện ích local `admin:account` chỉ đọc tên đăng nhập và `admin:password` phục hồi tương tác khi người dùng tự chạy, xác nhận và nhập mật khẩu hai lần. Không chạy phục hồi trên dev của chủ dự án trong phiên này; không có mật khẩu mặc định. Lệnh phục hồi từ chối production/noninteractive và thu hồi session admin khi người dùng thực hiện.


## D45 — CONFIRMED 23/10/2026: giá giảm, dữ liệu demo và chuẩn bị scale

Chủ dự án yêu cầu bỏ hai chữ/link Thương hiệu và Quản trị nhà sách tại footer, giữ logo nhãn hàng; thêm biểu tượng lửa cho Bán chạy nhất. New tiếp tục created_at/ID giảm dần; bestseller chủ dự án xác nhận xếp tổng số lượng đã bán, không theo tiền. Sau khi hỏi nguồn lịch sử thật, chủ dự án đổi yêu cầu sang tự tạo random: cho phép tạo khoảng1000đơn ở database demo local riêng, không sửa/trộn paid sales/stock/prices/customers trong thu_thanh thật và không đưa demo vào production. Không mở VNPAY hoặc fake paid endpoint trên web. Demo command phải có guard tên DB+môi trường, idempotent và có manifest nhận biết synthetic/provider demo; testFixtures tách DBtest.

Thêm phần trăm giảm giá khi tạo/sửa sản phẩm, tính sale_price server bằng số nguyên VND/làm tròn gần nhất; không tự giảm giá136sản phẩm hiện có. Không nhận đồng thời discount_percent và sale_price khác nguồn. API trả phần trăm được suy ra từ giá đang lưu; thẻ/popup thể hiện giá bán, giá gốc gạch ngang và badge giảm khi có giảm thật. Giá/tổng/quote/version/stock vẫn backend quyết định.

Mục tiêu10k người đồng thời là mục tiêu nghiệm thu hạ tầng, không mặc định10k checkout/RPS. Được tối ưu source, đọc public/cache/sharedRedis, truy vấn bounded, productionbuild/PHP-FPM/opcache/reverseproxy/CDN và thêm load harness/profiles. Không tự mua/provision/deploy hạ tầng hoặc tuyên bố10k đạt từ testlocalhost. Chỉ cache dữ liệu công khai; auth/cart/checkout/order/admin/personalized home/session/CSRF phải private/no-store và không cache Set-Cookie. Các mutation stock/price/order vẫn kiểm server; cache có TTL và invalidation sau commit. Test trước/sau đo query/response/latency trên môi trường cụ thể.

Dọn repo bằng inventory/reference audit; loại bản build/import/doc trùng đã thay thế khỏi vùng source hoạt động bằng archive có manifest/hash và hướng dẫn khôi phục. Không xóa env,DB,workbooknguồn,uploads,evidence,AGENTS/checkpoints/specs còn cần,dependencies hoặc userartwork. Không đổi tên hàng loạt source đang dùng. Acceptance: discount money edgecases/quote consistency/permissions; public cache isolation/invalidation/bounded recommendations; 1000demo receipts/order quantities totals/idempotence/guard; browser user/admin/responsive/errors; actual load smoke measured only at executed concurrency; finalbuild/typecheck/tests/docs/checkpoints. Backend related tests trước UI mới,provider/release gate giữ nguyên.


## D46 — CONFIRMED 23/10/2026: dọn tài liệu Markdown

Chủ dự án yêu cầu xóa các file Markdown không cần thiết. Loại review Figma cũ và prompt bootstrap/lab đã thay thế, gộp frontend verification vào VERIFICATION; giữ AGENTS/checkpoints/specs/guide hiện hành/audit môn học/nguồn asset. Archive ngoài repo, kiểm SHA/CRC trước xóa và sửa tham chiếu/check_pack. Không đổi source ứng dụng, database, env, workbook, upload hoặc package Figma. Acceptance: chỉ xóa danh sách đã audit, khôi phục được từng file, không còn link Markdown tới file đã xóa, kiểm tài liệu đạt. Chủ dự án xác nhận chưa có hạ tầng deploy; 10k vẫn chưa nghiệm thu.


## D47 — CONFIRMED 24/10/2026: bổ sung GitHub Project #4

Chủ dự án yêu cầu kiểm tra/bổ sung tất cả task theo master schedule đính kèm vào Project DongDao27/4. Repo đã xác minh DongDao27/Thu-Thanh-bookstore, có T01–T07 và năm milestone; thiếu T08–T18/MASTER. Được tạo issue thiếu, nối phụ thuộc, gắn milestone và thêm vào board; không tạo trùng hoặc đóng issue khi chưa nghiệm thu. Scope lịch cũ được đối chiếu D44–D46, không bật payment/deploy. Tiêu chí đạt: mỗi T01–T18 đúng một issue và có trong Project, MASTER đủ link, milestone đúng, trạng thái/checkbox cũ giữ nguyên; kiểm API sau mutation. Hiện gh token thiếu project, đang yêu cầu chủ tài khoản xác thực device flow.


## D48 — CONFIRMED 24/10/2026: commit/push toàn bộ source vào Payment

Chủ dự án chỉ định https://github.com/DongDao27/Payment làm nơi nhận source. API/ls-remote xác nhận repo đích trống và quyền ADMIN; local main chưa có commit/remote. Được tạo commit, origin và push main không force. Repo issue Thu-Thanh-bookstore/Project4 giữ nguyên. Chỉ commit source/assets/workbook/spec/tests/templates; không đưa env/keys/database runtime/vendor/node_modules/generated build vào Git. Acceptance: kiểm stagedfiles secrets/runtime/size, kiểm tra thích hợp, commit có hash thật, remote main trùng HEAD sau push, working tree sạch. Không deploy hoặc bật payment thật.


## D49 — CONFIRMED 24/10/2026: hiện trạng và hướng dẫn hai người đóng góp

Chủ dự án yêu cầu đề xuất cải thiện, liệt kê task đã làm và file hướng dẫn chia việc/commit trên hai máy; xác nhận tên Đông, Thiện. Phân công 9task/36điểm mỗi người là đề xuất trách nhiệm tương lai, chưa có username Thiện hoặc xác nhận công sức lịch sử. Không viết lại commit nền/author/ngày hoặc giả PR/đóng task. Source Payment, issue Thu-Thanh-bookstore/Project4, dùng fullURL để tham chiếu chéo repo. Acceptance: đủ18task hiện trạng có giới hạn rõ, bảng phân công cân bằng/reviewchéo, hướng dẫn identity/branch/stage/test/PR/merge/conflict/secrets và evidence thực; link/mapping/docs kiểm được. Không tự gán người/merge/push hoặc thay code ứng dụng.


## D50 — CONFIRMED 24/10/2026: đánh giá thiết kế trước mở bán

Chủ dự án yêu cầu đánh giá chuyên nghiệp và đề xuất sửa để dùng thật sau deploy. Scope đánh giá public browser/source/priorQA, không sửa UI/nghiệp vụ/Figma hoặc mua/deploy hạ tầng. Acceptance: chỉ rõ bằng chứng quan sát, ưu tiên/ảnh hưởng/tiêu chí đạt, phân biệt đề xuất với yêu cầu đã chốt; không nhận đạt provider/10k/WCAG từ local. Dùng banner riêng, giảm sốitem hoặc productURL là đề xuất cần duyệt, không tự thay D43/D44.


## D51 — CONFIRMED 24/10/2026: liên hệ/bản đồ, UX07/08 và admin chart

Chủ dự án cung cấp linkGoogleMaps địa chỉ295TônĐứcThắng,hotline0915466951,xác nhận07:30–21:30hằngngày. Yêu cầu mapdướifooter,UX07hitarea/focus/label,UX08recoveryvàđánhgiá/chỉnhadminicons/chart. Scope mới cho phép backend recovery trước UI: email broker Laravel, token hash/expire/oneuse/lock/sessionrevoke,customeronly,CSRF/rate/genericrequest;khôngSMSgiả/adminpublicreset. No schema mutation cần thiết nếu password_reset_tokens hiện hữu. EmailproductionphảiSMTPthật;localmailer chỉ development,khôngclaimdelivery. Backendtests/contracttrướcfrontend mới;UI maplazy/contact/passwordvisibility/inlineerrors,adminuniqueicons/chartaxis/filter/tooltipresponsive/dataempty. Acceptance bằng test/backend/APIcontract,frontendbuild/typecheck,browserisolatedDB/admin/guest/mobile/accessibility/map;khôngđổitổngdoanhthu,quyền,price/stock/provider/no-refund.

## D52 — CONFIRMED 24/10/2026: báo cáo Excel Đông/Thiện, 11–31/10/2026

Chủ dự án yêu cầu báo cáo phần trăm task GitHub và kế hoạch ba tuần; chốt ngày 11–17/10, 18–24/10, 25–31/10/2026. Scope tạo workbook và đọc GitHub; không thay trạng thái issue/Project, không commit/push hoặc deploy. Giữ phân công đề xuất D49: mỗi người 9 task/36 điểm, review chéo; không suy thành tác giả lịch sử. Tỷ lệ GitHub dựa trên issue đóng với lý do hoàn thành, tách rõ code local/chờ nghiệm thu. Hai tuần đã qua chỉ ghi bằng chứng có thật; lịch phân bổ là khung đối chiếu lập ngày24/10, không giả là kế hoạch đã chốt từ11/10. Tuần cuối là mục tiêu, provider/hosting/SMTP thật còn điều kiện ngoài. Acceptance: đủ18 task/link đúng, ba khoảng tuần đúng, tổng điểm cân bằng, công thức tỷ lệ có cached values, bảng tuần/in ấn dễ đọc, đối chiếu snapshot GitHub và kiểm cấu trúc XLSX bằng trình đọc độc lập. Không chạy lại application tests khi không đổi ứng dụng.

## D53 — CONFIRMED 24/10/2026: dọn repo, cập nhật README và commit toàn bộ thay đổi

Chủ dự án yêu cầu bỏ tài liệu đánh giá/đề xuất, hồ sơ test thừa và phần fetch dữ liệu không còn cần, cập nhật README rồi commit tất cả thay đổi. Dọn script tải ảnh/import workbook một lần, fixture browser thủ công cũ, snapshot/ảnh QA và audit trùng; giữ API fetch đang phục vụ web, bộ test tự động/CI, schema/domain/API, hướng dẫn chạy/deploy, nguồn ảnh, phân công và báo cáo Excel vừa yêu cầu. Lưu file bỏ trong ZIP ngoài repo, kiểm đường dẫn tuyệt đối/CRC/SHA trước khi xóa bản trong repo; manifest để truy lại chứng cứ lịch sử. Không xóa database, env, uploads, ảnh web/Figma, workbook, dữ liệu demo hoặc test nghiệp vụ.

Acceptance: danh sách dọn rõ, archive khôi phục đúng byte, không link hoạt động trỏ file đã bỏ, README đúng phiên bản/chức năng/contact/SMTP/giới hạn thật; kiểm tài liệu/API, backend tests/Pint/HTTP+ops smoke và frontend tests/build/typecheck trước commit. Không thêm tối ưu suy đoán hoặc đổi nghiệp vụ; giữ toàn bộ chức năng D51. Commit gồm source chưa commit D49–D52 và cleanup hiện tại, loại secret/runtime/dependencies; kiểm staged diff và hash thật, working tree sạch. Lượt này chỉ được yêu cầu commit, không tự deploy/mua hạ tầng hoặc bật provider thật.

## D54 — CONFIRMED 24/10/2026: tick checklist và đồng bộ trạng thái Project GitHub

Chủ dự án yêu cầu tick từng phần đã hoàn thành và chuyển task vào đúng trạng thái Project4. Được sửa body/checklist và Status các issue T01–T18/MASTER thuộc Thu-Thanh-bookstore; không tạo PR/review/tác giả giả hoặc tự gán username Thiện. Nguồn source Payment/main63d84ad, CI Backend/Frontend đều success đã đọc trực tiếp; local118backend/7client là kết quả D53, không chạy lại rồi gọi mới. Chỉ tick phần có source/tests/bằng chứng; ghi rõ adapter/fixture local khác merchant/provider thật. Dev done cho phần triển khai local đã đủ; In progress cho phần còn thiếu code hoặc nghiệm thu/canvas/SMTP/provider/staging/user testing. Không dùng In review/Merge Main để giả có PR/review; Closed chỉ khi acceptance và review thật đạt. MASTER phản ánh từng status/checklist và lịch ba tuần11–31/10 đã chốt D52, không hồi tố kết quả.

Acceptance: snapshot trước mutation, so sánh body/updatedAt ngay trước ghi để tránh đè sửa đồng thời; giữ checklist ngoài phạm vi, assignee/milestone/item cũ; thêm link source/test/CI và việc còn thiếu. Kiểm API sau ghi từngissue và tổng thể18task+MASTER, tất cả phần review/provider chưa đạt giữ unchecked. Inventory chưa có giá trong bảng và admin order chưa hiển thị lịch sử thanh toán phải ghi thiếu, không tick cả mục ghép. Không viết source ứng dụng, không đổi backend/release gate hoặc tạo thêm báo cáo QA rời trong repo vừa dọn.
