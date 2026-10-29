<flux:skeleton.group animate="shimmer">
    @if(auth()->user()?->isStudent())
        <div class="space-y-6">
            {{-- Clock & Today Status Skeleton --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-center items-center gap-2">
                    <flux:skeleton class="h-3 w-24 rounded" />
                    <flux:skeleton class="h-9 w-32 rounded-lg" />
                    <flux:skeleton class="h-3.5 w-36 rounded" />
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-center items-center gap-3">
                    <flux:skeleton class="h-3 w-24 rounded" />
                    <flux:skeleton class="h-8 w-28 rounded-md" />
                </div>
            </div>

            {{-- Profile & Attendance History Skeleton --}}
            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                        <div class="flex items-center gap-2">
                            <flux:skeleton class="h-8 w-8 rounded-lg" />
                            <flux:skeleton class="h-5 w-28 rounded" />
                        </div>
                        <div class="space-y-3">
                            @for($i = 0; $i < 4; $i++)
                                <div class="space-y-1.5">
                                    <flux:skeleton class="h-3 w-24 rounded" />
                                    <flux:skeleton.line class="w-4/5" />
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="rounded-2xl bg-cyan-50/60 border border-cyan-100 p-6 shadow-sm space-y-3">
                        <flux:skeleton class="h-5 w-36 rounded" />
                        <flux:skeleton.line />
                        <flux:skeleton class="h-10 w-full rounded-lg mt-2" />
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
                            <flux:skeleton class="h-8 w-8 rounded-lg" />
                            <flux:skeleton class="h-5 w-48 rounded" />
                        </div>
                        <div class="divide-y divide-gray-50 p-6 space-y-4">
                            @for($i = 0; $i < 5; $i++)
                                <div class="grid grid-cols-4 gap-4 items-center pt-3 first:pt-0">
                                    <flux:skeleton class="h-4 w-24 rounded" />
                                    <flux:skeleton class="h-4 w-14 rounded" />
                                    <flux:skeleton class="h-4 w-14 rounded" />
                                    <flux:skeleton class="h-6 w-16 rounded-md" />
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            {{-- Admin Summary Cards Skeleton --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card-soft-cyan">
                    <div class="flex items-center gap-2">
                        <flux:skeleton class="h-5 w-5 rounded" />
                        <flux:skeleton class="h-4 w-32 rounded" />
                    </div>
                    <div class="mt-4">
                        <flux:skeleton class="h-9 w-16 rounded-lg" />
                    </div>
                </div>

                <div class="card-soft-white">
                    <div class="flex items-center gap-2">
                        <flux:skeleton class="h-5 w-5 rounded" />
                        <flux:skeleton class="h-4 w-36 rounded" />
                    </div>
                    <div class="mt-4">
                        <flux:skeleton class="h-9 w-16 rounded-lg" />
                    </div>
                </div>

                <div class="card-soft-white">
                    <div class="flex items-center gap-2">
                        <flux:skeleton class="h-5 w-5 rounded" />
                        <flux:skeleton class="h-4 w-24 rounded" />
                    </div>
                    <div class="mt-4">
                        <flux:skeleton class="h-9 w-16 rounded-lg" />
                    </div>
                </div>

                <div class="card-soft-rose">
                    <div class="flex items-center gap-2">
                        <flux:skeleton class="h-5 w-5 rounded" />
                        <flux:skeleton class="h-4 w-36 rounded" />
                    </div>
                    <div class="mt-4">
                        <flux:skeleton class="h-9 w-16 rounded-lg" />
                    </div>
                </div>
            </div>

            {{-- Admin Today Attendance Table Skeleton --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <flux:skeleton class="h-8 w-8 rounded-lg" />
                        <flux:skeleton class="h-5 w-44 rounded" />
                    </div>
                    <flux:skeleton class="h-8 w-28 rounded-lg" />
                </div>

                <div class="divide-y divide-gray-50 px-6 py-4 space-y-4">
                    @for($i = 0; $i < 5; $i++)
                        <div class="grid grid-cols-5 gap-4 items-center pt-4 first:pt-0">
                            <div class="space-y-1.5">
                                <flux:skeleton class="h-4 w-32 rounded" />
                                <flux:skeleton class="h-3 w-20 rounded" />
                            </div>
                            <flux:skeleton class="h-4 w-full rounded" />
                            <flux:skeleton class="h-4 w-36 rounded" />
                            <flux:skeleton class="h-4 w-24 rounded" />
                            <flux:skeleton class="h-6 w-20 rounded-md" />
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endif
</flux:skeleton.group>
