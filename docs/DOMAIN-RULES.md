# Domain rules — chuẩn triển khai
## Giá và báo giá
unit_sale = sale_price ?? list_price. Giảm giá dạng sale_price, chưa có voucher engine.
items_subtotal = sum(list_price * q); discount_total = sum((list_price-unit_sale)*q); payable_total = items_subtotal-discount_total+shipping_fee.
POST quote lấy cart hiện tại, validate active products/category và available. Lưu immutable snapshot/fingerprint, pricing_version, quote expiry, không reserve.
POST order nhận quote_id và Idempotency-Key, không nhận totals/role/paid. Tạo đơn phải đối chiếu cart fingerprint, product/catalog/shipping version và available dưới lock; thay đổi trả 409 PRICE_CHANGED/CART_CHANGED/INSUFFICIENT_STOCK, không âm thầm tăng tổng.
Quote consumed bởi đúng một order. Replay cùng key trả order cũ dù cart đã được dọn; key cũ payload khác trả 409.
D29: phí delivery mặc định25.000đ; env không có/trống dùng mặc định25000, pickup0. Cấu hình không hợp lệ vẫn503 SHIPPING_NOT_CONFIGURED, không tự miễn phí. Quote/checkout tính server và phát hiện phí đổi sau quote.
Mặc định tính revenue xem DECISIONS; không gọi báo cáo là toàn doanh thu nhà sách.

## Transaction và lock order
Với order đã có: lock order → products theo id tăng dần → reservations/attempts/receipts liên quan. Tất cả expiry/cancel/payment/admin dùng chung service và cùng thứ tự khóa.
D68: khách hủy chủ động khóa user/cart trước order để gộp giỏ, rồi tuân thủ thứ tự order → products như trên; không gọi provider khi đang giữ bất kỳ khóa nào.
Checkout mới: lock user/cart + idempotency row trong transaction → tạo order chưa commit → products tăng dần → items/reservations/movements → consume quote → clear đúng cart rows đã checkout → commit.
Unique constraint + DB transaction là hàng rào cuối. Tranh chấp idempotency insert dùng unique-retry, đọc kết quả của transaction thắng; không trả 500 vì duplicate key thông thường.
Không gọi HTTP provider/email trong DB transaction. Kết quả provider query ngoài transaction, sau đó lock và re-check version/state trước transition.
Deadlock retry hữu hạn cho transaction thuần DB; không lặp HTTP payment/create-order bên ngoài idempotency.

## Giỏ
Customer authenticated, một giỏ nhiều món. POST item thêm quantity vào dòng có sẵn; PATCH đặt quantity tuyệt đối; DELETE xóa. Request lặp add không mặc định idempotent; FE khóa submit và có thể thêm request key nếu cần.
Add/cart validation không reserve. available luôn recheck checkout. Nếu item đã inactive, hiển thị unavailable và chặn quote cho dòng đó; không xóa âm thầm.
Clear cart sau tạo đơn, không sau thanh toán. D67 bỏ thao tác đặt lại khỏi giao diện theo yêu cầu chủ dự án; API đặt lại giữ tương thích và vẫn dùng giá/tồn hiện tại nếu được gọi.
D68: hủy đơn unpaid hợp lệ đồng thời phục hồi sản phẩm vào giỏ trong transaction đóng đơn/release. Lưu event `cart_restored` dưới khóa order; request hủy lặp không cộng thêm kể cả sau khi giỏ đã được mua/xóa. Gộp món hiện có, tối đa99/món, giữ món inactive/thiếu tồn để hiển thị unavailable thay vì bỏ âm thầm. Giỏ không giữ tồn hoặc giá; quote/đơn mới kiểm lại toàn bộ. Expiry tự động không đưa hàng về giỏ; khách chủ động gọi hủy đơn đã đóng unpaid được phục hồi một lần. Paid, pending/đối soát chưa xác minh không được phục hồi qua hủy.

## Tồn và ledger
available = on_hand - reserved.
reserve q: reserved += q, movement reserve business_key order_item_id:reserve.
consume lần đầu đã verified và hợp lệ: on_hand -= q; reserved -= q; reservation consumed; movement sale.
release sau verified unpaid/cancel: reserved -= q, status released, movement release.
Mọi transition áp dụng một lần bởi reservation status + ledger unique. Không khóa chỉ bằng cache; cache lock là bổ sung, MySQL là căn cứ.
Admin nhập hàng/kiểm kho dùng adjustment, reason bắt buộc; nhập tồn mới không được thấp hơn reserved. Không chỉnh stock_reserved trực tiếp qua product PATCH.
Refund không tự restock. Trả hàng thực tế nếu có dùng adjustment riêng có audit; scope chỉ hỗ trợ adjustment, chưa xây RMA.

## Thời gian
delivery hold = order created_at + 300 seconds; pickup = +86400 seconds. created_at từ server transaction, không từ browser.
Tại now >= expires_at: không tạo attempt mới. Retry/reload/QR không reset hạn. attempt.expires_at <= order.expires_at.
Quote expiry khác hold. Hết quote không ảnh hưởng order đã tạo.
Backend so UTC, API ISO8601 UTC + server_time; FE Asia/Ho_Chi_Minh khi hiển thị.
Có thể provider time chứng minh giao dịch trước deadline nhưng notification về sau: chỉ accept nếu reservation vẫn active và đơn chưa đóng, amount/signature đúng. Nếu đã release/closed thì late case, không tự mở lại hoặc allocate lại stock.
Chưa có provider timestamp đáng tin/không rõ thời điểm: reconciliation_required.
Scheduler quét đến hạn; endpoint cũng kiểm hạn ngay, không chờ cron.

## Lifecycle và trạng thái
Order lifecycle: open → completed | cancelled | expired. Terminal không tự quay lại open.
Payment status: unpaid | pending | paid | reconciliation_required. Failure của một attempt không biến đơn thành terminal failed; tổng hợp lại sau query.
Fulfillment: unprocessed → preparing → ready_for_pickup (pickup) → fulfilled; delivery: unprocessed → preparing → shipping → fulfilled.
Pickup được prepare trước paid. Shipping/fulfilled cần paid; fulfilled chuyển order completed. Không auto-complete payment-return.
Reservation: active → consumed hoặc released; terminal không đảo chiều.
Attempt: created → pending → succeeded | failed | cancelled | expired | unknown; unknown có thể cập nhật sau query. succeeded không bị callback fail cũ hạ.
Paid order có khoản dư: giữ paid để tiếp tục đơn accepted, mở reconciliation case cho tiền dư; reconciliation flag không được xóa bằng việc đóng modal.
Không cho admin PATCH payment_status=paid. Chỉ payment evidence đã xác minh cập nhật paid.

## VNPAY và callback
Adapter dựa tài liệu gateway merchant được cấp; không tự gán generic endpoint/format cho mọi loại QR.
VND trong DB, chuyển vnp_Amount nhân 100 tại adapter theo tài liệu; kiểm roundtrip, không nhân hai lần. HMAC/canonicalization đúng provider và test bằng fixture.
Validate merchant, signature constant-time, reference, amount, currency nếu provider trả, response/transaction status và unique transaction id.
Return URL chỉ verify input để hiển thị/trigger query; không mutate payment success. IPN/server query verified mới tạo receipt.
D55: return đã ký có TransactionStatus02 được ghi như failure đã ký (attempt failed, không receipt) để khách thử lại bằng attempt/reference mới; query kích hoạt từ return mà không xác định thì giữ pending cho IPN/scheduler, không mở reconciliation case. Khách thanh toán online cho cả delivery và pickup; QR tại quầy của admin vẫn503 tới khi có capability merchant.
Luôn ACK IPN theo provider contract riêng, KHÔNG dùng JSON envelope chung của API. Lặp callback hợp lệ được ACK idempotent. Test exact response for invalid signature/order/amount/duplicate.
Provider có thể gửi unsigned noise: ghi security counter/log đã lọc; không ghi toàn payload không kiểm soát. Unique fingerprint giảm event lặp, không thay receipt uniqueness.
Tạo attempt: serialize theo order. Nếu attempt trước pending/unknown thì query/status thay vì tạo yêu cầu thu mới. Chỉ retry khi attempt cũ được xác nhận thất bại/đóng hoặc quy trình provider cho phép và đã test.
Multi attempts được giữ lịch sử. Giao dịch accepted đầu tiên allocate đơn; khoản thành công tiếp theo ghi duplicate receipt, không reserve/trừ hàng/revenue accepted lần hai.
Timeout tạo payment request: không coi là failed chắc chắn; status unknown, query trước retry.
FakeGateway chỉ test; demo/provider credentials thiếu phải trả rõ gateway unavailable, không success giả.
Không dùng screenshot/chuyển khoản âm thanh làm evidence tự động.

## Hủy, hết hạn và đối soát
Unpaid không có active attempts: có thể cancel/release atomically.
Pending/unknown: query provider ngoài DB rồi apply dưới khóa. Nếu chắc chắn chưa thu và request cũ không còn payable thì cancel/expire/release. Nếu provider không chắc thì reconciliation và không release tự động.
Có attempt quá hạn không đồng nghĩa chắc chắn tiền chưa về; kiểm semantics/query merchant.
Reconcile command theo batch, bounded retry/backoff, có alert/case khi quá ngưỡng; mặc định follow-up 15 phút là kỹ thuật đề xuất chứ không tự giải phóng tồn sau 15 phút.
Late receipt sau release: late case. MVP chọn hoàn tiền thủ công hoặc giải pháp người bán phê duyệt; không auto-reopen. API ghi bằng chứng refund không tự gọi provider.
Case chỉ resolved khi có note/evidence và decision hợp lệ, không cho “đã xử lý” xóa dữ liệu tiền.

## Doanh thu đề xuất
Revenue report phân biệt accepted_sales, shipping_collected, refunds, unallocated_receipts; không gọi tổng tiền chuyển về là doanh thu hàng hóa.
accepted_sales theo paid_at của đơn có accepted receipt, tiền hàng sau giảm, không phí giao.
Refund adjustments theo refunded_at trong khoảng báo cáo, tách hoàn tiền phí giao và tiền hàng; net_sales = accepted merchandise - verified merchandise refunds.
Duplicate/late/mismatch receipt chưa allocate không vào sales; hiển thị reconciliation totals.
Ngày theo Asia/Ho_Chi_Minh, chuyển boundary UTC để query; from inclusive, to exclusive. Không cộng paid đơn qua nhiều attempts.

### Report clarification for implementation (D27, proposal only)
Only refunds associated with accepted receipts reduce sales/shipping totals. Refunds of duplicate/late/mismatch receipts are unallocated_refunds, separate from net_sales. unallocated_receipts reports gross receipts during the period, not an outstanding balance. These defaults are test fixtures until revenue/refund policies are approved.

D30/D31 supersede refund proposal for current operations: no customer returns/refunds; refund writes403 even if old env enables them. Existing ledgers/evidence remain intact. VNPAY attempt creation is deferred503 by default; D55 opens local Sandbox only when a private env disables the deferral and carries the merchant's own credentials. Verified callbacks/reconciliation remain evidence-based.


## D45 — phần trăm giảm giá

Admin có hai cách nhập độc lập: giá sale_price chính xác hoặc discount_percent integer0..100. Không trộn hai nguồn. Percent được chuyển thành sale_price dưới product row lock và catalog_version check, `intdiv(list_price*(100-percent)+50,100)`;0% trở về null. Resource tính phần trăm hiển thị từ giá đang lưu, không tạo ledger phần trăm riêng. Giá/quote/order vẫn nguyên tắc snapshot ở đầu tài liệu.

Home bán chạy xếp tổng số lượng paid/accepted, không theo doanh thu, không theo cờ. Demo1000đơn nằm riêng database/provider demo, không trở thành bằng chứng thanh toán thật hoặc dữ liệu triển khai production. Cachepublic không được dùng để chấp nhận giá/tồn tại checkout.

## D51 — Liên hệ và khôi phục

Chủ dự án xác nhận295TônĐứcThắng,HòaKhánh,ĐàNẵng;0915466951;07:30–21:30hằngngày. Map footer dùng đúng pin16.0561478,108.1686171 và link place được cung cấp. Recovery tự động chỉ qua email khách đăng ký, chứng minh sở hữu hộp thư bằng token; không có SMSgiả hoặc thay quyền admin. Phí25kchỉcheckout/pickup0/no-refund/VNPAYdeferred và công thức doanh thu giữ nguyên. Biểu đồ thể hiện dữ liệu thật của môi trường đang chạy, không tự tăng giá trị0 hoặc pha phí giao vào doanh thu.
