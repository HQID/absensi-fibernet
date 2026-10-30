"use client"

import { useEffect, useRef, type ReactNode } from "react"
import { XMarkIcon } from "@heroicons/react/24/outline"

export interface ModalProps {
  open: boolean
  onClose: () => void
  title?: string
  description?: string
  maxWidthClass?: string
  children: ReactNode
}

export function Modal({
  open,
  onClose,
  title,
  description,
  maxWidthClass = "max-w-lg",
  children,
}: ModalProps) {
  const dialogRef = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return

    if (open && !dialog.open) {
      dialog.showModal()
    } else if (!open && dialog.open) {
      dialog.close()
    }
  }, [open])

  return (
    <dialog
      ref={dialogRef}
      onClose={onClose}
      onClick={(e) => {
        if (e.target === dialogRef.current) onClose()
      }}
      className={`m-auto w-[calc(100%-2rem)] ${maxWidthClass} rounded-2xl border border-gray-100 bg-white p-0 text-zinc-900 shadow-2xl backdrop:bg-slate-900/40 backdrop:backdrop-blur-xs`}
    >
      <div className="p-6">
        {(title || description) && (
          <div className="mb-4 flex items-start justify-between gap-4">
            <div>
              {title && (
                <h2 className="text-lg font-bold text-zinc-900">{title}</h2>
              )}
              {description && (
                <p className="mt-1 text-sm text-zinc-500">{description}</p>
              )}
            </div>
            <button
              type="button"
              onClick={onClose}
              aria-label="Tutup modal"
              className="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 cursor-pointer"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          </div>
        )}
        {children}
      </div>
    </dialog>
  )
}
