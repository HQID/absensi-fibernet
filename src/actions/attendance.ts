"use server"

import { revalidatePath, updateTag } from "next/cache"
import { requireAdmin } from "@/lib/admin-helpers"
import { validateLocation } from "@/lib/geofence"
import { validateStudentActive } from "@/lib/student-guard"
import { createAdminClient } from "@/lib/supabase/admin"
import { createClient } from "@/lib/supabase/server"
import {
  isAfterClosingCutoff,
  isAfterLateCutoff,
  todayWitaDateString,
} from "@/lib/time"

export type AttendanceActionResult = {
  error?: string
  success?: string
}

function parseFiniteNumber(raw: FormDataEntryValue | null): number | null {
  if (typeof raw !== "string" || raw.trim() === "") return null
  const num = Number(raw)
  return Number.isFinite(num) ? num : null
}

function round2(val: number): number {
  return Math.round(val * 100) / 100
}

export async function checkInAction(
  formData: FormData
): Promise<AttendanceActionResult> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    return { error: "Sesi Anda telah berakhir. Silakan masuk kembali." }
  }

  const [{ data: profile }, { data: student }] = await Promise.all([
    supabase.from("profiles").select("role").eq("id", userId).maybeSingle(),
    supabase
      .from("students")
      .select("id, status, end_date")
      .eq("user_id", userId)
      .maybeSingle(),
  ])

  if (profile?.role !== "student") {
    return {
      error: "Hanya peserta PKL yang dapat melakukan check-in dan check-out.",
    }
  }

  const guard = validateStudentActive(student)
  if (!guard.active || !student) {
    return { error: guard.message ?? "Data siswa tidak ditemukan." }
  }

  const latitude = parseFiniteNumber(formData.get("latitude"))
  const longitude = parseFiniteNumber(formData.get("longitude"))
  const accuracy = parseFiniteNumber(formData.get("accuracy"))
  const locationType = String(formData.get("locationType") ?? "inside")
  const reason = String(formData.get("reason") ?? "").trim()
  const photo = formData.get("photo")

  if (latitude === null || longitude === null || accuracy === null) {
    return { error: "Koordinat lokasi GPS belum tersedia." }
  }

  if (locationType !== "inside" && locationType !== "outside") {
    return { error: "Jenis lokasi presensi tidak valid." }
  }

  if (!(photo instanceof File) || photo.size === 0) {
    return { error: "Foto bukti kehadiran wajib diambil terlebih dahulu." }
  }

  if (!photo.type.startsWith("image/")) {
    return { error: "Format file bukan gambar yang valid." }
  }

  if (photo.size > 4 * 1024 * 1024) {
    return { error: "Ukuran foto melebihi batas maksimal 4 MB." }
  }

  const now = new Date()
  const isLate = isAfterLateCutoff(now)

  if (locationType === "outside" || isLate) {
    if (reason.length === 0) {
      return {
        error:
          "Keterangan/Alasan wajib diisi (karena Anda memilih Luar Kantor atau terlambat).",
      }
    }
    if (reason.length < 3) {
      return {
        error: "Keterangan/Alasan minimal 3 karakter.",
      }
    }
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

  const { data: office } = await supabaseAdmin
    .from("office_locations")
    .select("*")
    .eq("active", true)
    .order("id", { ascending: true })
    .limit(1)
    .maybeSingle()

  if (!office) {
    return { error: "Lokasi kantor aktif tidak ditemukan." }
  }

  let distance: number | null = null
  if (locationType === "inside") {
    const validation = validateLocation(latitude, longitude, accuracy, office)
    if (!validation.valid) {
      return { error: validation.message }
    }
    distance = validation.distance
  }

  if (isAfterClosingCutoff(now)) {
    return {
      error:
        "Batas waktu presensi hari ini telah berakhir (Pukul 17:00). Anda tercatat sebagai Alpa/Tidak Hadir.",
    }
  }

  const today = todayWitaDateString(now)
  const { data: existing } = await supabaseAdmin
    .from("attendances")
    .select("id, check_in_at")
    .eq("student_id", student.id)
    .eq("attendance_date", today)
    .maybeSingle()

  if (existing?.check_in_at) {
    return { error: "Anda sudah melakukan check-in hari ini." }
  }

  const status = isLate ? "late" : "present"
  const [year, month, day] = today.split("-")
  const timestampSec = Math.floor(now.getTime() / 1000)
  const storagePath = `attendance/${year}/${month}/${day}/student_${student.id}_checkin_${timestampSec}.jpg`
  const bucket = process.env.SUPABASE_STORAGE_BUCKET || "photos"

  const photoBytes = new Uint8Array(await photo.arrayBuffer())
  const { error: uploadError } = await supabaseAdmin.storage
    .from(bucket)
    .upload(storagePath, photoBytes, {
      contentType: photo.type || "image/jpeg",
      upsert: false,
    })

  if (uploadError) {
    return { error: "Gagal menyimpan foto presensi ke penyimpanan." }
  }

  const { error: dbError } = await supabaseAdmin.from("attendances").upsert(
    {
      student_id: student.id,
      attendance_date: today,
      check_in_at: now.toISOString(),
      check_in_latitude: latitude,
      check_in_longitude: longitude,
      check_in_accuracy: round2(accuracy),
      check_in_distance: locationType === "inside" ? distance : null,
      check_in_photo_path: storagePath,
      status,
      location_type: locationType,
      reason: reason || null,
      is_approved: locationType === "outside" || status === "late" ? false : true,
    },
    { onConflict: "student_id,attendance_date" }
  )

  if (dbError) {
    await supabaseAdmin.storage.from(bucket).remove([storagePath])
    return { error: "Gagal menyimpan data presensi." }
  }

  updateTag("storage-stats")
  revalidatePath("/attendance")
  revalidatePath("/dashboard")
  revalidatePath("/reports")

  return { success: "Check-in berhasil!" }
}

export async function checkOutAction(
  formData: FormData
): Promise<AttendanceActionResult> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    return { error: "Sesi Anda telah berakhir. Silakan masuk kembali." }
  }

  const [{ data: profile }, { data: student }] = await Promise.all([
    supabase.from("profiles").select("role").eq("id", userId).maybeSingle(),
    supabase
      .from("students")
      .select("id, status, end_date")
      .eq("user_id", userId)
      .maybeSingle(),
  ])

  if (profile?.role !== "student") {
    return {
      error: "Hanya peserta PKL yang dapat melakukan check-in dan check-out.",
    }
  }

  const guard = validateStudentActive(student)
  if (!guard.active || !student) {
    return { error: guard.message ?? "Data siswa tidak ditemukan." }
  }

  const latitude = parseFiniteNumber(formData.get("latitude"))
  const longitude = parseFiniteNumber(formData.get("longitude"))
  const accuracy = parseFiniteNumber(formData.get("accuracy"))

  if (latitude === null || longitude === null || accuracy === null) {
    return { error: "Koordinat lokasi GPS belum tersedia." }
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

  const { data: office } = await supabaseAdmin
    .from("office_locations")
    .select("*")
    .eq("active", true)
    .order("id", { ascending: true })
    .limit(1)
    .maybeSingle()

  if (!office) {
    return { error: "Lokasi kantor aktif tidak ditemukan." }
  }

  const now = new Date()
  const today = todayWitaDateString(now)
  const { data: attendance } = await supabaseAdmin
    .from("attendances")
    .select("id, check_in_at, check_out_at, location_type")
    .eq("student_id", student.id)
    .eq("attendance_date", today)
    .maybeSingle()

  if (!attendance || !attendance.check_in_at) {
    return { error: "Anda belum melakukan check-in hari ini." }
  }

  if (attendance.check_out_at) {
    return { error: "Anda sudah melakukan check-out hari ini." }
  }

  let distance: number | null = null
  if (attendance.location_type === "inside") {
    const validation = validateLocation(latitude, longitude, accuracy, office)
    if (!validation.valid) {
      return { error: validation.message }
    }
    distance = validation.distance
  }

  const { error: updateError } = await supabaseAdmin
    .from("attendances")
    .update({
      check_out_at: now.toISOString(),
      check_out_latitude: latitude,
      check_out_longitude: longitude,
      check_out_accuracy: round2(accuracy),
      check_out_distance: distance,
    })
    .eq("id", attendance.id)

  if (updateError) {
    return { error: "Gagal menyimpan data check-out." }
  }

  revalidatePath("/attendance")
  revalidatePath("/dashboard")
  revalidatePath("/reports")

  return { success: "Check-out berhasil!" }
}

export async function approveAttendanceAction(
  formData: FormData
): Promise<AttendanceActionResult> {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return { error: admin.error }
  }

  const attendanceId = Number(formData.get("attendance_id"))
  const status = String(formData.get("status") ?? "").trim()

  if (!Number.isInteger(attendanceId) || attendanceId <= 0) {
    return { error: "ID presensi tidak valid." }
  }

  if (status !== "present" && status !== "late" && status !== "absent") {
    return { error: "Status persetujuan tidak valid." }
  }

  const { error } = await admin.supabaseAdmin
    .from("attendances")
    .update({
      status,
      is_approved: true,
    })
    .eq("id", attendanceId)

  if (error) {
    return { error: "Gagal memperbarui status persetujuan presensi." }
  }

  revalidatePath("/reports")
  revalidatePath(`/reports/${attendanceId}`)
  revalidatePath("/dashboard")

  return { success: "Status presensi berhasil diubah dan disetujui." }
}
