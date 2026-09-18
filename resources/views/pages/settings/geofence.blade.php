<?php

use Livewire\Component;
use App\Models\OfficeLocation;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public $location_id;
    public $name = '';
    public $latitude = '';
    public $longitude = '';
    public $radius_meters = 100;
    public $max_accuracy_meters = 50;
    public $active = true;

    public function mount()
    {
        $location = OfficeLocation::first();
        if ($location) {
            $this->location_id = $location->id;
            $this->name = $location->name;
            $this->latitude = $location->latitude;
            $this->longitude = $location->longitude;
            $this->radius_meters = $location->radius_meters;
            $this->max_accuracy_meters = $location->max_accuracy_meters;
            $this->active = $location->active;
        }
    }

    public function updateGeofence()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10'],
            'max_accuracy_meters' => ['required', 'integer', 'min:5'],
            'active' => ['boolean'],
        ]);

        if ($this->location_id) {
            $location = OfficeLocation::find($this->location_id);
            $location->update($validated);
        } else {
            $location = OfficeLocation::create($validated);
            $this->location_id = $location->id;
        }

        $this->dispatch('geofence-updated', name: $location->name);
        
        flux()->toast(
            text: 'Pengaturan lokasi kantor berhasil disimpan.',
            heading: 'Tersimpan',
            variant: 'success',
        );
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Pengaturan Geofence') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Pengaturan Geofence')" :subheading="__('Tentukan titik koordinat kantor, radius area absensi, dan batas akurasi GPS yang diizinkan.')">
        <form wire:submit="updateGeofence" class="my-6 space-y-6">
            <flux:input wire:model="name" :label="__('Nama Kantor')" required />

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:input wire:model="latitude" :label="__('Latitude')" type="number" step="any" required />
                <flux:input wire:model="longitude" :label="__('Longitude')" type="number" step="any" required />
            </div>
            <p class="text-xs text-zinc-500 mt-[-1rem] mb-4">Contoh: Latitude -0.897587, Longitude 119.873215</p>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:input wire:model="radius_meters" :label="__('Radius (Meter)')" type="number" required />
                <flux:input wire:model="max_accuracy_meters" :label="__('Batas Akurasi GPS (Meter)')" type="number" required />
            </div>
            
            <flux:switch wire:model="active" :label="__('Status Aktif')" :description="__('Tandai apakah titik lokasi ini sedang aktif digunakan untuk absensi atau tidak.')" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Simpan Pengaturan') }}</flux:button>
                </div>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
