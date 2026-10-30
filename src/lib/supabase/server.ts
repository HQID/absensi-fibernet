import { createServerClient } from "@supabase/ssr"
import { cookies } from "next/headers"
import type { Database } from "@/types/database.types"
import { getSupabasePublicEnv } from "./env"

export const SESSION_ONLY_COOKIE = "fibernet_session_only"

export async function createClient(options?: { sessionOnly?: boolean }) {
  const cookieStore = await cookies()
  const { url, publishableKey } = getSupabasePublicEnv()
  const isSessionOnly =
    options?.sessionOnly ??
    cookieStore.get(SESSION_ONLY_COOKIE)?.value === "1"

  return createServerClient<Database>(url, publishableKey, {
    cookies: {
      getAll() {
        return cookieStore.getAll()
      },
      setAll(cookiesToSet) {
        try {
          cookiesToSet.forEach(({ name, value, options: cookieOpts }) => {
            if (isSessionOnly && value !== "" && cookieOpts?.maxAge !== 0) {
              const sessionOpts = { ...cookieOpts }
              delete sessionOpts.maxAge
              delete sessionOpts.expires
              cookieStore.set(name, value, sessionOpts)
            } else {
              cookieStore.set(name, value, cookieOpts)
            }
          })
        } catch {
          // Server Component read-only context; refreshed via src/proxy.ts.
        }
      },
    },
  })
}
