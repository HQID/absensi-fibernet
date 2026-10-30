import type { Metadata, Viewport } from "next"
import { Instrument_Sans } from "next/font/google"
import { Toaster } from "sonner"
import { GlobalLoader } from "@/components/layout/global-loader"
import "./globals.css"

const instrumentSans = Instrument_Sans({
  variable: "--font-instrument-sans",
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
  display: "swap",
})

const appName = process.env.NEXT_PUBLIC_APP_NAME || "Absensi Fibernet"

export const metadata: Metadata = {
  title: {
    default: appName,
    template: `%s - ${appName}`,
  },
  description:
    "Sistem Presensi PKL Geofencing PT Fiber Networks Indonesia Branch Palu",
  manifest: "/manifest.json",
  icons: {
    icon: [
      { url: "/logo-fibernet.png?v=2", type: "image/png" },
      { url: "/favicon.ico?v=2" },
    ],
    apple: "/logo-fibernet.png?v=2",
  },
  appleWebApp: {
    capable: true,
    statusBarStyle: "default",
    title: "Absensi PKL",
  },
}

export const viewport: Viewport = {
  themeColor: "#0891b2",
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
}

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="id" className={`${instrumentSans.variable} h-full antialiased`}>
      <body className="min-h-full flex flex-col">
        {children}
        <GlobalLoader />
        <Toaster position="bottom-right" richColors />
      </body>
    </html>
  )
}
