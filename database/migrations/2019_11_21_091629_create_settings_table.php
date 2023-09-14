<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('s_id');
            $table->string('s_name', 150)->nullable(true);
            $table->string('s_key', 150)->nullable(true);
            $table->text('s_value')->nullable(true);
            $table->unsignedInteger("s_type")->comment('1. Textbox, 2. Textarea, 3. Selectbox, 4. Checkbox, 5. Radio, 6. File, 7. Number, 8. JSON')->default(1);
            $table->text('s_extra')->nullable(true);
            $table->unsignedTinyInteger("s_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('s_created_at')->nullable(true);
            $table->timestamp('s_updated_at')->nullable(true);
            $table->timestamp('s_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('settings');
    }

}
