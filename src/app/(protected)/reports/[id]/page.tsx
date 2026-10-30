import type { Metadata } from "next"
import Link from "next/link"
import { notFound, redirect } from "next/navigation"
import { ApprovalActionsClient } from "@/components/reports/approval-actions-client"
import { requireAdmin } from "@/lib/admin-helpers"
import {
  formatDateLongEn,
  formatDuration,
  witaTimeString,
} from "@/lib/time"

export const metadata: Metadata = {
  title: "Detail Presensi",
}

export default async function ReportDetailPage({
  params,
}: {
  params: Promise<{ id: string }>
}) {
  const admin = await requireAdmin()
  if (!admin.ok) {
    if (admin.status === 401) redirect("/login")
    redirect("/dashboard")
  }

  const { id } = await params
  const attendanceId = Number(id)
  if (!Number.isInteger(attendanceId) || attendanceId <= 0) {
    notFound()
  }

  const { data: attendance } = await admin.supabaseAdmin
    .from("attendances")
    .select(
      "*, students(student_code, school, profiles(name))"
    )
    .eq("id", attendanceId)
    .maybeSingle()

  if (!attendance) {
    notFound()
  }

  const studentObj = Array.isArray(attendance.students)
    ? attendance.students[0]
    : attendance.students
  const profileObj = Array.isArray(studentObj?.profiles)
    ? studentObj?.profiles[0]
    : studentObj?.profiles

  const statusColorClass =
    attendance.is_approved === false
      ? "text-amber-600"
      : attendance.status === "present"
        ? "text-emerald-600"
        : attendance.status === "late"
          ? "text-orange-600"
          : "text-red-600"

  const photoUrl = `/api/reports/${attendance.id}/photo`

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center gap-4">
        <Link
          href="/reports"
          className="h-10 w-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
        >
          <svg
            className="h-5 w-5 text-gray-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Detail Presensi</h1>
          <p className="text-sm text-gray-500">
            Informasi detail terkait presensi siswa.
          </p>
        </div>
      </div>

      {/* Informasi Siswa */}
      <div className="fibernet-card-blue">
        <div className="px-6 py-4 border-b border-gray-100">
          <h3 className="text-base font-semibold text-gray-900 flex items-center gap-2">
            <div className="h-7 w-7 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
              <svg
                className="w-3.5 h-3.5 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
            </div>
            Informasi Siswa
          </h3>
        </div>
        <div className="px-4 py-5 sm:p-0">
          <dl className="sm:divide-y sm:divide-gray-100">
            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">
                Nama Lengkap
              </dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                {profileObj?.name ?? "-"}
              </dd>
            </div>
            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">NIM / NISN</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {studentObj?.student_code ?? "-"}
              </dd>
            </div>
            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">
                Asal Sekolah
              </dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {studentObj?.school ?? "-"}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Data Presensi */}
      <div className="fibernet-card-purple">
        <div className="px-6 py-4 border-b border-gray-100">
          <h3 className="text-base font-semibold text-gray-900 flex items-center gap-2">
            <div className="h-7 w-7 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
              <svg
                className="w-3.5 h-3.5 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
              </svg>
            </div>
            Data Presensi
          </h3>
        </div>
        <div className="px-4 py-5 sm:p-0">
          <dl className="sm:divide-y sm:divide-gray-100">
            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Tanggal</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                {formatDateLongEn(attendance.attendance_date)}
              </dd>
            </div>

            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Status</dt>
              <dd
                className={`mt-1 text-sm sm:col-span-2 sm:mt-0 uppercase font-bold ${statusColorClass}`}
              >
                {attendance.is_approved === false
                  ? "PENDING"
                  : attendance.status}
              </dd>
            </div>

            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">
                Kategori Persetujuan
              </dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                {attendance.location_type === "outside" ? (
                  <span className="text-blue-600">
                    Di Luar Kantor (Kunjungan/Dinas)
                  </span>
                ) : (
                  <span>Di Dalam Kantor</span>
                )}

                {attendance.is_approved === false && (
                  <span className="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                    Pending Approval
                  </span>
                )}
                {attendance.is_approved === true && (
                  <span className="ml-2 inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                    Approved
                  </span>
                )}
              </dd>
            </div>

            {attendance.is_approved === false && (
              <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-amber-50/30">
                <dt className="text-sm font-medium text-gray-500 flex items-center">
                  Aksi Persetujuan
                </dt>
                <dd className="sm:col-span-2">
                  <ApprovalActionsClient attendanceId={attendance.id} />
                </dd>
              </div>
            )}

            {attendance.reason && (
              <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">
                  Keterangan / Alasan
                </dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 italic">
                  &ldquo;{attendance.reason}&rdquo;
                </dd>
              </div>
            )}

            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">
                Waktu Check-In
              </dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {attendance.check_in_at
                  ? witaTimeString(new Date(attendance.check_in_at))
                  : "-"}
              </dd>
            </div>

            {attendance.check_in_at && (
              <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">
                  Lokasi Check-In
                </dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 space-y-1">
                  <div>
                    Koordinat:{" "}
                    <a
                      href={`https://maps.google.com/?q=${attendance.check_in_latitude},${attendance.check_in_longitude}`}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center gap-1 text-blue-600 hover:underline font-medium"
                    >
                      {attendance.check_in_latitude},{" "}
                      {attendance.check_in_longitude}
                      <svg
                        className="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                      </svg>
                    </a>
                  </div>
                  <div>Akurasi GPS: {attendance.check_in_accuracy} m</div>
                  <div>
                    Jarak ke Kantor: {attendance.check_in_distance ?? "-"} m
                  </div>
                </dd>
              </div>
            )}

            {attendance.check_in_photo_path && (
              <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">
                  Foto Check-In
                </dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                  <a href={photoUrl} target="_blank" rel="noreferrer">
                    {/* Private cookie-authenticated route stream; next/image does not forward session cookies */}
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src={photoUrl}
                      alt="Foto Check-In"
                      className="mt-2 h-48 w-auto rounded-xl border border-gray-100 object-cover shadow-md hover:shadow-lg transition-shadow"
                    />
                  </a>
                </dd>
              </div>
            )}

            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">
                Waktu Check-Out
              </dt>
              <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {attendance.check_out_at
                  ? witaTimeString(new Date(attendance.check_out_at))
                  : "-"}
              </dd>
            </div>

            {attendance.check_out_at && (
              <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">
                  Lokasi Check-Out
                </dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 space-y-1">
                  <div>
                    Koordinat:{" "}
                    <a
                      href={`https://maps.google.com/?q=${attendance.check_out_latitude},${attendance.check_out_longitude}`}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center gap-1 text-blue-600 hover:underline font-medium"
                    >
                      {attendance.check_out_latitude},{" "}
                      {attendance.check_out_longitude}
                      <svg
                        className="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                      </svg>
                    </a>
                  </div>
                  <div>Akurasi GPS: {attendance.check_out_accuracy} m</div>
                  <div>
                    Jarak ke Kantor: {attendance.check_out_distance ?? "-"} m
                  </div>
                </dd>
              </div>
            )}

            <div className="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gradient-to-r from-blue-50/50 to-cyan-50/50">
              <dt className="text-sm font-semibold text-gray-900">
                Total Waktu Kerja
              </dt>
              <dd className="mt-1 text-sm font-bold text-gray-900 sm:col-span-2 sm:mt-0">
                {attendance.check_in_at && attendance.check_out_at ? (
                  formatDuration(
                    attendance.check_in_at,
                    attendance.check_out_at,
                    "long"
                  )
                ) : (
                  <span className="text-gray-400 font-normal">
                    Belum Check-Out
                  </span>
                )}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  )
}
