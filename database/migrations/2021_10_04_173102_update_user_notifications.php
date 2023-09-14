<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserNotifications extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger("u_group_message_notification")->comment('1=On, 2=Off')->default(2)->after('u_user_type');
            $table->unsignedTinyInteger("u_post_comment_notification")->comment('1=On, 2=Off')->default(1)->after('u_group_message_notification');
            $table->unsignedTinyInteger("u_post_like_notification")->comment('1=On, 2=Off')->default(1)->after('u_post_comment_notification');
            $table->unsignedTinyInteger("u_friend_request_notification")->comment('1=On, 2=Off')->default(1)->after('u_post_like_notification');
            $table->unsignedTinyInteger("u_event_notification")->comment('1=On, 2=Off')->default(1)->after('u_friend_request_notification');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('users', function (Blueprint $table) {
            // 
        });
    }

}
