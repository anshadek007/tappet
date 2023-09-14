<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_user', function (Blueprint $table) {
            $table->bigIncrements('u_id');
            $table->string('u_first_name', 100)->nullable(true);
            $table->string('u_last_name', 100)->nullable(true);
            $table->string('u_email', 150)->nullable(true);
            $table->string('u_mobile_number', 20)->nullable(true);
            $table->string('u_password', 250)->nullable(true);
            $table->enum('u_gender', ['Male', 'Female','Other','Prefer not to say'])->default('Prefer not to say');
            $table->date('u_dob')->nullable(true);
            $table->string('u_country_code', 50)->nullable(true);
            $table->string('u_country', 80)->nullable(true);
            $table->string('u_state', 80)->nullable(true);
            $table->string('u_city', 80)->nullable(true);
            $table->string('u_latitude', 30)->nullable(true);
            $table->string('u_longitude', 30)->nullable(true);
            $table->string('u_zipcode', 15)->nullable(true);
            $table->string('u_address', 255)->nullable(true);
            $table->string('u_image', 40)->nullable(true);
            $table->string('u_otp', 10)->nullable(true);
            $table->string('u_social_id', 200)->nullable(true)->comment('Social Id');
            $table->string('u_stripe_id', 255)->nullable(true)->comment('Stripe Customer ID for send payment');
            $table->string('u_stripe_account_id', 100)->nullable(true)->comment('Stripe Connect Account ID for receive payment');
            $table->unsignedTinyInteger("u_is_verified")->comment('1-Yes 2-No')->default(2);
            $table->unsignedTinyInteger("u_phone_verified")->comment('1-Yes 2-No')->default(2);
            $table->unsignedTinyInteger("u_user_type")->comment('1=Normal, 2=FB,3=Google')->default(1);
            $table->string("u_corporation_name", 200)->nullable(true);
            $table->string("u_website", 200)->nullable(true);
            $table->string("u_alternate_phone_number", 100)->nullable(true);
            $table->enum("u_timing",['Selected Days','24 Hours'])->nullable(true);
            $table->string("u_start_time", 100)->nullable(true);
            $table->string("u_end_time", 100)->nullable(true);
            $table->string("u_week_days", 100)->nullable(true);
            $table->unsignedTinyInteger("u_group_message_notification")->comment('1=On, 2=Off')->default(2);
            $table->unsignedTinyInteger("u_post_comment_notification")->comment('1=On, 2=Off')->default(1);
            $table->unsignedTinyInteger("u_post_like_notification")->comment('1=On, 2=Off')->default(1);
            $table->unsignedTinyInteger("u_friend_request_notification")->comment('1=On, 2=Off')->default(1);
            $table->unsignedTinyInteger("u_event_notification")->comment('1=On, 2=Off')->default(1);
            $table->string("u_cover_photo", 40)->nullable(true);
            $table->text("u_details")->nullable(true);
            $table->unsignedTinyInteger("u_pet_checkin_notification")->comment('1=On, 2=Off')->default(1);
            $table->unsignedTinyInteger("u_pet_checkout_notification")->comment('1=On, 2=Off')->default(2);
            $table->timestamp('u_last_login')->nullable(true);
            $table->rememberToken();
            $table->unsignedTinyInteger("u_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('u_created_at')->nullable(true);
            $table->timestamp('u_updated_at')->nullable(true);
            $table->timestamp('u_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_users');
    }
}
