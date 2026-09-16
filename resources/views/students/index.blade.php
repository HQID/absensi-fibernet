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

            <div class="px-6 py-12 text-center text-sm text-gray-500">
                Belum ada data peserta PKL.
            </div>

        </div>

    </div>

</x-layouts::app>