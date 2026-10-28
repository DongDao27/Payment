# Phân công và hướng dẫn commit cho Đông, Thiện

Cập nhật 24/10/2026. Source chính: [DongDao27/Payment](https://github.com/DongDao27/Payment). [Project #4](https://github.com/users/DongDao27/projects/4) và T01–T18 đang thuộc repo [Thu-Thanh-bookstore](https://github.com/DongDao27/Thu-Thanh-bookstore/issues). Hai repo có vai trò khác nhau; không tạo bản code thứ hai trong repo issue.

Báo cáo ba tuần **11–31/10/2026**: Excel Đông / Thiện (đã đưa khỏi workspace; khôi phục từ commit `3e1b0fb`). Cập nhật GitHubD54:18issue vẫn OPEN;8Dev done,10In progress;đã tick135/176mục checklist có bằng chứng. Tỷ lệ task được đóng vẫn0% vì thiếu review/acceptance; checkbox không phải phần trăm hoàn thiện sản phẩm. Workbook có5sheet, công thức theo task/điểm, kế hoạch25–31/10 và hướng dẫn cập nhật. Đây là phân công đề xuất, không xác nhận tác giả code cũ. Số liệu kỹ thuật D49 bên dưới là snapshot trước D51; hiện D51 có27task kỹ thuật và118backendtests, không phải cùng mẫu số18task GitHub.

Đây là phân công đề xuất cho giai đoạn tiếp theo, theo tên hai thành viên chủ dự án cung cấp. Chưa có xác nhận username của Thiện, nên chưa tự gán tài khoản trên GitHub hoặc gửi lời mời. Đông/Thiện thống nhất bảng này rồi dùng assignee và PR để ghi nhận công việc thực tế.

## 1. Dự án đã làm được gì?

Source được đối chiếu tại `63d84ad4096f83802ab800760d380900cbc09924`; code D51 trong commit `3e1b0fb`. D53 local118backendtests/1.675assertions,7APIclienttests,Pint/build/typecheck,HTTPcookie/CSRF11checks vàops5checks đạt. [Backend CI](https://github.com/DongDao27/Payment/actions/runs/40082807261) và [Frontend CI](https://github.com/DongDao27/Payment/actions/runs/40082807245) của63d84ad đều success, đọc trực tiếp trongD54. Không chạy lại app tests khi chỉ cập nhật task GitHub/tài liệu.

TASKS.json hiện có27task kỹ thuật: **19done,3in_progress,4externally_blocked,1todo**. Đây không phải 19/27 tiêu chí chấm điểm và không đồng nghĩa hoàn thành 70% sản phẩm. Nhiều task done là các phiên cải tiến Figma/UI; một task chưa xong như thanh toán thật có thể ảnh hưởng nhiều luồng.

| Task kỹ thuật | Trạng thái đang ghi | Phần đã kiểm chứng / còn thiếu |
|---|---|---|
| B00–B06 | Done local | Nền tảng/schema, auth/quyền, catalog/ảnh, tồn/ledger/lock, giỏ/quote, checkout/snapshot/idempotency |
| F00–F01 | Done trong phạm vi local | Map plugin thiết kế, catalog/auth/cart Next.js; không đồng nghĩa canvas thật đã được chủ dự án duyệt |
| DUI01–DUI08, FUI01 | Done theo từng phạm vi | Prototype/luồng, footer/logo/ảnh/quickview/newsletter, landing theo sales, admin catalog/report |
| DUI09 | In progress | Giảm giá, cache, 1.000 đơn demo, cleanup đã kiểm; nghiệm thu 10k còn chờ hạ tầng |
| F02–F03 | In progress | Checkout/orders và admin đã có UI; nghiệm thu đầy đủ với provider/release chưa xong |
| B07–B10 | Externally blocked | Có source/test adapter, expiry/reconcile/report; chưa đạt gate toàn backend với provider và release acceptance |
| R00 | Todo | User testing đại diện, staging, restore và bàn giao |

Doanh thu đã chốt D44 và report local đã có; B09 là trạng thái gate rộng/lịch sử, không được hiểu rằng chart hoặc công thức doanh thu chưa được triển khai. Không tự sửa B07–B10 thành done chỉ vì CI xanh. Refund write bị cấm theo chính sách không đổi trả/hoàn tiền, không phải chức năng đang cần bổ sung.

| Task Project | Hiện trạng source / việc còn để đóng issue |
|---|---|
| T01 — Proposal/luồng | Nghiệp vụ đã ghi PRD/DECISIONS; cần hai thành viên review bản nộp |
| T02 — Figma | Plugin V24/mock/flow đã kiểm; cần import, duyệt canvas Web/Mobile và link Figma dùng chung |
| T03 — Nền tảng/API | Laravel/Next/MySQL/schema/43 API paths/CI có; cần xác nhận Thiện chạy được trên máy riêng |
| T04 — Catalog/home | Ba feed, tìm/lọc/sort/page, 136 SKU; browser local đã kiểm |
| T05 — Quickview | Guest xem, mua mới login, giữ số lượng; browser đã kiểm |
| T06 — Auth/quyền | Auth lõi/recovery local có tests; SMTP thật chưa nghiệm thu |
| T07 — Giỏ | Nhiều món/thay số lượng/xóa, quote/check tồn; đã kiểm |
| T08 — Checkout | Tạo đơn/giữ tồn/snapshot/idempotency, giao25k/pickup0; đã kiểm local |
| T09 — Online payment | D55 có retry/redirect/return/query, D56 sửa màu kết quả; merchant Sandbox thực tế chưa chạy |
| T10 — Pickup/QR | Đặt trước/QR mẫu có; QR thực chưa có adapter, trả503/qr_content=null |
| T11 — Theo dõi/hủy/hết hạn | Source, quyền, reconciliation, expiry/race tests có; cần nghiệm thu với provider thật |
| T12 — Sản phẩm/ảnh | Create/update/archive/restore, % giảm, upload; đã kiểm local |
| T13 — Tồn | D56 có giá trong bảng tồn, ledger phân trang/actor/order; quyền/phân trang và browser local đạt |
| T14 — Đơn admin | D56 có lịch sử attempts/receipts/trạng thái phân trang; giữ guard unpaid và resource whitelist |
| T15 — Dashboard/doanh thu | Chart/filter/CSV/tiền hàng sau giảm, ship riêng; demo synthetic riêng |
| T16 — Kiểm thử | Local118/7 và CI63d84ad đạt; coverage/Sandbox/staging chưa nghiệm thu |
| T17 — User testing | Đã có browser QA kỹ thuật; chưa có bằng chứng người dùng đại diện thực hiện nhiệm vụ |
| T18 — Deploy/bàn giao | Source GitHub/guide/config có; chưa có hosting/HTTPS/restore/load production/video bàn giao |

Toàn bộ T01–T18 vẫn OPEN sau cập nhậtD54;8Dev done và10In progress. Chỉ chuyển Dev done/In review và đóng khi có bằng chứng ứng với acceptance; không tick toàn bộ chỉ dựa vào bảng này. Source/tests cụ thể: `backend/app/Services`, `backend/tests`, `frontend/src/app`, `frontend/src/components`, `README.md`.

## 3. Bảng phân công 9 task/người

Điểm là **ước lượng tương đối cho phạm vi cả task**, không phải giờ đã làm, điểm môn học hoặc xác nhận tác giả code cũ. Hai người cùng đề xuất điều chỉnh sau lần review đầu tiên. Mỗi người 9 task, tổng36điểm; review chéo toàn bộ. Chức năng nhiều lớp được giao theo task, không chia cứng toàn bộ frontend cho một người và backend cho người kia.

| Task | Issue | Chủ trì | Điểm | Người review | Vùng làm việc chính |
|---|---:|---|---:|---|---|
| T01 | #1 | Đông | 3 | Thiện | PRD/DECISIONS/proposal |
| T02 | #4 | Đông | 5 | Thiện | thu-thanh-figma-clicon/; canvas |
| T03 | #5 | Thiện | 4 | Đông | backend schema/config, API contract, setup |
| T04 | #6 | Đông | 4 | Thiện | home/catalog UI; public snapshot hợp đồng chung |
| T05 | #7 | Thiện | 2 | Đông | quickview/auth continuation; component/test liên quan |
| T06 | #8 | Đông | 4 | Thiện | auth UI/actions/requests/policies/tests |
| T07 | #9 | Đông | 3 | Thiện | cart UI/CartService/quote/tests |
| T08 | #10 | Thiện | 5 | Đông | CheckoutService/snapshot/locks/idempotency |
| T09 | #11 | Thiện | 5 | Đông | payment adapter/IPN/receipt/test; còn hoãn provider |
| T10 | #12 | Thiện | 4 | Đông | pickup/QR/admin payment; đồng bộ Figma với Đông |
| T11 | #13 | Đông | 4 | Thiện | orders UI/OrderService/reconcile/expiry tests |
| T12 | #14 | Đông | 4 | Thiện | admin products/media/%price/CatalogService |
| T13 | #15 | Thiện | 4 | Đông | InventoryService/ledger/race tests |
| T14 | #16 | Thiện | 4 | Đông | admin orders/fulfillment/permissions/tests |
| T15 | #17 | Đông | 4 | Thiện | RevenuePanel/RevenueService/CSV/tests |
| T16 | #18 | Thiện | 5 | Đông | tests/coverage/CI/bug verification |
| T17 | #19 | Thiện | 3 | Đông | user tasks/observations; cùng Đông sửa UI |
| T18 | #20 | Đông | 5 | Thiện | deploy/runbook/README/report/video; cùng Thiện smoke |

Phần đã có source là trách nhiệm duy trì/kiểm chứng tiếp, không yêu cầu viết lại cho có commit. Thiện hỗ trợ Đông test Docker/restore/health; Đông hỗ trợ Thiện UI payment/user testing để cân bằng giai đoạn cuối. Sau mỗi tuần xem lại số PR, độ khó và phần còn chờ, không so công bằng chỉ bằng số dòng hoặc số commit.

`routes/api.php`, OpenAPI/types, shared StoreProvider/CatalogService và schema có thể bị nhiều task chạm vào: người đang làm thông báo người kia, merge lần lượt, không tạo hai bản API. Chỉ Đông cập nhật TASKS → PROGRESS → HANDOFF → STATUS sau khi PR được review; Thiện ghi evidence trong PR để tránh xung đột checkpoint. Điều phối này không thay đổi người review tính năng.

## 4. Source cũ và đóng góp từ hai máy

Toàn bộ source đã nằm trong commit nền chung trên Payment/main. Không xóa/reset repo hoặc chia lại file đã có rồi commit dưới tên hai người để tạo lịch sử công việc. Không đổi author hoặc ngày commit để mô tả ai đã làm gì trong quá khứ.

Mỗi người dùng tài khoản GitHub và email của chính mình; clone cùng Payment, tạo nhánh từ main mới nhất, làm thay đổi thực tế, test rồi push từ máy mình. Git ghi author từ cấu hình, không chứng minh máy vật lý đã dùng; bằng chứng đóng góp là diff, PR, review và test logs. Co-author chỉ thêm khi hai người thực sự cùng làm một thay đổi. Tài liệu phân công này là kế hoạch, không thay báo cáo đóng góp thực tế.

## 5. Thiết lập trên mỗi máy

Chủ repo thêm Thiện vào Settings → Collaborators bằng đúng username Thiện cung cấp; Thiện nhận lời mời trên tài khoản của mình. Project riêng tư cũng cần quyền riêng để Thiện xem/sửa board; thêm collaborator repo chưa chắc đã cấp quyền Project. Không dùng token hoặc đăng nhập của Đông trên máy Thiện. Không cần cài extension VS Code để commit.

Trong terminal PowerShell, trên cả hai máy:

```powershell
git clone https://github.com/DongDao27/Payment.git
cd Payment
git remote -v
git status
gh auth login
```

Thiết lập identity riêng cho repo, thay email mẫu bằng email được GitHub xác minh hoặc noreply của chính tài khoản:

```powershell
# Máy Đông
git config --local user.name "Đông"
git config --local user.email "EMAIL_GITHUB_CUA_DONG"
```

```powershell
# Máy Thiện
git config --local user.name "Thiện"
git config --local user.email "EMAIL_GITHUB_CUA_THIEN"
```

```powershell
git config --get user.name
git config --get user.email
gh auth status
```

Máy Đông hiện có checkout `D:\ThuThanh-WebDesign` với origin đúng Payment, có thể dùng luôn, không bắt buộc clone thêm. Không sửa/xóa `frontend/next-env.d.ts` đang bị Next dev thay đổi để dọn tree; xem `git diff` trước và chỉ stage file thuộc task. Không chuyển branch khi còn việc chưa lưu mà chưa xử lý.

Clone Git không mang theo `.env`, thư viện, MySQL hay 1.000 đơn demo. Theo [LOCAL-SETUP](CHAY-WEB-VA-LARAVEL.md) để chuẩn bị PHP8.4/MySQL/Composer/Node; dùng `composer install` trong backend và `npm ci` trong frontend. Tạo env từ template, tự chọn secret và admin local. Chỉ chạy migrate sau khi kiểm tra đúng database của máy mình; tạo APP_KEY một lần khi env mới chưa có key. Dữ liệu demo cần chạy công cụ chuẩn bị riêng theo [hướng dẫn chạy](CHAY-WEB-VA-LARAVEL.md), không copy/commit `.env.demo` hoặc database. `prepare-demo.php` dựa local MySQL provisioning hiện có; với MySQL khác phải cấu hình và kiểm tra trước, không giả định clone xong tự có dữ liệu.

## 6. Một task → một nhánh → một PR

Ví dụ Đông làm thay đổi thật của T12; chỉ dùng tên file dưới đây nếu đã thực sự sửa chúng:

```powershell
git status
git switch main
git pull --ff-only origin main
git switch -c feat/dong-t12-product-validation
# Sửa chức năng, chạy kiểm tra thích hợp.
git diff
git add frontend/src/components/product-pricing-fields.tsx
git diff --cached
git diff --cached --check
git commit -m "fix(catalog): clarify discount validation for T12"
git push -u origin feat/dong-t12-product-validation
```

Thiện cũng bắt đầu từ main mới nhất trên máy mình, ví dụ `test/thien-t16-payment-cases` khi bổ sung ca thiếu thực sự:

```powershell
git status
git switch main
git pull --ff-only origin main
git switch -c test/thien-t16-payment-cases
# Thêm ca kiểm thử còn thiếu; không sao chép test đang có.
git add backend/tests/Feature/PaymentTest.php
git diff --cached
git diff --cached --check
git commit -m "test(payment): cover an unverified callback case for T16"
git push -u origin test/thien-t16-payment-cases
```

Đây là mẫu thao tác, không phải khẳng định các sửa đổi ví dụ đã làm hoặc đang cần làm. Nếu không có diff phù hợp thì không tạo empty commit. Commit message mô tả đúng diff thật; `feat`, `fix`, `test`, `docs`, `refactor` tùy nội dung.

Tạo PR trong **Payment**, base `main`, bằng GitHub UI Compare & pull request hoặc `gh pr create --repo DongDao27/Payment --base main`. Nội dung PR gồm vấn đề, thay đổi, file chính, kết quả kiểm tra và link issue **đầy đủ**:

```text
Liên quan: https://github.com/DongDao27/Thu-Thanh-bookstore/issues/14

Thay đổi: mô tả hành vi trước/sau của diff thực tế.
Kiểm tra: lệnh và kết quả thật; phần chưa chạy ghi rõ.
Reviewer: Thiện (chọn đúng tài khoản sau khi xác nhận username).
```

Không viết `Closes #14` trong Payment để ám chỉ issue14 của Thu-Thanh-bookstore: số issue thuộc repo khác. Dùng URL đầy đủ/`Refs DongDao27/Thu-Thanh-bookstore#14`; sau merge và nghiệm thu, đóng đúng issue ở repo theo dõi bằng tay. Không tự chuyển issues sang Payment trong quy trình này.

## 7. Chạy kiểm tra và review

Với nghiệp vụ Laravel: chạy test module thay đổi trước; khi sửa tiền/tồn/quyền/schema, chạy regression và kiểm concurrency liên quan trên **thu_thanh_test**, không trên DB thật. CI cũng cần đạt trước merge.

```powershell
cd backend
php artisan test --filter=CatalogTest
php vendor/bin/pint --test
# Khi cần regression toàn bộ:
php artisan test
```

```powershell
cd frontend
npm run test:api
npm run build
npm run typecheck
```

Hai khối trên bắt đầu từ gốc repo ở terminal phù hợp; không chạy `cd frontend` khi vẫn đang đứng trong backend. Chạy browser QA cho thay đổi tương tác; 7 API-client tests không thay kiểm UI. Chỉ tài liệu: `python scripts/check_pack.py` ở gốc và kiểm link, không cần chạy lại toàn bộ test ứng dụng.

Người kia đọc diff, chạy lại ca quan trọng và phản hồi trong PR. Không chỉ bấm Approve để đủ hình thức; không tự approve PR của mình. Khi cả Backend/Frontend CI xanh và review xong, người điều phối merge bằng GitHub UI. Ưu tiên Create a merge commit nếu repo cho phép để giữ các commit tác giả; squash vẫn hợp lệ nếu nhóm thống nhất và giữ attribution chính xác, không dùng nó để tự nhận công việc của người kia. Không push trực tiếp main cho task thường ngày.

Board hiện có: `To do → In progress → Dev done → In review → Merge Main → Closed`. Đây là các trạng thái đã đọc từ Project. Dev done khi local đạt; In review khi mở PR; Merge Main khi đã review và chuẩn bị/đã merge theo quy ước nhóm; Closed chỉ khi merge + acceptance đạt. Không kéo task provider/deploy vào Closed khi vẫn external-blocked. Đính kèm PR Payment vào issue gốc để người chấm lần theo.

Sau merge, trên cả hai máy:

```powershell
git status
git switch main
git pull --ff-only origin main
```

Nếu nhánh task đang làm cần code mới từ main: commit công việc hiện có trước, `git fetch origin`, rồi `git merge origin/main` trong nhánh task. Nếu conflict, mở file, giữ phần đúng của cả hai, bỏ conflict markers, test lại, `git add` các file đã xử lý, `git commit`, `git push`. Không chọn đại toàn bộ ours/theirs, không force-push/reset main. Mỗi người tự thực hiện commit trên máy của mình; không gửi thư mục `.git` qua lại.

## 8. Bằng chứng cần giữ khi nộp

Ghi tên/username thật, task chủ trì/review, PR URL, commit SHA thật, ngày merge và lệnh/test result. Phân biệt code baseline có sẵn với phần mỗi người sửa từ sau phân công. Ảnh/video demo phải đúng bản commit đã chạy. Không có yêu cầu hai người phải bằng nhau về số commit; bảng36điểm là kế hoạch cân bằng, kết quả thực tế báo cáo trung thực theo PR.

Không đưa `.env`, password, token, APP_KEY, database dump hoặc customer data thật vào Git/PR/log. File mẫu có giá trị trống được giữ. Không bật VNPAY/deploy để tạo bằng chứng nếu chưa có yêu cầu và đủ điều kiện. Danh sách nguồn/giới hạn xem [KNOWN-LIMITATIONS](../README.md) và [PERFORMANCE](../deploy/README.md).

Các snapshot QA/audit cũ đã lưu ngoài repo theo [manifest dọn source](CLEANUP-2026-10-24.json); báo cáo Excel snapshot ngày24/10 có thể khôi phục từ commit `3e1b0fb`.

## Project sau cập nhật D54

Các tick ở task tài chính thể hiện adapter/fixture local. Review thành viên còn lại giữ chưa tick; không tạo PR/review giả. Master đã đổi về lịch11–31/10/2026 được chủ dự án chốt; checklist tổng chỉ tick khi task được nghiệm thu/đóng.

| Task | Project Status | Mục đã tick/tổng |
|---|---|---:|
| [T01](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/1) | Dev done | 8/9 |
| [T02](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/4) | In progress | 6/8 |
| [T03](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/5) | Dev done | 8/9 |
| [T04](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/6) | Dev done | 7/8 |
| [T05](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/7) | Dev done | 8/9 |
| [T06](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/8) | In progress | 10/12 |
| [T07](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/9) | Dev done | 8/9 |
| [T08](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/10) | Dev done | 10/11 |
| [T09](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/11) | In progress | 8/11 |
| [T10](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/12) | In progress | 4/10 |
| [T11](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/13) | In progress | 10/11 |
| [T12](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/14) | Dev done | 8/9 |
| [T13](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/15) | In progress | 7/9 |
| [T14](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/16) | In progress | 8/10 |
| [T15](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/17) | Dev done | 9/10 |
| [T16](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/18) | In progress | 8/10 |
| [T17](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/19) | In progress | 3/10 |
| [T18](https://github.com/DongDao27/Thu-Thanh-bookstore/issues/20) | In progress | 5/11 |

Board hiện tự đóng issue khi chuyển Dev done. Những lần đóng tự động trong lượt này đã mở lại, giữ statusDev done và pendingreview; không sửa workflow toàn Project. Assignee/milestone/item cũ giữ nguyên.


## Cập nhật code ngày 28/10 — D56

T13/T14 đã bổ sung các phần UI/API còn thiếu, đạt kiểm tra local. Payment mới hoàn tất code/cấu hình theo xác nhận chủ dự án; chưa merchant thực tế. Local127backendtests/1.773assertions,7clienttests,build/typecheck/Pint và browser scoped đạt; bảng Project D54 phía trên là snapshot, chưa tự coi đã review/đóng task. Các việc SMTP/canvas/user testing/staging/restore/10k còn điều kiện ngoài. Hướng dẫn hiện hành ở README/CHAY-WEB-VA-LARAVEL/deploy README; không cần template trùng đã dọn.
