# Bắt đầu và tiếp tục dự án
Hiện hành **V24 / D45**: Next.js + Laravel, giá giảm phần trăm, home/cache và bộ 1.000 đơn demo riêng. Đọc docs/STATUS.json và HANDOFF để phân biệt source local đã kiểm với mục tiêu 10k production chưa nghiệm thu. Guide: docs/CHAY-WEB-VA-LARAVEL.md; cấu hình/tải: docs/PERFORMANCE.md; source Figma: thu-thanh-figma-clicon/.

## Repository hiện tại

Không bootstrap hoặc import lại dữ liệu thật. Thu_thanh giữ 136 sản phẩm/giá/tồn/admin; thu_thanh_demo là dữ liệu giả lập riêng; thu_thanh_test dùng kiểm thử. Phí giao25k/pickup0,không đổi trả/hoàn tiền,VNPAY thực hoãn. Công thức doanh thu đã chốt D44, tiền hàng sau giảm giá/phí giao riêng. Backend strict và release gate chưa đạt do tích hợp/hạ tầng thật.

Build pack trùng và output cũ đã được archive an toàn ngoài repo; xem docs/REPOSITORY-MAP.md và CLEANUP-MANIFEST.json. Tài liệu Figma cũ đã lưu ngoài repo; xem docs/MD-CLEANUP-MANIFEST.json. Đọc source thực tế, không suy từ log cũ rằng tính năng đã được deploy.

## Đọc đầu phiên
AGENTS.md → docs/STATUS.json → docs/HANDOFF.md → cuối docs/PROGRESS.md → docs/DECISIONS.md → docs/PRD.md → task và đặc tả liên quan.
Không đọc tất cả hàng nghìn dòng mỗi tool call; đọc lại khi phiên mới, context mất hoặc chuyển module.

## Audit trên Windows PowerShell (không đổi dữ liệu)
Mở gốc repo nhà sách, không phải D:\laravel-lap\coffee-shop.
Chạy từng lệnh: git status; git branch --show-current; git log -5 --oneline; Get-ChildItem.
Nếu backend/artisan tồn tại: cd backend; php artisan --version; composer show laravel/framework; php -m.
Kiểm tra PHP/Composer/Node/npm/Git và database thực tế. Ghi docs/STATUS.json.
Không chạy git log ở repo chưa có commit rồi coi đó là hỏng dự án.

## Bootstrap khi repo THỰC SỰ chưa có backend
Từ gốc repo, sau kiểm tra phiên bản và .git:
composer create-project laravel/laravel backend "12.*"
cd backend
php artisan install:api
Cấu hình MySQL theo templates/backend.env.example. Tạo riêng thu_thanh_test.
php artisan key:generate chỉ nếu chưa có APP_KEY.
php artisan migrate để chạy các migration đã review.
php artisan route:list
Cài PHPUnit theo scaffold và Laravel Pint; tài liệu API có thể dùng Scramble. Thiết lập CI chạy MySQL trước khi coi kiểm thử transaction là đạt.

Không chạy migrate:fresh hoặc seed demo trên database đang có dữ liệu thật.
Admin bootstrap lấy password từ môi trường/nhập tương tác; không có tài khoản mặc định với mật khẩu công khai.

## Bắt đầu công việc
B00 audit/environment → B01 schema → B02 auth → B03 catalog/media → B04 inventory → B05 cart/quote → B06 checkout/order → B07 payment → B08 expiry/reconciliation/fulfillment → B09 reporting → B10 backend verification.
Dùng docs/TASKS.json làm nguồn dependency; thứ tự trên là trình tự mặc định.
Figma chỉ đọc metadata/quyền để chuẩn bị; không build UI trước gate.


Cập nhật D34 (22/10/2026): chủ dự án xác nhận yêu cầu giảng viên Next.js frontend + Laravel backend, cho phép bắt đầu frontend dựa backend local B00–B06 trong lúc VNPAY/revenue deferred. Quy tắc khóa frontend trước B10 ở trên được thay bằng ngoại lệ phạm vi này; gate backend nghiêm ngặt/release không passed. D33 stack conflict đã giải quyết, các thiếu sót rubric khác vẫn giữ.

Trạng thái22/10:136 SKU/ảnh đã nhập; frontend storefront/auth/cart/quote/order và admin cơ bản đã build/QA. Đọc VERIFICATION.md và frontend/README.md; không bootstrap hoặc nhập lại dữ liệu khi chỉ muốn mở web.
