<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PetTypesTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('pet_types')->insert(
                array(
                    array(
                        "pt_name" => "Dog",
                        "pt_image" => "dog.jpg",
                        "pt_status" => 1,
                        "pt_created_at" => Carbon::now(),
                        "pt_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pt_name" => "Cat",
                        "pt_image" => "cat.jpg",
                        "pt_status" => 1,
                        "pt_created_at" => Carbon::now(),
                        "pt_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pt_name" => "Rabbit",
                        "pt_image" => "rabbit.jpg",
                        "pt_status" => 1,
                        "pt_created_at" => Carbon::now(),
                        "pt_updated_at" => Carbon::now(),
                    ),
                )
        );
    }

}
