<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        $this->call(SettingsTableSeeder::class);
        $this->call(AdminsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(RolePermissionGroupTableSeeder::class);
        $this->call(RolePermissionTypesTableSeeder::class);
        $this->call(PetBreedsTableSeeder::class);
        $this->call(PetTypesTableSeeder::class);
        $this->call(CountryTableSeeder::class);
        $this->call(CityTableSeeder::class);
    }

}
