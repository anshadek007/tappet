<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRolesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->increments('role_id');
            $table->unsignedTinyInteger("role_type")->comment('1-Admin 2-User (Seller OR Buyer)')->default(2);
            $table->string('role_name', 100)->nullable(true);
            $table->text('role_permissions')->nullable(true);
            $table->unsignedBigInteger("role_added_by_u_id")->nullable(true);
            $table->unsignedInteger("role_parent_role_id")->nullable(true);
            $table->unsignedTinyInteger("role_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('role_created_at')->nullable(true);
            $table->timestamp('role_updated_at')->nullable(true);
            $table->timestamp('role_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_roles');
    }

}
