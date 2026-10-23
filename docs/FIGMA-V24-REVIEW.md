# Figma V24 — kết quả local ngày 23/10/2026

Source: `thu-thanh-figma-clicon/`. Gói hiện hành: `output/figma/thuthanh-figma-clicon-v24-2026-10-23.zip`.

SHA256: `720136ceb08ae738b8111419b25a23014599159397b072efc91d608b435511d0`.

Đã bỏ tiêu đề Thương hiệu ở footer, giữ tám logo; thêm ngọn lửa ở Bán chạy nhất, giá gốc gạch ngang và badge phần trăm giảm. Metadata giá demo tách khỏi giá workbook gốc. SKU đầu tiên dùng luồng mua vẫn giữ nguyên giá và tổng thanh toán mẫu.

`node scripts/test-figma-clicon.cjs` đã đạt: 126 frame (124 prototype, hai review), ba page, bảy flow mỗi nền tảng, 2.804 liên kết, 24 SKU mẫu và 60 SVG preview. Có Landing page riêng, flow ngang ngoài section lớn; mock kiểm tra hình học, đường dẫn, số lượng, năm kết quả thanh toán trên ba kênh số lượng, giữ ảnh sửa tay, rebuild lặp và rollback/cancel.

ZIP có sáu file, một manifest; CRC, XML preview và đối chiếu byte source đã đạt. Preview SVG được tạo local, không đóng vào ZIP để giảm dung lượng.

Chưa kiểm chứng canvas Figma thật, font cài trên máy người duyệt hoặc Present. Import manifest trong gói và chạy builder theo hướng dẫn kèm theo để cập nhật canvas cũ. Web dùng đầy đủ 136 SKU; Figma chỉ là prototype mẫu, không xác nhận thanh toán thật.
