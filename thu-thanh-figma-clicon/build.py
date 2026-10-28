"""Bundle the offline Figma plugin with Python's standard library."""
from pathlib import Path
import json
root = Path(__file__).resolve().parent
data = json.loads((root/'src/data.json').read_text(encoding='utf-8'))
source = (root/'src/plugin.js').read_text(encoding='utf-8')
assert source.count('/*__DATA__*/') == 1
(root/'code.js').write_text(source.replace('/*__DATA__*/', 'const DATA = '+json.dumps(data,ensure_ascii=False,separators=(',',':'))+';'), encoding='utf-8')
print('Bundled V26:', len(data['products']), 'products; no network dependencies')
