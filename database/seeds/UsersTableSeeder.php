<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('users')->insert([
            'u_first_name' => "Tom",
            'u_last_name' => "Henry",
            'u_email' => "tomhenry@mailinator.com",
            'u_mobile_number' => "9879879879",
            'u_password' => bcrypt('123456'),
            'u_is_verified' => 1,
            'u_status' => 1,
        ]);
    }

}
