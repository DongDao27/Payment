# AGENTS — Luật làm việc của AI
Áp dụng cho toàn repo Thu Thanh sau khi copy bộ tài liệu này vào repo. Đây không phải một skill cài đặt.

## Thứ tự ưu tiên
Chỉ dẫn mới nhất của chủ dự án > yêu cầu chức năng được chấp thuận trong PRD/DECISIONS > đặc tả domain/API/database > task. Figma là nguồn giao diện; không được tự đổi nghiệp vụ bằng hình vẽ.
Nếu hai đặc tả mâu thuẫn: ghi conflict trong DECISIONS, sửa đồng bộ trước khi code phần liên quan. Không đoán rằng đề xuất cũ là quyết định của người dùng.

## BẮT BUỘC đầu mỗi phiên hoặc khi tiếp tục sau gián đoạn
1. Đọc START-HERE.md, docs/STATUS.json, docs/HANDOFF.md, các mục PROGRESS mới nhất và DECISIONS.
2. Đọc PRD và task đang làm, sau đó đọc các đặc tả liên quan.
3. Kiểm tra git status, branch, diff, log gần nhất và file thực tế. Tài liệu done không thay được bằng chứng source.
4. Không chạy lại bootstrap nếu repo đã có ứng dụng. Không ghi đè việc chưa commit. Không reset/clean/xóa dữ liệu để sửa lỗi.
5. Chọn task dependency đã đạt, theo thứ tự B00 → B10. Không tạo nhiều AI cùng sửa file checkpoint. Nếu có cộng tác, một người duy nhất cập nhật checkpoint.

## BACKEND TRƯỚC
Giai đoạn đầu: Laravel API, migration, MySQL, auth, admin, giỏ, đơn, tồn, thanh toán, worker/scheduler, test và API docs.
Không tạo hoặc chỉnh giao diện Next.js cho chức năng mới trước gate BACKEND_VERIFIED.
Được dùng PHPUnit, HTTP client, Postman hoặc kiểm tra cookie trong trình duyệt để verify backend; đây không phải xây UI.
Thiếu Figma không chặn backend.
Thiếu VNPAY credentials: vẫn làm adapter/test/failure paths; đánh dấu external-blocked cho tích hợp thật. Không tuyên bố backend gate đạt khi thiếu kiểm chứng bắt buộc; tiếp tục các việc backend độc lập.
Không tự bỏ qua gate để “cho kịp”. Nếu chủ dự án đổi thứ tự, ghi quyết định mới có phạm vi rõ.

## Triển khai
- Laravel là nguồn quyết định quyền, tiền, tồn, trạng thái. MySQL là database; Eloquent/migration quản lý dữ liệu.
- Không sao chép session UUID guest/cart và mutable historical price của Coffee App sang logic giỏ/đơn nhà sách.
- PHP 8.4 và Laravel 12 là baseline phát triển để bám lab; kiểm tra repo, Composer và hỗ trợ phiên bản trước khi pin. Không downgrade/nâng major source đã tồn tại.
- Next.js App Router là frontend; TypeScript/Tailwind là mặc định kỹ thuật đề xuất, giữ lựa chọn tương đương nếu repo đã chốt.
- Code đơn giản, controller mỏng, FormRequest/Policy/Service, guard clauses; không nested if sâu, không repository layer/microservice không cần thiết.
- Dùng DB transaction/row lock và idempotency; queue/notification chỉ sau commit. Không gọi provider mạng khi đang giữ khóa database.
- Khóa secret không vào Git/log/NEXT_PUBLIC_*. Session cookie + CSRF cho first-party app; không mặc định lưu bearer token trong localStorage.
- Production/deploy/payment thật cần yêu cầu riêng, không suy ra từ yêu cầu tạo tài liệu.

## Mỗi task
1. Ghi tiêu chí đạt trước khi thay đổi.
2. Triển khai và chạy kiểm tra thích hợp; nghiệp vụ tiền/tồn/quyền cần test meaningful.
3. Ghi lệnh, kết quả thực tế, môi trường và commit/test log (không tự bịa hash).
4. Cập nhật API, schema, tests và quyết định nếu thay đổi hợp đồng.
5. Cập nhật TASKS.json → PROGRESS.md → HANDOFF.md → STATUS.json sau khi hoàn thành phần việc liên quan. Không checkpoint trạng thái done trước source.
6. Trước mutation schema, deploy, merge hoặc task mới, checkpoint ngắn; khi dừng giữa task ghi trạng thái in_progress cùng việc chưa xong.
7. “Done” chỉ khi implementation và acceptance test đạt; skipped/not-run/external-blocked không được gọi là passed.

## Cuối phiên
HANDOFF phải chỉ rõ: task hiện tại, source thay đổi, kiểm tra thực tế, lỗi/blocker, lệnh tiếp theo và dữ liệu cần giữ. STATUS ghi last_verified_commit null nếu chưa có commit, git_dirty thực tế, backend gate và next_task_id.
Không yêu cầu người dùng “continue” sau mỗi hàm nếu công việc còn được ủy quyền và không bị chặn.
