import { formatDateShort, isDatePastWitaEndOfDay } from "./time.ts"

export type StudentGuardTarget = {
  status: string
  end_date: string
} | null

export type StudentGuardResult =
  | { active: true; message: null }
  | { active: false; message: string }

export function validateStudentActive(
  student: StudentGuardTarget,
  now: Date = new Date()
): StudentGuardResult {
  if (!student) {
    return {
      active: false,
      message: "Data siswa tidak ditemukan.",
    }
  }

  if (student.status !== "active") {
    return {
      active: false,
      message: "Akun PKL Anda telah dinonaktifkan.",
    }
  }

  if (isDatePastWitaEndOfDay(student.end_date, now)) {
    return {
      active: false,
      message: `Masa PKL Anda telah berakhir pada ${formatDateShort(student.end_date)}. Hubungi Admin untuk perpanjangan.`,
    }
  }

  return { active: true, message: null }
}

export function getInitials(name: string): string {
  const parts = name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (parts.length === 0) return "U"
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (
    parts[0].charAt(0) + parts[parts.length - 1].charAt(0)
  ).toUpperCase()
}
