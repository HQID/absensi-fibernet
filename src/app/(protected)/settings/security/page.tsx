import type { Metadata } from "next"
import {
  SecuritySettingsForm,
  SettingsCardHeader,
} from "@/components/settings/settings-ui"

export const metadata: Metadata = {
  title: "Security settings",
}

export default function SecuritySettingsPage() {
  return (
    <SettingsCardHeader
      heading="Perbarui Kata Sandi"
      subheading="Pastikan akun Anda menggunakan kata sandi acak yang panjang agar tetap aman."
    >
      <SecuritySettingsForm />
    </SettingsCardHeader>
  )
}
