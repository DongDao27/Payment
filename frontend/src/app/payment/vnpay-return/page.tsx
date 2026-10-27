"use client";
import Link from "next/link";
import { useEffect, useState } from "react";
import { Loading, Notice } from "@/components/ui";
import { api } from "@/lib/api-client";
import type { VnpayReturn } from "@/lib/types";
import { errorMessage, statusLabel } from "@/lib/format";

// VNPAY redirects here. Laravel verifies the signature and may query VNPAY;
// this page never decides that an order is paid.
export default function VnpayReturnPage() {
  const [result, setResult] = useState<VnpayReturn | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    const signed = new URLSearchParams(
      [...new URLSearchParams(window.location.search)].filter(([key]) =>
        key.startsWith("vnp_"),
      ),
    );
    api<VnpayReturn>(`/payments/vnpay/return?${signed}`)
      .then(({ data }) => {
        if (active) setResult(data);
      })
      .catch((error) => {
        if (active) setError(errorMessage(error));
      });
    return () => {
      active = false;
    };
  }, []);
  const message = !result
    ? ""
    : result.payment_status === "paid"
      ? "Thanh toán thành công. Đơn hàng đã được xác minh."
      : result.result === "failed"
        ? "Giao dịch không thành công hoặc đã bị hủy. Bạn có thể thanh toán lại khi đơn còn thời gian giữ hàng."
        : result.payment_status === "reconciliation_required"
          ? "Giao dịch đang chờ nhà sách đối soát. Vui lòng không thanh toán lại."
          : "Hệ thống đang chờ VNPAY xác nhận giao dịch. Vui lòng kiểm tra lại trạng thái đơn sau ít phút.";
  return (
    <div className="container page-section">
      <div className="breadcrumb">
        <Link href="/orders">Đơn hàng của tôi</Link>
        <span>/</span>Kết quả thanh toán
      </div>
      <h1>Kết quả thanh toán VNPAY</h1>
      {error && <Notice error>{error}</Notice>}
      {!result ? (
        !error && <Loading />
      ) : (
        <div className="detail-panel">
          <h2>Đơn {result.order_code}</h2>
          <p>
            Trạng thái:{" "}
            <strong>
              {statusLabel[result.payment_status] ?? result.payment_status}
            </strong>
          </p>
          <Notice error={result.result === "failed"}>{message}</Notice>
        </div>
      )}
      <div className="button-row">
        {result && (
          <Link
            className="button primary"
            href={`/orders/${encodeURIComponent(result.order_code)}`}
          >
            Xem chi tiết đơn
          </Link>
        )}
        <Link className="button" href="/orders">
          Về danh sách đơn
        </Link>
      </div>
    </div>
  );
}
