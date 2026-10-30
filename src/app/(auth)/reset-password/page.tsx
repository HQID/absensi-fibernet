import type { Metadata } from "next"
import { ResetPasswordForm } from "@/components/auth/auth-forms"
import { createClient } from "@/lib/supabase/server"

export const metadata: Metadata = {
  title: "Reset password",
}

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ email?: string }>
}) {
  const [params, supabase] = await Promise.all([searchParams, createClient()])
  const { data: claimsData } = await supabase.auth.getClaims()
  const claimEmail =
    typeof claimsData?.claims?.email === "string"
      ? claimsData.claims.email
      : undefined

  return <ResetPasswordForm initialEmail={params.email ?? claimEmail} />
}
