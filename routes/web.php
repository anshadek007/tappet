<?php

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

Route::get('/', "LoginController@index");
Route::get("login", "LoginController@index")->name("login");
Route::post("login", "LoginController@login")->name("login");

Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}/{email?}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');
Route::get('/password/success', 'Auth\ResetPasswordController@showResetSuccessForm');
Route::get('/invite_tour', 'TourInvitesController@invite_tour')->name('invite_tour');

Route::get('privacy', 'PrivacyController@index')->name('privacy');
Route::get('terms', 'PrivacyController@terms')->name('terms');
//Route::get('aboutus', 'AboutusController@index')->name('aboutus');
Route::get('faq', 'PrivacyController@faq')->name('faq');

Route::get("404", function() {
    return view("404");
})->name("404");

Route::group(['middleware' => 'auth'], function () {
    Route::get("logout", "DashboardController@logout")->name("logout");
    Route::get("dashboard", "DashboardController@index")->name("dashboard");
    Route::post("get_city_list", "DashboardController@get_city_list")->name("get_city_list");
    Route::post("get_foundation_list", "DashboardController@get_foundation_list")->name("get_foundation_list");
    Route::get("edit_profile/{id}", "DashboardController@edit_profile")->name("edit_profile");
    Route::post("update_profile/{id}", "DashboardController@update_profile")->name("update_profile");
    Route::get("change_password/{id}", "DashboardController@change_password")->name("change_password");

    Route::post("update_password/{id}", "DashboardController@update_password")->name("update_password");

    //User Role Module Routing
    Route::resource("user-roles", "UserRolesController");
    Route::prefix('user-roles')->group(function () {
        Route::post("delete", "UserRolesController@destroy")->name("user-roles.delete");
        Route::post("list-data", "UserRolesController@load_data_in_table")->name("user-roles.load_data_in_table");
        Route::post("change_status", "UserRolesController@change_status")->name("user-roles.change_status");
        Route::post("list-roles", "UserRolesController@all_role_list_for_select")->name("user-roles.list-roles");
    });

    //Admins Module Routing
    Route::resource("admins", "AdminsController");
    Route::prefix('admins')->group(function () {
        Route::post("delete", "AdminsController@destroy")->name("admins.delete");
        Route::post("list-data", "AdminsController@load_data_in_table")->name("admins.load_data_in_table");
        Route::post("change_status", "AdminsController@change_status")->name("admins.change_status");
    });

    //Users Module Routing
    Route::resource("users", "UsersController");
    Route::prefix('users')->group(function () {
        Route::post("delete", "UsersController@destroy")->name("users.delete");
        Route::post("list-data", "UsersController@load_data_in_table")->name("users.load_data_in_table");
        Route::post("change_status", "UsersController@change_status")->name("users.change_status");
    });

    //Categories Module Routing
    Route::resource("categories", "CategoriesController");
    Route::prefix('categories')->group(function () {
        Route::post("delete", "CategoriesController@destroy")->name("categories.delete");
        Route::post("list-data", "CategoriesController@load_data_in_table")->name("categories.load_data_in_table");
        Route::post("change_status", "CategoriesController@change_status")->name("categories.change_status");
        Route::post("change_order", "CategoriesController@change_order")->name("categories.change_order");
    });

    //Countries Module Routing
    Route::resource("countries", "CountriesController");
    Route::prefix('countries')->group(function () {
        Route::post("list-data", "CountriesController@load_data_in_table")->name("countries.load_data_in_table");
        Route::post("delete", "CountriesController@destroy")->name("countries.delete");
        Route::post("change_status", "CountriesController@change_status")->name("countries.change_status");
    });

    //PetBreeds Module Routing
    Route::resource("pet_breeds", "PetBreedsController");
    Route::prefix('pet_breeds')->group(function () {
        Route::post("list-data", "PetBreedsController@load_data_in_table")->name("pet_breeds.load_data_in_table");
        Route::post("delete", "PetBreedsController@destroy")->name("pet_breeds.delete");
        Route::post("change_status", "PetBreedsController@change_status")->name("pet_breeds.change_status");
    });

    //PetTypes Module Routing
    Route::resource("pet_types", "PetTypesController");
    Route::prefix('pet_types')->group(function () {
        Route::post("list-data", "PetTypesController@load_data_in_table")->name("pet_types.load_data_in_table");
        Route::post("delete", "PetTypesController@destroy")->name("pet_types.delete");
        Route::post("change_status", "PetTypesController@change_status")->name("pet_types.change_status");
    });

    //Pets Module Routing
    Route::resource("pets", "PetsController");
    Route::prefix('pets')->group(function () {
        Route::post("delete", "PetsController@destroy")->name("pets.delete");
        Route::post("list-data", "PetsController@load_data_in_table")->name("pets.load_data_in_table");
        Route::post("change_status", "PetsController@change_status")->name("pets.change_status");
        Route::get("delete_member/{id}", "PetsController@delete_member")->name("pets.delete_member");
        Route::get("delete_pet/{id}", "PetsController@delete_pet")->name("pets.delete_pet");
    });
    //Groups Module Routing
    Route::resource("groups", "GroupsController");
    Route::prefix('groups')->group(function () {
        Route::post("delete", "GroupsController@destroy")->name("groups.delete");
        Route::post("list-data", "GroupsController@load_data_in_table")->name("groups.load_data_in_table");
        Route::post("change_status", "GroupsController@change_status")->name("groups.change_status");
        Route::get("delete_member/{id}", "GroupsController@delete_member")->name("groups.delete_member");
        Route::get("delete_group/{id}", "GroupsController@delete_group")->name("groups.delete_group");
    });

    //Events Module Routing
    Route::resource("events", "EventsController");
    Route::prefix('events')->group(function () {
        Route::post("delete", "EventsController@destroy")->name("events.delete");
        Route::post("list-data", "EventsController@load_data_in_table")->name("events.load_data_in_table");
        Route::post("change_status", "EventsController@change_status")->name("events.change_status");
        Route::get("delete_member/{id}", "EventsController@delete_member")->name("events.delete_member");
        Route::get("delete_group/{id}", "EventsController@delete_group")->name("events.delete_group");
        Route::get("delete_event/{id}", "EventsController@delete_event")->name("events.delete_event");
    });

    //Posts Module Routing
    Route::resource("posts", "PostsController");
    Route::prefix('posts')->group(function () {
        Route::post("delete", "PostsController@destroy")->name("posts.delete");
        Route::post("list-data", "PostsController@load_data_in_table")->name("posts.load_data_in_table");
        Route::post("change_status", "PostsController@change_status")->name("posts.change_status");
        Route::get("delete_comment/{id}", "PostsController@delete_comment")->name("posts.delete_comment");
        Route::get("delete_post/{id}", "PostsController@delete_post")->name("posts.delete_post");
    });

    //Cities Module Routing
    Route::resource("cities", "CitiesController");
    Route::prefix('cities')->group(function () {
        Route::post("list-data", "CitiesController@load_data_in_table")->name("cities.load_data_in_table");
        Route::post("delete", "CitiesController@destroy")->name("cities.delete");
        Route::post("change_status", "CitiesController@change_status")->name("cities.change_status");
    });

    //Aboutus Module Routing
    Route::resource("aboutus", "AboutusController");
    Route::prefix('aboutus')->group(function () {
        Route::post("list-data", "AboutusController@load_data_in_table")->name("aboutus.load_data_in_table");
        Route::post("delete", "AboutusController@destroy")->name("aboutus.delete");
        Route::post("change_status", "AboutusController@change_status")->name("aboutus.change_status");
    });

    //Contactus Module Routing
    Route::resource("contactus", "ContactusController");
    Route::prefix('contactus')->group(function () {
        Route::post("list-data", "ContactusController@load_data_in_table")->name("contactus.load_data_in_table");
        Route::post("delete", "ContactusController@destroy")->name("contactus.delete");
        Route::post("change_status", "ContactusController@change_status")->name("contactus.change_status");
    });

    //Faqs Module Routing
    Route::resource("faqs", "FaqsController");
    Route::prefix('faqs')->group(function () {
        Route::post("list-data", "FaqsController@load_data_in_table")->name("faqs.load_data_in_table");
        Route::post("delete", "FaqsController@destroy")->name("faqs.delete");
        Route::post("change_status", "FaqsController@change_status")->name("faqs.change_status");
    });

    //Terms Module Routing
    Route::resource("terms", "TermsController");
    Route::prefix('terms')->group(function () {
        Route::post("list-data", "TermsController@load_data_in_table")->name("terms.load_data_in_table");
        Route::post("delete", "TermsController@destroy")->name("terms.delete");
        Route::post("change_status", "TermsController@change_status")->name("terms.change_status");
    });

    //Feedback Module Routing
    Route::resource("feedback", "FeedbackController");
    Route::prefix('feedback')->group(function () {
        Route::post("list-data", "FeedbackController@load_data_in_table")->name("feedback.load_data_in_table");
        Route::post("delete", "FeedbackController@destroy")->name("feedback.delete");
        Route::post("change_status", "FeedbackController@change_status")->name("feedback.change_status");
    });

    //Push Notification Module Routing
    Route::get('pushnotification', "PushnotificationController@index")->name("pushnotification.index");
    Route::post('pushnotification', "PushnotificationController@send")->name("pushnotification.send");

    //Push Notification Module Routing
    Route::resource('settings', "SettingsController");
});

Route::get('/testpush', function () {
    $test = Artisan::call("sendpush 1");
    dd($test);
});

Route::get('/send_friend_invite_push', function () {
    $test = Artisan::call("send_friend_invite_push 2");
    dd($test);
});

Route::get('/send_added_into_group_push', function () {
    $test = Artisan::call("send_added_into_group_push");
    dd($test);
});

Route::get('/send_added_into_event_push', function () {
    $test = Artisan::call("send_added_into_event_push");
    dd($test);
});

Route::get('/test_send_pending_push', function () {
    $test = Artisan::call("send_pending_push 2020-01-01");
    dd($test);
});

Route::get('/test_ios_push', function () {
    $token = request()->token;
    $test = Artisan::call("test_ios_push $token");
    dd($test);
});

Route::get('/run-migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    echo 'Migration done';
    die;
});

Route::get('/run-migrate-seed', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed');
    echo 'Migration done';
    die;
});

Route::get('/config:cache', function () {
    \Illuminate\Support\Facades\Artisan::call('config:cache');
    echo 'config cache clear';
    die;
});

Route::get('/run-php-testInf0', function () {
    phpinfo();die;
});

Route::get('app_test_send_email', 'LoginController@test_send_email');