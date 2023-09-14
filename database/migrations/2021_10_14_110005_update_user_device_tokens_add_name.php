<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserDeviceTokensAddName extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            $table->string('udt_device_name')->nullable(true)->after('udt_device_type');
            $table->string('udt_device_os_version')->nullable(true)->after('udt_device_name');
            $table->string('udt_app_version')->nullable(true)->after('udt_device_os_version');
            $table->string('udt_device_model_name')->nullable(true)->after('udt_app_version');
            $table->string('udt_api_version')->nullable(true)->after('udt_device_model_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            
        });
    }

}
