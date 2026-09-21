@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-xl font-bold text-gray-900">{{ $title }}</h1>
    <flux:subheading class="mt-1">{{ $description }}</flux:subheading>
</div>
