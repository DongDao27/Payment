# Bắt đầu và tiếp tục dự án

Hiện hành31/10/2026: D68 hủy unpaid hợp lệ đưa hàng về giỏ một lần và mở /cart; giỏ dùng giá/tồn hiện tại khi tạo đơn mới. Màn kết quả giữ thông báo thanh toán, bỏ bảng chi tiết bên dưới. Backend140/1923, E2E13, API7/build/typecheck/Pint local passed. Receipt Sandbox accepted trước đó giữ nguyên; provider/ops/Figma sync còn chờ. Xem [hướng dẫn web/admin](docs/CHAY-WEB-VA-LARAVEL.md).

## Đọc đầu phiên

AGENTS.md → docs/STATUS.json → docs/HANDOFF.md → mục mới nhất docs/PROGRESS.md → docs/DECISIONS.md → docs/PRD.md → task/đặc tả liên quan. Kiểm git status/branch/diff/log và source thật, không suy tính năng hoàn tất từ tài liệu lịch sử. Không bootstrap lại ứng dụng đã có.

Backend strict gate vẫn externally_blocked; D34 cho phép frontend trong phạm vi local, release chưa đạt. Mở chức năng mới theo dependency trong TASKS; Laravel quyết định quyền/giá/tồn/trạng thái. Giữ test CI và kiểm nghiệp vụ trước UI khi thay hợp đồng.

## Giữ dữ liệu

`thu_thanh` có137sản phẩm; D61 chỉ giảm11–17% cho7SKU theo yêu cầu, giữ tồn/admin; đã xóa1.000đơn giả lập và gỡ code demo; `thu_thanh_test` chỉ dành kiểm thử. Không xóa/import lại/reset dữ liệu hoặc tạo thêm admin để sửa giao diện. Giữ env/APP_KEY/uploads/workbook và `.runtime/mysql-data`.

Giao25k chỉcheckout, pickup0, không đổi trả/hoàn tiền; doanh thu tiền hàng sau giảm, phí giao riêng. VNPAY Sandbox có code D55, user xác nhận chưa giao dịch merchant thật; SMTP/hosting/10k chưa nghiệm thu. Không dùng dữ liệu demo làm bằng chứng doanh thu thật.

## Repo sau dọn

Chỉ giữ source, đặc tả/hướng dẫn hiện hành, checkpoint, test tự động và tài liệu bàn giao còn dùng. Đánh giá/đề xuất, snapshotQA và công cụ fetch/import một lần đã lưu ngoài repo; xem [README/khôi phục](README.md). Không xóa các fetchAPI runtime, ảnh web/Figma hoặc test nghiệp vụ.

Lệnh kiểm tài liệu: `python scripts/check_pack.py`; ứng dụng: [README](README.md). Khi kết thúc, cập nhật TASKS → PROGRESS → HANDOFF → STATUS với kiểm chứng thực tế và phần còn chờ. Không bật provider/deploy thật từ yêu cầu dọn source.
