import { createServerClient } from "@supabase/ssr"
import { NextResponse, type NextRequest } from "next/server"
import type { Database } from "@/types/database.types"
import { validateStudentActive } from "@/lib/student-guard"
import { getSupabasePublicEnv } from "./env"

const GUEST_ONLY_PATHS = ["/login", "/register", "/forgot-password"]
const PUBLIC_PATHS = ["/offline", "/api/auth/callback", "/reset-password"]

function isPathOrSubpath(pathname: string, base: string): boolean {
  return pathname === base || pathname.startsWith(`${base}/`)
}

function isAdminOnlyPath(pathname: string): boolean {
  return (
    isPathOrSubpath(pathname, "/students") ||
    isPathOrSubpath(pathname, "/reports") ||
    isPathOrSubpath(pathname, "/api/reports") ||
    isPathOrSubpath(pathname, "/settings/geofence") ||
    isPathOrSubpath(pathname, "/settings/storage")
  )
}

function redirectWithCookies(
  targetUrl: URL,
  sourceResponse: NextResponse
): NextResponse {
  const redirectResponse = NextResponse.redirect(targetUrl)
  sourceResponse.cookies.getAll().forEach((cookie) => {
    redirectResponse.cookies.set(cookie)
  })
  return redirectResponse
}

export async function updateSession(request: NextRequest) {
  let supabaseResponse = NextResponse.next({ request })
  const { url, publishableKey } = getSupabasePublicEnv()
  const isSessionOnly =
    request.cookies.get("fibernet_session_only")?.value === "1"

  const supabase = createServerClient<Database>(url, publishableKey, {
    cookies: {
      getAll() {
        return request.cookies.getAll()
      },
      setAll(cookiesToSet, headers) {
        cookiesToSet.forEach(({ name, value }) =>
          request.cookies.set(name, value)
        )
        supabaseResponse = NextResponse.next({ request })
        cookiesToSet.forEach(({ name, value, options }) => {
          if (isSessionOnly && value !== "" && options?.maxAge !== 0) {
            const sessionOpts = { ...options }
            delete sessionOpts.maxAge
            delete sessionOpts.expires
            supabaseResponse.cookies.set(name, value, sessionOpts)
          } else {
            supabaseResponse.cookies.set(name, value, options)
          }
        })
        Object.entries(headers).forEach(([key, value]) =>
          supabaseResponse.headers.set(key, value)
        )
      },
    },
  })

  const { data: claimsData } = await supabase.auth.getClaims()
  const userId = claimsData?.claims?.sub ?? null
  const { pathname } = request.nextUrl

  if (PUBLIC_PATHS.some((p) => isPathOrSubpath(pathname, p))) {
    return supabaseResponse
  }

  if (GUEST_ONLY_PATHS.some((p) => isPathOrSubpath(pathname, p))) {
    if (userId) {
      return redirectWithCookies(
        new URL("/dashboard", request.url),
        supabaseResponse
      )
    }
    return supabaseResponse
  }

  if (pathname === "/") {
    return redirectWithCookies(
      new URL(userId ? "/dashboard" : "/login", request.url),
      supabaseResponse
    )
  }

  if (!userId) {
    if (pathname.startsWith("/api/")) {
      return NextResponse.json({ message: "Unauthenticated." }, { status: 401 })
    }
    return redirectWithCookies(new URL("/login", request.url), supabaseResponse)
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", userId)
    .maybeSingle()

  if (!profile) {
    await supabase.auth.signOut()
    const loginUrl = new URL("/login", request.url)
    loginUrl.searchParams.set("error", "Profil pengguna tidak ditemukan.")
    return redirectWithCookies(loginUrl, supabaseResponse)
  }

  if (isAdminOnlyPath(pathname) && profile.role !== "admin") {
    return new NextResponse("403 | Forbidden", { status: 403 })
  }

  if (profile.role === "student") {
    const { data: student } = await supabase
      .from("students")
      .select("status, end_date")
      .eq("user_id", userId)
      .maybeSingle()

    const guard = validateStudentActive(student)
    if (!guard.active) {
      await supabase.auth.signOut()
      const loginUrl = new URL("/login", request.url)
      loginUrl.searchParams.set("error", guard.message)
      return redirectWithCookies(loginUrl, supabaseResponse)
    }
  }

  return supabaseResponse
}
