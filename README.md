# Nhà sách Thu Thanh

Website bán sách và quản lý cửa hàng, cập nhật **29/10/2026**. Frontend dùng **Next.js 16, React 19, TypeScript, Tailwind 4**; backend dùng **Laravel 12, PHP 8.4, MySQL 8.4**. Laravel quyết định quyền, giá, tồn kho và trạng thái đơn; trình duyệt dùng session cookie và CSRF.

## Chức năng hiện có

Cập nhật **31/10/2026 — D67**: kết quả thanh toán có tick xanh/X đỏ/đồng hồ chờ, thông tin và chi tiết đơn lấy từ Laravel; bỏ nút Đặt lại sản phẩm; footer dùng 10 logo ảnh gốc với mũi tên chuyển nhóm. Đã kiểm tra đọc Sandbox có receipt VNPAY `accepted` và đơn `paid` cho giao dịch 213.235đ của người dùng; việc này không thay thế nghiệm thu deploy/production hay các tình huống provider còn lại.

Cập nhật D66: Bán chạy nhất có bảng xếp hạng và khung chi tiết, nút số lượng căn đều, khách tự nhận trạng thái đơn từ server. Bộ kiểm thử trình duyệt FE–Laravel–MySQL chạy bằng `cd frontend` → `npm run test:e2e`; cài Chromium lần đầu bằng `npx playwright install chromium`. Chỉ chạy trên `thu_thanh_test`, không chạy đồng thời PHPUnit; cổng thanh toán trong E2E là giả lập, nghiệm thu merchant Sandbox vẫn riêng. Xem hướng dẫn đầy đủ trong [CHAY-WEB-VA-LARAVEL](docs/CHAY-WEB-VA-LARAVEL.md). Figma local mới nhất là **V27**, giữ landing riêng và tối đa 12 màn/hàng.

- Trang chủ gồm Bán chạy nhất, Sản phẩm mới, Gợi ý cho bạn; tìm kiếm gợi ý khi gõ, chọn để xem nhanh hoặc Enter mở kết quả; có lọc, sắp xếp và phân trang. Bán chạy gồm lựa chọn admin và sản phẩm đã có lượng bán paid/accepted; số bán thật vẫn xếp trước, sản phẩm mới xếp theo ngày tạo.
- Tài khoản khách, chỉnh sửa hồ sơ lưu MySQL, khôi phục mật khẩu email, giỏ nhiều món, báo giá, đặt hàng, VNPAY Sandbox cho hai cách nhận hàng, theo dõi/hủy/đặt lại đơn.
- Admin quản lý sản phẩm, chọn ảnh và tick Bán chạy ngay khi tạo/sửa, giảm giá, archive/restore, danh mục; slug tự sinh; giá và lịch sử tồn kho; đơn và lịch sử thanh toán/receipt/trạng thái; đối soát, biểu đồ doanh thu, bộ lọc và CSV.
- Footer gọn với bản đồ nhỏ dưới logo, bản tin, nhãn hàng và một khối liên hệ: **295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng**, **0915466951**, **07:30–21:30 hằng ngày**. Có nút lên đầu trang, banner chủ đề giữa ba feed; header không còn dòng chào mừng hoặc quảng cáo. Nguồn ảnh trong ASSET-SOURCES.

Giao hàng **25.000đ/đơn**, nhận tại cửa hàng **0đ**; phí chỉ hiện ở checkout/đơn. Không đổi trả/hoàn tiền. Doanh thu là tiền hàng sau giảm giá, phí giao thống kê riêng. Tạo đơn giữ hàng 5 phút khi giao, 24 giờ khi nhận cửa hàng; thanh toán lại không gia hạn.

## Chạy VNPAY Sandbox sau khi mở lại máy

Máy hiện tại đã lưu merchant trong workspace private, không cần đăng ký/nhập khóa lại. Chạy từ repo:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/sandbox.ps1 -Action start
```

Sau đó chạy frontend `npm run dev` như bên dưới, mở localhost3000. Launcher tự chạy MySQL/backend8000/queue/scheduler, có `-Action status` và `-Action stop`; không mở trùng và không đụng dữ liệu cửa hàng. Đừng chạy backend cửa hàng cùng port khi đang test Sandbox. Quick Tunnel/IPN có thể cần cập nhật URL khi mở lại. [Hướng dẫn Sandbox và kết quả kiểm thực tế](docs/CHAY-WEB-VA-LARAVEL.md).

D65 đã qua thẻ NCB/OTP và Return00, hủy24; xác nhận paid phía ứng dụng vẫn bị chặn bởi QueryDr99/IPN chưa đăng ký. Không coi đã nghiệm thu toàn luồng.

## Chạy trên máy Windows hiện tại

Terminal 1:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start
powershell -ExecutionPolicy Bypass -File scripts/start-backend.ps1
```

Terminal 2:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npm run dev
```

Mở **http://localhost:3000**, quản trị tại **http://localhost:3000/admin**. Trong backend, `php artisan admin:account` xem định danh admin, `php artisan admin:password` tự đổi mật khẩu local nếu cần. Máy này đã có admin; không có mật khẩu mặc định công khai.

Chạy thêm `php artisan queue:work database --tries=5 --timeout=60` và `php artisan schedule:work` ở hai terminal backend riêng cho email, xóa ảnh, hết hạn và đối soát.

[Hướng dẫn máy mới, email và VNPAY](docs/CHAY-WEB-VA-LARAVEL.md).

## Dữ liệu và kiến trúc

Trình duyệt → Next.js/proxy → Laravel API → MySQL. Eloquent/migration quản lý bảng; MySQL lưu dữ liệu bền vững. Máy Windows dùng `127.0.0.1:3307`, lưu tại `.runtime/mysql-data`, không cần XAMPP hoặc MongoDB.

| Database | Mục đích |
|---|---|
| `thu_thanh` | Dữ liệu cửa hàng hiện có; 137 sản phẩm hiện có, gồm 136 sản phẩm nguồn |
| `thu_thanh_test` | Test tự động và QA, tách dữ liệu chính |

Clone source không tự sao chép database. Cập nhật sản phẩm qua admin; giữ env, APP_KEY, database, workbook và uploads khi chuyển máy. Không import lại hoặc chạy `migrate:fresh` để sửa UI. Laravel dùng FormRequest/Policy/Service, transaction, row lock và idempotency để bảo vệ tiền/tồn.

D61 đã giảm 11–17% cho 7 sản phẩm tại database chính theo yêu cầu. Bán chạy lấy tối đa7 sản phẩm từ cờ trưng bày admin hoặc lượng bán paid/accepted; không gán doanh số giả. Đã gỡ seeder/lệnh chạy demo và xóa1.000đơn giả trong database riêng sau backup, giữ một đơn ngoài tập giả lập.

## Kiểm tra

Trong backend: `php artisan test`, `php vendor/bin/pint --test`.
Trong frontend: `npm run test:api`, `npm run build`, `npm run typecheck`.
Từ gốc: `python scripts/check_pack.py`; CI còn kiểm HTTP cookie/CSRF và worker/scheduler.

Ngày 29/10: **130 backend tests (kết quả mới nhất ở STATUS)**, **7 client tests**, build/typecheck đạt trên Windows/PHP 8.4/MySQL 8.4. Test tài chính dùng fixture; chưa thay giao dịch merchant Sandbox thực tế. Kết quả hiện hành ở STATUS/PROGRESS; output kiểm thử tạm được Git ignore.

## Cần cấu hình và nghiệm thu thêm

- Payment đã hoàn tất code/cấu hình; D65 đã thử NCB/OTP nhận Return00 và hủy24; chưa có accepted receipt vì QueryDr99/IPN chưa đăng ký. Merchant/CA đã lưu private, launcher chạy lại không reset dữ liệu. VNPAY Production và QR tại quầy chưa mở; không có nút fake-paid.
- Email recovery cần SMTP thật và worker; chưa có SMS cho tài khoản chỉ dùng điện thoại.
- Canvas Figma cần duyệt riêng; user testing cần người dùng đại diện. Docker, staging, HTTPS, backup/restore và mục tiêu 10.000 người đồng thời chưa nghiệm thu. [Runbook deploy](deploy/README.md).

## Bảo trì

Giao diện áp dụng [UI/UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) theo D57: giữ nhận diện Thu Thanh, dùng token chung và font Nunito Sans tiếng Việt tự host. Hướng dẫn cho các lần sửa UI nằm ở [frontend/AGENTS.md](frontend/AGENTS.md); skill là công cụ của Codex, không phải dependency khi chạy/deploy web.

Source ở `backend/`, `frontend/`; cấu hình triển khai ở `deploy/`; công cụ chạy/kiểm ở `scripts/`; plugin thiết kế ở `thu-thanh-figma-clicon/`; đặc tả và checkpoint ở `docs/`.

[API](docs/API.md) · [OpenAPI](docs/openapi.json) · [Database](docs/DATABASE.md) · [Nghiệp vụ](docs/DOMAIN-RULES.md) · [PRD](docs/PRD.md) · [Đông/Thiện](docs/PHAN-CONG-VA-COMMIT.md) · [Nguồn asset](docs/ASSET-SOURCES.md) · [Import Figma](thu-thanh-figma-clicon/HUONG-DAN.md).

Giữ env mẫu theo từng môi trường trong backend/frontend/deploy để cài đặt và CI. Không commit env thật, token, private key, database, vendor, node_modules hoặc build output. Đã gộp và dọn 13 file trùng ngày 28/10; ZIP ngoài repo và hash ở [manifest khôi phục](docs/CLEANUP-2026-10-28.json). Giải nén riêng file cần vào thư mục tạm, kiểm hash/diff rồi mới phục hồi; không ghi đè toàn repo. Checkpoint lịch sử có thể dẫn member đã archive.

Source: [DongDao27/Payment](https://github.com/DongDao27/Payment). Task: [GitHub Project #4](https://github.com/users/DongDao27/projects/4). Phân công không xác nhận tác giả code cũ; CI xanh không tự thay review/acceptance.
