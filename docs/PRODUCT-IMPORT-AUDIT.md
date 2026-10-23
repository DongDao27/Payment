# Kiểm tra workbook sản phẩm — 22/10/2026

Nguồn: `Nha_sach_Thu_Thanh_San_pham_2026-10-21.xlsx`. Workbook được đọc nguyên trạng, không sửa file gốc hoặc ghi MySQL.

```json
{
  "products": 136,
  "categories": {
    "Sách giáo khoa": 47,
    "Sách văn học": 18,
    "VPP · Dụng cụ học sinh": 18,
    "Đồ chơi": 18,
    "Truyện tranh": 17,
    "Quà lưu niệm": 18
  },
  "subcategories": 18,
  "embedded_images_matched": 136,
  "missing_store_price": 0,
  "missing_store_stock": 0,
  "missing_reference_price": 0,
  "staged_directory": ".runtime\\product-import\\ae2ca77c65437321",
  "source_sha256": "ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb"
}
```

SKU/slug unique, cột đúng hợp đồng, số nguyên VND hợp lệ; ảnh ghép theo drawing anchor của từng hàng, không đoán thứ tự tên file media. Ảnh nhúng là preview từ workbook, chưa chứng minh đủ độ phân giải cho ảnh lớn. Không download URL ngoài hoặc lấy tình trạng nguồn làm tồn cửa hàng.

Ô giá/tồn trống giữ null. Workbook hiện đã có giá/tồn cho toàn bộ sản phẩm; staging chưa đồng nghĩa đã nhập MySQL. ID danh mục con trong workbook không tự dùng làm FK của 6 danh mục gốc trong MySQL; cần mapping/mở rộng hợp đồng rõ ràng trước nhập. Chưa tự bật hot/bestseller, không lấy giá tham khảo thay giá bán.

## Kết quả nhập thực tế

`catalog:import` dry-run:create136/skip0. Apply:create136/skip0. Chạy lại:create0/skip136; không ghi đè giá/tồn. Đã đối chiếu toàn bộ136 SKU qua API phát triển với cột O/P và hash ảnh, khớp tất cả. Mỗi SKU có1 ảnh và opening ledger; actor hệ thống null, reason có nguồn sha workbook. Không tạo admin thay chủ dự án. Không thay schema.

CatalogImportTest3 tests kiểm dry-run/missing price/ảnh sai, retry không reset stock và injected failure ở sản phẩm thứ2 rollback toàn bộ sản phẩm/ledger/file mới. Lưu ý: ảnh nhúng là preview, chưa tải ảnh gốc từ URL ngoài. Workbook gốc được giữ nguyên.
