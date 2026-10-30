import type { Metadata } from "next"
import { LoginForm } from "@/components/auth/auth-forms"

export const metadata: Metadata = {
  title: "Masuk Aplikasi",
}

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ error?: string; status?: string }>
}) {
  const params = await searchParams
  return (
    <LoginForm initialError={params.error} initialStatus={params.status} />
  )
}
