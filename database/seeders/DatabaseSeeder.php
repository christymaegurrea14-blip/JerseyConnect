<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserInfo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserInfo::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'birth_date' => '1990-01-01',
            'phone' => '9123456789',
            'address' => 'Pob. Trinidad, Bohol',
        ]);

        User::create([
            'user_info_id' => 1,
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->call([
            CourierSeeder::class
        ]);
    }
}
