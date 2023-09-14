<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAboutusTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('aboutus', function (Blueprint $table) {
            $table->bigIncrements('a_id');
            $table->string('a_title', 255)->nullable(true);
            $table->text('a_description')->nullable(true);
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
        Schema::dropIfExists('aboutus');
    }

}
