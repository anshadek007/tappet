<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications_data', function (Blueprint $table) {
            $table->bigIncrements('nd_id');
            $table->text('nd_content')->nullable(true);
            $table->unsignedTinyInteger('nd_target')->comment('1-All 2-Android 3-iOS')->default(1);
            
            $table->timestamp('nd_created_at')->nullable(true);
            $table->timestamp('nd_updated_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications_data');
    }
}
