import { Suspense } from "react"
import type { Metadata } from "next"
import { redirect } from "next/navigation"
import {
  SettingsCardHeader,
  StorageSettingsClient,
} from "@/components/settings/settings-ui"
import { Skeleton } from "@/components/ui/skeleton"
import { requireAdmin } from "@/lib/admin-helpers"
import { getStorageStats } from "@/lib/storage-usage"

export const metadata: Metadata = {
  title: "Informasi Penyimpanan",
}

async function StorageContent() {
  const admin = await requireAdmin()
  if (!admin.ok) {
    if (admin.status === 401) redirect("/login")
    redirect("/dashboard")
  }

  const stats = await getStorageStats()
  return <StorageSettingsClient stats={stats} />
}

function StorageBodyFallback() {
  return (
    <div className="my-6 space-y-6">
      <div className="rounded-2xl border border-gray-100 bg-slate-50/70 p-5 space-y-4">
        <div className="flex items-center justify-between gap-3">
          <Skeleton className="h-6 w-48 rounded-full" />
          <div className="flex items-center gap-3">
            <Skeleton className="h-4 w-32 rounded" />
            <Skeleton className="h-8 w-20 rounded-lg" />
          </div>
        </div>
        <div className="space-y-2.5">
          <div className="flex items-baseline justify-between">
            <Skeleton className="h-8 w-44 rounded-lg" />
            <Skeleton className="h-4 w-12 rounded" />
          </div>
          <Skeleton className="h-3 w-full rounded-full" />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {Array.from({ length: 3 }).map((_, i) => (
          <div
            key={i}
            className="rounded-xl border border-gray-100 bg-white p-4 space-y-2"
          >
            <Skeleton className="h-3 w-32 rounded" />
            <Skeleton className="h-6 w-20 rounded" />
            <Skeleton className="h-3 w-36 rounded" />
          </div>
        ))}
      </div>

      <div className="rounded-xl border border-gray-100 bg-white p-4 flex items-center justify-between gap-4">
        <div className="space-y-2">
          <Skeleton className="h-4 w-40 rounded" />
          <Skeleton className="h-3 w-64 rounded" />
        </div>
        <Skeleton className="h-8 w-36 rounded-lg" />
      </div>
    </div>
  )
}

export default function StorageSettingsPage() {
  return (
    <SettingsCardHeader
      heading="Informasi Penyimpanan"
      subheading="Pantau pemakaian kuota foto presensi dan bersihkan file sementara yang tidak terpakai."
    >
      <Suspense fallback={<StorageBodyFallback />}>
        <StorageContent />
      </Suspense>
    </SettingsCardHeader>
  )
}
