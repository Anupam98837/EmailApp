<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) Insert demo user
        $userId = DB::table('users')->insertGetId([
            'name'       => 'demo1',
            'email'      => 'demo1@gmail.com',
            'phone'      => '1212121212',
            'photo'      => null,
            'password'   => Hash::make('demo1@1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2) Generate a new token
        $plain = bin2hex(random_bytes(40));
        $hash  = hash('sha256', $plain);

        // 3) Insert into personal_access_tokens
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'user',
            'tokenable_id'   => $userId,
            'name'           => 'demo1_token',
            'token'          => $hash,
            'abilities'      => json_encode(['*']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // 4) Output the plain token so you can copy it
        $this->command->info("Demo1’s access token: {$plain}");
    }
}
