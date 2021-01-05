<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * create() makes instances and save it to db
         * make() only makes the instances 
         */
        \App\Models\User::factory(4)->create();
        \App\Models\User::create([
            'id' => Str::uuid(),
            'name' => 'Super Admin',
            'email' => 'supeeradmin@onex.io',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'SUPERADMIN'
        ]);
    }
}
