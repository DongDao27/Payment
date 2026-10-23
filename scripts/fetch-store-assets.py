"""Fetch exact workbook source images and selected reference banners; no database writes."""
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed
import hashlib, io, json, sys, urllib.request
from urllib.parse import urlparse

ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT/'.runtime/qr-tools'))
from PIL import Image, ImageChops, ImageStat

STAGED=ROOT/'.runtime/product-import/ae2ca77c65437321'
OUT=ROOT/'frontend/public/catalog-hd'
OUT.mkdir(parents=True,exist_ok=True)
def fetch(url):
    request=urllib.request.Request(url,headers={'User-Agent':'Mozilla/5.0','Referer':'https://www.fahasa.com/'})
    with urllib.request.urlopen(request,timeout=25) as response:
        data=response.read(12_000_001)
        if len(data)>12_000_000:raise ValueError('Image too large')
        return data
def get_product(p):
    sku=p['sku'];url=p['source_image_url']
    try:
        host=urlparse(url).hostname or ''
        if urlparse(url).scheme!='https' or not host.endswith('.fahasa.com'):raise ValueError('Unapproved source host')
        data=fetch(url);im=Image.open(io.BytesIO(data));im.load()
        old=Image.open(STAGED/p['image']);old.load()
        if im.format not in ['JPEG','PNG','WEBP']:raise ValueError('Invalid image MIME')
        if min(im.size)<400 or im.width*im.height<=old.width*old.height:raise ValueError('No sharper original available')
        delta=ImageStat.Stat(ImageChops.difference(im.convert('RGB').resize((64,64)),old.convert('RGB').resize((64,64)))).mean
        difference=round(sum(delta)/3,3)
        if difference>8:raise ValueError('Cover differs from embedded source: '+str(difference))
        ext={'JPEG':'jpg','PNG':'png','WEBP':'webp'}[im.format];target=OUT/(sku+'.'+ext);target.write_bytes(data)
        return {'sku':sku,'src':'/catalog-hd/'+target.name,'source_url':url,'source_product_url':p['source_product_url'],'sha256':hashlib.sha256(data).hexdigest(),'width':im.width,'height':im.height,'bytes':len(data),'thumbnail_dimensions':list(old.size),'thumbnail_sha256':p['image_sha256'],'mean_rgb_difference':difference,'status':'verified_original'}
    except Exception as e:return {'sku':sku,'source_url':url,'status':'retained_thumbnail','reason':str(e)}
def main():
    catalog=json.loads((STAGED/'catalog.json').read_text(encoding='utf-8'))
    with ThreadPoolExecutor(max_workers=8) as pool:
        results=[future.result() for future in as_completed([pool.submit(get_product,p) for p in catalog['products']])]
    results.sort(key=lambda p:p['sku'])
    mapping={p['sku']:{k:p[k] for k in ['src','width','height']} for p in results if p['status']=='verified_original'}
    (ROOT/'frontend/src/lib/catalog-images.json').write_text(json.dumps(mapping,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    (ROOT/'docs/PRODUCT-IMAGE-SOURCES.json').write_text(json.dumps({'source_workbook_sha256':catalog['sha256'],'products':results},ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print(json.dumps({'originals':len(mapping),'retained':len(results)-len(mapping),'failures':[p for p in results if p['status']!='verified_original']},ensure_ascii=False))
if __name__=='__main__':main()
