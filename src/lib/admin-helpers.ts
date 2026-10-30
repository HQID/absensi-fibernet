import { createClient as createSupabaseJsClient } from "@supabase/supabase-js"
import { updateTag } from "next/cache"
import { createAdminClient } from "@/lib/supabase/admin"
import { getSupabasePublicEnv } from "@/lib/supabase/env"
import { createClient } from "@/lib/supabase/server"
import type { Database } from "@/types/database.types"

export type AdminContextResult =
  | {
      ok: true
      userId: string
      supabase: Awaited<ReturnType<typeof createClient>>
      supabaseAdmin: ReturnType<typeof createAdminClient>
    }
  | {
      ok: false
      status: 401 | 403 | 500
      error: string
    }

export async function requireAdmin(): Promise<AdminContextResult> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    return {
      ok: false,
      status: 401,
      error: "Sesi Anda telah berakhir. Silakan masuk kembali.",
    }
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", userId)
    .maybeSingle()

  if (profile?.role !== "admin") {
    return {
      ok: false,
      status: 403,
      error: "Akses ditolak. Hanya administrator yang diizinkan.",
    }
  }

  try {
    const supabaseAdmin = createAdminClient()
    return { ok: true, userId, supabase, supabaseAdmin }
  } catch {
    return {
      ok: false,
      status: 500,
      error:
        "Konfigurasi SUPABASE_SECRET_KEY belum diisi pada server (.env.local).",
    }
  }
}

export async function verifyUserCurrentPassword(
  email: string,
  password: string
): Promise<boolean> {
  if (!email || !password) return false
  const { url, publishableKey } = getSupabasePublicEnv()
  const verifier = createSupabaseJsClient<Database>(url, publishableKey, {
    auth: {
      persistSession: false,
      autoRefreshToken: false,
      detectSessionInUrl: false,
    },
  })
  const { data, error } = await verifier.auth.signInWithPassword({
    email,
    password,
  })
  return !error && Boolean(data.user)
}

export async function deleteStudentWithAssets(
  supabaseAdmin: ReturnType<typeof createAdminClient>,
  student: { id: number; user_id: string }
): Promise<{ deletedAttendances: number; deletedPhotos: number }> {
  const { data: profile } = await supabaseAdmin
    .from("profiles")
    .select("role")
    .eq("id", student.user_id)
    .maybeSingle()

  if (profile?.role === "admin") {
    throw new Error("Akun administrator tidak dapat dihapus melalui fitur ini.")
  }

  const { data: attendanceRows } = await supabaseAdmin
    .from("attendances")
    .select("id, check_in_photo_path")
    .eq("student_id", student.id)

  const rows = attendanceRows ?? []
  const deletedAttendances = rows.length

  const uniquePaths = Array.from(
    new Set(
      rows
        .map((row) => (row.check_in_photo_path ?? "").trim().replace(/^\/+/, ""))
        .filter((p) => p.length > 0)
    )
  )

  const bucket = process.env.SUPABASE_STORAGE_BUCKET || "photos"
  let deletedPhotos = 0

  for (let i = 0; i < uniquePaths.length; i += 100) {
    const chunk = uniquePaths.slice(i, i + 100)
    const { data: removed } = await supabaseAdmin.storage
      .from(bucket)
      .remove(chunk)
    deletedPhotos += removed ? removed.length : chunk.length
  }

  const { error: deleteAuthError } =
    await supabaseAdmin.auth.admin.deleteUser(student.user_id)

  if (deleteAuthError) {
    throw new Error("Gagal menghapus akun pengguna dari autentikasi.")
  }

  updateTag("storage-stats")

  return { deletedAttendances, deletedPhotos }
}
