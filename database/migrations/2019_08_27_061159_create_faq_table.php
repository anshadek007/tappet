<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFaqTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->bigIncrements('faq_id');
            $table->string('faq_title', 255)->nullable(true);
            $table->text('faq_description')->nullable(true);
            $table->unsignedTinyInteger("faq_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('faq_created_at')->nullable(true);
            $table->timestamp('faq_updated_at')->nullable(true);
            $table->timestamp('faq_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faq');
    }
}
