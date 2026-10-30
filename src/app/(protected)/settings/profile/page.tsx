import type { Metadata } from "next"
import { redirect } from "next/navigation"
import {
  ProfileSettingsForm,
  SettingsCardHeader,
} from "@/components/settings/settings-ui"
import { createClient } from "@/lib/supabase/server"

export const metadata: Metadata = {
  title: "Profile settings",
}

export default async function ProfileSettingsPage() {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    redirect("/login")
  }

  const [{ data: profile }, { data: student }] = await Promise.all([
    supabase
      .from("profiles")
      .select("name, email, role")
      .eq("id", userId)
      .maybeSingle(),
    supabase
      .from("students")
      .select("student_code, school, major")
      .eq("user_id", userId)
      .maybeSingle(),
  ])

  if (!profile) {
    redirect("/login")
  }

  return (
    <SettingsCardHeader
      heading="Profil"
      subheading="Perbarui data profil dan akun Anda"
    >
      <ProfileSettingsForm
        initialName={profile.name}
        email={profile.email}
        isStudent={profile.role === "student"}
        initialStudentCode={student?.student_code ?? ""}
        initialSchool={student?.school ?? ""}
        initialMajor={student?.major ?? ""}
      />
    </SettingsCardHeader>
  )
}
