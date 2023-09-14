<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SettingsTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('settings')->insert(
                [
                    [
                        "s_name" => "TEXT",
                        "s_key" => "Please enter textbox value",
                        "s_value" => "wq",
                        "s_type" => "1",
                        "s_extra" => null,
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "TEXTAREA",
                        "s_key" => "Please enter description",
                        "s_value" => "Desc",
                        "s_type" => "2",
                        "s_extra" => null,
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "SELECT",
                        "s_key" => "Please select value",
                        "s_value" => "1",
                        "s_type" => "3",
                        "s_extra" => "[\"A\",\"B\",\"C\"]",
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "CHECKBOX",
                        "s_key" => "Please check any one value",
                        "s_value" => "0,1",
                        "s_type" => "4",
                        "s_extra" => "[\"X\",\"Y\",\"Z\"]",
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "RADIO",
                        "s_key" => "Please check any one value",
                        "s_value" => "0",
                        "s_type" => "5",
                        "s_extra" => "[\"X\",\"Y\",\"Z\"]",
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "FILE",
                        "s_key" => "Please choose file",
                        "s_value" => "a.jpg",
                        "s_type" => "6",
                        "s_extra" => "",
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "NUMBER",
                        "s_key" => "Please enter number only",
                        "s_value" => "10",
                        "s_type" => "7",
                        "s_extra" => null,
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ],
                    [
                        "s_name" => "JSON",
                        "s_key" => "Add details",
                        "s_value" => null,
                        "s_type" => "8",
                        "s_extra" => null,
                        "s_status" => 2,
                        "s_created_at" => Carbon::now(),
                        "s_updated_at" => Carbon::now(),
                    ]
                ]
        );
    }

}
