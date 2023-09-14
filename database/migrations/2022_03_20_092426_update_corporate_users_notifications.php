<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCorporateUsersNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger("u_pet_checkin_notification")->comment('1=On, 2=Off')->default(1)->after('u_event_notification');
            $table->unsignedTinyInteger("u_pet_checkout_notification")->comment('1=On, 2=Off')->default(2)->after('u_pet_checkin_notification');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
