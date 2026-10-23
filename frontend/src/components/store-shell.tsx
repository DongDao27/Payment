"use client";

import Link from "next/link";
import Image from "next/image";
import brands from "@/lib/brands.json";
import { NewsletterForm } from "./newsletter-form";
import { usePathname, useRouter } from "next/navigation";
import { useState, type FormEvent, type ReactNode } from "react";
import { useStore } from "./store-provider";
import { Icon } from "./ui";
import { errorMessage } from "@/lib/format";

export function Brand({ light = false }: { light?: boolean }) {
  return (
    <Link
      href="/"
      className="brand"
      aria-label="Nhà sách Thu Thanh — Trang chủ"
    >
      <Image
        src={`/brand/logo-${light ? "light" : "dark"}.svg`}
        alt="Nhà sách Thu Thanh"
        width={310}
        height={82}
        priority={light}
      />
    </Link>
  );
}
export function StoreShell({ children }: { children: ReactNode }) {
  const { user, cart, requireLogin, logout, notify } = useStore();
  const path = usePathname();
  const router = useRouter();
  const [search, setSearch] = useState("");
  function submit(event: FormEvent) {
    event.preventDefault();
    router.push(`/products?${new URLSearchParams({ q: search.trim() })}`);
  }
  const count = cart?.items.reduce((sum, item) => sum + item.quantity, 0) ?? 0;
  return (
    <>
      {process.env.NEXT_PUBLIC_DEMO_MODE === "1" && (
        <div className="demo-environment" role="status">
          Môi trường demo — dữ liệu bán hàng giả lập
        </div>
      )}
      <a href="#main" className="skip-link">
        Đến nội dung chính
      </a>
      <div className="announcement">Chào mừng đến với nhà sách Thu Thanh</div>
      <header className="site-header">
        <div className="header-inner">
          <div className="mobile-menu">
            <CategoryMenu />
          </div>
          <Brand light />
          <form role="search" className="header-search" onSubmit={submit}>
            <label className="sr-only" htmlFor="site-search">
              Tìm sản phẩm
            </label>
            <input
              id="site-search"
              placeholder="Tìm sách, đồ dùng học tập…"
              value={search}
              maxLength={100}
              onChange={(event) => setSearch(event.target.value)}
            />
            <button type="submit" aria-label="Tìm kiếm">
              <Icon name="search" size={20} />
            </button>
          </form>
          <div className="header-actions">
            {user ? (
              <details className="account-menu">
                <summary aria-label="Tài khoản">
                  <Icon name="user" />
                  <span>{user.name.split(" ").at(-1)}</span>
                </summary>
                <div className="dropdown">
                  <strong>{user.name}</strong>
                  <Link href={user.role === "admin" ? "/admin" : "/orders"}>
                    {user.role === "admin"
                      ? "Quản lý cửa hàng"
                      : "Đơn hàng của tôi"}
                  </Link>
                  <button
                    onClick={() =>
                      logout().catch((error) => notify(errorMessage(error)))
                    }
                  >
                    Đăng xuất
                  </button>
                </div>
              </details>
            ) : (
              <button className="header-action" onClick={requireLogin}>
                <Icon name="user" />
                <span>Tài khoản</span>
              </button>
            )}
            <Link
              className="header-action cart-link"
              href="/cart"
              aria-label={`Giỏ hàng, ${count} sản phẩm`}
            >
              <Icon name="cart" />
              <span>Giỏ hàng</span>
              {count > 0 && <b className="cart-count">{count}</b>}
            </Link>
          </div>
        </div>
      </header>
      <nav className="store-navigation" aria-label="Điều hướng mua sắm">
        <div className="container">
          <CategoryMenu />
          <Link href="/">
            <Icon name="home" size={20} />
            Trang chủ
          </Link>
          <Link href="/orders">
            <Icon name="box" size={20} />
            Đơn hàng của tôi
          </Link>
          <span className="nav-location">Nhà sách tại Đà Nẵng</span>
        </div>
      </nav>
      <main id="main" tabIndex={-1}>
        {children}
      </main>
      <footer className="site-footer">
        <section className="container footer-brands" aria-label="Các nhãn hàng">
          <div className="brand-grid">
            {brands.map((brand) => (
              <div className="brand-tile" key={brand.name}>
                <Image
                  src={brand.src}
                  alt={brand.name}
                  width={brand.width}
                  height={brand.height}
                  sizes="(max-width: 700px) 40vw, 150px"
                />
              </div>
            ))}
          </div>
        </section>
        <div className="container footer-newsletter">
          <NewsletterForm />
          <div className="footer-visit">
            <h2>Ghé nhà sách Thu Thanh</h2>
            <address>295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng</address>
            <p>Chọn sách trực tuyến hoặc đặt trước để nhận tại cửa hàng.</p>
            <Link href="/policies#shipping">
              Hướng dẫn mua hàng và nhận sách →
            </Link>
          </div>
        </div>
        <div className="container footer-grid">
          <div className="footer-brand">
            <Brand />
            <address>295 Tôn Đức Thắng, Hòa Khánh, Đà Nẵng</address>
            <p>
              Sách và đồ dùng học tập cho mỗi ngày.
              <br />
              Đặt trực tuyến, giao tận nhà hoặc nhận tại cửa hàng.
            </p>
          </div>
          <div>
            <h3>DỊCH VỤ</h3>
            <Link href="/policies#terms">Điều khoản sử dụng</Link>
            <Link href="/policies#privacy">Bảo mật thông tin cá nhân</Link>
            <Link href="/policies#terms">Thông tin thanh toán</Link>
            <Link href="/products">Danh mục sản phẩm</Link>
          </div>
          <div>
            <h3>HỖ TRỢ</h3>
            <Link href="/policies">Chính sách mua hàng</Link>
            <Link href="/policies#returns">Không đổi trả, hoàn tiền</Link>
            <Link href="/policies#shipping">Chính sách vận chuyển</Link>
            <Link href="/policies#shipping">Hướng dẫn nhận tại cửa hàng</Link>
          </div>
          <div>
            <h3>TÀI KHOẢN CỦA TÔI</h3>
            <Link href="/login">Đăng nhập</Link>
            <Link href="/register">Tạo tài khoản</Link>
            <Link href="/orders">Lịch sử mua hàng</Link>
            <Link href="/cart">Giỏ hàng của tôi</Link>
          </div>
        </div>
        <div className="container copyright">
          © 2026 Thu Thanh <span>Nhà sách &amp; văn phòng phẩm</span>
        </div>
      </footer>
      <nav className="mobile-bottom-nav" aria-label="Điều hướng nhanh">
        <Link aria-current={path === "/" ? "page" : undefined} href="/">
          <Icon name="home" />
          Trang chủ
        </Link>
        <Link
          aria-current={path.startsWith("/products") ? "page" : undefined}
          href="/products"
        >
          <Icon name="menu" />
          Danh mục
        </Link>
        <Link aria-current={path === "/cart" ? "page" : undefined} href="/cart">
          <Icon name="cart" />
          Giỏ hàng{count ? ` (${count})` : ""}
        </Link>
        {user ? (
          <Link href={user.role === "admin" ? "/admin" : "/orders"}>
            <Icon name="user" />
            Tài khoản
          </Link>
        ) : (
          <button onClick={requireLogin}>
            <Icon name="user" />
            Tài khoản
          </button>
        )}
      </nav>
    </>
  );
}

function CategoryMenu() {
  const { categories } = useStore();
  return (
    <details className="category-menu">
      <summary>
        <Icon name="menu" />
        <span>Danh mục</span>
      </summary>
      <nav className="dropdown" aria-label="Danh sách danh mục">
        <Link
          href="/products"
          onClick={(event) =>
            event.currentTarget.closest("details")?.removeAttribute("open")
          }
        >
          Tất cả sản phẩm
        </Link>
        {categories.map((category) => (
          <Link
            key={category.id}
            href={`/products?category_id=${category.id}`}
            onClick={(event) =>
              event.currentTarget.closest("details")?.removeAttribute("open")
            }
          >
            {category.name}
          </Link>
        ))}
      </nav>
    </details>
  );
}
