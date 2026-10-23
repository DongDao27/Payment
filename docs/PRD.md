# PRD Thu Thanh 2.0 — backend trước
Ngày 21/10/2026. Bộ này thay định hướng khởi đầu “một vertical slice frontend/backend” trong PRD 1.0 bằng triển khai full backend trước. Không sửa/xóa file PRD cũ tự động.

## Bối cảnh và giá trị
Nhà sách gia đình bán truyền thống; khó nhớ giá/tồn và tổng hợp doanh thu. QR chưa điền sẵn số tiền, chờ thiết bị báo tiền về làm phục vụ chậm.
Mục tiêu: mở kênh e-commerce, tra giá/tồn, đặt trước để chuẩn bị hàng, ghi nhận giao dịch và doanh thu của hệ thống.
Đặt trước giảm thời gian chọn hàng/tính tiền, không bảo đảm không xếp hàng hoặc ngân hàng luôn cập nhật ngay.
Không có POS khách vãng lai trong MVP: báo cáo chưa phải toàn doanh thu cửa hàng, không phải phần mềm kê khai thuế.

## Vai trò
Guest: duyệt/tìm sản phẩm. Customer: đăng ký, đăng nhập, giỏ, đặt, trả tiền, chỉ xem đơn mình. Admin: một tài khoản được cấp, chỉ login, quản lý catalog/tồn/đơn/QR/report.
Một form đăng nhập chung; không public admin registration/promotion. Backend luôn kiểm quyền và ownership.

## Catalog
Sáu danh mục: Sách giáo khoa 1–12; Sách văn học; Văn phòng phẩm/dụng cụ học sinh; Đồ chơi; Truyện tranh; Quà lưu niệm.
grade 1–12 là thuộc tính của sách giáo khoa. Trang chủ có Sách đang hot, Best seller, Sản phẩm mới và footer.
Giao diện theo Figma thật. Mặc định dùng card → popup số lượng, không phát sinh màn chi tiết sản phẩm độc lập; API đọc thông tin sản phẩm vẫn cần cho popup.
Ảnh thật có quyền sử dụng; seed/demo tách rõ. Không tạo giá/tồn giả để thay dữ liệu thực.

## Chức năng và tiêu chí
| Mã | Yêu cầu |
|---|---|
| F01 | Trang chủ, danh mục, hot/bestseller/new/footer |
| F02 | Danh mục và danh sách, empty/error/loading |
| F03 | Search theo tên, query whitelist, sort/pagination |
| F04 | Dữ liệu sản phẩm đủ cho card/popup; không yêu cầu route UI riêng |
| F05 | Xem nhanh mở popup ảnh/thông tin/số lượng, nền tối; guest được xem. Khi thêm giỏ/Mua ngay mới login rồi trở lại đúng sản phẩm/số lượng; đóng không đổi giỏ (D42) |
| F06 | Register bằng email hoặc phone; chuẩn hóa, unique, role customer |
| F07 | Login chung, logout, me; admin/customer phân quyền server |
| F08 | Giỏ nhiều món, cộng/tăng/giảm/xóa, chặn trống; chưa giữ tồn |
| F09 | Checkout delivery/pickup, tên/phone, địa chỉ khi giao; báo đủ tổng/phí |
| F10 | Requote/kiểm tồn/transaction/snapshot/idempotent create, giữ 5 phút/24 giờ |
| F11 | Delivery VNPAY online, verified result, pending/failed/success/retry |
| F12 | Pickup đặt trước có mã đơn, chưa thanh toán, hạn 24 giờ |
| F13 | Admin mở yêu cầu QR đúng đơn pickup còn hiệu lực, không thu lặp |
| F14 | Khách xem list/detail đơn riêng và trạng thái |
| F15 | Hủy unpaid hợp lệ, expiry, release một lần, đặt lại với giá/tồn mới |
| F16 | Callback lặp/sai thứ tự, nhiều attempts, late money và đối soát |
| F17 | Tổng quan/report theo thời gian, dữ liệu từ giao dịch verified |
| F18 | Admin list/search/filter sản phẩm |
| F19 | Thêm category/giá/tồn ban đầu/ảnh theo quyền |
| F20 | Sửa/ẩn/ngừng bán; không mất snapshot đơn cũ |
| F21 | Chọn danh mục → tồn còn lại/đang giữ/có thể bán; adjustment có lý do |
| F22 | Admin orders/search/filter/detail/fulfillment/QR/audit |

## Luồng
D42: Guest xem card → Xem nhanh → popup sản phẩm/số lượng → Thêm vào giỏ/Mua ngay → chưa login thì AuthDialog → trở lại popup giữ sản phẩm/số lượng → xác nhận thêm giỏ → giỏ hoặc checkout. Chỉ một dialog; Quay lại/Escape không thay đổi giỏ.
Delivery: thông tin/quote → tạo đơn giữ 5 phút → provider → verified payment → preparing → shipping → fulfilled.
Pickup: tên/phone → tạo đơn giữ 24 giờ → mã đơn + chưa trả tiền → preparing/ready → đến cửa hàng đưa mã → admin QR → verified → nhận → fulfilled.
Thất bại giao dịch không tự hủy đơn. Retry không reset hạn. Callback uncertain hiển thị đang xử lý/chờ đối soát. Hết hạn/hủy chỉ release sau xác nhận chưa thanh toán.

## Ngoài scope
POS khách mua trực tiếp, Google login, marketplace/multi-admin, review/rating, tự động thuế/hóa đơn, voucher engine, tích hợp hãng vận chuyển, tự động refund, native app, realtime socket.
Fortify profile/2FA/token manager của lab là kiến thức tham khảo, không tự thêm cả cụm vào MVP. Password reset email là extension đã lên kế hoạch, chưa tuyên bố phone reset có OTP.

## Chất lượng
- Không giả vờ chạy app/test/VNPAY khi chưa chạy.
- Quyền API, CSRF, input/upload, secrets và PII được kiểm thử.
- Giá/tổng VND integer, transaction/idempotency và concurrent orders có test MySQL.
- State, totals và constraints giữ đúng cả khi callback/cron cùng chạy.
- Figma đúng frame/responsive, keyboard/focus/validation/loading/empty/error.
- Metrics thực nghiệm: completion time/error rate sau user test; chưa đặt số KPI hoặc cam kết tăng doanh thu khi chưa đo.
- Handoff mỗi phiên bắt buộc; tài liệu mâu thuẫn phải sửa trước code.

## Cập nhật được chủ dự án xác nhận — 21/10/2026

- Delivery mặc định25.000đ/đơn, pickup phí giao0đ; server là nguồn tính tiền.
- Không đổi trả/hoàn tiền. Hủy đơn chưa thanh toán theo F15 vẫn giữ; dữ liệu tiền bất thường/ledger cũ vẫn giữ cho đối soát.
- VNPAY Sandbox/QR thật dời sang giai đoạn sau theo D31; không giả paid hoặc tự thêm COD/manual paid. Hiện chặn tạo yêu cầu thanh toán mới bằng503 PAYMENT_INTEGRATION_DEFERRED.
- Có source prototype Figma local tại thu-thanh-store-v12-free; cần audit các màn vượt phạm vi và kiểm canvas trước khi tuyên bố giao diện khớp.
- Phạm vi backend-first và tiêu chí provider thực tế khi nghiệm thu release vẫn được giữ. Lượt này audit/cập nhật backend, không xây Next.js.


Cập nhật D34 (22/10/2026): chủ dự án xác nhận yêu cầu giảng viên Next.js frontend + Laravel backend, cho phép bắt đầu frontend dựa backend local B00–B06 trong lúc VNPAY/revenue deferred. Quy tắc khóa frontend trước B10 ở trên được thay bằng ngoại lệ phạm vi này; gate backend nghiêm ngặt/release không passed. D33 stack conflict đã giải quyết, các thiếu sót rubric khác vẫn giữ.


Cập nhật D39 (23/10/2026): bỏ dải bốn ô dịch vụ, footer trắng theo cột tham khảo ảnh Fahasa; nội dung/brand Thu Thanh, không đổi trả/hoàn tiền. Không công bố số phí vận chuyển tại browse/cart/footer; hiển thị tại checkout và chứng từ đơn. Figma được hoàn thiện luồng QR/kết quả mẫu ngay; code tích hợp payment vẫn dời sau. Các trạng thái thanh toán cho phép tăng8 màn so giới hạn D38, vẫn4 mẫu/danh mục/1SKU demo và app đủ136SKU. Xem DECISIONS D39.


D40 (23/10/2026) thay brand lowercase bằng THUTHANH serif đậm nghiêng. Bản Figma có admin login/dashboard counts và payment outcomes/receipt đầy đủ; dọn chú thích khỏi commerce screens, chọn kịch bản trong plugin UI. Chỉ banner đầu có Khám phá ngay, chữ Trang chủ quay về home. Không kích hoạt payment/report thật hoặc thay quyền server. Xem MD-CLEANUP-MANIFEST.json.


D41: cập nhật các route web hiện có theo V20, giữ đầy đủcatalog và luậtserver. /admin có thống kê vận hành thật (counts/chart7ngàyVietNam/recentorders) không phải revenue. Ngày thống kê theo Asia/Ho_Chi_Minh như filter adminorders, timestampsUTC. Hot/bestseller chỉ hiện khi admin đã gắn cờ có sản phẩm, không tạo xếp hạng giả. Release/provider/revenue/adminspecialized còn scope, không nâng gate. Hướng dẫn CHAY-WEB-VA-LARAVEL.md.


D43 (23/10/2026): bỏ Khám phá ngay, gray separators/white panels, logo riêng Constantia/book mark, footer8nhãn hàng và địa chỉ295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng. Giữ nguyên2banner Phương Nam chủ dự án chọn (kể cả chữ/logo trong ảnh); đây là ngoại lệ asset ảnh theo yêu cầu cụ thể, UI còn lại tiếng Việt. Bản tin public thu email có consent/CSRF/throttle/normalize/unique-idempotent, không tự gửi campaign. Backend/table riêng verified trước UI, không đổi tiền/tồn/quyền/payment. Figma Landing page duyệt riêng,prototype2page có home entry để giữ điều hướng hợp lệ;flow top-level hàng ngang ngoài board. Xem D43 và MD-CLEANUP-MANIFEST.json.


## D44 / V23 — yêu cầu hiện hành

D44 thay cách home gắn cờ/danh mục ở D41: chỉ Bán chạy nhất theo số lượng từ đơn paid/accepted, Sản phẩm mới, Gợi ý cho bạn. Bỏ hai link SGK/VPP trên navbar, giữ menu/catalog/dữ liệu. Card ảnh lớn hơn, crop viền trắng an toàn khi hiển thị, nút Xem nhanh căn đều. Doanh thu được chủ dự án chốt: tiền hàng sau giảm giá; phí giao hàng riêng. Admin có thêm archive/restore, status filter, danh mục, doanh thu (range/group/CSV), đối soát, hướng dẫn vào admin và phục hồi mật khẩu local tương tác. Không mở provider, hoàn tiền, POS hoặc nâng quyền khách. Figma V23 có 126 frame/3 page/7 flow mỗi nền tảng, 24 SKU mẫu; web giữ đầy đủ catalog.


## D45 / V24 — thay đổi hiện hành

Footer bỏ chữ Thương hiệu/link Quản trị nhà sách, giữ8logo. Bán chạy có lửa và xếp tổng số lượng paid/accepted. Sản phẩm mới là12sản phẩm được tạo gần nhất đang bán/danh mục active. Admin nhập phần trăm giảm, server persist sale_price, hiển thị giá gốc gạch ngang/badge/giá bán nhất quán card-popup. Không tự giảm giá catalog thật.

Theo yêu cầu mới nhất, dữ liệu1000đơn là random trong DBdemo riêng. Mục tiêu10kconcurrent là tiêu chí hạ tầng chưa nghiệm thu, không chuyển thành tuyên bố đã đạt từ loadlocalhost. Source/publiccache/boundedqueries/productionconfigs/profiles được chuẩn bị; không cacheprivate/session/checkout. Cleanup có archive kiểmCRC/SHA, giữsourceworkbook/DB/env/uploads/tài liệu cần bảo trì.
