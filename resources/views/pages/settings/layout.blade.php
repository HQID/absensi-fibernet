<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Pengaturan') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profil') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Keamanan') }}</flux:navlist.item>
            @if(auth()->user()->isAdmin())
            <flux:navlist.item :href="route('geofence.edit')" wire:navigate>{{ __('Lokasi Kantor') }}</flux:navlist.item>
            @endif
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @if(isset($heading) && $heading)
                <h2 class="text-lg font-semibold text-gray-900">{{ $heading }}</h2>
            @endif
            @if(isset($subheading) && $subheading)
                <p class="mt-1 text-sm text-gray-500">{{ $subheading }}</p>
            @endif

            <div class="mt-6 w-full max-w-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
