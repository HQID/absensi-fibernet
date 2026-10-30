"use client"

import { useEffect, useRef, useState, type ReactNode } from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import {
  ArrowRightStartOnRectangleIcon,
  Bars2Icon,
  CalendarIcon,
  ChevronDownIcon,
  ChevronUpDownIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  HomeIcon,
  UsersIcon,
  XMarkIcon,
} from "@heroicons/react/24/outline"
import { logoutAction } from "@/actions/auth"
import { getInitials } from "@/lib/student-guard"

export type ShellUser = {
  name: string
  email: string
  role: string
}

function UserMenuDropdown({
  user,
  variant,
}: {
  user: ShellUser
  variant: "sidebar" | "mobile"
}) {
  const pathname = usePathname()
  const [openPath, setOpenPath] = useState<string | null>(null)
  const containerRef = useRef<HTMLDivElement>(null)
  const open = openPath === pathname
  const setOpen = (next: boolean) => setOpenPath(next ? pathname : null)

  useEffect(() => {
    if (!open) return

    function handleClickOutside(e: MouseEvent) {
      if (
        containerRef.current &&
        !containerRef.current.contains(e.target as Node)
      ) {
        setOpenPath(null)
      }
    }

    function handleEsc(e: KeyboardEvent) {
      if (e.key === "Escape") setOpenPath(null)
    }

    document.addEventListener("mousedown", handleClickOutside)
    document.addEventListener("keydown", handleEsc)
    return () => {
      document.removeEventListener("mousedown", handleClickOutside)
      document.removeEventListener("keydown", handleEsc)
    }
  }, [open])

  const initials = getInitials(user.name)
  const firstLetter = user.name.trim().charAt(0).toUpperCase() || "U"

  return (
    <div ref={containerRef} className="relative">
      {variant === "sidebar" ? (
        <button
          type="button"
          data-flux-sidebar-profile=""
          data-test="sidebar-menu-button"
          onClick={() => setOpen(!open)}
          className="flex w-full items-center gap-3 rounded-xl px-2.5 py-2 text-left text-sm font-medium text-white hover:bg-white/10 transition cursor-pointer"
        >
          <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700 text-xs font-bold text-white">
            {initials}
          </span>
          <span className="flex-1 truncate text-white">{user.name}</span>
          <ChevronUpDownIcon className="h-4 w-4 shrink-0 text-slate-300" />
        </button>
      ) : (
        <button
          type="button"
          onClick={() => setOpen(!open)}
          className="inline-flex items-center gap-1.5 rounded-lg p-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100 transition cursor-pointer"
        >
          <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-xs font-bold text-zinc-700 border border-zinc-200">
            {initials}
          </span>
          <ChevronDownIcon className="h-4 w-4 text-zinc-500" />
        </button>
      )}

      {open && (
        <div
          className={`absolute z-50 w-64 rounded-2xl border border-gray-100 bg-white p-2 shadow-xl ${
            variant === "sidebar"
              ? "bottom-full left-0 mb-2"
              : "top-full right-0 mt-2"
          }`}
        >
          <div className="flex items-center gap-3 px-2 py-2 text-start text-sm bg-cyan-50/50 rounded-xl mb-1">
            <div className="h-10 w-10 shrink-0 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold shadow-sm border border-cyan-200">
              {firstLetter}
            </div>
            <div className="grid flex-1 text-start text-sm leading-tight min-w-0">
              <span className="truncate font-semibold text-zinc-900">
                {user.name}
              </span>
              <span className="truncate text-xs text-zinc-500">
                {user.email}
              </span>
            </div>
          </div>

          <div className="my-1 h-px bg-gray-100" />

          <Link
            href="/settings/profile"
            onClick={() => setOpen(false)}
            className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 transition"
          >
            <Cog6ToothIcon className="h-4 w-4 text-zinc-500" />
            <span>Profil</span>
          </Link>

          { variant === "mobile" && <div className="my-1 h-px bg-gray-100" /> }

          <form
            action={logoutAction}
            data-loading-text="Sedang keluar..."
            className="w-full"
          >
            <button
              type="submit"
              data-test="logout-button"
              className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 transition cursor-pointer"
            >
              <ArrowRightStartOnRectangleIcon className="h-4 w-4 text-zinc-500" />
              <span>Log out</span>
            </button>
          </form>
        </div>
      )}
    </div>
  )
}

export function AppShell({
  user,
  children,
}: {
  user: ShellUser
  children: ReactNode
}) {
  const pathname = usePathname()
  const [mobileOpenPath, setMobileOpenPath] = useState<string | null>(null)
  const mobileOpen = mobileOpenPath === pathname
  const setMobileOpen = (next: boolean) =>
    setMobileOpenPath(next ? pathname : null)

  const isStudent = user.role === "student"
  const isAdmin = user.role === "admin"

  const navItems = [
    {
      label: "Dashboard",
      href: "/dashboard",
      icon: HomeIcon,
      current: pathname === "/dashboard",
      visible: true,
    },
    {
      label: "Attendance",
      href: "/attendance",
      icon: CalendarIcon,
      current: pathname === "/attendance" || pathname.startsWith("/attendance/"),
      visible: isStudent,
    },
    {
      label: "Reports",
      href: "/reports",
      icon: DocumentTextIcon,
      current: pathname === "/reports" || pathname.startsWith("/reports/"),
      visible: isAdmin,
    },
    {
      label: "Peserta PKL",
      href: "/students",
      icon: UsersIcon,
      current: pathname === "/students" || pathname.startsWith("/students/"),
      visible: isAdmin,
    },
  ].filter((item) => item.visible)

  const sidebarContent = (
    <>
      <div className="flex items-center justify-between gap-2 px-2">
        <Link
          href="/dashboard"
          className="flex h-12 flex-1 items-center justify-center"
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src="/fibernet.png"
            alt="Logo"
            className="h-full w-full object-contain bg-white rounded-lg px-2 py-1 shadow-sm"
          />
        </Link>
        <button
          type="button"
          onClick={() => setMobileOpen(false)}
          aria-label="Tutup menu"
          className="lg:hidden rounded-lg p-1.5 text-slate-400 hover:text-white cursor-pointer"
        >
          <XMarkIcon className="h-5 w-5" />
        </button>
      </div>

      <nav className="mt-6 px-2">
        <div className="grid gap-1">
          {navItems.map((item) => {
            const Icon = item.icon
            return (
              <Link
                key={item.href}
                href={item.href}
                data-flux-sidebar-item=""
                data-current={item.current ? "" : undefined}
                className="flex items-center gap-3 px-3 font-medium"
              >
                <Icon className="h-5 w-5 shrink-0" />
                <div data-content="">{item.label}</div>
              </Link>
            )
          })}
        </div>
      </nav>

      <div className="flex-1" />

      <div className="px-2 py-3 mb-2">
        <div className="rounded-lg bg-slate-800/50 border border-slate-700/50 px-3 py-2.5">
          <div className="flex items-center gap-2">
            <div className="h-2 w-2 shrink-0 rounded-full bg-cyan-500 animate-pulse" />
            <span className="text-[11px] leading-tight text-slate-300 font-medium">
              PT Fiber Networks Indonesia Branch Palu
            </span>
          </div>
        </div>
      </div>

      <div className="hidden lg:block px-2">
        <UserMenuDropdown user={user} variant="sidebar" />
      </div>
    </>
  )

  return (
    <div className="min-h-screen bg-slate-50 lg:flex">
      {/* Desktop Sticky Sidebar */}
      <aside className="fibernet-sidebar hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col lg:sticky lg:top-0 lg:h-screen py-4 px-2">
        {sidebarContent}
      </aside>

      {/* Mobile Drawer Sidebar */}
      {mobileOpen && (
        <div className="fixed inset-0 z-50 flex lg:hidden">
          <div
            className="fixed inset-0 bg-slate-900/50 backdrop-blur-xs"
            onClick={() => setMobileOpen(false)}
          />
          <aside className="fibernet-sidebar relative z-10 flex w-64 max-w-[80vw] flex-col h-full py-4 px-2 shadow-2xl">
            {sidebarContent}
          </aside>
        </div>
      )}

      <div className="flex min-h-screen flex-1 flex-col min-w-0">
        {/* Mobile Header */}
        <header className="lg:hidden sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4">
          <button
            type="button"
            onClick={() => setMobileOpen(true)}
            aria-label="Buka navigasi"
            className="-ml-1.5 rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 cursor-pointer"
          >
            <Bars2Icon className="h-5 w-5" />
          </button>

          <div className="flex-1" />

          <UserMenuDropdown user={user} variant="mobile" />
        </header>

        {/* Main Content */}
        <main className="flex-1 p-6 lg:p-8">{children}</main>
      </div>
    </div>
  )
}
