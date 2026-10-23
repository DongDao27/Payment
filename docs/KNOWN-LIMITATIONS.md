# Giới hạn hiện hành — D45 / V24, 23/10/2026

- VNPAY/QR thật đang hoãn theo chủ dự án; không tự paid, không COD hoặc nút xác nhận tiền bằng tay. Provider adapter/failure/expiry/concurrency được kiểm bằng fixture, chưa là nghiệm thu Sandbox merchant thật. Đơn unpaid chưa được bàn giao.
- Công thức doanh thu đã được duyệt: tiền hàng sau giảm giá, phí giao hàng riêng. Báo cáo/chart/CSV đã có; database thật không bị thêm sales giả; thu_thanh_demo riêng có 1.000 paid orders synthetic để trình bày, không là doanh thu kinh doanh thật. Không đại diện toàn bộ doanh thu POS cửa hàng.
- Admin local có sản phẩm/ảnh/archive/restore/danh mục/tồn/đơn/doanh thu/đối soát. Không đổi trả/hoàn tiền. Tích hợp chứng từ/provider và mọi ca đối soát thực tế vẫn phụ thuộc giai đoạn thanh toán thật. Local password recovery không phải OTP/email recovery production.
- Figma V24: 126 frame/3 page/7 flow mỗi nền tảng,24 mẫu/1 SKU mua demo. Mock geometry/routes/rollback passed; canvas thật/font/Present/pixel comparison/chủ dự án duyệt còn pending. Web có đầy đủ136SKU và đơn nhiều sản phẩm.
- Bản tin lưu email/đồng ý thật, chưa gửi campaign. Không gắn chứng nhận, đối tác thanh toán hoặc hotline giả.
- 111 backend tests/1626 assertions,7clienttests/build/typecheck/browser passed. Coverage>70%, Lighthouse, accessibility audit toàn diện,18danh mục con riêng và mọi tiêu chí môn học chưa được nghiệm thu.
- Gitmain chưa commit/remote,CIremote/GitHubFlow/sprint evidence chưa chạy. Docker/hosting/staging/HTTPS/backup-restore/user testing/deploy chưa nghiệm thu; chưa đưa website lên mạng.

Không cần extension để chạy. Giữ .env/APP_KEY/MySQL data/workbook/uploads/evidence. Xem CHAY-WEB-VA-LARAVEL.md để chạy và đăng nhập admin. Backend strict gate vẫn externally_blocked do provider; frontend authorized_scoped,release not_started.

- Mục tiêu 10k concurrent chưa được kiểm chứng. Local 20 concurrent/200 requests đạt 0 lỗi; Docker/Redis/FPM/Nginx prepared nhưng chưa runtime/staging acceptance. Xem PERFORMANCE.md.
