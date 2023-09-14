<?php
namespace App\Http\Controllers\API\Venue;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Config;
use App\Mail\DemoEmail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\API\ApiController;
use DateTime;
use Helper;

class VenueUserController  extends ApiController
{

	public function login_venue_partner(request $request){
		try{
    	$input = $request->all();
        $validator = Validator :: make($input, [
                    "email" => "required",
                    "password" => "required",
        ]);

        $checkEmail = parent::checkKeyExist("email",$input);
        if(!empty($checkEmail)){
        	$err_msg = $checkEmail;
            return parent :: api_response([], false, $err_msg, 200);
        }

        $checkPassword = parent::checkKeyExist("password",$input);
        if(!empty($checkPassword)){
        	$err_msg = $checkPassword;
            return parent :: api_response([], false, $err_msg, 200);
        }

        $checkDevicetype = parent::checkKeyExist("device_type",$input);
        if(!empty($checkDevicetype)){
        	$err_msg = $checkDevicetype;
            return parent :: api_response([], false, $err_msg, 200);
        }

        $checkDevicetoken = parent::checkKeyExist("device_token",$input);
        if(!empty($checkDevicetoken)){
        	$err_msg = $checkDevicetoken;
            return parent :: api_response([], false, $err_msg, 200);
        }

        if ($validator->fails()) {
            $err_msg = $validator->errors()->first();
            return parent :: api_response([], false, $err_msg, 200);
        }else{
        	$check_user = DB::table("users")->select("id as userid","password","phone","first_name","last_name","email","phone","profile_image","api_key","type","venue_id","default_venue","is_applogin","is_venuepartner","firebase_id","unique_timestamp")->where("email", "=", $input["email"])->whereNull("deleted_at")->where("status", "=", "1")->first();
         
          if (!empty($check_user->userid) && !empty($check_user->venue_id)) {

          if($check_user->is_applogin=="1") {  

                if (Hash::check($input["password"], $check_user->password)) {
                    $device_type = (!empty($input["device_type"]) ? $input["device_type"] : "" );
                    $device_token = (!empty($input["device_token"]) ? $input["device_token"] : "" );
                    $api_token  = Str::random(40);
                    $update_data = array(
                      "device_type" => $device_type,
                      "device_token" => $device_token,
                      "api_key"=>$api_token
                    );                    
                    if($check_user->default_venue == 0){
                      $exp  = explode(",",$check_user->venue_id);
                      $check_user->default_venue = $exp[0];
                      $update_data['default_venue'] = $exp[0];
                    }  

                     if(empty($check_user->unique_timestamp)){
                       $check_user->unique_timestamp=""; 
                    }                  

                	 $update_device_det = DB::table("users")->where("id", "=", $check_user->userid)->update($update_data);

                	 if(empty($check_user->profile_image)){
                	 	$check_user->profile_image = url("public/default.png");
                	 }
                   else{
                    $check_user->profile_image = url("public/uploads/user/partner/venue_staff/".$check_user->profile_image);
                   }
                    unset($check_user->password);
                    
                    $check_user->phone       = (!empty($check_user->phone) ? $check_user->phone : "");   
                     $check_user->api_key       = (!empty($api_token) ? (string)$api_token : "");
                   $check_user->type = (string) $check_user->type;
                    //check user role permission 
                    $permission = array(
                                    
                                    "is_dashboard"   =>"0",
                                    "is_memory"      =>"0",
                                    "is_booked"      =>"0",
                                    "is_staff"       =>"0",
                                    "is_message"     =>"0",
                                    );
                    if($check_user->type==2){
                    if($check_user->is_venuepartner==1){
                      $permission = array(
                                   
                                    "is_dashboard"   =>"1",
                                    "is_memory"      =>"1",
                                    "is_booked"      =>"1",
                                    "is_staff"       =>"1",
                                    "is_message"     =>"1",
                                    "is_scan"     =>"1",
                                    );
                    }
                    else{
                      $get_user = DB::table("user_app_permission")->where("userid","=",$check_user->userid)->first();
                      $permission = array(
                                    "is_dashboard"   =>$get_user->is_dashboard,
                                    "is_memory"      =>$get_user->is_memory,
                                    "is_booked"      =>$get_user->is_booked,
                                    "is_staff"       =>$get_user->is_staff,
                                    "is_message"     =>$get_user->is_message,
                                     "is_scan"     =>$get_user->is_scan
                                    );

                    }

                    }
                    else{
                      $permission = array(
                                    "is_dashboard"   =>"1",
                                    "is_memory"      =>"1",
                                    "is_booked"      =>"1",
                                    "is_staff"       =>"1",
                                    "is_message"     =>"1",
                                    );

                    }

                    $check_user->permission = $permission;
                    $check_user->permission_message = "Sorry, you don’t have access to this. To fix this, ask your Amiggos Admin to provide access.";
                    $data = $check_user;

                    $message = "Login successfully.";
                    return parent::api_response($data, true, $message, 200);
                } else {
                    $data = [];
                    $message = "Invalid password.";
                    return parent::api_response((object) $data, false, $message, 200);
            }
            }
            else{
                    $data = [];
                    $message = "You are not allowed to login to app,please contact to venue admin.";
                    return parent::api_response((object) $data, false, $message, 200);

            }

            } else {
                $data = [];
                $message = "Account does not exist.";
                return parent::api_response((object) $data, false, $message, 200);
            }

        }
      
    }
    catch(\Exception $e){
    	$err_msg = $e->getMessage();;
        return parent :: api_response([], false, $err_msg, 200);
    }
	}

  public function sidebar_menu(request $request){
    try{
        $input = $request->all(); 
        $checktype = parent::checkKeyExist("type",$input);
        if(!empty($checktype)){
            $err_msg = $checktype;
            return parent :: api_response([], false, $err_msg, 200);
        }

        $validator  = Validator::make($input,[
            'userid'         => "required",  
            'type'           => "required", 
        ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200); 
        }else{  
          if($input["type"]==2){
           $user = Helper::userDetail($input["userid"]);   
         if($user->is_venuepartner==1){
             $setting = DB::table("app_setting")->select("id","value as key")->where("type","=",$input["type"])->orderBy('sort')->get()->toArray();
          }
           else{
            //get app stting
            $usersetting = DB::table("user_app_permission")->where("userid","=",$input["userid"])->first();

            $setting = DB::table("app_setting")->select("id","value as key")->where("type","=",$input["type"]);
            if($usersetting->is_location==0){
              $setting =$setting->Where('value', '!=',"Locations");
            }
            if($usersetting->is_memory_side==0){
              $setting =$setting->Where('value', '!=',"Memories");
            }

             if($usersetting->is_blockeduser==0){
              $setting =$setting->Where('value', '!=',"Blocked Users");
            }

            if($usersetting->is_language==0){
              $setting =$setting->Where('value', '!=',"Language");
            }

            $setting=$setting->orderBy('sort')->get()->toArray();

             
             

            
           }   
          }

          
          else{
           $setting = DB::table("app_setting")->select("id","value as key")->where("type","=",$input["type"])->orderBy('sort')->get()->toArray();
           

          }
          //check for default venue
          $default_venue = DB::table("users")->select("default_venue")->where("id","=",$input["userid"])->first();
          $default_venue = (!empty($default_venue->default_venue) ? $default_venue->default_venue : "0" ); 
          if(!empty($setting)){
            $err_msg = "Record fetched successfully.";
            return parent :: api_response(["default_venue"=>$default_venue,"setting"=>$setting],true,$err_msg, 200);  
          }else{
            $err_msg = "Setting is not found";
            return parent :: api_response([],false,$err_msg, 200);  
          }                
        }
    }
    catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
    }
  }

    public function language(Request $request) {

        $lang_data = parent :: getLanguageValues($request);

        $csvData = array();

        if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
            $csvData = $lang_data['csvData'];
        }

        $language = DB::table('language')->select(DB::raw('id,  name ,image ,iso2_code'))
                ->where('status', '=', '1')
                ->get();

        if (!empty($language)) {
            foreach ($language as $k => $lng) {
                $lng->image = url('public/uploads/flags_image/') . '/' . $lng->image;
            }
            $data['language'] = $language;

            $res_msg = isset($csvData['success']) ? $csvData['success'] : "";

            return parent :: api_response($data,true, $res_msg, '200');
        } else {
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
            return parent :: api_response([],true, $res_msg, '200');
        }
    }

    public function blockedUser_list(request $request){
      try{
        $input = $request->all(); 
        $validator  = Validator::make($input,[
                     'userid'    => "required",  
                     'venue_id'  => "required",              
                    ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200);
        }else{
          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }
          $get_blocked_user = DB::table("blocked_user as b")->select("b.blocked_user_id as userid","b.blocked_user_type","b.blocked_user_id")
          ->where("b.venue_id","=",$input["venue_id"])
          ->whereNull("b.deleted_at")->get();

        
          if(!empty($get_blocked_user[0]->userid)){ 

            foreach($get_blocked_user as $key=>$val){
              if($val->blocked_user_type=="4"){
              $user_det = DB::table("guest")->select("userid","name as first_name","last_name","profile")->where("userid","=",$val->blocked_user_id)->first();
                if(!empty($user_det->profile)){
                $get_blocked_user[$key]->profile = url("public/uploads/user/customer")."/".$user_det->profile;
              }
              else{
                $get_blocked_user[$key]->profile = url("public/default.png");
              }
              }
              else if($val->blocked_user_type=="2"){

                $user_det = DB::table("users")->select("id as userid","first_name","last_name","profile_image as profile")->where("id","=",$val->blocked_user_id)->first();
                if(!empty($user_det->profile)){
                $get_blocked_user[$key]->profile = url("public/uploads/user/partner/venue_staff")."/".$user_det->profile;
              }
              else{
                $get_blocked_user[$key]->profile = url("public/default.png");
              }
              
              }
              else if($val->blocked_user_type=="3"){

                $user_det = DB::table("users")->select("id as userid","first_name","last_name","profile_image as profile")->where("id","=",$val->blocked_user_id)->first();
                if(!empty($user_det->profile)){
                $get_blocked_user[$key]->profile = url("public/uploads/user/partner/venue_staff")."/".$user_det->profile;
              }
              else{
                $get_blocked_user[$key]->profile = url("public/default.png");
              }
              
              }

              $get_blocked_user[$key]->name = $user_det->first_name." ".$user_det->last_name;
                            
              
              unset($get_blocked_user[$key]->blocked_user_type);
              unset($get_blocked_user[$key]->blocked_user_id);
            }
            $res_msg = isset($csvData['success']) ? $csvData['success'] : "Record fetched successfully.";                       
            return parent::api_response(["blocked_user"=>$get_blocked_user],true, $res_msg, '200');
          }
          else{
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "No record found.";
            return parent :: api_response([],false, $res_msg, '200');
          }
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

    public function block_user(request $request){
      try{         
        $input      = $request->all();        
        $validator  = Validator::make($input,[
                   'userid'            => "required",  
                   'venue_id'          => "required", 
                   "blocked_user_id"   => "required",
                   "blocked_user_type" => "required" ,
                             
                ]);
        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200);         
        }else{
          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $get_blocked_user = DB::table("blocked_user")->select("blocked_user_id as userid")->where("venue_id","=",$input["venue_id"])->where("blocked_user_id","=",$input["blocked_user_id"])->where("blocked_user_type","=",$input["blocked_user_type"])->whereNull("deleted_at")->get();
          if(empty($get_blocked_user[0]->userid)){ 
            $user_det = Helper::userDetail($input['userid']);
            $venue_type ="";
            if(!empty($user_det->type)){
              $venue_type =$user_det->type;
            }

            $insert = DB::table("blocked_user")->insert(["user_id"=>$input['userid'],"blocked_user_id"=>$input["blocked_user_id"],"venue_id"=>$input["venue_id"],"blocked_user_type"=>$input["blocked_user_type"],"blocked_venue_type"=>$venue_type]);
            
            //getblocked user det
            $get_user = DB::table("guest")->select("device_type","firebase_id")->where("userid","=",$input["blocked_user_id"])->first();

            if(!empty($get_user->device_type) && !empty($get_user->firebase_id)){
              $device_type = $get_user->device_type;
              $device_token = $get_user->firebase_id;
              $subject      = "Profile blocked";
              $message      = "Your profile has been blocked.";
              $userid       = $input["blocked_user_id"];
              $key          = 10;
               /*$send_notification = $this->send_notification($device_type,$device_token,$subject,$message,$userid,$key);*/
            }
            $res_msg = isset($csvData['success']) ? $csvData['success'] : "User blocked successfully.";                       
            return parent :: api_response([],true, $res_msg, '200');
          }
          else{
             $res_msg = isset($csvData['already_blocked_user']) ? $csvData['already_blocked_user'] : "You have already blocked this user.";
             return parent :: api_response([],false, $res_msg, '200');
          }
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

    public function test_notification(request $request){
     $device_type = "1";
              $devicetoken = "1bf325b2d4932fc11c644d439690d55a65bc1b07eedc3644590a4b84b2d099b1";
              $subject      = "Profile blocked";
              $message      = "Your profile has been blocked."; 
     $send = Helper::sendNotification($device_type , $devicetoken, $message, $subject, $notify_data = "");
    }

    public function unblock_user(request $request){
      try{         
        $input      = $request->all();        
        $validator  = Validator::make($input,[
                   'userid'         => "required",  
                   'venue_id'       => "required", 
                   "blocked_user_id"=> "required"             
                ]);
        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200);         
        }else{
          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $get_blocked_user = DB::table("blocked_user")->select("blocked_user_id as userid")->where("venue_id","=",$input["venue_id"])->where("blocked_user_id","=",$input["blocked_user_id"])->whereNull("deleted_at")->get();
          if(!empty($get_blocked_user[0]->userid)){ 
            $date         = date("Y-m-d h:i:s");
            $unblock_user = DB::table("blocked_user")->where("venue_id","=",$input["venue_id"])->where("blocked_user_id","=",$input["blocked_user_id"])->update(["deleted_at"=>$date]);
            
            //getblocked user det
            $get_user = DB::table("guest")->select("device_type","firebase_id")->where("userid","=",$input["blocked_user_id"])->first();

            if(!empty($get_user->device_type) && !empty($get_user->firebase_id)){
              $device_type = $get_user->device_type;
              $device_token = $get_user->firebase_id;
              $subject      = "Profile Unblocked";
              $message      = "Your profile has been unblocked.";
              $userid       = $input["blocked_user_id"];
              $key          = 10;
               /*$send_notification = $this->send_notification($device_type,$device_token,$subject,$message,$userid,$key);*/
            }
            $res_msg = isset($csvData['success']) ? $csvData['success'] : "User unblocked successfully.";                       
            return parent :: api_response([],true, $res_msg, '200');
          }
          else{
             $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "No such user found in your blocked list..";
             return parent :: api_response([],false, $res_msg, '200');
          }
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

    public function promoters(Request $request){

      $input      = $request->All();
      $validator  = Validator::make($input,[
          "userid"         => "required",
          "promoters_type" => "required"  //2=venue,3=brand          
      ]);

      if($validator->fails()){
        $err_msg = $validator->errors()->first();
        return parent :: api_response([],false,$err_msg, 200);
      }else{   
        try{   

          $userid     = $input['userid'];
          $find_sets  = '';    
          if($input['promoters_type']==2){  
            die("ln");     //venue promoters
            $venues = DB::table('users')->select('venue_id')
                      ->where('id',$userid)->first();
            if(!empty($venues)){
              $venue_ids = explode(',',$venues->venue_id);            
              $find_set = array();
              for ($i=0; $i < count($venue_ids); $i++) { 
                $find_set[] = 'FIND_IN_SET('.$venue_ids[$i].',tagged_venue)';
              }
              $find_sets = implode(' OR ',$find_set);                        
            }else{
              $res_msg = "No venue found";
              return parent :: api_response([],false, $res_msg, '200');
            }
          }else if($input['promoters_type']==3){   //brand promoters   
          
           $get_default_brand = DB::table("users")->select("default_venue")->where("id","=",$userid)->first();  
          
           /* $brand = DB::table('featured_product')->select('id as brand_id','brandAdministrator')->where('id',$get_default_brand->default_venue)->first();*/
            /*if(!empty($brand)){
              $brand_ids = explode(',',$brand->brand_id);            
              $find_set = array();
              for ($i=0; $i < count($brand_ids); $i++) { 
                $find_set[] = 'FIND_IN_SET('.$brand_ids[$i].',featured_brand_id)';
              }
              $find_sets = implode(' OR ',$find_set);                           
            }else{
              $res_msg = "Invalid promoters type";
              return parent :: api_response([],false, $res_msg, '200');
            }*/
          }           
          $promoters = DB::table('memory_approval as ums')
                        ->select( DB::raw('GROUP_CONCAT(`ums`.`memory_id`) as memory_id'),'g.userid','g.name','g.last_name','real_freind_count','g.profile')
                        ->join('guest as g','g.userid','=','ums.userid')
                        ->where("featured_brand_id","=",$get_default_brand->default_venue)
                        ->groupBy('ums.userid')
                        ->get()->toArray();
          if(!empty($promoters)){
            foreach ($promoters as $key => $value) {
              $memory_id = explode(',', $value->memory_id);
              $promoters[$key]->profile = asset('public/uploads/user/customer/'.$value->profile);
              
              $total_view  = DB::table('user_memory_views')->whereIn('memory_id',$memory_id)->count();
              $total_click = DB::table('banner_click')                
              ->whereIn('memory_id',$memory_id)->count('id');                
              $promoters[$key]->total_view = $total_view;
              $promoters[$key]->total_click = $total_click; 
              $promoters[$key]->real_freind_count                              =Helper::total_real_freind($value->userid);   
              //unset($promoters[$key]->memory_id);
            }
            $res_msg = "Success";
            $data['total_click_image']  = asset('public/venue_icon/total_click.png');
            $data['total_view_image']   = asset('public/venue_icon/total_view.png');
            $data['total_friend_image'] = asset('public/venue_icon/total_friend.png');
            $data['promoters']          = $promoters;
            return parent :: api_response($data, true, $res_msg, '200');
          }else{
            $res_msg = "Promoters is not found";
            return parent :: api_response([],false, $res_msg, '200');
          }
        }catch(\Exception $e){
          $res_msg = $e->getMessage();
          return parent :: api_response([],false, $res_msg, '200');
        }      
      }
    }
      

    public function venue_location(request $request){
      try{
          $input      = $request->all(); 
          $validator  = Validator::make($input,[
                      'userid'         => "required",
                      'city_id'        => "required",  
                    ]);
          if($validator->fails()){
             $err_msg = $validator->errors()->first();
             return parent :: api_response([],false,$err_msg, 200);
          }else{
            $lang_data = parent :: getLanguageValues($request); 
            $csvData = array();
            if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
            }

            $get_address = DB::table("users")->where("id","=",$input["userid"])->select("venue_id","default_venue")->first();
           
            if(!empty($get_address->venue_id)){
              $exp = explode(",",$get_address->venue_id);
              foreach($exp as $v){
                $is_active  = false;
                $get_add    = DB::table("venue")->select('name')->where("id","=",$v)->where("city_id","=",$input["city_id"])->first();
                if($get_address->default_venue==$v){
                  $is_active =true;
                }
                if(!empty($get_add->name)){
                  $add[]= array("venue_id"=>$v,"name"=>$get_add->name,"is_active"=>$is_active);
                }
              }
            }
            else{
              $add[]= "";
            } 
            if(!empty($add)){
              $res_msg = isset($csvData['success']) ? $csvData['success'] : "locations fetched successfully.";
              return parent :: api_response(["location"=>$add], true, $res_msg, '200');
            }
            else{
              $res_msg = isset($csvData['success']) ? $csvData['success'] : "No location found.";
              return parent :: api_response([], true, $res_msg, '200');
            }
          } 
        }
        catch(\Exception $e){
          $res_msg = $e->getMessage();
          return parent :: api_response([],false,$res_msg, 200);  
        }
    }
    public function brands(request $request){
      try{
          $input      = $request->all(); 
          $validator  = Validator::make($input,[
                      'userid'         => "required"                      
                    ]);
          if($validator->fails()){
             $err_msg = $validator->errors()->first();
             return parent :: api_response([],false,$err_msg, 200);
          }else{
            $lang_data = parent :: getLanguageValues($request); 
            $csvData = array();
            if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
            }

            $brands = DB::table('featured_product as fp')
                          ->select('fp.id as brand_id','fp.name','users.default_venue')
                          ->join('users','fp.brandAdministrator','=','users.id')
                          ->where([['users.type','3'],['fp.status','1'],['brandAdministrator',$input["userid"]]])
                          ->whereNull('fp.deleted_at')->get()->toArray();
                                      
            if(!empty($brands)){
              foreach ($brands as $key => $value) {
                $brands[$key]->is_active = false;
                if($value->default_venue==$value->brand_id){
                  $brands[$key]->is_active = true;
                }                
              }
             

              $res_msg = isset($csvData['success']) ? $csvData['success'] : "success";
              return parent :: api_response(["brands"=>$brands], true, $res_msg, '200');                
            }
            else{
              // $res_msg = isset($csvData['success']) ? $csvData['success'] : "Brand is not found.";
              $res_msg = "Brand is not found.";
              return parent :: api_response([],false, $res_msg, '200');
            }
          } 
        }
        catch(\Exception $e){
          $res_msg = $e->getMessage();
          return parent :: api_response([],false,$res_msg, 200);  
        }
    }
    public function getmemorySetting(request $request){
      try{
        $input      = $request->all(); 
        $validator  = Validator::make($input,[
            'userid'   => "required", 
            'venue_id' => "required", 
            'usertype' => "required",  
        ]);
        if($validator->fails()){
           $err_msg = $validator->errors()->first();
           return parent :: api_response([],false,$err_msg, 200); 
        }else{
          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          if($input["usertype"]==2){
          $get_setting = DB::table("venue")->where("id","=",$input["venue_id"])->select("auto_approve_memory")->first();
          }
          else{
          $get_setting = DB::table("featured_product")->where("id","=",$input["venue_id"])->select("auto_approve_memory")->first();

          }

          $res_msg = isset($csvData['success']) ? $csvData['success'] : "setting fetched successfully.";               
          return parent :: api_response($get_setting,true, $res_msg, '200');
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }
    public function setMemorySetting(request $request){
      try{
        $input      = $request->all();
        $validator  = Validator::make($input,[
                        'userid'       => "required",
                        'venue_id'     =>"required",
                        'setting_type' =>"required",
                        'usertype' =>"required"
                      ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200);         
        }else{
          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          if($input["usertype"]==2){
          $update = DB::table("venue")->where("id","=",$input["venue_id"])->update(["auto_approve_memory"=>$input["setting_type"]]);
         }
         else {
           $update = DB::table("featured_product")->where("id","=",$input["venue_id"])->update(["auto_approve_memory"=>$input["setting_type"]]);

         }  

          $res_msg = isset($csvData['success']) ? $csvData['success'] : "setting changed successfully.";
          return parent :: api_response([],true, $res_msg, '200');
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

	public function forgot_password_venuePartner(request $request) {
       
		try{
        $input = $request->all();
       
        $validator = Validator :: make($input, [
                    'email' => "required"
        ]);

        if ($validator->fails()) {

             $err_msg = $validator->errors()->first();
             return parent :: api_response([], false, $err_msg, 200);
            //return parent :: api_response([],false,$validator->errors()->first(), 200);
        } else {

            $user_data = DB::table('users')
                            //->where('id','=',$input['user_id'])
                            ->where('email', '=', $input['email'])
                            ->whereNull("deleted_at")
                            ->select('id as userid', 'first_name','last_name')
                            ->get()->toArray();

            if (isset($user_data[0]) && !empty($user_data[0])) {
                $new_password = time();

                DB::table('users')
                        ->where('id', $user_data[0]->userid)
                        ->update(['password' => Hash::make($new_password)]);

                /* email functionality goes here */
                $objDemo = new \stdClass();
                $objDemo->demo_one = 'Your new password is ' . $new_password . ".Please change after login.";
                $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                $objDemo->website = Config::get('constants.SENDER_WEBSITE');
                $objDemo->sender_name = Config::get('constants.SENDER_NAME');
                $objDemo->receiver = $input['email'];
                $objDemo->receiver_name = $user_data[0]->first_name." ".$user_data[0]->last_name;
                $objDemo->subject = "Forgot Password";
			    Mail::to($input['email'])->send(new DemoEmail($objDemo));		
                

                

                $res_msg = "Your new password has been sent to your registered email address,please check your email. ";
                /* email functionality goes here */
                return parent :: api_response([], true, $res_msg, 200);
            } else {

                $res_msg = "Email address does not exist.";
                return parent :: api_response([], false, $res_msg, 200);
            }
        }
        }
    catch(\Exception $e){
    	$err_msg = $e->getMessage();;
        return parent :: api_response([], false, $err_msg, 200);
    }
    }

    public function validate_app_version_venuePartner(request $request){
    	try{
				$input = $request->all(); 
        $userid = "";
        if(isset($input["userid"])){
            $userid =$input["userid"];
        }


				$checkappVersion = parent::checkKeyExist("app_version",$input);
      		  if(!empty($checkappVersion)){
        		$err_msg = $checkappVersion;
          		return parent :: api_response([], false, $err_msg, 200);
        }

       			 $validator  = Validator :: make($input,[
           			 'app_version'         => "required",   
             
      			  ]);


       			 if($validator->fails()){

         			 $err_msg = $validator->errors()->first();
         			 return parent :: api_response([],false,$err_msg, 200);
         
       				 }else{
       				 	//get version
       				 	$version = DB::table('appversion')->first();
       				 	$type="";
       				 	if($version->version==$input['app_version']){
       				 			$type =1;
       				 	}
       				 	else{
       				 			$type  = $version->status;
       				 	}

       				 	/*if(!empty($version->status)){
       				 		$status=$version->status;
       				 	}
       				 	else{
       				 		$status="";
       				 	}*/

       				 	
       				 	
       				 	switch ($type) {
					  case 1:

					    $msg = "Your app version is  coreect.";
					    $btn_text_one= "";
						$btn_text_two= "";
					   
					    break;
					  case 2:
					   $msg = "Your app version is outdated please update.";
					    $btn_text_one= "Update";
						$btn_text_two= "";
					    break;
					  case 3:
					     $msg = "New Update is available.";
					     $btn_text_one= "Update";
						$btn_text_two= "Skiip";
						 break;
					 case 4:
					     $msg = "We are currently down.";
					     $btn_text_one= "Ok";
						$btn_text_two= "";	
					     break;
					   
					  default:
					    $msg = "Your app version is not coreect.";
					     $btn_text_one= "";
						$btn_text_two= "";
					    
					}

          $permission = (object) array();
          $user_type = 0;
          
          if(!empty($userid)){
           $permission = array(
                                    
                                    "is_dashboard"   =>"0",
                                    "is_memory"      =>"0",
                                    "is_booked"      =>"0",
                                    "is_staff"       =>"0",
                                    "is_message"     =>"0",
                                    ); 
           $check_user = DB::table("users")->select("id","type","is_applogin","is_venuepartner")->where("id", "=", $userid)->whereNull("deleted_at")->where("status", "=", "1")->first();
            if($check_user->type==2){
             
                    if($check_user->is_venuepartner==1){
                      $permission = array(
                                   
                                    "is_dashboard"   =>"1",
                                    "is_memory"      =>"1",
                                    "is_booked"      =>"1",
                                    "is_staff"       =>"1",
                                    "is_message"     =>"1",
                                    "is_scan"     =>"1",
                                    );
                    }
                    else{
                      $get_user = DB::table("user_app_permission")->where("userid","=",$check_user->id)->first();
                      $permission = array(
                                    "is_dashboard"   =>$get_user->is_dashboard,
                                    "is_memory"      =>$get_user->is_memory,
                                    "is_booked"      =>$get_user->is_booked,
                                    "is_staff"       =>$get_user->is_staff,
                                    "is_message"     =>$get_user->is_message,
                                     "is_scan"     =>$get_user->is_scan
                                    );

                    }

                    }
                    else{
                      $permission = array(
                                    "is_dashboard"   =>"1",
                                    "is_memory"      =>"1",
                                    "is_booked"      =>"1",
                                    "is_staff"       =>"1",
                                    "is_message"     =>"1",
                                    );

                    }

                    $check_user->permission = $permission;
                    $user_type              = $check_user->type;

          }

					$data = array("type"=>$type,"btn_text_one"=>$btn_text_one,"btn_text_two"=>$btn_text_two,"permission"=>$permission,"usertype"=>$user_type);    
					return parent :: api_response($data,true,$msg, 200);     

         		}	
		}
		catch(\Exception $e){
			$res_msg = $e->getMessage();
            return parent :: api_response([],false,$res_msg, 200);  
		}


    }


    public function validate_app_version_venuePartner_v1(request $request){
      try{
        $input = $request->all(); 
        $userid = "";
        if(isset($input["userid"])){
            $userid =$input["userid"];
            
        }

        if(isset($input["device_type"])){
            
            $device_type =$input["device_type"];
        }


        $checkappVersion = parent::checkKeyExist("app_version",$input);
            if(!empty($checkappVersion)){
            $err_msg = $checkappVersion;
              return parent :: api_response([], false, $err_msg, 200);
        }

             $validator  = Validator :: make($input,[
                 'app_version'         => "required", 
                  'device_type'         => "required"  
             
              ]);


             if($validator->fails()){

               $err_msg = $validator->errors()->first();
               return parent :: api_response([],false,$err_msg, 200);
         
               }else{
                //get version
                $version = DB::table('appversion')->where("device_type",$input["device_type"])->first();
                $type="";
                if(!empty($version)){
                if($version->version==$input['app_version']){
                    $type =1;
                }
                else{
                    $type  = $version->status;
                }
              }
              else{
                 $type  =0;
              }

                /*if(!empty($version->status)){
                  $status=$version->status;
                }
                else{
                  $status="";
                }*/

                
                
                switch ($type) {
            case 1:

              $msg = "Your app version is  coreect.";
              $btn_text_one= "";
            $btn_text_two= "";
             
              break;
            case 2:
             $msg = "Your app version is outdated please update.";
              $btn_text_one= "Update";
            $btn_text_two= "";
              break;
            case 3:
               $msg = "New Update is available.";
               $btn_text_one= "Update";
            $btn_text_two= "Skiip";
             break;
           case 4:
               $msg = "We are currently down.";
               $btn_text_one= "Ok";
            $btn_text_two= "";  
               break;
             
            default:
              $msg = "Your app version is not coreect.";
               $btn_text_one= "";
            $btn_text_two= "";
              
          }

          $permission = (object) array();
          $user_type = 0;
          
          if(!empty($userid)){
           $permission = array(
                                    
                                    "is_dashboard"   =>"0",
                                    "is_memory"      =>"0",
                                    "is_booked"      =>"0",
                                    "is_staff"       =>"0",
                                    "is_message"     =>"0",
                                    ); 
           $check_user = DB::table("users")->select("id","type","is_applogin","is_venuepartner")->where("id", "=", $userid)->whereNull("deleted_at")->where("status", "=", "1")->first();
            if($check_user->type==2){
             
                    if($check_user->is_venuepartner==1){
                      $permission = array(
                                   
                                    "is_dashboard"   =>"1",
                                    "is_memory"      =>"1",
                                    "is_booked"      =>"1",
                                    "is_staff"       =>"1",
                                    "is_message"     =>"1",
                                    "is_scan"     =>"1",
                                    );
                    }
                    else{
                      $get_user = DB::table("user_app_permission")->where("userid","=",$check_user->id)->first();
                      $permission = array(
                                    "is_dashboard"   =>$get_user->is_dashboard,
                                    "is_memory"      =>$get_user->is_memory,
                                    "is_booked"      =>$get_user->is_booked,
                                    "is_staff"       =>$get_user->is_staff,
                                    "is_message"     =>$get_user->is_message,
                                     "is_scan"     =>$get_user->is_scan
                                    );

                    }

                    }
                    else{
                      $permission = array(
                                    "is_dashboard"   =>"1",
                                    "is_memory"      =>"1",
                                    "is_booked"      =>"1",
                                    "is_staff"       =>"1",
                                    "is_message"     =>"1",
                                    );

                    }

                    $check_user->permission = $permission;
                    $user_type              = $check_user->type;

          }

          $data = array("type"=>$type,"btn_text_one"=>$btn_text_one,"btn_text_two"=>$btn_text_two,"permission"=>$permission,"usertype"=>$user_type);    
          return parent :: api_response($data,true,$msg, 200);     

            } 
    }
    catch(\Exception $e){
      $res_msg = $e->getMessage();
            return parent :: api_response([],false,$res_msg, 200);  
    }


    }

    public function location_citylist(request $request){
       try{
                $input = $request->all(); 
                $checkuserid = parent::checkKeyExist("userid",$input);
                if(!empty($checkuserid)){
                $err_msg = $checkuserid;
                return parent :: api_response([], false, $err_msg, 200);
                }

        

         $validator  = Validator :: make($input,[
                     'userid'         => "required",  
                    ]);


                 if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{
                     $get_address = DB::table("users")->where("id","=",$input["userid"])->select("venue_id","default_venue")->first();
                       if(!empty($get_address->venue_id)){
                         $exp = explode(",",$get_address->venue_id);
                        $check =array();
                         foreach($exp as $v){
                            $is_active =false;
                            $get_add = DB::table("venue as v")->select('v.city_id','c.name')->leftjoin("cities as c","v.city_id","c.id")->where("v.id","=",$v)->whereNull("v.deleted_at")->first();
                            if(!empty($get_add->city_id)){
                            if(!in_array($get_add->city_id,$check)){
                            if($get_address->default_venue==$v){
                                $is_active =true;
                            }
                            $add[]= array("venue_id"=>$v,"city_id"=>$get_add->city_id,"city_name"=>$get_add->name,"is_active"=>$is_active);

                           array_push($check,$get_add->city_id);
                       }
                       }
                         }
                       }
                       else{
                        $add[]= "";
                       } 

                     
                     if(!empty($add)){
                        $res_msg = isset($csvData['success']) ? $csvData['success'] : "locations fetched successfully.";
                       
                    return parent :: api_response(["location"=>$add],true, $res_msg, '200');
                     }
                     else{
                        $res_msg = isset($csvData['success']) ? $csvData['success'] : "No location found.";
                       
                    return parent :: api_response([],true, $res_msg, '200');

                     }

                     } 
       }

      catch(\Exception $e){
            $res_msg = $e->getMessage();
            return parent :: api_response([],false,$res_msg, 200);  
        }

    }

   

    

    

    public function change_default_venue(request $request){
         try{
                $input = $request->all(); 
                $checkuserid = parent::checkKeyExist("userid",$input);
                if(!empty($checkuserid)){
                $err_msg = $checkuserid;
                return parent :: api_response([], false, $err_msg, 200);
                }

                $checkvenue_id = parent::checkKeyExist("venue_id",$input);
                if(!empty($checkvenue_id)){
                $err_msg = $checkvenue_id;
                return parent :: api_response([], false, $err_msg, 200);
                }
           $validator  = Validator :: make($input,[
                     'userid'         => "required",  
                     'venue_id'       => "required"  
                    ]);


                 if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{

                        $update = DB::table("users")->where("id","=",$input["userid"])->update(["default_venue"=>$input["venue_id"]]);

                        $res_msg = isset($csvData['success']) ? $csvData['success'] : "default venue updated successfully.";
                       
                    return parent :: api_response(["venue_id"=>$input["venue_id"]],true, $res_msg, '200');


                     }   
                   }
                    catch(\Exception $e){
                     $res_msg = $e->getMessage();
                     return parent :: api_response([],false,$res_msg, 200);  
        }       

    }

    public function dashboard_partner(request $request){
        try{

          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }


           $input = $request->all(); 

                $checkuserid = parent::checkKeyExist("userid",$input);

                if(!empty($checkuserid)){
                $err_msg = $checkuserid;
                return parent :: api_response([], false, $err_msg, 200);
                }  


                $checksearch_type = parent::checkKeyExist("search_type",$input);
                if(!empty($checksearch_type)){
                $err_msg = $checksearch_type;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                $checkvenue_id = parent::checkKeyExist("venue_id",$input);
                if(!empty($checkvenue_id)){
                $err_msg = $checkvenue_id;
                return parent :: api_response([], false, $err_msg, 200);
                } 
             $date = date("Y-m-d H:i:s");   
             $venue_id = $input["venue_id"];   
             
             $subtitle1 = (!empty($csvData['dashboard_subheading1']) ? $csvData['dashboard_subheading1'] :"Active User"  );  
             $heading1 = (!empty($csvData['dashboard_heading1']) ? $csvData['dashboard_heading1'] :"Active User in Venue."  );  
             $validator  = Validator :: make($input,[
                     'userid'         => "required", 
                     'venue_id'       => "required",  
                     "search_type"    => "required"
                    ]);


                 if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{
              $user = Helper::userDetail($input["userid"]);  
              if($user->type=="2"){
              //if in case of venue login admin
              if($user->is_venuepartner==1){     
             //get venue location
             $ven_loc = DB::table("venue as v")->select("v.name as venue_name","c.name")->leftJoin("cities as c","v.city_id","c.id")->where("v.id",$input["venue_id"])->first();
             
             $location = $ven_loc->venue_name." , ".$ven_loc->name;    
                 
                        
             $active_user = $this->active_user_venue($venue_id);         
            
             $image1   = url("public/venue_icon/user.png"); 
             $created_at1 =$date;
             $updated_at1 =$date;
             //$subtitle1 = "Active user";
             $search_type = $input["search_type"];
             //for active user   
             $rec[]= array("count"=>$active_user,"image"=>$image1,"heading"=>$heading1,"created_at"=>$created_at1,"updated_at"=>$updated_at1,"subtitle"=>$subtitle1);



             //for total booking
            
             $total_booking = $this->get_total_booking($venue_id,$search_type);

             $heading2 = (!empty($csvData['dashboard_heading2']) ? $csvData['dashboard_heading2'] :"Total Booking"  );    
             $image2   = url("public/venue_icon/ticket.png"); 
             $created_at2 =$date;
             $updated_at2 =$date;
             $subtitle2 = $location;    
             $rec[]= array("count"=>$total_booking,"image"=>$image2,"heading"=>$heading2,"created_at"=>$created_at2,"updated_at"=>$updated_at2,"subtitle"=>$subtitle2);     

                 $total_booking_revenue = $this->get_total_bookingRevenue($venue_id,$search_type);
                //for total booking revenue  
             $heading3 = (!empty($csvData['dashboard_heading3']) ? $csvData['dashboard_heading3'] :"Potential Revenue"  );  
             $image3   = url("public/venue_icon/dollar.png"); 
             $created_at3 =$date;
             $updated_at3 =$date;
             $subtitle3 = $location;    
             $rec[]= array("count"=>$total_booking_revenue,"image"=>$image3,"heading"=>$heading3,"created_at"=>$created_at3,"updated_at"=>$updated_at3,"subtitle"=>$subtitle3); 

             //for active user   
             $heading4 =(!empty($csvData['dashboard_heading4']) ? $csvData['dashboard_heading4'] :"Total User Favurites"  );   
            $fav = $this->total_favorite_venue($venue_id,$search_type);  
             $image4   = url("public/venue_icon/Star.png"); 
             $created_at4 =$date;
             $updated_at4 =$date;
             $subtitle4 = $location;    
             $rec[]= array("count"=>$fav,"image"=>$image4,"heading"=>$heading4,"created_at"=>$created_at4,"updated_at"=>$updated_at4,"subtitle"=>$subtitle4); 


             $res_msg = "Record fetched successfully.";
                     return parent :: api_response(["menu"=>$rec],true,$res_msg, 200);
          }
          else{
              $setting = DB::table("user_app_permission")->where("userid","=",$input["userid"])->first(); 

              //get venue location
             $ven_loc = DB::table("venue as v")->select("c.name")->leftJoin("cities as c","v.city_id","c.id")->first();
             
             $location = $ven_loc->name;    
                 
                        
             $active_user = $this->active_user_venue($venue_id);         
            
             $image1   = url("public/venue_icon/user.png"); 
             $created_at1 =$date;
             $updated_at1 =$date;
             //$subtitle1 = "Active user";
             $search_type = $input["search_type"];

             //for active user   
              if($setting->active_user==1){ 
             $rec[]= array("count"=>$active_user,"image"=>$image1,"heading"=>$heading1,"created_at"=>$created_at1,"updated_at"=>$updated_at1,"subtitle"=>$subtitle1);
             }


             //for total booking
             $total_booking = $this->get_total_booking($venue_id,$search_type);

             $heading2 = (!empty($csvData['dashboard_heading2']) ? $csvData['dashboard_heading2'] :"Total Booking"  );    
             $image2   = url("public/venue_icon/ticket.png"); 
             $created_at2 =$date;
             $updated_at2 =$date;
             $subtitle2 = $location;   
             if($setting->tot_booking==1){ 
             $rec[]= array("count"=>$total_booking,"image"=>$image2,"heading"=>$heading2,"created_at"=>$created_at2,"updated_at"=>$updated_at2,"subtitle"=>$subtitle2);
             }     

                 $total_booking_revenue = $this->get_total_bookingRevenue($venue_id,$search_type);
                //for total booking revenue  
             $heading3 = (!empty($csvData['dashboard_heading3']) ? $csvData['dashboard_heading3'] :"Potential Revenue"  );  
             $image3   = url("public/venue_icon/dollar.png"); 
             $created_at3 =$date;
             $updated_at3 =$date;
             $subtitle3 = $location; 
             if($setting->pot_rev==1){    
             $rec[]= array("count"=>$total_booking_revenue,"image"=>$image3,"heading"=>$heading3,"created_at"=>$created_at3,"updated_at"=>$updated_at3,"subtitle"=>$subtitle3); 
             }

             //for active user   
             $heading4 =(!empty($csvData['dashboard_heading4']) ? $csvData['dashboard_heading4'] :"Total User Favurites"  );   
            $fav = $this->total_favorite_venue($venue_id,$search_type);  
             $image4   = url("public/venue_icon/Star.png"); 
             $created_at4 =$date;
             $updated_at4 =$date;
             $subtitle4 = $location;    
             if($setting->tot_fav==1){ 
             $rec[]= array("count"=>$fav,"image"=>$image4,"heading"=>$heading4,"created_at"=>$created_at4,"updated_at"=>$updated_at4,"subtitle"=>$subtitle4); 
             }
               
              if(($user->is_applogin==1) && !empty($rec)){ 
             $res_msg = "Record fetched successfully.";
                     return parent :: api_response(["menu"=>$rec],true,$res_msg, 200);
              }
              else{
                $res_msg = "Sorry, you don’t have access to this. To fix this, ask your Amiggos Admin to provide access.";
                     return parent :: api_response(["menu"=>[]],false,$res_msg, 200);

              }       



          }  
          }
          else{
              $search_type = $input["search_type"];  
             //for current budget balance
             if($search_type==1){  
             $heading1 = "Current Budget";
             $image2   = url("public/venue_icon/ticket.png");

             $created_at2 =$date;
             $updated_at2 =$date;
             $subtitle2 = "";
             $budget= "0";
             $get_budget = DB::table("featured_product")->select("id","budget","budget_balance")->where("id","=",$input["venue_id"])->first();
            $budget= "0";
            if(!empty($get_budget->id)){ 
              $budget=$get_budget->budget_balance;
             }  
             $total_current_budget ="$".Helper::numberFormat($budget);
             $rec[]= array("count"=>$total_current_budget,"image"=>$image2,"heading"=>$heading1,"created_at"=>$created_at2,"updated_at"=>$updated_at2,"subtitle"=>$subtitle2);
             } 

             //for brand
             $heading1 = "Tagged Memories"; 
             $image2   = url("public/venue_icon/ticket.png"); 
             $created_at2 =$date;
             $updated_at2 =$date;
             $subtitle2 = "";  
             $total_memories_views = $this->total_tagged_memory($venue_id,$search_type);  
             $rec[]= array("count"=>$total_memories_views,"image"=>$image2,"heading"=>$heading1,"created_at"=>$created_at2,"updated_at"=>$updated_at2,"subtitle"=>$subtitle2);

             $heading1 = "Total Memories Views"; 
             $image2   = url("public/venue_icon/ticket.png"); 
             $created_at2 =$date;
             $updated_at2 =$date;
             $subtitle2 = "";  
             $total_tagged_memories= $this->total_memory_views($venue_id,$search_type);  
             $rec[]= array("count"=>$total_tagged_memories,"image"=>$image2,"heading"=>$heading1,"created_at"=>$created_at2,"updated_at"=>$updated_at2,"subtitle"=>$subtitle2);

             $heading1 = "Banner Clicks"; 
             $image2   = url("public/venue_icon/ticket.png"); 
             $created_at2 =$date;
             $updated_at2 =$date;
             $subtitle2 = "";  
             $total_banner_count=$this->total_banner_count($venue_id,$search_type);  
             $rec[]= array("count"=>$total_banner_count,"image"=>$image2,"heading"=>$heading1,"created_at"=>$created_at2,"updated_at"=>$updated_at2,"subtitle"=>$subtitle2);

              $res_msg = "Record fetched successfully.";
                     return parent :: api_response(["menu"=>$rec],true,$res_msg, 200);


          }         
        }
        }
        catch(\Exception $e){
                     $res_msg = $e->getMessage();
                     return parent :: api_response([],false,$res_msg, 200);  
        } 
            
    }

 
  



    public function bookingList(request $request){
       try{

            $input = $request->all();
            
            $checkuserid = parent::checkKeyExist("userid",$input);
                if(!empty($checkuserid)){
                $err_msg = $checkuserid;
                return parent :: api_response([], false, $err_msg, 200);
                }  

                 $checkvenue_id = parent::checkKeyExist("venue_id",$input);
                if(!empty($checkvenue_id)){
                $err_msg = $checkvenue_id;
                return parent :: api_response([], false, $err_msg, 200);
                }  

                $checklist_type = parent::checkKeyExist("list_type",$input);
                if(!empty($checklist_type)){
                $err_msg = $checklist_type;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                $checklist_type = parent::checkKeyExist("page_no",$input);
                if(!empty($page_no)){
                $err_msg = $checklist_type;
                return parent :: api_response([], false, $err_msg, 200);
                }  

            $validator  = Validator :: make($input,[
                     'userid'         => "required", 
                     'venue_id'       => "required",  
                     "list_type"      => "required",
                     "page_no"         => "required"  
                    ]);




                 if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{   
                     $date = date("Y-m-d"); 
                     $end_time = date("H:i:s");

                     $limit =10;
                     $page_no   = isset($input['page_no']) && !empty($input['page_no']) ? $input['page_no'] : 0;
                     $offset    = $limit * $page_no; 

                     $get_booking= DB::table("booking as b")->select("b.id","u.name","u.last_name","u.profile","b.booking_date","b.booking_time as start_time")->where("b.venue_id","=",$input["venue_id"])->leftJoin("guest as u","u.userid","b.userid");
                     if($input['list_type']==1){
                        $get_booking=$get_booking->whereNotNull("checked_in");
                     } 
                     else if($input['list_type']==2){
                        $get_booking=$get_booking->whereDate("b.booking_date",">=",$date)->whereNull("checked_in");
                     } 
                     else if($input['list_type']==3){
                        /*$get_booking=$get_booking->where("oh.is_cancelled","=",1);*/
                     }

                     $get_booking=$get_booking->where("booking_status","=","Accepted")->orderBy("b.id","desc")->offset($offset)
                    ->limit($limit)->get();
                   
                    foreach($get_booking as $key=>$val){
                        $get_booking[$key]->start_time = date("h:i:a",strtotime($val->start_time));
                        if(!empty($get_booking[$key]->profile)){
                           $get_booking[$key]->profile=url("public/uploads/user/customer/".$get_booking[$key]->profile); 
                        }
                        else{
                          $get_booking[$key]->profile=url("public/default.png");
                        }

                       /* $get_booking[$key]->end_time = date("H:i:a",strtotime($val->end_time));*/

                        $get_booking[$key]->booking_date = date("d-M",strtotime($val->booking_date));

                        $get_booking[$key]->iscancelled=false;
                         if($input['list_type']==3){
                            $get_booking[$key]->iscancelled=true;
                           }

                    } 
                      $success_msg = "Booking list fetched successfully.";
                     return parent :: api_response(["bookibg_list"=>$get_booking],true,$success_msg, 200);

                     }   

       }
       catch(\Exception $e){
                     $res_msg = $e->getMessage();
                     return parent :: api_response([],false,$res_msg, 200);  
        }  
        
    }

    public function booking_detail(request $request){
        try{
             $input = $request->all();
            
             $checkuserid = parent::checkKeyExist("userid",$input);
                if(!empty($checkuserid)){
                $err_msg = $checkuserid;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                $checkubookingid = parent::checkKeyExist("bookingid",$input);
                if(!empty($checkubookingid)){
                $err_msg = $checkubookingid;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                $checkuvenue_id = parent::checkKeyExist("venue_id",$input);
                if(!empty($checkuvenue_id)){
                $err_msg = $checkuvenue_id;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                 $validator  = Validator :: make($input,[
                     'userid'         => "required", 
                     'venue_id'       => "required", 
                     'bookingid'       => "required",  
                 ]);
                 if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{   

                       $get_det = DB::table("booking as oh")->leftJoin("venue as v","oh.venue_id","v.id")
                       ->leftJoin("guest as u","u.userid","oh.userid")->select("oh.userid","oh.booking_date","oh.booking_time","oh.total_amount","oh.instructions","v.venue_home_image","u.name","u.last_name","u.profile","v.id as venue_id","u.dob")->where("oh.id","=",$input["bookingid"])->first();
                     
                      $get_det->booked_user_name = $get_det->name." ".$get_det->last_name;
                          
                          if(!empty($get_det->profile)){
                    $get_det->booked_user_image  =url("public/uploads/user/customer")."/".$get_det->profile;    
                    }
                    else{
                    $get_det->booked_user_image =url("public/default.png");
                    }

                  $get_det->booked_date_time =date("m/d/Y",strtotime($get_det->booking_date)).",".date("h:ia",strtotime($get_det->booking_time));
                  unset($get_det->booking_date);
                  unset($get_det->booking_time);
                  if (isset($get_det->venue_home_image) && !empty($get_det->venue_home_image)){

              $club_picture = explode(',',$get_det->venue_home_image);
              //foreach ($club_picture as $k => $v) {
                //if(!empty($v)){
                $get_det->booked_venue_image = url('public/uploads/venue/home_image/'.$club_picture[0]);
                //}
              //}
            } 
            else{
               $get_det->booked_venue_image = url("public/default.png");
            }   
          //get booking name
           $get_booking_name = DB::table("venue as v")->select("mt.name")->leftJoin("menu_type as mt","v.menu_type","mt.id")->where("v.id","=",$get_det->venue_id)->first(); 

           $get_det->booking_name = $get_booking_name->name;
          //fetch total guest
          $tot_guest = DB::table("booking_invite_list")->where("booking_id","=",$input['bookingid'])->count(); 
         
          $tot_guest  = $tot_guest+1;
          $menu =array();
          $get_det->total_guest = "Guest Total: ".$tot_guest;
            $get_det->purchase_by = "Purchase by : ".$get_det->booked_user_name;
            $age="0";
                        if(!empty($get_det->dob)){
                        $bdate = date("m.d.Y",strtotime($get_det->dob));
                        $bday = new DateTime($bdate); // Your date of birth
                        $today = new Datetime(date('m.d.y'));
                        $diff = $today->diff($bday);
                        
                        $useryear = $diff->y;
                        $usermonth = $diff->m;
                        $userdate = $diff->d;
                        
                        if($usermonth>0 || $userdate>0){
                            $useryear =$useryear+1;
                         $age =$useryear;   
                        }
                        else{
                          $age =$useryear;  
                        }
                        }
            $get_det->booked_user_age  = "Age: ".$age;
            $get_det->purchase_description_heading = "Special Instructions"." : ".$get_det->instructions;
            if(empty($get_det->instructions)){
              $get_det->instructions="NA";
            }
            $get_det->purchase_description = $get_det->instructions;
            $get_det->purchage_guest_list = "Guest List";
            $get_det->booking_amount = "Booking Total:".Config::get('constants.currency_symbol').$get_det->total_amount;
            $get_det->booking_id = $input["bookingid"];
             $get_det->booking_txt = "Booking Id :".$input["bookingid"];
            $get_det->view_menu = "view_menu";
            $get_det->show_checkin_button = "false"; 
            if($tot_guest>0){
            $get_det->show_checkin_button = "true";   
            }
           //get item
            $get_item = DB::table("booking_items as bi")->select("m.id","m.name","m.description","m.price","m.menu_image","bi.qty")->leftJoin("menu_item as m","m.id","bi.item_id")->where("bi.booking_id",$input["bookingid"])->get();
            foreach($get_item as $key=>$v){
               if($v->menu_image){
                 $get_item[$key]->menu_image = url("public/uploads/my_menu/".$v->menu_image); 
               }
               else{
                 $get_item[$key]->menu_image = url("public/default.png");
               }
            }

            
            $get_det->menu = $get_item; 
                      unset($get_det->name);
                      unset($get_det->last_name);
                      unset($get_det->party_date);
                      unset($get_det->amount);
                      unset($get_det->venue_image);


                     
                       $succ_msg = "booking details fetched successfully.";
                     return parent :: api_response(["booking_detail"=>$get_det],true,$succ_msg, 200); 
                     }   
        }
        catch(\Exception $e){
                     $res_msg = $e->getMessage();
                     return parent :: api_response([],false,$res_msg, 200);  
        } 
        
    }

    public function staff_list(request $request){
      try{
        $input      = $request->all();
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
          $csvData = $lang_data['csvData'];
        }                       
                    
        $get_role = DB::table("role")->select("id","name")->where("venue_id","=",$input["venue_id"])->get(); 
       
        if(empty($get_role[0])){

          $succ_msg = isset($csvData['staff_not_found']) ? $csvData['staff_not_found'] : "No staff found.";
        return parent :: api_response(["staff_list"=>$get_role],false,$succ_msg, 200); 
        }
               
        foreach($get_role as $key=>$val){
           $i=0;
          $get_staff = DB::table("users")->select("id","first_name","last_name","profile_image","status","firebase_id","unique_timestamp")->where("role_id","=",$val->id)->whereNull("deleted_at")->where("id","!=",$input["userid"])->get();

          foreach($get_staff as $keys=>$vals){
            
            if(!empty($vals->profile_image)){
              $get_staff[$keys]->profile_image = url("public/uploads/user/partner/venue_staff/".$vals->profile_image);
            }
            else{
              $get_staff[$keys]->profile_image = url("public/default.png");
            }
            $i++;
          }

          $get_role[$key]->venue_staff_list = $get_staff;
          
          $get_role[$key]->staff_count =$i; 
          
        }
        
        $succ_msg = isset($csvData['staff_list_fetched_successfully']) ? $csvData['staff_list_fetched_successfully'] : "staff list fetched successfully.";
        return parent :: api_response(["staff_list"=>$get_role],true,$succ_msg, 200); 
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

     public function new_member(request $request){
      try{
        $input      = $request->all();
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
          $csvData = $lang_data['csvData'];
        }  

        $validator  = Validator :: make($input,[
                     'userid'         => "required", 
                     'venue_id'       => "required",
                     'type'           => "required", 
                     ]);
                 if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{   
                    
            $search = "";
            if(!empty($input["search"])){
              $search =$input["search"];
            }

          if(empty($search)){
          $get_staff = DB::table("users")->select("id","first_name","last_name","profile_image","status","firebase_id","unique_timestamp as unique_member_id")->where("venue_id","=",$input["venue_id"])->where("id","!=",$input["userid"])->where("type",$input["type"])->where('is_applogin','=',1)->whereNull("deleted_at")->get();
          }
          else{
           $get_staff = DB::table("users")->select("id","first_name","last_name","profile_image","status","firebase_id","unique_timestamp as unique_member_id")->where("venue_id","=",$input["venue_id"])->where("id","!=",$input["userid"])->where('is_applogin','=',1)->whereNull("deleted_at")->where("first_name", "LIKE","%$search%")->get(); 

          }

          if(!empty($get_staff[0]->id)){

          foreach($get_staff as $keys=>$vals){
            if(!empty($vals->profile_image)){
              $get_staff[$keys]->profile_image = url("public/uploads/user/partner/venue_staff/".$vals->profile_image);
            }
            else{
              $get_staff[$keys]->profile_image = url("public/default.png");
            }
          }
        
          if(empty($vals->firebase_id)){
            $get_staff[$keys]->firebase_id="";
          }
          //$get_staff[$keys]->venue_staff_list = $get_staff;
        
        
        $succ_msg = isset($csvData['staff_list_fetched_successfully']) ? $csvData['staff_list_fetched_successfully'] : "staff list fetched successfully.";
        return parent :: api_response(["staff_list"=>$get_staff],true,$succ_msg, 200);
        } 
        else{
          $succ_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "No record found.";
        return parent :: api_response(["staff_list"=>$get_staff],false,$succ_msg, 200);

        }
      }
    }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    
    }

    public function user_status(request $request){
      try{
        $input = $request->all();        
        $validator  = Validator::make($input,[
          'userid'   => "required", 
          'staff_id' => "required",
          'status'   => "required",
        ]);
        if($validator->fails()){
          $err_msg = $validator->errors()->first();
            return parent :: api_response([],false,$err_msg, 200);
          }else{   
            $change_sta = DB::table("users")->where("id","=",$input["staff_id"])->update(["status"=>$input["status"]]);   
            $su_msg = "Status changed successfully.";
            return parent :: api_response([],true,$su_msg, 200);
          }      
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

    public function staff_detail(request $request){
    try{

      $input = $request->all();
      $lang_data = parent ::  getLanguageValues($request);($request);
      $csvData = array();
      if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
      }
            
             $checkuserid = parent::checkKeyExist("userid",$input);
                if(!empty($checkuserid)){
                $err_msg = $checkuserid;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                $checkrole_id = parent::checkKeyExist("role_id",$input);
                if(!empty($checkrole_id)){
                $err_msg = $checkrole_id;
                return parent :: api_response([], false, $err_msg, 200);
                } 

                 $validator  = Validator :: make($input,[
                     'userid'          => "required", 
                     'role_id'         => "required",
                     
                 ]);

                if($validator->fails()){

                     $err_msg = $validator->errors()->first();
                     return parent :: api_response([],false,$err_msg, 200);
         
                     }else{   
                     
                   
                       $get_staff = DB::table("users")->select("id","first_name","last_name","profile_image","status","unique_timestamp as unique_staff_id")->where("role_id","=",$input['role_id'])->where("id","!=",$input["userid"])->get();

                       foreach($get_staff as $key=>$val){
                        if(!empty($val->profile_image)){
                            $get_staff[$key]->profile_image = url("public/uploads/user/partner/venue_staff/".$val->profile_image);
                        }
                        else{
                            $get_staff[$key]->profile_image = url("public/default.png");
                        }

                        $get_staff[$key]->is_active = $val->status;

                       }
                      

                     


                     
                      $succ_msg = isset($csvData['staff_list_fetched_successfully']) ? $csvData['staff_list_fetched_successfully'] : "staff list fetched successfully.";
                     return parent :: api_response(["staff_list"=>$get_staff],true,$succ_msg, 200); 

                     }   
        
    }
    catch(\Exception $e){
                     $res_msg = $e->getMessage();
                     return parent :: api_response([],false,$res_msg, 200);  
        }  


    }

    

    //total user in venue
    public function active_user_venue($venue_id){
        $distance = 1;
        $cnt=0;
        //get venue
        $venue = DB::table("venue")->select("latitude","longitude")->where("id","=",$venue_id)->first();
        $latitude = (!empty($venue->latitude) ? $venue->latitude :"" );
        $longitude =(!empty($venue->longitude) ? $venue->longitude :"" );
        if(!empty($latitude) && !empty($longitude)){
         $get_user = DB::table("guest")->select(DB::raw("(((acos(sin((".$latitude."*pi()/180)) * sin((`latitude`*pi()/180))+cos((".$latitude."*pi()/180)) * cos((`latitude`*pi()/180)) * cos(((".$longitude."- `longitude`)*pi()/180))))*180/pi())*60*1.1515*1.609344) as distance"));
         $get_user = $get_user->havingRaw("distance<=$distance")->whereNull("deleted_at")->where("status","=","1")->get();
         $cnt =count($get_user);
         }
         return $cnt;
    }

    public function get_total_booking($venue_id,$search_type){
    if($search_type==2){
    $first_date = date("Y-m-d", strtotime("last week monday"));
    $last_date  = date("Y-m-d", strtotime("last sunday"));
   
    }
    else if($search_type==3){
       /*$first_date = date('Y-m-d', strtotime('first day of last month'));
       $last_date =  date('Y-m-d', strtotime('last day of last month'));*/
       $first_date = date('Y-m-d', strtotime('-30 Day'));
       $last_date =  date('Y-m-d', strtotime('-1 Day'));

       
    }  

        $date = date("Y-m-d");
      $get_booking = DB::table("booking")->where("venue_id","=",$venue_id);
     if($search_type==1){
        $get_booking = $get_booking->whereDate("created_at",$date);
     } 
     else{
        $get_booking = $get_booking->whereDate('created_at',">=",$first_date)->whereDate('created_at',"<=",$last_date);
        
     } 
     $get_booking = $get_booking->where("booking_status","=","Accepted")->count("id");
    
     return $get_booking;

    }

    public function total_favorite_venue($venue_id,$search_type){
    if($search_type==2){
    $first_date = date("Y-m-d", strtotime("last week monday"));
    $last_date  = date("Y-m-d", strtotime("last sunday"));
    
    }
    else if($search_type==3){
       //$first_date = date('Y-m-d', strtotime('first day of last month'));
       //$last_date =  date('Y-m-d', strtotime('last day of last month'));
        $first_date = date('Y-m-d', strtotime('-30 Day'));
       $last_date =  date('Y-m-d', strtotime('-1 Day'));
    }    
        $date = date("Y-m-d");
      $favorite_venue = DB::table("user_favorite_venue")->where("club_id","=",$venue_id)->where("status","=",1);
     if($search_type==1){
        $favorite_venue = $favorite_venue->whereDate("created_at",$date);
     } 
     else{
        $favorite_venue = $favorite_venue->whereDate('created_at',">=",$first_date)->whereDate('created_at',"<=",$last_date);
        
     } 
     $favorite_venue = $favorite_venue->count("id");
     return $favorite_venue;

    }


    public function total_tagged_memory($venue_id,$search_type){
    if($search_type==2){
    $first_date = date("Y-m-d", strtotime("last week monday"));
    $last_date  = date("Y-m-d", strtotime("last sunday"));
    
    }
    else if($search_type==3){
       //$first_date = date('Y-m-d', strtotime('first day of last month'));
       //$last_date =  date('Y-m-d', strtotime('last day of last month'));
       $first_date = date('Y-m-d', strtotime('-30 Day'));
       $last_date =  date('Y-m-d', strtotime('-1 Day'));

    }    
        $date = date("Y-m-d");
      $approval_memory = DB::table("memory_approval")->where("featured_brand_id","=",$venue_id);
     if($search_type==1){
        $approval_memory = $approval_memory->whereDate("created_at",$date);
     } 
     else{
        $approval_memory = $approval_memory->whereDate('created_at',">=",$first_date)->whereDate('created_at',"<=",$last_date);
        
     } 
     $approval_memory = $approval_memory->count("id");
     return $approval_memory;

    }

    public function total_banner_count($venue_id,$search_type){
     if($search_type==2){
    $first_date = date("Y-m-d", strtotime("last week monday"));
    $last_date  = date("Y-m-d", strtotime("last sunday"));
    
    }
    else if($search_type==3){
       //$first_date = date('Y-m-d', strtotime('first day of last month'));
       //$last_date =  date('Y-m-d', strtotime('last day of last month'));

       $first_date = date('Y-m-d', strtotime('-30 Day'));
       $last_date =  date('Y-m-d', strtotime('-1 Day'));

    }    
        $date = date("Y-m-d");
      $approval_memory = DB::table("banner_click")->where("brand_id","=",$venue_id);
     if($search_type==1){
        $approval_memory = $approval_memory->whereDate("created_at",$date);
     } 
     else{
        $approval_memory = $approval_memory->whereDate('created_at',">=",$first_date)->whereDate('created_at',"<=",$last_date);
        
     } 
     $approval_memory = $approval_memory->count("id");
     return $approval_memory;


    }


   public function total_memory_views($venue_id,$search_type){
     if($search_type==2){
    $first_date = date("Y-m-d", strtotime("last week monday"));
    $last_date  = date("Y-m-d", strtotime("last sunday"));
    
    }
    else if($search_type==3){
       //$first_date = date('Y-m-d', strtotime('first day of last month'));
       //$last_date =  date('Y-m-d', strtotime('last day of last month'));
        $first_date = date('Y-m-d', strtotime('-30 Day'));
       $last_date =  date('Y-m-d', strtotime('-1 Day'));
    }    
        $date = date("Y-m-d");
      $approval_memory = DB::table("memory_approval as ma")->select("memory_id")->where("ma.featured_brand_id","=",$venue_id);
     if($search_type==1){
        $approval_memory = $approval_memory->whereDate("created_at",$date);
     } 
     else{
        $approval_memory = $approval_memory->whereDate('created_at',">=",$first_date)->whereDate('created_at',"<=",$last_date)->where("approval_status","=","1");
     } 
     $approval_memory = $approval_memory->get();
    
     $count = 0;
     foreach($approval_memory as $am){
      $total_view = DB::table("user_memory_views as umv")->where("memory_id","=",$am->memory_id)->count("id");
      $count += $total_view;
     }

     return $count;

   }


    public function get_total_bookingRevenue($venue_id,$search_type){
        if($search_type==2){
    $first_date = date("Y-m-d", strtotime("last week monday"));
    $last_date  = date("Y-m-d", strtotime("last sunday"));
    
    }
    else if($search_type==3){
       //$first_date = date('Y-m-d', strtotime('first day of last month'));
       //$last_date =  date('Y-m-d', strtotime('last day of last month'));
        $first_date = date('Y-m-d', strtotime('-30 Day'));
        $last_date =  date('Y-m-d', strtotime('-1 Day'));

    }    
        $date = date("Y-m-d");
      $get_booking = DB::table("booking")->where("venue_id","=",$venue_id);
     if($search_type==1){
        $get_booking = $get_booking->whereDate("created_at",$date);
     } 
     else{
        $get_booking = $get_booking->whereDate('created_at',">=",$first_date)->whereDate('created_at',"<=",$last_date);
        
     } 
     $get_booking = $get_booking->where("booking_status","=","Accepted")->sum("total_amount");

     return Helper::numberFormat($get_booking);


    }



    public function  send_notification($device_type,$device_token,$subject,$message,$userid){
       if($device_type==1){
           $notify_data = array(
                           
                            "notification_key" =>$key
                        );

                        $json_notify_data = json_encode($notify_data);
          $send_notification =   parent:: sendNotification($device_type , $device_token, $message, $subject, $notify_data);
       }
       else{
        $notificationPayload = array(
                                    "body" => $subject,
                                    "title" => $message
                                );

                                $dataPayload = array(
                                    "body" => $subject,
                                    "title" => $message,
                                    "notification_key" => $key
                                );

                                $notify_data = array(
                                    "to" => $device_token,
                                    "notification" => $notificationPayload,
                                    "data" => $dataPayload
                                );
        $to = "userapp";
        $send_notification =   parent:: fcmNotification($message, $notify_data , $to);
         $json_notify_data = json_encode($notify_data);

       DB::table('user_notification')->insert([
                            ['message' => $message, 'user_id' => $userid, 'subject' => $subject, "device_type" => $device_type, "notification_key" => $key, "data" => $json_notify_data]
                        ]);
 

       }

    }

    public function getBatchCount(Request $request){     
      $input = $request->all();      
      $validator = Validator::make($input, [
                  "userid"    => "required",
                  "user_type" => "required",
                  "venue_id" => "required",
      ]);

      if($validator->fails()){
        $err_msg = $validator->errors()->first();
        return parent :: api_response([],false,$err_msg, 200); 
      }else{
         $cnt = 0;
        $date = date("Y-m-d"); 
         $getlistowner = DB::table("booking as b")->where("b.venue_id","=",$input["venue_id"])->whereNotNull("b.checked_in")->where("b.booking_status","=","Accepted")->count();
          $getlist = DB::table("booking_invite_list as li")->leftJoin("booking as b","li.booking_id","b.id")->where("b.venue_id","=",$input["venue_id"])->whereNotNull("li.checkin_date")->where("b.booking_status","=","Accepted")->count();
           
          $upcoming = DB::table("booking as b")->where("booking_status","=","Accepted")->where("venue_id","=",$input["venue_id"])->whereDate("b.booking_date",">=",$date)->whereNull("checked_in")->count(); 
           $cancelled = DB::table("booking as b")->where("venue_id","=",$input["venue_id"])->where("booking_status","=","Accepted")->count(); 
        $checkin = $getlistowner;
        $date_memory = date("Y-m-d H:i:s");
       
       /* $my_memory =  $get_user = DB::table("memory_approval as ma")->leftJoin("user_my_stories as st","st.id","ma.memory_id")->where("st.ends_at",">=",$date_memory)->groupBy("ma.venue_id")->where("ma.approval_status","=","1")->where("ma.venue_id","!=","0")->orderBy("st.id")->count();*/
        //staff count             
        $get_staff = DB::table("users")->where("type",$input["user_type"])->where("is_venuepartner",0)->where("venue_id","=",$input["venue_id"])->whereNull("deleted_at")->where("status","=","1")->where("id","!=",$input["userid"])->count();   

        //gey approval list
            $get_memory = DB::table("memory_approval as m")->leftJoin("user_my_stories as us","m.memory_id","us.id");
             if($input['user_type']=="2"){ 
             $get_memory = $get_memory->where("m.venue_id","=",$input["venue_id"]);
             }
             else{

             $get_memory = $get_memory->where("m.featured_brand_id","=",$input["venue_id"]);
             }
             $get_memory=$get_memory->where("us.ends_at",">=",$date)->where("m.approval_status","=","0")->groupBy("m.userid")->count();        

        $data['checkin']        =$checkin;
        $data['upcoming']       =$upcoming;
        $data['cancelled']      =0;         
        $data['new_memory']      = 0; 
        $data['memory_approval'] = $get_memory;
        $data['staff_count']     = $get_staff;

        $data['new_promoters'] = 0;  
        $data['new_booking'] = $cnt;          
        return parent :: api_response(['batch_count'=>$data],true,'Success', 200); 
      }
    }

   public function checkInOld(request $request){
      $input = $request->all(); 
      $validator  = Validator :: make($input,[
        'venue_id'       => "required",   
        'user_id'       => "required",   
        'booking_code'  => "required"   
      ]);
      if($validator->fails()){

          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() ,$request);
          return parent :: api_response((object) [],false,$err_msg, 200);

        }else{
          $current_date = date("Y-m-d");
          $current_time = date("h:i:s");
          $check_code   = DB::table("party_data")->where("                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           booking_code","=",$input['booking_code'])->select("booking_code","club_id","party_start","u_id","booking_id","party_date")->get();
          $check_admin   = DB::table("users")->where("id","=",$input['user_id'])->get();
          if(!empty($check_admin[0]->status && $check_admin[0]->status!=1)){
                $res_msg = "Invalid login."; 
                return parent :: api_response((object) [],false,$res_msg, 200);
            }
            if(empty($check_code[0]->booking_code)){
                $res_msg = "Booking Code does not exist."; 
                return parent :: api_response((object)[],false,$res_msg, 200);
            }
            if(!empty($check_code[0]->booking_code)){
            $booking_user = DB::table("user")->where("userid","=",$check_code[0]->u_id)->get();   
            
             if($booking_user[0]->status!=1){
             $res_msg = "Checkin user status is not active."; 
                return parent :: api_response((object) [],false,$res_msg, 200);
            }

            if($booking_user[0]->idproof_aproved!=1){
             $res_msg = "Checkin user idproof is not approved."; 
                return parent :: api_response((object) [],false,$res_msg, 200);
            }
          } 
          if(!empty($check_code[0]->booking_code)){
            if($check_code[0]->club_id!=$input['club_id']){   
                $res_msg = "Booking Code does not exist for this venue."; 
                return parent :: api_response((object) [],false,$res_msg, 200);
            }
          }


        }  
   }

   public function checkIn(request $request){
      $input = $request->all(); 
      
      $validator  = Validator :: make($input,[
           
        'bookingid'  => "required",
        /*'checkin_user' => "required",*/  
      ]);
      if($validator->fails()){
          
       $err_msg = $validator->errors()->first();
      return parent :: api_response([],false,$err_msg, 200);
         

        }else{
          $current_date = date("Y-m-d");
          $current_time = date("h:i:s");
          
          $getlistowner = DB::table("booking as b")->select("g.userid","g.name","g.last_name","g.profile","g.unique_timestamp as unique_member_id","b.is_checkin","b.checked_in as checkin_date")->leftJoin("guest as g","g.userid","b.userid")->where("b.id","=",$input["bookingid"])->get()->toArray();
          $getlist = DB::table("booking_invite_list as b")->select("g.userid","g.name","g.last_name","g.profile","g.unique_timestamp as unique_member_id","b.is_checkin","b.checkin_date")->leftJoin("guest as g","g.userid","b.friend_id")->where("b.booking_id","=",$input["bookingid"])->get()->toArray();

           $get_list=array_merge($getlistowner,$getlist); 
          $bookings = array_column($get_list, 'checkin_date');

        array_multisort($bookings, SORT_DESC, $get_list);  

          if(empty($get_list[0])){
            $res_msg ="No guest found.";
            return parent :: api_response([],false,$res_msg, 200);
          }

           foreach($get_list as $key=>$v){
            $get_list[$key]->name = $get_list[$key]->name." ".$get_list[$key]->last_name;
            if(!empty($v->profile)){
            $get_list[$key]->profile=url("public/uploads/user/customer/".$v->profile);
            }
            else{
            $get_list[$key]->profile=url("public/default.png");
           }

           $get_list[$key]->checkedin_time=(!empty($v->checkin_date) ? date("H:i:s",strtotime($v->checkin_date)) : "" );

           $get_list[$key]->checkin_date=(!empty($v->checkin_date) ? date("m/d/Y",strtotime($v->checkin_date)) : "" );
           unset($get_list[$key]->last_name);
            

           
           unset($get_list[$key]->last_name);
         }
            $res_msg ="checkin list fetched successfully.";
            return parent :: api_response($get_list,true,$res_msg, 200);
         }  
      }
   

   public function changedefault_venue(request $request){
    try{
       
        $input = $request->all(); 
        $userid = parent::checkKeyExist("userid",$input);
        if(!empty($userid)){
            $err_msg = $userid;
            return parent :: api_response([], false, $err_msg, 200);
        }

        $venueid = parent::checkKeyExist("venueid",$input);
        if(!empty($venueid)){
            $err_msg = $venueid;
            return parent :: api_response([], false, $err_msg, 200);
        }

        

        $validator  = Validator::make($input,[
            'userid'             => "required",  
            'venueid'            => "required",
        ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200); 
        }else{
          $update = DB::table("users")->where("id","=",$input["userid"])->update(["default_venue"=>$input["venueid"]]);
          $res_msg ="Default venue changed successfully.";
          return parent :: api_response([],true,$res_msg, 200);
          
        }
      }
       catch(\Exception $e){
       $res_msg = $e->getMessage();
       return parent :: api_response([],false,$res_msg, 200);  
     }
  }

 public function code_scan5jan2020(request $request){
    try{
       
        $input = $request->all(); 
        $userid = parent::checkKeyExist("userid",$input);
        if(!empty($userid)){
            $err_msg = $userid;
            return parent :: api_response([], false, $err_msg, 200);
        }
      
        $code = parent::checkKeyExist("code",$input);
        if(!empty($code)){
            $err_msg = $code;
            return parent :: api_response([], false, $err_msg, 200);
        }

        

        $validator  = Validator::make($input,[
            'userid'             => "required",  
            'code'               => "required",
            'venue_id'           => "required",
        ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200); 
        }else
        {
           
         if(!empty($input["code"])){
            $booking_code = explode("#",$input["code"]);
        }  
          $date = date("Y-m-d");
         
          if(!empty($booking_code[1])){
          $get_booking = DB::table("booking as b")->select("b.id","b.userid","b.venue_id","b.qr_code","b.booking_amount","b.total_amount","b.booking_date","b.is_checkin","v.name as venue_name","v.venue_home_image","items.name","items.price","items.qty","b.is_scan")->leftJoin("guest as g","g.userid","b.userid")->leftJoin("venue as v","b.venue_id","v.id")->leftJoin("booking_items as items","items.booking_id","b.id")->where("b.id","=",$booking_code[1])->first();
            }
            if(empty($get_booking->id)){
              $res_msg = "Invalid Qr code.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            else{  
            if($get_booking->venue_id!=$input["venue_id"]){
            $res_msg = "This qr code is not for your venue.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            else if($get_booking->booking_date<$date){
            $res_msg = "This qr code is expired.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            else if($get_booking->booking_date>$date){
            $res_msg = "This qr code is not exist for today.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            

           $explode = explode(",",$get_booking->venue_home_image);
            if(!empty($explode)){
              foreach ($explode as $v) {
                $get_booking->venue_home_image = asset('public/uploads/venue/home_image/'.$v);
              }
            }
            else{
            $get_booking->venue_home_image = url("public/default.png");  
            }  
            
            $get_booking->qrcode  = url("public/uploads/qrcode/".$get_booking->qr_code);

            

            $date = date("Y-m-d H:i:s");
            
            if(!empty($booking_code[3]) && $booking_code[3]=="1"){
            if($get_booking->is_checkin==1){
            $res_msg = "Code is already scanned.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            $updatstatus = DB::table("booking")->where("id","=",$get_booking->id)->update(["is_scan"=>"1","checked_in"=>$date,"is_checkin"=>"1"]);
            $res_msg = "checked in successfully.";
            return parent :: api_response($get_booking,true,$res_msg, 200);
           }
           else{
            //check if already exist
            $check_if_scanned = DB::table("booking_invite_list")->select("id")->where("booking_code","=",$input["code"])->where("is_checkin","=",1)->first(); 
            if(!empty($check_if_scanned->id)){
             $res_msg = "Code is already scanned.";
            return parent :: api_response([],false,$res_msg, 200);
            }

            $updatstatus = DB::table("booking_invite_list")->where("booking_id","=",$get_booking->id)->where("friend_id","=",$booking_code[2])->update(["is_checkin"=>"1","checkin_date"=>$date]);
            $res_msg = "checked in successfully.";
            return parent :: api_response($get_booking,true,$res_msg, 200);

           }

            $res_msg = "Invalid Qr Code.";
            return parent :: api_response([],false,$res_msg, 200);
            }
          }
        }  
      
      catch (\Exception $e) {
      $data = [];

      $message = $e->getMessage();
      return parent::api_response([],false, $message, 200);
        }  
  }


  public function code_scan(request $request){
    try{
       
        $input = $request->all(); 
        $userid = parent::checkKeyExist("userid",$input);
        if(!empty($userid)){
            $err_msg = $userid;
            return parent :: api_response([], false, $err_msg, 200);
        }
      
        $code = parent::checkKeyExist("code",$input);
        if(!empty($code)){
            $err_msg = $code;
            return parent :: api_response([], false, $err_msg, 200);
        }

        

        $validator  = Validator::make($input,[
            'userid'             => "required",  
            'code'               => "required",
            'venue_id'           => "required",
        ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200); 
        }else
        {
           
         if(!empty($input["code"])){
            $booking_code = explode("#",$input["code"]);
        }  
          $date = date("Y-m-d");
         
          if(!empty($booking_code[1])){
          $get_booking = DB::table("booking as b")->select("b.id","b.userid","b.venue_id","b.qr_code","b.booking_amount","b.total_amount","b.booking_date","b.is_checkin","v.name as venue_name","v.venue_home_image","items.name","items.price","items.qty","b.is_scan")->leftJoin("guest as g","g.userid","b.userid")->leftJoin("venue as v","b.venue_id","v.id")->leftJoin("booking_items as items","items.booking_id","b.id")->where("b.id","=",$booking_code[1])->first();

             

            }
            if(empty($get_booking->id)){
              $res_msg = "Invalid Qr code.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            else{  
            if($get_booking->venue_id!=$input["venue_id"]){
            $res_msg = "This qr code is not for your venue.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            else if($get_booking->booking_date<$date){
            $res_msg = "This qr code is expired.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            else if($get_booking->booking_date>$date){
            $res_msg = "This qr code is not exist for today.";
            return parent :: api_response([],false,$res_msg, 200);
            }

            //check for venue timing
             $getvenueTimng = DB::table("venue_timing")->select("time_value")->where("venue_id","=",$input["venue_id"])->first();
               $day = date("N",strtotime($date));
               $day = $day-1;  
               $day_arr = array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
                $venue_time = json_decode($getvenueTimng->time_value,true);
                $venue_time =$venue_time[$day_arr[$day]];
                if(!empty($venue_time)){
             $explode = explode("-",$venue_time);
             $from_time  = date("H:i",strtotime($explode[0]));
             $to_time  = date("H:i",strtotime($explode[1]));
             $time = date("H:i");
             if($time<$from_time || $time>$to_time){
              $res_msg = "Venue is not open for this time.";
            return parent :: api_response([],false,$res_msg, 200);

             } 
          }    


            //
            

           $explode = explode(",",$get_booking->venue_home_image);
            if(!empty($explode)){
              foreach ($explode as $v) {
                $get_booking->venue_home_image = asset('public/uploads/venue/home_image/'.$v);
              }
            }
            else{
            $get_booking->venue_home_image = url("public/default.png");  
            }  
            
            $get_booking->qrcode  = url("public/uploads/qrcode/".$get_booking->qr_code);

            

            $date = date("Y-m-d H:i:s");
            
            if(!empty($booking_code[3]) && $booking_code[3]=="1"){
            if($get_booking->is_checkin==1){
            $res_msg = "Code is already scanned.";
            return parent :: api_response([],false,$res_msg, 200);
            }
            $updatstatus = DB::table("booking")->where("id","=",$get_booking->id)->update(["is_scan"=>"1","checked_in"=>$date,"is_checkin"=>"1"]);
            $res_msg = "checked in successfully.";
            return parent :: api_response($get_booking,true,$res_msg, 200);
           }
           else{
            //check if already exist
            $check_if_scanned = DB::table("booking_invite_list")->select("id")->where("booking_code","=",$input["code"])->where("is_checkin","=",1)->first(); 
            if(!empty($check_if_scanned->id)){
             $res_msg = "Code is already scanned.";
            return parent :: api_response([],false,$res_msg, 200);
            }

            $updatstatus = DB::table("booking_invite_list")->where("booking_id","=",$get_booking->id)->where("friend_id","=",$booking_code[2])->update(["is_checkin"=>"1","checkin_date"=>$date]);
            
            //code to send notification to owner
             /*$get_owner = DB::table("booking as b")->leftJoin("guest as g","g.userid","b.user_id")->leftJoin("venue as v","b.venue_id","v.id")->select("b.userid","g.device_type","g.device_token","v.name as venue_name")->where("b.id",$get_booking->id)->first();
             $venue_name  = $get_owner->venue_name;   
             $get_rec_guest= DB::table("guest")->select("name","last_name")->where("userid",$booking_code[2])->first();
             $name = $get_rec_guest->name." ".$get_rec_guest->last_name;   
             $subject = "Your Guest has arrived!";
             $message =$name." has just checked-in to ".$venue_name."! Say Hello!";
              $notify_data = array(
                           "notification_key" =>77 
                         );
                        $json_notify_data = json_encode($notify_data);
             if($get_owner->device_type==1){
                        
                       
                        $res_notification = Helper:: sendNotification($get_owner->device_type , $get_owner->device_token, $message, $subject , $json_notify_data,"userapp");  

                        }

                        else{
                        $notificationPayload = array(
                             "body"=>$messsage,
                             "titile"=> $subject
                            );
            
            $dataPayload = array(
                "body" => $message,
                "title"=> $subject,
                "type"      =>"1",
                "notification_key" =>77 
               
            );
           
            $notify_data = array(
                "to" => $get_owner->device_token,
                "notification"=>$notificationPayload,
                "data"=>$dataPayload
            );
                       //$json_notify_data = json_encode($notify_data); 
                       $send_notification = Helper::fcmNotification($message, $notify_data, "userapp");    

                        }
                      $insert = DB::table('user_notification')->insert([
                                
                            ['message' => $sub_notify_msg, 'user_id' => $get_owner->user_id, 'subject' => $subject, "device_type" => $get_owner->device_type, "notification_key" =>77, "data" => $json_notify_data,
                              "user_type" =>4  
                           ]
                        ]); */    

            //

            $res_msg = "checked in successfully.";
            return parent :: api_response($get_booking,true,$res_msg, 200);

           }

            $res_msg = "Invalid Qr Code.";
            return parent :: api_response([],false,$res_msg, 200);
            }
          }
        }  
      
      catch (\Exception $e) {
      $data = [];

      $message = $e->getMessage();
      return parent::api_response([],false, $message, 200);
        }  
  }   

 public function chat_setting(request $request){
 try{
   $input = $request->all(); 
      $validator  = Validator :: make($input,[
        'userid'        => "required", 
        'type'        => "required",   
        'receiveramiggosid'      => "required",   
        'message'                => "required",
          
      ]);
      if($validator->fails()){

          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() ,$request);
          return parent :: api_response((object) [],false,$err_msg, 200);

        }else{
            $key                = "8";  
         //get user detail
         if($input["type"]==1){ 
         $get_user = DB::table("guest")->select("device_type","device_id as device_token","push_notification")->where("unique_timestamp","=",$input["receiveramiggosid"])->first();

         $sender_name = DB::table("guest")->select("name as first_name","last_name","unique_timestamp")->where("userid","=",$input["userid"])->first(); 

          $to = "userapp"; 
            if($get_user->push_notification!=1){ 
              $res_msg ="";
              return parent :: api_response([],true,$res_msg, 200);
            }  

         }
         else{
          $get_user = DB::table("users")->where("unique_timestamp","=",$input["receiveramiggosid"])->first();

         $sender_name = DB::table("users")->select("first_name","last_name","unique_timestamp")->where("id","=",$input["userid"])->first();
          $to = "venueapp"; 
         }

         $device_token = $get_user->device_token; 
         $device_type  = $get_user->device_type; 
         
         $first_name   = (!empty($sender_name->first_name) ? $sender_name->first_name : "" );
         $last_name   = (!empty($last_name->last_name) ? $sender_name->last_name : "" ); 
         $sender_Name  = $first_name." ".$last_name;
         $subject      = "Chat Notification"; 
         $message      = $input["message"];
         $userid       = $input["userid"];
         

         if($device_type==1){

           $notify_data = array(
                           
                            "body" => $message,
                                    "title" => $sender_Name,
                                    "notification_key" => $key,
                                    "chat_senderid"    =>$sender_name->unique_timestamp
                        );

                        $json_notify_data = json_encode($notify_data);
          $send_notification =   Helper:: sendNotification($device_type , $device_token, $message, $subject, $json_notify_data,$to);
       }
       else{  
         
         $notificationPayload = array(
                                    "body" => $message,
                                    "title" =>$sender_Name
                                );
       
                                $dataPayload = array(
                                    "body" => $message,
                                    "title" => $sender_Name,
                                    "notification_key" => $key,
                                    "chat_senderid"    =>$sender_name->unique_timestamp
                                );
 
                                $notify_data = array(
                                    "to" => $device_token,
                                    "notification" => $notificationPayload,
                                    "data" => $dataPayload
                                );
       
        $send_notification =   Helper:: fcmNotification($message, $notify_data , $to);

        }
         $json_notify_data = json_encode($notify_data);

       DB::table('user_notification')->insert([
                            ['message' => $message, 'user_id' => $userid, 'subject' => $subject, "device_type" => $device_type, "notification_key" => $key, "data" => $json_notify_data]
                        ]);
          $res_msg ="Notification sended successfully.";
          return parent :: api_response([],true,$res_msg, 200);

        }  
  }
 catch(\Exception $e){
       $res_msg = $e->getMessage();
       return parent :: api_response([],false,$res_msg, 200);  
     } 
}

 public function delete_memory(request $request){
  try
  {
    $input = $request->all();
    $validator  = Validator::make($input,[
            'userid'             => "required",  
            'memory_id'               => "required",
        ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200); 
        }else
        {
           $type="1";
           if(isset($input["type"])){
              $type =$input["type"];
           }

           if($type=="1"){

           $get_memory = DB::table("user_my_stories")->select("id","memory_type")->where("id","=",$input["memory_id"])->first();
           if(!empty($get_memory->id)){
            $delete_memory = DB::table("user_my_stories")->where("id","=",$input["memory_id"])->delete();
            $delete_memory_appr = DB::table("memory_approval")->where("memory_id","=",$input["memory_id"])->delete();
            if($get_memory->memory_type=="2"){
              $delete_memory_appr = DB::table("our_stories_files")->where("our_story_id","=",$input["memory_id"])->delete();
            } 
            $res_msg ="Memory deleted successfully.";
              return parent :: api_response([],true,$res_msg, 200);
           
           }
           else{
              $res_msg ="No memory found";
              return parent :: api_response([],false,$res_msg, 200);
           }
         }
         else{

           $delete_memory_appr = DB::table("our_stories_files")->where("id","=",$input["memory_id"])->delete();
           $res_msg ="Memory deleted successfully.";
              return parent :: api_response([],true,$res_msg, 200);
         }

        }
  }
  catch(\Exception $e){
       $res_msg = $e->getMessage();
       return parent :: api_response([],false,$res_msg, 200);  
  }

}

 public function logout(Request $request) {
      $input = $request->all();
        $validator = Validator::make($input, [
            'userid' => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent :: getErrorMsg($validator->errors()->toArray(), $request);
            return parent :: api_response((object) [], false, $err_msg, 200);
        } else {
          $input = $request->all();
          $user_id = $input['userid'];
          $check_user = DB::table('users')->where('id', '=', $user_id)->get();
          if (!empty($check_user)) {
              $update_data = DB::table('users')->where('id', '=', $user_id)->update(array('device_token' => ""));
              $res_msg = isset($csvData['logout_seccess']) ? $csvData['logout_seccess'] : "Logout success.";
              return parent::api_response([], true, $res_msg, 200);
          } else {
              $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";
              return parent :: api_response([], true, $res_msg, 200);
          }
      }
    }

    public function banner_click(request $request){
       try
   {
     $input = $request->all(); 
        $validator  = Validator :: make($input,[
                     'userid'         => "required",  
                     'brand_id'       => "required",
                     'usertype'       => "required",
                     'memory_id'       => "required"
                    ]);


                 if($validator->fails()){
                    $err_msg = $validator->errors()->first();
                    return parent :: api_response([],false,$err_msg, 200);

                 }
                 else{
                  $insert_click = DB::table("banner_click")->insert(["brand_id"=>$input['brand_id'],"userid"=>$input['userid'],"usertype"=>$input["usertype"],"memory_id"=>$input["memory_id"]]);
                  $message = "";
    return parent::api_response([], true, $message, 200);

                 } 
    }
   catch (\Exception $e) {
    $message = $e->getMessage();
    return parent::api_response([], true, $message, 200);
   }  


    }

    public function getmybudget(request $request){
      try{
        $input = $request->all(); 
        $validator  = Validator::make($input,[
                     'userid'    => "required",  
                     'brand_id'  => "required", 
                     'type'  => "required",              
                    ]);

        if($validator->fails()){
          $err_msg = $validator->errors()->first();
          return parent :: api_response([],false,$err_msg, 200);
        }else{
          $lang_data = parent :: getLanguageValues($request); 
          $csvData = array();
         
         $get_budget = DB::table("featured_product")->select("id","budget","budget_balance")->where("id","=",$input["brand_id"])->first();
        
          if(!empty($get_budget->id)){ 

            $budget_balance="$".Helper::numberFormat($get_budget->budget_balance);
            $res_msg = isset($csvData['success']) ? $csvData['success'] : "Record fetched successfully.";                       
            return parent::api_response(["mybudget"=>$budget_balance],true, $res_msg, '200');
          }
          else{
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "No record found.";
            return parent :: api_response([],false, $res_msg, '200');
          }
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response([],false,$res_msg, 200);  
      }
    }

}
