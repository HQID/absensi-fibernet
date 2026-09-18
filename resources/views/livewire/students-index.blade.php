<div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Peserta PKL</h1>
                    <p class="mt-1 text-sm text-gray-500">Kelola masa periode PKL dan status aktif siswa.</p>
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
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $student->user->name }}</td>
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
                                    <button wire:click="editStudent({{ $student->id }})" class="inline-flex items-center gap-1 text-sm font-medium text-cyan-600 hover:text-cyan-800 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Edit
                                    </button>
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
                <p class="text-sm text-gray-500">Siswa: {{ $editingStudent->user->name }}</p>
                
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
</div>
