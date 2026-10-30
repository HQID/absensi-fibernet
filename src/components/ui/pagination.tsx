import Link from "next/link"
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/react/24/outline"

export interface PaginationProps {
  basePath: string
  currentPage: number
  perPage: number
  totalCount: number
  queryParams?: Record<string, string | undefined>
}

function buildHref(
  basePath: string,
  page: number,
  queryParams?: Record<string, string | undefined>
): string {
  const params = new URLSearchParams()
  if (queryParams) {
    for (const [key, value] of Object.entries(queryParams)) {
      if (value !== undefined && value !== "") {
        params.set(key, value)
      }
    }
  }
  if (page > 1) {
    params.set("page", String(page))
  }
  const qs = params.toString()
  return qs ? `${basePath}?${qs}` : basePath
}

export function Pagination({
  basePath,
  currentPage,
  perPage,
  totalCount,
  queryParams,
}: PaginationProps) {
  const totalPages = Math.max(1, Math.ceil(totalCount / perPage))
  if (totalPages <= 1) return null

  const from = totalCount === 0 ? 0 : (currentPage - 1) * perPage + 1
  const to = Math.min(totalCount, currentPage * perPage)

  const pages: number[] = []
  const start = Math.max(1, currentPage - 2)
  const end = Math.min(totalPages, currentPage + 2)
  for (let p = start; p <= end; p++) {
    pages.push(p)
  }

  return (
    <nav
      aria-label="Pagination"
      className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <p className="text-sm text-gray-600">
        Showing <span className="font-semibold text-gray-900">{from}</span> to{" "}
        <span className="font-semibold text-gray-900">{to}</span> of{" "}
        <span className="font-semibold text-gray-900">{totalCount}</span>{" "}
        results
      </p>

      <div className="inline-flex items-center gap-1">
        {currentPage > 1 ? (
          <Link
            href={buildHref(basePath, currentPage - 1, queryParams)}
            className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors"
            aria-label="Previous page"
          >
            <ChevronLeftIcon className="h-4 w-4" />
          </Link>
        ) : (
          <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-100 bg-gray-50 text-gray-300">
            <ChevronLeftIcon className="h-4 w-4" />
          </span>
        )}

        {pages.map((page) =>
          page === currentPage ? (
            <span
              key={page}
              aria-current="page"
              className="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-[#0891b2] px-2.5 text-xs font-semibold text-white"
            >
              {page}
            </span>
          ) : (
            <Link
              key={page}
              href={buildHref(basePath, page, queryParams)}
              className="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            >
              {page}
            </Link>
          )
        )}

        {currentPage < totalPages ? (
          <Link
            href={buildHref(basePath, currentPage + 1, queryParams)}
            className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors"
            aria-label="Next page"
          >
            <ChevronRightIcon className="h-4 w-4" />
          </Link>
        ) : (
          <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-100 bg-gray-50 text-gray-300">
            <ChevronRightIcon className="h-4 w-4" />
          </span>
        )}
      </div>
    </nav>
  )
}
