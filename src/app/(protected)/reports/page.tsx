import { Suspense } from "react"
import type { Metadata } from "next"
import Form from "next/form"
import Link from "next/link"
import { redirect } from "next/navigation"
import { ReportsTableSkeleton } from "@/components/ui/page-skeletons"
import { Pagination } from "@/components/ui/pagination"
import { Skeleton } from "@/components/ui/skeleton"
import { requireAdmin } from "@/lib/admin-helpers"
import { syncAbsentees } from "@/lib/sync-absentees"
import {
  currentMonthRangeWita,
  formatDateSlash,
  formatDuration,
  formatTime12h,
} from "@/lib/time"

export const metadata: Metadata = {
  title: "Laporan Presensi",
}

const PER_PAGE = 15

async function ReportsFilterAndTable({
  startDate,
  endDate,
  rawSchool,
  rawStatus,
  rawStudentId,
  currentPage,
}: {
  startDate: string
  endDate: string
  rawSchool: string
  rawStatus: string
  rawStudentId: string
  currentPage: number
}) {
  const admin = await requireAdmin()
  if (!admin.ok) {
    if (admin.status === 401) redirect("/login")
    redirect("/dashboard")
  }

  await syncAbsentees(startDate, endDate)

  const { data: allStudents } = await admin.supabaseAdmin
    .from("students")
    .select("id, school")
    .order("school", { ascending: true })

  const seenSchools = new Set<string>()
  const schools: string[] = []
  const matchingSchoolStudentIds: number[] = []
  const normalizedSchoolFilter = rawSchool.toLowerCase()

  for (const s of allStudents ?? []) {
    const trimmed = (s.school ?? "").trim()
    if (!trimmed) continue
    const lower = trimmed.toLowerCase()
    if (!seenSchools.has(lower)) {
      seenSchools.add(lower)
      schools.push(trimmed)
    }
    if (normalizedSchoolFilter && lower === normalizedSchoolFilter) {
      matchingSchoolStudentIds.push(s.id)
    }
  }

  type ReportRow = {
    id: number
    attendance_date: string
    check_in_at: string | null
    check_out_at: string | null
    location_type: string
    status: string
    students:
      | {
          student_code: string
          school: string
          profiles: { name: string } | { name: string }[] | null
        }
      | {
          student_code: string
          school: string
          profiles: { name: string } | { name: string }[] | null
        }[]
      | null
  }

  let rows: ReportRow[] = []
  let totalCount = 0

  const schoolHasNoMatches =
    Boolean(normalizedSchoolFilter) && matchingSchoolStudentIds.length === 0

  if (!schoolHasNoMatches) {
    let query = admin.supabaseAdmin
      .from("attendances")
      .select(
        "id, attendance_date, check_in_at, check_out_at, location_type, status, students!inner(student_code, school, profiles(name))",
        { count: "exact" }
      )
      .gte("attendance_date", startDate)
      .lte("attendance_date", endDate)
      .order("attendance_date", { ascending: false })
      .order("id", { ascending: false })

    if (rawStudentId && /^\d+$/.test(rawStudentId)) {
      query = query.eq("student_id", Number(rawStudentId))
    }

    if (
      rawStatus === "present" ||
      rawStatus === "late" ||
      rawStatus === "absent"
    ) {
      query = query.eq("status", rawStatus)
    }

    if (normalizedSchoolFilter) {
      query = query.in("student_id", matchingSchoolStudentIds)
    }

    const from = (currentPage - 1) * PER_PAGE
    const to = from + PER_PAGE - 1

    const { data, count } = await query.range(from, to)
    rows = (data ?? []) as unknown as ReportRow[]
    totalCount = count ?? 0
  }

  const resetParams = new URLSearchParams({
    start_date: startDate,
    end_date: endDate,
  })
  const hasExtraFilters = Boolean(rawSchool || rawStatus || rawStudentId)
  const formResetKey = `${startDate}|${endDate}|${rawSchool}|${rawStatus}|${rawStudentId}`

  return (
    <>
      {/* Filters */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <Form
          key={formResetKey}
          action="/reports"
          data-nav-form="true"
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end"
        >
          {rawStudentId && (
            <input type="hidden" name="student_id" value={rawStudentId} />
          )}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tanggal Mulai
            </label>
            <input
              type="date"
              name="start_date"
              defaultValue={startDate}
              className="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tanggal Selesai
            </label>
            <input
              type="date"
              name="end_date"
              defaultValue={endDate}
              className="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Sekolah / Kampus
            </label>
            <select
              name="school"
              defaultValue={
                schools.find(
                  (opt) => opt.toLowerCase() === normalizedSchoolFilter
                ) ?? ""
              }
              className="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm"
            >
              <option value="">Semua Sekolah / Kampus</option>
              {schools.map((schoolOption) => (
                <option key={schoolOption} value={schoolOption}>
                  {schoolOption}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Status
            </label>
            <select
              name="status"
              defaultValue={rawStatus}
              className="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-cyan-500 sm:text-sm"
            >
              <option value="">Semua Status</option>
              <option value="present">Hadir</option>
              <option value="late">Terlambat</option>
              <option value="absent">Absen</option>
            </select>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="submit"
              className="fibernet-btn-primary inline-flex flex-1 h-[42px] items-center justify-center rounded-lg px-4 text-sm font-medium cursor-pointer"
            >
              Filter
            </button>
            {hasExtraFilters && (
              <Link
                href={`/reports?${resetParams.toString()}`}
                title="Reset Filter"
                className="inline-flex h-[42px] items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
              >
                Reset
              </Link>
            )}
          </div>
        </Form>
      </div>

      {/* Table */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="fibernet-table-header">
              <tr>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Tanggal
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Nama Peserta
                </th>
                <th
                  scope="col"
                  className="px-6 py-4 font-semibold text-gray-900 w-1/3"
                >
                  Check-In & Out
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Sekolah/Kampus
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Jenis
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Status
                </th>
                <th
                  scope="col"
                  className="px-6 py-4 font-semibold text-gray-900 text-right"
                >
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {rows.length > 0 ? (
                rows.map((attendance) => {
                  const studentObj = Array.isArray(attendance.students)
                    ? attendance.students[0]
                    : attendance.students
                  const profileObj = Array.isArray(studentObj?.profiles)
                    ? studentObj?.profiles[0]
                    : studentObj?.profiles

                  return (
                    <tr
                      key={attendance.id}
                      className="hover:bg-gray-50/50 transition-colors"
                    >
                      <td className="px-6 py-4 whitespace-nowrap text-gray-700">
                        {formatDateSlash(attendance.attendance_date)}
                      </td>
                      <td className="px-6 py-4">
                        <div className="font-medium text-gray-900">
                          {profileObj?.name ?? "-"}
                        </div>
                        <div className="text-xs text-gray-500">
                          {studentObj?.student_code ?? "-"}
                        </div>
                      </td>

                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          {attendance.check_in_at ? (
                            <span
                              className={`font-medium whitespace-nowrap ${
                                attendance.status === "late"
                                  ? "text-rose-600"
                                  : "text-gray-900"
                              }`}
                            >
                              {formatTime12h(attendance.check_in_at)}
                            </span>
                          ) : (
                            <span className="text-gray-400 font-medium whitespace-nowrap">
                              --:--
                            </span>
                          )}

                          <div className="flex-1 flex items-center min-w-[80px]">
                            <div
                              className={`w-2 h-2 rounded-full ${
                                attendance.check_in_at
                                  ? "bg-gray-400"
                                  : "bg-gray-200"
                              }`}
                            />
                            <div
                              className={`h-px ${
                                attendance.check_in_at
                                  ? "bg-gray-400"
                                  : "bg-gray-200"
                              } flex-1 relative`}
                            >
                              {attendance.check_in_at &&
                                attendance.check_out_at && (
                                  <span className="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 bg-white px-2 text-xs text-gray-400 whitespace-nowrap">
                                    {formatDuration(
                                      attendance.check_in_at,
                                      attendance.check_out_at,
                                      "short"
                                    )}
                                  </span>
                                )}
                            </div>
                            <div
                              className={`w-2 h-2 rounded-full border-2 bg-white ${
                                attendance.check_out_at
                                  ? "border-gray-400"
                                  : "border-gray-200"
                              }`}
                            />
                          </div>

                          {attendance.check_out_at ? (
                            <span className="text-gray-400 font-medium whitespace-nowrap">
                              {formatTime12h(attendance.check_out_at)}
                            </span>
                          ) : (
                            <span className="text-gray-400 font-medium whitespace-nowrap">
                              --:--
                            </span>
                          )}
                        </div>
                      </td>

                      <td className="px-6 py-4 text-gray-500 font-medium text-sm">
                        {studentObj?.school ?? "-"}
                      </td>

                      <td className="px-6 py-4">
                        {attendance.location_type === "outside" ? (
                          <span className="text-orange-600 font-medium text-sm">
                            Luar Kantor
                          </span>
                        ) : (
                          <span className="text-gray-700 font-medium text-sm">
                            Dalam Kantor
                          </span>
                        )}
                      </td>

                      <td className="px-6 py-4">
                        {attendance.status === "present" ? (
                          <span className="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">
                            HADIR
                          </span>
                        ) : attendance.status === "late" ? (
                          <span className="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 uppercase">
                            TERLAMBAT
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">
                            ABSEN
                          </span>
                        )}
                      </td>

                      <td className="px-6 py-4 text-right">
                        <Link
                          href={`/reports/${attendance.id}`}
                          className="inline-flex items-center gap-1 text-sm font-medium text-cyan-600 hover:text-cyan-800 transition-colors"
                        >
                          Detail
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
                              d="M9 5l7 7-7 7"
                            />
                          </svg>
                        </Link>
                      </td>
                    </tr>
                  )
                })
              ) : (
                <tr>
                  <td colSpan={7} className="px-6 py-16 text-center">
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
                            strokeWidth="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                          />
                        </svg>
                      </div>
                      <p className="text-sm text-gray-500">
                        Tidak ada data presensi yang ditemukan.
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
              basePath="/reports"
              currentPage={currentPage}
              perPage={PER_PAGE}
              totalCount={totalCount}
              queryParams={{
                start_date: startDate,
                end_date: endDate,
                school: rawSchool || undefined,
                status: rawStatus || undefined,
                student_id: rawStudentId || undefined,
              }}
            />
          </div>
        )}
      </div>
    </>
  )
}

function ReportsBodyFallback() {
  return (
    <>
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="space-y-2">
              <Skeleton className="h-4 w-24 rounded" />
              <Skeleton className="h-10 w-full rounded-lg" />
            </div>
          ))}
          <Skeleton className="h-[42px] w-full rounded-lg" />
        </div>
      </div>
      <ReportsTableSkeleton />
    </>
  )
}

export default async function ReportsPage({
  searchParams,
}: {
  searchParams: Promise<{
    start_date?: string
    end_date?: string
    school?: string
    status?: string
    student_id?: string
    page?: string
  }>
}) {
  const params = await searchParams
  const defaults = currentMonthRangeWita()
  const startDate =
    params.start_date && /^\d{4}-\d{2}-\d{2}$/.test(params.start_date)
      ? params.start_date
      : defaults.startDate
  const endDate =
    params.end_date && /^\d{4}-\d{2}-\d{2}$/.test(params.end_date)
      ? params.end_date
      : defaults.endDate

  const rawSchool = (params.school ?? "").trim()
  const rawStatus = (params.status ?? "").trim()
  const rawStudentId = (params.student_id ?? "").trim()
  const rawPage = Number(params.page ?? "1")
  const currentPage = Number.isInteger(rawPage) && rawPage > 0 ? rawPage : 1

  const exportParams = new URLSearchParams()
  exportParams.set("start_date", startDate)
  exportParams.set("end_date", endDate)
  if (rawSchool) exportParams.set("school", rawSchool)
  if (rawStudentId) exportParams.set("student_id", rawStudentId)
  if (rawStatus) exportParams.set("status", rawStatus)
  const exportHref = `/api/reports/export?${exportParams.toString()}`
  const suspenseKey = `${startDate}|${endDate}|${rawSchool}|${rawStatus}|${rawStudentId}|${currentPage}`

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
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
                  strokeWidth="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">
                Laporan Presensi
              </h1>
              <p className="text-sm text-gray-500">
                Riwayat presensi peserta PKL dan fitur export.
              </p>
            </div>
          </div>
          <a
            href={exportHref}
            data-download=""
            className="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors px-4 py-2.5 text-sm font-semibold"
          >
            <svg
              className="mr-2 h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            Export XLSX
          </a>
        </div>
      </div>

      <Suspense key={suspenseKey} fallback={<ReportsBodyFallback />}>
        <ReportsFilterAndTable
          startDate={startDate}
          endDate={endDate}
          rawSchool={rawSchool}
          rawStatus={rawStatus}
          rawStudentId={rawStudentId}
          currentPage={currentPage}
        />
      </Suspense>
    </div>
  )
}
