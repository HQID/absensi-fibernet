<x-layouts::app :title="__('Admin Dashboard')">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Dashboard Presensi
                    </h1>
                    <p class="text-sm text-gray-500">
                        Monitoring presensi peserta PKL PT Fiber Networks Indonesia Branch Palu.
                    </p>
                </div>
            </div>
        </div>

        <livewire:dashboard-content />
    </div>
</x-layouts::app>
