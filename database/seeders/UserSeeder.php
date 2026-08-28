<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'r.brockington@sound-service.eu'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        $hang = User::firstOrCreate(
            ['email' => 'customer@hangszerdiszkont.hu'],
            [
                'name' => 'Retail User',
                'password' => Hash::make('password'),
            ]
        );

        $dort = User::firstOrCreate(
            ['email' => 'customer@musikcenterdortmund.de'],
            [
                'name' => 'User',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole('admin');
        $hang->assignRole('customer');
        $dort->assignRole('customer');
    }
}
