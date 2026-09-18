<x-layouts::app :title="__('Peserta PKL')">

    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Peserta PKL
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Daftar peserta PKL yang terdaftar.
                </p>
            </div>

            <button
                type="button"
                disabled
                class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-500"
            >
                + Tambah Peserta
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nama</th>
                            <th scope="col" class="px-6 py-3">NIM / NISN</th>
                            <th scope="col" class="px-6 py-3">Sekolah/Kampus</th>
                            <th scope="col" class="px-6 py-3">Jurusan</th>
                            <th scope="col" class="px-6 py-3">Periode PKL</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($students as $student)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $student->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $student->user->email }}</div>
                                </td>
                                <td class="px-6 py-4">{{ $student->student_code }}</td>
                                <td class="px-6 py-4">{{ $student->school }}</td>
                                <td class="px-6 py-4">{{ $student->major }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-900">{{ \Carbon\Carbon::parse($student->start_date)->format('d M Y') }} -</div>
                                    <div class="text-xs text-gray-900">{{ \Carbon\Carbon::parse($student->end_date)->format('d M Y') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 uppercase">{{ $student->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Belum ada data peserta PKL yang terdaftar.
                                </td>
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

    </div>

</x-layouts::app>