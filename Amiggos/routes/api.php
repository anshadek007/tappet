<?php
use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*route for venye app*/

/*Route for venue app end*/

Route::get('deleteMemory', 'API\Venue\CronController@deleteMemory');
Route::post('banner_click', 'API\Venue\VenueUserController@banner_click');


Route::post('chat_setting', 'API\Venue\VenueUserController@chat_setting');



Route::post('delete_memory', 'API\Venue\VenueUserController@delete_memory');
Route::group(['prefix' =>'auth'],function(){
	Route::post('validate_app_version_venuePartner', 'API\Venue\VenueUserController@validate_app_version_venuePartner');

    Route::post('validate_app_version_venuePartner_v1', 'API\Venue\VenueUserController@validate_app_version_venuePartner_v1');


	Route::post('login_venue_partner', 'API\Venue\VenueUserController@login_venue_partner');
	Route::post('forgot_password_venuePartner', 'API\Venue\VenueUserController@forgot_password_venuePartner');

    

	Route::get('language', 'API\Venue\VenueUserController@language'); 
	Route::post('test_notification', 'API\Venue\VenueUserController@test_notification'); 
});

Route::group(['prefix' =>'partner','middleware' => ['partnerApikey']],function(){
	Route::post('venueapp_setting', 'API\Venue\VenueUserController@sidebar_menu');
	Route::post('blockedUser_list', 'API\Venue\VenueUserController@blockedUser_list');
	Route::post('unblock_user', 'API\Venue\VenueUserController@unblock_user');
   
   Route::post('block_user', 'API\Venue\VenueUserController@block_user');

	Route::post('venue_location', 'API\Venue\VenueUserController@venue_location');
	Route::post('getBrands', 'API\Venue\VenueUserController@brands');
	Route::post('getmemorySetting', 'API\Venue\VenueUserController@getmemorySetting');
	Route::post('venue_memory_setting', 'API\Venue\VenueUserController@setMemorySetting');
	Route::post('change_default_venue', 'API\Venue\VenueUserController@change_default_venue');
	Route::post('location_citylist', 'API\Venue\VenueUserController@location_citylist');
	Route::post('dashboard_partner', 'API\Venue\VenueUserController@dashboard_partner');
	Route::post('bookingList', 'API\Venue\VenueUserController@bookingList');
	Route::post('booking_detail', 'API\Venue\VenueUserController@booking_detail');
	
	Route::post('staff_list', 'API\Venue\VenueUserController@staff_list');

	Route::post('new_member', 'API\Venue\VenueUserController@new_member');
	

	Route::post('staff_detail', 'API\Venue\VenueUserController@staff_detail');

	
	Route::post('getmybudget', 'API\Venue\VenueUserController@getmybudget');
	Route::post('user_status','API\Venue\VenueUserController@user_status');
	Route::post('promoters', 'API\Venue\VenueUserController@promoters');
	Route::post('tagging_search', 'API\Venue\MemoryController@tagging_search');

	/*Route::get('viewMemoryPartner/{userid}/{memory_id}/{image_id?}', 'API\MemoryController@viewMemoryPartner');*/


	Route::post('create_memory', 'API\Venue\MemoryController@create_memory');
	
	Route::post('view_memory_partner', 'API\Venue\MemoryController@view_memory_partner');

	

	Route::post('get_featured_product', 'API\Venue\MemoryController@get_featured_product');
	Route::post('memory_approval_list', 'API\Venue\MemoryController@memory_approval_list');
	Route::post('approved_tagged_memory', 'API\Venue\MemoryController@approved_tagged_memory');
	Route::post('addBrandBudget', 'API\StripeController@addBrandBudget');
	Route::post('upcomming_invoice', 'API\StripeController@upcomming_invoice');
	Route::post('getBatachCount', 'API\Venue\VenueUserController@getBatchCount');
    Route::post('checkIn', 'API\Venue\VenueUserController@checkIn');
    Route::post('changedefault_venue', 'API\Venue\VenueUserController@changedefault_venue');
     
    Route::post('code_scan', 'API\Venue\VenueUserController@code_scan');

    Route::post('logout', 'API\Venue\VenueUserController@logout');


});	

Route::post('get_state','API\CustomerController@get_state');
Route::post('get_city','API\CustomerController@get_city');
Route::post('getLanguageConstant_v1', 'API\CustomerController@getLanguageConstant_v1');

Route::group(['prefix'=>'guest'],function(){
	Route::get('getLanguageConstant', 'API\CustomerController@getLanguageConstant');
	Route::post('register', 'API\CustomerController@register');
	Route::post('login', 'API\CustomerController@login');
	Route::post('forgotPassword', 'API\CustomerController@forgotPassword');
	
	Route::post('validateAppVersion', 'API\CustomerController@validateAppVersion');

	Route::group(["middleware" => ['customerAuth']],function(){
		Route::post('getProfile', 'API\CustomerController@getProfile');

		Route::post('getProfile_v1', 'API\CustomerController@getProfile_v1');
		Route::post('set_defaultprofile', 'API\CustomerController@set_defaultprofile');

		Route::post('updateProfile', 'API\CustomerController@updateProfile');
   
        Route::post('updateProfile_image', 'API\CustomerController@updateProfile_image');

		Route::post('updateFirebaseId', 'API\CustomerController@updateFirebaseId');
		Route::post('logout', 'API\CustomerController@logout');
		
		Route::post('updateLatLangUser', 'API\CustomerController@updateLatLangUser');

		Route::post('user_apptime', 'API\CustomerController@user_apptime');
		

		Route::post('create_memory', 'API\MemoryController@create_memory');
		Route::post('addImage', 'API\CustomerController@addImage');
		Route::post('removeOtherImage', 'API\CustomerController@removeOtherImage');
		Route::post('notification_setting', 'API\CustomerController@notification_setting');
		
		Route::post('my_photoid', 'API\CustomerController@my_photoid');
		Route::post('get_photoid', 'API\CustomerController@get_photoid');
		Route::post('delete_photoid', 'API\CustomerController@delete_photoid');
		
		Route::post('mylifestyle','API\CustomerController@mylifestyle');

		Route::post('mylifestyle_v1','API\CustomerController@mylifestyle_v1');
		
		Route::post('mylifestyleCategory','API\CustomerController@mylifestyleCategory');
		
		Route::post('setMylifestyle','API\CustomerController@setMylifestyle');

		Route::post('setMylifestyle_v1','API\CustomerController@setMylifestyle_v1');

		Route::post('updateSettingValue', 'API\CustomerController@updateSettingValue');

		/*friend relate api's start*/
		Route::post('sendFriendRequest','API\FriendController@sendFriendRequest');
		Route::post('acceptFriendRequest', 'API\FriendController@acceptFriendRequest');
		Route::post('declineFriendRequest', 'API\FriendController@declineFriendRequest');
	    Route::post('cancelFriendRequest', 'API\FriendController@cancelFriendRequest');
		Route::post('blockFriend', 'API\FriendController@blockFriend');	    
	    Route::post('unblockFriend', 'API\FriendController@unblockFriend');
		Route::post('blockList', 'API\FriendController@blockList');
	    Route::post('unFriendUser', 'API\FriendController@unFriend');
	    Route::post('getRequestList', 'API\FriendController@getRequestList');
		
		Route::post('getFriendsProfile', 'API\FriendController@getFriendsProfile');

		Route::post('getFriendsProfile_v1', 'API\FriendController@getFriendsProfile_v1');
		
		Route::post('getNearByFriends', 'API\FriendController@getNearByFriends');

		Route::post('addRemovefavouriteFriend', 'API\FriendController@addRemovefavouriteFriend');
		
		Route::post('getNearByUser', 'API\MemoryController@getNearByUser');
		
		Route::post('dashboard', 'API\CustomerController@dashboard');

		Route::post('dashboard_v1', 'API\CustomerController@dashboard_v1');
		
		
		Route::post('getAllNotification', 'API\CustomerController@getAllNotification');

		Route::post('batch_count_customer', 'API\CustomerController@batch_count_customer');

        Route::post('check_blockStatus', 'API\CustomerController@check_blockStatus');
		
		Route::post('clearNotification', 'API\CustomerController@clearNotification');
	    /*friend relate api's End*/

	    /*Club Api's start*/
	    Route::post('getVenueDetails', 'API\ClubController@getVenueDetails');
        Route::post('checkIn_customer', 'API\ClubController@checkIn_customer');

	    Route::post('create_favoriteVenue', 'API\ClubController@create_favoriteVenue');
	    
	    Route::post('getMenu', 'API\ClubController@getMenu');
	    

	    Route::post('save_booking', 'API\ClubController@save_booking');
	    Route::post('save_booking_v1', 'API\ClubController@save_booking_v1');
	    
	    Route::post('getbookings', 'API\ClubController@getbookings');
	    Route::post('getBookingDetails', 'API\ClubController@getBookingDetails');
	    Route::post('createBookingPayment', 'API\ClubController@createBookingPayment');
	    Route::post('getFriendsForPartyInvite', 'API\ClubController@getFriendsForPartyInvite');
	    
	    Route::post('sendPartyInvite', 'API\ClubController@sendPartyInvite');

	    Route::post('unsendPartyInvite', 'API\ClubController@unsendPartyInvite');
	    
	    Route::post('userPartyInvites', 'API\ClubController@userPartyInvites');
	    Route::post('get_timeSlot', 'API\ClubController@get_timeSlot');

	    /*Route::post('getClubCalendar', 'API\ClubController@getClubCalendar');*/

	    Route::post('tagging_search_customer', 'API\MemoryController@tagging_search_customer');

	    Route::post('/createOurStory', 'API\MemoryController@createOurStory');

	    Route::post('/createOurStory_v1', 'API\MemoryController@createOurStory_v1');

	    Route::post('rejectOurStoryInvite', 'API\MemoryController@rejectOurStoryInvite');

	    Route::post('acceptInvitation', 'API\ClubController@acceptInvitation');
	    Route::post('rejectInvitation', 'API\ClubController@rejectInvitation');

	    Route::get('viewMemoryUser/{userid}/{memory_id}/{image_id?}', 'API\MemoryController@viewMemoryUser');

	    Route::post('get_featured_product', 'API\MemoryController@get_featured_product');
	    
	    Route::post('getMyStories', 'API\MemoryController@getMyStories');

		Route::post('getMyStories_v1', 'API\MemoryController@getMyStories_v1');	    
	    
	    Route::post('getOurStories', 'API\MemoryController@getOurStories');
	   
	   Route::post('getOurStories_v1', 'API\MemoryController@getOurStories_v1');

	   Route::post('memory_viewed_list', 'API\MemoryController@memory_viewed_list');

	    Route::post('get_tagedVenue_from_memory', 'API\MemoryController@get_tagedVenue_from_memory');

	    

	    Route::post('userFreindsList', 'API\CustomerController@userFreindsList');
        
        Route::post('batchcount_customer', 'API\CustomerController@batchcount_customer');


        Route::post('userFreindsList_memory', 'API\CustomerController@userFreindsList_memory'); 	    
 
	    Route::post('getFreindProfile', 'API\CustomerController@getFreindProfile');
	     Route::post('freindRealFreind', 'API\CustomerController@freindRealFreind');
	      Route::post('get_Freinffavorite_venue', 'API\CustomerController@get_Freinffavorite_venue');
	      Route::post('getSettingDetails', 'API\CustomerController@getSettingDetails');

	      Route::post('userFreindsList_chat', 'API\CustomerController@userFreindsList_chat');
	});

});

Route::prefix('payment')->group(function(){
	Route::post('creatStripCustomer', 'API\StripeController@creatStripCustomer');
    Route::post('getCard', 'API\StripeController@get_card');
    Route::post('updateCard', 'API\StripeController@updateCard');
    Route::post('deleteCard', 'API\StripeController@delete_card');
    Route::get('generate_stripe_token', 'API\StripeController@generate_stripe_token');
    Route::post('stripe_payment_webhook', 'API\StripeController@stripe_payment_webhook');

    
});
