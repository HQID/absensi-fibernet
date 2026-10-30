"use server"

import { revalidatePath } from "next/cache"
import { deleteStudentWithAssets, requireAdmin } from "@/lib/admin-helpers"

export type StudentActionResult = {
  error?: string
  success?: string
}

export async function updateStudentAction(
  formData: FormData
): Promise<StudentActionResult> {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return { error: admin.error }
  }

  const studentId = Number(formData.get("student_id"))
  const endDate = String(formData.get("end_date") ?? "").trim()
  const status = String(formData.get("status") ?? "").trim()

  if (!Number.isInteger(studentId) || studentId <= 0) {
    return { error: "ID peserta PKL tidak valid." }
  }

  if (!/^\d{4}-\d{2}-\d{2}$/.test(endDate)) {
    return { error: "Tanggal selesai PKL wajib diisi dengan format yang valid." }
  }

  if (status !== "active" && status !== "inactive") {
    return { error: "Status akun tidak valid." }
  }

  const { data: student } = await admin.supabaseAdmin
    .from("students")
    .select("id, start_date")
    .eq("id", studentId)
    .maybeSingle()

  if (!student) {
    return { error: "Data peserta PKL tidak ditemukan." }
  }

  if (endDate < student.start_date) {
    return {
      error: "Tanggal selesai PKL harus sama atau setelah tanggal mulai PKL.",
    }
  }

  const { error } = await admin.supabaseAdmin
    .from("students")
    .update({ end_date: endDate, status })
    .eq("id", studentId)

  if (error) {
    return { error: "Gagal memperbarui data peserta PKL." }
  }

  revalidatePath("/students")
  revalidatePath("/dashboard")
  revalidatePath("/reports")

  return { success: "Data siswa berhasil diperbarui." }
}

export async function deleteStudentPermanentlyAction(
  formData: FormData
): Promise<StudentActionResult> {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return { error: admin.error }
  }

  const studentId = Number(formData.get("student_id"))
  const confirmationInput = String(
    formData.get("confirmation_input") ?? ""
  ).trim()

  if (!Number.isInteger(studentId) || studentId <= 0) {
    return { error: "ID peserta PKL tidak valid." }
  }

  const { data: student } = await admin.supabaseAdmin
    .from("students")
    .select("id, user_id, student_code, profiles(name)")
    .eq("id", studentId)
    .maybeSingle()

  if (!student) {
    return { error: "Data peserta PKL sudah tidak ditemukan." }
  }

  if (confirmationInput !== student.student_code.trim()) {
    return { error: "NIM / NISN yang diketik tidak sesuai." }
  }

  const profileObj = Array.isArray(student.profiles)
    ? student.profiles[0]
    : student.profiles
  const studentName = profileObj?.name || "Peserta PKL"

  try {
    const result = await deleteStudentWithAssets(admin.supabaseAdmin, {
      id: student.id,
      user_id: student.user_id,
    })

    revalidatePath("/students")
    revalidatePath("/dashboard")
    revalidatePath("/reports")

    return {
      success: `Data ${studentName} beserta riwayat presensi dan ${result.deletedPhotos} foto berhasil dihapus permanen.`,
    }
  } catch (err) {
    return {
      error:
        err instanceof Error
          ? err.message
          : "Gagal menghapus data peserta PKL.",
    }
  }
}
