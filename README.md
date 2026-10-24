# Nhà sách Thu Thanh

Website bán sách và quản lý nhà sách, sử dụng **Next.js 16 / React 19 / TypeScript / Tailwind 4** cho giao diện và **Laravel 12 / PHP 8.4 / MySQL 8.4** cho API. Laravel kiểm soát quyền, giá, tồn kho, thanh toán và trạng thái đơn; trình duyệt dùng session cookie và CSRF.

## Chức năng hiện có

- Trang chủ: **Bán chạy nhất**, **Sản phẩm mới**, **Gợi ý cho bạn**. Bán chạy xếp theo tổng số lượng đã bán trong đơn thanh toán hợp lệ; sản phẩm mới xếp theo ngày tạo/ID mới nhất.
- Tìm kiếm, lọc, sắp xếp, phân trang; ảnh sách lớn, xem nhanh, chọn số lượng, tiếp tục mua sau đăng nhập.
- Đăng ký/đăng nhập, giỏ nhiều sản phẩm, báo giá, đặt hàng, theo dõi/hủy/đặt lại đơn. Khôi phục mật khẩu khách qua email, hiện/ẩn mật khẩu và thông báo lỗi tại trường nhập.
- Admin: sản phẩm/ảnh/giảm giá/ngừng bán/mở bán lại; danh mục; điều chỉnh và lịch sử tồn kho; đơn hàng; đối soát; biểu đồ doanh thu, bộ lọc và xuất CSV.
- Footer: bản tin, Google Maps, **295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng**, **0915466951**, **07:30–21:30 hằng ngày**.

Phí giao hàng **25.000đ/đơn**, nhận tại cửa hàng **0đ**; phí xuất hiện khi checkout/đơn hàng. Không đổi trả/hoàn tiền. Doanh thu là **tiền hàng sau giảm giá**, thống kê phí giao hàng riêng. Giá gốc gạch ngang và phần trăm giảm chỉ hiển thị khi có giảm giá thật.

## Chạy trên máy hiện tại

Máy hiện tại đã có dependencies, MySQL và 136 sản phẩm/ảnh. Không cần tải lại dữ liệu hoặc import workbook để chạy web.

Terminal 1, bật MySQL rồi chạy Laravel:

```powershell
cd D:\ThuThanh-WebDesign
powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start
cd backend
php artisan serve --host=localhost --port=8000
```

Terminal 2, chạy Next.js:

```powershell
cd D:\ThuThanh-WebDesign\frontend
npm run dev
```

Mở **http://localhost:3000**; trang quản trị ở **http://localhost:3000/admin**. Dùng hostname `localhost` nhất quán để cookie/CSRF hoạt động đúng. Trong `backend/`, dùng `php artisan admin:account` để xem định danh admin hiện có; nếu cần tự đặt lại mật khẩu local, chạy `php artisan admin:password`. Không có mật khẩu quản trị công khai và không cần tạo thêm admin trên máy này.

Để xử lý email/xóa ảnh và hết hạn/đối soát nền, mở hai terminal riêng trong `backend/`:

```powershell
php artisan queue:work database --tries=5 --timeout=60
```

```powershell
php artisan schedule:work
```

[Hướng dẫn chạy, demo và email](docs/CHAY-WEB-VA-LARAVEL.md) · [Thiết lập máy mới](docs/LOCAL-SETUP.md).

## Dữ liệu và môi trường

| Môi trường | Database | Mục đích |
|---|---|---|
| Local chính | `thu_thanh` | 136 sản phẩm, giá/tồn và tài khoản hiện có |
| Demo riêng | `thu_thanh_demo` | 1.000 đơn giả lập; không phải doanh số kinh doanh thật |
| Kiểm thử | `thu_thanh_test` | Test nghiệp vụ và test cạnh tranh tồn kho |

MySQL portable của máy này dùng `127.0.0.1:3307`, lưu trong `.runtime/mysql-data`; không cần XAMPP hoặc MongoDB. Ảnh upload nằm trong Laravel storage, metadata nằm trong MySQL. Không xóa `.runtime`, đổi `APP_KEY` hoặc chạy `migrate:fresh` trên database đang sử dụng. Cập nhật sản phẩm qua admin; các script tải ảnh/import workbook một lần đã được dọn.

## Kiểm tra trước khi commit

Trong `backend/`:

```powershell
php artisan test
php vendor/bin/pint --test
```

Trong `frontend/`:

```powershell
npm run test:api
npm run build
npm run typecheck
```

Từ gốc repo: `python scripts/check_pack.py`. Bộ test tự động và các smoke test HTTP/worker/scheduler của GitHub Actions vẫn được giữ. Kết quả local mới nhất: **118 backend tests / 1.675 assertions**, **7 frontend API-client tests**, Pint, build và typecheck đạt. [Kiểm chứng và giới hạn](docs/VERIFICATION.md).

## Triển khai

Cấu hình Docker, Next standalone, Nginx, PHP-FPM/OPcache và Redis ở [deploy/](deploy/README.md). **Chưa triển khai hoặc nghiệm thu production**. VNPAY/QR thật tiếp tục hoãn; đơn mới không tự được xác nhận đã thanh toán. Email recovery cần SMTP thật và worker; chưa nghiệm thu gửi email thật. Mục tiêu **10.000 người đồng thời chưa được chứng minh** trên hạ tầng thực.

[Runbook vận hành](docs/OPERATIONS.md) · [Hiệu năng](docs/PERFORMANCE.md) · [Giới hạn hiện hành](docs/KNOWN-LIMITATIONS.md). Giữ secret trong env private; không commit database, token, mật khẩu, `vendor`, `node_modules` hoặc output build.

## Cấu trúc và tài liệu

| Thư mục | Nội dung |
|---|---|
| `backend/` | API Laravel, nghiệp vụ, migration và bộ test |
| `frontend/` | Các trang khách/admin và component Next.js |
| `deploy/` | Cấu hình triển khai và môi trường mẫu |
| `scripts/` | Chạy local/demo, kiểm tài liệu/asset, smoke CI và load profile |
| `thu-thanh-figma-clicon/` | Plugin thiết kế V24; [hướng dẫn import](thu-thanh-figma-clicon/HUONG-DAN.md) |
| `docs/` | Đặc tả, hướng dẫn, checkpoint bảo trì |

[API](docs/API.md) · [Database](docs/DATABASE.md) · [Kiến trúc](docs/ARCHITECTURE.md) · [Nghiệp vụ](docs/DOMAIN-RULES.md) · [Phân công Đông/Thiện](docs/PHAN-CONG-VA-COMMIT.md).

Source: [DongDao27/Payment](https://github.com/DongDao27/Payment). Task: [GitHub Project #4](https://github.com/users/DongDao27/projects/4), issue thuộc repo Thu-Thanh-bookstore. Các báo cáo đánh giá/đề xuất, snapshot QA và công cụ lấy dữ liệu dùng một lần đã được lưu ngoài repo; xem [bản đồ và khôi phục](docs/REPOSITORY-MAP.md).
