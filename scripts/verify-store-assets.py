"""Verify local source hashes, dimensions and lossless Figma conversions."""
from pathlib import Path
import base64
import hashlib
import io
import json
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / '.runtime/qr-tools'))
from PIL import Image

assets = json.loads((ROOT / 'docs/PRODUCT-IMAGE-SOURCES.json').read_text(encoding='utf-8'))
mapping = json.loads((ROOT / 'frontend/src/lib/catalog-images.json').read_text(encoding='utf-8'))
assert len(assets['products']) == len(mapping) == 136
for asset in assets['products']:
    content = (ROOT / ('frontend/public' + asset['src'])).read_bytes()
    assert hashlib.sha256(content).hexdigest() == asset['sha256']
    assert mapping[asset['sku']]['src'] == asset['src']
    with Image.open(io.BytesIO(content)) as image:
        assert image.size == (asset['width'], asset['height'])
        assert min(image.size) >= 580
    assert asset['status'] == 'verified_original'
    assert asset['mean_rgb_difference'] <= 8
data = json.loads((ROOT / 'thu-thanh-figma-clicon/src/data.json').read_text(encoding='utf-8'))
for product in data['products']:
    asset = next(a for a in assets['products'] if a['sku'] == product['id'])
    encoded = base64.b64decode(product['image'])
    assert hashlib.sha256(encoded).hexdigest() == product['image_sha256']
    assert product['source_image_sha256'] == asset['sha256']
    with Image.open(io.BytesIO(encoded)) as image, Image.open(ROOT / ('frontend/public' + asset['src'])) as source:
        assert image.format == 'PNG'
        assert image.size == source.size
        assert image.convert('RGB').tobytes() == source.convert('RGB').tobytes()
metadata = json.loads((ROOT / 'docs/ASSET-SOURCES.json').read_text(encoding='utf-8'))
for asset in [metadata['banner'], *metadata['brand'].values()]:
    path = asset.get('local_path', asset.get('path'))
    assert hashlib.sha256((ROOT / path).read_bytes()).hexdigest() == asset['sha256']
for asset in metadata.get('manufacturers', []):
    content = (ROOT / ('frontend/public' + asset['src'])).read_bytes()
    assert hashlib.sha256(content).hexdigest() == asset['sha256']
    with Image.open(io.BytesIO(content)) as image:
        assert image.size == (asset['width'], asset['height'])
for brand in data.get('brands', []):
    assert hashlib.sha256(base64.b64decode(brand['image'])).hexdigest() == brand['sha256']
for asset in metadata.get('additional_banners', []):
    assert hashlib.sha256((ROOT / asset['local_path']).read_bytes()).hexdigest() == asset['sha256']
for banner in data.get('additionalBanners', []):
    assert hashlib.sha256(base64.b64decode(banner['image'])).hexdigest() == banner['sha256']
campaigns = metadata.get('storefront_campaigns_D58', [])
for asset in campaigns:
    content = (ROOT / asset['local_path']).read_bytes()
    assert hashlib.sha256(content).hexdigest() == asset['sha256']
    with Image.open(io.BytesIO(content)) as image:
        assert image.size == (asset['width'], asset['height'])
config = json.loads((ROOT / 'frontend/src/lib/store-banners.json').read_text(encoding='utf-8'))
for banner in config.values():
    for variant in [banner, *([banner['mobile']] if banner.get('mobile') else [])]:
        asset = next(a for a in campaigns if a['local_path'] == 'frontend/public' + variant['src'])
        assert (asset['width'], asset['height']) == (variant['width'], variant['height'])
for banner in data.get('webCampaigns', {}).values():
    for variant in [banner, *([banner['mobile']] if banner.get('mobile') else [])]:
        embedded = base64.b64decode(variant['image'])
        assert hashlib.sha256(embedded).hexdigest() == variant['image_sha256']
        with Image.open(io.BytesIO(embedded)) as image, Image.open(ROOT / ('frontend/public' + variant['src'])) as original:
            assert image.format in ['JPEG', 'PNG', 'GIF']
            assert image.size == original.size == (variant['width'], variant['height'])
            assert image.convert('RGBA').tobytes() == original.convert('RGBA').tobytes()
if data.get('store'):
    map_content = base64.b64decode(data['store']['mapImage'])
    assert hashlib.sha256(map_content).hexdigest() == data['store']['map_sha256']
    with Image.open(io.BytesIO(map_content)) as image:
        assert image.size == (300, 164)
        assert len(image.convert('RGB').getcolors(100000)) > 100
print(json.dumps({'result': 'passed', 'originals': 136, 'figma_lossless_png': 24, 'banners': 1 + len(metadata.get('additional_banners', [])), 'campaign_variants': len(campaigns), 'brand_variants': 2, 'manufacturers': len(metadata.get('manufacturers', []))}))
