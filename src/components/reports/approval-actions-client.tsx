"use client"

import { useState, useTransition } from "react"
import { useRouter } from "next/navigation"
import { toast } from "sonner"
import { approveAttendanceAction } from "@/actions/attendance"
import { Button } from "@/components/ui/button"

export function ApprovalActionsClient({
  attendanceId,
}: {
  attendanceId: number
}) {
  const router = useRouter()
  const [isPending, startTransition] = useTransition()
  const [activeStatus, setActiveStatus] = useState<string | null>(null)

  function handleApprove(status: "present" | "late" | "absent") {
    setActiveStatus(status)
    const formData = new FormData()
    formData.set("attendance_id", String(attendanceId))
    formData.set("status", status)

    startTransition(async () => {
      const result = await approveAttendanceAction(formData)
      setActiveStatus(null)
      if (result.error) {
        toast.error(result.error)
        return
      }
      toast.success(
        result.success || "Status presensi berhasil diubah dan disetujui."
      )
      router.refresh()
    })
  }

  return (
    <div className="mt-1 sm:col-span-2 sm:mt-0 flex flex-wrap gap-2">
      <Button
        type="button"
        variant="primary"
        size="sm"
        disabled={isPending}
        loading={isPending && activeStatus === "present"}
        onClick={() => handleApprove("present")}
      >
        Terima (Hadir)
      </Button>
      <Button
        type="button"
        variant="filled"
        size="sm"
        disabled={isPending}
        loading={isPending && activeStatus === "late"}
        onClick={() => handleApprove("late")}
      >
        Terima (Terlambat)
      </Button>
      <Button
        type="button"
        variant="danger"
        size="sm"
        disabled={isPending}
        loading={isPending && activeStatus === "absent"}
        onClick={() => handleApprove("absent")}
      >
        Tolak (Alpa)
      </Button>
    </div>
  )
}
