# Nguồn asset hiện hành — D43 / V22

Logo header/footer **Nhà sách Thu Thanh** là vector riêng: chữ Constantia Bold outline, dòng NHÀ SÁCH sans và biểu tượng quyển sách; một màu xanh/trắng với dấu nhấn vàng. Không dùng chữ/logo nhiều màu của Phương Nam. SVG không cần font runtime; hash trong ASSET-SOURCES.json.

Theo lựa chọn rõ ràng của chủ dự án, dùng nguyên byte3banner Phương Nam: manga1920×600, [Sách mới tháng này](https://cdn.hstatic.net/files/200001055148/file/banner_slide__47_.png)900×506 và [New Books](https://cdn.hstatic.net/files/200001055148/file/banner_slide__48_.png)900×506. Ảnh sau giữ cả nội dung tiếng Anh/logo có sẵn theo yêu cầu dùng nguyên ảnh; logo thương hiệu app vẫn riêng. Không tự nhập sách trong banner, không dựng giá/khuyến mãi cho chúng. Nguồn homepage https://nhasachphuongnam.com/; file local/hash/URL/dimensions trong JSON. Hai banner tự dựng tạm trước khi có câu trả lời đã được thay khỏi web/Figma.

Footer8nhãn hàng: Thiên Long,Faber-Castell,KEYROAD,Maped,MILAN,Pentel,Pilot,BITEX. File PNG giữ nguyên từ home_partner_*.png CDN nguồn, URL/hash/dimensions trong manufacturers của ASSET-SOURCES.json và frontend/src/lib/brands.json. Nhãn là dải thương hiệu, không tuyên bố đối tác/đại lý; không gắn badge Bộ Công Thương hoặc logo payment chưa tích hợp.

136ảnh sản phẩm vẫn là ảnh gốc của đúngSKU theo URL workbook,580/600/1200 vuông; thumbnail145vuông. PRODUCT-IMAGE-SOURCES.json lưu URL/hash/dimensions và meanRGBdifference tối đa3.928/255 so thumbnail sau chuẩn hóa64vuông. Không upscale/đổi bìa. Mapping chỉ bộ import SHA ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb; giữ ảnh admin upload/import khác.24ảnh Figma chuyển PNG lossless, pixel/dimensions giữ nguyên theo yêu cầu createImage PNG/JPEG/GIF; lưu cả hash nguồn/hashPNG. Brand/banner nhúng giúp plugin chạy offline.

Kiểm `python scripts/verify-store-assets.py`: ảnh gốc136,PNG24,logo2,banner3,brand8 và bytes ảnh nhúng Figma. Không sửa workbook,giá,tồn,ledger hoặc snapshot đơn cũ.
