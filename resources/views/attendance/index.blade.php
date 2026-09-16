<x-layouts::app :title="__('Presensi')">

    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Presensi
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Kelola dan lakukan presensi peserta PKL.
            </p>
        </div>

        <livewire:student-attendance />

    </div>

</x-layouts::app>