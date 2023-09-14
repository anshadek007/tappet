<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CityTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('cities')->insert(
                array(
                    array(
                        "city_name" => "New York",
                        "city_country_id" => 1,
                        "city_status" => 1,
                        "city_created_at" => Carbon::now(),
                        "city_updated_at" => Carbon::now(),
                    ),
                    array(
                        "city_name" => "Peris",
                        "city_country_id" => 1,
                        "city_status" => 1,
                        "city_created_at" => Carbon::now(),
                        "city_updated_at" => Carbon::now(),
                    ),
                    array(
                        "city_name" => "London",
                        "city_country_id" => 2,
                        "city_status" => 1,
                        "city_created_at" => Carbon::now(),
                        "city_updated_at" => Carbon::now(),
                    ),
                    array(
                        "city_name" => "Newyork",
                        "city_country_id" => 2,
                        "city_status" => 1,
                        "city_created_at" => Carbon::now(),
                        "city_updated_at" => Carbon::now(),
                    ),
                )
        );
    }

}
