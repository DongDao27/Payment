# Giao diện Nhà sách Thu Thanh

Next.js16.4.2, React19.3.1, TypeScript và Tailwind4.3.3 theo lockfile. App Router gồm storefront, tài khoản/khôi phục mật khẩu, giỏ/checkout/đơn và các trang quản trị. Giao diện hiện hành dùng plugin Figma V24, dữ liệu đầy đủ từ Laravel; không áp dụng giới hạn sản phẩm của prototype vào web.

Trong thư mục này, chạy `npm ci` khi thiết lập máy mới, sau đó `npm run dev`. Mở http://localhost:3000; admin ở /admin. Cần Laravel và MySQL chạy riêng; xem [hướng dẫn](../docs/CHAY-WEB-VA-LARAVEL.md).

`LARAVEL_INTERNAL_URL` mặc định http://localhost:8000, proxy /api/v1, /sanctum/csrf-cookie và /storage. Browser dùng cookie/session/CSRF; backend xác định admin/customer và quyết định giá/tồn/trạng thái. Không lưu bearer token trong localStorage hoặc đưa secret vào NEXT_PUBLIC.

Kiểm tra: `npm run test:api`, `npm run build`, `npm run typecheck`. Các fixture browser dùng một lần đã dọn; bộ test API-client và GitHub Actions vẫn giữ. SMTP/provider/deploy thật chưa nghiệm thu. Docker có frontend production và cấu hình scale ở [deploy](../deploy/README.md).
