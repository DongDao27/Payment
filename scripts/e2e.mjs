import { spawnSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { dirname, join, resolve, delimiter } from 'node:path';
import { fileURLToPath } from 'node:url';
import { existsSync, symlinkSync } from 'node:fs';
import net from 'node:net';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const frontend = join(root, 'frontend');
const env = { ...process.env, APP_ENV: 'testing', E2E_TESTING: '1', PHP_INI_SCAN_DIR: delimiter + join(root, 'backend/docker/php') };
function run(command, args, cwd = root, capture = false) {
  const result = spawnSync(command, args, { cwd, env, stdio: capture ? 'pipe' : 'inherit', encoding: 'utf8' });
  if (capture) { process.stdout.write(result.stdout ?? ''); process.stderr.write(result.stderr ?? ''); }
  if (result.error) throw result.error;
  if (result.status !== 0) process.exit(result.status ?? 1);
  return result;
}
async function available(port) {
  await new Promise((accept, reject) => {
    const server = net.createServer();
    server.once('error', () => reject(new Error(`Port ${port} occupied; stop only your existing QA server first.`)));
    server.listen(port, 'localhost', () => server.close(accept));
  });
}
await available(18082);
await available(3100);
if (!existsSync(join(root, 'backend/.env.testing'))) throw new Error('Prepare private backend/.env.testing for thu_thanh_test first.');
if (process.platform === 'win32') run('powershell', ['-ExecutionPolicy', 'Bypass', '-File', 'scripts/mysql-local.ps1', '-Action', 'start']);
run('python', ['scripts/prepare-e2e.py']);
const modules = join(root, '.runtime/e2e-frontend/node_modules');
if (!existsSync(modules)) symlinkSync(join(frontend, 'node_modules'), modules, process.platform === 'win32' ? 'junction' : 'dir');
const seed = run('php', ['backend/tests/Support/e2e-seed.php'], root, true);
if (!seed.stdout?.includes('E2E fixtures ready:')) throw new Error('E2E fixture setup did not finish; servers will not start.');
const require = createRequire(join(frontend, 'package.json'));
run(process.execPath, [require.resolve('@playwright/test/cli'), 'test', ...process.argv.slice(2)], frontend);
