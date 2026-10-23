# Đối chiếu môn Advanced Web Development — CSB35050

**Cập nhật22/10/2026 (D34/D35):** chủ dự án xác nhận giảng viên yêu cầu Next.js frontend/Laravel backend, nên stack conflict D33 đã được giải quyết theo yêu cầu mới. UI Next.js local đã được xây, backend84 tests/1353 assertions và frontend6 tests/build/browser QA có bằng chứng tại VERIFICATION.md. Phân tích21/10 bên dưới là snapshot trước khi build UI; không dùng nhận xét “chưa có UI/chờ xác nhận Next.js” ở bản cũ làm blocker hiện tại. Các mục token abilities/Scramble/Telescope/coverage/monitoring/GitHub/deploy còn thiếu vẫn chưa được tự miễn hoặc nhận đủ điểm.

Ngày audit: 21/10/2026. Phạm vi: hai DOCX người dùng cung cấp, source/config/routes/tests thực tế và bằng chứng kiểm thử đã lưu. Đây là đánh giá hiện trạng, không phải điểm do giảng viên chấm; không tự đổi kiến trúc hoặc cài package.

## Nguồn và cách hiểu

- `D:/Advanced Web - Final Examination Prep - CSB35050 - VNUK.docx`: gợi ý đề tài và công cụ. Các đề tài Vocabulary/Expense/Todo và mọi công cụ trong danh sách không đồng loạt trở thành yêu cầu bắt buộc.
- `D:/Advanced Web - Final Project Brief - CSB35050 - VNUK.docx`: Assignment Brief, Assessment Criteria, Rubric, CLO và Submission Guidelines; đây là căn cứ đối chiếu chi tiết. Bản có ngày ký 10/12/2025; chưa có bằng chứng về bản rubric khác.
- SHA256 lần lượt: `e8a94df72e5757dace5ac1118b2fc62fe193cd9a0a0fc49d56426bf57ed542e6`, `5be75981cb50f33a264248ca80fbe22314de972f9547c870979ba6b6fa4096ec`.

Đồ án chiếm 50% điểm tổng kết môn; các task trong rubric cộng thành 10 điểm đồ án, không phải 10 điểm toàn môn. Tài liệu trong DOCX được dùng làm tiêu chí đánh giá, không được hiểu là lệnh tự upload GitHub, đổi stack hoặc thực hiện giao dịch ngoài yêu cầu của người dùng.

## Kết luận

**Chưa đáp ứng đủ rubric để kết luận bài hoàn chỉnh.** Backend Laravel/MySQL có nền tảng và test tốt, nhưng rubric còn chấm UI cụ thể, external API hoạt động, coverage >70%, Sanctum token/abilities, Scramble, Telescope, slow-query alert, server monitoring, caching và quy trình nộp bài. Thêm danh sách sản phẩm/ảnh rồi làm Next.js sẽ chưa tự giải quyết các mục thiếu này.

Ký hiệu trong bảng: **Có** = có bằng chứng source/kiểm chứng tương ứng; **Một phần** = có nền tảng nhưng thiếu phần checklist; **Chưa** = chưa tìm thấy implementation/bằng chứng. Đây không phải quy đổi tự động sang điểm.

## Đối chiếu từng tiêu chí

| Mục | Điểm | Hiện trạng | Bằng chứng và việc còn thiếu |
|---|---:|---|---|
| 1a GitHub repository | 0,25 | Chưa | Repo local branch main, chưa commit, `git remote -v` trống. Chưa có GitHub repository/name/description được kiểm chứng. |
| 1b Commit messages | 0,25 | Chưa | Chưa có lịch sử commit; không thể chứng minh atomic commits hoặc references tới issues. Tạo lịch sử thật từ thời điểm làm tiếp, không bịa commit quá khứ. |
| 1c GitHub Issues | 0,25 | Chưa | TASKS.json là quản lý local, không thay GitHub Issues có mô tả và acceptance criteria. |
| 1d GitHub Workflow | 0,25 | Chưa theo checklist | Rubric ghi Pull Requests + Topic Branches + Issues liên kết mỗi PR. `.github/workflows/backend.yml` là CI Actions, đã có source nhưng chưa chạy remote và không thay các mục này. |
| 2a Schema/migrations | 0,5 | Có | Models/quan hệ/foreign keys/indexes/check constraints và 5 domain migrations; SchemaTest + migration test MySQL có bằng chứng trong VERIFICATION.md. Snapshots đơn là dữ liệu lịch sử có chủ đích, không dùng giá catalog mutable thay lịch sử. ERD dạng hình còn thiếu ở README. |
| 2b Model/factory/seeder | 0,5 | Có, demo còn tối thiểu | UserFactory/ProductFactory/CategoryFactory, CategorySeeder và DatabaseSeeder; SchemaTest kiểm idempotency. Dev chỉ có 6 danh mục, không seed tài khoản/giá giả. Bộ dữ liệu demo phong phú có thể làm riêng môi trường test/demo, không ghi đè dữ liệu thật. |
| 3a Blade + Livewire + Tailwind | 0,25 | Chưa | Chỉ có welcome.blade.php scaffold, web.php không cung cấp UI; chưa có Livewire/package hoặc storefront. Kế hoạch Next.js hiện tại khác stack ghi trong rubric. |
| 3b Registration/login + profile/dashboard | 0,25 | Một phần | AuthController/RegisterCustomer/hash/session và AuthTest có; chưa có form UI và profile/dashboard người dùng như checklist. |
| 3c Forms + CSRF + server validation | 0,25 | Một phần | FormRequest, StrictFormRequest, web middleware + 11 HTTP cookie/CSRF checks đã có. Chưa có form tương tác, hiển thị validation/error UI. |
| 3e Query/filter/display | 0,25 | Một phần | CatalogService dùng Eloquent, bộ lọc/search/sort/page đã có test; chưa display bằng Blade/components theo rubric. Nhãn 3e giữ nguyên tài liệu, không có mục 3d trong danh sách assessment. |
| 3f External API | 1 | Một phần, chưa nghiệm thu thật | VnpayGateway có signing/query/timeout/error paths và tests với fixture/fake. VNPAY_DEFERRED=true theo chủ dự án, chưa có external service thật được chứng minh chạy; không coi mock là đủ 1 điểm. Rubric cho phép service phù hợp khác như email/error tracking/storage; chưa chọn hoặc tích hợp thay thế. |
| 3g Unit/integration tests, coverage >70% | 1 | Một phần | Bằng chứng JUnit gần nhất 81 tests/1329 assertions, failures/errors/skipped=0; có Unit/Feature/Integration. Chưa có coverage report >70%; PHP hiện không nạp Xdebug/PCOV, CI ghi coverage:none. Không suy coverage từ số test. |
| 4a Resource controllers CRUD | 0,5 | Một phần | Controller theo resource/action, Eloquent/FormRequest/status codes và API v1 đã có. Product admin có index/store/update, public show; DELETE ảnh/cart items có thật. Sản phẩm chủ ý archive qua is_active, chưa có destroy/delete sản phẩm chuẩn. Cần chọn resource demo đủ C/R/U/D và kiểm thử rõ, hoặc xác nhận giảng viên chấp nhận archive; không xóa order/sản phẩm lịch sử chỉ để có DELETE. `apiResource()` không phải điều kiện tự thân. |
| 4b Sanctum token/scopes | 0,25 | Chưa đúng yêu cầu token | Package Sanctum/auth:sanctum có nhưng ứng dụng dùng web session/cookie. User chưa dùng HasApiTokens; chưa có issue/revoke token hoặc ability checks. Cookie auth không phải sai cho web first-party, nhưng chưa đáp ứng token/permissions checklist. Có thể bổ sung API client riêng bằng token, giữ cookie cho browser, sau khi chốt scope. |
| 4c Scramble auto docs | 0,25 | Chưa | Có docs/API.md, OpenAPI JSON và route/schema contract tests; composer.json không có dedoc/scramble hoặc setup route UI. OpenAPI viết tay không thay yêu cầu auto docs bằng Scramble. |
| 4d API tests, coverage >70% | 1 | Một phần | Auth/catalog/checkout/payment/API contract/concurrency tests đã có. Thiếu coverage report và ngưỡng >70% được kiểm chứng. Có thể dùng chung một báo cáo với 3g nếu phạm vi UI/API và mẫu số được nêu rõ; không cộng số test để suy điểm. |
| 5a Telescope | 0,25 | Chưa | Không có package/provider/setup Telescope. TELESCOPE_ENABLED=false trong phpunit.xml không chứng minh package đã được cài. |
| 5b Error handling/logging/notify | 0,25 | Một phần | bootstrap/app.php chuẩn hóa API errors, RequestContext gắn request_id; config/logging.php và callback error log có. Chưa có notification critical error đã cấu hình và test; chưa có error pages/UI tương ứng. Có template Slack channel mặc định không chứng minh alert thực sự hoạt động. |
| 5c Slow query alert | 0,25 | Chưa | Chưa thấy query-duration hook/listener, threshold, alert dispatch/dedup hay test. Log SQL hoặc Telescope tự thân không thay alert tới người quản trị. |
| 5d Server monitoring + thresholds | 0,25 | Chưa | Chưa có agent/dashboard CPU/RAM/disk/network và alert rules. app:environment-check, /up hoặc Docker template không thay monitoring host/server. |
| 6a Async processing | 0,5 | Có | DeleteProductImageFile implements ShouldQueue, retry/backoff; dispatch afterCommit; MediaTest và ops-smoke chạy worker/database queue thật có evidence. Jobs kiểm lại record trước xóa, failure cho retry. Scheduler không được đánh đồng với queue async. |
| 6b Resource loading optimization | 0,5 | Chưa | Chưa có UI/code splitting/lazy image/responsive images/bundle evidence. Validation JPEG/PNG/WebP và kích thước upload chưa phải resize/compression/optimization. |
| 6c Caching strategies | 0,5 | Chưa đủ | API/sanctum đang no-store,private để bảo vệ dữ liệu. Không thấy cache-aside public catalog/categories, invalidation hoặc static-cache configuration được kiểm chứng. Cache config bảng cache/rate limiter/scheduler locks không thay application caching. Không cache shared cart/order/user data để lấy điểm. |
| 6d Antipattern mitigation | 0,5 | Một phần | Catalog eager-load category/images, whenLoaded resources, pagination, DB indexes, transactions/locks, test last-item race và I/O provider ngoài lock đã có. Chưa có profiling/monitoring bằng chứng phát hiện bottleneck hoặc performance trước/sau; không khẳng định mọi query đã tối ưu. |

## Pattern/nguyên tắc đã dùng thật

| Pattern/nguyên tắc | Source để trình bày | Phạm vi kết luận |
|---|---|---|
| Service Layer, controller mỏng | `app/Services/CatalogService.php`, `CheckoutService.php`, `InventoryService.php`; admin ProductController truyền validated data vào service | Tách HTTP khỏi nghiệp vụ tiền/tồn. Chưa kết luận mọi service đều tối ưu hoặc thỏa toàn bộ SOLID. |
| Dependency Injection / phụ thuộc contract | PaymentService constructor nhận PaymentGateway; AppServiceProvider bind interface → VnpayGateway | Có bằng chứng Dependency Inversion ở boundary thanh toán; dễ thay implementation/test. Không cần tạo interface cho mọi service. |
| Adapter | VnpayGateway implements PaymentGateway | Chuyển payload/signature/query VNPAY thành contract nội bộ. Hiện chỉ một provider thật trong source; không gọi đây là nhiều strategy đã triển khai. |
| DTO bất biến | `app/Payments/VerifiedPayment.php`: final readonly class | Mang kết quả đã normalize qua boundary; giảm truyền mảng provider tùy ý trong domain. |
| Active Record / ORM | `app/Models/Product.php`, Order.php và các Eloquent relations | Pattern do Eloquent cung cấp; không phải Repository riêng tự viết. Factory dùng cho dữ liệu test không tự biến thành GoF Factory Method. |
| Policy + middleware + FormRequest | OrderPolicy, RequireRole, ProductRequest, StrictFormRequest | Tách authorization, input validation, serialization khỏi nghiệp vụ. Policy class là authorization policy, không tự gọi GoF Strategy vì trùng tên. |
| Resource/serialization boundary | `app/Http/Resources/ProductResource.php`, OrderResource.php | Whitelist response fields, whenLoaded tránh load quan hệ bất ngờ. |
| Queue job + after-commit side effects | DeleteProductImageFile và ProductImageService::delete | Giảm side effect trước commit, retry đáng tin cậy; có smoke worker thật. |
| Transaction/idempotency/locking | CheckoutService/InventoryService/PaymentService; Integration tests | Pattern bảo đảm tính nhất quán khi đồng thời/retry, không phải cứ có state strings là GoF State Pattern. Ledger/snapshot hiện tại cũng không phải Event Sourcing đầy đủ. |
| MVC | Models/controllers và Laravel framework | View ứng dụng chưa xây; không trình bày là MVC UI đầy đủ đã hoàn tất. |

Rubric CLO1 yêu cầu nguyên tắc thiết kế và design patterns, không quy định số lượng GoF pattern hoặc bắt buộc Repository/Singleton. Có thể trình bày các pattern trên bằng luồng thực và tradeoff; thêm lớp rỗng chỉ để gắn tên pattern không giúp đáp ứng tiêu chí còn thiếu.

## Xung đột với kế hoạch hiện tại

1. PRD/ARCHITECTURE đang chọn Next.js, rubric ghi Blade/Livewire/Tailwind và yêu cầu xin giảng viên chấp thuận stack/technique khác. Chưa có bằng chứng chấp thuận. Figma vẫn dùng được làm nguồn thiết kế cho cả hai phương án; source backend Laravel có thể giữ. Không tự chuyển stack trong lượt audit.
2. First-party cookie auth đang là quyết định dự án; môn yêu cầu Sanctum token có scopes/permissions. Có thể bổ sung API client token tách phạm vi thay vì chuyển browser sang bearer/localStorage. Chưa triển khai hoặc thay đổi hợp đồng.
3. VNPAY được hoãn hợp lệ cho tiến độ dự án, nhưng 3f/CLO5 vẫn chưa đủ bằng chứng external API thật. Không giả payment success hoặc tự bỏ yêu cầu của môn. Khi làm phần này cần VNPAY thật hoặc một dịch vụ phù hợp khác được chốt.
4. BACKEND_VERIFIED là gate dự án, khác rubric môn. Backend pass tests không đồng nghĩa bài nộp đủ UI/monitoring/GitHub/coverage, và gate payment hoãn không biến thành lỗi test.

Các conflict được ghi ở DECISIONS.md (D33, trạng thái OPEN); tài liệu này không thay thế quyết định stack của chủ dự án.

## Hồ sơ nộp bài và đánh giá cá nhân

- GitHub source, branches/commits/Issues/PRs thật: chưa có bằng chứng remote trong repo.
- README có overview/tools và cách chạy, nhưng thiếu screenshots chức năng chạy thật, ERD dạng sơ đồ và link video demo. DATABASE.md dạng text không thay hình ERD yêu cầu trong README.
- Video screencast tối đa 10 phút, ít nhất 720p MP4; link xem được (YouTube unlisted/public hoặc nơi khác), không private: chưa có evidence trong repo. Video cần demo yêu cầu chạy thật, không chỉ trình chiếu code/Figma.
- Self-Report của từng thành viên cho cả 5 sprints và các required assignments: chưa có bằng chứng được cung cấp. Chưa tìm thấy trong repo không đủ để kết luận sinh viên chưa nộp ngoài repo. Brief nêu thiếu/trễ Self-Report bị trừ 10% group mark; đóng góp cá nhân còn được đánh giá riêng.
- Chưa user-test/peer review UI theo usability/aesthetics/user-friendliness; Figma source không thay UI đã hoạt động. Không tự thêm customization nếu không áp dụng vào scope.

## Thứ tự xử lý đề xuất cho mục tiêu nộp đúng rubric

1. Chốt với giảng viên việc Next.js thay Blade/Livewire; nếu chưa có chấp thuận thì phương án bám rubric là Blade + Livewire + Tailwind dùng backend/services hiện tại. Không cần viết lại nghiệp vụ.
2. Thiết lập GitHub repository, issues và topic-branch → PR có link issue; tạo commit thật cho công việc tiếp theo. CI Actions là bằng chứng bổ sung.
3. Bổ sung Scramble, API token/abilities cho API client, Telescope; bảo vệ docs/monitoring và secret, không public mọi dashboard.
4. Chọn coverage driver Xdebug hoặc PCOV tương thích runtime và đo coverage source app; CI chạy coverage và kiểm ngưỡng >70%. VSCode PHP Debug extension không cung cấp PHP coverage driver. Không đổi mẫu số để che phần chưa được test.
5. Hoàn thiện UI/auth forms/dashboard/query display và external API thật; dữ liệu sản phẩm/ảnh là đầu vào, chức năng Excel/CSV import không phải yêu cầu bắt buộc riêng của rubric.
6. Làm critical-error/slow-query alerts và server metrics/thresholds có bằng chứng test nhận alert; resource loading và cache/invalidation có phép đo. Không tính việc chỉ cài package là hoàn thành.
7. Bổ sung ERD/screenshots/README, video demo và báo cáo từng sprint; kiểm checklist nghiệm thu trước nộp.

## Kiểm chứng của lượt audit này

Đọc DOCX trực tiếp bằng ZIP/XML (gồm nội dung trong table); xem Composer, routes, controllers, models, services, policies, requests/resources, job, provider, logging/nginx/CI config, factories/seeders và tests. `git status`, branch/diff/remote kiểm read-only: main chưa có commit, source untracked, remote trống. PHP modules không có Xdebug/PCOV; CI ghi coverage:none. Đọc JUnit lưu sẵn: 81 tests, 1329 assertions, 0 errors/failures/skipped; **không chạy lại tests hoặc đo coverage** trong lượt chỉ đánh giá tài liệu này. Không mutation schema, không cài dependency, không đổi application source, không upload hoặc deploy.
