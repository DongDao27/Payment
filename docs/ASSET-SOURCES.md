# Nguồn asset hiện hành — D43 / V22

Logo header/footer **Nhà sách Thu Thanh** là vector riêng: chữ Constantia Bold outline, dòng NHÀ SÁCH sans và biểu tượng quyển sách; một màu xanh/trắng với dấu nhấn vàng. Không dùng chữ/logo nhiều màu của Phương Nam. SVG không cần font runtime; hash trong ASSET-SOURCES.json.

Theo lựa chọn rõ ràng của chủ dự án, dùng nguyên byte3banner Phương Nam: manga1920×600, [Sách mới tháng này](https://cdn.hstatic.net/files/200001055148/file/banner_slide__47_.png)900×506 và [New Books](https://cdn.hstatic.net/files/200001055148/file/banner_slide__48_.png)900×506. Ảnh sau giữ cả nội dung tiếng Anh/logo có sẵn theo yêu cầu dùng nguyên ảnh; logo thương hiệu app vẫn riêng. Không tự nhập sách trong banner, không dựng giá/khuyến mãi cho chúng. Nguồn homepage https://nhasachphuongnam.com/; file local/hash/URL/dimensions trong JSON. Hai banner tự dựng tạm trước khi có câu trả lời đã được thay khỏi web/Figma.

Footer8nhãn hàng: Thiên Long,Faber-Castell,KEYROAD,Maped,MILAN,Pentel,Pilot,BITEX. File PNG giữ nguyên từ home_partner_*.png CDN nguồn, URL/hash/dimensions trong manufacturers của ASSET-SOURCES.json và frontend/src/lib/brands.json. Nhãn là dải thương hiệu, không tuyên bố đối tác/đại lý; không gắn badge Bộ Công Thương hoặc logo payment chưa tích hợp.

136ảnh sản phẩm vẫn là ảnh gốc của đúngSKU theo URL workbook,580/600/1200 vuông; thumbnail145vuông. PRODUCT-IMAGE-SOURCES.json lưu URL/hash/dimensions và meanRGBdifference tối đa3.928/255 so thumbnail sau chuẩn hóa64vuông. Không upscale/đổi bìa. Mapping chỉ bộ import SHA ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb; giữ ảnh admin upload/import khác.24ảnh Figma chuyển PNG lossless, pixel/dimensions giữ nguyên theo yêu cầu createImage PNG/JPEG/GIF; lưu cả hash nguồn/hashPNG. Brand/banner nhúng giúp plugin chạy offline.

Kiểm `python scripts/verify-store-assets.py`: ảnh gốc136,PNG24,logo2,banner3,brand8 và bytes ảnh nhúng Figma. Không sửa workbook,giá,tồn,ledger hoặc snapshot đơn cũ.

## Tài liệu kỹ thuật tham chiếu

D59: plugin V25 nhúng bốn biến thể campaign từ web; JPEG giữ bytes gốc, WEBP chuyển PNG không mất pixel để Figma đọc được. Verifier so sánh từng pixel với ảnh web. Map nhỏ là ảnh riêng của iframe Google Maps, 300×164, cùng pin 16.0561478/108.1686171 và giữ hiển thị Google; không chụp toàn bộ UI. Nguồn/hash/bytes ở `store`/`webCampaigns` trong data.json; chữ/nút/layout là node chỉnh sửa được. SVG preview nhúng font Nunito Sans có sẵn trong web; logo vector không đổi.

D58: bổ sung ảnh chủ đề văn học ở header và manga/thiếu nhi giữa ba feed từ [Fahasa](https://www.fahasa.com/), ảnh gốc giữ nguyên pixel, tự host để không hotlink. Đã xem trực tiếp và loại bản có logo Fahasa; không dùng voucher/freeship/mức giảm giá cụ thể của website nguồn. Ảnh manga còn câu quảng bá chung, giá sản phẩm vẫn hoàn toàn từ Laravel. Header từ [Tủ sách văn học](https://www.fahasa.com/tu-sach-van-hoc), manga từ [Nhập hội Manga–Comic](https://www.fahasa.com/nhap-hoi-manga-comic), thiếu nhi và bản mobile từ [Trang thiếu nhi](https://www.fahasa.com/trang-thieu-nhi). URL tải, hash và kích thước thật trong `storefront_campaigns_D58` của ASSET-SOURCES.json; cấu hình hiển thị ở `frontend/src/lib/store-banners.json`. Không dùng ảnh lịch sử này để tuyên bố chương trình ưu đãi của Fahasa đang diễn ra tại Thu Thanh. Ba banner Phương Nam trước đó và plugin Figma giữ nguyên.

D57: font giao diện Nunito Sans từ [Google Fonts](https://github.com/google/fonts/tree/main/ofl/nunitosans), giấy phép SIL OFL 1.1 giữ tại `frontend/src/app/fonts/OFL.txt`. File variable WOFF2 tự host 79.468 byte; giữ trục weight 200–900, các trục khác ở mặc định, đã kiểm glyph tiếng Việt và ký hiệu ₫. Font logo SVG riêng giữ nguyên. UI/UX Pro Max được cài cho Codex từ [upstream](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill/tree/28b3b8dee8c976df4fa3e94aed4e092ad43fcb03), không đóng gói dữ liệu skill vào ứng dụng.

Laravel12: https://laravel.com/docs/12.x/sanctum, https://laravel.com/docs/12.x/database, https://laravel.com/docs/12.x/queries, https://laravel.com/docs/12.x/validation, https://laravel.com/docs/12.x/scheduling, https://laravel.com/docs/12.x/deployment.
Next.js: https://nextjs.org/docs/app. VNPAY: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html và https://sandbox.vnpayment.vn/apis/docs/truy-van-hoan-tien/querydr%26refund.html.
Các link tham khảo không chứng minh merchant/callback/production đã nghiệm thu; kết quả thực tế ghi ởSTATUS/PROGRESS. Tài liệu cũ đãgộp/archivetheoCLEANUP-2026-10-28.json.
