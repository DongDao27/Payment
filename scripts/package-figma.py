"""Package only the current offline plugin, excluding nested legacy copies."""
from pathlib import Path
import hashlib
import json
import re
import zipfile
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
source = ROOT / 'thu-thanh-figma-clicon'
data = json.loads((source / 'src/data.json').read_text(encoding='utf-8'))
expected = (source / 'src/plugin.js').read_text(encoding='utf-8').replace('/*__DATA__*/', 'const DATA = '+json.dumps(data, ensure_ascii=False, separators=(',', ':'))+';')
assert (source / 'code.js').read_text(encoding='utf-8') == expected
version=int(re.search(r'V(\d+)',json.loads((source/'manifest.json').read_text(encoding='utf-8'))['name']).group(1))
files = [source / name for name in ['manifest.json', 'code.js', 'ui.html', 'build.py', 'HUONG-DAN.md', 'ICONS-LICENSE.txt', 'LUONG-THIET-KE.json', 'src/plugin.js', 'src/data.json', 'xem-truoc/index.html']]
previews = sorted((source / 'xem-truoc').glob('*.svg'))
assert len(previews) == (60 if version >=23 else 58 if version >=22 else 56)
for preview in previews:
    ET.parse(preview)
if version>=22:
    files=[source/name for name in ['manifest.json','code.js','ui.html','HUONG-DAN.md','ICONS-LICENSE.txt','LUONG-THIET-KE.json']]
else:
    files.extend(previews)
output = ROOT / f'output/figma/thuthanh-figma-clicon-v{version}-2026-10-23.zip'
with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for path in files:
        archive.write(path, path.relative_to(source).as_posix())
with zipfile.ZipFile(output) as archive:
    assert archive.testzip() is None
    assert archive.namelist().count('manifest.json') == 1
    assert len([name for name in archive.namelist() if name.endswith('manifest.json')]) == 1
    assert len(archive.namelist()) == len(files)
    for path in files:
        assert archive.read(path.relative_to(source).as_posix()) == path.read_bytes()
result = {'artifact': output.relative_to(ROOT).as_posix(), 'sha256': hashlib.sha256(output.read_bytes()).hexdigest(), 'files': len(files), 'svg_previews': len(previews), 'svg_previews_packaged': 0 if version>=22 else len(previews), 'bytes': output.stat().st_size, 'crc': 'passed', 'source_byte_match': 'passed', 'manifest_count': 1}
(ROOT / f'output/figma/v{version}-package.json').write_text(json.dumps(result, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
print(json.dumps(result))
