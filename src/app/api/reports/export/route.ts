import ExcelJS from "exceljs"
import { NextResponse, type NextRequest } from "next/server"
import { requireAdmin } from "@/lib/admin-helpers"
import { syncAbsentees } from "@/lib/sync-absentees"
import { formatTime24h } from "@/lib/time"

function slugify(value: string): string {
  return value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
}

export async function GET(request: NextRequest) {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return NextResponse.json({ message: admin.error }, { status: admin.status })
  }

  const sp = request.nextUrl.searchParams
  const startDate = (sp.get("start_date") ?? "").trim() || null
  const endDate = (sp.get("end_date") ?? "").trim() || null
  const school = (sp.get("school") ?? "").trim() || null
  const status = (sp.get("status") ?? "").trim() || null
  const studentId = (sp.get("student_id") ?? "").trim() || null

  if (
    startDate &&
    endDate &&
    /^\d{4}-\d{2}-\d{2}$/.test(startDate) &&
    /^\d{4}-\d{2}-\d{2}$/.test(endDate)
  ) {
    await syncAbsentees(startDate, endDate)
  }

  let matchingSchoolStudentIds: number[] | null = null
  if (school) {
    const normalizedSchool = school.toLowerCase()
    const { data: students } = await admin.supabaseAdmin
      .from("students")
      .select("id, school")

    matchingSchoolStudentIds = (students ?? [])
      .filter((s) => (s.school ?? "").trim().toLowerCase() === normalizedSchool)
      .map((s) => s.id)
  }

  type ExportAttendanceRow = {
    attendance_date: string
    check_in_at: string | null
    check_out_at: string | null
    location_type: string
    status: string
    check_in_distance: number | null
    check_in_accuracy: number | null
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

  let rows: ExportAttendanceRow[] = []

  if (matchingSchoolStudentIds === null || matchingSchoolStudentIds.length > 0) {
    let query = admin.supabaseAdmin
      .from("attendances")
      .select(
        "attendance_date, check_in_at, check_out_at, location_type, status, check_in_distance, check_in_accuracy, students!inner(student_code, school, profiles(name))"
      )
      .order("attendance_date", { ascending: false })
      .order("id", { ascending: false })

    if (startDate && /^\d{4}-\d{2}-\d{2}$/.test(startDate)) {
      query = query.gte("attendance_date", startDate)
    }
    if (endDate && /^\d{4}-\d{2}-\d{2}$/.test(endDate)) {
      query = query.lte("attendance_date", endDate)
    }
    if (studentId && /^\d+$/.test(studentId)) {
      query = query.eq("student_id", Number(studentId))
    }
    if (status === "present" || status === "late" || status === "absent") {
      query = query.eq("status", status)
    }
    if (matchingSchoolStudentIds !== null) {
      query = query.in("student_id", matchingSchoolStudentIds)
    }

    const { data } = await query
    rows = (data ?? []) as unknown as ExportAttendanceRow[]
  }

  const workbook = new ExcelJS.Workbook()
  const sheet = workbook.addWorksheet("Rekap Presensi")

  const headings = [
    "NIM/NISN",
    "Nama Peserta",
    "Sekolah/Kampus",
    "Tanggal",
    "Check In",
    "Check Out",
    "Jenis Presensi",
    "Status",
    "Jarak (m)",
    "Akurasi GPS (m)",
  ]

  const headerRow = sheet.addRow(headings)
  headerRow.eachCell((cell) => {
    cell.font = { bold: true, color: { argb: "FFFFFFFF" } }
    cell.fill = {
      type: "pattern",
      pattern: "solid",
      fgColor: { argb: "FF0891B2" },
    }
  })

  const maxLengths = headings.map((h) => h.length)

  for (const item of rows) {
    const studentObj = Array.isArray(item.students)
      ? item.students[0]
      : item.students
    const profileObj = Array.isArray(studentObj?.profiles)
      ? studentObj?.profiles[0]
      : studentObj?.profiles

    const jenis =
      item.location_type === "inside"
        ? "Dalam Kantor"
        : item.location_type === "outside"
          ? "Luar Kantor"
          : "-"

    const statusLabel =
      item.status === "present"
        ? "HADIR"
        : item.status === "late"
          ? "TERLAMBAT"
          : item.status === "absent"
            ? "ABSEN"
            : "-"

    const values = [
      studentObj?.student_code ?? "-",
      profileObj?.name ?? "-",
      studentObj?.school ?? "-",
      item.attendance_date.slice(0, 10),
      item.check_in_at ? formatTime24h(item.check_in_at) : "-",
      item.check_out_at ? formatTime24h(item.check_out_at) : "-",
      jenis,
      statusLabel,
      item.check_in_distance ?? "-",
      item.check_in_accuracy ?? "-",
    ]

    sheet.addRow(values)
    values.forEach((v, idx) => {
      const len = String(v).length
      if (len > maxLengths[idx]) {
        maxLengths[idx] = len
      }
    })
  }

  sheet.columns.forEach((column, idx) => {
    column.width = Math.max(12, maxLengths[idx] + 4)
  })

  const buffer = await workbook.xlsx.writeBuffer()
  const slug = school ? slugify(school) : ""
  const schoolSlug = slug ? `-${slug}` : ""
  const filename = `rekap-presensi${schoolSlug}-${startDate || "all"}-to-${endDate || "all"}.xlsx`

  return new NextResponse(buffer, {
    status: 200,
    headers: {
      "Content-Type":
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "Content-Disposition": `attachment; filename="${filename}"`,
      "Cache-Control": "no-store",
    },
  })
}
