import type { Metadata } from "next"
import { ReloadButton } from "./reload-button"

export const metadata: Metadata = {
  title: {
    absolute: "Offline - Absensi PKL Fibernet",
  },
}

export default function OfflinePage() {
  return (
    <div className="bg-slate-50 antialiased min-h-screen flex items-center justify-center p-4">
      <div className="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden p-8 text-center border border-gray-100">
        <div className="mb-6 flex justify-center">
          <div className="h-20 w-20 rounded-full bg-slate-100 flex items-center justify-center">
            <svg
              className="w-10 h-10 text-slate-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
              <line
                x1="4"
                y1="4"
                x2="20"
                y2="20"
                strokeWidth={2}
                stroke="currentColor"
              />
            </svg>
          </div>
        </div>
        <h1 className="text-2xl font-bold text-gray-900 mb-2">
          Anda Sedang Offline
        </h1>
        <p className="text-slate-500 mb-8">
          Sepertinya perangkat Anda kehilangan koneksi internet. Aplikasi Absensi
          membutuhkan koneksi internet untuk mengirim data lokasi.
        </p>
        <ReloadButton />
      </div>
    </div>
  )
}
