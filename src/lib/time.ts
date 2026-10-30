export const APP_TIMEZONE = process.env.APP_TIMEZONE || "Asia/Makassar"

const witaDateFormatter = new Intl.DateTimeFormat("en-CA", {
  timeZone: APP_TIMEZONE,
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
})

const witaTimeFormatter = new Intl.DateTimeFormat("en-GB", {
  timeZone: APP_TIMEZONE,
  hour: "2-digit",
  minute: "2-digit",
  second: "2-digit",
  hour12: false,
})

const SHORT_MONTHS = [
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "May",
  "Jun",
  "Jul",
  "Aug",
  "Sep",
  "Oct",
  "Nov",
  "Dec",
] as const

const LONG_MONTHS = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
] as const

const indonesianFullDateFormatter = new Intl.DateTimeFormat("id-ID", {
  timeZone: APP_TIMEZONE,
  weekday: "long",
  day: "numeric",
  month: "long",
  year: "numeric",
})

const time12hFormatter = new Intl.DateTimeFormat("en-US", {
  timeZone: APP_TIMEZONE,
  hour: "2-digit",
  minute: "2-digit",
  hour12: true,
})

const time24hFormatter = new Intl.DateTimeFormat("en-GB", {
  timeZone: APP_TIMEZONE,
  hour: "2-digit",
  minute: "2-digit",
  hour12: false,
})

const indonesianWeekdayFormatter = new Intl.DateTimeFormat("id-ID", {
  timeZone: APP_TIMEZONE,
  weekday: "long",
})

export function todayWitaDateString(date: Date = new Date()): string {
  return witaDateFormatter.format(date)
}

export function witaTimeString(date: Date = new Date()): string {
  return witaTimeFormatter.format(date)
}

export function isAfterLateCutoff(date: Date = new Date()): boolean {
  return witaTimeString(date) > "08:15:00"
}

export function isAfterClosingCutoff(date: Date = new Date()): boolean {
  return witaTimeString(date) > "17:00:00"
}

export function isDatePastWitaEndOfDay(
  endDateYmd: string,
  now: Date = new Date()
): boolean {
  return todayWitaDateString(now) > endDateYmd
}

export function formatDateShort(dateYmdOrIso: string): string {
  const ymd =
    dateYmdOrIso.length === 10
      ? dateYmdOrIso
      : todayWitaDateString(new Date(dateYmdOrIso))
  const [year, month, day] = ymd.split("-")
  return `${day} ${SHORT_MONTHS[Number(month) - 1]} ${year}`
}

export function formatIndonesianFullDate(date: Date = new Date()): string {
  return indonesianFullDateFormatter.format(date)
}

export function formatTime12h(isoString: string): string {
  return time12hFormatter.format(new Date(isoString))
}

export function formatTime24h(isoString: string): string {
  return time24hFormatter.format(new Date(isoString))
}

export function formatIndonesianWeekday(dateYmdOrIso: string): string {
  const normalized =
    dateYmdOrIso.length === 10
      ? `${dateYmdOrIso}T12:00:00+08:00`
      : dateYmdOrIso
  return indonesianWeekdayFormatter.format(new Date(normalized))
}

export function formatDuration(
  checkInIso: string,
  checkOutIso: string,
  style: "short" | "long" = "short"
): string {
  const diffMs = Math.max(
    0,
    new Date(checkOutIso).getTime() - new Date(checkInIso).getTime()
  )
  const totalMinutes = Math.floor(diffMs / 60_000)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60

  return style === "long"
    ? `${hours} Jam ${minutes} Menit`
    : `${hours}h ${minutes}m`
}

export function currentMonthRangeWita(now: Date = new Date()): {
  startDate: string
  endDate: string
} {
  const today = todayWitaDateString(now)
  const [year, month] = today.split("-")
  const lastDay = new Date(
    Date.UTC(Number(year), Number(month), 0)
  ).getUTCDate()
  return {
    startDate: `${year}-${month}-01`,
    endDate: `${year}-${month}-${String(lastDay).padStart(2, "0")}`,
  }
}

export function formatDateSlash(dateYmd: string): string {
  const [year, month, day] = dateYmd.slice(0, 10).split("-")
  return `${day}/${month}/${year}`
}

export function formatDateLongEn(dateYmd: string): string {
  const [year, month, day] = dateYmd.slice(0, 10).split("-")
  return `${day} ${LONG_MONTHS[Number(month) - 1]} ${year}`
}

export function formatCheckedAtWita(date: Date = new Date()): string {
  return `${formatDateShort(todayWitaDateString(date))}, ${witaTimeString(date)}`
}

export function formatBytes(bytes: number): string {
  const units = ["B", "KB", "MB", "GB", "TB"]
  let value = Math.max(0, bytes)
  let unitIndex = 0

  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024
    unitIndex++
  }

  const formatted =
    unitIndex === 0
      ? Math.trunc(value).toString()
      : value.toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })

  return `${formatted} ${units[unitIndex]}`
}

const YMD_REGEX = /^\d{4}-\d{2}-\d{2}$/

export function computeEligibleAbsentDates(
  startDate: string,
  endDate: string,
  now: Date = new Date()
): string[] {
  if (!YMD_REGEX.test(startDate) || !YMD_REGEX.test(endDate)) {
    return []
  }

  const todayStr = todayWitaDateString(now)
  const cappedEnd = endDate > todayStr ? todayStr : endDate

  if (startDate > cappedEnd) {
    return []
  }

  const startUtc = new Date(`${startDate}T00:00:00Z`)
  const endUtc = new Date(`${cappedEnd}T00:00:00Z`)
  if (Number.isNaN(startUtc.getTime()) || Number.isNaN(endUtc.getTime())) {
    return []
  }

  const isBeforeCutoff = witaTimeString(now).slice(0, 5) < "17:00"
  const dates: string[] = []
  const cursor = new Date(startUtc.getTime())

  while (cursor <= endUtc) {
    const day = cursor.getUTCDay()
    if (day !== 0 && day !== 6) {
      const dateStr = cursor.toISOString().slice(0, 10)
      if (!(dateStr === todayStr && isBeforeCutoff)) {
        dates.push(dateStr)
      }
    }
    cursor.setUTCDate(cursor.getUTCDate() + 1)
  }

  return dates
}
