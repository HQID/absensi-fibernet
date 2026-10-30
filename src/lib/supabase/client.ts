import { createBrowserClient } from "@supabase/ssr"
import type { Database } from "@/types/database.types"
import { getSupabasePublicEnv } from "./env"

export function createClient() {
  const { url, publishableKey } = getSupabasePublicEnv()
  return createBrowserClient<Database>(url, publishableKey)
}
