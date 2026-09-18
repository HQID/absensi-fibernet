<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
        class="text-white hover:bg-white/10"
    />

    <flux:menu class="border-gray-100 shadow-xl rounded-2xl bg-white p-2">
        <div class="flex items-center gap-3 px-2 py-2 text-start text-sm bg-cyan-50/50 rounded-xl mb-1">
            <div class="h-10 w-10 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold shadow-sm border border-cyan-200">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                Profil
            </flux:menu.item>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
