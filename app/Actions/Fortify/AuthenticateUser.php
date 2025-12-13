<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function __invoke($request)
    {
        $user = User::where(Fortify::username(), $request->{Fortify::username()})->first();

        if (! $user) {
            return null;
        }

        $password = $request->password;

        if ($user->old_password) {
            if ($this->validateOldPassword($password, $user->old_password)) {
                $user->password = Hash::make($password);
                $user->old_password = null;
                $user->save();

                return $user;
            }
        }

        if (Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    /**
     * Validate password against old password format.
     */
    private function validateOldPassword(string $password, string $oldPasswordHash): bool
    {
        // Placeholder: Using MD5 as example (replace with actual old DB algorithm)
        $hashedPassword = md5($password);

        return hash_equals($oldPasswordHash, $hashedPassword);
    }
}
