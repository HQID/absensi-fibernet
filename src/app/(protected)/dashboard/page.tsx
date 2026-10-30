import { Suspense } from "react"
import type { Metadata } from "next"
import { redirect } from "next/navigation"
import { DashboardSkeleton } from "@/components/dashboard/dashboard-skeleton"
import {
  AdminDashboardContent,
  StudentDashboardContent,
} from "@/components/dashboard/dashboard-views"
import { createClient } from "@/lib/supabase/server"

export async function generateMetadata(): Promise<Metadata> {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) return { title: "Dashboard" }

  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", userId)
    .maybeSingle()

  return {
    title: profile?.role === "student" ? "Student Dashboard" : "Admin Dashboard",
  }
}

export default async function DashboardPage() {
  const supabase = await createClient()
  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub

  if (!userId) {
    redirect("/login")
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("name, role")
    .eq("id", userId)
    .maybeSingle()

  if (!profile) {
    redirect("/login")
  }

  if (profile.role === "student") {
    return (
      <div className="space-y-6">
        <div className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
          <h1 className="text-2xl font-bold text-gray-900">
            Halo, {profile.name} 👋
          </h1>
          <p className="mt-1 text-sm text-gray-500">
            Berikut adalah riwayat presensi Anda.
          </p>
        </div>

        <Suspense fallback={<DashboardSkeleton role="student" />}>
          <StudentDashboardContent userId={userId} />
        </Suspense>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="h-10 w-10 rounded-xl bg-cyan-50 flex items-center justify-center">
            <svg
              className="w-5 h-5 text-cyan-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              />
            </svg>
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              Dashboard Presensi
            </h1>
            <p className="text-sm text-gray-500">
              Monitoring presensi peserta PKL PT Fiber Networks Indonesia Branch
              Palu.
            </p>
          </div>
        </div>
      </div>

      <Suspense fallback={<DashboardSkeleton role="admin" />}>
        <AdminDashboardContent />
      </Suspense>
    </div>
  )
}
