import { createClient as createSupabaseClient } from "@supabase/supabase-js"
import type { Database } from "@/types/database.types"
import { getSupabasePublicEnv, getSupabaseSecretKey } from "./env"

export function createAdminClient() {
  const { url } = getSupabasePublicEnv()
  const secretKey = getSupabaseSecretKey()

  return createSupabaseClient<Database>(url, secretKey, {
    auth: {
      autoRefreshToken: false,
      persistSession: false,
    },
  })
}
