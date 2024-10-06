<?php

namespace Database\Seeders;

use App\Enums\Role as EnumsRole;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Hiren Admin',
            'email' => 'hello@hirenkavad.com',
            'password' => Hash::make('Hiren@123'),
        ]);

        User::factory()->create([
            'name' => 'Hiren User',
            'email' => 'hirenkavad@gmail.com',
            'password' => Hash::make('Hiren@123'),
        ]);

        Role::create(['name' => EnumsRole::ADMIN]);
        Role::create(['name' => EnumsRole::USER]);

        $user = User::where('email', 'hello@hirenkavad.com')->first();
        $user->assignRole(EnumsRole::ADMIN);

        $user = User::where('email', 'hirenkavad@gmail.com')->first();
        $user->assignRole(EnumsRole::USER);
    }
}
