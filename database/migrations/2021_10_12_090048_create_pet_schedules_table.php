<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetSchedulesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pet_schedules', function (Blueprint $table) {
            $table->bigIncrements('pet_schedule_id');
            $table->unsignedBigInteger('pet_schedule_pet_id')->nullable(true);
            $table->string('pet_schedule_name')->nullable(true);
            $table->date('pet_schedule_start_date')->nullable(true);
            $table->time('pet_schedule_start_time')->nullable(true);
            $table->date('pet_schedule_end_date')->nullable(true);
            $table->time('pet_schedule_end_time')->nullable(true);
            $table->enum('pet_schedule_repeat_on', ['Does not repeat', 'Everyday', 'Every week', 'Every month', 'Every year', 'Custom'])->default('Does not repeat');
            $table->enum('pet_schedule_recurring', ['Everyday', 'Every week', 'Every month', 'Every year'])->nullable(true);
            $table->unsignedInteger('pet_schedule_repeating_weekly_every_weekday')->nullable(true);
            $table->unsignedInteger('pet_schedule_repeating_day_of_month')->nullable(true);
            $table->unsignedInteger('pet_schedule_repeating_day_of_year')->nullable(true);
            $table->string('pet_schedule_repeating_ends')->nullable(true);
            $table->unsignedInteger('pet_schedule_reminder')->nullable(true)->comment('In Minutes');
            $table->string('pet_schedule_note')->nullable(true);
            $table->unsignedTinyInteger("pet_schedule_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('pet_schedule_created_at')->nullable(true);
            $table->timestamp('pet_schedule_updated_at')->nullable(true);
            $table->timestamp('pet_schedule_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pet_schedules');
    }

}
