"use client"

import { useId, useState, type InputHTMLAttributes } from "react"
import { EyeIcon, EyeSlashIcon } from "@heroicons/react/24/outline"

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  viewable?: boolean
}

export function Input({
  label,
  error,
  viewable = false,
  type = "text",
  id,
  className = "",
  ...props
}: InputProps) {
  const generatedId = useId()
  const inputId = id || generatedId
  const [visible, setVisible] = useState(false)

  const resolvedType = viewable && type === "password"
    ? visible
      ? "text"
      : "password"
    : type

  return (
    <div data-flux-field="" className="grid gap-2">
      {label && (
        <label
          htmlFor={inputId}
          data-flux-label=""
          className="text-sm font-medium text-zinc-800 select-none"
        >
          {label}
        </label>
      )}

      <div className="relative">
        <input
          id={inputId}
          type={resolvedType}
          data-flux-control=""
          aria-invalid={error ? true : undefined}
          className={`w-full h-10 rounded-lg border bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 shadow-xs transition disabled:bg-zinc-50 disabled:text-zinc-500 ${
            viewable ? "pr-10" : ""
          } ${
            error
              ? "border-rose-500 focus:ring-rose-500"
              : "border-zinc-200 hover:border-zinc-300"
          } ${className}`}
          {...props}
        />

        {viewable && type === "password" && (
          <button
            type="button"
            onClick={() => setVisible((v) => !v)}
            aria-label={visible ? "Sembunyikan kata sandi" : "Lihat kata sandi"}
            className="absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400 hover:text-zinc-700 cursor-pointer"
          >
            {visible ? (
              <EyeSlashIcon className="h-4 w-4" />
            ) : (
              <EyeIcon className="h-4 w-4" />
            )}
          </button>
        )}
      </div>

      {error && <p className="text-xs font-medium text-rose-600">{error}</p>}
    </div>
  )
}
