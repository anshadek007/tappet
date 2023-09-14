<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('groups', function (Blueprint $table) {
            $table->bigIncrements('group_id');
            $table->unsignedBigInteger('group_owner_id')->nullable(true);
            $table->string('group_name', 100)->nullable(true);
            $table->string('group_image', 40)->nullable(true);
            $table->text('group_description')->nullable(true);
            $table->enum('group_privacy', ['Public', 'Private'])->default('Private');
            $table->unsignedTinyInteger("group_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('group_created_at')->nullable(true);
            $table->timestamp('group_updated_at')->nullable(true);
            $table->timestamp('group_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('groups');
    }

}
