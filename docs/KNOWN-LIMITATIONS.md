# Giới hạn hiện hành — 24/10/2026

- VNPAY/QR thật đang hoãn. Có adapter/failure/expiry/concurrency tests, chưa nghiệm thu merchant Sandbox/callback thật; đơn unpaid không được bàn giao, không có COD hoặc nút fake-paid.
- Email recovery khách đã kiểm local: token hết hạn/một lần, generic response, session revoke, CSRF/throttle và queued notification mã hóa. SMTP thật/worker delivery chưa nghiệm thu. Phone-only chưa có SMS; admin không reset qua public recovery.
- Doanh thu tiền hàng sau giảm, ship riêng; dashboard/chart/CSV đã có.1.000 đơn giả lập chỉ thuộc `thu_thanh_demo`, không phải doanh số thật hoặc toàn bộ POS cửa hàng.
- Figma V24/plugin local đã có; chưa xác nhận canvas thật/font/Present/pixel comparison hoặc chủ dự án duyệt. Web dùng đầy đủ136SKU, prototype chỉ24mẫu/1SKU demo.
- Bản tin lưu email/đồng ý, chưa gửi campaign. Không gắn chứng nhận hay đối tác thanh toán giả.
- Local118backendtests/1.675assertions,7clienttests/Pint/build/typecheck đạt. Chưa đo coverage>70%, chứng nhận fullWCAG/Lighthouse hoặc user testing đại diện.
- Docker/Redis/FPM/Nginx đã chuẩn bị, chưa nghiệm thu hosting/staging/HTTPS/backup-restore/production. Mục tiêu10k đồng thời chưa chứng minh trên hạ tầng thật.
- Source thuộc Payment; issue/Project4 thuộc Thu-Thanh-bookstore. GitHub task chưa đóng ở snapshot24/10; phân công Đông/Thiện không xác nhận tác giả code lịch sử.

Không cần extension để chạy. Giữ env/APP_KEY/database/workbook/uploads. [Hướng dẫn chạy](CHAY-WEB-VA-LARAVEL.md), [hiệu năng](PERFORMANCE.md), [kiểm chứng](VERIFICATION.md). Backend strict vẫn externally_blocked, frontend authorized_scoped, release not_started.
