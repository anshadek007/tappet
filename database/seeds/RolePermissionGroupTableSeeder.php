<?php

use Illuminate\Database\Seeder;

class RolePermissionGroupTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('user_role_permission_groups')->insert(
                array(
                    array("urpg_name" => "Admins"),
                    array("urpg_name" => "Users"),
                    array("urpg_name" => "Breeds"),
                    array("urpg_name" => "Pet Types"),
                    array("urpg_name" => "Pets"),
                    array("urpg_name" => "Countries"),
                    array("urpg_name" => "Cities"),
                    array("urpg_name" => "Categories"),
                    array("urpg_name" => "Terms"),
                    array("urpg_name" => "Contactus"),
                    array("urpg_name" => "Feedback"),
                    array("urpg_name" => "Faqs"),
                    array("urpg_name" => "Aboutus"),
                    array("urpg_name" => "Global Push Notification"),
                    array("urpg_name" => "Settings"),
                    array("urpg_name" => "Groups"),
                    array("urpg_name" => "Events"),
                    array("urpg_name" => "Posts"),
                )
        );
    }

}
