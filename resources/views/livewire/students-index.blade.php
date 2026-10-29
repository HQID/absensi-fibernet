<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Peserta PKL</h1>
                    <p class="text-sm text-gray-500">Kelola masa periode PKL dan status aktif siswa.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="fibernet-table-header">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Nama</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">NIM / NISN</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Sekolah/Kampus</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Jurusan</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Periode</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-gray-900">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50/50 transition-colors" wire:key="student-row-{{ $student->id }}">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $student->user?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $student->student_code }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $student->school }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $student->major }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $student->start_date->format('d M Y') }} - {{ $student->end_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($student->status === 'active' && now()->lessThanOrEqualTo($student->end_date->endOfDay()))
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 uppercase">AKTIF</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 uppercase">NONAKTIF</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="editStudent({{ $student->id }})" class="inline-flex items-center gap-1 text-sm font-medium text-cyan-600 hover:text-cyan-800 transition-colors cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Edit
                                    </button>
                                    <button type="button" wire:click="confirmDeleteStudent({{ $student->id }})" class="inline-flex items-center gap-1 text-sm font-medium text-rose-600 hover:text-rose-800 transition-colors cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-12 w-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <p class="text-sm text-gray-500">Belum ada peserta PKL yang terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Student Modal -->
    <flux:modal name="edit-student-modal" class="md:w-96">
        <div class="p-4 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Ubah Data PKL</h2>
            @if($editingStudent)
                <p class="text-sm text-gray-500">Siswa: {{ $editingStudent->user?->name ?? '-' }}</p>

                <form wire:submit="saveStudent" class="space-y-4 mt-4">
                    <flux:input type="date" wire:model="endDate" :label="__('Tanggal Selesai PKL')" required />

                    <flux:radio.group wire:model="status" :label="__('Status Akun')">
                        <flux:radio value="active" :label="__('Aktif')" />
                        <flux:radio value="inactive" :label="__('Nonaktif')" />
                    </flux:radio.group>

                    <div class="flex justify-end gap-2 mt-4">
                        <flux:modal.close>
                            <flux:button variant="ghost">Batal</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>

    <!-- 2-Step Permanent Delete Student Modal -->
    <flux:modal name="delete-student-modal" class="md:w-[28rem]">
        <div class="p-4 space-y-4">
            @if($deletingStudent)
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                        Konfirmasi Tahap {{ $deleteStep }} dari 2
                    </span>
                </div>

                @if($deleteStep === 1)
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Hapus Permanen Peserta PKL?</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Periksa kembali detail peserta dan dampak penghapusan di bawah ini.
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-sm space-y-1.5">
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-500">Nama Peserta</span>
                                <span class="font-semibold text-gray-900 text-right">{{ $deletingStudent->user?->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-500">NIM / NISN</span>
                                <span class="font-semibold text-gray-900 text-right">{{ $deletingStudent->student_code }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-500">Sekolah / Kampus</span>
                                <span class="font-medium text-gray-700 text-right">{{ $deletingStudent->school }}</span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-3.5 text-sm text-rose-900 space-y-2">
                            <p class="font-semibold">Seluruh data berikut akan dihapus permanen:</p>
                            <ul class="list-disc pl-5 space-y-1 text-xs sm:text-sm text-rose-800">
                                <li>Akun login dan biodata peserta PKL</li>
                                <li><strong>{{ $deleteAttendanceCount }}</strong> riwayat presensi di menu Reports</li>
                                <li><strong>{{ $deletePhotoCount }}</strong> file foto selfie di storage Supabase</li>
                            </ul>
                            <p class="pt-1 text-xs text-rose-700 font-medium">
                                Pastikan Anda sudah mengunduh Export XLSX peserta ini bila masih diperlukan. Data yang dihapus tidak dapat dipulihkan.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <flux:modal.close>
                                <flux:button variant="ghost">Batal</flux:button>
                            </flux:modal.close>
                            <flux:button type="button" variant="danger" wire:click="proceedToFinalDeleteStep">
                                Saya Mengerti, Lanjut →
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div
                        class="space-y-4"
                        wire:key="delete-step-2-{{ $deletingStudent->id }}"
                        x-data="{ typed: '', expected: @js(trim($deletingStudent->student_code)) }"
                    >
                        <div>
                            <h2 class="text-lg font-bold text-rose-700">Verifikasi Akhir Penghapusan</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Ketik ulang NIM / NISN <code class="rounded bg-rose-50 px-1.5 py-0.5 font-bold text-rose-700 select-all">{{ $deletingStudent->student_code }}</code> milik <strong>{{ $deletingStudent->user?->name ?? '-' }}</strong> untuk membuka kunci tombol hapus permanen.
                            </p>
                        </div>

                        <form wire:submit="deleteStudent" class="space-y-4">
                            <div>
                                <input
                                    type="text"
                                    x-model="typed"
                                    wire:model="deleteConfirmationInput"
                                    autocomplete="off"
                                    placeholder="Ketik {{ $deletingStudent->student_code }}"
                                    class="block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-rose-500 sm:text-sm"
                                />
                                @error('deleteConfirmationInput')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-2">
                                <flux:button type="button" variant="ghost" wire:click="backToFirstDeleteStep">
                                    ← Kembali
                                </flux:button>
                                <flux:button
                                    type="submit"
                                    variant="danger"
                                    x-bind:disabled="typed.trim() !== expected"
                                    data-test="confirm-permanent-delete-student"
                                >
                                    Hapus Permanen Data & Foto
                                </flux:button>
                            </div>
                        </form>
                    </div>
                @endif
            @endif
        </div>
    </flux:modal>
</div>
