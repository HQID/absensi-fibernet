"use client"

import { useEffect, useState } from "react"

function formatLocaleClock(date: Date, timezone: string): string {
  return date.toLocaleTimeString("id-ID", {
    timeZone: timezone,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })
}

export function LiveClock({
  initialTime,
  fullDate,
  timezone = "Asia/Makassar",
}: {
  initialTime: string
  fullDate: string
  timezone?: string
}) {
  const [time, setTime] = useState(initialTime)

  useEffect(() => {
    const update = () => setTime(formatLocaleClock(new Date(), timezone))
    update()
    const timer = setInterval(update, 1000)
    return () => clearInterval(timer)
  }, [timezone])

  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center flex flex-col justify-center items-center">
      <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
        Waktu Saat Ini
      </p>
      <div className="text-2xl sm:text-4xl font-bold text-gray-900 tabular-nums">
        {time}
      </div>
      <p className="text-xs sm:text-sm text-gray-500 mt-1">{fullDate}</p>
    </div>
  )
}
