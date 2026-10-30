import { test, expect, type Page, type APIRequestContext } from "@playwright/test";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";
const root = resolve(dirname(fileURLToPath(import.meta.url)), "../..");
const base = "http://localhost:3100", backend = "http://localhost:18082";
const password = "E2E-Local-Password-2026!";
const browserIssues = new Map<Page, string[]>();
function observe(page: Page) {
  const issues: string[] = []; browserIssues.set(page, issues);
  page.on("pageerror", error => issues.push(error.message));
  page.on("console", message => {
    if (message.type() === "warning" && message.text().includes("Image with src")) issues.push(message.text());
  });
}

test.beforeEach(async ({ page }) => {
  browserIssues.clear(); observe(page);
  await page.route("https://www.google.com/maps/**", r => r.fulfill({ body: "", contentType: "text/html" }));
  await page.route("https://e2e-provider.invalid/**", async r => {
    const u = new URL(r.request().url());
    const response = await page.request.get(backend + (u.pathname === "/pay" ? "/__e2e/provider" : u.pathname) + u.search, { maxRedirects: 0 });
    await r.fulfill({ response });
  });
  await page.route("**/*.vnpayment.vn/**", r => r.abort("blockedbyclient"));
});
test.afterEach(() => { for (const issues of browserIssues.values()) expect(issues).toEqual([]); });
async function register(page: Page) {
  await page.goto("/register"); const d = page.getByRole("dialog");
  await d.getByLabel("Họ và tên", { exact: true }).fill("Khách E2E");
  await d.getByLabel("Email hoặc số điện thoại", { exact: true }).fill(`customer-${Date.now()}-${Math.random().toString(16).slice(2)}@example.test`);
  await d.getByLabel("Mật khẩu", { exact: true }).fill(password);
  await d.getByLabel("Nhập lại mật khẩu", { exact: true }).fill(password);
  await d.getByRole("button", { name: "Đăng ký", exact: true }).click();
  await expect(d).not.toBeVisible();
}
async function admin(page: Page) {
  await page.goto("/login"); const d = page.getByRole("dialog");
  await d.getByLabel("Email hoặc số điện thoại", { exact: true }).fill("admin-e2e@example.test");
  await d.getByLabel("Mật khẩu", { exact: true }).fill(password);
  await d.getByRole("button", { name: "Đăng nhập", exact: true }).click();
  await expect(page).toHaveURL(/\/admin$/);
}
async function mutation(page: Page, path: string, method: "POST" | "PATCH", data: unknown) {
  await page.request.get(base + "/sanctum/csrf-cookie");
  const c = (await page.context().cookies(base)).find(c => c.name === "XSRF-TOKEN");
  return page.request.fetch(base + "/api/v1" + path, { method, data,
    headers: { Origin: base, Referer: base + "/", "X-XSRF-TOKEN": decodeURIComponent(c!.value), Accept: "application/json" } });
}
async function checkout(page: Page, delivery = false) {
  await page.goto("/products?q=Sách%20kiểm%20thử%20E2E%201");
  await page.getByRole("button", { name: "Xem nhanh Sách kiểm thử E2E 1", exact: true }).click();
  await page.getByRole("dialog").getByRole("button", { name: "Mua ngay", exact: true }).click();
  if (!delivery) await page.getByRole("radio", { name: /Nhận tại cửa hàng/ }).check();
  await page.getByLabel("Số điện thoại", { exact: true }).fill("0900000001");
  if (delivery) {
    await page.getByLabel("Tỉnh / thành phố", { exact: true }).fill("Đà Nẵng");
    await page.getByLabel("Phường / xã, địa phương", { exact: true }).fill("Hòa Khánh");
    await page.getByLabel("Địa chỉ cụ thể", { exact: true }).fill("295 Tôn Đức Thắng");
  }
  await page.getByRole("button", { name: "Kiểm tra và lấy báo giá", exact: true }).click();
  await page.getByRole("button", { name: "Xác nhận đặt hàng", exact: true }).click();
  await expect(page.getByRole("button", { name: "Thanh toán qua VNPAY", exact: true })).toBeVisible();
  return page.url().split("/").pop()!;
}
async function finish(page: Page, outcome: "success" | "cancel" | "unknown") {
  await page.getByRole("button", { name: "Thanh toán qua VNPAY", exact: true }).click();
  await expect(page.getByRole("heading", { name: "Cổng kiểm thử tự động" })).toBeVisible();
  await page.getByRole("button", { name: { success: "Xác nhận thành công", cancel: "Hủy thanh toán", unknown: "Chưa xác định" }[outcome], exact: true }).click();
  await expect(page.getByRole("heading", { name: "Kết quả thanh toán VNPAY", exact: true })).toBeAttached();
  await expect(page.locator("#payment-outcome-title")).toBeVisible();
}
async function evidence(request: APIRequestContext, code: string) {
  return (await request.get(backend + "/__e2e/evidence?order_code=" + code)).json();
}

test("responsive ranking, keyboard selection and aligned quantity controls", async ({ page }) => {
  for (const width of [1440, 768, 375]) {
    await page.setViewportSize({ width, height: 950 }); await page.goto("/");
    const ranking = page.getByRole("list", { name: "Xếp hạng bán chạy" });
    await expect(ranking.getByRole("button")).toHaveCount(7);
    await ranking.getByRole("button").nth(1).click();
    await expect(ranking.getByRole("button").nth(1)).toHaveAttribute("aria-pressed", "true");
    await ranking.getByRole("button").nth(1).press("ArrowDown");
    await expect(ranking.getByRole("button").nth(2)).toHaveAttribute("aria-pressed", "true");
    await expect(page.getByText(/Đã bán trong tuần/)).toHaveCount(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    if (width === 1440) await page.locator(".bestseller-showcase").screenshot({ path: resolve(root, "output/e2e/bestseller-desktop.png") });
    await page.locator(".bestseller-cover").click();
    const boxes = await page.getByRole("dialog").locator(".quick-quantity button, .quick-quantity input").evaluateAll(ns => ns.map(n => {
      const b = n.getBoundingClientRect(); return { y: b.y, h: b.height };
    }));
    expect(boxes).toHaveLength(3);
    expect(Math.max(...boxes.map(b => b.y)) - Math.min(...boxes.map(b => b.y))).toBeLessThanOrEqual(1);
    expect(boxes.every(b => b.h === 48)).toBeTruthy();
    if (width === 1440) await page.getByRole("dialog").screenshot({ path: resolve(root, "output/e2e/quantity-desktop.png") });
    await page.getByRole("dialog").getByRole("button", { name: "Đóng", exact: true }).click();
  }
  await page.screenshot({ path: resolve(root, "output/e2e/mobile-home.png"), fullPage: true });
});

test("search, quantity boundaries, cart and profile persist through real API", async ({ page }) => {
  await register(page); await page.goto("/");
  const search = page.getByRole("combobox", { name: "Tìm sản phẩm", exact: true });
  await search.fill("Sách kiểm thử E2E 1"); await page.getByRole("option").first().waitFor();
  await search.press("ArrowDown"); await search.press("Enter");
  const d = page.getByRole("dialog"); await expect(d).toBeVisible();
  await expect(d.getByRole("button", { name: "Giảm số lượng" })).toBeDisabled();
  await d.getByRole("spinbutton", { name: "Số lượng" }).fill("0");
  await expect(d.getByRole("button", { name: "Thêm vào giỏ", exact: true })).toBeDisabled();
  await d.getByRole("spinbutton", { name: "Số lượng" }).fill("2");
  await d.getByRole("button", { name: "Thêm vào giỏ", exact: true }).click(); await expect(d).not.toBeVisible();
  expect((await (await page.request.get(base + "/api/v1/cart")).json()).data.items[0].quantity).toBe(2);
  await page.goto("/account"); await page.getByLabel("Họ và tên", { exact: true }).fill("Khách đã sửa E2E");
  await page.getByRole("button", { name: /Lưu/ }).click();
  await expect.poll(async () => (await (await page.request.get(base + "/api/v1/auth/me")).json()).data.name).toBe("Khách đã sửa E2E");
  await page.reload(); await expect(page.getByLabel("Họ và tên", { exact: true })).toHaveValue("Khách đã sửa E2E");
});

test("delivery fee, verified provider query, replay and tamper protection", async ({ page, request }) => {
  await register(page); const code = await checkout(page, true);
  await expect(page.getByRole("button", { name: "Cập nhật trạng thái", exact: true })).toHaveCount(0);
  await expect(page.getByRole("button", { name: "Đặt lại sản phẩm", exact: true })).toHaveCount(0);
  const order = (await (await page.request.get(base + "/api/v1/orders/" + code)).json()).data;
  expect(order.totals.shipping_fee).toBe(25000); expect(order.totals.payable_total).toBe(110000);
  await finish(page, "success");
  await expect(page.getByRole("heading", { name: "Thanh toán thành công", exact: true })).toBeVisible();
  await expect(page.locator(".payment-outcome--success .payment-outcome-icon")).toBeVisible();
  await expect(page.locator(".payment-overview")).toContainText("110.000");
  await expect(page.locator(".payment-items")).toHaveCount(0);
  await page.locator(".payment-result-page").screenshot({ path: resolve(root, "output/e2e/payment-success-desktop.png") });
  await page.setViewportSize({ width: 375, height: 900 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  await page.screenshot({ path: resolve(root, "output/e2e/payment-success-mobile.png"), fullPage: true });
  const before = await evidence(request, code); expect(before.payment_status).toBe("paid"); expect(before.receipts).toBe(1);
  const signed = new URL(page.url()).searchParams;
  expect((await (await request.get(backend + "/api/v1/payments/vnpay/ipn?" + signed)).json()).RspCode).toBe("02");
  signed.set("vnp_Amount", "1");
  expect((await (await request.get(backend + "/api/v1/payments/vnpay/ipn?" + signed)).json()).RspCode).toBe("97");
  await page.reload(); await expect(page.getByRole("heading", { name: "Thanh toán thành công", exact: true })).toBeVisible();
  expect(await evidence(request, code)).toEqual(before);
});

test("provider cancellation stays unpaid and customer cannot use admin endpoints", async ({ page, request }) => {
  await register(page); const code = await checkout(page); await finish(page, "cancel");
  await expect(page.getByRole("heading", { name: "Thanh toán không thành công", exact: true })).toBeVisible();
  await expect(page.locator(".payment-outcome--failure .payment-outcome-icon")).toBeVisible();
  await expect(page.locator(".payment-items")).toHaveCount(0);
  await page.locator(".payment-result-page").screenshot({ path: resolve(root, "output/e2e/payment-failure-desktop.png") });
  expect((await evidence(request, code)).receipts).toBe(0); expect((await evidence(request, code)).payment_status).toBe("unpaid");
  expect((await page.request.get(base + "/api/v1/admin/products")).status()).toBe(403);
  expect((await mutation(page, "/admin/products", "POST", { name: "Unauthorized" })).status()).toBe(403);
  await page.goto("/admin"); await expect(page.getByRole("heading", { name: "Quản lý cửa hàng", exact: true })).toHaveCount(0);
});

test("uncertain return stays pending; backend IPN updates customer automatically", async ({ page, request }) => {
  await register(page); const code = await checkout(page); await finish(page, "unknown");
  await expect(page.locator(".payment-outcome--pending .payment-outcome-icon")).toBeVisible();
  const before = await evidence(request, code); expect(before.receipts).toBe(0); expect(before.payment_status).toBe("pending");
  const ref = new URL(page.url()).searchParams.get("vnp_TxnRef")!;
  const confirmation = await request.get(backend + "/__e2e/finish?reference=" + ref + "&outcome=success", { maxRedirects: 0 });
  const callback = new URL(confirmation.headers().location).searchParams;
  expect((await (await request.get(backend + "/api/v1/payments/vnpay/ipn?" + callback)).json()).RspCode).toBe("00");
  await expect(page.getByRole("heading", { name: "Thanh toán thành công", exact: true })).toBeVisible({ timeout: 20000 });
  expect((await evidence(request, code)).receipts).toBe(1);
});

test("admin creates with image/discount, edits, archives and restores product", async ({ page }) => {
  await admin(page); await page.goto("/admin/products");
  await page.getByRole("button", { name: "+ Thêm sản phẩm", exact: true }).click();
  const d = page.getByRole("dialog"), suffix = Date.now(), name = "Sản phẩm CRUD E2E " + suffix;
  await d.getByLabel("Tên sản phẩm", { exact: true }).fill(name);
  await d.getByLabel("SKU", { exact: true }).fill("E2E-CRUD-" + suffix);
  await d.getByLabel("Danh mục", { exact: true }).selectOption({ label: "Sách kiểm thử E2E" });
  await d.getByLabel("Giá bìa / giá niêm yết", { exact: true }).fill("100000");
  await d.getByLabel("Cách nhập giá giảm", { exact: true }).selectOption("percent");
  await d.getByLabel(/Phần trăm giảm giá/).fill("15"); await d.getByLabel("Tồn ban đầu", { exact: true }).fill("12");
  await d.getByRole("checkbox", { name: "Hiển thị trong Bán chạy nhất", exact: true }).check();
  await d.locator('input[type="file"]').setInputFiles(resolve(root, "frontend/public/catalog-hd/TT-VH-001.webp"));
  await d.getByRole("button", { name: "Lưu sản phẩm", exact: true }).click(); await expect(d.getByText(/Đã lưu/)).toBeVisible();
  await d.getByRole("button", { name: "Đóng", exact: true }).click();
  await page.getByLabel("Tìm sản phẩm quản lý").fill(name);
  await page.locator(".admin-toolbar").getByRole("button", { name: "Tìm", exact: true }).click();
  const row = page.getByRole("row").filter({ hasText: name }); await expect(row).toContainText("85.000");
  await row.getByRole("button", { name: "Chỉnh sửa / ảnh", exact: true }).click();
  await expect(d.getByRole("checkbox", { name: "Hiển thị trong Bán chạy nhất", exact: true })).toBeChecked();
  await d.getByLabel("Tên sản phẩm", { exact: true }).fill(name + " đã sửa");
  await d.getByRole("button", { name: "Lưu sản phẩm", exact: true }).click(); await expect(d.getByText(/Đã lưu/)).toBeVisible();
  await d.getByRole("button", { name: "Đóng", exact: true }).click();
  page.on("dialog", dialog => dialog.accept()); await row.getByRole("button", { name: "Xóa / ngừng bán", exact: true }).click();
  await expect(row).toContainText("Đã ẩn");
  await row.getByRole("button", { name: /Khôi phục|Hiện lại|Mở bán/ }).click(); await expect(row).toContainText("Đang bán");
});

test("admin navigation and revenue charts render without overflow", async ({ page }) => {
  test.setTimeout(120000);
  await admin(page);
  for (const width of [1440, 375]) for (const path of ["/admin", "/admin/categories", "/admin/inventory", "/admin/orders", "/admin/reconciliation", "/admin/revenue"]) {
    await page.setViewportSize({ width, height: 950 });
    await page.goto(path); await expect(page.getByRole("heading", { name: "Quản lý cửa hàng", exact: true })).toBeVisible();
    await expect(page.locator("main")).not.toContainText("Không gian quản trị");
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  }
});

test("newsletter consent, password recovery and invalid reset link", async ({ page }) => {
  await page.goto("/");
  const newsletter = page.getByRole("region", { name: "Đăng ký nhận bản tin", exact: true });
  await newsletter.getByLabel("Email nhận bản tin").fill(`newsletter-${Date.now()}@example.test`);
  await newsletter.getByRole("checkbox").check();
  const saved = page.waitForResponse(r => r.url().includes("/newsletter/subscriptions") && r.request().method() === "POST");
  await newsletter.getByRole("button", { name: "Đăng ký", exact: true }).click();
  expect((await saved).ok()).toBeTruthy(); await expect(newsletter.getByRole("status")).toBeVisible();
  await page.goto("/forgot-password");
  await page.getByLabel("Email đăng ký", { exact: true }).fill(`missing-${Date.now()}@example.test`);
  const sent = page.waitForResponse(r => r.url().includes("/auth/forgot-password") && r.request().method() === "POST");
  await page.getByRole("button", { name: "Gửi liên kết khôi phục", exact: true }).click();
  expect((await sent).ok()).toBeTruthy();
  await page.goto("/reset-password");
  await expect(page.getByText(/Liên kết chưa đầy đủ hoặc không hợp lệ/)).toBeVisible();
});

test("inventory adjustment records ledger; only admin changes order fulfillment", async ({ page, browser }) => {
  test.setTimeout(120000);
  await register(page); const code = await checkout(page); await finish(page, "success");
  expect((await mutation(page, "/admin/orders/" + code + "/fulfillment", "PATCH", { status: "preparing", version: 1 })).status()).toBe(403);
  const manager = await browser.newPage({ baseURL: base });
  observe(manager);
  manager.on("dialog", dialog => dialog.accept());
  try {
    await admin(manager); await manager.goto("/admin/inventory");
    const product = (await (await manager.request.get(base + "/api/v1/products?q=" + encodeURIComponent("Sách kiểm thử E2E 1"))).json()).data[0];
    const stock = manager.getByRole("row").filter({ hasText: "E2E-BOOK-1" });
    await stock.getByRole("button", { name: "Điều chỉnh", exact: true }).click();
    const dialog = manager.getByRole("dialog");
    await dialog.getByLabel("Số lượng tăng / giảm", { exact: true }).fill("3");
    await dialog.getByLabel("Lý do", { exact: true }).fill("Kiểm kê E2E +3");
    await dialog.getByRole("button", { name: "Xác nhận điều chỉnh", exact: true }).click();
    await expect(dialog).not.toBeVisible();
    const adjusted = (await (await manager.request.get(base + "/api/v1/products/" + product.id)).json()).data;
    expect(adjusted.available_quantity).toBe(product.available_quantity + 3);
    await stock.getByRole("button", { name: "Lịch sử", exact: true }).click();
    await expect(dialog.getByText("Kiểm kê E2E +3", { exact: true }).first()).toBeVisible();
    await dialog.getByRole("button", { name: "Đóng", exact: true }).click();
    await manager.goto("/admin/orders");
    await manager.getByLabel("Tìm đơn hàng", { exact: true }).fill(code);
    await manager.locator(".admin-toolbar").getByRole("button", { name: "Tìm", exact: true }).click();
    await manager.getByRole("row").filter({ hasText: code }).getByRole("button", { name: "Chi tiết", exact: true }).click();
    for (const name of ["Bắt đầu chuẩn bị", "Sẵn sàng nhận", "Xác nhận hoàn tất"]) {
      const changed = manager.waitForResponse(r => r.url().includes("/fulfillment") && r.request().method() === "PATCH");
      await dialog.getByRole("button", { name, exact: true }).click();
      expect((await changed).ok()).toBeTruthy();
    }
    await expect.poll(async () => (await (await page.request.get(base + "/api/v1/orders/" + code)).json()).data.fulfillment_status).toBe("fulfilled");
  } finally { await manager.close(); }
});

test("ranked product adds one item to cart without fabricating sales", async ({ page }) => {
  await register(page); await page.goto("/");
  const ranking = page.getByRole("list", { name: "Xếp hạng bán chạy" });
  await ranking.getByRole("button").nth(3).click();
  const selectedName = await page.locator(".bestseller-product-copy h3").innerText();
  await page.getByRole("button", { name: "Thêm vào giỏ hàng", exact: true }).click();
  await expect.poll(async () => {
    const cart = (await (await page.request.get(base + "/api/v1/cart")).json()).data;
    return cart.items.some((item: { product: { name: string }; quantity: number }) => item.product.name === selectedName && item.quantity === 1);
  }).toBeTruthy();
});

test("footer uses ten original image logos and accessible carousel arrows", async ({ page }) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  for (const width of [1440, 768, 375]) {
    await page.setViewportSize({ width, height: 900 }); await page.goto("/");
    const brands = page.getByRole("region", { name: "Thương hiệu", exact: true });
    await expect(brands.locator("img")).toHaveCount(10);
    await brands.scrollIntoViewIfNeeded();
    await expect.poll(() => brands.locator("img").evaluateAll(nodes => nodes.every(n => (n as HTMLImageElement).complete && (n as HTMLImageElement).naturalWidth > 0))).toBeTruthy();
    await expect(brands.locator(".brand-name, small")).toHaveCount(0);
    await brands.getByRole("button", { name: "Thương hiệu tiếp theo", exact: true }).click();
    await expect.poll(() => brands.locator(".brand-track").evaluate(n => n.scrollLeft)).toBeGreaterThan(0);
    await expect(brands.getByRole("button", { name: "Thương hiệu trước", exact: true })).toBeEnabled();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    await brands.screenshot({ path: resolve(root, `output/e2e/brand-logos-${width}.png`) });
  }
});

test("invalid payment return never displays a paid confirmation", async ({ page }) => {
  await page.goto("/payment/vnpay-return?vnp_SecureHash=invalid");
  await expect(page.getByRole("heading", { name: "Không thể xác minh giao dịch", exact: true })).toBeVisible();
  await expect(page.locator(".payment-outcome--failure .payment-outcome-icon")).toBeVisible();
  await expect(page.locator(".payment-outcome--success, .payment-overview, .payment-items")).toHaveCount(0);
});

test("cancel returns items to the saved cart and allows a fresh checkout without replay duplication", async ({ page }) => {
  page.on("dialog", dialog => dialog.accept());
  await register(page); const code = await checkout(page);
  const product = (await (await page.request.get(base + "/api/v1/products?q=" + encodeURIComponent("Sách kiểm thử E2E 1"))).json()).data[0];
  expect((await mutation(page, "/cart/items", "POST", { product_id: product.id, quantity: 2 })).ok()).toBeTruthy();
  await page.getByRole("button", { name: "Hủy đơn chưa thanh toán", exact: true }).click();
  await expect(page).toHaveURL(/\/cart$/);
  await expect(page.getByRole("heading", { name: "Sách kiểm thử E2E 1", exact: true })).toBeVisible();
  const cart = async () => (await (await page.request.get(base + "/api/v1/cart")).json()).data;
  expect((await cart()).items[0].quantity).toBe(3);
  expect((await (await page.request.get(base + "/api/v1/orders/" + code)).json()).data.lifecycle).toBe("cancelled");
  expect((await mutation(page, "/orders/" + code + "/cancel", "POST", {})).ok()).toBeTruthy();
  expect((await cart()).items[0].quantity).toBe(3);
  await page.reload(); await expect(page.getByRole("heading", { name: "Sách kiểm thử E2E 1", exact: true })).toBeVisible();
  await page.getByRole("link", { name: /Tiến hành đặt hàng/ }).click();
  await page.getByRole("radio", { name: /Nhận tại cửa hàng/ }).check();
  await page.getByLabel("Số điện thoại", { exact: true }).fill("0900000001");
  await page.getByRole("button", { name: "Kiểm tra và lấy báo giá", exact: true }).click();
  await page.getByRole("button", { name: "Xác nhận đặt hàng", exact: true }).click();
  await expect(page.getByRole("button", { name: "Thanh toán qua VNPAY", exact: true })).toBeVisible();
  const newCode = page.url().split("/").pop()!;
  expect(newCode).not.toBe(code); expect((await cart()).items).toHaveLength(0);
  expect((await mutation(page, "/orders/" + code + "/cancel", "POST", {})).ok()).toBeTruthy();
  expect((await cart()).items).toHaveLength(0);
});
