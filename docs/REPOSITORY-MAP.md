# Bản đồ repo và khôi phục file cũ

## Source đang sử dụng

| Đường dẫn | Vai trò |
|---|---|
| `backend/app/Services` | Quy tắc giá, catalog/cache, giỏ/quote/checkout, tồn kho, thanh toán/report |
| `backend/app/Http` | Controller mỏng, validation, resource, middleware quyền/proxy/request |
| `backend/app/Console/Commands` | Công cụ admin và seed demo có guard; không có endpoint fake-paid |
| `frontend/src/app` | Next App Router, các trang khách và admin |
| `frontend/src/components` | UI dùng chung; ProductPrice/điều khiển giá tách riêng để tránh lệch card/popup/form |
| `frontend/src/lib` | API client, types, metadata ảnh và gộp public request |
| `frontend/src/app/styles/storefront.css` | Styles storefront/footer/price/report hiện hành, thay hai file version cũ |
| `deploy` | Docker/Nginx/PHP-FPM và overlay scale, chưa nghiệm thu runtime |
| `scripts` | Audit, chuẩn bị demo, load test, build/package/verify Figma, kiểm tài liệu |
| `thu-thanh-figma-clicon` | Source/plugin Figma hiện hành V24; không dùng bộ v12 cũ |
| `docs/qa` | Kết quả kiểm thử máy đọc được, không chứa password/token |
| `docs/MD-CLEANUP-MANIFEST.json` | Danh sách tài liệu cũ đã xóa và archive khôi phục ngoài repo |

Giữ `AGENTS.md`, START-HERE, PRD/domain/API/database, TASKS/STATUS/HANDOFF/PROGRESS và bài audit môn học. Đây là tài liệu còn cần để bảo trì; không xóa vì không được frontend import. CSV test cases là tài liệu kiểm thử, không phải dữ liệu bán hàng để import.

## Đã dọn

149 file cũ, 183.666.923 byte, đã chuyển vào ZIP ngoài repo; kiểm CRC và SHA-256 từng file trước khi xóa bản trùng. Gồm build pack tài liệu gốc đã được triển khai vào repo, ZIP/thư mục giải nén/screenshot Figma cũ và hai stylesheet đã gộp. Manifest: [CLEANUP-MANIFEST.json](CLEANUP-MANIFEST.json).

Archive nằm tại `D:\ThuThanh-Archives\thu-thanh-legacy-20261023-185819.zip`; SHA-256 archive được ghi trong manifest. Có thể giải nén ra thư mục tạm để lấy lại file theo đường dẫn gốc, xem diff rồi mới chép vào repo. Không giải nén ghi đè toàn repo. Khi chuyển máy, sao lưu cả thư mục archive nếu cần các bằng chứng cũ. Các task lịch sử giữ liên kết manifest cho evidence đã archive.

Không dọn `.runtime/mysql-data`, `.env*`, APP_KEY, workbook nguồn, upload/evidence, dependencies hoặc backup source. File Excel 136 sản phẩm được giữ vì là nguồn đối chiếu giá/tồn, không phải file thừa. `.runtime/demo-storage` và database demo cũng được giữ để tiếp tục trình bày.

`output/` và SVG xem trước được sinh lại, được bỏ khỏi Git để tránh commit build lớn. ZIP V24 hiện hành vẫn có trong `output/figma/`. Tạo lại: `python thu-thanh-figma-clicon/build.py`, `node scripts/test-figma-clicon.cjs`, `python scripts/package-figma.py`. ZIP import có 6 file và nhúng toàn bộ ảnh; người import không cần Python.

## Bảo trì

Thêm loại giá hiển thị ở `ProductPrice`; thay quy tắc tính tiền ở backend, không lấy giá client làm nguồn. Nếu thay dữ liệu bằng bulk SQL ngoài Eloquent, gọi StorefrontSnapshot::invalidate sau commit. Không thêm cache vào API private hoặc cookie/CSRF. Thay asset cùng URL cần version/purge CDN thích hợp. Mở chức năng mới đọc PRD/DECISIONS và cập nhật hợp đồng/test/checkpoint, không sao chép controller/service thành một luồng nghiệp vụ khác.


## D46 — tài liệu Markdown đã tinh gọn

Đã xóa 12 file khỏi repo: tám review lịch sử, hai prompt bootstrap/resume trùng AGENTS/START-HERE, tài liệu chuyển lab và FRONTEND-VERIFICATION đã gộp vào VERIFICATION.md. Archive ngoài repo có CRC/SHA từng file trong [MD-CLEANUP-MANIFEST.json](MD-CLEANUP-MANIFEST.json). Giữ đặc tả/nghiệp vụ, hướng dẫn chạy/deploy, chứng cứ môn học và tài liệu nguồn asset vì vẫn cần bảo trì/nộp bài.
