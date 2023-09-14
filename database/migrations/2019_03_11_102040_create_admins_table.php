<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('admins', function (Blueprint $table) {
            $table->bigIncrements('a_id');
            $table->string('a_first_name', 50)->nullable(true);
            $table->string('a_last_name', 50)->nullable(true);
            $table->string('a_user_name', 100)->nullable(true);
            $table->string('a_email', 150)->nullable(true);
            $table->string('a_password', 250)->nullable(true);
            $table->string('a_image', 40)->nullable(true);
            $table->unsignedInteger("a_role_id")->nullable(true);
            $table->text('a_parent_users_id')->nullable(true);
            $table->timestamp('a_last_login')->nullable(true);
            $table->rememberToken();
            $table->unsignedTinyInteger("a_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('a_created_at')->nullable(true);
            $table->timestamp('a_updated_at')->nullable(true);
            $table->timestamp('a_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('admins');
    }

}
