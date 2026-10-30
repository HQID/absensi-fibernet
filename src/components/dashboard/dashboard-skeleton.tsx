import { Skeleton } from "@/components/ui/skeleton"

export function DashboardSkeleton({ role }: { role?: string }) {
  if (role === "student") {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-2 gap-4">
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-center items-center gap-2">
            <Skeleton className="h-3 w-24 rounded" />
            <Skeleton className="h-9 w-32 rounded-lg" />
            <Skeleton className="h-3.5 w-36 rounded" />
          </div>

          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-center items-center gap-3">
            <Skeleton className="h-3 w-24 rounded" />
            <Skeleton className="h-8 w-28 rounded-md" />
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-3">
          <div className="md:col-span-1 space-y-6">
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
              <div className="flex items-center gap-2">
                <Skeleton className="h-8 w-8 rounded-lg" />
                <Skeleton className="h-5 w-28 rounded" />
              </div>
              <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                  <div key={i} className="space-y-1.5">
                    <Skeleton className="h-3 w-24 rounded" />
                    <Skeleton className="h-4 w-4/5 rounded" />
                  </div>
                ))}
              </div>
            </div>

            <div className="rounded-2xl bg-cyan-50/60 border border-cyan-100 p-6 shadow-sm space-y-3">
              <Skeleton className="h-5 w-36 rounded" />
              <Skeleton className="h-4 w-full rounded" />
              <Skeleton className="h-10 w-full rounded-lg mt-2" />
            </div>
          </div>

          <div className="md:col-span-2">
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
              <div className="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
                <Skeleton className="h-8 w-8 rounded-lg" />
                <Skeleton className="h-5 w-48 rounded" />
              </div>
              <div className="divide-y divide-gray-50 p-6 space-y-4">
                {Array.from({ length: 5 }).map((_, i) => (
                  <div
                    key={i}
                    className="grid grid-cols-4 gap-4 items-center pt-3 first:pt-0"
                  >
                    <Skeleton className="h-4 w-24 rounded" />
                    <Skeleton className="h-4 w-14 rounded" />
                    <Skeleton className="h-4 w-14 rounded" />
                    <Skeleton className="h-6 w-16 rounded-md" />
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="card-soft-cyan">
          <div className="flex items-center gap-2">
            <Skeleton className="h-5 w-5 rounded" />
            <Skeleton className="h-4 w-32 rounded" />
          </div>
          <div className="mt-4">
            <Skeleton className="h-9 w-16 rounded-lg" />
          </div>
        </div>

        <div className="card-soft-white">
          <div className="flex items-center gap-2">
            <Skeleton className="h-5 w-5 rounded" />
            <Skeleton className="h-4 w-36 rounded" />
          </div>
          <div className="mt-4">
            <Skeleton className="h-9 w-16 rounded-lg" />
          </div>
        </div>

        <div className="card-soft-white">
          <div className="flex items-center gap-2">
            <Skeleton className="h-5 w-5 rounded" />
            <Skeleton className="h-4 w-24 rounded" />
          </div>
          <div className="mt-4">
            <Skeleton className="h-9 w-16 rounded-lg" />
          </div>
        </div>

        <div className="card-soft-rose">
          <div className="flex items-center gap-2">
            <Skeleton className="h-5 w-5 rounded" />
            <Skeleton className="h-4 w-36 rounded" />
          </div>
          <div className="mt-4">
            <Skeleton className="h-9 w-16 rounded-lg" />
          </div>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
          <div className="flex items-center gap-3">
            <Skeleton className="h-8 w-8 rounded-lg" />
            <Skeleton className="h-5 w-44 rounded" />
          </div>
          <Skeleton className="h-8 w-28 rounded-lg" />
        </div>

        <div className="divide-y divide-gray-50 px-6 py-4 space-y-4">
          {Array.from({ length: 5 }).map((_, i) => (
            <div
              key={i}
              className="grid grid-cols-5 gap-4 items-center pt-4 first:pt-0"
            >
              <div className="space-y-1.5">
                <Skeleton className="h-4 w-32 rounded" />
                <Skeleton className="h-3 w-20 rounded" />
              </div>
              <Skeleton className="h-4 w-full rounded" />
              <Skeleton className="h-4 w-36 rounded" />
              <Skeleton className="h-4 w-24 rounded" />
              <Skeleton className="h-6 w-20 rounded-md" />
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
