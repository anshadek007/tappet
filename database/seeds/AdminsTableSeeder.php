<?php

use Illuminate\Database\Seeder;

class AdminsTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('admins')->insert([
            'a_first_name' => "Super",
            'a_last_name' => "Admin",
            'a_user_name' => "admin",
            'a_email' => "admin@superadmin.com",
            'a_password' => bcrypt('123456'),
            'a_role_id' => 1,
        ]);

        DB::table('user_roles')->insert([
            'role_type' => 1,
            'role_name' => "Super Admin",
            'role_permissions' => '{"2":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"3":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"4":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"5":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"6":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"7":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"8":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"9":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"10":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"11":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"12":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"},"13":{"1":"can_view_other_data","2":"create","3":"edit","4":"destroy","5":"index","6":"import","7":"export"}}',
            'role_added_by_u_id' => 1
        ]);
    }

}
