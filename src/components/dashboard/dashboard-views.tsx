import Link from "next/link"
import { LiveClock } from "@/components/dashboard/live-clock"
import { createClient } from "@/lib/supabase/server"
import {
  APP_TIMEZONE,
  formatDateShort,
  formatDuration,
  formatIndonesianFullDate,
  formatTime12h,
  formatTime24h,
  todayWitaDateString,
} from "@/lib/time"

type NestedStudent = {
  student_code: string
  school: string
  profiles: { name: string } | { name: string }[] | null
}

function unwrapOne<T>(val: T | T[] | null | undefined): T | null {
  if (!val) return null
  return Array.isArray(val) ? (val[0] ?? null) : val
}

export async function AdminDashboardContent() {
  const supabase = await createClient()
  const today = todayWitaDateString()

  const [{ count: activeStudentsCount }, { data: rows }] = await Promise.all([
    supabase
      .from("students")
      .select("id", { count: "exact", head: true })
      .eq("status", "active"),
    supabase
      .from("attendances")
      .select(
        "id, check_in_at, check_out_at, status, location_type, students(student_code, school, profiles(name))"
      )
      .eq("attendance_date", today)
      .order("id", { ascending: true }),
  ])

  const totalStudents = activeStudentsCount ?? 0
  const attendancesToday = rows ?? []

  const presentCount = attendancesToday.filter(
    (a) => a.status === "present"
  ).length
  const lateCount = attendancesToday.filter((a) => a.status === "late").length
  const absentCount = Math.max(0, totalStudents - (presentCount + lateCount))

  return (
    <div className="space-y-6">
      {/* Summary Cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="card-soft-cyan">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-2 text-gray-700 font-medium">
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
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
              <span>Total Peserta PKL</span>
            </div>
          </div>
          <div className="mt-4">
            <p className="text-3xl font-bold text-gray-900">{totalStudents}</p>
          </div>
        </div>

        <div className="card-soft-white">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-2 text-gray-700 font-medium">
              <svg
                className="w-5 h-5 text-emerald-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span>Hadir Tepat Waktu</span>
            </div>
          </div>
          <div className="mt-4">
            <p className="text-3xl font-bold text-gray-900">{presentCount}</p>
          </div>
        </div>

        <div className="card-soft-white">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-2 text-gray-700 font-medium">
              <svg
                className="w-5 h-5 text-amber-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span>Terlambat</span>
            </div>
          </div>
          <div className="mt-4">
            <p className="text-3xl font-bold text-gray-900">{lateCount}</p>
          </div>
        </div>

        <div className="card-soft-rose">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-2 text-gray-700 font-medium">
              <svg
                className="w-5 h-5 text-rose-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span>Tidak Hadir / Belum</span>
            </div>
          </div>
          <div className="mt-4">
            <p className="text-3xl font-bold text-gray-900">{absentCount}</p>
          </div>
        </div>
      </div>

      {/* Table Presensi Hari Ini */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
          <div className="flex items-center gap-3">
            <div className="h-8 w-8 rounded-lg bg-cyan-50 flex items-center justify-center">
              <svg
                className="w-4 h-4 text-cyan-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
              </svg>
            </div>
            <h2 className="font-semibold text-gray-900">
              Daftar Presensi Hari Ini
            </h2>
          </div>
          <Link
            href="/reports"
            className="inline-flex items-center gap-1 rounded-lg bg-cyan-50 px-3 py-1.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100 transition-colors"
          >
            Lihat Semua
            <svg
              className="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Link>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="fibernet-table-header">
              <tr>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Nama Peserta
                </th>
                <th
                  scope="col"
                  className="px-6 py-4 font-semibold text-gray-900 w-1/3"
                >
                  Check-In &amp; Out
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Sekolah/Kampus
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Jenis
                </th>
                <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                  Status
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {attendancesToday.length > 0 ? (
                attendancesToday.map((attendance) => {
                  const student = unwrapOne(
                    attendance.students as NestedStudent | NestedStudent[] | null
                  )
                  const profile = unwrapOne(student?.profiles)

                  return (
                    <tr
                      key={attendance.id}
                      className="hover:bg-gray-50/50 transition-colors"
                    >
                      <td className="px-6 py-4">
                        <div className="font-semibold text-gray-900">
                          {profile?.name ?? "-"}
                        </div>
                        <div className="text-xs text-gray-500">
                          {student?.student_code ?? "-"}
                        </div>
                      </td>

                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          {attendance.check_in_at ? (
                            <span
                              className={`font-medium whitespace-nowrap ${
                                attendance.status === "late"
                                  ? "text-rose-600"
                                  : "text-gray-900"
                              }`}
                            >
                              {formatTime12h(attendance.check_in_at)}
                            </span>
                          ) : (
                            <span className="text-gray-400 font-medium whitespace-nowrap">
                              --:--
                            </span>
                          )}

                          <div className="flex-1 flex items-center min-w-[80px]">
                            <div
                              className={`w-2 h-2 rounded-full ${
                                attendance.check_in_at
                                  ? "bg-gray-400"
                                  : "bg-gray-200"
                              }`}
                            />
                            <div
                              className={`h-px flex-1 relative ${
                                attendance.check_in_at
                                  ? "bg-gray-400"
                                  : "bg-gray-200"
                              }`}
                            >
                              {attendance.check_in_at &&
                                attendance.check_out_at && (
                                  <span className="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 bg-white px-2 text-xs text-gray-400 whitespace-nowrap">
                                    {formatDuration(
                                      attendance.check_in_at,
                                      attendance.check_out_at,
                                      "short"
                                    )}
                                  </span>
                                )}
                            </div>
                            <div
                              className={`w-2 h-2 rounded-full border-2 bg-white ${
                                attendance.check_out_at
                                  ? "border-gray-400"
                                  : "border-gray-200"
                              }`}
                            />
                          </div>

                          {attendance.check_out_at ? (
                            <span className="text-gray-400 font-medium whitespace-nowrap">
                              {formatTime12h(attendance.check_out_at)}
                            </span>
                          ) : (
                            <span className="text-gray-400 font-medium whitespace-nowrap">
                              --:--
                            </span>
                          )}
                        </div>
                      </td>

                      <td className="px-6 py-4 text-gray-500 font-medium text-sm">
                        {student?.school ?? "-"}
                      </td>

                      <td className="px-6 py-4">
                        {attendance.location_type === "outside" ? (
                          <span className="text-orange-600 font-medium text-sm">
                            Luar Kantor
                          </span>
                        ) : (
                          <span className="text-gray-700 font-medium text-sm">
                            Dalam Kantor
                          </span>
                        )}
                      </td>

                      <td className="px-6 py-4">
                        {attendance.status === "present" ? (
                          <span className="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">
                            HADIR
                          </span>
                        ) : attendance.status === "late" ? (
                          <span className="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 uppercase">
                            TERLAMBAT
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">
                            ABSEN
                          </span>
                        )}
                      </td>
                    </tr>
                  )
                })
              ) : (
                <tr>
                  <td colSpan={5} className="px-6 py-16 text-center">
                    <div className="flex flex-col items-center">
                      <div className="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                        <svg
                          className="w-6 h-6 text-gray-400"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                          />
                        </svg>
                      </div>
                      <p className="text-sm text-gray-500">
                        Belum ada yang melakukan presensi hari ini.
                      </p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export async function StudentDashboardContent({ userId }: { userId: string }) {
  const supabase = await createClient()
  const now = new Date()
  const today = todayWitaDateString(now)
  const initialClock = now.toLocaleTimeString("id-ID", {
    timeZone: APP_TIMEZONE,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })

  const { data: student } = await supabase
    .from("students")
    .select("id, student_code, school, major, start_date, end_date")
    .eq("user_id", userId)
    .maybeSingle()

  const [{ data: recentAttendances }, { data: todayAttendance }] = student
    ? await Promise.all([
        supabase
          .from("attendances")
          .select("id, attendance_date, check_in_at, check_out_at, status")
          .eq("student_id", student.id)
          .order("attendance_date", { ascending: false })
          .limit(10),
        supabase
          .from("attendances")
          .select("id, status")
          .eq("student_id", student.id)
          .eq("attendance_date", today)
          .maybeSingle(),
      ])
    : [{ data: [] }, { data: null }]

  const attendances = recentAttendances ?? []

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 gap-4">
        <LiveClock
          initialTime={initialClock}
          fullDate={formatIndonesianFullDate(now)}
          timezone={APP_TIMEZONE}
        />

        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center flex flex-col justify-center items-center">
          <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            Status Hari Ini
          </p>
          {todayAttendance ? (
            todayAttendance.status === "present" ? (
              <span className="inline-flex items-center rounded-md bg-emerald-50 px-3 py-1.5 text-xs sm:text-sm font-semibold text-emerald-600">
                HADIR
              </span>
            ) : todayAttendance.status === "late" ? (
              <span className="inline-flex items-center rounded-md bg-rose-50 px-3 py-1.5 text-xs sm:text-sm font-semibold text-rose-600">
                TERLAMBAT
              </span>
            ) : (
              <span className="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-600">
                ABSEN
              </span>
            )
          ) : (
            <span className="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-600">
              BELUM PRESENSI
            </span>
          )}
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-3">
        <div className="md:col-span-1 space-y-6">
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div className="flex items-center gap-2 mb-4">
              <div className="h-8 w-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                <svg
                  className="w-4 h-4 text-cyan-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-gray-900">Profil PKL</h3>
            </div>

            {student ? (
              <dl className="space-y-3 text-sm">
                <div>
                  <dt className="text-gray-500">NIM/NIS</dt>
                  <dd className="font-medium text-gray-900">
                    {student.student_code}
                  </dd>
                </div>
                <div>
                  <dt className="text-gray-500">Asal Sekolah/Kampus</dt>
                  <dd className="font-medium text-gray-900">
                    {student.school}
                  </dd>
                </div>
                <div>
                  <dt className="text-gray-500">Jurusan</dt>
                  <dd className="font-medium text-gray-900">{student.major}</dd>
                </div>
                <div>
                  <dt className="text-gray-500">Periode</dt>
                  <dd className="font-medium text-gray-900">
                    {formatDateShort(student.start_date)} -{" "}
                    {formatDateShort(student.end_date)}
                  </dd>
                </div>
              </dl>
            ) : (
              <p className="text-gray-500">Data siswa tidak ditemukan.</p>
            )}
          </div>

          <div className="relative overflow-hidden rounded-2xl bg-cyan-50 border border-cyan-100 p-6 shadow-sm">
            <div className="relative">
              <h3 className="text-lg font-semibold text-cyan-900 mb-2">
                Presensi Hari Ini
              </h3>
              <p className="text-sm text-cyan-700 mb-4">
                Lakukan presensi kehadiran Anda menggunakan fitur geofencing.
              </p>
              <Link
                href="/attendance"
                className="inline-flex w-full justify-center items-center rounded-lg bg-white border border-cyan-200 px-4 py-2.5 text-sm font-semibold text-cyan-700 hover:bg-gray-50 transition-colors shadow-sm"
              >
                Buka Halaman Presensi
              </Link>
            </div>
          </div>
        </div>

        <div className="md:col-span-2">
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div className="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
              <div className="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg
                  className="w-4 h-4 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <h2 className="font-semibold text-gray-900">
                10 Riwayat Presensi Terakhir
              </h2>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="fibernet-table-header">
                  <tr>
                    <th
                      scope="col"
                      className="px-6 py-4 font-semibold text-gray-900"
                    >
                      Tanggal
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-4 font-semibold text-gray-900"
                    >
                      Check In
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-4 font-semibold text-gray-900"
                    >
                      Check Out
                    </th>
                    <th
                      scope="col"
                      className="px-6 py-4 font-semibold text-gray-900"
                    >
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {attendances.length > 0 ? (
                    attendances.map((attendance) => (
                      <tr
                        key={attendance.id}
                        className="hover:bg-gray-50/50 transition-colors"
                      >
                        <td className="px-6 py-4 font-medium text-gray-900">
                          {formatDateShort(attendance.attendance_date)}
                        </td>
                        <td className="px-6 py-4">
                          {attendance.check_in_at ? (
                            <span className="text-gray-900 font-medium">
                              {formatTime24h(attendance.check_in_at)}
                            </span>
                          ) : (
                            <span className="text-gray-400">-</span>
                          )}
                        </td>
                        <td className="px-6 py-4">
                          {attendance.check_out_at ? (
                            <span className="text-gray-900 font-medium">
                              {formatTime24h(attendance.check_out_at)}
                            </span>
                          ) : (
                            <span className="text-gray-400">-</span>
                          )}
                        </td>
                        <td className="px-6 py-4">
                          {attendance.status === "present" ? (
                            <span className="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600">
                              Hadir
                            </span>
                          ) : attendance.status === "late" ? (
                            <span className="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600">
                              Terlambat
                            </span>
                          ) : (
                            <span className="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                              Absen
                            </span>
                          )}
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={4} className="px-6 py-16 text-center">
                        <div className="flex flex-col items-center">
                          <div className="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                            <svg
                              className="w-6 h-6 text-gray-400"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                              />
                            </svg>
                          </div>
                          <p className="text-sm text-gray-500">
                            Belum ada riwayat presensi.
                          </p>
                        </div>
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
