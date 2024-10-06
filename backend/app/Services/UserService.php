<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Create an authentication token for a user based on their email.
     *
     * @param string $email The email address of the user
     * @return string|null The plain text token if user is found, null otherwise
     */
    public function createTokenFromEmail(string $email): ?string
    {
        $user = User::where('email', $email)->first();
        return $user ? $user->createToken('auth_token')->plainTextToken : null;
    }

    /**
     * Find or create a user based on the provided request data.
     *
     * @param array $data The data to find or create the user
     * @return User The found or created user
     */
    public function findOrCreateUser(array $data): User
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => isset($data['password']) ? Hash::make($data['password']) : Hash::make(Str::random(16)),
                'email_verified_at' => isset($data['google_id']) ? now() : null,
                'google_id' => $data['google_id'] ?? null,
            ]
        );
        $user->assignRole(Role::USER);
        return $user;
    }

    /**
     * Transform a user object to return non-sensitive user details.
     *
     * @param User $user The user object
     * @return array The transformed user details
     */
    public function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_verified' => $user->email_verified_at ? true : false,
            'avatar_url' => $user->avatar_url,
        ];
    }
}
