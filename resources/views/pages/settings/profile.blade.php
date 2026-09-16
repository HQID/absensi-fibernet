<?php

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $student_code = '';
    public string $school = '';
    public string $major = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $student = Auth::user()->student;
        if ($student) {
            $this->student_code = $student->student_code;
            $this->school = $student->school;
            $this->major = $student->major;
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $student = $user->student;
        if ($student) {
            $student->update([
                'student_code' => $this->student_code,
                'school' => $this->school,
                'major' => $this->major,
            ]);
        }

        Flux::toast(variant: 'success', text: __('Profil berhasil diperbarui.'));
    }

}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Pengaturan Profil') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profil')" :subheading="__('Perbarui data profil dan akun Anda')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Nama Lengkap')" type="text" required autofocus autocomplete="name" />

            @if(Auth::user()->isStudent())
                <flux:input wire:model="student_code" :label="__('NIM / NISN')" type="text" required />
                <flux:input wire:model="school" :label="__('Asal Sekolah / Kampus')" type="text" required />
                <flux:input wire:model="major" :label="__('Jurusan')" type="text" required />
            @endif


            <div>
                <flux:input wire:model="email" :label="__('Alamat Email')" type="email" required autocomplete="email" disabled />

            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Simpan Perubahan') }}
                    </flux:button>
                </div>

            </div>
        </form>

            <livewire:pages::settings.delete-user-form />
    </x-pages::settings.layout>
</section>
