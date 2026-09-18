<x-layouts::auth :title="__('Daftar Akun Baru')">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Pendaftaran Peserta PKL" description="Isi formulir di bawah ini untuk mendaftarkan akun." />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Nama Lengkap')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Nama lengkap Anda')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Alamat Email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Student Code (NIM/NISN) -->
            <flux:input
                name="student_code"
                :label="__('NIM / NISN')"
                :value="old('student_code')"
                type="text"
                required
                placeholder="Nomor Induk Mahasiswa / Siswa"
            />

            <!-- School/University -->
            <flux:input
                name="school"
                :label="__('Asal Sekolah / Kampus')"
                :value="old('school')"
                type="text"
                required
                placeholder="Contoh: Universitas Tadulako"
            />

            <!-- Major -->
            <flux:input
                name="major"
                :label="__('Jurusan')"
                :value="old('major')"
                type="text"
                required
                placeholder="Contoh: Teknik Informatika"
            />

            <div class="grid grid-cols-2 gap-4">
                <!-- Start Date -->
                <flux:input
                    name="start_date"
                    :label="__('Tanggal Mulai PKL')"
                    :value="old('start_date')"
                    type="date"
                    required
                />
                
                <!-- End Date -->
                <flux:input
                    name="end_date"
                    :label="__('Tanggal Selesai PKL')"
                    :value="old('end_date')"
                    type="date"
                    required
                />
            </div>

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Kata Sandi')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Minimal 8 karakter')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Konfirmasi Kata Sandi')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Ulangi kata sandi')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    Daftar Akun
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600">
            <span>Sudah memiliki akun?</span>
            <flux:link :href="route('login')" wire:navigate>Masuk di sini</flux:link>
        </div>
    </div>
</x-layouts::auth>
