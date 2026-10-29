"""Copy frontend for E2E without touching the owner's .next or next-env.d.ts."""
from pathlib import Path
import shutil

root = Path(__file__).resolve().parents[1]
source = root / 'frontend'
target = root / '.runtime/e2e-frontend'
target.mkdir(parents=True, exist_ok=True)
for name in ['src', 'public', 'e2e', 'tests']:
    shutil.copytree(source / name, target / name, dirs_exist_ok=True)
for name in ['package.json', 'package-lock.json', 'tsconfig.json', 'postcss.config.mjs', 'playwright.config.ts']:
    shutil.copy2(source / name, target / name)
config = (source / 'next.config.ts').read_text(encoding='utf-8')
config = config.replace('output: "standalone",', 'output: "standalone",\n  turbopack: { root: '+repr(root.as_posix())+' },')
(target / 'next.config.ts').write_text(config, encoding='utf-8')
print('Isolated E2E frontend prepared; owner build/cache unchanged.')
