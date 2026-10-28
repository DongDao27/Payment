"use client";

import { ProductPicture } from "./product-picture";
import type { Product } from "@/lib/types";
import { imageSrc } from "@/lib/product-images";
export { imageSrc } from "@/lib/product-images";
import { ProductPrice } from "./product-price";
import { Icon } from "./ui";
import { useStore } from "./store-provider";

export function ProductCard({
  product,
  priority = false,
  featured = false,
}: {
  product: Product;
  priority?: boolean;
  featured?: boolean;
}) {
  const { openProduct } = useStore();
  const image = product.images[0];
  const available = product.is_active && product.available_quantity > 0;
  return (
    <article className={`product-card${featured ? " featured-product" : ""}`}>
      <button
        className="product-image"
        onClick={() => openProduct(product)}
        aria-label={`Xem ${product.name}`}
      >
        {image && imageSrc(image.url) ? (
          <ProductPicture product={product} priority={priority} />
        ) : (
          <Icon name="book" size={64} />
        )}
      </button>
      <p className="product-category">{product.category.name}</p>
      <h3>
        <button onClick={() => openProduct(product)}>{product.name}</button>
      </h3>
      <div className="product-bottom">
        <ProductPrice product={product} />
      </div>
      {product.sold_count !== undefined && product.sold_count > 0 && (
        <small className="sold-count">
          Đã bán {product.sold_count.toLocaleString("vi-VN")}
        </small>
      )}
      {!available && <span className="muted stock-label">Tạm hết hàng</span>}
      <button
        className="quick-view-trigger"
        onClick={() => openProduct(product)}
        aria-label={`Xem nhanh ${product.name}`}
      >
        <span className="quick-view-icon" aria-hidden="true">
          <Icon name="arrow" size={16} />
        </span>
        <span className="quick-view-label">Xem nhanh</span>
      </button>
    </article>
  );
}
