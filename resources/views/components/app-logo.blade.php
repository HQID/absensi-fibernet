@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-12 items-center justify-center w-full">
            <x-app-logo-icon class="h-full w-full" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-12 items-center justify-center w-full">
            <x-app-logo-icon class="h-full w-full" />
        </x-slot>
    </flux:brand>
@endif
