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
        <div class="fibernet-card p-6">
            @if(isset($heading) && $heading)
                <div class="flex items-center gap-3 mb-1">
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $heading }}</h2>
                </div>
            @endif
            @if(isset($subheading) && $subheading)
                <p class="mt-1 text-sm text-gray-500 ml-11">{{ $subheading }}</p>
            @endif

            <div class="mt-6 w-full max-w-2xl">
                {{ $slot }}
            </div>
        </div>
    </div>

</div>
