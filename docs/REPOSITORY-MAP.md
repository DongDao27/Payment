# Bản đồ source và khôi phục

| Đường dẫn | Vai trò |
|---|---|
| `backend/app/Services` | Giá, catalog/cache, giỏ/checkout, tồn, đơn, thanh toán, recovery và doanh thu |
| `backend/app/Http` | Controller, FormRequest, resource, middleware và quyền |
| `backend/database` | Schema, factory và seeder có kiểm soát |
| `backend/tests`, `frontend/tests` | Test tự động đang được CI sử dụng |
| `frontend/src/app`, `frontend/src/components` | Trang khách/admin và component dùng chung |
| `frontend/src/lib` | API client, types, metadata ảnh và thông tin nhà sách |
| `scripts` | Chạy local/demo, kiểm tài liệu/asset, smoke CI, package Figma và load profile |
| `deploy` | Docker/Nginx/FPM/Redis; chưa nghiệm thu staging |
| `thu-thanh-figma-clicon` | Plugin Figma V24 và hướng dẫn import |
| `docs/reports` | Báo cáo Excel Đông/Thiện11–31/10/2026 |

## Dọn ngày24/10/2026

Theo yêu cầu chủ dự án, bỏ các báo cáo đánh giá/rubric/đề xuất cũ, test plan/CSV/traceability trùng source tests, snapshot và screenshot QA, fixture browser thủ công cùng công cụ tải ảnh/import workbook một lần. Workbook sản phẩm, ảnh web/Figma, database, env, uploads, test tự động và smoke CI được giữ.

Danh sách từng file, SHA-256, vị trí ZIP và hướng dẫn khôi phục ở [CLEANUP-2026-10-24.json](CLEANUP-2026-10-24.json). ZIP nằm ngoài repo tại `D:\ThuThanh-Archives`; đã kiểm CRC và từng SHA trước khi bỏ bản trong repo. Một số checkpoint hoặc báo cáo lịch sử còn ghi đường dẫn cũ: tìm member tương ứng trong manifest, không hiểu đó là file còn hoạt động. Công cụ tạo/fetch báo cáo Excel cũ cũng có trong archive; fileExcel đã bàn giao vẫn giữ nguyên.

Khôi phục bằng cách giải nén riêng member cần thiết ra thư mục tạm, kiểm hash/diff rồi mới chép lại file đó. Không giải nén đè toàn repository. Nếu chuyển máy và cần lịch sử, sao lưu cả ZIP ngoài repo. Các lần dọn trước nằm trong [CLEANUP-MANIFEST.json](CLEANUP-MANIFEST.json) và [MD-CLEANUP-MANIFEST.json](MD-CLEANUP-MANIFEST.json).

## Bảo trì

Giữ AGENTS, START-HERE, PRD/domain/API/database, checkpoint và tài liệu vận hành hiện hành. Test mới bổ sung vào bộ test source; kết quả chạy tạm trong storage/logs/output bịignore, không tạo thêm báo cáo đánh giá để commit nếu không được yêu cầu.

Giữ `.runtime/mysql-data`, `.env*`, APP_KEY, upload và workbook khi cập nhật code. Không reset/seed lại dữ liệu thật để sửa UI. Fetch API runtime vẫn cần cho web; chỉ công cụ lấy dữ liệu một lần được dọn. Giá/tiền/tồn/quyền vẫn do Laravel quyết định.

Figma: `python thu-thanh-figma-clicon/build.py`, `node scripts/test-figma-clicon.cjs`, `python scripts/package-figma.py`. Output ZIP/preview không commit. Canvas thật vẫn cần chủ dự án duyệt riêng.
