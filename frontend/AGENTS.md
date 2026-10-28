## Giao diện Thu Thanh — D57

Chủ dự án yêu cầu dùng [UI/UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) khi sửa UI. Skill được cài ở `$CODEX_HOME/skills/ui-ux-pro-max` (mặc định `~/.codex/skills/ui-ux-pro-max`); đọc `SKILL.md`, truy vấn đúng Next.js và ưu tiên yêu cầu đã chốt trong DECISIONS. Máy mới cần cài skill riêng, không copy bộ dữ liệu skill vào source ứng dụng.

Giữ xanh–trắng, logo Thu Thanh và ba feed hiện có. D58 cho phép bổ sung banner Fahasa không logo, giữ ba ảnh Phương Nam đã duyệt; cấu hình ở `src/lib/store-banners.json`, nguồn/hash ở ASSET-SOURCES. Footer chỉ một khối liên hệ/map nhỏ dưới logo; nút lên đầu trang phải tránh bottomnav và tôn trọng reduced motion. Font Nunito Sans variable tự host tại `src/app/fonts`, có OFL và tiếng Việt; token dùng chung ở `src/app/styles/storefront.css`. Sửa selector hiện có, tránh thêm lớp CSS override mới. Kiểm tra card/xem nhanh, form, admin, keyboard, reduced motion và các rộng 375/768/1024/1440; không thay nghiệp vụ bằng gợi ý từ skill.

<!-- BEGIN:nextjs-agent-rules -->

## This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` (resolved from this file's directory; in monorepos the `next` package may not be visible from the repo root) before writing any code. Heed deprecation notices.

This block is written and re-added by `next dev` — verify at `node_modules/next/dist/server/lib/generate-agent-files.js`. Removing it from a diff only re-creates the uncommitted change; committing it with your work keeps the tree clean.

<!-- END:nextjs-agent-rules -->
