"use client";

import { useId, useRef, useState } from "react";
import type { Product } from "@/lib/types";
import { api } from "@/lib/api-client";
import { errorMessage } from "@/lib/format";
import { ProductPicture } from "./product-picture";
import { ProductPrice } from "./product-price";
import { FlameIcon } from "./flame-icon";
import { Icon, Notice } from "./ui";
import { useStore } from "./store-provider";

export function BestsellerShowcase({ products }: { products: Product[] }) {
  const [selected, setSelected] = useState(products[0]?.id);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const lock = useRef(false);
  const detailId = useId();
  const titleId = useId();
  const { user, refreshCart, notify, requireProductLogin, openProduct } = useStore();
  const product = products.find((item) => item.id === selected) ?? products[0];
  if (!product) return null;

  async function add() {
    if (lock.current || !product.is_active || product.available_quantity < 1) return;
    if (!user) return requireProductLogin(product, 1);
    if (user.role !== "customer") return;
    lock.current = true;
    setBusy(true);
    setError("");
    try {
      await api("/cart/items", { method: "POST", body: { product_id: product.id, quantity: 1 } });
      await refreshCart();
      notify("Đã thêm sản phẩm vào giỏ hàng.");
    } catch (error) {
      setError(errorMessage(error));
    } finally {
      lock.current = false;
      setBusy(false);
    }
  }

  return (
    <div className="bestseller-showcase">
      <ol className="bestseller-ranking" aria-label="Xếp hạng bán chạy">
        {products.map((item, index) => (
          <li key={item.id}>
            <button
              className="bestseller-rank-button"
              aria-label={`Top ${index + 1}: ${item.name}`}
              aria-pressed={product.id === item.id}
              aria-controls={detailId}
              disabled={busy}
              onClick={() => { setSelected(item.id); setError(""); }}
              onKeyDown={(event) => {
                if (!["ArrowDown", "ArrowUp", "Home", "End"].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === "Home" ? 0 : event.key === "End" ? products.length - 1
                  : (index + (event.key === "ArrowDown" ? 1 : -1) + products.length) % products.length;
                setSelected(products[next].id);
                const buttons = event.currentTarget.closest("ol")?.querySelectorAll<HTMLButtonElement>("button");
                buttons?.[next].focus();
              }}
            >
              <span className="bestseller-rank-number" aria-hidden="true">{index + 1}</span>
              <span className="bestseller-thumbnail">
                {item.images.length ? <ProductPicture product={item} /> : <Icon name="book" size={32} />}
              </span>
              <span className="bestseller-rank-copy">
                <span className="bestseller-top"><FlameIcon /> Top {index + 1}</span>
                <span className="bestseller-name">{item.name}</span>
                {Number(item.sold_count) > 0 && <span className="muted">Đã bán {item.sold_count!.toLocaleString("vi-VN")}</span>}
              </span>
            </button>
          </li>
        ))}
      </ol>
      <section id={detailId} className="bestseller-detail" aria-labelledby={titleId}>
        <div className="bestseller-product-main">
          <button className="bestseller-cover" aria-label={`Xem nhanh ${product.name}`} onClick={() => openProduct(product)}>
            {product.images.length ? <ProductPicture product={product} /> : <Icon name="book" size={64} />}
          </button>
          <div className="bestseller-product-copy">
            <h3 id={titleId}>{product.name}</h3>
            <p className="muted">{product.category.name}</p>
            <p className="bestseller-sku">Mã sản phẩm: {product.sku}</p>
            {Number(product.sold_count) > 0 && <p className="muted">Đã bán {product.sold_count!.toLocaleString("vi-VN")}</p>}
            <ProductPrice product={product} />
            <button className="button primary bestseller-add" onClick={add}
              disabled={busy || product.available_quantity < 1 || !product.is_active || (user !== null && user.role !== "customer")}>
              <Icon name="cart" size={22} />
              {busy ? "Đang thêm…" : product.available_quantity < 1 ? "Tạm hết hàng" : "Thêm vào giỏ hàng"}
            </button>
            {error && <Notice error>{error}</Notice>}
          </div>
        </div>
        <div className="bestseller-description">
          <h4>Mô tả sản phẩm</h4>
          <p>{product.description?.trim().slice(0, 1200) || `${product.name} thuộc danh mục ${product.category.name.toLowerCase()}.`}</p>
        </div>
      </section>
    </div>
  );
}
