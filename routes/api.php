<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Router;

Route::group(array('prefix' => 'v1'), function() {
    Route::get('/test_ios_push', function () {
        $token = request()->token;
        $test = Artisan::call("test_ios_push $token");
        dd($test);
    });
    Route::get('/test_android_push', function () {
        $token = request()->token;
        $test = Artisan::call("test_android_push $token");
        dd($test);
    });
});

Route::group(['middleware' => ['apiDataLogger']], function() {

    Route::post('test_mail', 'api\v1\UserController@test_mail');
    Route::get('password/reset/{token}/{email?}', 'api\v1\ResetPasswordController@showResetForm')->name('password.reset')->middleware(['web']);
    Route::get('password/corporate/reset/{token}/{email?}', 'api\v1\CorporateResetPasswordController@showResetForm')->name('corporate.password.reset')->middleware(['web']);
    Route::post('password/reset', 'api\v1\ResetPasswordController@reset')->name('api.password.update');
    Route::post('password/corporate/reset', 'api\v1\CorporateResetPasswordController@reset')->name('api.password.corporate.update');

    
  

    // Route::group(['middleware' => ['isAppUser']], function (Router $router) {
       
        Route::group(array('prefix' => 'v1'), function() {
            Route::post('password/email', 'api\v1\ForgotPasswordController@sendResetApiLinkEmail')->name('forgotpassword');
            Route::post('corporateuser/password/email', 'api\v1\CorporateForgotPasswordController@sendResetApiLinkEmail')->name('corporateuser.forgotpassword');
          
           

            Route::middleware(['cors'])->group(function () {
                
            //public pet details 
            Route::get('pets/get_pet_details_public/{pet_id}', 'api\v1\PetsController@get_pet_details_for_public')->name('public.pets.get_pet_details');
             //guest routes
            Route::post('guest/signup', 'api\v1\GuestController@sign_up')->name('api.guest.register');
            });
            // user route
            Route::middleware('auth:api')->group(function () {
                //User routes
                Route::resource('user', 'api\v1\UserController');
                Route::post('updateuserdetail', 'api\v1\UserController@updateUserDetail')->name('update.userdetail');
                Route::post('update_profile_picture', 'api\v1\UserController@updateProfilePicture')->name('update.update_profile_picture');
                Route::post('changePassword', 'api\v1\UserController@changePassword')->name('change.password');
                Route::post('updatedevicetoken', 'api\v1\UserController@updateDeviceToken')->name('update.devicetoken');
                Route::post('users/update_notification_settings', 'api\v1\UserController@update_notification_settings')->name('users.update_notification_settings');
                Route::post('user/logout', 'api\v1\UserController@logout')->name('logout');
                Route::get('users/get_user_devices', 'api\v1\UserController@get_user_devices')->name('users.get_user_devices');
                Route::get('users/get_blocked_users', 'api\v1\UserController@get_blocked_users')->name('users.get_blocked_users');
                Route::get('users/get_call_history', 'api\v1\UserController@get_call_history')->name('users.get_call_history');
                Route::get('users/get_call_history_by_user_id', 'api\v1\UserController@get_call_history_by_user_id')->name('users.get_call_history_by_user_id');
                Route::post('users/block_or_unblock_user', 'api\v1\UserController@block_or_unblock_user')->name('users.block_or_unblock_user');
                Route::post('remove_device', 'api\v1\UserController@remove_device')->name('users.remove_device');
                Route::post('users/get_user_and_group_details', 'api\v1\UserController@get_user_and_group_details')->name('users.get_user_and_group_details');
                Route::post('users/store_call_history', 'api\v1\UserController@store_call_history')->name('users.store_call_history');

                //Groups route
                Route::get('groups/get_all_groups', 'api\v1\GroupsController@get_all_groups')->name('groups.get_all_groups');
                Route::get('groups/get_group_details', 'api\v1\GroupsController@get_group_details')->name('groups.get_group_details');
                Route::post('groups/create_group', 'api\v1\GroupsController@create_group')->name('groups.create_group');
                Route::post('groups/edit_group', 'api\v1\GroupsController@edit_group')->name('groups.edit_group');
                Route::post('groups/add_group_member', 'api\v1\GroupsController@add_group_member')->name('groups.add_group_member');
                Route::post('groups/manage_group_admin', 'api\v1\GroupsController@manage_group_admin')->name('groups.manage_group_admin');
                Route::post('groups/remove_group_member', 'api\v1\GroupsController@remove_group_member')->name('groups.remove_group_member');
                Route::post('groups/manage_group_privacy', 'api\v1\GroupsController@manage_group_privacy')->name('groups.manage_group_privacy');
                Route::get('groups/get_all_groups_and_friends', 'api\v1\GroupsController@get_all_groups_and_friends')->name('groups.get_all_groups_and_friends');
                Route::post('groups/leave_group', 'api\v1\GroupsController@leave_group')->name('groups.leave_group');

                //Posts route
                Route::get('posts/get_all_posts', 'api\v1\PostsController@get_all_posts')->name('posts.get_all_posts');
                Route::get('posts/get_all_post_comments', 'api\v1\PostsController@get_all_post_comments')->name('posts.get_all_post_comments');
                Route::get('posts/get_all_post_likes', 'api\v1\PostsController@get_all_post_likes')->name('posts.get_all_post_likes');
                Route::get('posts/get_post_details', 'api\v1\PostsController@get_post_details')->name('posts.get_post_details');
                Route::post('posts/create_post', 'api\v1\PostsController@create_post')->name('posts.create_post');
                Route::post('posts/edit_post', 'api\v1\PostsController@edit_post')->name('posts.edit_post');
                Route::post('posts/delete_post', 'api\v1\PostsController@delete_post')->name('posts.delete_post');
                Route::post('posts/like_or_unlike_post', 'api\v1\PostsController@like_or_unlike_post')->name('posts.like_or_unlike_post');
                Route::post('posts/add_comment', 'api\v1\PostsController@add_comment')->name('posts.add_comment');
                Route::post('posts/delete_post_media', 'api\v1\PostsController@delete_post_media')->name('posts.delete_post_media');

                //Events route
                Route::get('events/get_all_events', 'api\v1\EventsController@get_all_events')->name('events.get_all_events');
                Route::get('events/get_event_details', 'api\v1\EventsController@get_event_details')->name('events.get_event_details');
                Route::post('events/create_event', 'api\v1\EventsController@create_event')->name('events.create_events');
                Route::post('events/edit_event', 'api\v1\EventsController@edit_event')->name('events.edit_event');
                Route::post('events/add_event_images', 'api\v1\EventsController@add_event_images')->name('events.add_event_images');
                Route::post('events/invite_friend_or_group', 'api\v1\EventsController@invite_friend_or_group')->name('events.invite_friend_or_group');
                Route::post('events/event_invitation_action', 'api\v1\EventsController@event_invitation_action')->name('events.event_invitation_action');
                Route::post('events/delete_event', 'api\v1\EventsController@delete_event')->name('events.delete_event');
                Route::get('events/get_all_events_by_group_id', 'api\v1\EventsController@get_all_events_by_group_id')->name('events.get_all_events_by_group_id');
                Route::post('corporatevents/event_invitation_action', 'api\v1\EventsController@Crevent_invitation_action')->name('corporatevents.event_invitation_action');

                //Pets routes
                Route::post('pets/add_pet', 'api\v1\PetsController@add_pet')->name('pets.add_pet');
                Route::post('pets/edit_pet', 'api\v1\PetsController@edit_pet')->name('pets.edit_pet');
                Route::post('pets/add_pet_images', 'api\v1\PetsController@add_pet_images')->name('pets.add_pet_images');
                Route::post('pets/add_pet_co_owner', 'api\v1\PetsController@add_pet_co_owner')->name('pets.add_pet_co_owner');
                Route::get('pets/get_pet_details', 'api\v1\PetsController@get_pet_details')->name('pets.get_pet_details');
                Route::get('pets/get_pet_co_owners', 'api\v1\PetsController@get_pet_co_owners')->name('pets.get_pet_co_owners');
                Route::post('pets/add_collar', 'api\v1\PetsController@add_collar')->name('pets.add_collar');
                Route::post('pets/add_pet_schedule', 'api\v1\PetsController@add_pet_schedule')->name('pets.add_pet_schedule');
                Route::post('pets/add_pet_schedule_note', 'api\v1\PetsController@add_pet_schedule_note')->name('pets.add_pet_schedule_note');
                Route::post('pets/update_live_location', 'api\v1\PetsController@update_live_location')->name('pets.update_live_location');
                Route::get('pets/get_live_location_pets', 'api\v1\PetsController@get_live_location_pets')->name('pets.get_live_location_pets');
                Route::post('pets/start_run', 'api\v1\PetsController@start_run')->name('pets.start_run');
                Route::post('pets/stop_run', 'api\v1\PetsController@stop_run')->name('pets.stop_run');
                Route::post('pets/update_pet_activity_live_location', 'api\v1\PetsController@update_pet_activity_live_location')->name('pets.update_pet_activity_live_location');
                Route::get('pets/get_pet_all_activities', 'api\v1\PetsController@get_pet_all_activities')->name('pets.get_pet_all_activities');
                Route::post('pets/delete_pet', 'api\v1\PetsController@delete_pet')->name('pets.delete_pet');
                Route::post('pets/delete_pet_image', 'api\v1\PetsController@delete_pet_image')->name('pets.delete_pet_image');

                //Friends
                Route::get('friends/recommended_people', 'api\v1\FriendsController@recommended_people')->name('friends.recommended_people');
                Route::get('friends/explore_people', 'api\v1\FriendsController@explore_people')->name('friends.explore_people');
                Route::get('friends/search_people', 'api\v1\FriendsController@search_people')->name('friends.search_people');
                Route::post('friends/add_friend', 'api\v1\FriendsController@add_friend')->name('friends.add_friend');
                Route::post('friends/friend_request_action', 'api\v1\FriendsController@friend_request_action')->name('friends.friend_request_action');
                Route::post('friends/my_friends', 'api\v1\FriendsController@my_friends')->name('friends.my_friends');
                Route::post('friends/get_mutual_friends', 'api\v1\FriendsController@get_mutual_friends')->name('friends.get_mutual_friends');
                Route::post('friends/get_other_friends', 'api\v1\FriendsController@get_other_friends')->name('friends.get_other_friends');
                Route::post('friends/remove_friend', 'api\v1\FriendsController@remove_friend')->name('friends.remove_friend');

                Route::post('invite_friend', 'api\v1\UserController@invite_friend')->name('invite_friend');
                Route::post('my_friends', 'api\v1\UserController@my_friends')->name('my_friends');
                Route::post('friend_request_action', 'api\v1\UserController@friend_request_action')->name('friend_request_action');
                Route::post('send_chat_notifications', 'api\v1\NotificationsController@send_chat_notifications')->name('notifications.send_chat_notifications');
                Route::post('getnotifications', 'api\v1\NotificationsController@index')->name('notifications.list');
                Route::post('clearnotifications', 'api\v1\NotificationsController@destroy')->name('notifications.delete');
                Route::post('removenotification', 'api\v1\NotificationsController@remove')->name('notification.remove');
                Route::post('feedback', 'api\v1\FeedbackController@store')->name('feedback');
                Route::get('get_user_activity', 'api\v1\UserController@get_user_activity')->name('get_user_activity');
                // 
                Route::get('get_follwer_list', 'api\v1\UserController@get_follwer_list')->name('get_follwer_list');

            });

            // corporate route 
            Route::middleware('auth:corporate')->group(function () {

                //Events route
                Route::get('corporatevents/get_event_details', 'api\v1\CorporateEventsController@get_event_details')->name('corporatevents.get_event_details');
                Route::post('corporatevents/create_event', 'api\v1\CorporateEventsController@create_event')->name('corporatevents.create_events');
                Route::post('corporatevents/edit_event', 'api\v1\CorporateEventsController@edit_event')->name('corporatevents.edit_event');
                Route::post('corporatevents/add_event_images', 'api\v1\CorporateEventsController@add_event_images')->name('corporatevents.add_event_images');
                Route::post('corporatevents/invite_friend_or_group', 'api\v1\CorporateEventsController@invite_friend_or_group')->name('corporatevents.invite_friend_or_group');
                Route::post('corporatevents/delete_event', 'api\v1\CorporateEventsController@delete_event')->name('corporatevents.delete_event');
                Route::get('corporatevents/get_all_events_by_group_id', 'api\v1\CorporateEventsController@get_all_events_by_group_id')->name('corporatevents.get_all_events_by_group_id');

                /** 
                 * Corporate AUTH routes
                 */
                // Route::resource('corporateuser', 'api\v1\CorporateUserController');
                Route::post('corporateuser/logout', 'api\v1\CorporateUserController@logout')->name('corporateuser.logout');
                Route::post('corporateuser/updateuserdetail', 'api\v1\CorporateUserController@updateUserDetail')->name('update.corporateuserdetail');
                Route::post('corporateuser/changePassword', 'api\v1\CorporateUserController@changePassword')->name('change.corporateuserpassword');
                Route::get('corporateuser/get_user_devices', 'api\v1\CorporateUserController@get_user_devices')->name('corporateuser.get_user_devices');
                Route::post('corporateuser/remove_device', 'api\v1\CorporateUserController@remove_device')->name('corporateuser.remove_device');
                Route::post('corporateuser/updatedevicetoken', 'api\v1\CorporateUserController@updateDeviceToken')->name('updatecorporateuser.devicetoken');
                Route::post('corporateuser/add_corporate_services', 'api\v1\CorporateUserController@addCorporateServices')->name('add.addCorporateServices');
                Route::post('corporateuser/update_notification_settings_corporate', 'api\v1\CorporateUserController@update_notification_settings_corporate')->name('corporateuser.update_notification_settings_corporate');
                Route::post('corporateuser/block_or_unblock_user_corporate', 'api\v1\CorporateUserController@block_or_unblock_user_corporate')->name('corporateuser.block_or_unblock_user_corporate');
                Route::post('corporateuser/deleteCorporateServices', 'api\v1\CorporateUserController@deleteCorporateServices')->name('corporateuser.deleteCorporateServices');
                Route::get('corporateuser/get_user_events', 'api\v1\CorporateUserController@get_user_events')->name('corporateuser.get_user_events');
                Route::get('corporatevents/get_all_events', 'api\v1\CorporateEventsController@get_all_events')->name('corporatevents.get_all_events');

                /**
                 * Corporate post routes
                 */
                Route::get('corporateuser/get_all_posts_corporate', 'api\v1\CorporatePostsController@get_all_posts_corporate')->name('corporateuser.get_all_posts_corporate');
                Route::get('corporateuser/get_all_post_comments_corporate', 'api\v1\CorporatePostsController@get_all_post_comments_corporate')->name('corporateuser.get_all_post_comments_corporate');
                Route::get('corporateuser/get_all_post_likes_corporate', 'api\v1\CorporatePostsController@get_all_post_likes_corporate')->name('corporateuser.get_all_post_likes_corporate');
                Route::post('corporateuser/create_post_corporate', 'api\v1\CorporatePostsController@create_post_corporate')->name('corporateuser.create_post_corporate');
                Route::post('corporateuser/edit_post_corporate', 'api\v1\CorporatePostsController@edit_post_corporate')->name('corporateuser.edit_post_corporate');
                Route::post('corporateuser/delete_post_corporate', 'api\v1\CorporatePostsController@delete_post_corporate')->name('corporateuser.delete_post_corporate');
                Route::post('corporateuser/like_or_unlike_post_corporate', 'api\v1\CorporatePostsController@like_or_unlike_post_corporate')->name('corporateuser.like_or_unlike_post_corporate');
                Route::post('corporateuser/add_comment_corporate', 'api\v1\CorporatePostsController@add_comment_corporate')->name('corporateuser.add_comment_corporate');
                Route::get('corporateuser/get_all_users_posts', 'api\v1\CorporatePostsController@get_all_users_posts')->name('corporateuser.get_all_users_posts');
                Route::post('corporateuser/delete_post_media', 'api\v1\CorporatePostsController@delete_post_media')->name('corporateuser.delete_post_media');

                //Rate review
                Route::get('corporateuser/get_rateReview', 'api\v1\CorporateUserController@get_rateReview')->name('corporateuser.get_rateReview');
                Route::post('corporateuser/reviewReplay', 'api\v1\CorporateUserController@reviewReplay')->name('corporateuser.reviewReplay');
                Route::get('corporateuser/removeReplay/{id}', 'api\v1\CorporateUserController@removeReplay')->name('corporateuser.removeReplay');
                Route::get('corporateuser/rateReviewDetails/{id}', 'api\v1\CorporateUserController@rateReviewDetails')->name('corporateuser.rateReviewDetails');
                
                // pet route
                Route::get('corporateuser/pet_details', 'api\v1\CorPetsController@pet_details')->name('corporateuser.pet_details');
                Route::post('corporateuser/checkIn', 'api\v1\CorPetsController@checkIn')->name('corporateuser.checkIn');
                Route::post('corporateuser/checkOut', 'api\v1\CorPetsController@checkOut')->name('corporateuser.checkOut');
                Route::get('corporateuser/petList', 'api\v1\CorPetsController@petList')->name('corporateuser.petList');
                Route::get('corporateuser/checkInHistory', 'api\v1\CorPetsController@checkInHistory')->name('corporateuser.checkInHistory');
                Route::get('corporateuser/petOwnerDetails', 'api\v1\CorPetsController@petOwnerDetails')->name('corporateuser.petOwnerDetails');
                Route::get('corporateuser/dashboard', 'api\v1\CorPetsController@dashboard')->name('corporateuser.dashboard');

            });

            //corporate service
            Route::get('corporateuser/get_corporate_services', 'api\v1\CorporateUserController@get_corporate_services')->name('corporateuser.get_corporate_services');
            Route::get('corporateuser/get_corporate/{id}', 'api\v1\CorporateUserController@get_corporate')->name('corporateuser.get_corporate');
            Route::post('corporateuser/follwer', 'api\v1\CorporateUserController@follwer')->name('corporateuser.follwer');
            Route::post('corporateuser/rateReview', 'api\v1\CorporateUserController@rateReview')->name('corporateuser.rateReview');
            // event
            Route::get('corporatevents/all_events_corporate', 'api\v1\CorporateEventsController@all_events_corporate')->name('corporatevents.all_events_corporate');
            Route::get('corporatevents/get_corporate_events/{id}', 'api\v1\CorporateEventsController@get_corporate_events')->name('corporatevents.get_corporate_events');
            Route::get('event/event_details/{id}', 'api\v1\CorporateEventsController@event_details')->name('event.event_details');

            Route::get('settings', 'api\v1\SettingsController@index')->name('settings');

            Route::post('user/create_password', 'api\v1\UserController@createPassword')->name('user.create_password');
            Route::post('user/forgot_password', 'api\v1\UserController@ForgotPassword')->name('user.forgot_password');
            Route::post('user/verify_otp', 'api\v1\UserController@verifyOTP')->name('verify.otp');
            Route::post('user/verify_email_otp', 'api\v1\UserController@verifyEmailOTP')->name('verifyemail.otp');
            
            Route::post('user/resendotp', 'api\v1\UserController@resendOTP')->name('resend.otp');

            Route::post('user/login', 'api\v1\UserController@login')->name('api.login');
            Route::post('user/register', 'api\v1\UserController@register')->name('api.register');

            Route::get('home/categories', 'api\v1\HomeController@categories')->name('categories.list');
            Route::get('allcategories', 'api\v1\CategoriesController@allcategoriesList')->name('categories.all');

            Route::post('contactus', 'api\v1\ContactusController@store')->name('contactus');
            Route::get('faqs', 'api\v1\FaqsController@index')->name('faqs');
            Route::get('aboutus', 'api\v1\AboutusController@index')->name('aboutus');
            Route::get('terms_and_conditions', 'api\v1\TermsController@index')->name('terms_and_conditions');
            Route::get('get_all_countries', 'api\v1\CountriesController@index')->name('get_all_countries');
            Route::get('get_city_by_country_id/{id}', 'api\v1\CitiesController@show')->name('get_city_by_country_id');

            //Pets routes
            Route::get('pets/get_pet_types', 'api\v1\PetsController@get_pet_types')->name('pets.get_pet_types');
            Route::get('pets/get_pet_breeds', 'api\v1\PetsController@get_pet_breeds')->name('pets.get_pet_breeds');
            Route::get('pets/get_pet_schedules', 'api\v1\PetsController@get_pet_schedules')->name('pets.get_pet_schedules');


            /**
             * Corporate user routes
             */
            Route::post('corporateuser/register', 'api\v1\CorporateUserController@register')->name('api.corporateuserregister');
            Route::post('corporateuser/verify_email_otp', 'api\v1\CorporateUserController@verifyEmailOTP')->name('verifyemail.corporateuserotp');
            Route::post('corporateuser/login', 'api\v1\CorporateUserController@login')->name('api.corporateuserlogin');
            Route::post('corporateuser/forgot_password', 'api\v1\CorporateUserController@ForgotPassword')->name('corporateuser.forgot_password');
            Route::post('corporateuser/create_password', 'api\v1\CorporateUserController@createPassword')->name('corporateuser.create_password');
            Route::post('corporateuser/get_corporate_user', 'api\v1\CorporateUserController@get_corporate_user')->name('corporateuser.get_corporate_user');
            Route::post('corporateuser/corporate_filter', 'api\v1\CorporateUserController@corporate_filter')->name('corporateuser.corporate_filter');
            Route::post('corporateuser/resendotp', 'api\v1\CorporateUserController@resendOTP')->name('corporateuser.otp');
            
            Route::post('corporateuser/get_mostPopular_corporate', 'api\v1\CorporateUserController@get_mostPopular_corporate')->name('corporateuser.get_mostPopular_corporate');
            Route::post('corporateuser/get_nearYou_corporate', 'api\v1\CorporateUserController@get_nearYou_corporate')->name('corporateuser.get_nearYou_corporate');
            Route::post('corporateuser/get_topRated_corporate', 'api\v1\CorporateUserController@get_topRated_corporate')->name('corporateuser.get_topRated_corporate');
        });
        
    // });
});
