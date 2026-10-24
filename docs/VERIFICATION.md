# Kiểm chứng hiện hành — 24/10/2026

Kiểm trước commit D53 trên Windows, PHP8.4.25/Laravel12, MySQL8.4 và Next16.4.2. Dữ liệu test tách trong `thu_thanh_test`; không reset/import database chính hoặc demo.

| Kiểm tra | Kết quả thực tế |
|---|---|
| `php artisan test --log-junit=storage/logs/phpunit.xml` | **118passed,1.675assertions**, CLI38,42giây, không failure/error/skip |
| `php vendor/bin/pint --test` | Passed |
| `composer validate --strict`, `composer check-platform-reqs` | Passed |
| `php scripts/auth-cookie-smoke.php` | **11checks passed**, HTTP/session/CSRF thật trên DBtest |
| `php scripts/ops-smoke.php` | **5checks passed**, worker/expire/reconcile/scheduler và job cleanup trên DBtest |
| `npm run test:api` | **7tests passed** |
| `npm run build`, `npm run typecheck` | Passed; bao gồm forgot/reset password và các trang admin |
| `php scripts/validate-deploy.php` | YAML parsed; không phải Docker build hoặc Nginx runtime |

JUnit mới nhất ở `backend/storage/logs/phpunit.xml` bị Gitignore. Browser1440/390/320 và recovery/admin/chart đã kiểm trong D51; không chạy lại browser để gọi là bằng chứng mới khi chỉ dọn tài liệu/công cụ. Snapshot QA lịch sử được lưu trong ZIP ngoài repo theo [manifest cleanup](CLEANUP-2026-10-24.json).

Bộ test source trong `backend/tests`/`frontend/tests` và GitHub Actions được giữ nguyên. `python scripts/check_pack.py` kiểm đặc tả/API/checkpoint/link và sự hiện diện test/CI, không thay chạy ứng dụng. Tham số CPU/RAM, Docker/Redis runtime, SMTP thật, provider Sandbox, coverage>70%, fullWCAG, người dùng đại diện, staging/restore và tải10k **chưa nghiệm thu**. CI của commit mới chỉ có kết quả sau khi được push và chạy trên GitHub; không lấy CI commit cũ làm kết quả commit mới.
