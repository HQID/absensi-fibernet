"use server"

import { revalidatePath } from "next/cache"
import { cookies, headers } from "next/headers"
import { redirect } from "next/navigation"
import { createAdminClient } from "@/lib/supabase/admin"
import { createClient, SESSION_ONLY_COOKIE } from "@/lib/supabase/server"
import { validateStudentActive } from "@/lib/student-guard"

export type AuthActionState = {
  error?: string
  status?: string
  fieldErrors?: Record<string, string>
  values?: Record<string, string>
}

function getString(formData: FormData, key: string): string {
  const val = formData.get(key)
  return typeof val === "string" ? val.trim() : ""
}

export async function loginAction(
  _prevState: AuthActionState,
  formData: FormData
): Promise<AuthActionState> {
  const email = getString(formData, "email").toLowerCase()
  const password = String(formData.get("password") ?? "")
  const remember = formData.get("remember") !== null

  if (!email || !password) {
    return {
      error: "Email dan kata sandi wajib diisi.",
      values: { email },
    }
  }

  const cookieStore = await cookies()
  if (remember) {
    cookieStore.delete(SESSION_ONLY_COOKIE)
  } else {
    cookieStore.set(SESSION_ONLY_COOKIE, "1", {
      httpOnly: true,
      sameSite: "lax",
      path: "/",
    })
  }

  const supabase = await createClient({ sessionOnly: !remember })
  const { data, error } = await supabase.auth.signInWithPassword({
    email,
    password,
  })

  if (error || !data.user) {
    return {
      error: "Email atau kata sandi yang Anda masukkan salah.",
      values: { email },
    }
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", data.user.id)
    .single()

  if (!profile) {
    await supabase.auth.signOut()
    return {
      error: "Profil pengguna tidak ditemukan.",
      values: { email },
    }
  }

  if (profile.role === "student") {
    const { data: student } = await supabase
      .from("students")
      .select("status, end_date")
      .eq("user_id", data.user.id)
      .maybeSingle()

    const guard = validateStudentActive(student)
    if (!guard.active) {
      await supabase.auth.signOut()
      return {
        error: guard.message,
        values: { email },
      }
    }
  }

  revalidatePath("/", "layout")
  redirect("/dashboard")
}

export async function registerAction(
  _prevState: AuthActionState,
  formData: FormData
): Promise<AuthActionState> {
  const name = getString(formData, "name")
  const email = getString(formData, "email").toLowerCase()
  const student_code = getString(formData, "student_code")
  const school = getString(formData, "school")
  const major = getString(formData, "major")
  const start_date = getString(formData, "start_date")
  const end_date = getString(formData, "end_date")
  const password = String(formData.get("password") ?? "")
  const password_confirmation = String(
    formData.get("password_confirmation") ?? ""
  )

  const values = {
    name,
    email,
    student_code,
    school,
    major,
    start_date,
    end_date,
  }

  const fieldErrors: Record<string, string> = {}

  if (!name || name.length > 255) {
    fieldErrors.name = "Nama lengkap wajib diisi (maksimal 255 karakter)."
  }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || email.length > 255) {
    fieldErrors.email = "Format alamat email tidak valid."
  }
  if (!student_code || student_code.length > 255) {
    fieldErrors.student_code = "NIM / NISN wajib diisi."
  }
  if (!school || school.length > 255) {
    fieldErrors.school = "Asal Sekolah / Kampus wajib diisi."
  }
  if (!major || major.length > 255) {
    fieldErrors.major = "Jurusan wajib diisi."
  }
  if (!/^\d{4}-\d{2}-\d{2}$/.test(start_date)) {
    fieldErrors.start_date = "Tanggal mulai PKL tidak valid."
  }
  if (!/^\d{4}-\d{2}-\d{2}$/.test(end_date)) {
    fieldErrors.end_date = "Tanggal selesai PKL tidak valid."
  } else if (start_date && end_date < start_date) {
    fieldErrors.end_date =
      "Tanggal selesai PKL harus sama atau setelah tanggal mulai."
  }
  if (password.length < 8) {
    fieldErrors.password = "Kata sandi minimal harus 8 karakter."
  }
  if (password !== password_confirmation) {
    fieldErrors.password_confirmation = "Konfirmasi kata sandi tidak cocok."
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { fieldErrors, values }
  }

  let supabaseAdmin
  try {
    supabaseAdmin = createAdminClient()
  } catch {
    return {
      error:
        "Konfigurasi SUPABASE_SECRET_KEY belum diisi pada server (.env.local).",
      values,
    }
  }

  const [{ data: existingEmail }, { data: existingCode }] = await Promise.all([
    supabaseAdmin
      .from("profiles")
      .select("id")
      .eq("email", email)
      .maybeSingle(),
    supabaseAdmin
      .from("students")
      .select("id")
      .eq("student_code", student_code)
      .maybeSingle(),
  ])

  if (existingEmail) {
    fieldErrors.email = "Alamat email sudah terdaftar."
  }
  if (existingCode) {
    fieldErrors.student_code = "NIM / NISN sudah terdaftar."
  }
  if (Object.keys(fieldErrors).length > 0) {
    return { fieldErrors, values }
  }

  const { data: createdAuth, error: createAuthError } =
    await supabaseAdmin.auth.admin.createUser({
      email,
      password,
      email_confirm: true,
      user_metadata: { name },
    })

  if (createAuthError || !createdAuth.user) {
    return {
      error:
        createAuthError?.message?.toLowerCase().includes("already")
          ? "Alamat email sudah terdaftar."
          : "Gagal mendaftarkan akun. Silakan coba kembali.",
      values,
    }
  }

  const userId = createdAuth.user.id

  const { error: profileError } = await supabaseAdmin.from("profiles").insert({
    id: userId,
    name,
    email,
    role: "student",
  })

  if (profileError) {
    await supabaseAdmin.auth.admin.deleteUser(userId)
    return {
      error: "Gagal menyimpan profil pengguna.",
      values,
    }
  }

  const { error: studentError } = await supabaseAdmin.from("students").insert({
    user_id: userId,
    student_code,
    school,
    major,
    start_date,
    end_date,
    status: "active",
  })

  if (studentError) {
    await supabaseAdmin.auth.admin.deleteUser(userId)
    return {
      fieldErrors: studentError.message.includes("student_code")
        ? { student_code: "NIM / NISN sudah terdaftar." }
        : undefined,
      error: studentError.message.includes("student_code")
        ? undefined
        : "Gagal menyimpan biodata peserta PKL.",
      values,
    }
  }

  const supabase = await createClient()
  const { error: signInError } = await supabase.auth.signInWithPassword({
    email,
    password,
  })

  if (signInError) {
    redirect("/login?status=Akun+berhasil+didaftarkan.+Silakan+masuk.")
  }

  revalidatePath("/", "layout")
  redirect("/dashboard")
}

export async function logoutAction(): Promise<void> {
  const supabase = await createClient()
  await supabase.auth.signOut()
  const cookieStore = await cookies()
  cookieStore.delete(SESSION_ONLY_COOKIE)
  revalidatePath("/", "layout")
  redirect("/login")
}

export async function forgotPasswordAction(
  _prevState: AuthActionState,
  formData: FormData
): Promise<AuthActionState> {
  const email = getString(formData, "email").toLowerCase()

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return {
      fieldErrors: { email: "Format alamat email tidak valid." },
      values: { email },
    }
  }

  const hdrs = await headers()
  const origin =
    hdrs.get("origin") ||
    process.env.NEXT_PUBLIC_APP_URL ||
    (process.env.VERCEL_PROJECT_PRODUCTION_URL
      ? `https://${process.env.VERCEL_PROJECT_PRODUCTION_URL}`
      : process.env.VERCEL_URL
        ? `https://${process.env.VERCEL_URL}`
        : "http://localhost:3000")

  const supabase = await createClient()
  const { error } = await supabase.auth.resetPasswordForEmail(email, {
    redirectTo: `${origin.replace(/\/$/, "")}/api/auth/callback?next=/reset-password`,
  })

  if (error) {
    return {
      error: "Gagal mengirim tautan reset kata sandi. Silakan coba beberapa saat lagi.",
      values: { email },
    }
  }

  return {
    status:
      "We have emailed your password reset link.",
    values: { email: "" },
  }
}

export async function resetPasswordAction(
  _prevState: AuthActionState,
  formData: FormData
): Promise<AuthActionState> {
  const email = getString(formData, "email")
  const password = String(formData.get("password") ?? "")
  const password_confirmation = String(
    formData.get("password_confirmation") ?? ""
  )

  const fieldErrors: Record<string, string> = {}
  if (password.length < 8) {
    fieldErrors.password = "Kata sandi minimal harus 8 karakter."
  }
  if (password !== password_confirmation) {
    fieldErrors.password_confirmation = "Konfirmasi kata sandi tidak cocok."
  }
  if (Object.keys(fieldErrors).length > 0) {
    return { fieldErrors, values: { email } }
  }

  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()

  if (!claimsData?.claims?.sub) {
    return {
      error:
        "Tautan reset kata sandi tidak valid atau telah kedaluwarsa. Silakan minta tautan baru.",
      values: { email },
    }
  }

  const { error } = await supabase.auth.updateUser({ password })
  if (error) {
    return {
      error: error.message || "Gagal memperbarui kata sandi.",
      values: { email },
    }
  }

  await supabase.auth.signOut()
  revalidatePath("/", "layout")
  redirect("/login?status=Your+password+has+been+reset.")
}
