<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50">
        <flux:sidebar sticky collapsible="mobile" class="fibernet-sidebar">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden text-slate-400 hover:text-white" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="mt-6">
                <flux:sidebar.group class="grid gap-1">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    
                    @if(auth()->user()->isStudent())
                    <flux:sidebar.item icon="calendar" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:sidebar.item>
                    @endif

                    @if(auth()->user()->isAdmin())
                    <flux:sidebar.item icon="document-text" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                        {{ __('Reports') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('students')" :current="request()->routeIs('students')" wire:navigate>
                        {{ __('Peserta PKL') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <!-- Sidebar footer branding -->
            <div class="px-4 py-3 mb-2">
                <div class="rounded-lg bg-slate-800/50 border border-slate-700/50 px-3 py-2.5">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 shrink-0 rounded-full bg-cyan-500 animate-pulse"></div>
                        <span class="text-[11px] leading-tight text-slate-300 font-medium">PT Fiber Networks Indonesia Branch Palu</span>
                    </div>
                </div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden border-b border-gray-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu class="border-gray-100 shadow-xl rounded-2xl bg-white p-2">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-3 px-2 py-2 text-start text-sm bg-cyan-50/50 rounded-xl mb-1">
                                <div class="h-10 w-10 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold shadow-sm border border-cyan-200">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Profil
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
