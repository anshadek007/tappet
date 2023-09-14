<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupMembersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('group_members', function (Blueprint $table) {
            $table->bigIncrements('gm_id');
            $table->unsignedBigInteger('gm_group_id')->nullable(true);
            $table->unsignedBigInteger('gm_user_id')->nullable(true);
            $table->enum('gm_role', ['Admin', 'User'])->default('User');
            $table->unsignedTinyInteger("gm_status")->comment('1-Active 2-Pending 3-Leave group')->default(2);
            $table->timestamp('gm_created_at')->nullable(true);
            $table->timestamp('gm_updated_at')->nullable(true);
            $table->timestamp('gm_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('group_members');
    }

}
