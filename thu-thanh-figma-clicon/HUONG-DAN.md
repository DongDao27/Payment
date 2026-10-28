# Nhà sách Thu Thanh · Figma V25

## Import bản mới

1. Giải nén `thuthanh-figma-clicon-v25-2026-10-28.zip` vào thư mục riêng.
2. Figma Desktop → Plugins → Development → Import plugin from manifest… → chọn **manifest.json ở gốc thư mục vừa giải nén**.
3. Đóng plugin cũ, mở **THUTHANH · Clicon V25**, chọn kịch bản thanh toán và bấm **Tạo bản demo gọn**.
4. Plugin mở trang **Landing page**, gồm hai bản duyệt máy tính/điện thoại đặt cạnh nhau. Dùng nút Máy tính/Điện thoại và chọn flow trong UI plugin để Present prototype.

Có thể import trực tiếp `D:\ThuThanh-WebDesign\thu-thanh-figma-clicon\manifest.json`. ID `thu-thanh-clicon-v16` giữ ổn định để nâng cấp; tên hiển thị phải là **V25**. Canvas không tự cập nhật khi source đổi: cần chạy lại builder. Không cần extension VS Code hoặc Python để import.

## Đồng bộ web D57–D58

- Ưu tiên font **Nunito Sans** như web; nếu Figma không cung cấp font này, plugin dùng Inter/Roboto/Noto Sans/Arial. Logo Thu Thanh là vector outline, không cần cài font logo.
- Header mua sắm dùng banner văn học không logo Fahasa. Admin có header riêng, không banner hoặc footer mua sắm.
- Giữ ba ảnh Phương Nam đã duyệt. Thêm banner manga giữa Bán chạy nhất/Sản phẩm mới và banner thiếu nhi giữa Sản phẩm mới/Gợi ý cho bạn; mobile có ảnh thiếu nhi riêng. Không thêm Khám phá ngay hoặc khối danh mục vào landing.
- Footer chỉ một khối liên hệ và **map 300 × 164 dưới logo**: **295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng**, **0915 466 951**, **07:30–21:30 hằng ngày**. Giữ bản tin, bốn mục chính sách và tám nhãn hàng; bỏ nội dung trùng.
- Nút **Lên đầu trang** 48px, bán trong suốt, ở hàng bản quyền có tương tác **Scroll to** đầu màn hiện tại. Figma thể hiện trạng thái dock ở footer; web tự hiện/ẩn nút nổi theo vị trí cuộn và tôn trọng reduced motion.
- Khung, chữ, logo, nút, giá và card vẫn là layer chỉnh sửa được. Chỉ ảnh sách/banner/map là ảnh; không dùng screenshot toàn trang làm thiết kế.

Ảnh nhúng giúp plugin chạy offline. Map là ảnh nội dung Google Maps cùng pin trên web, giữ hiển thị nguồn; nhấn map/địa chỉ mở Google Maps. Banner không đổi giá, tồn hoặc luật ưu đãi của Thu Thanh.

## Bố cục và demo

Giữ **3 page, 126 frame**: 124 màn prototype và 2 frame duyệt Landing page; mỗi nền tảng 62 màn/7 flow. Màn nằm riêng, theo hàng từ trái sang phải, nhãn flow ở ngoài frame, cách nhau ít nhất 320px; không có board/section bao tất cả. Figma không điều hướng chéo page nên mỗi nền tảng vẫn có một home entry để logo/Trang chủ quay về đúng landing.

Catalog có **24 SKU, 4 mỗi danh mục**; chỉ **Toán 1 TT-SGK-002** chạy demo mua, số lượng 1–2. Website thật vẫn đủ 136 SKU. Xem nhanh trước đăng nhập; khi mua mới đăng nhập rồi trở lại giữ số lượng. Chỉ một overlay cùng lúc; quantity dùng SWAP, đóng dùng CLOSE.

Phí ship chỉ hiện khi thanh toán/đơn. Demo: giao 1 cuốn 39.000đ, giao 2 cuốn 53.000đ, nhận 1 cuốn 14.000đ. Năm kịch bản: thành công, thất bại, chưa có kết quả, hết hạn và đối soát; không hủy đơn đã trả tiền, không thử lại khi chưa xác định kết quả. QR là văn bản vô hại `THUTHANH DEMO ONLY ... KHONG CHUYEN TIEN`, không phải QR ngân hàng. Web có code VNPAY Sandbox; giao dịch merchant thật vẫn cần nghiệm thu riêng.

Bản tin và thống kê trong Figma là trạng thái thiết kế, không gọi API, gửi email hoặc tạo giao dịch.

## Dựng lại an toàn và kiểm tra

V25 nhận diện node plugin V17–V25 và chỉ thay bản cũ sau khi cả ba page dựng xong. Giữ node ngoài plugin và ảnh sản phẩm sửa tay, kể cả tại Landing page, qua nhiều lần dựng. Hủy/lỗi giữ bản trước. Chữ/layout sửa tay bên trong node plugin sẽ dựng lại: lưu bản sao trước khi chạy.

ZIP chỉ có **6 file** cần import/đọc. Source ở `src/`, `build.py`; 60 SVG xem trước ở `xem-truoc/` được tạo bởi `node scripts/test-figma-clicon.cjs` từ gốc repo. ZIP không chứa SVG để giữ gọn.

Đã kiểm bằng mock API: cấu trúc/layer bounds, flow không chồng, banner/footer, Scroll to/map URL, 24 SKU, tiền/số lượng, 5 kịch bản thanh toán, ảnh sửa tay, nâng cấp V24, rollback/hủy trên 3 page. **Canvas thật của chủ dự án chưa được thao tác trong phiên này**; sau import cần Present để duyệt font, wrapping và hotspot thực tế. Mock/SVG không thay nghiệm thu canvas hay provider.
