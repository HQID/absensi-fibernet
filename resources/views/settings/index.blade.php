<x-layouts::app :title="__('Pengaturan')">

    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Pengaturan
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Konfigurasi sistem presensi.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

            <h2 class="font-semibold text-gray-900">
                Geofence
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Konfigurasi lokasi dan radius presensi.
            </p>

            <div class="mt-4">
                <button
                    type="button"
                    disabled
                    class="rounded-lg bg-gray-200 px-4 py-2 text-sm text-gray-500"
                >
                    Belum tersedia
                </button>
            </div>

        </div>

    </div>

</x-layouts::app>