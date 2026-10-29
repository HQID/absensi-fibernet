<x-layouts::app :title="__('Student Dashboard')">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">
                Halo, {{ Auth::user()->name }} 👋
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Berikut adalah riwayat presensi Anda.
            </p>
        </div>

        <livewire:dashboard-content />
    </div>
</x-layouts::app>
