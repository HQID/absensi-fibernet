"use server"

import { revalidatePath, updateTag } from "next/cache"
import {
  deleteStudentWithAssets,
  requireAdmin,
  verifyUserCurrentPassword,
} from "@/lib/admin-helpers"
import { cleanupUnusedStorageFiles, formatBytes } from "@/lib/storage-usage"
import { createAdminClient } from "@/lib/supabase/admin"
import { createClient } from "@/lib/supabase/server"

export type SettingsActionResult = {
  error?: string
  success?: string
  heading?: string
  fieldErrors?: Record<string, string>
  redirectTo?: string
}

export async function updateProfileAction(
  formData: FormData
): Promise<SettingsActionResult> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    return { error: "Sesi Anda telah berakhir. Silakan masuk kembali." }
  }

  let supabaseAdmin
  try {
    supabaseAdmin = createAdminClient()
  } catch {
    return {
      error:
        "Konfigurasi SUPABASE_SECRET_KEY belum diisi pada server (.env.local).",
    }
  }

  const [{ data: profile }, { data: student }] = await Promise.all([
    supabaseAdmin
      .from("profiles")
      .select("id, role")
      .eq("id", userId)
      .maybeSingle(),
    supabaseAdmin
      .from("students")
      .select("id")
      .eq("user_id", userId)
      .maybeSingle(),
  ])

  if (!profile) {
    return { error: "Profil pengguna tidak ditemukan." }
  }

  const name = String(formData.get("name") ?? "").trim()
  const studentCode = String(formData.get("student_code") ?? "").trim()
  const school = String(formData.get("school") ?? "").trim()
  const major = String(formData.get("major") ?? "").trim()

  const fieldErrors: Record<string, string> = {}
  if (!name || name.length > 255) {
    fieldErrors.name = "Nama lengkap wajib diisi (maksimal 255 karakter)."
  }

  if (profile.role === "student" && student) {
    if (!studentCode || studentCode.length > 255) {
      fieldErrors.student_code = "NIM / NISN wajib diisi."
    }
    if (!school || school.length > 255) {
      fieldErrors.school = "Asal Sekolah / Kampus wajib diisi."
    }
    if (!major || major.length > 255) {
      fieldErrors.major = "Jurusan wajib diisi."
    }

    if (!fieldErrors.student_code) {
      const { data: duplicateCode } = await supabaseAdmin
        .from("students")
        .select("id")
        .eq("student_code", studentCode)
        .neq("id", student.id)
        .maybeSingle()

      if (duplicateCode) {
        fieldErrors.student_code = "NIM / NISN sudah digunakan peserta lain."
      }
    }
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { fieldErrors }
  }

  const { error: profileErr } = await supabaseAdmin
    .from("profiles")
    .update({ name })
    .eq("id", userId)

  if (profileErr) {
    return { error: "Gagal memperbarui profil pengguna." }
  }

  await supabaseAdmin.auth.admin.updateUserById(userId, {
    user_metadata: { name },
  })

  if (profile.role === "student" && student) {
    const { error: studentErr } = await supabaseAdmin
      .from("students")
      .update({
        student_code: studentCode,
        school,
        major,
      })
      .eq("id", student.id)

    if (studentErr) {
      return { error: "Gagal memperbarui biodata peserta PKL." }
    }
  }

  revalidatePath("/", "layout")
  return { success: "Profil berhasil diperbarui." }
}

export async function deleteOwnAccountAction(
  formData: FormData
): Promise<SettingsActionResult> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    return { error: "Sesi Anda telah berakhir. Silakan masuk kembali." }
  }

  const password = String(formData.get("password") ?? "")
  if (!password) {
    return { fieldErrors: { password: "Kata sandi wajib diisi." } }
  }

  let supabaseAdmin
  try {
    supabaseAdmin = createAdminClient()
  } catch {
    return {
      error:
        "Konfigurasi SUPABASE_SECRET_KEY belum diisi pada server (.env.local).",
    }
  }

  const [{ data: profile }, { data: student }] = await Promise.all([
    supabaseAdmin
      .from("profiles")
      .select("email, role")
      .eq("id", userId)
      .maybeSingle(),
    supabaseAdmin
      .from("students")
      .select("id, user_id")
      .eq("user_id", userId)
      .maybeSingle(),
  ])

  if (!profile) {
    return { error: "Profil pengguna tidak ditemukan." }
  }

  const validPassword = await verifyUserCurrentPassword(profile.email, password)
  if (!validPassword) {
    return {
      fieldErrors: { password: "Kata sandi yang Anda masukkan salah." },
    }
  }

  await supabase.auth.signOut()

  if (student && profile.role !== "admin") {
    await deleteStudentWithAssets(supabaseAdmin, student)
  } else {
    await supabaseAdmin.auth.admin.deleteUser(userId)
  }

  revalidatePath("/", "layout")
  return { redirectTo: "/login" }
}

export async function updatePasswordAction(
  formData: FormData
): Promise<SettingsActionResult> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    return { error: "Sesi Anda telah berakhir. Silakan masuk kembali." }
  }

  const currentPassword = String(formData.get("current_password") ?? "")
  const password = String(formData.get("password") ?? "")
  const passwordConfirmation = String(
    formData.get("password_confirmation") ?? ""
  )

  const fieldErrors: Record<string, string> = {}
  if (!currentPassword) {
    fieldErrors.current_password = "Kata sandi saat ini wajib diisi."
  }
  if (password.length < 8) {
    fieldErrors.password = "Kata sandi minimal harus 8 karakter."
  }
  if (password !== passwordConfirmation) {
    fieldErrors.password_confirmation = "Konfirmasi kata sandi tidak cocok."
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { fieldErrors }
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("email")
    .eq("id", userId)
    .maybeSingle()

  if (!profile) {
    return { error: "Profil pengguna tidak ditemukan." }
  }

  const isCurrentValid = await verifyUserCurrentPassword(
    profile.email,
    currentPassword
  )
  if (!isCurrentValid) {
    return {
      fieldErrors: {
        current_password: "Kata sandi saat ini tidak sesuai.",
      },
    }
  }

  const { error: updateErr } = await supabase.auth.updateUser({ password })
  if (updateErr) {
    return { error: updateErr.message || "Gagal memperbarui kata sandi." }
  }

  return { success: "Kata sandi berhasil diperbarui." }
}

export async function updateGeofenceAction(
  formData: FormData
): Promise<SettingsActionResult> {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return { error: admin.error }
  }

  const locationIdRaw = String(formData.get("location_id") ?? "").trim()
  const name = String(formData.get("name") ?? "").trim()
  const latRaw = Number(formData.get("latitude"))
  const lngRaw = Number(formData.get("longitude"))
  const radiusRaw = Number(formData.get("radius_meters"))
  const accuracyRaw = Number(formData.get("max_accuracy_meters"))
  const active = String(formData.get("active") ?? "false") === "true"

  const fieldErrors: Record<string, string> = {}
  if (!name || name.length > 255) {
    fieldErrors.name = "Nama kantor wajib diisi (maksimal 255 karakter)."
  }
  if (!Number.isFinite(latRaw) || latRaw < -90 || latRaw > 90) {
    fieldErrors.latitude = "Latitude harus bernilai antara -90 hingga 90."
  }
  if (!Number.isFinite(lngRaw) || lngRaw < -180 || lngRaw > 180) {
    fieldErrors.longitude = "Longitude harus bernilai antara -180 hingga 180."
  }
  if (!Number.isInteger(radiusRaw) || radiusRaw < 10) {
    fieldErrors.radius_meters = "Radius minimal adalah 10 meter."
  }
  if (!Number.isInteger(accuracyRaw) || accuracyRaw < 5) {
    fieldErrors.max_accuracy_meters =
      "Batas akurasi GPS minimal adalah 5 meter."
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { fieldErrors }
  }

  const payload = {
    name,
    latitude: latRaw,
    longitude: lngRaw,
    radius_meters: radiusRaw,
    max_accuracy_meters: accuracyRaw,
    active,
  }

  const parsedId = Number(locationIdRaw)
  if (Number.isInteger(parsedId) && parsedId > 0) {
    const { error } = await admin.supabaseAdmin
      .from("office_locations")
      .update(payload)
      .eq("id", parsedId)

    if (error) {
      return { error: "Gagal menyimpan pengaturan lokasi kantor." }
    }
  } else {
    const { data: existing } = await admin.supabaseAdmin
      .from("office_locations")
      .select("id")
      .order("id", { ascending: true })
      .limit(1)
      .maybeSingle()

    if (existing) {
      const { error } = await admin.supabaseAdmin
        .from("office_locations")
        .update(payload)
        .eq("id", existing.id)
      if (error) {
        return { error: "Gagal menyimpan pengaturan lokasi kantor." }
      }
    } else {
      const { error } = await admin.supabaseAdmin
        .from("office_locations")
        .insert(payload)
      if (error) {
        return { error: "Gagal membuat pengaturan lokasi kantor." }
      }
    }
  }

  revalidatePath("/settings/geofence")
  revalidatePath("/attendance")
  revalidatePath("/dashboard")

  return {
    heading: "Tersimpan",
    success: "Pengaturan lokasi kantor berhasil disimpan.",
  }
}

export async function refreshStorageStatsAction(): Promise<SettingsActionResult> {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return { error: admin.error }
  }

  updateTag("storage-stats")
  revalidatePath("/settings/storage")

  return {
    heading: "Diperbarui",
    success: "Data kapasitas penyimpanan berhasil diperbarui.",
  }
}

export async function cleanupStorageFilesAction(): Promise<SettingsActionResult> {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return { error: admin.error }
  }

  try {
    const result = await cleanupUnusedStorageFiles()
    revalidatePath("/settings/storage")
    const freedText = formatBytes(result.freed_bytes)

    return {
      heading: "Pembersihan Selesai",
      success: `${result.deleted_count} file sampah (${freedText}) berhasil dihapus dari penyimpanan.`,
    }
  } catch {
    return { error: "Gagal membersihkan file sampah dari penyimpanan." }
  }
}
