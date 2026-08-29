<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(OrderSeeder::class);
    }
}
