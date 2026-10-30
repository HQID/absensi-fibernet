import { DashboardSkeleton } from "@/components/dashboard/dashboard-skeleton"
import { PageHeaderSkeleton } from "@/components/ui/page-skeletons"

export default function DashboardLoading() {
  return (
    <div className="space-y-6">
      <PageHeaderSkeleton />
      <DashboardSkeleton />
    </div>
  )
}
