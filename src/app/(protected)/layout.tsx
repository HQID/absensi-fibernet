import type { ReactNode } from "react"
import { redirect } from "next/navigation"
import { AppShell } from "@/components/layout/app-shell"
import { createClient } from "@/lib/supabase/server"

export default async function ProtectedLayout({
  children,
}: {
  children: ReactNode
}) {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    redirect("/login")
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("name, email, role")
    .eq("id", userId)
    .maybeSingle()

  if (!profile) {
    redirect("/login")
  }

  return (
    <AppShell
      user={{
        name: profile.name,
        email: profile.email,
        role: profile.role,
      }}
    >
      {children}
    </AppShell>
  )
}
