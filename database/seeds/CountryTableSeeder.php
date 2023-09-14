<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CountryTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('countries')->insert(
                array(
                    array(
                        "c_name" => "USA",
                        "c_status" => 1,
                        "c_created_at" => Carbon::now(),
                        "c_updated_at" => Carbon::now(),
                    ),
                    array(
                        "c_name" => "UK",
                        "c_status" => 1,
                        "c_created_at" => Carbon::now(),
                        "c_updated_at" => Carbon::now(),
                    ),
                )
        );
    }

}
