import type { Metadata } from "next"
import { RegisterForm } from "@/components/auth/auth-forms"

export const metadata: Metadata = {
  title: "Daftar Akun Baru",
}

export default function RegisterPage() {
  return <RegisterForm />
}
