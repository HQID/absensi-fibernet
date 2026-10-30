"use client"

import { useState } from "react"

export interface SwitchProps {
  name?: string
  label?: string
  description?: string
  defaultChecked?: boolean
  checked?: boolean
  onChange?: (checked: boolean) => void
  disabled?: boolean
}

export function Switch({
  name,
  label,
  description,
  defaultChecked = false,
  checked: controlledChecked,
  onChange,
  disabled = false,
}: SwitchProps) {
  const [internalChecked, setInternalChecked] = useState(defaultChecked)
  const isChecked =
    controlledChecked !== undefined ? controlledChecked : internalChecked

  function toggle() {
    if (disabled) return
    const next = !isChecked
    if (controlledChecked === undefined) setInternalChecked(next)
    onChange?.(next)
  }

  return (
    <div className="flex items-center justify-between gap-4">
      {(label || description) && (
        <div className="grid gap-0.5">
          {label && (
            <span className="text-sm font-medium text-zinc-800">{label}</span>
          )}
          {description && (
            <span className="text-xs text-zinc-500">{description}</span>
          )}
        </div>
      )}

      {name && (
        <input type="hidden" name={name} value={isChecked ? "true" : "false"} />
      )}

      <button
        type="button"
        role="switch"
        aria-checked={isChecked}
        disabled={disabled}
        onClick={toggle}
        className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-[#0891b2] focus:ring-offset-2 disabled:opacity-50 ${
          isChecked ? "bg-[#0891b2]" : "bg-zinc-200"
        }`}
      >
        <span
          className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition duration-150 ${
            isChecked ? "translate-x-5" : "translate-x-0"
          }`}
        />
      </button>
    </div>
  )
}
