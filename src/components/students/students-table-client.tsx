"use client"

import { useState, useTransition } from "react"
import { useRouter } from "next/navigation"
import { toast } from "sonner"
import {
  deleteStudentPermanentlyAction,
  updateStudentAction,
} from "@/actions/students"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Modal } from "@/components/ui/modal"

export type StudentRowItem = {
  id: number
  name: string
  student_code: string
  school: string
  major: string
  start_date: string
  end_date: string
  period_label: string
  status: string
  is_effectively_active: boolean
  attendance_count: number
  photo_count: number
}

export function StudentsTableClient({
  students,
}: {
  students: StudentRowItem[]
}) {
  const router = useRouter()
  const [isPending, startTransition] = useTransition()

  const [editingStudent, setEditingStudent] = useState<StudentRowItem | null>(
    null
  )
  const [editEndDate, setEditEndDate] = useState("")
  const [editStatus, setEditStatus] = useState<"active" | "inactive">("active")
  const [editError, setEditError] = useState<string | null>(null)

  const [deletingStudent, setDeletingStudent] = useState<StudentRowItem | null>(
    null
  )
  const [deleteStep, setDeleteStep] = useState<1 | 2>(1)
  const [typedCode, setTypedCode] = useState("")
  const [deleteError, setDeleteError] = useState<string | null>(null)

  function openEdit(student: StudentRowItem) {
    setEditingStudent(student)
    setEditEndDate(student.end_date)
    setEditStatus(student.status === "inactive" ? "inactive" : "active")
    setEditError(null)
  }

  function closeEdit() {
    if (isPending) return
    setEditingStudent(null)
    setEditError(null)
  }

  function openDelete(student: StudentRowItem) {
    setDeletingStudent(student)
    setDeleteStep(1)
    setTypedCode("")
    setDeleteError(null)
  }

  function closeDelete() {
    if (isPending) return
    setDeletingStudent(null)
    setDeleteStep(1)
    setTypedCode("")
    setDeleteError(null)
  }

  function handleSaveEdit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    if (!editingStudent) return
    setEditError(null)

    const formData = new FormData()
    formData.set("student_id", String(editingStudent.id))
    formData.set("end_date", editEndDate)
    formData.set("status", editStatus)

    startTransition(async () => {
      const result = await updateStudentAction(formData)
      if (result.error) {
        setEditError(result.error)
        return
      }
      setEditingStudent(null)
      toast.success("Tersimpan", {
        description: result.success || "Data siswa berhasil diperbarui.",
      })
      router.refresh()
    })
  }

  function handleConfirmDelete(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    if (!deletingStudent) return
    setDeleteError(null)

    const formData = new FormData()
    formData.set("student_id", String(deletingStudent.id))
    formData.set("confirmation_input", typedCode)

    startTransition(async () => {
      const result = await deleteStudentPermanentlyAction(formData)
      if (result.error) {
        setDeleteError(result.error)
        return
      }
      setDeletingStudent(null)
      setDeleteStep(1)
      setTypedCode("")
      toast.success("Peserta Dihapus", {
        description: result.success || "Data peserta PKL berhasil dihapus.",
      })
      router.refresh()
    })
  }

  return (
    <>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="fibernet-table-header">
            <tr>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                Nama
              </th>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                NIM / NISN
              </th>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                Sekolah/Kampus
              </th>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                Jurusan
              </th>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                Periode
              </th>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                Status
              </th>
              <th scope="col" className="px-6 py-4 font-semibold text-gray-900">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {students.length > 0 ? (
              students.map((student) => (
                <tr
                  key={student.id}
                  className="hover:bg-gray-50/50 transition-colors"
                >
                  <td className="px-6 py-4 font-medium text-gray-900">
                    {student.name}
                  </td>
                  <td className="px-6 py-4 text-gray-600">
                    {student.student_code}
                  </td>
                  <td className="px-6 py-4 text-gray-600">{student.school}</td>
                  <td className="px-6 py-4 text-gray-600">{student.major}</td>
                  <td className="px-6 py-4 text-gray-600 whitespace-nowrap">
                    {student.period_label}
                  </td>
                  <td className="px-6 py-4">
                    {student.is_effectively_active ? (
                      <span className="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">
                        AKTIF
                      </span>
                    ) : (
                      <span className="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">
                        NONAKTIF
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <button
                        type="button"
                        onClick={() => openEdit(student)}
                        className="inline-flex items-center gap-1 text-sm font-medium text-cyan-600 hover:text-cyan-800 transition-colors cursor-pointer"
                      >
                        <svg
                          className="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                          />
                        </svg>
                        Edit
                      </button>
                      <button
                        type="button"
                        onClick={() => openDelete(student)}
                        className="inline-flex items-center gap-1 text-sm font-medium text-rose-600 hover:text-rose-800 transition-colors cursor-pointer"
                      >
                        <svg
                          className="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                          />
                        </svg>
                        Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={7} className="px-6 py-16 text-center">
                  <div className="flex flex-col items-center">
                    <div className="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                      <svg
                        className="w-6 h-6 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                      </svg>
                    </div>
                    <p className="text-sm text-gray-500">
                      Belum ada peserta PKL yang terdaftar.
                    </p>
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Edit Student Modal */}
      <Modal
        open={editingStudent !== null}
        onClose={closeEdit}
        title="Ubah Data PKL"
        description={
          editingStudent ? `Siswa: ${editingStudent.name}` : undefined
        }
        maxWidthClass="max-w-sm"
      >
        {editingStudent && (
          <form onSubmit={handleSaveEdit} className="space-y-4">
            <Input
              type="date"
              label="Tanggal Selesai PKL"
              min={editingStudent.start_date}
              value={editEndDate}
              onChange={(e) => setEditEndDate(e.target.value)}
              error={editError ?? undefined}
              required
            />

            <fieldset className="space-y-2">
              <legend className="text-sm font-medium text-zinc-800">
                Status Akun
              </legend>
              <div className="space-y-2">
                <label className="flex items-center gap-2.5 text-sm text-zinc-700 cursor-pointer">
                  <input
                    type="radio"
                    name="status"
                    value="active"
                    checked={editStatus === "active"}
                    onChange={() => setEditStatus("active")}
                    className="h-4 w-4 accent-[#0891b2]"
                  />
                  Aktif
                </label>
                <label className="flex items-center gap-2.5 text-sm text-zinc-700 cursor-pointer">
                  <input
                    type="radio"
                    name="status"
                    value="inactive"
                    checked={editStatus === "inactive"}
                    onChange={() => setEditStatus("inactive")}
                    className="h-4 w-4 accent-[#0891b2]"
                  />
                  Nonaktif
                </label>
              </div>
            </fieldset>

            <div className="flex justify-end gap-2 pt-2">
              <Button type="button" variant="ghost" onClick={closeEdit}>
                Batal
              </Button>
              <Button type="submit" variant="primary" loading={isPending}>
                Simpan Perubahan
              </Button>
            </div>
          </form>
        )}
      </Modal>

      {/* 2-Step Permanent Delete Student Modal */}
      <Modal
        open={deletingStudent !== null}
        onClose={closeDelete}
        maxWidthClass="max-w-[28rem]"
      >
        {deletingStudent && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                Konfirmasi Tahap {deleteStep} dari 2
              </span>
            </div>

            {deleteStep === 1 ? (
              <div className="space-y-4">
                <div>
                  <h2 className="text-lg font-bold text-gray-900">
                    Hapus Permanen Peserta PKL?
                  </h2>
                  <p className="mt-1 text-sm text-gray-500">
                    Periksa kembali detail peserta dan dampak penghapusan di
                    bawah ini.
                  </p>
                </div>

                <div className="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-sm space-y-1.5">
                  <div className="flex justify-between gap-2">
                    <span className="text-gray-500">Nama Peserta</span>
                    <span className="font-semibold text-gray-900 text-right">
                      {deletingStudent.name}
                    </span>
                  </div>
                  <div className="flex justify-between gap-2">
                    <span className="text-gray-500">NIM / NISN</span>
                    <span className="font-semibold text-gray-900 text-right">
                      {deletingStudent.student_code}
                    </span>
                  </div>
                  <div className="flex justify-between gap-2">
                    <span className="text-gray-500">Sekolah / Kampus</span>
                    <span className="font-medium text-gray-700 text-right">
                      {deletingStudent.school}
                    </span>
                  </div>
                </div>

                <div className="rounded-xl border border-rose-200 bg-rose-50/70 p-3.5 text-sm text-rose-900 space-y-2">
                  <p className="font-semibold">
                    Seluruh data berikut akan dihapus permanen:
                  </p>
                  <ul className="list-disc pl-5 space-y-1 text-xs sm:text-sm text-rose-800">
                    <li>Akun login dan biodata peserta PKL</li>
                    <li>
                      <strong>{deletingStudent.attendance_count}</strong>{" "}
                      riwayat presensi di menu Reports
                    </li>
                    <li>
                      <strong>{deletingStudent.photo_count}</strong> file foto
                      selfie di storage Supabase
                    </li>
                  </ul>
                  <p className="pt-1 text-xs text-rose-700 font-medium">
                    Pastikan Anda sudah mengunduh Export XLSX peserta ini bila
                    masih diperlukan. Data yang dihapus tidak dapat dipulihkan.
                  </p>
                </div>

                <div className="flex justify-end gap-2 pt-2">
                  <Button type="button" variant="ghost" onClick={closeDelete}>
                    Batal
                  </Button>
                  <Button
                    type="button"
                    variant="danger"
                    onClick={() => {
                      setDeleteStep(2)
                      setTypedCode("")
                      setDeleteError(null)
                    }}
                  >
                    Saya Mengerti, Lanjut →
                  </Button>
                </div>
              </div>
            ) : (
              <div className="space-y-4">
                <div>
                  <h2 className="text-lg font-bold text-rose-700">
                    Verifikasi Akhir Penghapusan
                  </h2>
                  <p className="mt-1 text-sm text-gray-600">
                    Ketik ulang NIM / NISN{" "}
                    <code className="rounded bg-rose-50 px-1.5 py-0.5 font-bold text-rose-700 select-all">
                      {deletingStudent.student_code}
                    </code>{" "}
                    milik <strong>{deletingStudent.name}</strong> untuk membuka
                    kunci tombol hapus permanen.
                  </p>
                </div>

                <form onSubmit={handleConfirmDelete} className="space-y-4">
                  <div>
                    <input
                      type="text"
                      value={typedCode}
                      onChange={(e) => setTypedCode(e.target.value)}
                      autoComplete="off"
                      placeholder={`Ketik ${deletingStudent.student_code}`}
                      className="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-rose-500 sm:text-sm"
                    />
                    {deleteError && (
                      <p className="mt-1.5 text-xs font-medium text-rose-600">
                        {deleteError}
                      </p>
                    )}
                  </div>

                  <div className="flex items-center justify-between gap-2 pt-2">
                    <Button
                      type="button"
                      variant="ghost"
                      onClick={() => {
                        setDeleteStep(1)
                        setTypedCode("")
                        setDeleteError(null)
                      }}
                    >
                      ← Kembali
                    </Button>
                    <Button
                      type="submit"
                      variant="danger"
                      loading={isPending}
                      disabled={
                        typedCode.trim() !==
                        deletingStudent.student_code.trim()
                      }
                      data-test="confirm-permanent-delete-student"
                    >
                      Hapus Permanen Data & Foto
                    </Button>
                  </div>
                </form>
              </div>
            )}
          </div>
        )}
      </Modal>
    </>
  )
}
