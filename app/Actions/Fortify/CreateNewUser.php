<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'student_code' => ['required', 'string', 'max:255', 'unique:students'],
            'school' => ['required', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        \App\Models\Student::create([
            'user_id' => $user->id,
            'student_code' => $input['student_code'],
            'school' => $input['school'],
            'major' => $input['major'],
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'status' => 'active',
        ]);

        return $user;
    }
}
