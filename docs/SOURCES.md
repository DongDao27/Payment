# Nguồn và trạng thái xác minh
Ngày soạn 21/10/2026. Yêu cầu kinh doanh dựa trao đổi người dùng và PRD1 đã đọc, không lấy từ tài liệu framework.

- PRD cũ: Thu-Thanh-PRD-va-Cau-truc-du-an.md, phiên bản1 ngày17/10/2026; build pack2 đổi thứ tự thành backend trước theo yêu cầu mới, không overwrite file cũ.
- Lab Laravel Framework đã đọc: migration/API/errors/UI/testing/security; tài liệu chuyển lab cũ nằm trong MD-CLEANUP-MANIFEST.json; tái sử dụng kiến thức, không copy UI TALL.
- Laravel12 Sanctum: https://laravel.com/docs/12.x/sanctum — đối chiếu session cookie SPA/CSRF.
- Laravel12 Database: https://laravel.com/docs/12.x/database — transaction.
- Laravel12 Queries: https://laravel.com/docs/12.x/queries — row locks, đối chiếu khi implement.
- Laravel12 Validation: https://laravel.com/docs/12.x/validation
- Laravel12 Scheduling: https://laravel.com/docs/12.x/scheduling
- Laravel12 Testing: https://laravel.com/docs/12.x/testing
- Laravel12 Deployment: https://laravel.com/docs/12.x/deployment
- Next.js structure: https://nextjs.org/docs/app/getting-started/project-structure — đọc quy ước app/src/route groups.
- VNPAY: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
- VNPAY FAQ: https://sandbox.vnpayment.vn/apis/docs/faqs/

Hai trang VNPAY không tải lại được trong lượt soạn gói này (timeout). Các invariant payment trong gói là yêu cầu kỹ thuật; chữ ký, ACK codes, endpoint, timestamp và QR capability phải đối chiếu tài liệu merchant cập nhật tại B07. Không tuyên bố đã kiểm thử kết nối Sandbox hoặc nhận quyền Production.
Chưa truy cập Figma hoặc repo nhà sách, chưa chạy test ứng dụng. Link tài liệu framework không tự chứng minh stack đang có đúng version hoặc bug-free.

## Implementation verification21/10/2026
PAY and querydr docs successfully retrieved from official sandbox.vnpayment.vn via curl after browser fetch timeout. PAY: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html ; query: https://sandbox.vnpayment.vn/apis/docs/truy-van-hoan-tien/querydr%26refund.html . Local snapshots ignored under .runtime. Adapter targets PAY2.1.0; no evidence of real merchant capability, network callback or in-store QR integration.
