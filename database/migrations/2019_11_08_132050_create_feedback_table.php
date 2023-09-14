<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('feedback', function (Blueprint $table) {
            $table->bigIncrements('f_id');
            $table->unsignedBigInteger('f_user_id');
            $table->text('f_content')->nullable(true);
            $table->unsignedTinyInteger("f_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('f_created_at')->nullable(true);
            $table->timestamp('f_updated_at')->nullable(true);
            $table->timestamp('f_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('feedback');
    }

}
