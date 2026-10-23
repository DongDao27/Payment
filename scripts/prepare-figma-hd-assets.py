"""Embed approved catalog originals as lossless PNG for Figma createImage.

Pillow is a tooling dependency only; this does not resize or retouch the photos.
"""
from pathlib import Path
import base64
import hashlib
import io
import json
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / '.runtime/qr-tools'))
from PIL import Image

path = ROOT / 'thu-thanh-figma-clicon/src/data.json'
data = json.loads(path.read_text(encoding='utf-8'))
assets = json.loads((ROOT / 'docs/PRODUCT-IMAGE-SOURCES.json').read_text(encoding='utf-8'))
for product in data['products']:
    asset = next(a for a in assets['products'] if a['sku'] == product['id'])
    original = (ROOT / ('frontend/public' + asset['src'])).read_bytes()
    assert hashlib.sha256(original).hexdigest() == asset['sha256']
    with Image.open(io.BytesIO(original)) as source:
        rgb = source.convert('RGB')
        output = io.BytesIO()
        rgb.save(output, format='PNG', optimize=True)
        encoded = output.getvalue()
        with Image.open(io.BytesIO(encoded)) as result:
            assert result.size == rgb.size
            assert result.convert('RGB').tobytes() == rgb.tobytes()
        product['image'] = base64.b64encode(encoded).decode()
        product['image_sha256'] = hashlib.sha256(encoded).hexdigest()
        product['source_image_sha256'] = asset['sha256']
        product['image_format'] = 'PNG'
        product['image_width'], product['image_height'] = rgb.size
path.write_text(json.dumps(data, ensure_ascii=False, separators=(',', ':')), encoding='utf-8')
print('Prepared 24 lossless PNG originals for Figma; source pixels and dimensions unchanged.')
