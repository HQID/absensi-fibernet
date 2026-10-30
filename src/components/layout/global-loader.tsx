"use client"

import { Suspense, useEffect, useRef } from "react"
import { usePathname, useSearchParams } from "next/navigation"

export function startGlobalOverlay(label = "Memproses...") {
  if (typeof window !== "undefined") {
    window.dispatchEvent(
      new CustomEvent("fibernet:loader-overlay", { detail: { label } })
    )
  }
}

export function resetGlobalLoader() {
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("fibernet:loader-reset"))
  }
}

function RouteChangeResetter({
  onRouteSettled,
}: {
  onRouteSettled: () => void
}) {
  const pathname = usePathname()
  const searchParams = useSearchParams()
  const isFirstRender = useRef(true)

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false
      return
    }
    onRouteSettled()
  }, [pathname, searchParams, onRouteSettled])

  return null
}

export function GlobalLoader() {
  const barRef = useRef<HTMLDivElement>(null)
  const pillRef = useRef<HTMLDivElement>(null)
  const pillTextRef = useRef<HTMLSpanElement>(null)
  const overlayRef = useRef<HTMLDivElement>(null)
  const overlayTextRef = useRef<HTMLParagraphElement>(null)

  const timersRef = useRef<{
    trickle: ReturnType<typeof setInterval> | null
    hideBar: ReturnType<typeof setTimeout> | null
    safety: ReturnType<typeof setTimeout> | null
    progress: number
    activeNavUrl: string | null
  }>({
    trickle: null,
    hideBar: null,
    safety: null,
    progress: 0,
    activeNavUrl: null,
  })

  function clearTimers() {
    const t = timersRef.current
    if (t.trickle) clearInterval(t.trickle)
    if (t.hideBar) clearTimeout(t.hideBar)
    if (t.safety) clearTimeout(t.safety)
    t.trickle = null
    t.hideBar = null
    t.safety = null
  }

  function unlockFormsAndButtons() {
    document
      .querySelectorAll<HTMLFormElement>('form[data-submitting="true"]')
      .forEach((form) => {
        delete form.dataset.submitting
      })

    document
      .querySelectorAll<
        HTMLButtonElement | HTMLInputElement
      >('[data-locked-by-loader="true"]')
      .forEach((btn) => {
        btn.disabled = false
        delete btn.dataset.lockedByLoader
      })
  }

  function finishTopBar() {
    const t = timersRef.current
    if (t.trickle) clearInterval(t.trickle)
    if (t.safety) clearTimeout(t.safety)
    t.trickle = null
    t.safety = null

    if (pillRef.current) pillRef.current.dataset.active = "false"

    const bar = barRef.current
    if (bar && bar.style.opacity === "1") {
      bar.style.width = "100%"
      t.hideBar = setTimeout(() => {
        bar.style.opacity = "0"
        bar.style.width = "0%"
      }, 220)
    }
  }

  function resetAllLoaders(smoothBar = false) {
    timersRef.current.activeNavUrl = null

    if (smoothBar) {
      finishTopBar()
    } else {
      clearTimers()
      if (barRef.current) {
        barRef.current.style.opacity = "0"
        barRef.current.style.width = "0%"
      }
      if (pillRef.current) pillRef.current.dataset.active = "false"
    }

    if (overlayRef.current) overlayRef.current.dataset.active = "false"
    if (typeof document !== "undefined" && document.body) {
      document.body.style.overflow = ""
    }
    unlockFormsAndButtons()
  }

  useEffect(() => {
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js").catch(() => {
        // Ignore SW registration failure in unsupported/dev contexts
      })
    }

    const t = timersRef.current

    function startTopBar(label = "Memuat...") {
      clearTimers()
      const bar = barRef.current
      const pill = pillRef.current
      if (!bar || !pill) return

      if (pillTextRef.current) pillTextRef.current.textContent = label
      t.progress = 18
      bar.style.opacity = "1"
      bar.style.width = `${t.progress}%`
      pill.dataset.active = "true"

      t.trickle = setInterval(() => {
        if (t.progress < 70) t.progress += 12
        else if (t.progress < 90) t.progress += 3
        else if (t.progress < 95) t.progress += 0.5
        bar.style.width = `${Math.min(t.progress, 95)}%`
      }, 250)

      t.safety = setTimeout(() => resetAllLoaders(false), 15000)
    }

    function startOverlay(label = "Memproses...") {
      clearTimers()
      const overlay = overlayRef.current
      if (!overlay) return

      if (overlayTextRef.current) overlayTextRef.current.textContent = label
      overlay.dataset.active = "true"
      document.body.style.overflow = "hidden"

      t.safety = setTimeout(() => resetAllLoaders(false), 20000)
    }

    function parseInternalLink(link: HTMLAnchorElement): URL | null {
      const rawHref = link.getAttribute("href") || ""
      if (
        !rawHref ||
        rawHref.startsWith("#") ||
        rawHref.startsWith("javascript:") ||
        link.target === "_blank"
      ) {
        return null
      }
      try {
        const url = new URL(link.href, window.location.origin)
        if (url.origin !== window.location.origin) return null
        if (
          url.pathname === window.location.pathname &&
          url.search === window.location.search
        ) {
          return null
        }
        return url
      } catch {
        return null
      }
    }

    function lockFormSubmitButtons(form: HTMLFormElement) {
      form.dataset.submitting = "true"
      const buttons = form.querySelectorAll<
        HTMLButtonElement | HTMLInputElement
      >('button[type="submit"], input[type="submit"]')
      setTimeout(() => {
        buttons.forEach((btn) => {
          if (!btn.disabled) {
            btn.disabled = true
            btn.dataset.lockedByLoader = "true"
          }
        })
      }, 0)
    }

    function onSubmitCapture(e: Event) {
      const form = e.target
      if (!(form instanceof HTMLFormElement)) return

      if (form.dataset.submitting === "true") {
        e.preventDefault()
        e.stopImmediatePropagation()
        return
      }

      if (form.dataset.noGlobalLoader === "true" || form.target === "_blank") {
        return
      }

      if (typeof form.checkValidity === "function" && !form.checkValidity()) {
        return
      }

      const overlayLabel =
        form.dataset.loadingText ||
        (form.dataset.loading === "overlay" ? "Memproses..." : null)

      if (overlayLabel) {
        lockFormSubmitButtons(form)
        startOverlay(overlayLabel)
        return
      }

      if (form.dataset.navForm === "true") {
        lockFormSubmitButtons(form)
        startTopBar("Memuat...")
      }
    }

    function onSubmitBubble(e: Event) {
      const form = e.target
      if (!(form instanceof HTMLFormElement)) return
      if (
        form.dataset.submitting === "true" ||
        form.dataset.noGlobalLoader === "true" ||
        form.target === "_blank" ||
        e.defaultPrevented
      ) {
        return
      }
      if (typeof form.checkValidity === "function" && !form.checkValidity()) {
        return
      }

      lockFormSubmitButtons(form)
      startTopBar("Memuat...")
    }

    function onClickCapture(e: MouseEvent) {
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return
      }
      const link =
        e.target instanceof Element
          ? e.target.closest<HTMLAnchorElement>("a[href]")
          : null
      if (!link) return

      if (link.dataset.downloading === "true") {
        e.preventDefault()
        e.stopImmediatePropagation()
        return
      }

      const targetUrl = parseInternalLink(link)
      if (targetUrl && t.activeNavUrl === targetUrl.href) {
        e.preventDefault()
        e.stopImmediatePropagation()
      }
    }

    function onClickBubble(e: MouseEvent) {
      if (
        e.defaultPrevented ||
        e.button !== 0 ||
        e.metaKey ||
        e.ctrlKey ||
        e.shiftKey ||
        e.altKey
      ) {
        return
      }
      const link =
        e.target instanceof Element
          ? e.target.closest<HTMLAnchorElement>("a[href]")
          : null
      if (!link) return

      if (
        link.hasAttribute("data-download") ||
        link.hasAttribute("download")
      ) {
        link.dataset.downloading = "true"
        link.classList.add("opacity-60", "pointer-events-none")
        startTopBar("Menyiapkan unduhan...")

        setTimeout(() => {
          delete link.dataset.downloading
          link.classList.remove("opacity-60", "pointer-events-none")
          finishTopBar()
        }, 4000)
        return
      }

      const targetUrl = parseInternalLink(link)
      if (!targetUrl) return

      t.activeNavUrl = targetUrl.href
      startTopBar("Memuat halaman...")
    }

    const onCustomOverlay = (ev: Event) => {
      const detail = (ev as CustomEvent<{ label?: string }>).detail
      startOverlay(detail?.label || "Memproses...")
    }
    const onCustomReset = () => resetAllLoaders(false)
    const onPageShow = () => resetAllLoaders(false)

    document.addEventListener("submit", onSubmitCapture, true)
    document.addEventListener("submit", onSubmitBubble)
    document.addEventListener("click", onClickCapture, true)
    document.addEventListener("click", onClickBubble)
    window.addEventListener("fibernet:loader-overlay", onCustomOverlay)
    window.addEventListener("fibernet:loader-reset", onCustomReset)
    window.addEventListener("pageshow", onPageShow)

    return () => {
      clearTimers()
      document.removeEventListener("submit", onSubmitCapture, true)
      document.removeEventListener("submit", onSubmitBubble)
      document.removeEventListener("click", onClickCapture, true)
      document.removeEventListener("click", onClickBubble)
      window.removeEventListener("fibernet:loader-overlay", onCustomOverlay)
      window.removeEventListener("fibernet:loader-reset", onCustomReset)
      window.removeEventListener("pageshow", onPageShow)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  return (
    <>
      <Suspense fallback={null}>
        <RouteChangeResetter onRouteSettled={() => resetAllLoaders(true)} />
      </Suspense>

      <div id="fibernet-loader-root" aria-live="polite">
        <div className="fibernet-top-bar-wrap">
          <div ref={barRef} id="fibernet-top-bar" className="fibernet-top-bar" />
        </div>

        <div
          ref={pillRef}
          id="fibernet-top-pill"
          className="fibernet-top-pill"
          data-active="false"
        >
          <div className="inline-flex items-center gap-2 rounded-full bg-slate-900/90 px-3.5 py-1.5 text-xs font-medium text-white shadow-lg ring-1 ring-white/15 backdrop-blur-md">
            <svg
              className="h-3.5 w-3.5 animate-spin text-cyan-400"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-90"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
            <span ref={pillTextRef} id="fibernet-top-pill-text">
              Memuat...
            </span>
          </div>
        </div>

        <div
          ref={overlayRef}
          id="fibernet-overlay"
          className="fibernet-overlay"
          data-active="false"
          role="status"
        >
          <div className="mx-4 flex max-w-xs flex-col items-center gap-3 rounded-2xl border border-gray-100 bg-white px-7 py-6 text-center shadow-2xl">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-cyan-50">
              <svg
                className="h-6 w-6 animate-spin text-cyan-600"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  className="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="4"
                />
                <path
                  className="opacity-90"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
            </div>
            <div>
              <p
                ref={overlayTextRef}
                id="fibernet-overlay-text"
                className="text-sm font-semibold text-gray-900"
              >
                Memproses...
              </p>
              <p className="mt-0.5 text-xs text-gray-500">
                Mohon tunggu sebentar
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
