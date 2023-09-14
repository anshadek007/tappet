<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDeviceTokensTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->bigIncrements('udt_id');
            $table->unsignedBigInteger('udt_u_id')->nullable(true);
            $table->string('udt_security_token', 200)->nullable(true);
            $table->text('udt_device_token')->nullable(true);
            $table->enum('udt_device_type', ['android', 'ios'])->default('ios');
            $table->unsignedTinyInteger("udt_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('udt_created_at')->nullable(true);
            $table->timestamp('udt_updated_at')->nullable(true);
            $table->timestamp('udt_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_device_tokens');
    }

}
