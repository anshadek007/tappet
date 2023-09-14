<?php

use Illuminate\Database\Seeder;

class RolePermissionTypesTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('user_role_permission_types')->insert(
                array(
                    array(
                        "urpt_name" => "Permissions",
                        "urpt_controller_name" => "",
                        "urpt_urpg_id" => 1,
                        "urpt_status" => 2
                    ),
                    array(
                        "urpt_name" => "Roles",
                        "urpt_controller_name" => "user-roles",
                        "urpt_urpg_id" => 1,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Admins",
                        "urpt_controller_name" => "admins",
                        "urpt_urpg_id" => 1,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Users",
                        "urpt_controller_name" => "users",
                        "urpt_urpg_id" => 2,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Breeds",
                        "urpt_controller_name" => "pet_breeds",
                        "urpt_urpg_id" => 3,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Pet Types",
                        "urpt_controller_name" => "pet_types",
                        "urpt_urpg_id" => 4,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Pets",
                        "urpt_controller_name" => "pets",
                        "urpt_urpg_id" => 5,
                        "urpt_status" => 1
                    ),
                       array(
                        "urpt_name" => "Countries",
                        "urpt_controller_name" => "countries",
                        "urpt_urpg_id" => 6,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Cities",
                        "urpt_controller_name" => "cities",
                        "urpt_urpg_id" => 7,
                        "urpt_status" => 1
                    ),
                      array(
                        "urpt_name" => "Categories",
                        "urpt_controller_name" => "categories",
                        "urpt_urpg_id" => 8,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Terms",
                        "urpt_controller_name" => "terms",
                        "urpt_urpg_id" => 9,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Contactus",
                        "urpt_controller_name" => "contactus",
                        "urpt_urpg_id" => 10,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Feedback",
                        "urpt_controller_name" => "feedback",
                        "urpt_urpg_id" => 11,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Faqs",
                        "urpt_controller_name" => "faqs",
                        "urpt_urpg_id" => 12,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Aboutus",
                        "urpt_controller_name" => "aboutus",
                        "urpt_urpg_id" => 13,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Global Push Notification",
                        "urpt_controller_name" => "pushnotification",
                        "urpt_urpg_id" => 14,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Settings",
                        "urpt_controller_name" => "settings",
                        "urpt_urpg_id" => 15,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Groups",
                        "urpt_controller_name" => "groups",
                        "urpt_urpg_id" => 16,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Events",
                        "urpt_controller_name" => "events",
                        "urpt_urpg_id" => 17,
                        "urpt_status" => 1
                    ),
                    array(
                        "urpt_name" => "Posts",
                        "urpt_controller_name" => "posts",
                        "urpt_urpg_id" => 18,
                        "urpt_status" => 1
                    ),
                )
        );
    }

}
