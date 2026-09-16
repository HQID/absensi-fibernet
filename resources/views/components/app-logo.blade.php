@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-8 items-center justify-center">
            <x-app-logo-icon class="h-full" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-8 items-center justify-center">
            <x-app-logo-icon class="h-full" />
        </x-slot>
    </flux:brand>
@endif
