<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'role' => 'admin']);
        User::create(['name' => 'Atasan', 'email' => 'atasan@example.com', 'password' => Hash::make('password'), 'role' => 'atasan']);
        User::create(['name' => 'Pegawai 1', 'email' => 'pegawai1@example.com', 'password' => Hash::make('password'), 'role' => 'pegawai']);
        User::create(['name' => 'Pegawai 2', 'email' => 'pegawai2@example.com', 'password' => Hash::make('password'), 'role' => 'pegawai']);
    }
}
