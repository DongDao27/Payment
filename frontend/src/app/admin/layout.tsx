"use client";
import type { ReactNode } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { AccountGate } from "@/components/account-gate";

export default function AdminLayout({ children }: { children: ReactNode }) {
  const path = usePathname();
  return (
    <AccountGate admin>
      <div className="container page-section">
        <div className="breadcrumb">
          <Link href="/">Trang chủ</Link>
          <span>/</span>Quản trị
        </div>
        <div className="page-heading">
          <div>
            <span className="eyebrow muted">NHÀ SÁCH THU THANH</span>
            <h1>Quản lý cửa hàng</h1>
          </div>
        </div>
        <div className="admin-workspace">
          <nav className="admin-nav" aria-label="Quản lý cửa hàng">
            {[
              ["/admin", "Tổng quan"],
              ["/admin/products", "Sản phẩm"],
              ["/admin/categories", "Danh mục"],
              ["/admin/revenue", "Doanh thu"],
              ["/admin/reconciliation", "Đối soát"],
              ["/admin/inventory", "Tồn kho"],
              ["/admin/orders", "Đơn hàng"],
            ].map(([href, label]) => (
              <Link
                key={href}
                href={href}
                aria-current={path === href ? "page" : undefined}
              >
                {label}
              </Link>
            ))}
          </nav>
          <div className="admin-content">{children}</div>
        </div>
      </div>
    </AccountGate>
  );
}
