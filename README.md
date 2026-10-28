# Nhà sách Thu Thanh

Website bán sách và quản lý cửa hàng, cập nhật **28/10/2026**. Frontend dùng **Next.js 16, React 19, TypeScript, Tailwind 4**; backend dùng **Laravel 12, PHP 8.4, MySQL 8.4**. Laravel quyết định quyền, giá, tồn kho và trạng thái đơn; trình duyệt dùng session cookie và CSRF.

## Chức năng hiện có

- Trang chủ gồm Bán chạy nhất, Sản phẩm mới, Gợi ý cho bạn; có tìm kiếm, lọc, sắp xếp, phân trang và xem nhanh. Bán chạy xếp theo lượng bán trong đơn paid/accepted, sản phẩm mới xếp theo ngày tạo.
- Tài khoản khách, khôi phục mật khẩu email, giỏ nhiều món, báo giá, đặt hàng, VNPAY Sandbox cho hai cách nhận hàng, theo dõi/hủy/đặt lại đơn.
- Admin quản lý sản phẩm, ảnh, giảm giá, archive/restore, danh mục; giá và lịch sử tồn kho; đơn và lịch sử thanh toán/receipt/trạng thái; đối soát, biểu đồ doanh thu, bộ lọc và CSV.
- Footer có Google Maps, bản tin, nhãn hàng; địa chỉ **295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng**, điện thoại **0915466951**, mở cửa **07:30–21:30 hằng ngày**.

Giao hàng **25.000đ/đơn**, nhận tại cửa hàng **0đ**; phí chỉ hiện ở checkout/đơn. Không đổi trả/hoàn tiền. Doanh thu là tiền hàng sau giảm giá, phí giao thống kê riêng. Tạo đơn giữ hàng 5 phút khi giao, 24 giờ khi nhận cửa hàng; thanh toán lại không gia hạn.

## Chạy trên máy Windows hiện tại

Terminal 1:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start
cd backend
php artisan serve --host=localhost --port=8000
```

Terminal 2:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npm run dev
```

Mở **http://localhost:3000**, quản trị tại **http://localhost:3000/admin**. Trong backend, `php artisan admin:account` xem định danh admin, `php artisan admin:password` tự đổi mật khẩu local nếu cần. Máy này đã có admin; không có mật khẩu mặc định công khai.

Chạy thêm `php artisan queue:work database --tries=5 --timeout=60` và `php artisan schedule:work` ở hai terminal backend riêng cho email, xóa ảnh, hết hạn và đối soát.

[Hướng dẫn máy mới, demo, email và VNPAY](docs/CHAY-WEB-VA-LARAVEL.md).

## Dữ liệu và kiến trúc

Trình duyệt → Next.js/proxy → Laravel API → MySQL. Eloquent/migration quản lý bảng; MySQL lưu dữ liệu bền vững. Máy Windows dùng `127.0.0.1:3307`, lưu tại `.runtime/mysql-data`, không cần XAMPP hoặc MongoDB.

| Database | Mục đích |
|---|---|
| `thu_thanh` | Dữ liệu cửa hàng hiện có; bộ nguồn 136 sản phẩm/ảnh |
| `thu_thanh_demo` | 1.000 đơn giả lập riêng, không phải doanh số thật |
| `thu_thanh_test` | Test tự động và QA, tách dữ liệu chính/demo |

Clone source không tự sao chép database. Cập nhật sản phẩm qua admin; giữ env, APP_KEY, database, workbook và uploads khi chuyển máy. Không import lại hoặc chạy `migrate:fresh` để sửa UI. Laravel dùng FormRequest/Policy/Service, transaction, row lock và idempotency để bảo vệ tiền/tồn.

## Kiểm tra

Trong backend: `php artisan test`, `php vendor/bin/pint --test`.
Trong frontend: `npm run test:api`, `npm run build`, `npm run typecheck`.
Từ gốc: `python scripts/check_pack.py`; CI còn kiểm HTTP cookie/CSRF và worker/scheduler.

Ngày 28/10: **127 backend tests / 1.773 assertions**, **7 client tests**, build/typecheck đạt trên Windows/PHP 8.4/MySQL 8.4. Test tài chính dùng fixture; chưa thay giao dịch merchant Sandbox thực tế. Kết quả hiện hành ở STATUS/PROGRESS; output kiểm thử tạm được Git ignore.

## Cần cấu hình và nghiệm thu thêm

- Payment đã hoàn tất code/cấu hình; chủ dự án xác nhận chưa chạy giao dịch merchant thật. Máy Windows chưa có TMN/hash secret. Cần tự điền env private và kiểm success/hủy/query/IPN Sandbox. VNPAY Production và QR tại quầy chưa mở; không có nút fake-paid.
- Email recovery cần SMTP thật và worker; chưa có SMS cho tài khoản chỉ dùng điện thoại.
- Canvas Figma cần duyệt riêng; user testing cần người dùng đại diện. Docker, staging, HTTPS, backup/restore và mục tiêu 10.000 người đồng thời chưa nghiệm thu. [Runbook deploy](deploy/README.md).

## Bảo trì

Source ở `backend/`, `frontend/`; cấu hình triển khai ở `deploy/`; công cụ chạy/kiểm ở `scripts/`; plugin thiết kế ở `thu-thanh-figma-clicon/`; đặc tả và checkpoint ở `docs/`.

[API](docs/API.md) · [OpenAPI](docs/openapi.json) · [Database](docs/DATABASE.md) · [Nghiệp vụ](docs/DOMAIN-RULES.md) · [PRD](docs/PRD.md) · [Đông/Thiện](docs/PHAN-CONG-VA-COMMIT.md) · [Nguồn asset](docs/ASSET-SOURCES.md) · [Import Figma](thu-thanh-figma-clicon/HUONG-DAN.md).

Giữ env mẫu theo từng môi trường trong backend/frontend/deploy để cài đặt và CI. Không commit env thật, token, private key, database, vendor, node_modules hoặc build output. Đã gộp và dọn 13 file trùng ngày 28/10; ZIP ngoài repo và hash ở [manifest khôi phục](docs/CLEANUP-2026-10-28.json). Giải nén riêng file cần vào thư mục tạm, kiểm hash/diff rồi mới phục hồi; không ghi đè toàn repo. Checkpoint lịch sử có thể dẫn member đã archive.

Source: [DongDao27/Payment](https://github.com/DongDao27/Payment). Task: [GitHub Project #4](https://github.com/users/DongDao27/projects/4). Phân công không xác nhận tác giả code cũ; CI xanh không tự thay review/acceptance.
