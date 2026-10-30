"use client"

import { useRef, useState, useTransition, type ReactNode } from "react"
import Link from "next/link"
import { usePathname, useRouter } from "next/navigation"
import { toast } from "sonner"
import {
  cleanupStorageFilesAction,
  deleteOwnAccountAction,
  refreshStorageStatsAction,
  updateGeofenceAction,
  updatePasswordAction,
  updateProfileAction,
} from "@/actions/settings"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Modal } from "@/components/ui/modal"
import { Switch } from "@/components/ui/switch"
import { formatBytes } from "@/lib/time"

export function SettingsNavClient({ isAdmin }: { isAdmin: boolean }) {
  const pathname = usePathname()

  const items = [
    { href: "/settings/profile", label: "Profil" },
    { href: "/settings/security", label: "Keamanan" },
    ...(isAdmin
      ? [
          { href: "/settings/geofence", label: "Lokasi Kantor" },
          { href: "/settings/storage", label: "Penyimpanan" },
        ]
      : []),
  ]

  return (
    <nav aria-label="Pengaturan" className="flex flex-col space-y-1">
      {items.map((item) => {
        const active = pathname === item.href
        return (
          <Link
            key={item.href}
            href={item.href}
            className={`rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
              active
                ? "bg-cyan-50 text-[#0891b2]"
                : "text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900"
            }`}
          >
            {item.label}
          </Link>
        )
      })}
    </nav>
  )
}

export function SettingsCardHeader({
  heading,
  subheading,
  children,
}: {
  heading: string
  subheading: string
  children: ReactNode
}) {
  return (
    <>
      <div className="flex items-center gap-3 mb-1">
        <div className="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
          <svg
            className="w-4 h-4 text-white"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
            />
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
        </div>
        <h2 className="text-lg font-semibold text-gray-900">{heading}</h2>
      </div>
      <p className="mt-1 text-sm text-gray-500 ml-11">{subheading}</p>

      <div className="mt-6 w-full max-w-2xl">{children}</div>
    </>
  )
}

export function ProfileSettingsForm({
  initialName,
  email,
  isStudent,
  initialStudentCode,
  initialSchool,
  initialMajor,
}: {
  initialName: string
  email: string
  isStudent: boolean
  initialStudentCode: string
  initialSchool: string
  initialMajor: string
}) {
  const router = useRouter()
  const [isSaving, startSave] = useTransition()
  const [isDeleting, startDelete] = useTransition()
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [deleteModalOpen, setDeleteModalOpen] = useState(false)
  const [deletePassword, setDeletePassword] = useState("")
  const [deleteError, setDeleteError] = useState<string | undefined>(undefined)

  function handleProfileSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setFieldErrors({})
    const formData = new FormData(e.currentTarget)

    startSave(async () => {
      const res = await updateProfileAction(formData)
      if (res.fieldErrors) {
        setFieldErrors(res.fieldErrors)
        return
      }
      if (res.error) {
        toast.error(res.error)
        return
      }
      toast.success(res.success || "Profil berhasil diperbarui.")
      router.refresh()
    })
  }

  function handleDeleteSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setDeleteError(undefined)
    const formData = new FormData()
    formData.set("password", deletePassword)

    startDelete(async () => {
      const res = await deleteOwnAccountAction(formData)
      if (res.fieldErrors?.password) {
        setDeleteError(res.fieldErrors.password)
        return
      }
      if (res.error) {
        setDeleteError(res.error)
        return
      }
      if (res.redirectTo) {
        router.push(res.redirectTo)
        router.refresh()
      }
    })
  }

  return (
    <>
      <form onSubmit={handleProfileSubmit} className="my-6 w-full space-y-6">
        <Input
          name="name"
          label="Nama Lengkap"
          type="text"
          defaultValue={initialName}
          error={fieldErrors.name}
          required
          autoFocus
          autoComplete="name"
        />

        {isStudent && (
          <>
            <Input
              name="student_code"
              label="NIM / NISN"
              type="text"
              defaultValue={initialStudentCode}
              error={fieldErrors.student_code}
              required
            />
            <Input
              name="school"
              label="Asal Sekolah / Kampus"
              type="text"
              defaultValue={initialSchool}
              error={fieldErrors.school}
              required
            />
            <Input
              name="major"
              label="Jurusan"
              type="text"
              defaultValue={initialMajor}
              error={fieldErrors.major}
              required
            />
          </>
        )}

        <div>
          <Input
            name="email"
            label="Alamat Email"
            type="email"
            defaultValue={email}
            autoComplete="email"
            disabled
          />
        </div>

        <div className="flex items-center gap-4">
          <div className="flex items-center justify-end">
            <Button
              variant="primary"
              type="submit"
              loading={isSaving}
              className="w-full"
              data-test="update-profile-button"
            >
              Simpan Perubahan
            </Button>
          </div>
        </div>
      </form>

      <section className="mt-10 space-y-6 border-t border-gray-100 pt-6">
        <div className="relative mb-5">
          <h3 className="text-base font-semibold text-zinc-900">
            Delete account
          </h3>
          <p className="mt-1 text-sm text-zinc-500">
            Delete your account and all of its resources
          </p>
        </div>

        <Button
          type="button"
          variant="danger"
          data-test="delete-user-button"
          onClick={() => {
            setDeletePassword("")
            setDeleteError(undefined)
            setDeleteModalOpen(true)
          }}
        >
          Delete account
        </Button>

        <Modal
          open={deleteModalOpen}
          onClose={() => {
            if (!isDeleting) setDeleteModalOpen(false)
          }}
          title="Are you sure you want to delete your account?"
          description="Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account."
          maxWidthClass="max-w-lg"
        >
          <form onSubmit={handleDeleteSubmit} className="space-y-6">
            <Input
              name="password"
              label="Password"
              type="password"
              value={deletePassword}
              onChange={(e) => setDeletePassword(e.target.value)}
              error={deleteError}
              viewable
            />

            <div className="flex justify-end space-x-2">
              <Button
                type="button"
                variant="filled"
                onClick={() => setDeleteModalOpen(false)}
              >
                Cancel
              </Button>
              <Button
                variant="danger"
                type="submit"
                loading={isDeleting}
                data-test="confirm-delete-user-button"
              >
                Delete account
              </Button>
            </div>
          </form>
        </Modal>
      </section>
    </>
  )
}

export function SecuritySettingsForm() {
  const formRef = useRef<HTMLFormElement>(null)
  const [isPending, startTransition] = useTransition()
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})

  function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setFieldErrors({})
    const formData = new FormData(e.currentTarget)

    startTransition(async () => {
      const res = await updatePasswordAction(formData)
      formRef.current?.reset()
      if (res.fieldErrors) {
        setFieldErrors(res.fieldErrors)
        return
      }
      if (res.error) {
        toast.error(res.error)
        return
      }
      toast.success(res.success || "Kata sandi berhasil diperbarui.")
    })
  }

  return (
    <form ref={formRef} onSubmit={handleSubmit} className="mt-6 space-y-6">
      <Input
        name="current_password"
        label="Kata Sandi Saat Ini"
        type="password"
        required
        autoComplete="current-password"
        error={fieldErrors.current_password}
        viewable
      />
      <Input
        name="password"
        label="Kata Sandi Baru"
        type="password"
        required
        autoComplete="new-password"
        error={fieldErrors.password}
        viewable
      />
      <Input
        name="password_confirmation"
        label="Konfirmasi Kata Sandi"
        type="password"
        required
        autoComplete="new-password"
        error={fieldErrors.password_confirmation}
        viewable
      />

      <div className="flex items-center gap-4">
        <Button
          variant="primary"
          type="submit"
          loading={isPending}
          data-test="update-password-button"
        >
          Simpan Perubahan
        </Button>
      </div>
    </form>
  )
}

export function GeofenceSettingsForm({
  locationId,
  initialName,
  initialLatitude,
  initialLongitude,
  initialRadiusMeters,
  initialMaxAccuracyMeters,
  initialActive,
}: {
  locationId: number | null
  initialName: string
  initialLatitude: string
  initialLongitude: string
  initialRadiusMeters: number
  initialMaxAccuracyMeters: number
  initialActive: boolean
}) {
  const router = useRouter()
  const [isPending, startTransition] = useTransition()
  const [active, setActive] = useState(initialActive)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})

  function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setFieldErrors({})
    const formData = new FormData(e.currentTarget)
    formData.set("active", active ? "true" : "false")

    startTransition(async () => {
      const res = await updateGeofenceAction(formData)
      if (res.fieldErrors) {
        setFieldErrors(res.fieldErrors)
        return
      }
      if (res.error) {
        toast.error(res.error)
        return
      }
      toast.success(res.heading || "Tersimpan", {
        description:
          res.success || "Pengaturan lokasi kantor berhasil disimpan.",
      })
      router.refresh()
    })
  }

  return (
    <form onSubmit={handleSubmit} className="my-6 space-y-6">
      <input
        type="hidden"
        name="location_id"
        value={locationId ? String(locationId) : ""}
      />

      <Input
        name="name"
        label="Nama Kantor"
        defaultValue={initialName}
        error={fieldErrors.name}
        required
      />

      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <Input
          name="latitude"
          label="Latitude"
          type="number"
          step="any"
          defaultValue={initialLatitude}
          error={fieldErrors.latitude}
          required
        />
        <Input
          name="longitude"
          label="Longitude"
          type="number"
          step="any"
          defaultValue={initialLongitude}
          error={fieldErrors.longitude}
          required
        />
      </div>
      <p className="text-xs text-zinc-500 mt-[-1rem] mb-4">
        Contoh: Latitude -0.897587, Longitude 119.873215
      </p>

      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <Input
          name="radius_meters"
          label="Radius (Meter)"
          type="number"
          defaultValue={String(initialRadiusMeters)}
          error={fieldErrors.radius_meters}
          required
        />
        <Input
          name="max_accuracy_meters"
          label="Batas Akurasi GPS (Meter)"
          type="number"
          defaultValue={String(initialMaxAccuracyMeters)}
          error={fieldErrors.max_accuracy_meters}
          required
        />
      </div>

      <Switch
        name="active"
        label="Status Aktif"
        description="Tandai apakah titik lokasi ini sedang aktif digunakan untuk absensi atau tidak."
        checked={active}
        onChange={setActive}
      />

      <div className="flex items-center gap-4">
        <div className="flex items-center justify-end">
          <Button
            variant="primary"
            type="submit"
            loading={isPending}
            className="w-full"
          >
            Simpan Pengaturan
          </Button>
        </div>
      </div>
    </form>
  )
}

export type StorageStatsClientProps = {
  disk: string
  driver: string
  bucket: string | null
  total_bytes: number
  quota_mb: number
  quota_bytes: number
  percent_used: number
  active_count: number
  active_bytes: number
  temp_count: number
  temp_bytes: number
  orphan_count: number
  orphan_bytes: number
  cleanable_count: number
  cleanable_bytes: number
  checked_at: string
  error: string | null
}

export function StorageSettingsClient({
  stats,
}: {
  stats: StorageStatsClientProps
}) {
  const router = useRouter()
  const [isRefreshing, startRefresh] = useTransition()
  const [isCleaning, startCleanup] = useTransition()
  const [cleanupModalOpen, setCleanupModalOpen] = useState(false)

  const percentBar = Math.min(100, Math.max(0, stats.percent_used))
  const barColor =
    stats.percent_used >= 90
      ? "bg-rose-600"
      : stats.percent_used >= 75
        ? "bg-amber-500"
        : "bg-gradient-to-r from-blue-500 to-cyan-500"

  function handleRefresh() {
    startRefresh(async () => {
      const res = await refreshStorageStatsAction()
      if (res.error) {
        toast.error(res.error)
        return
      }
      toast.success(res.heading || "Diperbarui", {
        description:
          res.success || "Data kapasitas penyimpanan berhasil diperbarui.",
      })
      router.refresh()
    })
  }

  function handleCleanup() {
    startCleanup(async () => {
      const res = await cleanupStorageFilesAction()
      if (res.error) {
        toast.error(res.error)
        return
      }
      setCleanupModalOpen(false)
      toast.success(res.heading || "Pembersihan Selesai", {
        description:
          res.success || "File sampah berhasil dihapus dari penyimpanan.",
      })
      router.refresh()
    })
  }

  return (
    <>
      <div className="my-6 space-y-6">
        {stats.error && (
          <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p className="font-semibold">Tidak dapat memuat rincian bucket</p>
            <p className="mt-1 text-xs text-rose-700">{stats.error}</p>
          </div>
        )}

        {/* Main Quota Overview Card */}
        <div className="rounded-2xl border border-gray-100 bg-slate-50/70 p-5 space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-2">
              {stats.driver === "s3" ? (
                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                  Cloud S3 / Supabase ({stats.bucket ?? stats.disk})
                </span>
              ) : (
                <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-500/20">
                  <span className="h-1.5 w-1.5 rounded-full bg-slate-500" />
                  Penyimpanan Lokal ({stats.disk})
                </span>
              )}
            </div>

            <div className="flex items-center gap-3">
              <span className="text-xs text-gray-500">
                Dicek: {stats.checked_at}
              </span>
              <Button
                type="button"
                size="sm"
                variant="filled"
                loading={isRefreshing}
                onClick={handleRefresh}
                data-test="refresh-storage-button"
              >
                Segarkan
              </Button>
            </div>
          </div>

          <div>
            <div className="flex items-baseline justify-between gap-2">
              <div>
                <span className="text-2xl font-bold text-gray-900">
                  {formatBytes(stats.total_bytes)}
                </span>{" "}
                <span className="text-sm text-gray-500">
                  dari kuota {formatBytes(stats.quota_bytes)}
                </span>
              </div>
              <span className="text-sm font-semibold text-gray-700">
                {stats.percent_used.toFixed(2)}%
              </span>
            </div>

            <div className="mt-2.5 h-3 w-full overflow-hidden rounded-full bg-gray-200/80">
              <div
                className={`h-full rounded-full transition-all duration-300 ${barColor}`}
                style={{ width: `${percentBar}%` }}
              />
            </div>
          </div>
        </div>

        {/* 3 Category Breakdown */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-xs">
            <p className="text-xs font-medium text-gray-500">
              Foto Presensi Aktif
            </p>
            <p className="mt-1.5 text-lg font-bold text-emerald-700">
              {formatBytes(stats.active_bytes)}
            </p>
            <p className="mt-0.5 text-xs text-gray-500">
              {stats.active_count} file tercatat di database
            </p>
          </div>

          <div
            className={`rounded-xl border ${
              stats.temp_count > 0
                ? "border-amber-200 bg-amber-50/40"
                : "border-gray-100 bg-white"
            } p-4 shadow-xs`}
          >
            <p className="text-xs font-medium text-gray-500">
              File Sementara (livewire-tmp)
            </p>
            <p
              className={`mt-1.5 text-lg font-bold ${
                stats.temp_count > 0 ? "text-amber-700" : "text-gray-900"
              }`}
            >
              {formatBytes(stats.temp_bytes)}
            </p>
            <p className="mt-0.5 text-xs text-gray-500">
              {stats.temp_count} file duplikat sementara
            </p>
          </div>

          <div
            className={`rounded-xl border ${
              stats.orphan_count > 0
                ? "border-rose-200 bg-rose-50/40"
                : "border-gray-100 bg-white"
            } p-4 shadow-xs`}
          >
            <p className="text-xs font-medium text-gray-500">
              Foto Tanpa Data (Orphan)
            </p>
            <p
              className={`mt-1.5 text-lg font-bold ${
                stats.orphan_count > 0 ? "text-rose-700" : "text-gray-900"
              }`}
            >
              {formatBytes(stats.orphan_bytes)}
            </p>
            <p className="mt-0.5 text-xs text-gray-500">
              {stats.orphan_count} file tanpa riwayat presensi
            </p>
          </div>
        </div>

        {/* Cleanup Action Section */}
        <div className="rounded-xl border border-gray-100 bg-white p-4 sm:flex sm:items-center sm:justify-between gap-4">
          <div className="space-y-1">
            <h3 className="text-sm font-semibold text-gray-900">
              Pembersih File Sampah
            </h3>
            {stats.cleanable_count > 0 ? (
              <p className="text-xs text-amber-700 font-medium">
                Ditemukan {stats.cleanable_count} file tak terpakai (
                {formatBytes(stats.cleanable_bytes)}) yang aman dibersihkan.
              </p>
            ) : stats.temp_count + stats.orphan_count > 0 ? (
              <p className="text-xs text-gray-500">
                Terdapat {stats.temp_count + stats.orphan_count} file baru (&lt;
                15 menit) yang dilindungi sementara agar tidak mengganggu proses
                upload aktif.
              </p>
            ) : (
              <p className="text-xs text-gray-500">
                Penyimpanan bersih. Tidak ada sisa file sementara maupun foto
                yatim.
              </p>
            )}
          </div>

          <div className="mt-3 sm:mt-0 shrink-0">
            {stats.cleanable_count > 0 ? (
              <Button
                type="button"
                variant="danger"
                size="sm"
                onClick={() => setCleanupModalOpen(true)}
                data-test="open-cleanup-modal-button"
              >
                Bersihkan File Sampah
              </Button>
            ) : (
              <Button
                type="button"
                variant="filled"
                size="sm"
                disabled
                data-test="open-cleanup-modal-button"
              >
                Bersihkan File Sampah
              </Button>
            )}
          </div>
        </div>
      </div>

      <Modal
        open={cleanupModalOpen}
        onClose={() => {
          if (!isCleaning) setCleanupModalOpen(false)
        }}
        maxWidthClass="max-w-[26rem]"
      >
        <div className="space-y-4">
          <div>
            <h3 className="text-lg font-bold text-zinc-900">
              Bersihkan File Sampah?
            </h3>
            <p className="mt-1 text-sm text-zinc-500">
              Sistem akan menghapus{" "}
              <strong>{stats.cleanable_count} file</strong> tak terpakai sebesar{" "}
              <strong>{formatBytes(stats.cleanable_bytes)}</strong> dari folder{" "}
              <code className="rounded bg-zinc-100 px-1 py-0.5 text-xs">
                livewire-tmp
              </code>{" "}
              dan file yatim yang berusia lebih dari 15 menit.
            </p>
          </div>

          <div className="rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-xs text-emerald-800">
            Seluruh <strong>{stats.active_count} foto presensi aktif</strong>{" "}
            peserta PKL yang tercatat di database dijamin aman dan tidak akan
            tersentuh.
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <Button
              type="button"
              variant="ghost"
              onClick={() => setCleanupModalOpen(false)}
            >
              Batal
            </Button>
            <Button
              type="button"
              variant="danger"
              loading={isCleaning}
              onClick={handleCleanup}
              data-test="confirm-cleanup-storage-button"
            >
              Ya, Hapus File Sampah
            </Button>
          </div>
        </div>
      </Modal>
    </>
  )
}
