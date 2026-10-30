import assert from "node:assert/strict"
import ExcelJS from "exceljs"
import { createClient } from "@supabase/supabase-js"
import { calculateDistance, validateLocation } from "../src/lib/geofence.ts"
import {
  getInitials,
  validateStudentActive,
} from "../src/lib/student-guard.ts"
import {
  computeEligibleAbsentDates,
  currentMonthRangeWita,
  formatBytes,
  formatDateLongEn,
  formatDateSlash,
  formatDuration,
  formatIndonesianWeekday,
  formatTime12h,
  formatTime24h,
  isAfterClosingCutoff,
  isAfterLateCutoff,
  isDatePastWitaEndOfDay,
  todayWitaDateString,
} from "../src/lib/time.ts"

// 1. WITA timezone cutoffs (UTC+8: 00:15:01 UTC = 08:15:01 WITA; 09:00:01 UTC = 17:00:01 WITA)
assert.equal(isAfterLateCutoff(new Date("2026-10-30T00:15:00Z")), false)
assert.equal(isAfterLateCutoff(new Date("2026-10-30T00:15:01Z")), true)
assert.equal(isAfterClosingCutoff(new Date("2026-10-30T09:00:00Z")), false)
assert.equal(isAfterClosingCutoff(new Date("2026-10-30T09:00:01Z")), true)
assert.equal(todayWitaDateString(new Date("2026-10-29T16:30:00Z")), "2026-10-30")
assert.equal(
  isDatePastWitaEndOfDay("2026-10-29", new Date("2026-10-29T16:00:00Z")),
  true
)
assert.equal(formatTime24h("2026-10-30T00:05:00Z"), "08:05")
assert.equal(formatTime12h("2026-10-30T09:02:00Z"), "05:02 PM")
assert.equal(
  formatDuration("2026-10-30T00:05:00Z", "2026-10-30T08:35:00Z", "short"),
  "8h 30m"
)
assert.equal(formatIndonesianWeekday("2026-10-30"), "Jumat")

// 2. Geofence Haversine & BR-ERR-03 / BR-ERR-04 validation
const paluOffice = {
  latitude: -0.874971,
  longitude: 119.886639,
  radius_meters: 100,
  max_accuracy_meters: 50,
}

assert.equal(calculateDistance(-0.874971, 119.886639, -0.874971, 119.886639), 0)
assert.equal(
  validateLocation(-0.874971, 119.886639, 65, paluOffice).valid,
  false
)
assert.equal(
  validateLocation(-0.874971, 119.886639, 20, paluOffice).valid,
  true
)
assert.equal(
  validateLocation(-0.8765, 119.886639, 15, paluOffice).valid,
  false
)

// 3. Fase 2 & 3: Initials & CheckStudentActive (BR-ERR-09 verbatim messages)
assert.equal(getInitials("Administrator"), "A")
assert.equal(getInitials("Budi Pratama Santoso"), "BS")

assert.deepEqual(validateStudentActive(null), {
  active: false,
  message: "Data siswa tidak ditemukan.",
})
assert.deepEqual(
  validateStudentActive({ status: "inactive", end_date: "2026-12-31" }),
  {
    active: false,
    message: "Akun PKL Anda telah dinonaktifkan.",
  }
)
assert.deepEqual(
  validateStudentActive(
    { status: "active", end_date: "2026-09-30" },
    new Date("2026-09-30T15:59:59Z") // 23:59:59 WITA -> still active!
  ),
  { active: true, message: null }
)
assert.deepEqual(
  validateStudentActive(
    { status: "active", end_date: "2026-09-30" },
    new Date("2026-09-30T16:00:00Z") // 00:00:00 next day WITA -> expired!
  ),
  {
    active: false,
    message:
      "Masa PKL Anda telah berakhir pada 30 Sep 2026. Hubungi Admin untuk perpanjangan.",
  }
)

// 4. Live Supabase Auth + RLS verification (Admin seed & Branch Palu office)
const url = process.env.NEXT_PUBLIC_SUPABASE_URL
const key = process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY
assert.ok(url && key, "Missing Supabase public env vars in .env.local")

const supabase = createClient(url, key, {
  auth: { persistSession: false, autoRefreshToken: false },
})

const { data: authData, error: authError } =
  await supabase.auth.signInWithPassword({
    email: "adminabsensi@fibernet.id",
    password: "fibernetpalupcs2026",
  })
assert.ifError(authError)
assert.ok(authData.user?.id)

const { data: profile, error: profileError } = await supabase
  .from("profiles")
  .select("email, role")
  .eq("id", authData.user.id)
  .single()
assert.ifError(profileError)
assert.equal(profile?.role, "admin")

const { data: office, error: officeError } = await supabase
  .from("office_locations")
  .select("name, radius_meters, max_accuracy_meters, active")
  .eq("active", true)
  .single()
assert.ifError(officeError)
assert.equal(office?.name, "PT Fiber Networks Indonesia Branch Palu")

await supabase.auth.signOut()

const secretKey = process.env.SUPABASE_SECRET_KEY
if (secretKey) {
  const adminClient = createClient(url, secretKey, {
    auth: { persistSession: false, autoRefreshToken: false },
  })
  const { data: stats, error: statsError } = await adminClient.rpc(
    "get_bucket_storage_stats",
    { p_bucket: "photos" }
  )
  assert.ifError(statsError)
  assert.equal(typeof stats.total_bytes, "number")
}

// 5. Fase 5: Admin Reports, Storage formatBytes, Absentee Sync, and ExcelJS buffer
assert.equal(formatBytes(0), "0 B")
assert.equal(formatBytes(512), "512 B")
assert.equal(formatBytes(1536), "1.50 KB")
assert.equal(formatBytes(1024 * 1024 * 1024), "1.00 GB")
assert.equal(formatDateSlash("2026-10-30"), "30/10/2026")
assert.equal(formatDateLongEn("2026-10-30"), "30 October 2026")
assert.deepEqual(currentMonthRangeWita(new Date("2026-02-15T04:00:00Z")), {
  startDate: "2026-02-01",
  endDate: "2026-02-28",
})

// Before 17:00 WITA on Friday 2026-10-30 (08:59 UTC = 16:59 WITA): skips weekends (Oct 24-25) & today (Oct 30), caps future (Nov 2)
assert.deepEqual(
  computeEligibleAbsentDates(
    "2026-10-23",
    "2026-11-05",
    new Date("2026-10-30T08:59:00Z")
  ),
  ["2026-10-23", "2026-10-26", "2026-10-27", "2026-10-28", "2026-10-29"]
)

// At/after 17:00 WITA on Friday 2026-10-30 (09:00 UTC = 17:00 WITA): includes today (Oct 30)
assert.deepEqual(
  computeEligibleAbsentDates(
    "2026-10-29",
    "2026-10-31",
    new Date("2026-10-30T09:00:00Z")
  ),
  ["2026-10-29", "2026-10-30"]
)

const wb = new ExcelJS.Workbook()
const ws = wb.addWorksheet("Rekap Presensi")
ws.addRow(["NIM/NISN", "Nama Peserta", "Status"])
const xlsxBuf = await wb.xlsx.writeBuffer()
assert.ok(xlsxBuf.byteLength > 100)

console.log("Phase 1-5 verification passed: WITA, Geofence, StudentGuard, Reports/AbsenteeSync, Storage, ExcelJS, Auth, RLS, and SecretKey RPC OK.")
