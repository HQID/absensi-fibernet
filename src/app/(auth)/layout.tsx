import Link from "next/link"
import type { ReactNode } from "react"

export default function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen fibernet-auth-bg antialiased">
      <div className="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div className="flex w-full max-w-sm flex-col gap-2">
          <Link
            href="/"
            className="flex flex-col items-center gap-2 font-medium"
          >
            <span className="flex h-20 mb-4 items-center justify-center">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src="/fibernet.png"
                alt="Logo"
                className="h-20 w-auto object-contain bg-white rounded-lg px-2 py-1 shadow-sm"
              />
            </span>
            <span className="sr-only">Absensi Fibernet</span>
          </Link>

          <div className="flex flex-col gap-6 rounded-2xl bg-white/80 backdrop-blur-xl border border-white/40 shadow-xl p-8">
            {children}
          </div>
        </div>
      </div>
    </div>
  )
}
