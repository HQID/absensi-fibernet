<?php

use Livewire\Component;
use App\Models\OfficeLocation;
use Flux\Flux;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public $location_id;
    public $name = '';
    public $latitude = '';
    public $longitude = '';
    public $center_latitude = '';
    public $center_longitude = '';
    public $radius_meters = 100;
    public $max_accuracy_meters = 50;
    public $polygon_coordinates = [];
    public $active = true;

    public function mount()
    {
        $location = OfficeLocation::first();
        if ($location) {
            $this->location_id = $location->id;
            $this->name = $location->name;
            $this->latitude = (string) $location->latitude;
            $this->longitude = (string) $location->longitude;
            $this->center_latitude = $location->center_latitude ? (string) $location->center_latitude : (string) $location->latitude;
            $this->center_longitude = $location->center_longitude ? (string) $location->center_longitude : (string) $location->longitude;
            $this->radius_meters = $location->radius_meters;
            $this->max_accuracy_meters = $location->max_accuracy_meters;
            $this->polygon_coordinates = $location->polygon_coordinates ?? [];
            $this->active = (bool) $location->active;
        }
    }

    public function updateGeofence()
    {
        // Decode polygon if passed as JSON string from JS
        $polygon = $this->polygon_coordinates;
        if (is_string($polygon)) {
            $decoded = json_decode($polygon, true);
            $polygon = is_array($decoded) ? $decoded : [];
        }

        $this->polygon_coordinates = $polygon;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10'],
            'max_accuracy_meters' => ['required', 'integer', 'min:5'],
            'polygon_coordinates' => ['nullable', 'array'],
            'active' => ['boolean'],
        ]);

        $validated['center_latitude'] = $validated['latitude'];
        $validated['center_longitude'] = $validated['longitude'];

        if ($this->location_id) {
            $location = OfficeLocation::find($this->location_id);
            $location->update($validated);
        } else {
            $location = OfficeLocation::create($validated);
            $this->location_id = $location->id;
        }

        $this->dispatch('geofence-updated', name: $location->name);
        
        Flux::toast(
            text: 'Pengaturan lokasi & poligon kantor berhasil disimpan.',
            heading: 'Tersimpan',
            variant: 'success',
        );
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <!-- Leaflet CSS & JS Assets -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <flux:heading level="2" class="sr-only">{{ __('Pengaturan Geofence') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Pengaturan Geofence & Poligon')" :subheading="__('Tentukan batas area kantor menggunakan citra satelit Google Maps beresolusi tinggi.')">
        <form wire:submit="updateGeofence" class="my-6 space-y-6">
            <flux:input wire:model="name" :label="__('Nama Kantor')" required />

            <!-- Interactive Map Polygon Editor Container -->
            <div 
                x-data="geofenceMapEditor()" 
                x-init="initMap()" 
                class="rounded-2xl border border-gray-200 bg-gray-50/50 p-4 space-y-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                            Peta Batas Geofencing Kantor (Citra Satelit Google)
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Gambarkan batas tanah/gedung kantor di atas citra satelit Google Maps untuk validasi presensi <b>Ray Casting</b>.
                        </p>
                    </div>

                    <!-- Polygon Status Pill & Layer Quick Switcher -->
                    <div class="flex items-center gap-2">
                        <template x-if="polygonPoints.length >= 3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Poligon Aktif (<span x-text="polygonPoints.length"></span> Titik)
                            </span>
                        </template>
                        <template x-if="polygonPoints.length < 3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                Mode Radius Lingkaran
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Layer Switcher Segmented Button -->
                <div class="flex flex-wrap items-center justify-between gap-2 bg-white p-1.5 rounded-xl border border-gray-200 text-xs">
                    <span class="text-gray-500 text-[11px] font-medium ml-1">Tipe Peta:</span>
                    <div class="flex items-center gap-1">
                        <button 
                            type="button" 
                            @click="setLayer('hybrid')"
                            class="px-2.5 py-1 rounded-lg font-medium transition-all text-xs flex items-center gap-1"
                            :class="activeLayer === 'hybrid' ? 'bg-cyan-600 text-white shadow-sm font-semibold' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            Google Satellite
                        </button>
                        <button 
                            type="button" 
                            @click="setLayer('streets')"
                            class="px-2.5 py-1 rounded-lg font-medium transition-all text-xs flex items-center gap-1"
                            :class="activeLayer === 'streets' ? 'bg-cyan-600 text-white shadow-sm font-semibold' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            Google Street View
                        </button>
                    </div>
                </div>

                <!-- Leaflet Map Container -->
                <div class="relative">
                    <div 
                        wire:ignore 
                        x-ref="mapContainer" 
                        class="h-[420px] w-full rounded-xl border border-gray-200 shadow-inner z-0"
                    ></div>

                    <!-- Floating Drawing Mode Indicator -->
                    <div 
                        x-show="isDrawing" 
                        x-transition 
                        class="absolute top-3 left-3 z-[1000] bg-cyan-600/95 backdrop-blur text-white px-3 py-1.5 rounded-lg shadow-lg text-xs font-medium flex items-center gap-2"
                    >
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-200 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Mode Menggambar: Klik di peta untuk menambah titik sudut
                    </div>
                </div>

                <!-- Map Action Toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-gray-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Toggle Add Point Mode -->
                        <button 
                            type="button" 
                            @click="toggleDrawing()" 
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 shadow-sm"
                            :class="isDrawing ? 'bg-cyan-600 text-white' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-200'"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span x-text="isDrawing ? 'Selesai Menambah Titik' : '+ Tambah Titik Sudut'"></span>
                        </button>

                        <!-- Auto 4-Point Box Generator -->
                        <button 
                            type="button" 
                            @click="generateDefaultBox()" 
                            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white hover:bg-cyan-50 text-cyan-700 border border-cyan-200 transition-colors flex items-center gap-1.5 shadow-sm"
                            title="Buat poligon persegi otomatis sekitar 40 meter di sekeliling kantor"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"></path></svg>
                            Buat Poligon 4 Titik
                        </button>

                        <!-- Clear Polygon -->
                        <button 
                            type="button" 
                            x-show="polygonPoints.length > 0"
                            @click="if(confirm('Hapus seluruh titik poligon dan kembali ke radius lingkaran?')) clearPolygon()" 
                            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white hover:bg-red-50 text-red-600 border border-red-200 transition-colors flex items-center gap-1.5 shadow-sm"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Hapus Poligon
                        </button>
                    </div>

                    <div class="text-[11px] text-gray-500">
                        <span>💡 Geser nomor marker biru di peta untuk menyesuaikan lekukan atap/tanah gedung.</span>
                    </div>
                </div>

                <!-- Vertices Coordinates Table (Collapsible) -->
                <div x-show="polygonPoints.length > 0" class="pt-2">
                    <details class="text-xs text-gray-600 bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
                        <summary class="font-semibold cursor-pointer text-gray-700 hover:text-cyan-600 flex items-center justify-between">
                            <span>Daftar Titik Sudut Poligon (<span x-text="polygonPoints.length"></span> Koordinat)</span>
                            <span class="text-[10px] text-gray-400">Klik untuk lihat/hapus per titik</span>
                        </summary>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pt-2 border-t border-gray-100">
                            <template x-for="(point, idx) in polygonPoints" :key="idx">
                                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 border border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-cyan-600 text-white font-bold flex items-center justify-center text-[10px] shadow-sm" x-text="idx + 1"></span>
                                        <span class="font-mono text-[11px] text-gray-700">
                                            <span x-text="point.lat.toFixed(6)"></span>, <span x-text="point.lng.toFixed(6)"></span>
                                        </span>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="removePoint(idx)" 
                                        class="text-red-500 hover:text-red-700 p-1 rounded"
                                        title="Hapus titik ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Form Coordinates & Parameters -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:input wire:model.live.debounce.500ms="latitude" :label="__('Latitude Titik Pusat')" type="number" step="any" required />
                <flux:input wire:model.live.debounce.500ms="longitude" :label="__('Longitude Titik Pusat')" type="number" step="any" required />
            </div>
            <p class="text-xs text-zinc-500 mt-[-1rem] mb-2">Geser marker pin merah di peta untuk mengubah titik koordinat pusat secara otomatis.</p>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:input wire:model.live.debounce.500ms="radius_meters" :label="__('Radius Baseline (Meter)')" type="number" required />
                <flux:input wire:model="max_accuracy_meters" :label="__('Batas Toleransi Akurasi GPS (Meter)')" type="number" required />
            </div>
            <p class="text-xs text-zinc-500 mt-[-1rem] mb-4">Radius lingkaran biru (garis putus-putus) bertindak sebagai acuan baseline komparasi skripsi dan fallback jika poligon kosong.</p>
            
            <flux:switch wire:model="active" :label="__('Status Lokasi Aktif')" :description="__('Tandai apakah titik lokasi dan poligon ini sedang aktif digunakan untuk absensi.')" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end w-full">
                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto px-8">{{ __('Simpan Pengaturan Geofence') }}</flux:button>
                </div>
            </div>
        </form>
    </x-pages::settings.layout>

    <!-- Alpine.js Map Editor Controller -->
    <script>
        function geofenceMapEditor() {
            return {
                map: null,
                centerMarker: null,
                circleLayer: null,
                polygonLayer: null,
                vertexMarkers: [],
                polygonPoints: [],
                isDrawing: false,
                activeLayer: 'hybrid',
                layers: {},

                initMap() {
                    // Poll until Leaflet L object is ready
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initMap(), 150);
                        return;
                    }

                    const initialLat = parseFloat(@this.latitude) || -0.874971;
                    const initialLng = parseFloat(@this.longitude) || 119.886639;
                    const initialRadius = parseInt(@this.radius_meters) || 100;

                    // Parse existing polygon from Livewire
                    let existing = @this.polygon_coordinates;
                    if (typeof existing === 'string') {
                        try { existing = JSON.parse(existing); } catch(e) { existing = []; }
                    }
                    this.polygonPoints = Array.isArray(existing) ? existing : [];

                    // 1. Initialize Map
                    this.map = L.map(this.$refs.mapContainer, {
                        center: [initialLat, initialLng],
                        zoom: 18,
                        maxZoom: 21
                    });

                    // 2. Define High-Resolution Google Maps Tile Layers
                    // Google Hybrid: Satelit Asli + Label Jalan & Gedung Terupdate
                    this.layers.hybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                        maxZoom: 21,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps Satellite'
                    });

                    // Google Streets: Peta Jalan Resmi Google
                    this.layers.streets = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                        maxZoom: 21,
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '&copy; Google Maps'
                    });

                    // Set default to Google Hybrid (Satelit Nyata Terupdate)
                    this.layers.hybrid.addTo(this.map);

                    // 3. Center Marker (Draggable)
                    this.centerMarker = L.marker([initialLat, initialLng], { 
                        draggable: true,
                        title: 'Titik Pusat Kantor'
                    }).addTo(this.map);

                    this.centerMarker.bindPopup('<b>Titik Pusat Kantor</b><br>Geser untuk memperbarui Latitude/Longitude.');

                    this.centerMarker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        @this.set('latitude', pos.lat.toFixed(8));
                        @this.set('longitude', pos.lng.toFixed(8));
                        this.updateCirclePosition();
                    });

                    // 4. Circular Baseline Radius (High-contrast cyan over satellite imagery)
                    this.circleLayer = L.circle([initialLat, initialLng], {
                        radius: initialRadius,
                        color: '#38bdf8',
                        fillColor: '#0ea5e9',
                        fillOpacity: 0.15,
                        weight: 2,
                        dashArray: '6, 6'
                    }).addTo(this.map);

                    // 5. Render Initial Polygon
                    this.renderPolygon();

                    // 6. Map Click Event (for drawing points)
                    this.map.on('click', (e) => {
                        if (this.isDrawing) {
                            this.addPoint(e.latlng.lat, e.latlng.lng);
                        }
                    });

                    // 7. Watchers for Livewire input changes
                    this.$watch('$wire.latitude', () => this.syncMapFromInputs());
                    this.$watch('$wire.longitude', () => this.syncMapFromInputs());
                    this.$watch('$wire.radius_meters', (val) => {
                        const rad = parseInt(val) || 100;
                        if (this.circleLayer) this.circleLayer.setRadius(rad);
                    });

                    // Invalidate size after layout renders
                    setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 350);
                },

                setLayer(type) {
                    this.activeLayer = type;
                    // Remove all tile layers
                    Object.values(this.layers).forEach(layer => {
                        if (this.map.hasLayer(layer)) {
                            this.map.removeLayer(layer);
                        }
                    });
                    // Add selected tile layer
                    if (this.layers[type]) {
                        this.layers[type].addTo(this.map);
                    }
                },

                syncMapFromInputs() {
                    const lat = parseFloat(@this.latitude);
                    const lng = parseFloat(@this.longitude);
                    if (!isNaN(lat) && !isNaN(lng) && this.centerMarker) {
                        this.centerMarker.setLatLng([lat, lng]);
                        this.updateCirclePosition();
                    }
                },

                updateCirclePosition() {
                    if (this.centerMarker && this.circleLayer) {
                        const pos = this.centerMarker.getLatLng();
                        this.circleLayer.setLatLng(pos);
                    }
                },

                toggleDrawing() {
                    this.isDrawing = !this.isDrawing;
                },

                addPoint(lat, lng) {
                    this.polygonPoints.push({
                        lat: parseFloat(lat.toFixed(8)),
                        lng: parseFloat(lng.toFixed(8))
                    });
                    this.syncToLivewire();
                    this.renderPolygon();
                },

                removePoint(idx) {
                    this.polygonPoints.splice(idx, 1);
                    this.syncToLivewire();
                    this.renderPolygon();
                },

                clearPolygon() {
                    this.polygonPoints = [];
                    this.isDrawing = false;
                    this.syncToLivewire();
                    this.renderPolygon();
                },

                generateDefaultBox() {
                    const lat = parseFloat(@this.latitude) || -0.874971;
                    const lng = parseFloat(@this.longitude) || 119.886639;
                    const deltaLat = 0.00035; // ~38 meters
                    const deltaLng = 0.00035;

                    this.polygonPoints = [
                        { lat: parseFloat((lat + deltaLat).toFixed(8)), lng: parseFloat((lng - deltaLng).toFixed(8)) },
                        { lat: parseFloat((lat + deltaLat).toFixed(8)), lng: parseFloat((lng + deltaLng).toFixed(8)) },
                        { lat: parseFloat((lat - deltaLat).toFixed(8)), lng: parseFloat((lng + deltaLng).toFixed(8)) },
                        { lat: parseFloat((lat - deltaLat).toFixed(8)), lng: parseFloat((lng - deltaLng).toFixed(8)) }
                    ];

                    this.isDrawing = false;
                    this.syncToLivewire();
                    this.renderPolygon();
                    this.map.setView([lat, lng], 18);
                },

                renderPolygon() {
                    if (!this.map) return;

                    // Remove old polygon layer
                    if (this.polygonLayer) {
                        this.map.removeLayer(this.polygonLayer);
                        this.polygonLayer = null;
                    }

                    // Remove old vertex markers
                    this.vertexMarkers.forEach(m => this.map.removeLayer(m));
                    this.vertexMarkers = [];

                    if (this.polygonPoints.length === 0) return;

                    const latlngs = this.polygonPoints.map(p => [p.lat, p.lng]);

                    if (this.polygonPoints.length >= 3) {
                        this.polygonLayer = L.polygon(latlngs, {
                            color: '#22d3ee', // Cyan-400 (Sangat kontras di atas satelit gelap)
                            fillColor: '#06b6d4',
                            fillOpacity: 0.38,
                            weight: 3.5
                        }).addTo(this.map);
                    } else if (this.polygonPoints.length === 2) {
                        this.polygonLayer = L.polyline(latlngs, {
                            color: '#22d3ee',
                            weight: 3.5,
                            dashArray: '5, 5'
                        }).addTo(this.map);
                    }

                    // Add vertex handles for dragging
                    this.polygonPoints.forEach((point, index) => {
                        const icon = L.divIcon({
                            className: 'vertex-handle',
                            html: `<div style="background-color:#06b6d4; color:#0f172a; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; border:2.5px solid white; box-shadow:0 3px 8px rgba(0,0,0,0.6); cursor:grab;">${index + 1}</div>`,
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        });

                        const vMarker = L.marker([point.lat, point.lng], { 
                            icon, 
                            draggable: true 
                        }).addTo(this.map);

                        vMarker.on('drag', (e) => {
                            const newPos = e.target.getLatLng();
                            this.polygonPoints[index].lat = parseFloat(newPos.lat.toFixed(8));
                            this.polygonPoints[index].lng = parseFloat(newPos.lng.toFixed(8));
                            if (this.polygonLayer) {
                                this.polygonLayer.setLatLngs(this.polygonPoints.map(p => [p.lat, p.lng]));
                            }
                        });

                        vMarker.on('dragend', () => {
                            this.syncToLivewire();
                        });

                        this.vertexMarkers.push(vMarker);
                    });
                },

                syncToLivewire() {
                    @this.set('polygon_coordinates', this.polygonPoints);
                }
            };
        }
    </script>
</section>
