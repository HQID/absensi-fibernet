import { createAdminClient } from "@/lib/supabase/admin"
import { computeEligibleAbsentDates } from "@/lib/time"

export { computeEligibleAbsentDates }

const YMD_REGEX = /^\d{4}-\d{2}-\d{2}$/

export async function syncAbsentees(
  startDate: string,
  endDate: string
): Promise<void> {
  if (!YMD_REGEX.test(startDate) || !YMD_REGEX.test(endDate)) {
    return
  }

  let supabaseAdmin
  try {
    supabaseAdmin = createAdminClient()
  } catch {
    return
  }

  const { data: students } = await supabaseAdmin
    .from("students")
    .select("id, start_date, end_date")
    .eq("status", "active")

  if (!students || students.length === 0) {
    return
  }

  const earliestStudentStart = students.reduce(
    (min, s) => (s.start_date < min ? s.start_date : min),
    students[0].start_date
  )
  const effectiveStart =
    startDate < earliestStudentStart ? earliestStudentStart : startDate

  const dates = computeEligibleAbsentDates(effectiveStart, endDate)
  if (dates.length === 0) {
    return
  }

  const firstDate = dates[0]
  const lastDate = dates[dates.length - 1]

  const { data: existingRows } = await supabaseAdmin
    .from("attendances")
    .select("student_id, attendance_date")
    .gte("attendance_date", firstDate)
    .lte("attendance_date", lastDate)

  const existingSet = new Set(
    (existingRows ?? []).map(
      (item) => `${item.student_id}|${item.attendance_date}`
    )
  )

  const rowsToInsert: {
    student_id: number
    attendance_date: string
    status: "absent"
  }[] = []

  for (const dateStr of dates) {
    for (const student of students) {
      if (existingSet.has(`${student.id}|${dateStr}`)) {
        continue
      }
      if (dateStr < student.start_date || dateStr > student.end_date) {
        continue
      }
      rowsToInsert.push({
        student_id: student.id,
        attendance_date: dateStr,
        status: "absent",
      })
    }
  }

  for (let i = 0; i < rowsToInsert.length; i += 500) {
    const chunk = rowsToInsert.slice(i, i + 500)
    await supabaseAdmin.from("attendances").upsert(chunk, {
      onConflict: "student_id,attendance_date",
      ignoreDuplicates: true,
    })
  }
}
