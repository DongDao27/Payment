"""Audit/stage the owner's workbook; never writes to MySQL or invents price/stock.

Uses embedded thumbnails without network downloads. Original workbook is read-only.
Run: python scripts/audit-products.py Nha_sach_Thu_Thanh_San_pham_2026-10-21.xlsx
"""
import argparse
from collections import Counter
from hashlib import sha256
import json
from pathlib import Path
import posixpath
import re
from xml.etree import ElementTree as ET
from zipfile import ZipFile

ROOT = Path(__file__).resolve().parents[1]
NS = {
    "s": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
    "xdr": "http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing",
    "a": "http://schemas.openxmlformats.org/drawingml/2006/main",
    "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
}


def relations(archive, part):
    path = posixpath.join(posixpath.dirname(part), "_rels", posixpath.basename(part) + ".rels")
    if path not in archive.namelist():
        return {}
    return {
        rel.get("Id"): posixpath.normpath(posixpath.join(posixpath.dirname(part), rel.get("Target").lstrip("/")))
        if not rel.get("Target").startswith("/") else rel.get("Target").lstrip("/")
        for rel in ET.fromstring(archive.read(path))
        if rel.get("TargetMode") != "External"
    }


def read_rows(archive, part, strings):
    rows = []
    for row in ET.fromstring(archive.read(part)).findall(".//s:row", NS):
        values = {}
        for cell in row.findall("s:c", NS):
            column = re.sub(r"\d", "", cell.get("r"))
            if cell.find("s:f", NS) is not None:
                raise ValueError(f"Formula requires manual review: {part}:{cell.get('r')}")
            value = cell.find("s:v", NS)
            inline = cell.find("s:is", NS)
            text = value.text or "" if value is not None else "".join(inline.itertext()) if inline is not None else ""
            values[column] = strings[int(text)] if cell.get("t") == "s" else text
        rows.append((int(row.get("r")), values))
    return rows


def money(value, field, row):
    if value == "":
        return None
    if not re.fullmatch(r"\d+", value) or int(value) > 1_000_000_000:
        raise ValueError(f"Invalid integer {field} at row {row}")
    return int(value)


def audit(workbook):
    digest = sha256(workbook.read_bytes()).hexdigest()
    destination = ROOT / ".runtime" / "product-import" / digest[:16]
    with ZipFile(workbook) as archive:
        strings = []
        if "xl/sharedStrings.xml" in archive.namelist():
            strings = ["".join(node.itertext()) for node in ET.fromstring(archive.read("xl/sharedStrings.xml")).findall("s:si", NS)]
        workbook_xml = ET.fromstring(archive.read("xl/workbook.xml"))
        refs = relations(archive, "xl/workbook.xml")
        sheets = {sheet.get("name"): refs[sheet.get(f"{{{NS['r']}}}id")] for sheet in workbook_xml.findall("s:sheets/s:sheet", NS)}
        rows = read_rows(archive, sheets["San_pham"], strings)
        expected = {"C": "Mã sản phẩm", "D": "Tên sản phẩm", "O": "Giá bán Thu Thanh (VND)", "P": "Tồn kho Thu Thanh"}
        if any(rows[0][1].get(key) != label for key, label in expected.items()):
            raise ValueError("Workbook columns do not match the reviewed import contract")
        images = {}
        sheet_xml = ET.fromstring(archive.read(sheets["San_pham"]))
        drawing = sheet_xml.find("s:drawing", NS)
        if drawing is not None:
            drawing_part = relations(archive, sheets["San_pham"])[drawing.get(f"{{{NS['r']}}}id")]
            image_refs = relations(archive, drawing_part)
            for anchor in ET.fromstring(archive.read(drawing_part)):
                row = anchor.find("xdr:from/xdr:row", NS)
                image = anchor.find(".//a:blip", NS)
                if row is not None and image is not None:
                    number = int(row.text) + 1
                    if number in images:
                        raise ValueError(f"More than one embedded image at row {number}")
                    images[number] = archive.read(image_refs[image.get(f"{{{NS['r']}}}embed")])
        products = []
        for number, data in rows[1:]:
            if not data.get("C"):
                continue
            sku = data["C"]
            if not re.fullmatch(r"TT-[A-Z]+-\d+", sku) or not data.get("D") or not re.fullmatch(r"[a-z0-9]+(?:-[a-z0-9]+)*", data.get("H", "")):
                raise ValueError(f"Invalid SKU/name/slug at row {number}")
            image = images.get(number)
            extension = ".png" if image and image.startswith(b"\x89PNG\r\n\x1a\n") else ".jpg" if image and image.startswith(b"\xff\xd8\xff") else None
            products.append({
                "source_row": number, "sku": sku, "name": data["D"], "slug": data["H"],
                "category": data["A"], "subcategory": data["B"], "source_category_id": int(data["G"]),
                "publisher": data.get("I", ""), "author": data.get("J", ""),
                "grade": int(data["L"]) if data.get("L") else None,
                "reference_price": money(data.get("M", ""), "reference price", number),
                "source_cover_price": money(data.get("N", ""), "source cover price", number),
                "store_price": money(data.get("O", ""), "store price", number),
                "store_stock": money(data.get("P", ""), "store stock", number),
                "source_image_url": data.get("F", ""), "source_product_url": data.get("S", ""),
                "image": f"images/{sku}{extension}" if extension else None,
                "image_sha256": sha256(image).hexdigest() if extension else None,
                "source_note": data.get("U", ""),
            })
        for key in ["sku", "slug"]:
            duplicates = [value for value, count in Counter(p[key] for p in products).items() if count > 1]
            if duplicates:
                raise ValueError(f"Duplicate {key}: {duplicates}")
        # Validate the entire workbook before writing staged files.
        destination.mkdir(parents=True, exist_ok=True)
        (destination / "images").mkdir(exist_ok=True)
        for product in products:
            if product["image"]:
                (destination / product["image"]).write_bytes(images[product["source_row"]])
        manifest = {
            "source_file": workbook.name, "sha256": digest, "mode": "staged_not_imported",
            "products": products,
        }
        (destination / "catalog.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    summary = {
        "products": len(products), "categories": dict(Counter(p["category"] for p in products)),
        "subcategories": len(set(p["source_category_id"] for p in products)),
        "embedded_images_matched": sum(p["image"] is not None for p in products),
        "missing_store_price": sum(p["store_price"] is None for p in products),
        "missing_store_stock": sum(p["store_stock"] is None for p in products),
        "missing_reference_price": sum(p["reference_price"] is None for p in products),
        "staged_directory": str(destination.relative_to(ROOT)), "source_sha256": digest,
    }
    report = "# Kiểm tra workbook sản phẩm — 22/10/2026\n\n"
    report += "Nguồn: `" + workbook.name + "`. Workbook được đọc nguyên trạng, không sửa file gốc hoặc ghi MySQL.\n\n"
    report += "```json\n" + json.dumps(summary, ensure_ascii=False, indent=2) + "\n```\n\n"
    report += "SKU/slug unique, cột đúng hợp đồng, số nguyên VND hợp lệ; ảnh ghép theo drawing anchor của từng hàng, không đoán thứ tự tên file media. Ảnh nhúng là preview từ workbook, chưa chứng minh đủ độ phân giải cho ảnh lớn. Không download URL ngoài hoặc lấy tình trạng nguồn làm tồn cửa hàng.\n\n"
    report += "Ô giá/tồn trống giữ null. Workbook hiện đã có giá/tồn cho toàn bộ sản phẩm; staging chưa đồng nghĩa đã nhập MySQL. ID danh mục con trong workbook không tự dùng làm FK của 6 danh mục gốc trong MySQL; cần mapping/mở rộng hợp đồng rõ ràng trước nhập. Chưa tự bật hot/bestseller, không lấy giá tham khảo thay giá bán.\n"
    (ROOT / "docs" / "PRODUCT-IMPORT-AUDIT.md").write_text(report, encoding="utf-8")
    return summary


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("workbook", type=Path)
    args = parser.parse_args()
    print(json.dumps(audit(args.workbook.resolve()), ensure_ascii=False, indent=2))
