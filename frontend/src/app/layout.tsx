import type { Metadata } from "next";
import { StoreProvider } from "@/components/store-provider";
import { StoreShell } from "@/components/store-shell";
import "./globals.css";
import "./clicon.css";
import "./bookstore.css";
import "./styles/storefront.css";

export const metadata: Metadata = {
  title: {
    default: "Thu Thanh — Nhà sách & văn phòng phẩm",
    template: "%s | Thu Thanh",
  },
  description:
    "Khám phá sách giáo khoa, văn học, truyện tranh và đồ dùng học tập tại nhà sách Thu Thanh.",
};
export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="vi" data-scroll-behavior="smooth">
      <body>
        <StoreProvider>
          <StoreShell>{children}</StoreShell>
        </StoreProvider>
      </body>
    </html>
  );
}
