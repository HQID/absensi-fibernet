import { Skeleton } from "@/components/ui/skeleton"

export function PageHeaderSkeleton({
  withAction = false,
}: {
  withAction?: boolean
}) {
  return (
    <div className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div className="flex items-center gap-3">
          <Skeleton className="h-10 w-10 rounded-xl" />
          <div className="space-y-2">
            <Skeleton className="h-6 w-48 rounded-lg" />
            <Skeleton className="h-4 w-72 max-w-full rounded" />
          </div>
        </div>
        {withAction && <Skeleton className="h-10 w-32 rounded-lg" />}
      </div>
    </div>
  )
}

export function AttendanceContentSkeleton() {
  return (
    <>
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col items-center space-y-5">
        <div className="flex items-center gap-2">
          <Skeleton className="h-8 w-8 rounded-lg" />
          <Skeleton className="h-5 w-40 rounded" />
        </div>
        <Skeleton className="h-11 w-52 rounded-xl" />
      </div>

      <div className="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex items-center gap-3">
          <Skeleton className="h-8 w-8 rounded-lg" />
          <Skeleton className="h-5 w-48 rounded" />
        </div>
        <div className="divide-y divide-gray-50 p-6 space-y-4">
          {Array.from({ length: 5 }).map((_, i) => (
            <div
              key={i}
              className="grid grid-cols-4 gap-4 items-center pt-4 first:pt-0"
            >
              <div className="space-y-1.5">
                <Skeleton className="h-4 w-24 rounded" />
                <Skeleton className="h-3 w-16 rounded" />
              </div>
              <Skeleton className="h-4 w-14 rounded" />
              <Skeleton className="h-4 w-14 rounded" />
              <Skeleton className="h-6 w-20 rounded-md" />
            </div>
          ))}
        </div>
      </div>
    </>
  )
}

export function AttendancePageSkeleton() {
  return (
    <div className="space-y-6">
      <PageHeaderSkeleton />
      <AttendanceContentSkeleton />
    </div>
  )
}

export function StudentsTableSkeleton() {
  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div className="border-b border-gray-100 px-6 py-4 grid grid-cols-7 gap-4">
        {Array.from({ length: 7 }).map((_, i) => (
          <Skeleton key={i} className="h-4 w-20 rounded" />
        ))}
      </div>
      <div className="divide-y divide-gray-50 px-6 py-4 space-y-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div
            key={i}
            className="grid grid-cols-7 gap-4 items-center pt-4 first:pt-0"
          >
            <Skeleton className="h-4 w-28 rounded" />
            <Skeleton className="h-4 w-24 rounded" />
            <Skeleton className="h-4 w-32 rounded" />
            <Skeleton className="h-4 w-24 rounded" />
            <Skeleton className="h-4 w-36 rounded" />
            <Skeleton className="h-6 w-16 rounded-md" />
            <div className="flex items-center gap-3">
              <Skeleton className="h-4 w-10 rounded" />
              <Skeleton className="h-4 w-12 rounded" />
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export function StudentsPageSkeleton() {
  return (
    <div className="space-y-6">
      <PageHeaderSkeleton />
      <StudentsTableSkeleton />
    </div>
  )
}

export function ReportsTableSkeleton() {
  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div className="border-b border-gray-100 px-6 py-4 grid grid-cols-7 gap-4">
        {Array.from({ length: 7 }).map((_, i) => (
          <Skeleton key={i} className="h-4 w-20 rounded" />
        ))}
      </div>
      <div className="divide-y divide-gray-50 px-6 py-4 space-y-4">
        {Array.from({ length: 10 }).map((_, i) => (
          <div
            key={i}
            className="grid grid-cols-7 gap-4 items-center pt-4 first:pt-0"
          >
            <Skeleton className="h-4 w-20 rounded" />
            <div className="space-y-1.5">
              <Skeleton className="h-4 w-28 rounded" />
              <Skeleton className="h-3 w-20 rounded" />
            </div>
            <Skeleton className="h-4 w-full rounded" />
            <Skeleton className="h-4 w-28 rounded" />
            <Skeleton className="h-4 w-24 rounded" />
            <Skeleton className="h-6 w-20 rounded-md" />
            <div className="flex justify-end">
              <Skeleton className="h-4 w-14 rounded" />
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export function ReportsPageSkeleton() {
  return (
    <div className="space-y-6">
      <PageHeaderSkeleton withAction />
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="space-y-2">
              <Skeleton className="h-4 w-24 rounded" />
              <Skeleton className="h-10 w-full rounded-lg" />
            </div>
          ))}
          <Skeleton className="h-[42px] w-full rounded-lg" />
        </div>
      </div>
      <ReportsTableSkeleton />
    </div>
  )
}

export function ReportDetailPageSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Skeleton className="h-10 w-10 rounded-xl" />
        <div className="space-y-2">
          <Skeleton className="h-6 w-44 rounded-lg" />
          <Skeleton className="h-4 w-64 rounded" />
        </div>
      </div>

      <div className="fibernet-card-blue">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
          <Skeleton className="h-7 w-7 rounded-lg" />
          <Skeleton className="h-5 w-36 rounded" />
        </div>
        <div className="divide-y divide-gray-100">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="py-4 px-6 grid sm:grid-cols-3 gap-4">
              <Skeleton className="h-4 w-28 rounded" />
              <Skeleton className="h-4 w-48 rounded sm:col-span-2" />
            </div>
          ))}
        </div>
      </div>

      <div className="fibernet-card-purple">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
          <Skeleton className="h-7 w-7 rounded-lg" />
          <Skeleton className="h-5 w-36 rounded" />
        </div>
        <div className="divide-y divide-gray-100">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="py-4 px-6 grid sm:grid-cols-3 gap-4">
              <Skeleton className="h-4 w-32 rounded" />
              <Skeleton className="h-4 w-56 rounded sm:col-span-2" />
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

export function SettingsFormSkeleton({ fields = 4 }: { fields?: number }) {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Skeleton className="h-8 w-8 rounded-lg" />
        <Skeleton className="h-5 w-44 rounded" />
      </div>
      <Skeleton className="h-4 w-72 max-w-full rounded ml-11" />

      <div className="mt-6 w-full max-w-2xl space-y-6">
        {Array.from({ length: fields }).map((_, i) => (
          <div key={i} className="space-y-2">
            <Skeleton className="h-4 w-32 rounded" />
            <Skeleton className="h-10 w-full rounded-lg" />
          </div>
        ))}
        <Skeleton className="h-10 w-40 rounded-lg" />
      </div>
    </div>
  )
}

export function StorageStatsSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Skeleton className="h-8 w-8 rounded-lg" />
        <Skeleton className="h-5 w-48 rounded" />
      </div>
      <Skeleton className="h-4 w-80 max-w-full rounded ml-11" />

      <div className="mt-6 w-full max-w-2xl space-y-6">
        <div className="rounded-2xl border border-gray-100 bg-slate-50/70 p-5 space-y-4">
          <div className="flex items-center justify-between gap-3">
            <Skeleton className="h-6 w-48 rounded-full" />
            <div className="flex items-center gap-3">
              <Skeleton className="h-4 w-32 rounded" />
              <Skeleton className="h-8 w-20 rounded-lg" />
            </div>
          </div>
          <div className="space-y-2.5">
            <div className="flex items-baseline justify-between">
              <Skeleton className="h-8 w-44 rounded-lg" />
              <Skeleton className="h-4 w-12 rounded" />
            </div>
            <Skeleton className="h-3 w-full rounded-full" />
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <div
              key={i}
              className="rounded-xl border border-gray-100 bg-white p-4 space-y-2"
            >
              <Skeleton className="h-3 w-32 rounded" />
              <Skeleton className="h-6 w-20 rounded" />
              <Skeleton className="h-3 w-36 rounded" />
            </div>
          ))}
        </div>

        <div className="rounded-xl border border-gray-100 bg-white p-4 flex items-center justify-between gap-4">
          <div className="space-y-2">
            <Skeleton className="h-4 w-40 rounded" />
            <Skeleton className="h-3 w-64 rounded" />
          </div>
          <Skeleton className="h-8 w-36 rounded-lg" />
        </div>
      </div>
    </div>
  )
}

export function SettingsPageSkeleton() {
  return (
    <section className="w-full">
      <div className="mb-6 space-y-2">
        <Skeleton className="h-7 w-24 rounded-lg" />
        <Skeleton className="h-4 w-64 rounded" />
      </div>

      <div className="flex items-start max-md:flex-col">
        <div className="me-10 w-full pb-4 md:w-[220px] space-y-1.5">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-9 w-full rounded-lg" />
          ))}
        </div>

        <div className="flex-1 self-stretch max-md:pt-6">
          <div className="fibernet-card p-6">
            <SettingsFormSkeleton />
          </div>
        </div>
      </div>
    </section>
  )
}
