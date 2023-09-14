<?php

use Illuminate\Support\Facades\Route;

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
Auth::routes();
Route::get('/', 'Auth\LoginController@index');
Route::get('/login', 'Auth\LoginController@index');
Route::post('/login', 'Auth\AdminLoginController@login')->name('login');
Route::get('clearRoute', 'Auth\AdminLoginController@clearRoute');
Route::get('/logout_user/','Web\DashboardController@logout');

Route::group(['middleware'=>'auth'],function(){

Route::group(['prefix'=>'admin'],function() {
Route::get('/dashboard', 'Web\DashboardController@index')->name('dashboard');
   
   Route::post('/ajax_dashboard', 'Web\DashboardController@ajax_dashboard')->name('ajax_dashboard');

	Route::get('/home', 'Web\DashboardController@index')->name('home');
	//role route
	Route::get('/role', 'Web\RoleController@index')->name('role');
	Route::get('/add_role', 'Web\RoleController@add_role')->name('add_role');
	Route::get('/ajax_role', 'Web\RoleController@ajax_role')->name('ajax_role');
	Route::post('/save_role', 'Web\RoleController@save_role')->name('save_role');
	Route::get('/edit_role/{id}', 'Web\RoleController@edit_role')->name('edit_role');
	//partner route
	Route::get('/partner', 'Web\PartnerController@index')->name('partner');
    Route::get('/registerfirebase', 'Web\PartnerController@registerfirebase')->name('registerfirebase');
    

	Route::get('/changestatusamiggostaff/{id}/{status}', 'Web\PartnerController@changestatusamiggostaff')->name('changestatusamiggostaff');
	Route::get('/guest', 'Web\GuestController@index')->name('guest');
	Route::post('/guest', 'Web\GuestController@index')->name('guest');
	Route::get('/deleteguest/{id}', 'Web\GuestController@deleteguest')->name('deleteguest');

	Route::get('/viewguest/{id}', 'Web\GuestController@viewguest')->name('viewguest');
	Route::get('/editguest/{id}', 'Web\GuestController@editguest')->name('editguest');
	Route::get('/changestatusguest/{id}/{status}', 'Web\GuestController@changestatusguest')->name('changestatusguest');
	Route::post('/approveDocument', 'Web\GuestController@approveDocument')->name('approveDocument');
	Route::post('/declineDocument', 'Web\GuestController@declineDocument')->name('declineDocument');
	Route::get('/idproof', 'Web\GuestController@idproof')->name('idproof');
	Route::post('/idproof', 'Web\GuestController@idproof')->name('idproof');
	Route::post('/getcity', 'Web\GuestController@getcity')->name('getcity');
	Route::post('/updateguest', 'Web\GuestController@updateguest')->name('updateguest');

	Route::post('/partner', 'Web\PartnerController@index')->name('partner');
	Route::get('/chat/{id}', 'Web\PartnerController@chat')->name('chat');
	Route::post('/addpartner', 'Web\PartnerController@addpartner')->name('addpartner');
	Route::post('/addvenuepartner', 'Web\PartnerController@addvenuepartner')->name('addvenuepartner');
	Route::post('/addbrandpartner', 'Web\PartnerController@addbrandpartner')->name('addbrandpartner');
	Route::post('/editpartner', 'Web\PartnerController@editpartner')->name('editpartner');
	Route::post('/changepassword', 'Web\PartnerController@changepassword')->name('changepassword');
	Route::get('/deletepartner/{id}', 'Web\PartnerController@deletepartner')->name('deletepartner');
	Route::get('/viewpartner/{id}', 'Web\PartnerController@viewpartner')->name('viewpartner');
	Route::get('/changestatus/{id}/{status}', 'Web\PartnerController@changestatus')->name('changestatus');
	Route::get('/venuepartner', 'Web\PartnerController@venuepartner')->name('venuepartner');
	Route::get('/ajaxvenue', 'Web\PartnerController@ajaxvenue')->name('ajaxvenue');
	Route::post('/venuepartner', 'Web\PartnerController@venuepartner')->name('venuepartner');
	Route::get('/featuredpartner', 'Web\PartnerController@featuredpartner')->name('featuredpartner');
	Route::post('/featuredpartner', 'Web\PartnerController@featuredpartner')->name('featuredpartner');
	Route::post('/delete_venue_image', 'Web\VenuesController@delete_venue_image')->name('delete_venue_image');
	

	Route::prefix('setting')->group(function(){
	   Route::get('/','Web\SettingsController@index')->name('setting');
	   Route::get('/create','Web\SettingsController@create')->name('add-language');;
	   Route::post('/create','Web\SettingsController@create')->name('save-language');

	   Route::post('/save_distance','Web\SettingsController@save_distance')->name('save-distance');
	   Route::get('/edit/{id}','Web\SettingsController@edit');
	   Route::post('/update','Web\SettingsController@update')->name('update-language');	   
	   Route::get('/change_status/{lang_key_id}/{status}','Web\SettingsController@change_status');	   

	   
	   Route::get('/create_page','Web\SettingsController@create_page')->name('create_page');

	   Route::post('/getMemorysetting','Web\SettingsController@getMemorysetting')->name('getMemorysetting');

	   Route::post('/saveMemorysetting','Web\SettingsController@saveMemorysetting')->name('saveMemorysetting');

	   

	   Route::post('/create_page','Web\SettingsController@create_page')->name('save-page');
	   Route::get('/edit_page/{id}','Web\SettingsController@edit_page');	     
   	});
   	Route::prefix('featuredBrands')->group(function(){
		Route::get('/', 'Web\FeaturedBrandController@index')->name('featuredBrands');
		Route::get('/ajaxBrand', 'Web\FeaturedBrandController@ajaxBrand')->name('ajaxBrand');
		Route::get('/addBrand', 'Web\FeaturedBrandController@addBrand')->name('addBrand');
		Route::post('/saveFeaturedBrand', 'Web\FeaturedBrandController@saveFeaturedBrand')->name('saveFeaturedBrand');
		Route::get('/view_featured_brand/{id}', 'Web\FeaturedBrandController@view_featured')->name('view_featured');
		Route::get('/change_status/{brand_id}/{status}','Web\FeaturedBrandController@change_status')->name('change_brand_status');
		Route::get('/brand_promoters', 'Web\FeaturedBrandController@brand_promoters')->name('promoters');
	});

	Route::prefix('report')->group(function(){
	  Route::get('/report', 'Web\ReportsController@index')->name('report');
	  /*Route::post('/genrateReport', 'Web\ReportsController@genrateReport')->name('genrateReport');*/
	  Route::get('/genrateReport', 'Web\ReportsController@genrateReport')->name('genrateReport');
    
   /*  Route::match(array('GET','POST'),'genrateReport', 'Web\ReportsController@genrateReport')->name('genrateReport');*/

	});	


Route::post('/guest', 'Web\GuestController@index')->name('guest');
Route::get('/deleteguest/{id}', 'Web\GuestController@deleteguest')->name('deleteguest');
Route::get('/viewguest/{id}', 'Web\GuestController@viewguest')->name('viewguest');
Route::get('/editguest/{id}', 'Web\GuestController@editguest')->name('editguest');
Route::post('/approveDocument', 'Web\GuestController@approveDocument')->name('approveDocument');
Route::post('/declineDocument', 'Web\GuestController@declineDocument')->name('declineDocument');
Route::get('/idproof', 'Web\GuestController@idproof')->name('idproof');
Route::post('/idproof', 'Web\GuestController@idproof')->name('idproof');
Route::post('/getcity', 'Web\GuestController@getcity')->name('getcity');
Route::post('/updateguest', 'Web\GuestController@updateguest')->name('updateguest');
Route::post('/partner', 'Web\PartnerController@index')->name('partner');
Route::post('/addpartner', 'Web\PartnerController@addpartner')->name('addpartner');
Route::post('/addvenuepartner', 'Web\PartnerController@addvenuepartner')->name('addvenuepartner');
Route::post('/addbrandpartner', 'Web\PartnerController@addbrandpartner')->name('addbrandpartner');
Route::post('/editpartner', 'Web\PartnerController@editpartner')->name('editpartner');
Route::post('/changepassword', 'Web\PartnerController@changepassword')->name('changepassword');
Route::get('/deletepartner/{id}', 'Web\PartnerController@deletepartner')->name('deletepartner');
Route::get('/viewpartner/{id}', 'Web\PartnerController@viewpartner')->name('viewpartner');
Route::get('/changestatus/{id}/{status}', 'Web\PartnerController@changestatus')->name('changestatus');
Route::get('/venuepartner', 'Web\PartnerController@venuepartner')->name('venuepartner');
// Route::get('/ajaxvenue', 'Web\PartnerController@ajaxvenue')->name('ajaxvenue');
Route::post('/venuepartner', 'Web\PartnerController@venuepartner')->name('venuepartner');
Route::get('/featuredpartner', 'Web\PartnerController@featuredpartner')->name('featuredpartner');
Route::post('/featuredpartner', 'Web\PartnerController@featuredpartner')->name('featuredpartner');
});

Route::prefix('StaticPages')->group(function(){
      Route::get('/helpUsingAmiggos','Web\StaticController@helpUsingAmiggos');
      Route::get('/helpMyAccount','Web\StaticController@helpMyAccount');
      Route::get('/helpSafety','Web\StaticController@helpSafety');
      Route::get('/helpPrivacy','Web\StaticController@helpPrivacy');
      Route::get('/Termscondition','Web\StaticController@Termscondition');
      Route::get('/settingLegal','Web\StaticController@settingLegal');
      Route::get('/settingPrivacyPolicy','Web\StaticController@settingPrivacyPolicy');
      Route::get('/amiggos_tc','Web\StaticController@amiggos_tc');
      //settingLegal.
 });

Route::prefix('booking')->group(function(){
Route::get('/booking','Web\BookingController@index')->name("booking");
Route::get('/booking_detail/{id}','Web\BookingController@booking_detail')->name("booking_detail");
Route::get('/ajaxbooking', 'Web\BookingController@ajaxbooking')->name('ajaxbooking');
});	

Route::get('/my_profile/','Web\DashboardController@profile');
Route::post('/updateUser', 'Web\DashboardController@update')->name('updateUser');
Route::get('/notification', 'Web\NotificationController@index')->name('notification');
Route::get('/create_notification/{id}', 'Web\NotificationController@create')->name('create_notification');
Route::get('/venue', 'Web\VenuesController@index')->name('venue');
Route::get('/categories', 'Web\VenuesController@categories')->name('categories');
Route::get('/ajaxcategories', 'Web\VenuesController@ajaxcategories')->name('ajaxcategories');
Route::post('/save_venuetiming', 'Web\VenuesController@save_venuetiming')->name('save_venuetiming');
Route::get('/delete_category/{id}', 'Web\VenuesController@delete_category');
Route::get('/delete_inventory/{id}', 'Web\VenuesController@delete_inventory');
Route::get('/add_category', 'Web\VenuesController@add_category')->name('add_category');
Route::get('/googleVenue', 'Web\VenuesController@googleVenue')->name("googleVenue");
Route::get('/change_status/{id}/{status}', 'Web\VenuesController@change_status')->name("change_status");
Route::get('/ajaxgoogleVenue', 'Web\VenuesController@ajaxgoogleVenue')->name('ajaxgoogleVenue');
Route::post('/addParentCategory', 'Web\VenuesController@addParentCategory')->name('addParentCategory');
Route::post('/editParentCategory', 'Web\VenuesController@editParentCategory')->name('editParentCategory');
Route::post('/add_subCategory', 'Web\VenuesController@add_subCategory')->name('add_subCategory');
Route::post('/update_subCategory', 'Web\VenuesController@update_subCategory')->name('update_subCategory');
Route::get('/edit_category/{id}', 'Web\VenuesController@edit_category')->name('edit_category');
Route::get('/venue', 'Web\VenuesController@index')->name('venue');
Route::get('/add_venue', 'Web\VenuesController@add_venue')->name('add_venue');
Route::post('/save_venue', 'Web\VenuesController@save_venue')->name('save_venue');
Route::get('/ajaxvenue', 'Web\VenuesController@ajaxvenue')->name('ajaxvenue');
Route::post('/getchildcategory', 'Web\VenuesController@getchildcategory')->name('getchildcategory');
Route::get('/change_status_venue/{id}/{status}', 'Web\VenuesController@change_status_venue')->name("change_status_venue");
Route::get('/deletegvenue/{id}', 'Web\VenuesController@deletegvenue')->name('deletegvenue');
Route::get('/editvenue/{id}', 'Web\VenuesController@editvenue')->name('editvenue');
Route::get('/team_member/{id}', 'Web\VenuesController@team_member')->name('team_member');
Route::get('/ajax_teammember', 'Web\VenuesController@ajax_teammember')->name('ajax_teammember');
Route::get('/add_teammember/{id}', 'Web\VenuesController@add_teammember')->name('add_teammember');
Route::post('/changepassword_member', 'Web\VenuesController@changepassword_member')->name('changepassword_member');
Route::get('/teammember_status/{id}/{status}', 'Web\VenuesController@teammember_status')->name('teammember_status');
Route::get('/edit_member/{id}', 'Web\VenuesController@edit_member')->name('edit_member');
Route::post('/save_googleVenue', 'Web\VenuesController@save_googleVenue')->name('save_googleVenue');


Route::get('/app_setting/{id}', 'Web\VenuesController@app_setting')->name('app_setting');
Route::post('/save_setting', 'Web\VenuesController@save_setting')->name('save_setting');

Route::get('/edit_venue/{id}/{status}', 'Web\VenuesController@edit_venue')->name('edit_venue');
Route::post('/save_member', 'Web\VenuesController@save_member')->name('save_member');
Route::get('/venue_timing/{id}', 'Web\VenuesController@venue_timing')->name('venue_timing');
Route::get('/venue_timing/{id}', 'Web\VenuesController@venue_timing')->name('venue_timing');
Route::post('/getCities', 'Web\NotificationController@getCities')->name('getCities');
Route::post('/add_notification', 'Web\NotificationController@add_notification')->name('add_notification');
Route::get('/viewnotification/{id}', 'Web\NotificationController@viewnotification')->name('viewnotification');
Route::post('/getNotificationByFilter', 'Web\NotificationController@getNotificationByFilter')->name('getNotificationByFilter');
Route::get('/inventory/{id}', 'Web\VenuesController@inventory')->name('inventory');
Route::get('/ajax_inventory', 'Web\VenuesController@ajax_inventory')->name('ajax_inventory');
Route::get('/inventory_status/{id}/{status}', 'Web\VenuesController@inventory_status')->name('inventory_status');
Route::get('/add_inventory/{id}', 'Web\VenuesController@add_inventory')->name('add_inventory');

Route::post('/save_inventory', 'Web\VenuesController@save_inventory')->name('save_inventory');

Route::get('/edit_inventory/{id}', 'Web\VenuesController@edit_inventory')->name('edit_inventory');
Route::get('/myMenu/{id}', 'Web\VenuesController@myMenu')->name('myMenu');
Route::get('/edit_mymenu/{id}', 'Web\VenuesController@edit_mymenu')->name('edit_mymenu');
Route::post('/save_menu', 'Web\VenuesController@save_menu')->name('save_menu');
Route::get('/ajax_mymenu', 'Web\VenuesController@ajax_mymenu')->name('ajax_mymenu');
Route::get('/menu_status/{id}/{status}', 'Web\VenuesController@menu_status')->name("menu_status");
Route::get('/delete_menu/{id}', 'Web\VenuesController@delete_menu')->name('delete_menu');
Route::get('/add_menu/{id}', 'Web\VenuesController@add_menu')->name('add_menu');
Route::post('/save_section', 'Web\VenuesController@save_section')->name('save_section');

Route::post('/edit_section', 'Web\VenuesController@edit_section')->name('edit_section');



Route::get('/menuSection/{id}', 'Web\VenuesController@menuSection')->name('menuSection');
Route::get('/ajax_section', 'Web\VenuesController@ajax_section')->name('ajax_section');
Route::get('/delete_section/{id}', 'Web\VenuesController@delete_section')->name('delete_section');
}); //Authantication finish