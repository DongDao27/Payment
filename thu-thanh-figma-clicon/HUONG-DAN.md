# Nhà sách Thu Thanh · Figma V24

## Import và mở đúng bản

1. Giải nén `thuthanh-figma-clicon-v24-2026-10-23.zip` vào thư mục riêng.
2. Figma Desktop → Plugins → Development → Import plugin from manifest… → chọn **manifest.json ở gốc thư mục vừa giải nén**.
3. Đóng plugin cũ, chạy **THUTHANH · Clicon V24**, chọn kịch bản thanh toán rồi bấm tạo. Chờ báo hoàn tất.
4. Plugin mở trang **Landing page**. Đây là trang duyệt giao diện riêng, có bản máy tính và điện thoại đặt cạnh nhau.
5. Muốn chạy prototype: dùng nút Máy tính/Điện thoại và chọn flow trong UI plugin, rồi Present. Hai trang prototype giữ các màn theo từng hàng từ trái sang phải, nhãn flow nằm ngoài frame, không có bảng/section lớn bao quanh.

**Canvas cũ không tự thay đổi khi code được cập nhật: phải chạy lại builder.** Có thể import trực tiếp `D:\ThuThanh-WebDesign\thu-thanh-figma-clicon\manifest.json`. ID vẫn `thu-thanh-clicon-v16` để thay bản trước; không chọn manifest trong thư mục backup/lồng cũ. Không cần extension VS Code hoặc Python để import.

Figma không hỗ trợ prototype điều hướng chéo page. Vì vậy trang Landing page là bản duyệt tĩnh; mỗi trang prototype có một home entry riêng để logo/Trang chủ quay về đúng landing và chạy demo đầy đủ. Chỉ hai bản duyệt bổ sung có mục đích, không nhân trang chi tiết theo từng SKU.

## Những phần hiện hành

- Logo riêng **Nhà sách Thu Thanh**: chữ Constantia dạng outline và biểu tượng quyển sách, một màu; khác chữ SÁCH nhiều màu của Phương Nam. Không cần cài font logo.
- Nền xám ngăn giữa các khối trắng. Bỏ toàn bộ nút/chữ Khám phá ngay.
- Giữ banner manga và thêm **đúng hai banner Phương Nam chủ dự án chọn**: Sách mới tháng này, New Books. Giữ nguyên ảnh/chữ/logo trong ảnh nguồn, không biến thành logo nhà sách hoặc tự nhập sản phẩm trong banner.
- Footer có8nhãn hàng: Thiên Long, Faber-Castell, KEYROAD, Maped, MILAN, Pentel, Pilot, BITEX; form bản tin và địa chỉ **295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng**. Không gắn badge chứng nhận hoặc phương thức thanh toán chưa có.
- Thẻ Xem nhanh mở popup ảnh lớn/giá/tồn/số lượng trước khi đăng nhập. Khi mua mới đăng nhập rồi trở lại giữ số lượng để xác nhận. Chỉ1overlay tại một thời điểm: đổi quantity dùng SWAP, đóng dùng CLOSE.

## Demo và phạm vi

Mỗi nền tảng có62màn prototype/7flow; trang Landing page thêm2frame duyệt, tổng126frame trên3page. Các màn đặt riêng ngoài board, khoảng cách ngang tối thiểu320px. Home viewport vẫn cuộn. 24sản phẩm,4mỗi danh mục; chỉ cuốn Toán1 TT-SGK-002 chạy demo mua, số lượng1–2. Website thật đủ136SKU.

Đăng nhập có nhánh khách/quản trị; app thật dùng login chung và quyền Laravel. Đăng ký bản tin trong Figma chỉ là mô phỏng trạng thái, không gửi email hoặc gọi API.

Thanh toán: giao1cuốn39.000đ, giao2cuốn53.000đ, nhận1cuốn14.000đ. QR→chờ xác minh→kết quả, có5kịch bản: thành công/thất bại/chưa có kết quả/hết hạn/đối soát. Receipt đã trả tiền không có hủy unpaid; chỉ retry khi thất bại xác định còn hạn. Unknown không retry. Đổi kịch bản ở UI rồi dựng lại. QR là văn bản vô hại `THUTHANH DEMO ONLY ... KHONG CHUYEN TIEN`, không phải QR ngân hàng; VNPAY thật vẫn hoãn.

Rebuild thay node plugin V17–V24 sau khi cả3page hoàn tất. Giữ node ngoài plugin và ảnh sửa tay, kể cả ảnh sửa tại Landing page, qua nhiều lần dựng lại. Node mới được đặt tránh vùng ghi chú của chủ dự án. Chữ/layout sửa tay trong node plugin sẽ dựng lại: lưu bản sao trước. Lỗi/hủy giữ bản trước; không xóa toàn page.

## Gói nhẹ và source

ZIP V24 chỉ chứa6file cần chạy/đọc: manifest,code,UI,hướng dẫn,license và danh sách luồng. Toàn bộ ảnh được nhúng trong code nên plugin chạy offline. Source build và60SVG xem trước giữ trong repo tại `src/`, `build.py`, `xem-truoc/index.html`, giúp gói import nhỏ hơn bản V21. Người import không cần build.

Mock kiểm cấu trúc/geometry/luồng/quantity/tiền/5kịch bản3nhánh/ảnh manual/rollback3page; SVG là bản xem trước tĩnh. **Chưa kiểm trên canvas thật của chủ dự án**: sau import cần Present để duyệt font, wrapping và hotspot thực tế. Không gọi mock là pixel-perfect hoặc provider thật đã chạy.

## V24 — home và doanh thu

Home chỉ có Bán chạy nhất, Sản phẩm mới, Gợi ý cho bạn; không chia thành các khối danh mục. Navbar bỏ SGK/VPP, logo/Trang chủ về landing. Card tăng chiều cao ảnh, chữ Xem nhanh cân giữa. Flow quản trị thêm màn Doanh thu, tách tiền hàng sau giảm giá và phí giao hàng; prototype hiển thị trạng thái chưa có doanh thu. Các sản phẩm trên home Figma là fixture thiết kế, không phải kết quả kinh doanh; ứng dụng thật chỉ xếp hạng bằng đơn đã trả tiền. Giữ 24 SKU catalog/4 mỗi danh mục và chỉ SKU TT-SGK-002 chạy demo mua. Không áp giới hạn demo vào web. Gói import 6 file, gallery 60 SVG nằm ở repo.


## D45 — V24

Footer bỏ chữ Thương hiệu, giữ8logo. Bán chạy có biểu tượng lửa. Card có giá gốc gạch ngang/badge trên sản phẩm có giá giảm từ dữ liệu demo riêng; giá workbook gốc vẫn giữ để đối chiếu. Chỉ SKU TT-SGK-002 chạy mua,giá14.000đ và tổng/QR không đổi. Form quản trị có phần trăm giảm. Website thật tính giá server và có1000đơn ở môi trườngdemo riêng; Figma không tạo giao dịch/provider.
