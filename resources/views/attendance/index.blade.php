<x-layouts::app :title="__('Presensi')">

    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Presensi
                    </h1>
                    <p class="text-sm text-gray-500">
                        Kelola dan lakukan presensi peserta PKL.
                    </p>
                </div>
            </div>
        </div>

        <livewire:student-attendance />

    </div>

</x-layouts::app>