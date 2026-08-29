<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        DB::table('users')->insert([
            ['name' => 'Administrator', 'email' => 'admin@majujaya.co.id', 'type' => 1, 'no_hp' => '081234567890', 'password' => Hash::make('password'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Budi Santoso', 'email' => 'budi@majujaya.co.id', 'type' => 2, 'no_hp' => '081234567891', 'password' => Hash::make('password'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sari Wulandari', 'email' => 'sari@majujaya.co.id', 'type' => 2, 'no_hp' => '081234567892', 'password' => Hash::make('password'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Agus Prasetyo', 'email' => 'agus@majujaya.co.id', 'type' => 2, 'no_hp' => '081234567893', 'password' => Hash::make('password'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pak Hendra (Owner)', 'email' => 'hendra@majujaya.co.id', 'type' => 3, 'no_hp' => '081234567894', 'password' => Hash::make('password'), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
