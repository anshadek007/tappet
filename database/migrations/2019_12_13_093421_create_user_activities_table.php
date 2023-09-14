<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserActivitiesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->bigIncrements('ua_id');
            $table->tinyInteger('ua_activity_type')->comment('1-Tour Completed 2-Given Rating')->nullable(true);
            $table->unsignedBigInteger('ua_activity_id')->nullable(true);
            $table->unsignedBigInteger('ua_u_id')->nullable(true);
            $table->unsignedTinyInteger("ua_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('ua_created_at')->nullable(true);
            $table->timestamp('ua_updated_at')->nullable(true);
            $table->timestamp('ua_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_activities');
    }

}
