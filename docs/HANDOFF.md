# D53 — dọn repo/README hoàn tất, commit đang chuẩn bị (2026-10-24T16:35:11+07:00)

Đã dọn39file kiểmCRC/SHA vào archive ngoài repo tại manifest docs/CLEANUP-2026-10-24.json. Bỏ đánh giá/đề xuất/testplan dư,snapshotQA và scriptfetch/import/fixturebrowser dùng một lần; giữ bộtest tự động/CI,HTTP+opssmoke,k6profile,đặc tả/runbook,guidehai người vàXLSX11–31/10. Không xóa data/env/workbook/uploads hoặc ảnh web/Figma. Các đường dẫnQA trong checkpoint cũ là member archive, không file hoạt động.

README mới mô tả web/admin/contact/recovery,localhost3000/8000,MySQL3307,DBreal/demo/test,SMTP và productionlimits. LOCAL-SETUP/START-HERE/VERIFICATION/frontendREADME/REPOSITORY-MAP đã đồng bộ,docschecker kiểm23required/27tasks/43paths và link hoạt động.

Test trước commit:118backendtests1675assertions0fail/skip CLI38.42s;Pint/Composer validate/platform passed;cookieCSRF11/ops5checks passed.7clienttests/build/typecheck passed. Composer/npm audit0;136HD/24Figma/3banner/8manufacturerassetspassed. MySQL vàserverdev đã chạy sẵn được giữ. BrowserD51 không chạy lại;SMTP/provider/staging/restore/10k không nghiệm thu. JUnit ởstorage/logs bịignore; manifest chứa kết quả ngắn thay QA snapshot mới.

Gitmain đang có toàn bộ sourceD51 chưa commit và cleanupD53; không reset/rewrite authorship. Bước tiếp: stagedsecret/runtime/size/diff review → commit thật theo yêu cầu; cập nhật hash bằng chứng sau commit. Backend externally_blocked/frontend authorized_scoped/release not_started,DUI09 pending không đổi. Cần SMTP thật/canvasreview/provider/hosting như các giới hạn trongREADME.

# D52 — báo cáo Excel hoàn tất, 2026-10-24T15:47:12+07:00

File: docs/reports/Bao-cao-Dong-Thien_11-31-10-2026.xlsx;5sheet, ba tuần11–17/18–24/25–31/10, mỗi người9task36điểm theo đề xuất D49. GitHub snapshot24/10:18OPEN/To do, tỷ lệ nghiệm thu0% cả hai; hiện trạng code local riêng. Không xác nhận tác giả lịch sử, không dựng ngày làm việc quá khứ. Kế hoạch tuần cuối gồm review từngtask, user testing/coverage/bản nộp; provider/deploy còn điều kiện, chưa hứa31/10 đạt production.

Kiểm độc lập openpyxl:54formulas/cachedvalues,26hyperlinks,18task đúngmapping/điểm;ZIPCRC passed. Excel COM readonly tính lại0%/0%, exportPDF và xem ảnh: tổng quan1trang, tuần3trang không orphan. Audit docs/qa/weekly-report-audit.json, snapshot docs/qa/weekly-report-github.json. Tái tạo bằng python scripts/export-weekly-report.py --offline; bỏ --offline để đọc GitHub mới. Tái tạo sẽ ghi đè chỉnh sửa tay trong workbook; Excel cộtJ dành tự đánh giá riêng, G/H là state/reason để tính nghiệm thu. Không chạy lại test app, số118/7 vẫn bằng chứng D51; check_pack/diffcheck sau checkpoint.

Gitmain503ca3d còn dirty, giữ toàn bộ thay đổi chưa commit D49–D51 và next-env có sẵn. Không stage/commit/push, không mutation GitHub/DB/env/source app. Giữ136SKU/1000demo/admin/uploads/workbook gốc; backend externally_blocked/release not_started/DUI09 pending không đổi. Python reporting libs và PDF preview chỉ trong .runtime/output bịignore; Excel automation đã đóng sau export.

Tiếp theo: hai thành viên review các issue thật, đính kèm evidence/PR và cập nhật đúng trạng thái GitHub; mở fileExcel để báo cáo. Muốn tiếp tục nghiệm thu app: theo D51 bên dưới, cần SMTP thật/canvas/provider/staging theo phạm vi được cấp. Báo cáo không tự thay lịch MASTER GitHub4tuần thành3tuần.


# D51 / DUI10 — hoàn tất local, SMTP thật còn chờ cấu hình

Footer Google place đúng link/pin chủdựán,hotline0915466951,07:30–21:30hằngngày,iframe lazy/no-referrer vàmaptiles đã xem được. UX07 link44px/focus/mobileaccount label; UX08 forgot/reset email+hiệnẩn+inlineerror. Backend brokerhash/60phút/oneuse/customeronly/generic/CSRF/rate/encryptedqueue,transaction lock/sessionrevoke và HMAC binding/Sanctum middleware cho cảsessionngoàiDB. Không schema migration,khôngSMShayadminpublicreset. Mailerlog/arrayproduction503;SMTP thật/worker delivery còn cần cấu hình và nghiệmthu.

Admin7naviconskhác nhau,4KPIicons,màu/card/active navigation,header/footer riêng. Chartrevenuearea/orderbars,trục0/đơnvị/grid/legend/readout,keyboardroving/scrollresponsive,7/30/90presets/month/CSV,data thật theoAPI. Đã sửa320headeroverflow,selectaria-label vàchartResizeObserver. Đánhgiáadmin/cácđềxuấttiếp ởDANH-GIA-SAN-PHAM;FigmaV24 không đổi.

Full118backendtests1675assertions0fail/error/skip,CLI32.41s/JUnit31.902844s;fullPintpass. Nextbuild/typecheck7clienttests pass. BrowserD51isolateddemo1440/390/320 map/phone/hours/hittarget/reset+newlogin/roleicons/chartAPI/keyboard/month/CSV,0pageerrors. Demo adminhash phục hồi,QAuser xóa,1000paid/136active/zeroreserved giữ;realDB/env/workbook/uploads không sửa. SMTP/SMS/providerpayment/Redisruntime/fullWCAG/staging/10k không gọi passed.

Source/docs mới chưa commit/push;gitmain503ca3d,dirtytrue,last_verified_commitnull cho task chưacommit. Bảo toàn sửa docsD49/D50vànext-env dev cósẵn. Evidence docs/qa/D51-browser.json,D51-data-preservation.json,JUnit. API43paths vàtestcases90 đồngbộ. TaskDUI10done trongphạmvilocal;DUI09capacity/backendgate/release vẫn giữpending nhưtrước. Tiếptheo:chủdựán cấu hìnhSMTP+FRONTEND_URL thật,worker/scheduler theoCHAY-WEB-VA-LARAVEL vàtestdelivery;phone-only liênhệnhàsách,khôngfakeOTP. QAserver/browser do phiên này mở dừng saukiểmtra;đểxemweb chạyguide demo nhưtrước.


# D50 — đánh giá thiết kế/mức sẵn sàng mở bán

Report docs/DANH-GIA-SAN-PHAM.md có P0/P1/P2,tiêu chí đạt và đề xuất phối hợp Đông/Thiện. Browser guest thật1440/390/320 không tràn ngang; home6073/8837/8890px,sản phẩm đầu khoảng1593/1313/1292px,emptybestseller trongDBthật,2bannerphụcùng/products,nútaccountheadermobilekhôngaccessible name. Quickview mở/Escapeđóng được. Search0results/login/cart/admingate/policies xemreadonly;source checkout/payment/readme/metadata đối chiếu. Không dùng screenshot ảnh chưa tải để kết luận banner hỏng;banner naturalWidth đã xác nhận. Admin có quyền/checkout mutation không chạy lại;source+QA cũ ghi rõ.

Kết luận có thể demo/chạy thử,còn P0payment/supportpolicy/stagingrestore trước mởbán. Giảm banner/items,brandassetriêng,bookmetadata/recovery/SEO/accessibility là đề xuất,chưa chủdựán duyệt và chưacode. Giữ quy tắc phí25kchỉcheckout,pickup0,no-refunds,VNPAYdeferred,khôngthêmCOD/fakepaid. Không tuyên bố WCAG/Lighthouse/productionload đạt.

Evidence docs/qa/design-readiness-audit.json;ảnh output/playwright/design-review-*.png (ignored). Link/fence/bằngchứng kiểm đạt;check_pack/diffcheck chạy sau checkpoint. Không chạy lại applicationtests vì khôngđổisource;111backend/7client vẫn bằngchứngD48. Gitmain503ca3d,dirtythực gồm docsD49/D50vànext-envcósẵn,khôngstage/commit/push. GiữDB/env/workbook/uploads. Browser+haiHTTPserver do lượt này mở được đóng sau review,MySQLcũgiữ. Tiếp theo chủdựán chọn scopeUX/P0để triểnkhai;DUI09capacity/gatesgiữnguyên.


# D49 — hướng dẫn phân công/commit cho Đông, Thiện

Đã tạo docs/PHAN-CONG-VA-COMMIT.md: bảng hiện trạng từngT01–T18, phân biệt code local/acceptance;18/26task kỹ thuật done,3inprogress,4externalblocked,1todo. Bảng đề xuất Đông9task36điểm/Thiện9task36điểm,reviewchéo,không là xác nhận ai viết code cũ. Username Thiện chưa biết nên không tự assign/invite; không mutation GitHub hoặc viết lại lịch sử. Source vẫn Payment; issues vẫn Thu-Thanh-bookstore/Project4. Hướng dẫn clone/identity/env/nhánh/commit từngfile/test/PR đúngrepo/refissuefullURL/merge/conflict và bảo toàn dữ liệu.

Xác minh GitHub: Backend/Frontend CI của503ca3d đều success. Sửa README/KNOWN-LIMITATIONS và đặt cập nhật đầu COURSE-RUBRIC-AUDIT để ghi chú cũ không bị hiểu thành hiện trạng. Ưu tiên tiếp: PR/review thật của hai người,clone máy thứhai,canvasreview,coverage/user testing;payment/deploy/10k chỉ mở khi đủ điều kiện. Không gọi đủ điểm hoặc release done.

Kiểm tra tài liệu: đủ18taskduynhất,9task36điểmmỗingười,18reviewchéo,Markdown fences/linklocal đạt; check_pack và git diff --check được chạy sau cập nhật. Evidence docs/qa/team-guide-audit.json. Không chạy lại111backend/7clienttests vì chỉ sửa docs; số test từ D48. Giữ nguyên thay đổi có sẵn frontend/next-env.d.ts do Next dev dùng .next/dev/types; không stage/commit/push hộ trong lượt yêu cầu tạo guide này.

Gitmain HEAD503ca3d,dirtythực gồm docs mới/sửa và next-env của người dùng;last_verified_commit vẫn33428a4source đã test,D48. Bước tiếp theo: hai thành viên thống nhất bảng,thiết lập identity/collaborator/Project đúng tài khoản,rồi mỗi máy làm diff thật và mởPR. Đông là người cập nhật checkpoint sau review. Gates/payment/no-refund/DB/workbook/env giữ nguyên;DUI09capacity vẫn pending,nohosting.


# D48 — source đã commit/push vào Payment, 24/10/2026

Repo source hiện hành: https://github.com/DongDao27/Payment, origin HTTPS, main tracking origin/main. Source commit thật: 33428a4a1a42cf20dea27d5ce5f63b94441a2f94. Đã push không force, đối chiếu ls-remote main khớp và tree sạch trước checkpoint tài liệu này. 525 file source/assets/workbook/spec/tests/config được đưa lên; env/database/runtime/vendor/node_modules/output sinh tự động không vào Git. Repo issue Thu-Thanh-bookstore và Project4 vẫn giữ nguyên, không tự chuyển issue.

Kiểm tra chạy lại trước commit:111backendtests/1626assertions/0errors/failures/skips,CLI38.81s/JUnit33.517170s; fullPint passed;7APIclienttests,Nextbuild/typecheck passed;check_pack passed. Quét index theo mẫu secret phổ biến, runtimepath và giới hạn100MB/file đạt; không là audit bảo mật toàn diện. Chỉ chuẩn hóa newline/EOF tài liệu, không đổi nghiệp vụ. Package Figma được đóng lại và kiểm CRC/source bytes sau newline guide, SHA hiện hành trong STATUS/FIGMA-V24-REVIEW.

GitHub Actions Backend/Frontend đã tự chạy khi push; kết quả cuối đang pending, không gọi remote CI passed. Evidence: docs/qa/source-publication.json. Checkpoint này là commit tài liệu riêng sau source commit đã xác minh. last_verified_commit trỏ commit source được test; git_dirty ghi đúng thời điểm đang cập nhật checkpoint, không coi trường đó là trạng thái live sau push.

Tiếp theo: kiểm tra Actions trên Payment; nhóm phân công/review issue T01–T18 ở Project4 và liên kết PR đúng repo. Provider/canvas/deploy/10k vẫn chưa nghiệm thu; backend strict externally_blocked,release not_started. Giữ database thật/demo,env,workbook/uploads và archive ngoài repo; không reset/import lại. Hướng dẫn chạy docs/CHAY-WEB-VA-LARAVEL.md.


# D47 — bổ sung GitHub Project hoàn tất, 24/10/2026

Repo GitHub đã xác minh: DongDao27/Thu-Thanh-bookstore. Project riêng tư DongDao27/4. Có sẵn T01–T07; đã tạo 11 issue T08–T18 (#10–#20) và MASTER #21. Mỗi T01–T18 đúng một issue, đủ milestone M1–M5 và có trong Project; MASTER liên kết đủ18 task. Task kỹ thuật local được gom theo các đầu việc chức năng trong bản master người dùng gửi, không nhân đôi thành board khác. T03 được gắn lại milestone M1 còn thiếu.

Đã thêm checklist/acceptance/phụ thuộc, cập nhật landing/guest quickview/giảm giá/doanh thu/demo và giới hạn payment/deploy theo quyết định hiện hành. Không đóng issue hoặc tự đánh dấu checklist hoàn tất. Giữ item cũ tạo repository, trạng thái và assignee đã có. Không tự đặt ngày kế hoạch, người review hoặc người phụ trách cho nhóm khi chưa chốt phân công.

Kiểm chứng qua GitHub CLI/API: 18 issue duy nhất, milestone đúng, thành viên Project đúng, MASTER đủ link; evidence docs/qa/github-schedule-sync.json. gh đã được chủ tài khoản cấp quyền Project qua device flow; không lưu token vào repo. Không thay source ứng dụng, database/env hoặc remote của repo local. Git main local chưa có commit/remote; last_verified_commit=null, dirty=true. Application tests không chạy lại vì chỉ đồng bộ issue/tài liệu, không thay hành vi ứng dụng.

Tiếp theo: nhóm phân công và review task, đính kèm PR/test thật trước khi đóng. T09/T10 provider còn hoãn; T17 cần user testing đại diện; T18/10k chờ hạ tầng. Backend strict/release gate giữ nguyên; phần capacity của DUI09 vẫn mở. Giữ database thật/demo, workbook và secrets như trước. Hướng dẫn chạy vẫn docs/CHAY-WEB-VA-LARAVEL.md.


# D46 — dọn Markdown hoàn tất

Đã xóa 12 file Markdown không còn cần trong repo: tám review cũ, hai prompt khởi động/tiếp tục trùng luật hiện hành, hướng dẫn chuyển lab và frontend verification đã gộp vào docs/VERIFICATION.md. Archive ngoài repo kiểm CRC/SHA trước xóa; xem docs/MD-CLEANUP-MANIFEST.json. Tham chiếu và scripts/check_pack.py cập nhật theo bộ tài liệu còn dùng. Không xóa AGENTS/checkpoint/đặc tả/guide/audit môn học/nguồn asset; không đổi ứng dụng, DB, env hoặc ảnh.

Kiểm tra thực tế: python scripts/check_pack.py đạt (27 required,26tasks,22functions,41APIpaths,86scenarios); audit link Markdown local không có link hỏng; git diff --check đạt. Không chạy lại application tests vì thay đổi chỉ tài liệu và kiểm tra tài liệu. Kết quả 111 backend/7client tests bên dưới thuộc phiên D45 trước đó.

DUI09 local đã verified, phần 10k vẫn chưa nghiệm thu. Chủ dự án xác nhận chưa có hạ tầng deploy; bước tiếp theo khi sẵn sàng là chọn staging rồi kiểm runtime/load theo PERFORMANCE.md. Payment thật vẫn hoãn. Git main chưa có commit, dirty=true, last_verified_commit=null; giữ thu_thanh/thu_thanh_demo/env/workbook/uploads. Chạy web theo docs/CHAY-WEB-VA-LARAVEL.md; không cần import lại database. Các checkpoint dưới đây là lịch sử.


# D45 / DUI09 — V24 local verified; production capacity pending

Đã thực hiện footer/ngọn lửa/giá giảm theo phần trăm, public cache tách session và gợi ý bounded; tạo đúng 1.000 đơn ngẫu nhiên trong thu_thanh_demo riêng. Bestseller theo tổng lượng bán paid/accepted; sản phẩm mới theo created_at/id. Dữ liệu thật 136 sản phẩm/giá/tồn/admin giữ nguyên. Không bật provider thật.

Kiểm tra thực tế: 111 backend tests/1.626 assertions/0 lỗi hoặc skip (JUnit 28,433283s), full Pint; Next build/typecheck và 7 API-client tests đạt. Browser khách/admin/customer ở 320/390/1440 đạt, không page error; đã kiểm tra CRUD/giảm giá/new feed/quote/role/report. Demo audit 1.000 paid orders và accepted demo receipts, tổng tiền khớp, 136 sản phẩm active, zero reserved units. Đã phục hồi hash mật khẩu demo admin và thu hồi phiên QA; không đổi admin thật.

Load local 20 đồng thời/200 request: 0 lỗi, 35,77 request/s, p95 601,13ms sau khi nạp OPcache; trước đó 5,45 request/s/p95 3.710,22ms. Đây là PHP development server một worker, không chứng minh 10k. Docker/Redis/FPM/Nginx config và k6 profile đã chuẩn bị, YAML parsed; chưa có Docker/k6/staging để build/nghiệm thu. Hỏi chủ dự án hạ tầng; không triển khai hoặc mua tài nguyên.

Archive 149 file trùng/cũ ngoài repo có checksum và manifest khôi phục: docs/CLEANUP-MANIFEST.json; map source docs/REPOSITORY-MAP.md. V24 Figma local mock/ZIP đạt, xem docs/FIGMA-V24-REVIEW.md; canvas thật chờ chủ dự án import/duyệt. Không bỏ workbook, env, DB, uploads hoặc source hiện hành.

Chạy demo: hai terminal ở gốc repo gọi scripts/start-demo.ps1 -Component backend và -Component frontend (dừng Next cũ cổng3000 trước). Admin: /admin, admin-demo@example.test; tự chọn mật khẩu bằng php artisan admin:password --env=demo từ backend. Xem docs/CHAY-WEB-VA-LARAVEL.md. Không có password công khai. Không cần tạo/import lại database demo.

Việc tiếp theo: cung cấp hosting/staging/CPU/RAM; build và kiểm tra Docker, HTTPS, backup/restore rồi chạy tải theo docs/PERFORMANCE.md trong phạm vi được phép. Payment thật/VNPAY vẫn deferred; backend strict gate externally_blocked, release not_started. Git main chưa commit/remote, last_verified_commit null, git_dirty true. Giữ cả thu_thanh, thu_thanh_demo, .env.demo và demo-storage; không migrate:fresh hay seed demo vào database thật.

Evidence: docs/qa/DUI09-*.json, backend/storage/logs/phpunit.xml, output/figma/v24-package.json. Các số liệu cũ bên dưới là lịch sử, không thay cho checkpoint V24 này.

# D45 / DUI09 in_progress

Discount/cache/backend first;1000random orders only separate demoDB;no deployment/10k claim. Backups v23 under .runtime.

# Checkpoint mới nhất — D44 / DUI08 done

## D44 / DUI08 — V23, 2026-10-23T16:45:42+07:00

Hoàn tất phạm vi local: landing chỉ ba feed bán chạy/mới/gợi ý; bỏ SGK/VPP navbar và các category sections, giữ catalog/dữ liệu. Paid ranking SUM quantity + accepted receipt EXISTS, không adminflag hoặc unpaid. Gợi ý đa dạng/còn tồn và chỉ ưu tiên giỏ/đơn paid/accepted của chính khách. Card home4cols, ảnh330px desktop/220px mobile và display-only whitespace bounds108nguồn; font/icon/text quickview căn giữa,44px minimum. Ảnh admin uploads không bị crop import cũ.

Admin có sản phẩm/ảnh/create/edit/archive/restore/statusfilter,category CRUD hide/show,stock/orders,RevenuePanel dashboard + /admin/revenue (date range/day-week-month/zero-fill/chart/CSV,tiền hàng sau discount,shipping riêng),reconciliation list/manualreview/verified-unpaid có backendcondition. /admin entry ở footer + AccountGate hướng dẫn;CLI admin:account chỉ đọc,admin:password local interactive confirm/secret twice/sessionrevoke. Không chạy reset credential dev. D15 đã được owner chốt qua D44;approved_v1 bật tronglocal/default/templates,refund disabled,VNPAY deferred.

Backend100tests1476assertions0errors0failures0skips,CLI28.67s,JUnit24.869672s;Pint fullpassed sau sửa line endings config/store.php. Môi trường PHP8.4.25/Laravel12.70/MySQL8.4.13 thu_thanh_test,không dev reset. Next build/typecheck/6APIclienttests passed final. Browser320/390/1440,guestquickview/adminlogin/product create-edit-archive-restore/categorycreate-hide/day-monthreport;positive accepted+discount fixture riêng testDB/chartshipping25000/CSV/imageupload-delete/stockadjust/manualcase;customerregister qty2 buyNow checkout pickuporder/adminUI403+revenue403/adminprepare-ready/unpaidfulfillmentblocked,0uncaughtpageerrors. JS confirm trong automation được chấp nhận bằng stub để CLI không dừng ở dialog; backend money/version/permission riêng meaningfultests. Một lượt browser tạm ngắt do máy/browser session đóng trong thời gian chờ; mở lại session và chạy đạt,không thay dev.

Figma V23 nativehome/nav/cards + adminRevenue,126frames(124prototype+2review)/3pages/7flows mỗi platform,2804links,24SKU/4mỗi category,60SVGlocal;mock fonts/geometry/cardregions/role/5paymentoutcomes3channels/manualphoto repeated/rollback/cancel passed;syntaxpassed. ZIP6files1manifest offline CRC/sourcebytes/XML60verified: `output/figma/thuthanh-figma-clicon-v23-2026-10-23.zip`,SHA256 `abeb07d8b5dd56dfe5559d7753b51e212ff21cf67a3605546cc4d08176200d63`. Chưa chạy canvas thật/pixelcomparison.

Dev read-only:136products,6categories,1admin,136prices/136stocks khớp workbook,0product thiếu ảnh,0paidorders/0bestsellers (đúng dữ liệu thật),/home new12/recommended12cóảnh và320/390/1440khôngoverflow. Không import lại/reset/deploy/mởVNPAY. QA Next3100/Laravel18080/browser đã dừng;server sẵn có localhost3000/8000 giữ nguyên,MySQL đang chạy. Gitmain chưa commit/remote,toàn source untracked/dirtytrue,last_verified_commit=null.

Source thay:backend CatalogService/RevenueService/ProductResource/public+adminProductController/ArchiveProductRequest/routes/configstore/CLIadminaccount-password/tests/OpenAPI;frontendhome/card/picture/bounds/quickviewCSS/store-provider/adminproducts/categories/revenue/reconciliation/dashboard/nav/entry;plugin source/bundle/UI/manifest/mock/package/gallery;guides/decisions/schema/API/checkpoints. Không migration mới.

Lệnh tiếp: chủ dự án mở localhost3000/admin;`cd backend; php artisan admin:account` xem identity local,đăng nhập password hiện có;chỉ tự chạy `admin:password` nếu quên. Restart Laravel/Next nếu giữ terminal cũ,Ctrl+F5. Import ZIPV23 và run builder để cập nhật canvas. Task tiếp F03 còn acceptance tổng thể/ownerFigma review;VNPAY merchant/callback/worker/liveSandbox/deploy/CIremote/coverage>70%/coursereport vẫn chưa nghiệm thu. Giữ .env/APP_KEY/MySQLdata/workbook/storage/uploads/evidence và backups. Không cần cài VSCodeextension.


# Lịch sử trước V23

# Checkpoint mới nhất — 2026-10-23T10:46:22+07:00

D43 / DUI07 hoàn tất trong phạm vi web và plugin local V22. Có Landing page riêng2frame duyệt;2page prototype/61màn mỗi page/7flow hàng ngang mỗi page,frame và nhãn top-level không có section lớn bao.124frame tổng,122prototype,2758links,58SVG local. Gap ngang320px và gap flow520px;node ngoài plugin/ảnh sửa tay cả master được giữ,đặt tránh ghi chú. Master duyệt tĩnh vì không có prototype cross-page;home entry tại mỗi page prototype giữ Trang chủ hoạt động.

Web:gray gaps/panel trắng,logo Constantia/bookmark riêng,0Khám phá ngay,8nhãn hàng/footer/newsletter/address295 Tôn Đức Thắng,Hòa Khánh,Đà Nẵng. Owner đã chọn nguyên2banner Phương Nam;ảnh nguồn/chữ/logo giữ nguyên,thay toàn bộ banner tự dựng tạm. Banner manga giữ. Checkoutpickup/policies có địa chỉ mới,không sửa snapshot đơn.

BackendNewsletterController/SubscribeNewsletterRequest/NewsletterSubscription/newmigration:publicPOST newsletter/subscriptions,normalize emailRFC/consent/CSRF/throttle10/min/unique-idempotent/generic202. Không expose email/id/membership,không tạo user/order và chưa gửi campaign. Bảng riêng thêm sau --pretend và --path;không sửa bảng nghiệp vụ.7targetedtests37assertions trước khi UI;full92tests1417assertions0skip/fail/error,CLI20.52s/JUnit20.140679s. TargetedPint6files passed. OpenAPI40paths/API/schema/PRD synced.

Final Nextbuild/typecheck/6APIclienttests passed;productiontargets API/CSRF/storage localhost8000,không QA18080. Chromium320/390/1440home/catalog nooverflow/logofit/graypanels/gaps>=16px/0CTA/8brandsloaded/address exact;newsletter thiếu consent không submit,202/duplicate202 và readonly testDB1row+consent;guestquickview/authcancel quantity2 giữ.0orderscreated/0uncaughtpageerrors. Original2banner src và newsletterbutton màu xanh checked. Policyaddress browserchecked;finalformatter/addresscopy có build/typecheck. Expectedguest401 và NextdevLCP advisory,không claim clean-console.

Figma build/JSsyntax/mock passed:geometry/containment/nooverlap/flowrows/4SKU mỗi category/firstSKUdemo/money/quantity/SWAP-CLOSE/5payment scenarios3channels/ảnh generated nângHD/ảnh manual master qua2rebuild/owner notes avoidance/rollback3pages/cancel. Original2bannerSHA/aspect ratio asserted.136ảnhgốc/PNG24lossless/3banner/8brands/2logo hashes passed. WorkbookSHA ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb giữ. Dev136products6categories1admin và136giá/tồn khớp trước/sau;QA chỉ dùng thu_thanh_test.

Live ownercanvas/pixeldiff/provider/deploy/remoteCI not_run. Owner cần importmanifestV22/chạybuilder và Present từ2page prototype;Landingpage để duyệt. VNPAYreal deferred503,revenueformulaOPEN,specializedadmin/course/deploy còn riêng;backendexternally_blocked,release not_started. Không gọi toàn dự án done.

Sau ngắt process dừng,đã start existingMySQL và resume isolatedQA có databaseguard,không reset/import hoặc đổi fixtureaccount. HTTP/dev/preview đã dừng;MySQL giữ. Fixture runtime secret được dọn cuối;testDBfixtures/audit giữ. env/APP_KEY/mysql-data/uploads/workbook/oldZIP/userartwork giữ. Gitmain chưa có commit/remote,dirtytrue,last_verified_commitnull. Một command quoting PowerShell lỗi trước execution đã thay bằng helperPython,không có source mutation từ lệnh lỗi.

Gói output/figma/thuthanh-figma-clicon-v22-2026-10-23.zip; 11692697bytes;6files1manifest;CRC/source byte match passed;58SVG XML local passed. SHA256 603d7ab450b6454e44a9d3a75103737ccb59490c47622dd9506e7861d7403bfe. Source/build/gallery ở repo,ảnh nhúng đủ trong code nên gói import offline không cần Python/extension.

Next F03 specializedUI/course hoặc ownercanvasreview. Run CHAY-WEB-VA-LARAVEL.md;import HUONG-DAN V22. Newslettermigration đã áp dụng dev,không cần reimportproducts hoặc càiVSCodeextension. V21backups .runtime/figma-v21-before-v22 vàfrontend-v21-before-v22 giữ.

---

# Checkpoint mới nhất — 2026-10-23T09:07:33+07:00

**DUI06 / D42 done trong phạm vi web + plugin local V21.** Logo NHÀ SÁCH THU THANH, card XEM NHANH/spacing,guest quickview/auth giữ quantity,136ảnhgốcHD,banner manga Phương Nam. Webfull136SKU; Figma24SKU,4sản phẩm mỗi6danh mục,1SKU mua demo,120frames/7flows mỗi platform. Gói hiện hành `output/figma/thuthanh-figma-clicon-v21-2026-10-23.zip`,SHA `af9abd739b9272732d727d0d4f4fe89c929d2e907728936b6c08cd9330513afe`. Source `thu-thanh-figma-clicon/manifest.json` phải import và chạy lại builder để thay canvas cũ. Canvas thật chưa verify,không tự coi mock là livepass. Guide CHAY-WEB-VA-LARAVEL.md/HUONG-DAN V21.

Source thay: frontend components product-card/store-provider/ui/store-shell/home-storefront,app/bookstore.css/layout,lib/product-images/catalog-images,public/catalog-hd/brand/banners;plugin src/data/source/UI/manifest/build/gallery;asset fetch/prepare/verify/package + mocktests. API/schema/Laravel source không đổi. PRD F05 đồng bộ D42. Chi tiết MD-CLEANUP-MANIFEST.json và ASSET-SOURCES.

Thực kiểm:finalbuild/typecheck6APItests;Dashboard2tests20assertions12.88s;asset136originals24losslessPNG;mock120screens2642links/5scenarios3channels/SWAP/CLOSE/legacyHD/repeatedmanualpreservation/rollback;ZIP66files56SVG1manifest CRC/XML/sourcebyte match. Chromium isolatedDB320/390/1440 home/catalog/cart/quick/authqty2/buyNowcheckoutqty3/guest/customer/admin/keyboard/backdrop0orders0pageerrors passed. Dev136/6/1admin and136prices/stocks match;workbookhash nguyên. Full87/1386 evidence cũ D41,không rerun full.

Lỗi đã sửa:header logo lấn search,focus sau đóng dialog,ảnh manual mất ở lần rebuild thứ hai. Harness sửa cartselector/APIabsolute/density-corrected naturalWidth; ban đầu AdminDashboardfilter không tìm test,đã chạy đúngDashboardTest. Sau gián đoạn các process dừng,MySQL connection refused; bật instance hiện có và audit lại passed,không reset/import. Gitmain chưa commit/remote,alluntracked,dirtytrue,last_verified_commit=null.

Hiện HTTP QA/dev/preview dừng,MySQL đã bật lại. Fixture credentials và generated scripts có password đã xóa;testDBorders/audit/operator giữ,devdata/env/APP_KEY/uploads/mysql-data/workbook/userartwork/oldZIPs giữ. Lệnh tiếp theo cho owner:MySQL start → backend `php artisan serve --host=localhost --port=8000`;frontend `npm run dev`;mở localhost3000,Ctrl+F5. Scheduler/worker chạy riêng khi demo expiry/queue. Không cần cài thêm extension hoặc import lại sản phẩm.

Next **F03** chuyên biệt admin/category/reconciliation/evidence/course checks,không tự mở provider/revenue. VNPAY thật deferred_by_owner,formulaOPEN,backend externally_blocked,release not_started. Không deploy/publish/CIremote/livecanvas acceptance. Không gọi cả dự án hoàn tất.

---

# Checkpoint mới nhất — 2026-10-23T03:43:54+07:00

**FUI01/D41 done scoped web update + guide**, whole project not complete. Current web Clicon in home-storefront.tsx/clicon.css/store-shell/product-card and shared forms/admin. Full136SKU/multiitem unchanged, no prototype data/role simulation/QR sample in app. Admin counts/chart/recent orders read real API `/admin/dashboard`; new DashboardService/controller and DashboardTest. Adminorders q-URL supports recentorder links. Figma artifact remainsV20 unchanged. Guide **CHAY-WEB-VA-LARAVEL.md** answers run/Laravel/MySQL/XAMPP/MongoDB/deploy; no production action. LOCAL-SETUP/frontendREADME/API/OpenAPI/DB/trace updated.

Actual:87backendtests1386assertions25.42s(JUnit24.963982)passed0skip/fail, targetedDashboard2/20passed, Pintpassed. Frontend6APItests/build/typecheckpassed; final build rewrites defaultlocalhost8000, no18080. Browser admin/test customer on isolated MySQL: login/realcounts/chart7/390+1440/editorreadonly/authintent/customer403/cartqty2/feeonlycheckout/serverquote/0orderscreated/0pageerrors passed. Home/catalogdev1920/390/320 responsive inspected; no all-route pixel-parity claim. Recenttableoverflow fixed. Latest copy/ARIA/imagebox/q-link covered finalbuild; qlink not separately clicked in browser. Expected401/intentional403 and Nextdev image notices recorded; no clean-console-allpages claim.

Dev preserved136products6cats1admin,136prices/stocks match before/after. TestDBfixtures137/7 separate, not dev. Preserve env/APP_KEY/mysql-data/uploads/workbook/user artwork. No migrations/reset/import/admincreation on dev. Frontend source backup .runtime/frontend-before-clicon-v21. Tests enforce testDB thu_thanh_test. HTTP8000/3000/3100/test18080 created here stopped, MySQL retained. Run2terminals per guide (plusworker/scheduler for expiry). Gitmain no commits/remote,dirtytrue,last_verified_commit=null.

Next F03 specialized admin categories/reconciliation/evidence and remaining course checks; revenue formula OPEN/VNPAY real deferred/QRadapter absent/Compose lacksNext/stagingrestore notrun. Backend gate externallyblocked/release notstarted. Do not call webbuild/provider mocks full-project completion. Latest owner status question answered that scope and remaining work are separate.

---

# FUI01 in_progress — D41

Web visual update plus read-only real dashboard authorized; acceptance recorded before edits. No schema/data/env/provider mutation. Preserve dev136/6/1admin.

# Checkpoint mới nhất — 2026-10-23T01:51:46+07:00

DUI05/D40 done local. Current **THUTHANH · Clicon V20**, manifest `thu-thanh-figma-clicon/manifest.json` (stableID thu-thanh-clicon-v16); artifact `output/figma/thuthanh-figma-clicon-v20-2026-10-23.zip`.112frames/2516links,56screens/platform,7flows,48SVG.24products4/category/1SKU demo retained. Owner must reimport correct V20 manifest and run build: existing V19 canvas does not self-update. Do not import nested older copy. Full guide in plugin HUONG-DAN.md; review evidence MD-CLEANUP-MANIFEST.json.

Changed source: plugin role login/dashboard fixture counts/chart, payment result selector in plugin UI (5scenarios), clean canvas labels, QR→pending→success/fail/expired/review with quantity/channel-specific receipts and paid list. No unpaid cancel on paid; no retry on unknown. One hero Khám phá ngay; other decorative banners no CTA. Breadcrumb text and logo→home. THUTHANH Georgia Bold Italic with serif fallback/accent. Order duplicated compact product removed. Next common brand/banner/login explanation synchronized; real Laravel auth/payment/report unchanged. Dashboard fixture counts not real revenue.

Tests actual: mock112/2516 all5scenarios/3branches, geometry and V19 replace/manual/rollback/cancel; Next6APItests/build/typecheckpassed. Browser preview mobile admin login/dashboard and paid2desktop inspected0console; actual web390 no overflow, Georgia brand, hero1CTA, logo/home link; guest401 expected. No owner admin browser login/e2e, live canvas, real provider, deploy. Backend NOT changed or rerun this turn; earlier85/1366 D39 evidence retained. ZIP58files(one manifest),hash 26f407ae2df2a5e3f820db27e5d746956c5a73b9a89967ec40f01d95d0c7eea9, source/XML/CRC checked. No commits yet, gitmain dirtytrue/hashnull.

Preserve136products/6cats/1admin,workbook/env/APP_KEY/uploads/mysql-data/prior archives/manual art. No reset/import/migration/newadmin. Backup .runtime/figma-v19-before-v20; nested user copy untouched. Started preview18892/backend8000/Next3000 and browsers stopped after QA; MySQL retained. Next F03 full Clicon/admin interfaces after design review. Payment true deferred/QRadapter absent/revenue formula OPEN/release notstarted; don't call prototype acceptance real-provider completion. Run commands remain MD-CLEANUP-MANIFEST.json + LOCAL-SETUP.

---

# DUI05 in_progress — D40

Acceptance in DECISIONS. Clean prototype/admin login/payment outcomes/THUTHANH; provider deferred. Preserve dev data and prior artifacts.

# Checkpoint mới nhất — 2026-10-23T01:18:06+07:00

DUI04 / D39 done cho gói local và common UI. V19 tại `thu-thanh-figma-clicon/manifest.json`; ZIP `output/figma/thuthanh-figma-clicon-v19-2026-10-23.zip`.96frames/2082links/36SVG,48screens/platform/7flows;4samples/category/firstSKU,8 new payment states only. Full QR demo workflow; actual QR adapter absent and VNPAY deferred. Guide/run/payment audit `MD-CLEANUP-MANIFEST.json`. Next F03; full Clicon/card/routes/admin/reconciliation/report/deploy still pending.

Source changed: plugin/src/data(code QR)/bundle/UI/manifest/guide/previews, mock script; Next store-shell/home/cart/policies/admin product wording/CSS. Fee amount hidden browse/cart/footer; server25000/0 unchanged. No Laravel business/source changes. Footer and QR panels/section rebuild verified locally; canvas font/Present pending owner import. Do not claim absolute no overlapping layers on unseen canvas.

Actual checks: backend85/1366passed18.17s(JUnit17.870484); frontend6APItests/build/typecheckpassed; mock96/2082/data+money+geometry+replaceV18+rollback; QR3 independently decoded; browser footer390/1920 no overflow,0brokenimages, preview console0. Web guest401 and4preload warnings recorded. ZIP46files(one manifest)hash 0b5a8d08144b9c312a5169af2565e23b26b0a0a7b9862ef3dc46c24555ebbd90. No full browser authenticated checkout E2E/live provider/real canvas/deploy/remoteCI this turn.

Dev136products6cats1admin; prices/stocks136match, read only. Preserve workbook/env/APP_KEY/.runtime/mysql-data/uploads/manual design sections and priorZIPs. No import/reset/new admin/migrations needed. Gitmain untracked/no commit/no remote; last_verified_commit=null. MySQL retained running; local HTTP/browser preview processes created here all stopped. Commands next: run MySQL start, Laravel serve8000, Next npm run dev3000; scheduler/worker in separate terminals as report. Payment credentials/revenue/hosting only needed when owner resumes those stages. Strict backend externally_blocked/release not_started unchanged.

---

# DUI04 in_progress — D39

Footer/checkout-only fee/QR sample prototype and audit authorized. Preserve dev136 products and admin, payment deferred. Acceptance recorded in DECISIONS before source edits.

# Checkpoint mới nhất — 2026-10-23T00:32:51+07:00

DUI03/D38 done cho **Figma compact và audit code**, chưa phải full project/release. Current `thu-thanh-figma-clicon/manifest.json`: V18 Demo gọn,ID ổn định thu-thanh-clicon-v16. ZIP `output/figma/thuthanh-figma-clicon-v18-2026-10-23.zip`.24 mẫu/4 danh mục,only first TT-SGK-002 purchasing demo;80frames1260links,two platforms7flows each. V17 backup .runtime/figma-v17-before-v18,older ZIP retained.

Read MD-CLEANUP-MANIFEST.json: app136SKU/multi-item/full catalog unaffected. Backend85tests1366assertions21,59s passed incl new two arbitrary SKU checkout test; frontend6clienttests/build/typecheck passed;figma mock/preservation/rollback/preview passed. Live Figma approval,full application browser e2e/deploy/real provider/coverage not_run.

**Next:** owner import V18/Present review; then F03 remaining/admin and applying approved Clicon to all Next routes while retaining full API-driven functionality. Figma limit is not app limit. Need specialized admin category/recon/evidence, revenue decision/payment later, deploy frontend absent Compose. Local web commands in CODE-READINESS and LOCAL-SETUP. Dev136 products6cats **1admin already exists**, prices/stocks136match; do not reimport/reset or create another admin.

MySQL was off, started existing safely and retained running;previewHTTP18890/browser closed. Preserve mysql-data,env/APP_KEY/uploads/workbook. Gitmain untracked/no commits or remote,last_verified_commit=null,dirtytrue. Money/auth/inventory implementation unchanged this turn; test added at backend/tests/Feature/CheckoutTest.php. Backend gate externally_blocked/release not_started.

---

# Checkpoint mới nhất — 2026-10-22T21:50:38+07:00

DUI02/D37 hoàn tất **gói plugin để import duyệt**, không phải live Figma acceptance. Bộ hiện hành `thu-thanh-figma-clicon/manifest.json`, tên thuthanh · Clicon V17 · Bảy luồng, ID ổn định thu-thanh-clicon-v16. ZIP `output/figma/thuthanh-figma-clicon-v17-2026-10-22.zip`. Source mới trong src/plugin.js, dữ liệu136 ảnh/giá/tồn src/data.json; build.py tạo code.js. Dòng nhỏ header exact “Chào mừng đến với nhà sách Thu Thanh”, logo thuthanh. Hai page,7 section/flow maps mỗi page; đã bỏ layout V16 trong edition hiện hành.

Checks: syntax/manifest/UI; mock640 frames17302 links,136 source data/hashes và pagination/filter, money/quantity,selected SKU/overlay/close, manual preservation/rebuild/platform-failure/cancel passed.20 SVG và bundle consistency passed; browser desktop/mobile preview inspected/console0errors. Lệnh tái kiểm: python thu-thanh-figma-clicon/build.py; node --check thu-thanh-figma-clicon/code.js; node scripts/test-figma-clicon.cjs; python scripts/check_pack.py. Source workbook hash ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb.

**Tiếp theo:** chủ dự án import manifest trên Figma Desktop, bấm Dựng mới, chọn flow/nền tảng và Present để kiểm font/scroll/overlay. Chưa tạo/edit cloud file; reference URL chỉ làm mẫu. Sau owner review mới áp dụng layout V17 cho frontend. F02/F03 thực còn dang dở, không suy640 khung mock là chức năng web đã xong. Payment real deferred, revenue OPEN, backend externally_blocked/release not_started.

Giữ .runtime/mysql-data, env/APP_KEY/uploads/workbook và backup .runtime/figma-v16-before-v17/ZIP V16; không reset dữ liệu. Runtime browser/HTTP preview phiên này đã đóng, không đụng MySQL. Chưa commit/remote, git_dirty=true,last_verified_commit=null. Nút mua tương tác dùng kịch bản Toán1 quantity1–2 và giỏ nhiều đầu sách; không giả form/giỏ API động136 món trong plugin.

---

# Checkpoint mới nhất — 2026-10-22T18:34:35+07:00

DUI01/D36: đã bàn giao plugin `thu-thanh-figma-clicon/manifest.json` để chủ dự án import duyệt theo Clicon. Logo `thuthanh`, nhãn tiếng Việt; ảnh/giá/tồn48 mẫu từ Excel. Bộ V15.1 và trang cũ giữ nguyên; API/database/Next.js chưa thay theo thiết kế mới. ZIP `output/figma/thuthanh-figma-clicon-v16-2026-10-22.zip`, hướng dẫn trong folder.

Kiểm tra thật: JS syntax,manifest/UI và mock172 screens1653 links,48 source data/image hashes,rebuild/manual images/cancel passed; browser preview SVG/HTML inspected. Chưa chạy plugin trong Figma thật, chưa owner visual approval; không tuyên bố pixel-perfect. Xem MD-CLEANUP-MANIFEST.json. Lệnh tái kiểm `node scripts/test-figma-clicon.cjs`.

Tiếp theo: chủ dự án import development plugin trên Figma Desktop và duyệt hai trang Clicon; sau khi duyệt mới đổi phong cách frontend. F02/F03 còn dang dở như checkpoint trước. Shipping25000/pickup0,no returns/refunds,VNPAY deferred; backend externally_blocked/release not_started. Chưa commit/remote, dirtytrue,last_verified_commit=null. Giữ workbook,mysql-data,env/APP_KEY/uploads và plugin/canvas cũ. HTTP preview/browser phiên này đóng sau kiểm tra.

---

# Handoff hiện tại

Checkpoint: 2026-10-22T01:19:32+07:00. Repo D:/ThuThanh-WebDesign, main, chưa commit/remote; source mới untracked, git_dirty=true, last_verified_commit=null. Root docs canonical, build pack gốc giữ tham khảo.

## Quyết định và scope
D34: chủ dự án xác nhận yêu cầu giảng viên Next.js frontend + Laravel backend và yêu cầu bắt đầu UI, cho phép scoped frontend dựa B00–B06 local verified khi B10 strict chưa đạt. D33 stack conflict resolved theo thông tin này; các yêu cầu môn khác chưa tự miễn. D35: chủ dự án chọn plugin local vì chưa có cloud link; workbook được cập nhật có đủ136 giá/tồn/ảnh. Không hỏi cloud link lại hoặc chuyển Blade/Livewire.

B00–B06 done, B07–B10 externally_blocked. F00/F01 done phạm vi source-plugin local; F02/F03 in_progress, release not_started. Backend gate chưa passed, không fake Sandbox/QR/paid hoặc doanh thu. Delivery25000/pickup0; không đổi trả/hoàn tiền. VNPAY deferred; revenue formula OPEN.

## Đã có và dữ liệu
- frontend/: Next16.4.2/React19.3.1/TypeScript/Tailwind4.3.3, package-lock, App Router. Header/footer/editorial palette theo plugin V15.1, reuse Phosphor/license. /, /products, /login,/register, /cart,/checkout,/orders/detail,/admin/products/inventory/orders,/policies.
- Same-origin API/CSRF/storage proxy tới fixed Laravel origin; session cookie/CSRF, role gate, no bearer/private data in localStorage. Server quote/order totals, idempotency và countdown từ server.
- Backend CatalogImportService/CLI +3 tests, nullable system actor cho trusted CLI opening ledger; Python audit/stage, không đổi schema hoặc env secret.
- Workbook sha ae2ca77c6543732131739c830b5595c207c28f5398a9eb9a8131a30ee5fb0deb:136 SKU/ảnh đã nhập dev DB thu_thanh bằng O/P,6 category gốc;18 nhóm con giữ trong mô tả/audit. Import rerun skip existing, không reset giá/tồn. All136 prices/stocks/image hashes compared API passed. Chưa có real admin do AI tạo.

## Kiểm chứng
PHP8.4.25/Laravel12.70.0/MySQL8.4.13 UTC loopback3307. Full backend84 tests1353 assertions21.21s passed; cookie11/ops5 passed. Frontend6 API-client tests, build/typecheck/npm audit passed; PHP syntax158, Pint, Composer validate/platform passed. Browser thực/MySQL test: auth/intent/doubleclick/cart/quote/delivery25000/pickup0/order/cancel/reorder/role403, admin create/update/hide/upload/stock/order; responsive1440/768/390/360 và keyboard passed. Screenshots docs/qa; details VERIFICATION.md.

Không có canvas URL/export nên chưa pixel-diff; source-based visual inspection không phải pixel-perfect. Coverage70%, GitHub remote CI, Docker image/staging/restore, real payment/provider, user testing/release chưa chạy. Fixture/browser credentials chỉ test/ignored runtime, không user secret; fixture mới storage riêng .runtime/frontend-browser-storage, HTTP image200 verified. Test DB có thể còn dữ liệu QA, không phải dữ liệu cửa hàng; lần PHPUnit RefreshDatabase chỉ dùng guarded thu_thanh_test.

## Lệnh chạy local / việc tiếp
1. Từ gốc: powershell -ExecutionPolicy Bypass -File scripts/mysql-local.ps1 -Action start. Không install/reset data lại.
2. Terminal backend: php artisan serve --host=localhost --port=8000. Terminal frontend: npm run dev (cwd frontend). Mở localhost:3000. Dependencies đã cài; máy mới dùng composer install/npm ci theo lock.
3. Trong backend: php artisan admin:create để chủ dự án tự nhập secret; khi dùng expiry/xóa ảnh chạy thêm schedule:work và queue:work database --tries=5 --timeout=60.
4. F03 còn admin category/reconciliation/evidence nâng cao; F02 payment/result/QR để sau theo D31; report cần chốt công thức. Không tạo COD/manual-paid để vượt gate. User review banner/assets/ảnh gốc nét hơn trước release; không bắt nộp link Figma khi đã chọn plugin.
5. Các thiếu sót môn còn token abilities/Scramble/Telescope/coverage/monitoring/caching/GitHub workflow hồ sơ — xem COURSE-RUBRIC-AUDIT. Đừng suy frontend build là đủ điểm môn.

Giữ .runtime/mysql-data, mysql-admin.cnf, backend/.env/.env.testing, APP_KEY, public/private storage và workbook gốc. Không reset/clean/xóa dữ liệu để sửa lỗi, không migrate:fresh development. Không commit test/browser credentials, env hay screenshots chứa thông tin thật.

Operational state cuối phiên 2026-10-22T01:21:37+07:00: preview/QA HTTP servers8000/3000/3100/18080 và3 browser sessions của phiên đã đóng; MySQL giữ chạy. Dùng lệnh backend serve/frontend dev ở trên để mở web. Browser fixture isolation được xác minh HTTP200 từ storage riêng, stop graceful exit0; credentials runtime không còn.

D38 packaging note: nested extracted V17 copy under plugin folder is retained, excluded from V18 ZIP. Active manifest is root thu-thanh-figma-clicon/manifest.json; clean ZIP has30 files/one manifest only.


D41 dependency correction: FUI01 depends_on DUI05/F01 (đãdone), không phụ thuộc full F02 payment vẫnin_progress. check_pack phát hiện mapping ban đầu quá rộng và đã sửa theo scope D41; F02/F03/provider/release không được gọi done.
