import type { ReactNode } from "react"
import { redirect } from "next/navigation"
import { SettingsNavClient } from "@/components/settings/settings-ui"
import { createClient } from "@/lib/supabase/server"

export default async function SettingsLayout({
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
    .select("role")
    .eq("id", userId)
    .maybeSingle()

  const isAdmin = profile?.role === "admin"

  return (
    <section className="w-full">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-gray-900">Profil</h1>
        <p className="mt-1 text-sm text-gray-500">
          Kelola profil dan pengaturan akun Anda
        </p>
      </div>

      <div className="flex items-start max-md:flex-col">
        <div className="me-10 w-full pb-4 md:w-[220px]">
          <SettingsNavClient isAdmin={isAdmin} />
        </div>

        <hr className="w-full border-gray-200 md:hidden" />

        <div className="flex-1 self-stretch max-md:pt-6">
          <div className="fibernet-card p-6">{children}</div>
        </div>
      </div>
    </section>
  )
}
