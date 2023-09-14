<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRolePermissionGroupsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_role_permission_groups', function (Blueprint $table) {
            $table->increments('urpg_id');
            $table->string('urpg_name', 150)->nullable(true);
            $table->unsignedTinyInteger("urpg_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('urpg_created_at')->nullable(true);
            $table->timestamp('urpg_updated_at')->nullable(true);
            $table->timestamp('urpg_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_role_permission_groups');
    }

}
