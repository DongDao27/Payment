"""Archive superseded generated files locally, verify every byte, then remove duplicates.

Never touches runtime data, secrets, the source workbook, current code or dependencies.
"""
from pathlib import Path
from datetime import datetime
import hashlib
import json
import os
import re
import shutil
import stat
import zipfile

ROOT = Path(__file__).resolve().parents[1]
ARCHIVES = ROOT.parent / 'ThuThanh-Archives'
if (ROOT / 'docs/CLEANUP-MANIFEST.json').exists():
    raise RuntimeError('Cleanup already recorded. Keep its manifest and archive; do not overwrite recovery evidence.')
ARCHIVES.mkdir(exist_ok=True)
archive = ARCHIVES / ('thu-thanh-legacy-' + datetime.now().strftime('%Y%m%d-%H%M%S') + '.zip')
if archive.exists():
    raise RuntimeError('Refusing to overwrite an existing archive.')
candidates = [ROOT / 'Thu-Thanh-AI-Build-Pack']
for name in ['storefront-v22.css', 'storefront-v23.css']:
    if name in (ROOT / 'frontend/src/app/layout.tsx').read_text(encoding='utf-8'):
        raise RuntimeError('A legacy stylesheet is still imported; keep it until the current stylesheet is wired.')
    candidates.append(ROOT / 'frontend/src/app' / name)
candidates += [p for p in (ROOT / 'output/figma').iterdir() if 'v24' not in p.name.lower()]
candidates += [p for p in (ROOT / 'output/playwright').iterdir() if p.name.startswith('v23-')]
candidates = [p for p in candidates if p.exists()]
records = []
for candidate in candidates:
    resolved = candidate.resolve()
    if resolved == ROOT or not resolved.is_relative_to(ROOT):
        raise RuntimeError('Cleanup target escaped workspace: ' + str(candidate))
    members = [candidate] + (list(candidate.rglob('*')) if candidate.is_dir() else [])
    for member in members:
        if member.is_symlink() or getattr(member.lstat(), 'st_file_attributes', 0) & stat.FILE_ATTRIBUTE_REPARSE_POINT:
            raise RuntimeError('Refusing to archive or remove a link/reparse point: ' + str(member))
        if member.is_file():
            records.append({'path': member.relative_to(ROOT).as_posix(), 'bytes': member.stat().st_size, 'sha256': hashlib.sha256(member.read_bytes()).hexdigest()})
with zipfile.ZipFile(archive, 'x', zipfile.ZIP_DEFLATED, compresslevel=6) as output:
    for record in records:
        output.write(ROOT / record['path'], record['path'])
with zipfile.ZipFile(archive) as saved:
    assert saved.testzip() is None
    for record in records:
        assert hashlib.sha256(saved.read(record['path'])).hexdigest() == record['sha256']
# The resolved absolute targets were checked above and every original is recoverable.
for record in records:
    if hashlib.sha256((ROOT / record['path']).read_bytes()).hexdigest() != record['sha256']:
        raise RuntimeError('A file changed during archiving; originals retained.')
for candidate in candidates:
    if candidate.is_dir():
        shutil.rmtree(candidate)
    else:
        candidate.unlink()

history = ['FIGMA-CLICON-REVIEW.md', 'FIGMA-V17-FLOWS.md', 'FIGMA-V20-REVIEW.md', 'FIGMA-V21-REVIEW.md', 'FIGMA-V22-REVIEW.md', 'FIGMA-V23-REVIEW.md', 'CODE-READINESS-2026-10-23.md', 'RUN-AND-PAYMENT-REVIEW-2026-10-23.md']
relocations = {ROOT / 'docs' / name: ROOT / 'docs/archive' / name for name in history if (ROOT / 'docs' / name).exists()}
(ROOT / 'docs/archive').mkdir(exist_ok=True)
markdown = [ROOT / 'README.md', ROOT / 'START-HERE.md', ROOT / 'AGENTS.md', *list((ROOT / 'docs').rglob('*.md'))]
link = re.compile(r'(!?\[[^\]]*\]\()([^\s)]+)(\))')
for original in markdown:
    destination = relocations.get(original, original)
    text = original.read_text(encoding='utf-8')
    def repair(match):
        target = match[2]
        if target.startswith(('#', 'http:', 'https:', 'mailto:', 'data:')):
            return match[0]
        path, mark, anchor = target.partition('#')
        old_path = (original.parent / path).resolve()
        new_path = relocations.get(old_path, old_path)
        if new_path != old_path or destination != original:
            path_text = new_path.as_posix() if new_path.drive != destination.drive else os.path.relpath(new_path, destination.parent).replace('\\', '/')
            target = path_text + (mark + anchor if mark else '')
        return match[1] + target + match[3]
    text = link.sub(repair, text)
    for old, new in relocations.items():
        text = text.replace(old.relative_to(ROOT).as_posix(), new.relative_to(ROOT).as_posix())
    destination.write_text(text, encoding='utf-8')
for original, destination in relocations.items():
    if original.resolve().is_relative_to(ROOT) and destination.resolve().is_relative_to(ROOT):
        original.unlink()
tasks = ROOT / 'docs/TASKS.json'
doc = json.loads(tasks.read_text(encoding='utf-8'))
for task in doc['tasks']:
    for i, evidence in enumerate(task.get('evidence', [])):
        for old, new in relocations.items():
            evidence = evidence.replace(old.relative_to(ROOT).as_posix(), new.relative_to(ROOT).as_posix())
        task['evidence'][i] = evidence
    if any(evidence.startswith('output/figma/') and 'v24' not in evidence for evidence in task.get('evidence', [])):
        task['historical_evidence_archive'] = 'docs/CLEANUP-MANIFEST.json'
tasks.write_text(json.dumps(doc, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
manifest = {'archive': str(archive), 'archive_sha256': hashlib.sha256(archive.read_bytes()).hexdigest(), 'crc': 'passed', 'all_original_bytes_verified': True, 'files': len(records), 'original_bytes': sum(record['bytes'] for record in records), 'archive_bytes': archive.stat().st_size, 'removed_after_verified_backup': records, 'historical_docs_moved': {old.relative_to(ROOT).as_posix(): new.relative_to(ROOT).as_posix() for old, new in relocations.items()}}
(ROOT / 'docs/CLEANUP-MANIFEST.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print(json.dumps({key: value for key, value in manifest.items() if key not in ['removed_after_verified_backup', 'historical_docs_moved']}, ensure_ascii=False))
