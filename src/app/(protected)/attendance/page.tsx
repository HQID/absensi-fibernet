import { Suspense } from "react"
import type { Metadata } from "next"
import { redirect } from "next/navigation"
import { AttendanceFormClient } from "@/components/attendance/attendance-form-client"
import { AttendanceContentSkeleton } from "@/components/ui/page-skeletons"
import { Pagination } from "@/components/ui/pagination"
import { createClient } from "@/lib/supabase/server"
import {
  formatDateShort,
  formatIndonesianWeekday,
  formatTime24h,
  isAfterLateCutoff,
  todayWitaDateString,
} from "@/lib/time"

export const metadata: Metadata = {
  title: "Presensi",
}

const PER_PAGE = 5

async function AttendanceContent({ currentPage }: { currentPage: number }) {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    redirect("/login")
  }

  const [{ data: profile }, { data: student }] = await Promise.all([
    supabase.from("profiles").select("role").eq("id", userId).maybeSingle(),
    supabase.from("students").select("id").eq("user_id", userId).maybeSingle(),
  ])

  const isStudentWithProfile = Boolean(
    profile?.role === "student" && student?.id
  )

  const now = new Date()
  const today = todayWitaDateString(now)
  const from = (currentPage - 1) * PER_PAGE
  const to = from + PER_PAGE - 1

  const [{ data: attendanceToday }, { data: historyRows, count }] =
    isStudentWithProfile && student
      ? await Promise.all([
          supabase
            .from("attendances")
            .select("check_in_at, check_out_at, status")
            .eq("student_id", student.id)
            .eq("attendance_date", today)
            .maybeSingle(),
          supabase
            .from("attendances")
            .select(
              "id, attendance_date, check_in_at, check_out_at, status, is_approved",
              { count: "exact" }
            )
            .eq("student_id", student.id)
            .order("attendance_date", { ascending: false })
            .range(from, to),
        ])
      : [{ data: null }, { data: [], count: 0 }]

  const history = historyRows ?? []
  const totalCount = count ?? 0

  return (
    <>
      <AttendanceFormClient
        isStudentWithProfile={isStudentWithProfile}
        initialIsLate={isAfterLateCutoff(now)}
        attendanceToday={
          attendanceToday
            ? {
                check_in_at: attendanceToday.check_in_at,
                check_in_time_24h: attendanceToday.check_in_at
                  ? formatTime24h(attendanceToday.check_in_at)
                  : null,
                check_out_at: attendanceToday.check_out_at,
                check_out_time_24h: attendanceToday.check_out_at
                  ? formatTime24h(attendanceToday.check_out_at)
                  : null,
                status: attendanceToday.status,
              }
            : null
        }
      />

      {isStudentWithProfile && (
        <div className="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div className="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex items-center gap-3">
            <div className="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
              <svg
                className="w-4 h-4 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <h3 className="text-lg font-semibold text-gray-900">
              Riwayat Presensi Saya
            </h3>
          </div>

          <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
              <thead className="fibernet-table-header">
                <tr>
                  <th
                    scope="col"
                    className="px-4 py-3 sm:px-6 font-semibold text-gray-900"
                  >
                    Tanggal
                  </th>
                  <th
                    scope="col"
                    className="px-4 py-3 sm:px-6 font-semibold text-gray-900"
                  >
                    Check In
                  </th>
                  <th
                    scope="col"
                    className="px-4 py-3 sm:px-6 font-semibold text-gray-900"
                  >
                    Check Out
                  </th>
                  <th
                    scope="col"
                    className="px-4 py-3 sm:px-6 font-semibold text-gray-900"
                  >
                    Status
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {history.length > 0 ? (
                  history.map((record) => (
                    <tr
                      key={record.id}
                      className="hover:bg-gray-50/50 transition-colors"
                    >
                      <td className="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                        <div className="font-medium text-gray-900">
                          {formatDateShort(record.attendance_date)}
                        </div>
                        <div className="text-xs text-gray-400">
                          {formatIndonesianWeekday(record.attendance_date)}
                        </div>
                      </td>
                      <td className="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                        {record.check_in_at ? (
                          <div className="text-gray-900 font-medium">
                            {formatTime24h(record.check_in_at)}
                          </div>
                        ) : (
                          <span className="text-gray-400">-</span>
                        )}
                      </td>
                      <td className="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                        {record.check_out_at ? (
                          <div className="text-gray-900 font-medium">
                            {formatTime24h(record.check_out_at)}
                          </div>
                        ) : (
                          <span className="text-gray-400">-</span>
                        )}
                      </td>
                      <td className="px-4 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                        {record.is_approved === false ? (
                          <span className="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-600 uppercase">
                            PENDING
                          </span>
                        ) : record.status === "present" ? (
                          <span className="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">
                            HADIR
                          </span>
                        ) : record.status === "late" ? (
                          <span className="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 uppercase">
                            TERLAMBAT
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">
                            ABSEN
                          </span>
                        )}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={4} className="px-6 py-16 text-center">
                      <div className="flex flex-col items-center">
                        <div className="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                          <svg
                            className="w-6 h-6 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                          </svg>
                        </div>
                        <p className="text-sm text-gray-500">
                          Anda belum memiliki riwayat presensi.
                        </p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {totalCount > PER_PAGE && (
            <div className="border-t border-gray-100 px-6 py-4">
              <Pagination
                basePath="/attendance"
                currentPage={currentPage}
                perPage={PER_PAGE}
                totalCount={totalCount}
              />
            </div>
          )}
        </div>
      )}
    </>
  )
}

export default async function AttendancePage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>
}) {
  const params = await searchParams
  const rawPage = Number(params.page ?? "1")
  const currentPage =
    Number.isFinite(rawPage) && rawPage >= 1 ? Math.floor(rawPage) : 1

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="h-10 w-10 rounded-xl bg-cyan-50 flex items-center justify-center">
            <svg
              className="w-5 h-5 text-cyan-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Presensi</h1>
            <p className="text-sm text-gray-500">
              Kelola dan lakukan presensi peserta PKL.
            </p>
          </div>
        </div>
      </div>

      <Suspense key={currentPage} fallback={<AttendanceContentSkeleton />}>
        <AttendanceContent currentPage={currentPage} />
      </Suspense>
    </div>
  )
}
