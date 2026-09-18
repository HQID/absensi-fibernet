<div class="space-y-6">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Peserta PKL</h1>
                <p class="mt-1 text-sm text-gray-500">Kelola masa periode PKL dan status aktif siswa.</p>
            </div>
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nama</th>
                            <th scope="col" class="px-6 py-3">NIM / NISN</th>
                            <th scope="col" class="px-6 py-3">Sekolah/Kampus</th>
                            <th scope="col" class="px-6 py-3">Jurusan</th>
                            <th scope="col" class="px-6 py-3">Periode</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($students as $student)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $student->user->name }}</td>
                                <td class="px-6 py-4">{{ $student->student_code }}</td>
                                <td class="px-6 py-4">{{ $student->school }}</td>
                                <td class="px-6 py-4">{{ $student->major }}</td>
                                <td class="px-6 py-4">
                                    {{ $student->start_date->format('d M Y') }} - {{ $student->end_date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($student->status === 'active' && now()->lessThanOrEqualTo($student->end_date->endOfDay()))
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 uppercase">AKTIF</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 uppercase">NONAKTIF</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="editStudent({{ $student->id }})" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">Belum ada peserta PKL yang terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($students->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
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
