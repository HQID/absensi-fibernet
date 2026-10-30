import { Suspense } from "react"
import type { Metadata } from "next"
import { redirect } from "next/navigation"
import {
  StudentsTableClient,
  type StudentRowItem,
} from "@/components/students/students-table-client"
import { StudentsTableSkeleton } from "@/components/ui/page-skeletons"
import { Pagination } from "@/components/ui/pagination"
import { requireAdmin } from "@/lib/admin-helpers"
import { formatDateShort, isDatePastWitaEndOfDay } from "@/lib/time"

export const metadata: Metadata = {
  title: "Peserta PKL",
}

const PER_PAGE = 10

async function StudentsContent({ currentPage }: { currentPage: number }) {
  const admin = await requireAdmin()
  if (!admin.ok) {
    if (admin.status === 401) redirect("/login")
    redirect("/dashboard")
  }

  const from = (currentPage - 1) * PER_PAGE
  const to = from + PER_PAGE - 1

  const { data: rawStudents, count } = await admin.supabaseAdmin
    .from("students")
    .select(
      "id, student_code, school, major, start_date, end_date, status, profiles(name)",
      { count: "exact" }
    )
    .order("created_at", { ascending: false })
    .range(from, to)

  const totalCount = count ?? 0
  const studentRows = rawStudents ?? []
  const studentIds = studentRows.map((s) => s.id)

  const countsMap = new Map<
    number,
    { attendanceCount: number; photos: Set<string> }
  >()

  if (studentIds.length > 0) {
    const { data: attRows } = await admin.supabaseAdmin
      .from("attendances")
      .select("student_id, check_in_photo_path")
      .in("student_id", studentIds)

    for (const att of attRows ?? []) {
      let bucketEntry = countsMap.get(att.student_id)
      if (!bucketEntry) {
        bucketEntry = { attendanceCount: 0, photos: new Set<string>() }
        countsMap.set(att.student_id, bucketEntry)
      }
      bucketEntry.attendanceCount += 1
      const cleanPath = (att.check_in_photo_path ?? "")
        .trim()
        .replace(/^\/+/, "")
      if (cleanPath.length > 0) {
        bucketEntry.photos.add(cleanPath)
      }
    }
  }

  const items: StudentRowItem[] = studentRows.map((s) => {
    const profileObj = Array.isArray(s.profiles) ? s.profiles[0] : s.profiles
    const impact = countsMap.get(s.id)
    return {
      id: s.id,
      name: profileObj?.name ?? "-",
      student_code: s.student_code,
      school: s.school,
      major: s.major,
      start_date: s.start_date,
      end_date: s.end_date,
      period_label: `${formatDateShort(s.start_date)} - ${formatDateShort(s.end_date)}`,
      status: s.status,
      is_effectively_active:
        s.status === "active" && !isDatePastWitaEndOfDay(s.end_date),
      attendance_count: impact?.attendanceCount ?? 0,
      photo_count: impact?.photos.size ?? 0,
    }
  })

  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <StudentsTableClient students={items} />

      {totalCount > PER_PAGE && (
        <div className="border-t border-gray-100 px-6 py-4">
          <Pagination
            basePath="/students"
            currentPage={currentPage}
            perPage={PER_PAGE}
            totalCount={totalCount}
          />
        </div>
      )}
    </div>
  )
}

export default async function StudentsPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>
}) {
  const params = await searchParams
  const rawPage = Number(params.page ?? "1")
  const currentPage = Number.isInteger(rawPage) && rawPage > 0 ? rawPage : 1

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div className="sm:flex sm:items-center sm:justify-between">
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
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Peserta PKL</h1>
              <p className="text-sm text-gray-500">
                Kelola masa periode PKL dan status aktif siswa.
              </p>
            </div>
          </div>
        </div>
      </div>

      <Suspense key={currentPage} fallback={<StudentsTableSkeleton />}>
        <StudentsContent currentPage={currentPage} />
      </Suspense>
    </div>
  )
}
