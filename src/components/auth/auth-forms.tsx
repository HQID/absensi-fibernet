"use client"

import { useActionState, useEffect, useRef } from "react"
import Link from "next/link"
import {
  forgotPasswordAction,
  loginAction,
  registerAction,
  resetPasswordAction,
  type AuthActionState,
} from "@/actions/auth"
import { resetGlobalLoader } from "@/components/layout/global-loader"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"

const initialState: AuthActionState = {}

function useResetOverlayOnFinish(isPending: boolean, state: AuthActionState) {
  const wasPendingRef = useRef(false)

  useEffect(() => {
    if (isPending) {
      wasPendingRef.current = true
      return
    }
    if (wasPendingRef.current || state !== initialState) {
      wasPendingRef.current = false
      resetGlobalLoader()
    }
  }, [isPending, state])
}

function AuthHeader({
  title,
  description,
}: {
  title: string
  description: string
}) {
  return (
    <div className="flex w-full flex-col text-center">
      <h1 className="text-xl font-bold text-gray-900">{title}</h1>
      <p className="mt-1 text-sm text-zinc-500">{description}</p>
    </div>
  )
}

function StatusAlert({ status }: { status?: string }) {
  if (!status) return null
  return (
    <div className="text-center text-sm font-medium text-green-600">
      {status}
    </div>
  )
}

function ErrorAlert({ error }: { error?: string }) {
  if (!error) return null
  return (
    <div className="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-center text-sm font-medium text-rose-700">
      {error}
    </div>
  )
}

export function LoginForm({
  initialError,
  initialStatus,
}: {
  initialError?: string
  initialStatus?: string
}) {
  const [state, formAction, isPending] = useActionState(
    loginAction,
    initialState
  )

  useResetOverlayOnFinish(isPending, state)

  const displayError = state.error ?? initialError
  const displayStatus = state.status ?? initialStatus

  return (
    <div className="flex flex-col gap-6">
      <AuthHeader
        title="Sistem Presensi PKL"
        description="Silakan masuk menggunakan email dan kata sandi Anda."
      />

      <StatusAlert status={displayStatus} />
      <ErrorAlert error={displayError} />

      <form
        action={formAction}
        data-loading-text="Sedang masuk..."
        className="flex flex-col gap-6"
      >
        <Input
          name="email"
          label="Email address"
          type="email"
          required
          autoFocus
          autoComplete="email"
          placeholder="email@example.com"
          defaultValue={state.values?.email ?? ""}
          error={state.fieldErrors?.email}
        />

        <div className="relative">
          <Input
            name="password"
            label="Password"
            type="password"
            required
            autoComplete="current-password"
            placeholder="Password"
            viewable
            error={state.fieldErrors?.password}
          />
          <Link
            href="/forgot-password"
            className="absolute top-0 end-0 text-sm font-medium text-zinc-700 underline underline-offset-4 hover:text-zinc-900"
          >
            Forgot your password?
          </Link>
        </div>

        <label className="inline-flex items-center gap-2 text-sm text-zinc-700 select-none cursor-pointer">
          <input
            type="checkbox"
            name="remember"
            className="h-4 w-4 rounded border-zinc-300 text-[#0891b2] focus:ring-[#0891b2]"
          />
          <span>Remember me</span>
        </label>

        <div className="flex items-center justify-end">
          <Button
            variant="primary"
            type="submit"
            loading={isPending}
            className="w-full"
            data-test="login-button"
          >
            Masuk
          </Button>
        </div>
      </form>

      <div className="space-x-1 text-center text-sm text-zinc-600">
        <span>Belum memiliki akun?</span>
        <Link
          href="/register"
          className="font-medium text-zinc-800 underline underline-offset-4 hover:text-zinc-950"
        >
          Daftar sekarang
        </Link>
      </div>
    </div>
  )
}

export function RegisterForm() {
  const [state, formAction, isPending] = useActionState(
    registerAction,
    initialState
  )

  useResetOverlayOnFinish(isPending, state)

  return (
    <div className="flex flex-col gap-6">
      <AuthHeader
        title="Pendaftaran Peserta PKL"
        description="Isi formulir di bawah ini untuk mendaftarkan akun."
      />

      <StatusAlert status={state.status} />
      <ErrorAlert error={state.error} />

      <form
        action={formAction}
        data-loading-text="Mendaftarkan akun..."
        className="flex flex-col gap-6"
      >
        <Input
          name="name"
          label="Nama Lengkap"
          type="text"
          required
          autoFocus
          autoComplete="name"
          placeholder="Nama lengkap Anda"
          defaultValue={state.values?.name ?? ""}
          error={state.fieldErrors?.name}
        />

        <Input
          name="email"
          label="Alamat Email"
          type="email"
          required
          autoComplete="email"
          placeholder="email@example.com"
          defaultValue={state.values?.email ?? ""}
          error={state.fieldErrors?.email}
        />

        <Input
          name="student_code"
          label="NIM / NISN"
          type="text"
          required
          placeholder="Nomor Induk Mahasiswa / Siswa"
          defaultValue={state.values?.student_code ?? ""}
          error={state.fieldErrors?.student_code}
        />

        <Input
          name="school"
          label="Asal Sekolah / Kampus"
          type="text"
          required
          placeholder="Contoh: Universitas Tadulako"
          defaultValue={state.values?.school ?? ""}
          error={state.fieldErrors?.school}
        />

        <Input
          name="major"
          label="Jurusan"
          type="text"
          required
          placeholder="Contoh: Teknik Informatika"
          defaultValue={state.values?.major ?? ""}
          error={state.fieldErrors?.major}
        />

        <div className="grid grid-cols-2 gap-4">
          <Input
            name="start_date"
            label="Tanggal Mulai PKL"
            type="date"
            required
            defaultValue={state.values?.start_date ?? ""}
            error={state.fieldErrors?.start_date}
          />
          <Input
            name="end_date"
            label="Tanggal Selesai PKL"
            type="date"
            required
            defaultValue={state.values?.end_date ?? ""}
            error={state.fieldErrors?.end_date}
          />
        </div>

        <Input
          name="password"
          label="Kata Sandi"
          type="password"
          required
          autoComplete="new-password"
          placeholder="Minimal 8 karakter"
          viewable
          error={state.fieldErrors?.password}
        />

        <Input
          name="password_confirmation"
          label="Konfirmasi Kata Sandi"
          type="password"
          required
          autoComplete="new-password"
          placeholder="Ulangi kata sandi"
          viewable
          error={state.fieldErrors?.password_confirmation}
        />

        <div className="flex items-center justify-end">
          <Button
            type="submit"
            variant="primary"
            loading={isPending}
            className="w-full"
            data-test="register-user-button"
          >
            Daftar Akun
          </Button>
        </div>
      </form>

      <div className="space-x-1 text-center text-sm text-zinc-600">
        <span>Sudah memiliki akun?</span>
        <Link
          href="/login"
          className="font-medium text-zinc-800 underline underline-offset-4 hover:text-zinc-950"
        >
          Masuk di sini
        </Link>
      </div>
    </div>
  )
}

export function ForgotPasswordForm() {
  const [state, formAction, isPending] = useActionState(
    forgotPasswordAction,
    initialState
  )

  useResetOverlayOnFinish(isPending, state)

  return (
    <div className="flex flex-col gap-6">
      <AuthHeader
        title="Forgot password"
        description="Enter your email to receive a password reset link"
      />

      <StatusAlert status={state.status} />
      <ErrorAlert error={state.error} />

      <form
        action={formAction}
        data-loading-text="Memproses kata sandi..."
        className="flex flex-col gap-6"
      >
        <Input
          name="email"
          label="Email address"
          type="email"
          required
          autoFocus
          placeholder="email@example.com"
          defaultValue={state.values?.email ?? ""}
          error={state.fieldErrors?.email}
        />

        <Button
          variant="primary"
          type="submit"
          loading={isPending}
          className="w-full"
          data-test="email-password-reset-link-button"
        >
          Email password reset link
        </Button>
      </form>

      <div className="space-x-1 text-center text-sm text-zinc-400">
        <span>Or, return to</span>
        <Link
          href="/login"
          className="font-medium text-zinc-700 underline underline-offset-4 hover:text-zinc-900"
        >
          log in
        </Link>
      </div>
    </div>
  )
}

export function ResetPasswordForm({ initialEmail }: { initialEmail?: string }) {
  const [state, formAction, isPending] = useActionState(
    resetPasswordAction,
    initialState
  )

  useResetOverlayOnFinish(isPending, state)

  return (
    <div className="flex flex-col gap-6">
      <AuthHeader
        title="Reset password"
        description="Please enter your new password below"
      />

      <StatusAlert status={state.status} />
      <ErrorAlert error={state.error} />

      <form
        action={formAction}
        data-loading-text="Memproses kata sandi..."
        className="flex flex-col gap-6"
      >
        <Input
          name="email"
          label="Email"
          type="email"
          required
          autoComplete="email"
          defaultValue={state.values?.email ?? initialEmail ?? ""}
          error={state.fieldErrors?.email}
        />

        <Input
          name="password"
          label="Password"
          type="password"
          required
          autoComplete="new-password"
          placeholder="Password"
          viewable
          error={state.fieldErrors?.password}
        />

        <Input
          name="password_confirmation"
          label="Confirm password"
          type="password"
          required
          autoComplete="new-password"
          placeholder="Confirm password"
          viewable
          error={state.fieldErrors?.password_confirmation}
        />

        <div className="flex items-center justify-end">
          <Button
            type="submit"
            variant="primary"
            loading={isPending}
            className="w-full"
            data-test="reset-password-button"
          >
            Reset password
          </Button>
        </div>
      </form>
    </div>
  )
}
