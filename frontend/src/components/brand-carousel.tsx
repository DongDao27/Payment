"use client";
import Link from "next/link";
import Image from "next/image";
import { useRef, useState, useEffect } from "react";
import logos from "@/lib/brands.json";
import { useStore } from "./store-provider";

export function BrandCarousel() {
  const { brands } = useStore();
  const track = useRef<HTMLDivElement>(null);
  const [edges, setEdges] = useState({ start: true, end: false });
  function measure() {
    const el = track.current;
    if (el)
      setEdges({
        start: el.scrollLeft < 2,
        end: el.scrollLeft + el.clientWidth >= el.scrollWidth - 2,
      });
  }
  useEffect(() => {
    const el = track.current;
    if (!el) return;
    const observer = new ResizeObserver(measure);
    observer.observe(el);
    measure();
    return () => observer.disconnect();
  }, [brands]);
  if (!brands.length) return null;
  function move(direction: number) {
    const el = track.current;
    if (el)
      el.scrollBy({
        left: direction * el.clientWidth * 0.8,
        behavior: matchMedia("(prefers-reduced-motion: reduce)").matches
          ? "instant"
          : "smooth",
      });
  }
  const sorted = [...brands].sort(
    (a, b) => Number(b.name === "Thiên Long") - Number(a.name === "Thiên Long"),
  );
  return (
    <section
      className="container footer-brands brand-carousel"
      aria-label="Thương hiệu và nhà xuất bản có sản phẩm"
    >
      <button
        type="button"
        className="brand-arrow"
        disabled={edges.start}
        onClick={() => move(-1)}
        aria-label="Thương hiệu trước"
        aria-controls="footer-brand-track"
      >
        ‹
      </button>
      <div
        className="brand-track"
        id="footer-brand-track"
        ref={track}
        onScroll={measure}
      >
        {sorted.map((brand) => {
          const logo = logos.find((item) => item.name === brand.name);
          return (
            <Link
              href={`/products?${new URLSearchParams({ brand: brand.name })}`}
              className="brand-tile"
              key={brand.name}
              aria-label={`${brand.name}, ${brand.product_count} sản phẩm`}
            >
              {logo ? (
                <Image
                  src={logo.src}
                  alt={brand.name}
                  width={logo.width}
                  height={logo.height}
                  sizes="220px"
                />
              ) : (
                <strong className="brand-name">{brand.name}</strong>
              )}
              <small>{brand.product_count} sản phẩm</small>
            </Link>
          );
        })}
      </div>
      <button
        type="button"
        className="brand-arrow"
        disabled={edges.end}
        onClick={() => move(1)}
        aria-label="Thương hiệu tiếp theo"
        aria-controls="footer-brand-track"
      >
        ›
      </button>
    </section>
  );
}
