<?php

use App\Services\StorageUsageService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Informasi Penyimpanan')] class extends Component
{
    /**
     * @var array{
     *     disk: string,
     *     driver: string,
     *     bucket: string|null,
     *     total_bytes: int,
     *     quota_mb: int,
     *     quota_bytes: int,
     *     percent_used: float,
     *     active_count: int,
     *     active_bytes: int,
     *     temp_count: int,
     *     temp_bytes: int,
     *     orphan_count: int,
     *     orphan_bytes: int,
     *     cleanable_count: int,
     *     cleanable_bytes: int,
     *     checked_at: string,
     *     error: string|null
     * }
     */
    public array $stats;

    public function mount(StorageUsageService $storageService): void
    {
        $this->stats = $storageService->getStats();
    }

    public function refreshStats(StorageUsageService $storageService): void
    {
        $this->stats = $storageService->getStats(forceRefresh: true);

        Flux::toast(
            text: 'Data kapasitas penyimpanan berhasil diperbarui.',
            heading: 'Diperbarui',
            variant: 'success',
        );
    }

    public function cleanupStorage(StorageUsageService $storageService): void
    {
        $result = $storageService->cleanupUnusedFiles();
        $this->stats = $storageService->getStats(forceRefresh: true);

        Flux::modal('cleanup-storage-modal')->close();

        $freedText = StorageUsageService::formatBytes($result['freed_bytes']);
        Flux::toast(
            text: "{$result['deleted_count']} file sampah ({$freedText}) berhasil dihapus dari penyimpanan.",
            heading: 'Pembersihan Selesai',
            variant: 'success',
        );
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Informasi Penyimpanan') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Informasi Penyimpanan')"
        :subheading="__('Pantau pemakaian kuota foto presensi dan bersihkan file sementara yang tidak terpakai.')"
    >
        @php
            $percentBar = min(100, max(0, $stats['percent_used']));
            $barColor = match (true) {
                $stats['percent_used'] >= 90 => 'bg-rose-600',
                $stats['percent_used'] >= 75 => 'bg-amber-500',
                default => 'bg-gradient-to-r from-blue-500 to-cyan-500',
            };
        @endphp

        <div class="my-6 space-y-6">
            @if($stats['error'])
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p class="font-semibold">Tidak dapat memuat rincian bucket</p>
                    <p class="mt-1 text-xs text-rose-700">{{ $stats['error'] }}</p>
                </div>
            @endif

            <!-- Main Quota Overview Card -->
            <div class="rounded-2xl border border-gray-100 bg-slate-50/70 p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        @if($stats['driver'] === 's3')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Cloud S3 / Supabase ({{ $stats['bucket'] ?? $stats['disk'] }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-500/20">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                Penyimpanan Lokal ({{ $stats['disk'] }})
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500">Dicek: {{ $stats['checked_at'] }}</span>
                        <flux:button
                            type="button"
                            size="sm"
                            variant="filled"
                            wire:click="refreshStats"
                            wire:loading.attr="disabled"
                            data-test="refresh-storage-button"
                        >
                            Segarkan
                        </flux:button>
                    </div>
                </div>

                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <div>
                            <span class="text-2xl font-bold text-gray-900">
                                {{ \App\Services\StorageUsageService::formatBytes($stats['total_bytes']) }}
                            </span>
                            <span class="text-sm text-gray-500">
                                dari kuota {{ \App\Services\StorageUsageService::formatBytes($stats['quota_bytes']) }}
                            </span>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">
                            {{ number_format($stats['percent_used'], 2) }}%
                        </span>
                    </div>

                    <div class="mt-2.5 h-3 w-full overflow-hidden rounded-full bg-gray-200/80">
                        <div
                            class="h-full rounded-full transition-all duration-300 {{ $barColor }}"
                            style="width: {{ $percentBar }}%"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- 3 Category Breakdown -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-xs">
                    <p class="text-xs font-medium text-gray-500">Foto Presensi Aktif</p>
                    <p class="mt-1.5 text-lg font-bold text-emerald-700">
                        {{ \App\Services\StorageUsageService::formatBytes($stats['active_bytes']) }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $stats['active_count'] }} file tercatat di database</p>
                </div>

                <div class="rounded-xl border {{ $stats['temp_count'] > 0 ? 'border-amber-200 bg-amber-50/40' : 'border-gray-100 bg-white' }} p-4 shadow-xs">
                    <p class="text-xs font-medium text-gray-500">File Sementara (livewire-tmp)</p>
                    <p class="mt-1.5 text-lg font-bold {{ $stats['temp_count'] > 0 ? 'text-amber-700' : 'text-gray-900' }}">
                        {{ \App\Services\StorageUsageService::formatBytes($stats['temp_bytes']) }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $stats['temp_count'] }} file duplikat sementara</p>
                </div>

                <div class="rounded-xl border {{ $stats['orphan_count'] > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-gray-100 bg-white' }} p-4 shadow-xs">
                    <p class="text-xs font-medium text-gray-500">Foto Tanpa Data (Orphan)</p>
                    <p class="mt-1.5 text-lg font-bold {{ $stats['orphan_count'] > 0 ? 'text-rose-700' : 'text-gray-900' }}">
                        {{ \App\Services\StorageUsageService::formatBytes($stats['orphan_bytes']) }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $stats['orphan_count'] }} file tanpa riwayat presensi</p>
                </div>
            </div>

            <!-- Cleanup Action Section -->
            <div class="rounded-xl border border-gray-100 bg-white p-4 sm:flex sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-sm font-semibold text-gray-900">Pembersih File Sampah</h3>
                    @if($stats['cleanable_count'] > 0)
                        <p class="text-xs text-amber-700 font-medium">
                            Ditemukan {{ $stats['cleanable_count'] }} file tak terpakai ({{ \App\Services\StorageUsageService::formatBytes($stats['cleanable_bytes']) }}) yang aman dibersihkan.
                        </p>
                    @elseif(($stats['temp_count'] + $stats['orphan_count']) > 0)
                        <p class="text-xs text-gray-500">
                            Terdapat {{ $stats['temp_count'] + $stats['orphan_count'] }} file baru (&lt; 15 menit) yang dilindungi sementara agar tidak mengganggu proses upload aktif.
                        </p>
                    @else
                        <p class="text-xs text-gray-500">
                            Penyimpanan bersih. Tidak ada sisa file sementara maupun foto yatim.
                        </p>
                    @endif
                </div>

                <div class="mt-3 sm:mt-0 shrink-0">
                    @if($stats['cleanable_count'] > 0)
                        <flux:modal.trigger name="cleanup-storage-modal">
                            <flux:button variant="danger" size="sm" data-test="open-cleanup-modal-button">
                                Bersihkan File Sampah
                            </flux:button>
                        </flux:modal.trigger>
                    @else
                        <flux:button variant="filled" size="sm" disabled data-test="open-cleanup-modal-button">
                            Bersihkan File Sampah
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>

        <flux:modal name="cleanup-storage-modal" class="md:w-[26rem]">
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">Bersihkan File Sampah?</flux:heading>
                    <flux:subheading class="mt-1">
                        Sistem akan menghapus <strong>{{ $stats['cleanable_count'] }} file</strong> tak terpakai sebesar <strong>{{ \App\Services\StorageUsageService::formatBytes($stats['cleanable_bytes']) }}</strong> dari folder <code>livewire-tmp</code> dan file yatim yang berusia lebih dari 15 menit.
                    </flux:subheading>
                </div>

                <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-xs text-emerald-800">
                    Seluruh <strong>{{ $stats['active_count'] }} foto presensi aktif</strong> peserta PKL yang tercatat di database dijamin aman dan tidak akan tersentuh.
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="button"
                        variant="danger"
                        wire:click="cleanupStorage"
                        wire:loading.attr="disabled"
                        data-test="confirm-cleanup-storage-button"
                    >
                        Ya, Hapus File Sampah
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </x-pages::settings.layout>
</section>
