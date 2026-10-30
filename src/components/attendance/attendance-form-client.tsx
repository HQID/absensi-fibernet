"use client"

import { useEffect, useRef, useState, useTransition, type ChangeEvent } from "react"
import { checkInAction, checkOutAction } from "@/actions/attendance"

export type TodayAttendanceSummary = {
  check_in_at: string | null
  check_in_time_24h: string | null
  check_out_at: string | null
  check_out_time_24h: string | null
  status: string
} | null

function checkClientIsLateWita(): boolean {
  const witaStr = new Intl.DateTimeFormat("en-GB", {
    timeZone: "Asia/Makassar",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  }).format(new Date())
  return witaStr > "08:15:00"
}

export function AttendanceFormClient({
  isStudentWithProfile,
  initialIsLate,
  attendanceToday,
}: {
  isStudentWithProfile: boolean
  initialIsLate: boolean
  attendanceToday: TodayAttendanceSummary
}) {
  const [isPending, startTransition] = useTransition()
  const [isLate, setIsLate] = useState(initialIsLate)

  const [coords, setCoords] = useState<{
    latitude: number
    longitude: number
    accuracy: number
    stage: "checkin" | "checkout"
  } | null>(null)
  const [locating, setLocating] = useState(false)

  const [locationType, setLocationType] = useState<"inside" | "outside">(
    "inside"
  )
  const [reason, setReason] = useState("")

  const [compressing, setCompressing] = useState(false)
  const [compressedFile, setCompressedFile] = useState<File | null>(null)
  const [photoPreview, setPhotoPreview] = useState<string | null>(null)
  const [photoSizeInfo, setPhotoSizeInfo] = useState("")
  const previewUrlRef = useRef<string | null>(null)

  const [errorMessage, setErrorMessage] = useState("")
  const [successMessage, setSuccessMessage] = useState("")

  useEffect(() => {
    const timer = setInterval(() => {
      setIsLate(checkClientIsLateWita())
    }, 15_000)
    return () => clearInterval(timer)
  }, [])

  useEffect(() => {
    return () => {
      if (previewUrlRef.current) {
        URL.revokeObjectURL(previewUrlRef.current)
      }
    }
  }, [])

  if (!isStudentWithProfile) {
    return (
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6">
        <div className="text-center py-8">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-50 mb-4 shadow-sm">
            <svg
              className="h-7 w-7 text-cyan-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <h3 className="text-lg font-semibold text-gray-900">
            Akses Terbatas
          </h3>
          <p className="text-sm text-gray-500 mt-1">
            Hanya peserta PKL yang dapat melakukan check-in dan check-out.
          </p>
        </div>
      </div>
    )
  }

  const currentStage: "checkin" | "checkout" | "done" =
    !attendanceToday || !attendanceToday.check_in_at
      ? "checkin"
      : !attendanceToday.check_out_at
        ? "checkout"
        : "done"

  const locationReady = coords !== null && coords.stage === currentStage

  function getLocation() {
    if (typeof navigator === "undefined" || !navigator.geolocation) {
      alert("Browser Anda tidak mendukung Geolocation.")
      return
    }

    setLocating(true)
    setErrorMessage("")

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocating(false)
        if (currentStage === "done") return
        setCoords({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          stage: currentStage,
        })
      },
      (error) => {
        setLocating(false)
        let msg = "Lokasi gagal diperoleh."
        if (error.code === 1) {
          msg =
            "Akses lokasi ditolak. Harap izinkan akses lokasi di browser Anda."
        }
        if (error.code === 2) {
          msg = "Lokasi tidak tersedia (GPS tidak aktif)."
        }
        if (error.code === 3) {
          msg = "Waktu permintaan lokasi habis."
        }
        alert(msg)
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    )
  }

  function handlePhotoSelect(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    if (!file) return

    setCompressing(true)
    setCompressedFile(null)
    if (previewUrlRef.current) {
      URL.revokeObjectURL(previewUrlRef.current)
      previewUrlRef.current = null
    }
    setPhotoPreview(null)
    setPhotoSizeInfo("")

    const reader = new FileReader()
    reader.onload = (e) => {
      const img = new window.Image()
      img.onload = () => {
        const MAX_WIDTH = 1280
        const MAX_HEIGHT = 1280
        let width = img.width
        let height = img.height

        if (width > MAX_WIDTH || height > MAX_HEIGHT) {
          if (width > height) {
            height = Math.round((height * MAX_WIDTH) / width)
            width = MAX_WIDTH
          } else {
            width = Math.round((width * MAX_HEIGHT) / height)
            height = MAX_HEIGHT
          }
        }

        const canvas = document.createElement("canvas")
        canvas.width = width
        canvas.height = height

        const ctx = canvas.getContext("2d")
        if (!ctx) {
          setCompressing(false)
          alert("Gagal mengompres gambar.")
          return
        }
        ctx.drawImage(img, 0, 0, width, height)

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              setCompressing(false)
              alert("Gagal mengompres gambar.")
              return
            }

            const origKB = Math.round(file.size / 1024)
            const compKB = Math.round(blob.size / 1024)
            const nextPreviewUrl = URL.createObjectURL(blob)
            previewUrlRef.current = nextPreviewUrl

            const nextFile = new File([blob], `capture_${Date.now()}.jpg`, {
              type: "image/jpeg",
              lastModified: Date.now(),
            })

            setPhotoSizeInfo(`Ukuran foto: ${compKB} KB (dari ${origKB} KB)`)
            setPhotoPreview(nextPreviewUrl)
            setCompressedFile(nextFile)
            setCompressing(false)
          },
          "image/jpeg",
          0.72
        )
      }
      img.onerror = () => {
        setCompressing(false)
        alert("Format file bukan gambar yang valid.")
      }
      img.src = String(e.target?.result ?? "")
    }
    reader.onerror = () => {
      setCompressing(false)
      alert("Gagal membaca berkas gambar.")
    }
    reader.readAsDataURL(file)
  }

  function handleCheckIn() {
    if (!coords || !compressedFile) return
    setErrorMessage("")
    setSuccessMessage("")

    startTransition(async () => {
      const fd = new FormData()
      fd.set("latitude", String(coords.latitude))
      fd.set("longitude", String(coords.longitude))
      fd.set("accuracy", String(coords.accuracy))
      fd.set("locationType", locationType)
      fd.set("reason", reason)
      fd.set("photo", compressedFile)

      const result = await checkInAction(fd)
      if (result.error) {
        setErrorMessage(result.error)
        return
      }
      if (result.success) {
        setSuccessMessage(result.success)
        setCompressedFile(null)
        if (previewUrlRef.current) {
          URL.revokeObjectURL(previewUrlRef.current)
          previewUrlRef.current = null
        }
        setPhotoPreview(null)
        setPhotoSizeInfo("")
        setCoords(null)
      }
    })
  }

  function handleCheckOut() {
    if (!coords) return
    setErrorMessage("")
    setSuccessMessage("")

    startTransition(async () => {
      const fd = new FormData()
      fd.set("latitude", String(coords.latitude))
      fd.set("longitude", String(coords.longitude))
      fd.set("accuracy", String(coords.accuracy))

      const result = await checkOutAction(fd)
      if (result.error) {
        setErrorMessage(result.error)
        return
      }
      if (result.success) {
        setSuccessMessage(result.success)
        setCoords(null)
      }
    })
  }

  const needsReason = locationType === "outside" || isLate

  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6">
      {errorMessage && (
        <div className="mb-4 rounded-xl bg-red-50 p-4 border border-red-100 flex items-start gap-3">
          <div className="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
            <svg
              className="h-3 w-3 text-red-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </div>
          <p className="text-sm text-red-700">{errorMessage}</p>
        </div>
      )}

      {successMessage && (
        <div className="mb-4 rounded-xl bg-emerald-50 p-4 border border-emerald-100 flex items-start gap-3">
          <div className="h-5 w-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
            <svg
              className="h-3 w-3 text-emerald-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <p className="text-sm text-emerald-700">{successMessage}</p>
        </div>
      )}

      <div className="text-center">
        {currentStage === "checkin" && (
          <>
            <div className="flex items-center justify-center gap-2 mb-6">
              <div className="h-8 w-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                <svg
                  className="w-4 h-4 text-cyan-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                  />
                </svg>
              </div>
              <h2 className="text-lg font-semibold text-gray-900">
                Check In Presensi
              </h2>
            </div>

            {!locationReady ? (
              <div className="mb-4">
                <button
                  type="button"
                  disabled={locating}
                  onClick={getLocation}
                  className="rounded-xl bg-cyan-600 hover:bg-cyan-700 disabled:opacity-60 text-white transition-colors px-6 py-3 text-sm font-semibold w-full sm:w-auto cursor-pointer"
                >
                  <span className="flex items-center justify-center gap-2">
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                      />
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                    </svg>
                    {locating ? "Mendapatkan lokasi..." : "Dapatkan Lokasi Saya"}
                  </span>
                </button>
              </div>
            ) : (
              <div className="mb-4 text-left">
                <div className="bg-cyan-50 border border-cyan-100 text-cyan-800 text-sm p-3 rounded-xl mb-4 flex items-center">
                  <div className="h-6 w-6 rounded-full bg-cyan-100 flex items-center justify-center mr-2 shrink-0">
                    <svg
                      className="h-3.5 w-3.5 text-cyan-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>
                  Lokasi terverifikasi (Akurasi:
                  <span className="mx-1 font-semibold">
                    {Math.round(coords.accuracy)}
                  </span>
                  m)
                </div>

                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Jenis Lokasi Presensi
                  </label>
                  <select
                    value={locationType}
                    onChange={(e) =>
                      setLocationType(e.target.value as "inside" | "outside")
                    }
                    className="mt-1 block w-full rounded-xl border border-gray-200 bg-white py-2 pl-3 pr-10 text-base focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 sm:text-sm"
                  >
                    <option value="inside">Di Dalam Kantor</option>
                    <option value="outside">
                      Di Luar Kantor (Kunjungan/Dinas)
                    </option>
                  </select>
                </div>

                {needsReason && (
                  <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Keterangan / Alasan <span className="text-red-500">*</span>
                    </label>
                    <p className="text-xs text-gray-500 mb-2">
                      Wajib diisi karena Anda memilih Luar Kantor atau Anda
                      datang terlambat.
                    </p>
                    <textarea
                      value={reason}
                      onChange={(e) => setReason(e.target.value)}
                      rows={2}
                      className="mt-1 block w-full rounded-xl border border-gray-200 px-3 py-2 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 sm:text-sm"
                      placeholder="Masukkan alasan Anda..."
                    />
                  </div>
                )}

                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Foto Bukti Kehadiran
                  </label>
                  <input
                    type="file"
                    onChange={handlePhotoSelect}
                    accept="image/*"
                    capture="user"
                    className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-colors"
                  />
                </div>

                {compressing && (
                  <div className="text-sm text-cyan-600 mb-4 flex items-center justify-center gap-2">
                    <svg
                      className="animate-spin h-4 w-4"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        className="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        strokeWidth={4}
                      />
                      <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                      />
                    </svg>
                    Sedang memproses &amp; mengompres foto...
                  </div>
                )}

                {photoPreview && !compressing && (
                  <div className="mb-4 flex flex-col items-center">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src={photoPreview}
                      className="h-44 w-auto rounded-xl object-cover shadow-sm border border-gray-200"
                      alt="Preview Presensi"
                    />
                    <span className="text-xs text-gray-500 mt-2 font-medium">
                      {photoSizeInfo}
                    </span>
                  </div>
                )}

                {compressedFile && !compressing && (
                  <button
                    type="button"
                    disabled={isPending}
                    onClick={handleCheckIn}
                    className="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors px-5 py-3 text-sm font-semibold disabled:opacity-50 flex justify-center items-center gap-2 cursor-pointer"
                  >
                    {!isPending ? (
                      <span>
                        <svg
                          className="w-5 h-5 inline mr-1"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M5 13l4 4L19 7"
                          />
                        </svg>
                        Submit Check In
                      </span>
                    ) : (
                      <span className="flex items-center gap-2">
                        <svg
                          className="animate-spin h-4 w-4"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth={4}
                          />
                          <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                          />
                        </svg>
                        Menyimpan...
                      </span>
                    )}
                  </button>
                )}
              </div>
            )}
          </>
        )}

        {currentStage === "checkout" && attendanceToday && (
          <>
            <div className="flex items-center justify-center gap-2 mb-6">
              <div className="h-8 w-8 rounded-lg bg-orange-50 flex items-center justify-center">
                <svg
                  className="w-4 h-4 text-orange-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                  />
                </svg>
              </div>
              <h2 className="text-lg font-semibold text-gray-900">
                Check Out Presensi
              </h2>
            </div>

            <div className="bg-gray-50 border border-gray-100 p-4 rounded-xl mb-6 text-left text-sm text-gray-700 space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-500">Waktu Check-In</span>
                <span className="font-semibold">
                  {attendanceToday.check_in_time_24h ?? "-"}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Status</span>
                <span
                  className={`font-semibold uppercase ${
                    attendanceToday.status === "present"
                      ? "text-emerald-600"
                      : "text-orange-600"
                  }`}
                >
                  {attendanceToday.status}
                </span>
              </div>
            </div>

            {!locationReady ? (
              <div className="mb-4">
                <button
                  type="button"
                  disabled={locating}
                  onClick={getLocation}
                  className="rounded-xl bg-cyan-600 hover:bg-cyan-700 disabled:opacity-60 text-white transition-colors px-6 py-3 text-sm font-semibold w-full sm:w-auto cursor-pointer"
                >
                  <span className="flex items-center justify-center gap-2">
                    <svg
                      className="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                      />
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                    </svg>
                    {locating ? "Mendapatkan lokasi..." : "Dapatkan Lokasi Saya"}
                  </span>
                </button>
              </div>
            ) : (
              <div className="mb-4">
                <div className="bg-cyan-50 border border-cyan-100 text-cyan-800 text-sm p-3 rounded-xl mb-4 text-left flex items-center">
                  <div className="h-6 w-6 rounded-full bg-cyan-100 flex items-center justify-center mr-2 shrink-0">
                    <svg
                      className="h-3.5 w-3.5 text-cyan-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>
                  Lokasi terverifikasi (Akurasi:
                  <span className="mx-1 font-semibold">
                    {Math.round(coords.accuracy)}
                  </span>
                  m)
                </div>

                <button
                  type="button"
                  disabled={isPending}
                  onClick={handleCheckOut}
                  className="w-full rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50 transition-colors flex justify-center items-center gap-2 shadow-sm cursor-pointer"
                >
                  {!isPending ? (
                    <span>
                      <svg
                        className="w-5 h-5 inline mr-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                        />
                      </svg>
                      Submit Check Out
                    </span>
                  ) : (
                    <span className="flex items-center gap-2">
                      <svg
                        className="animate-spin h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          className="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          strokeWidth={4}
                        />
                        <path
                          className="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                      </svg>
                      Menyimpan...
                    </span>
                  )}
                </button>
              </div>
            )}
          </>
        )}

        {currentStage === "done" && attendanceToday && (
          <div className="py-8">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 mb-4 shadow-sm border border-emerald-100">
              <svg
                className="h-8 w-8 text-emerald-600"
                fill="none"
                viewBox="0 0 24 24"
                strokeWidth={2}
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M4.5 12.75l6 6 9-13.5"
                />
              </svg>
            </div>
            <h2 className="text-xl font-bold text-gray-900">
              Presensi Selesai
            </h2>
            <p className="text-sm text-gray-500 mt-2">
              Anda telah menyelesaikan presensi untuk hari ini.
            </p>

            <div className="mt-6 bg-gray-50 p-5 rounded-xl text-left text-sm text-gray-700 border border-gray-100 inline-block w-full max-w-sm space-y-3">
              <div className="flex justify-between items-center border-b border-gray-100 pb-2">
                <span className="text-gray-500">Check In</span>
                <span className="font-semibold text-emerald-600">
                  {attendanceToday.check_in_time_24h ?? "-"}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-500">Check Out</span>
                <span className="font-semibold text-orange-600">
                  {attendanceToday.check_out_time_24h ?? "-"}
                </span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
