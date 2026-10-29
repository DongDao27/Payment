import { defineConfig, devices } from "@playwright/test";
import { dirname, resolve, delimiter } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const isolated = resolve(root, ".runtime/e2e-frontend");
export default defineConfig({
  testDir: "./e2e",
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 60000,
  expect: { timeout: 15000 },
  outputDir: resolve(root, "output/e2e/results"),
  reporter: [["list"], ["html", { outputFolder: resolve(root, "output/e2e/report"), open: "never" }]],
  use: { baseURL: "http://localhost:3100", screenshot: "only-on-failure", trace: "retain-on-failure" },
  projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
  webServer: [
    {
      name: "Isolated Laravel",
      command: "php -S localhost:18082 -t backend/public backend/tests/Support/e2e-router.php 2> .runtime/e2e-server.log",
      cwd: root,
      env: { APP_ENV: "testing", E2E_TESTING: "1", PHP_INI_SCAN_DIR: delimiter + resolve(root, "backend/docker/php") },
      url: "http://localhost:18082/up", reuseExistingServer: false, timeout: 60000,
    },
    {
      name: "Isolated Next",
      command: `node "${resolve(root, "frontend/node_modules/next/dist/bin/next")}" dev --hostname localhost --port 3100`,
      cwd: isolated,
      env: { LARAVEL_INTERNAL_URL: "http://localhost:18082" },
      url: "http://localhost:3100", reuseExistingServer: false, timeout: 90000,
    },
  ],
});
