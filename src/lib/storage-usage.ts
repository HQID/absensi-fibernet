import { unstable_cache, updateTag } from "next/cache"
import { createAdminClient } from "@/lib/supabase/admin"
import { formatBytes, formatCheckedAtWita } from "@/lib/time"
import type { BucketStorageStats } from "@/types/database.types"

export { formatBytes }

export type StorageStatsView = {
  disk: string
  driver: string
  bucket: string | null
  total_bytes: number
  quota_mb: number
  quota_bytes: number
  percent_used: number
  active_count: number
  active_bytes: number
  temp_count: number
  temp_bytes: number
  orphan_count: number
  orphan_bytes: number
  cleanable_count: number
  cleanable_bytes: number
  checked_at: string
  error: string | null
}

async function fetchUncachedStorageStats(): Promise<StorageStatsView> {
  const bucket = process.env.SUPABASE_STORAGE_BUCKET || "photos"
  const rawQuota = Number(process.env.STORAGE_QUOTA_MB || 1024)
  const quotaMb = Math.max(1, Number.isFinite(rawQuota) ? Math.trunc(rawQuota) : 1024)
  const quotaBytes = quotaMb * 1024 * 1024

  try {
    const supabaseAdmin = createAdminClient()
    const { data, error } = await supabaseAdmin.rpc("get_bucket_storage_stats", {
      p_bucket: bucket,
    })

    if (error) {
      throw new Error(error.message)
    }

    const parsed = (data ?? {}) as Partial<BucketStorageStats>
    const totalBytes = Math.max(0, Number(parsed.total_bytes ?? 0))

    return {
      disk: "supabase",
      driver: "s3",
      bucket,
      total_bytes: totalBytes,
      quota_mb: quotaMb,
      quota_bytes: quotaBytes,
      percent_used: Math.round((totalBytes / quotaBytes) * 10000) / 100,
      active_count: Math.max(0, Number(parsed.active_count ?? 0)),
      active_bytes: Math.max(0, Number(parsed.active_bytes ?? 0)),
      temp_count: Math.max(0, Number(parsed.temp_count ?? 0)),
      temp_bytes: Math.max(0, Number(parsed.temp_bytes ?? 0)),
      orphan_count: Math.max(0, Number(parsed.orphan_count ?? 0)),
      orphan_bytes: Math.max(0, Number(parsed.orphan_bytes ?? 0)),
      cleanable_count: Math.max(0, Number(parsed.cleanable_count ?? 0)),
      cleanable_bytes: Math.max(0, Number(parsed.cleanable_bytes ?? 0)),
      checked_at: formatCheckedAtWita(),
      error: null,
    }
  } catch (err) {
    const message = err instanceof Error ? err.message : "Unknown error"
    return {
      disk: "supabase",
      driver: "s3",
      bucket,
      total_bytes: 0,
      quota_mb: quotaMb,
      quota_bytes: quotaBytes,
      percent_used: 0,
      active_count: 0,
      active_bytes: 0,
      temp_count: 0,
      temp_bytes: 0,
      orphan_count: 0,
      orphan_bytes: 0,
      cleanable_count: 0,
      cleanable_bytes: 0,
      checked_at: formatCheckedAtWita(),
      error: `Gagal membaca informasi storage: ${message}`,
    }
  }
}

const getCachedStorageStats = unstable_cache(
  fetchUncachedStorageStats,
  ["fibernet-storage-stats"],
  {
    revalidate: 600,
    tags: ["storage-stats"],
  }
)

export async function getStorageStats(): Promise<StorageStatsView> {
  return getCachedStorageStats()
}

type BucketFileEntry = {
  path: string
  size: number
  lastModifiedMs: number | null
}

async function listBucketFilesRecursive(
  supabaseAdmin: ReturnType<typeof createAdminClient>,
  bucket: string,
  prefix = ""
): Promise<BucketFileEntry[]> {
  const results: BucketFileEntry[] = []
  let offset = 0
  const limit = 1000

  while (true) {
    const { data, error } = await supabaseAdmin.storage
      .from(bucket)
      .list(prefix, { limit, offset })

    if (error || !data || data.length === 0) {
      break
    }

    for (const item of data) {
      if (!item.name || item.name.startsWith(".")) {
        continue
      }
      const fullPath = prefix ? `${prefix}/${item.name}` : item.name
      if (item.id === null) {
        const nested = await listBucketFilesRecursive(
          supabaseAdmin,
          bucket,
          fullPath
        )
        results.push(...nested)
      } else {
        const size = Math.max(0, Number(item.metadata?.size ?? 0))
        const rawTime = item.updated_at || item.created_at
        const parsedMs = rawTime ? new Date(rawTime).getTime() : NaN
        results.push({
          path: fullPath,
          size,
          lastModifiedMs: Number.isFinite(parsedMs) ? parsedMs : null,
        })
      }
    }

    if (data.length < limit) {
      break
    }
    offset += limit
  }

  return results
}

export async function cleanupUnusedStorageFiles(): Promise<{
  deleted_count: number
  freed_bytes: number
}> {
  const supabaseAdmin = createAdminClient()
  const bucket = process.env.SUPABASE_STORAGE_BUCKET || "photos"

  const { data: attendanceRows } = await supabaseAdmin
    .from("attendances")
    .select("check_in_photo_path")
    .not("check_in_photo_path", "is", null)

  const knownPaths = new Set(
    (attendanceRows ?? [])
      .map((r) => (r.check_in_photo_path ?? "").trim().replace(/^\/+/, ""))
      .filter((p) => p.length > 0)
  )

  const cutoffMs = Date.now() - 15 * 60_000
  const allFiles = await listBucketFilesRecursive(supabaseAdmin, bucket)

  const targets = allFiles.filter((file) => {
    if (file.lastModifiedMs !== null && file.lastModifiedMs > cutoffMs) {
      return false
    }
    const isTemp = file.path.startsWith("livewire-tmp/")
    const isOrphanAttendance =
      file.path.startsWith("attendance/") && !knownPaths.has(file.path)
    return isTemp || isOrphanAttendance
  })

  let deletedCount = 0
  let freedBytes = 0

  for (let i = 0; i < targets.length; i += 100) {
    const chunk = targets.slice(i, i + 100)
    const { error } = await supabaseAdmin.storage
      .from(bucket)
      .remove(chunk.map((item) => item.path))

    if (!error) {
      deletedCount += chunk.length
      freedBytes += chunk.reduce((sum, item) => sum + item.size, 0)
    }
  }

  updateTag("storage-stats")

  return {
    deleted_count: deletedCount,
    freed_bytes: freedBytes,
  }
}
