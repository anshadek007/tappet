<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PetBreedsTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('pet_breeds')->insert(
                array(
                    array(
                        "pb_name" => "German Shepherd",
                        "pb_status" => 1,
                        "pb_created_at" => Carbon::now(),
                        "pb_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pb_name" => "German Spitz",
                        "pb_status" => 1,
                        "pb_created_at" => Carbon::now(),
                        "pb_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pb_name" => "Giant Schnauzer",
                        "pb_status" => 1,
                        "pb_created_at" => Carbon::now(),
                        "pb_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pb_name" => "German Wireheird Pointer",
                        "pb_status" => 1,
                        "pb_created_at" => Carbon::now(),
                        "pb_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pb_name" => "Glen of Imaal Terrier",
                        "pb_status" => 1,
                        "pb_created_at" => Carbon::now(),
                        "pb_updated_at" => Carbon::now(),
                    ),
                    array(
                        "pb_name" => "Wolf Gog",
                        "pb_status" => 1,
                        "pb_created_at" => Carbon::now(),
                        "pb_updated_at" => Carbon::now(),
                    ),
                )
        );
    }

}
