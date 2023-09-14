<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRolePermissionTypesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_role_permission_types', function (Blueprint $table) {
            $table->increments('urpt_id');
            $table->string('urpt_name', 150)->nullable(true);
            $table->string('urpt_controller_name', 150)->nullable(true);
            $table->unsignedBigInteger("urpt_urpg_id")->nullable(true);
            $table->unsignedTinyInteger("urpt_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('urpt_created_at')->nullable(true);
            $table->timestamp('urpt_updated_at')->nullable(true);
            $table->timestamp('urpt_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_role_permission_types');
    }

}
