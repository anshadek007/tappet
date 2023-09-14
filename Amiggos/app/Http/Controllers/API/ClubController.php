<?php
namespace App\Http\Controllers\API;
use Config;
use App\Mail\DemoEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Guest;
use App\Offer;
use Illuminate\Support\Facades\DB;
use \Exception;
use Stripe;
use Helper;
use DateTime;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PDF;
use Storage;
use App\Mail\BookingEmail;

class ClubController extends ApiController
{
    protected $uploadsFolder='public/uploads/';

    public function getVenueDetails(Request $request) {
      try{
        $lang_data = parent :: getLanguageValues($request);
        $csvData = array();
        if ( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
          $csvData = $lang_data['csvData'];
        }

        $input = $request->all();
        $validator  = Validator::make($input,[
          'club_id'   => "required"
        ]);

        if ($validator->fails()) {
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray(),$request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        } else {
          $club_data = DB::table('venue as v')
                ->select('v.id as club_id','v.name','v.teamadmin_id','v.menu_type','v.user_set_time','v.is_amigo_club','vt.time_value','vt.club_working_value','ot.time_value as ot_time_value','ot.club_working_value as ot_club_working_value','v.venue_home_image as home_image','v.address','v.agelimit','v.dress_code','v.reservation','v.go_order','v.dine_in','v.delivery','v.phone','cn.name as club_country','v.other_img','v.deliver_upto_1km','v.deliver_upto_5km','v.deliver_upto_10km','v.venue_referral','s.name as club_state','c.name as club_city','description as club_description','reservation','go_order','dine_in','delivery','mask_req','ufv.status as is_favorite','user_set_time','menu_type.name as menu_type_name','menu_type','latitude','longitude','zipcode','price_category.price_category','tax')
                ->leftJoin('venue_timing as vt', 'vt.venue_id', '=', 'v.id')
                ->leftJoin('operational_hour as ot', 'ot.venue_id', '=', 'v.id')
                ->leftJoin('countries as cn', 'cn.id', '=', 'v.country_id')
                ->leftJoin('states as s', 's.id', '=', 'v.state_id')
                ->leftJoin('cities as c', 'c.id', '=', 'v.city_id')
                ->leftJoin('menu_type', 'menu_type.id', '=', 'v.menu_type')
                ->leftJoin('price_category', 'price_category.id', '=', 'v.price_category')
                ->leftJoin('user_favorite_venue as ufv',function($join) use ($input){
                  $join->on('ufv.club_id','=','v.id');
                  $join->where('ufv.user_id','=',$input['userid']);
                })
                ->where('v.status','=',1)
                ->where('v.id','=',$input['club_id'])
                ->whereNull('v.deleted_at')
                ->first();


          $headers       =   $request->headers->all();
          $language_code = $headers['language-code'][0];
          if(!empty($club_data)){
            $club_name        = $club_data->name;
            $club_description = $club_data->club_description;
            $club_country     = $club_data->club_country;
            $club_state       = $club_data->club_state;
            $club_city        = $club_data->club_city;

            if(empty($club_data->phone)){
             /*$getphone = DB::table("users")->select("phone")->where("id","=",$club_data->teamadmin_id)->first();

             $phone = (!empty($getphone->phone) ? $getphone->phone : "8888888888" );*/
             $phone = "8888888888";
            }

            //$club_data->phone_number = $phone;

            if (($club_data->is_amigo_club==1) && (empty($club_data->time_value) || empty($club_data->club_working_value) )) {
              $res_msg = isset($csvData['Unable_to_fetch_days_and_time_plz_contact_admin']) ? $csvData['Unable_to_fetch_days_and_time_plz_contact_admin'] : "Unable to fetch date and time plz_contact_admin.";
              //return parent :: api_response((object)[],true,$res_msg, 200);
            }
            $club_picture     = array();

            if (isset($club_data->home_image) && !empty($club_data->home_image)){

              $club_picture = explode(',',$club_data->home_image);
              foreach ($club_picture as $k => $v) {
                if(!empty($v)){
                $club_picture[$k] = asset($this->uploadsFolder.'/venue/home_image/'.$v);
                }
              }
            }
            else{
               $club_picture     = array(url("public/default.png"));
            }

            $club_data->mask_image ="";
            if(!empty($club_data->other_img)){
              $club_data->mask_image = url("public/uploads/venue/other_image/".$club_data->other_img);
            }

            //if(!empty($club_picture)){
            $club_data->home_image = $club_picture;
            //}
            //else{
            //$club_data->home_image = url("public/uploads/default.png");
            //}

            $working_days = explode(',',$club_data->club_working_value);
            $working_time = json_decode($club_data->time_value,true);
            $weekdays     = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday","Sunday");
            $days         = array();
            foreach ($weekdays as $key => $value) {
              if($value!=""){
                if (in_array($key+1,$working_days) ) {
                  $days[] = array("name"=>$weekdays[$key],"is_open"=>1,'timing'=>$working_time[$weekdays[$key]]);
                }else{
                  $days[] = array("name"=>$weekdays[$key],"is_open"=>0,'timing'=>$working_time[$weekdays[$key]]);
                }
              }
            }


            $ot_working_days = explode(',',$club_data->ot_club_working_value);
            $working_time = json_decode($club_data->ot_time_value,true);
            $weekdays     = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday","Sunday");
            $ot_days         = array();
            foreach ($weekdays as $key => $value) {
              if($value!=""){
                if (in_array($key+1,$ot_working_days) ) {
                   $ot_days[] = array("name"=>$weekdays[$key],"is_open"=>1,'timing'=>$working_time[$weekdays[$key]]);
                }else{
                   $ot_days[] = array("name"=>$weekdays[$key],"is_open"=>0,'timing'=>$working_time[$weekdays[$key]]);
                }
              }
            }


            if((empty($club_data->agelimit) || $club_data->agelimit == "None")){
              $club_data->agelimit = "";
            }

             if(empty($club_data->club_country)){
              $club_data->club_country = "";
            }

             if(empty($club_data->dress_code)){
              $club_data->dress_code = "";
            }

            if(empty($club_data->club_working_value)){
              $club_data->club_working_value = "";
            }

             if(empty($club_data->other_img)){
              $club_data->other_img = "";
            }

            if(empty($club_data->club_description)){
              $club_data->club_description = "";
            }
            if(empty($club_data->menu_type_name)){
              $club_data->menu_type_name = "";
            }

            if(empty($club_data->menu_type_name)){
              $club_data->menu_type_name = "";
            }

            if(empty($club_data->price_category)){
              $club_data->price_category = "";
            }



            $club_data->working_days = $days;
            $club_data->operation_working_days = $ot_days;

            unset($club_data->time_value);
            if($club_data->is_favorite==''){
              $club_data->is_favorite = 0;
            }
            $club_data->dateonly = "0";
            if($club_data->menu_type=="1"){
              $club_data->dateonly = "1";
            }

            $club_data->isclock   = "0";
            $club_data->timeslot  = "0";
            if($club_data->user_set_time=="1"){
              $club_data->isclock = "1";
            }
            else{
              $club_data->timeslot = "1";
            }

            if(!empty($club_data->menu_type) && $club_data->menu_type=="1"){
              $club_data->isclock   = "0";
              $club_data->timeslot  = "0";
            }

            if(empty($club_data->menu_type)){
              $club_data->menu_type = "";
            }

            $club_data->is_booking_available= "0";
            if($club_data->reservation==1 || $club_data->go_order==1){
              $club_data->is_booking_available= "1";
              $club_data->is_amigo_club = "1";
            }elseif($club_data->dine_in==1 || $club_data->delivery==1) {
              $club_data->is_booking_available= "1";
            }
            // if(empty($club_data->teamadmin_id)){
            //    $club_data->is_booking_available= "0";
            // }
            $admin_fees = DB::table("setting")->where("key","booking_fees")->first();
            $data["club_data"] = $club_data;
            $data['admin_fees'] = $admin_fees->value;
            $res_msg = isset($csvData['Club_details_fetched_successfully']) ? $csvData['Club_details_fetched_successfully'] : "";
            return parent :: api_response($data,true,$res_msg, 200);
          } else {
            $data["club_data"] = [];
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
            return parent :: api_response($data,false,$res_msg, 200);
          }
        }
      }
      catch(\Exception $e){
        $data["club_data"] = [];
        $res_msg = $e->getMessage();;
        return parent :: api_response($data,false,$res_msg, 200);
      }
    }

    public function getOfferDetails(Request $request) {
      $input = $request->all();
      // dd($input);
      $validator  = Validator::make($input,[
        'club_id'   => "required"
      ]);

      if ($validator->fails()) {
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray(),$request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      } else {
        $venue_id = $input['club_id'];
        $fetch_record = DB::table('offers')->select('offer_id','offer_title','offer_image','offer_description','offer_code','offer_status','club_id','discount_type','discount_value',
        'offer_valid_from','offer_valid_till')->WHERE('club_id',$venue_id)->WHERE('offer_status','1')->whereNull("deleted_at")->get()->toArray();
      }
      // dd($fetch_record);
      foreach ($fetch_record as $key => $v) {
        if(!empty($v)){
          $fetch_record[$key]->offer_image = asset($this->uploadsFolder.'/offers/'.$v->offer_image);
        }
      }
      $response = array();
      if (count($fetch_record) > 0) {
        $message = 'Birth Offer found successfully';
      } else {
        $message = "No data found.";
      }

      $response["result"] = !empty($fetch_record) ? $fetch_record : array();
      $response["message"] = $message;
      $response["status"] = true;
      return response()->json($response, 200);
    }

    public function getbookingSlots(Request $request) {

      $input = $request->all();
      $validator  = Validator::make($input,[
        'club_id'   => "required",
        'booking_date'   => "required"
      ]);

      if ($validator->fails()) {
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray(),$request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      } else {
        $venue_id = $input['club_id'];
        $date = $input['booking_date'];
          $fetch_record = DB::table('venue_timing')
                                  ->WHERE('venue_id',$venue_id)
                                  ->first();
          if($fetch_record){
            $bookingDateDay = Carbon::parse($input['booking_date'])->format('l');
            if($fetch_record->time_value){
              if($fetch_record->time_interval){
                $weekDaysTime = json_decode($fetch_record->time_value);
                $weekDaysArray = (array)$weekDaysTime;
                $arrayKeyValue = $weekDaysArray[$bookingDateDay];
                if($arrayKeyValue != '-'){
                  $timeArray = (explode('-',$arrayKeyValue));
                  $startTime = date("H:i:s", strtotime($timeArray[0]));
                  $endTime = date("H:i:s", strtotime($timeArray[1]));
                  $i=0;

                  $all_time_slots = array();
                  while(strtotime($startTime) <= strtotime($endTime)){
                      $start = $startTime;
                      $end = date('H:i:s',strtotime('+'.$fetch_record->time_interval.' minutes',strtotime($startTime)));
                      $startTime = date('H:i:s',strtotime('+'.$fetch_record->time_interval.' minutes',strtotime($startTime)));
                      $i++;
                      if(strtotime($startTime) <= strtotime($endTime)){
                          $time = [];
                          $time['slot_start_time'] = $start;
                          $time['slot_end_time'] = $end;
                          $already_booked_slot = DB::table('venue_booking_slots')
                                              ->WHERE('date',$input['booking_date'])
                                              ->WHERE('time',$start)
                                              ->first();
                                              // dd($already_booked_slot);
                          if (!empty($already_booked_slot)) {
                            $time['total_available_party'] = $already_booked_slot->total_members;
                          }else {
                            $time['total_available_party'] = $fetch_record->party_per_slot;
                          }
                          $time['date'] = $date;
                          $all_time_slots[] = $time;
                      }
                  }
                  $message = "Slot list.";
                }else{
                  $message = "Slot not fount on this day.";
                }
              }else{
                $message = "Slot not fount on this day.";
              }
            }else{
              $message = "Slot not fount on this day.";
            }
          }
          else{
            $message = "No data found.";
          }
        $response = array();
        // $time = array();
        // dd($time);
        if ((isset($time) && count($time)) > 0) {
            $message = 'Booking Slots Status';
        } else {
            $message = "No data found.";
        }

        $response["result"] = !empty($all_time_slots) ? $all_time_slots : array();
        // $response["result"] = (isset($all_time_slots) && !empty($all_time_slots)) ? ['time_slot' => $all_time_slots] : array();
        $response["message"] = $message;
        $response["status"] = true;
        return response()->json($response, 200);
        }
    }

    public function get_timeSlot(request $request){
      try{
        $input = $request->all();
        $validator  = Validator::make($input,[
          'userid'   => "required",
          'club_id'   => "required",
          'date'      => "required"
        ]);

        if ($validator->fails()) {
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray(),$request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        } else {

          $time_slot = array();

          $get_time = DB::table("menu_item")->where("venue_id","=",$input["club_id"])->whereDate('to_date','>=',$input['date'])->whereNull("deleted_at")->get();

          $current_date = date("Y-m-d");
          $current_time = date("H:i:s");


          if(!empty($get_time[0]->id)){
          foreach($get_time as $value){
           $to_time = date("H:i:s",strtotime($value->to_time));


          if($current_date!=$input['date']){

          $from_time  = date("h:ia",strtotime($value->from_time));
          $to_time  = date("h:ia",strtotime($value->to_time));
          $timeslot = $from_time."-".$to_time;
          $time_slot[]=$timeslot;
          }
          else{
          if($current_time<$to_time){
            $from_time  = date("h:ia",strtotime($value->from_time));
          $to_time  = date("h:ia",strtotime($value->to_time));
          $timeslot = $from_time."-".$to_time;
          $time_slot[]=$timeslot;



          }


          }
          }
         if(!empty($time_slot)){
         $succ_msg = "time slot fetched successfylly.";
          return parent :: api_response(["timeSlot"=>$time_slot],true,$succ_msg, 200);
         }
         else{
         $succ_msg = "No time slot found.";
          return parent :: api_response(["timeSlot"=>$time_slot],false,$succ_msg, 200);

         }
        }
        else{
          $getvenueTimng = DB::table("venue_timing")->select("time_value")->where("venue_id","=",$input["club_id"])->first();

            //get day
             $get_time = DB::table("menu_item")->where("venue_id","=",$input["club_id"])->where("offer_type","=","2")->whereNull("deleted_at")->get();


             if(!empty($get_time[0]->id)){
             foreach($get_time as $value){
             $day = date("N",strtotime($input["date"]));

             $package_day = explode(",",$value->standard_package_day);
              if(in_array($day,$package_day)){


              $day = $day-1;
              $day_arr = array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");

              $venue_time = json_decode($getvenueTimng->time_value,true);


                $venue_time =$venue_time[$day_arr[$day]];
          if(!empty($venue_time)){

            //$to_time = date("H:i:s",strtotime($explode[1]));


          if($current_date!=$input['date']){

             $explode = explode("-",$venue_time);
             $from_time  = date("h:ia",strtotime($explode[0]));
             $to_time  = date("h:ia",strtotime($explode[1]));
             $venue_time = $from_time."-".$to_time;
           }
           else{
            $explode = explode("-",$venue_time);
            $to_time = date("H:i:s",strtotime($explode[1]));
             if($current_time<$to_time){

             $from_time  = date("h:ia",strtotime($explode[0]));
             $to_time  = date("h:ia",strtotime($explode[1]));
             $venue_time = $from_time."-".$to_time;
            }

           }
          }
          }
          }
        }
         if(!empty($venue_time)){
         $time_slot[]=$venue_time;
         }
         else{
          $succ_msg = "No timeslot found.";
          return parent :: api_response(["timeSlot"=>$time_slot],false,$succ_msg, 200);

         }
         $succ_msg = "time slot fethed successfylly.";
          return parent :: api_response(["timeSlot"=>$time_slot],true,$succ_msg, 200);

        }


        }
      }
      catch(\Exception $e){
        $data["club_data"] = [];
        $res_msg = $e->getMessage();;
        return parent :: api_response($data,false,$res_msg, 200);
      }

    }

    public function create_favoriteVenue(request $request){
      try{
        $input = $request->all();
        $validator  = Validator::make($input,[
          'userid'   => "required",
          'club_id'  => "required"
        ]);

        if ($validator->fails()) {
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }
        else{
          $data_array = array(
                          "user_id" => $input["userid"],
                          "club_id" => $input["club_id"],
                          "status"  => "1"
                        );

          $check_fav = DB::table("user_favorite_venue")->select("id")->where($data_array)->first();
          if(!empty($check_fav->id)){
             $update_fav = DB::table("user_favorite_venue")->where("user_id","=",$input["userid"])->where("club_id","=",$input["club_id"])->delete();
             $data = [];
             $res_msg = "You have removed this venue from your favorite venue.";
             return parent :: api_response((object)$data,false,$res_msg, 200);
          }
          $insert_fav = DB::table("user_favorite_venue")->insert($data_array);
          $data       = [];
          //code to send notification
              $get_venue_det = DB::table("venue as v")->select("u.id","u.device_type","u.device_token")->leftJoin("users as u","u.id","v.teamadmin_id")->where("v.id","=",$input["club_id"])->first();
               $guest_det = dB::table("guest")->select("name","last_name")->where("userid","=",$input["userid"])->first();
             if(!empty($get_venue_det)){

                        $subject =isset($csvData['favorite_venue_subject']) ? $csvData['favorite_venue_subject'] : "Someone Loves You!";
                        $name =$guest_det->name." ".$guest_det->last_name;
                        $message= "@#$# has added you to their Favorite Venues";
                        $sub_notify_msg =isset($csvData['favorite_venue_message']) ? $csvData['favorite_venue_message'] :$message;

                         $sub_notify_msg = str_replace("@#$#",$name,$sub_notify_msg);

                         $notify_data = array(
                          "userid" =>$get_venue_det->id,

                          "notification_key" =>484
                         );
                        $json_notify_data = json_encode($notify_data);
                        if($get_venue_det->device_type==1){

                        $res_notification = Helper:: sendNotification($get_venue_det->device_type , $get_venue_det->device_token, $sub_notify_msg, $subject , $json_notify_data,"venueapp");

                        }

                        else{
                        $notificationPayload = array(
                             "body"=>$sub_notify_msg,
                             "titile"=> $subject
                            );

            $dataPayload = array(
                "body" => $sub_notify_msg,
                "title"=> $subject,
                "userid" =>$get_venue_det->id,
                "notification_key" =>484

            );

            $notify_data = array(
                "to" => $get_venue_det->device_token,
                "notification"=>$notificationPayload,
                "data"=>$dataPayload
            );
                       //$json_notify_data = json_encode($notify_data);
                       $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "venueapp");

                        }

                        $insert = DB::table('user_notification')->insert([

                            ['message' => $sub_notify_msg, 'user_id' => $get_venue_det->id, 'subject' => $subject, "device_type" => $get_venue_det->device_type, "notification_key" =>2, "data" => $json_notify_data,
                              "user_type" =>2
                           ]
                        ]);

             }
          $res_msg    = "successfully added to your favorite venue.";
          return parent :: api_response((object)$data,true,$res_msg, 200);
        }
      }
      catch(\Exception $e){
        $data = [];
        $res_msg = $e->getMessage();;
        return parent :: api_response((object)$data,false,$res_msg, 200);
      }
    }

    public function create_favoriteVenueold(request $request){
      try{
        $input = $request->all();
        $validator  = Validator::make($input,[
          'userid'   => "required",
          'club_id'  => "required"
        ]);

        if ($validator->fails()) {
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }
        else{
          $data_array = array(
                          "user_id" => $input["userid"],
                          "club_id" => $input["club_id"],
                          "status"  => "1"
                        );

          $check_fav = DB::table("user_favorite_venue")->select("id")->where($data_array)->first();
          if(!empty($check_fav->id)){
             $update_fav = DB::table("user_favorite_venue")->where("user_id","=",$input["userid"])->where("club_id","=",$input["club_id"])->delete();
             $data = [];
             $res_msg = "You have removed this venue from your favorite venue.";
             return parent :: api_response((object)$data,false,$res_msg, 200);
          }
          $insert_fav = DB::table("user_favorite_venue")->insert($data_array);
          $data       = [];
          $res_msg    = "successfully added to your favorite venue.";
          return parent :: api_response((object)$data,true,$res_msg, 200);
        }
      }
      catch(\Exception $e){
        $data = [];
        $res_msg = $e->getMessage();;
        return parent :: api_response((object)$data,false,$res_msg, 200);
      }
    }

    public function getMenu(Request $request){
      //try{
      $input      = $request->all();
      $validator  = Validator::make($input,[
                      'userid'=>'required',
                      'venue_id'=>'required',
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
          $csvData = $lang_data['csvData'];
        }

        $current_date = date("Y-m-d");
        $date   = $input['date'];
        $time       = $input['time'];
        $format_time= date("H:i:s",strtotime($time));
        if($date && $date<$current_date){
           return parent::api_response((object)[],false,"Date should not be less than current date.", 200);
        }

        $request_timestamp = $date.' '.$time;

        // $method = $input['method'];
        // if($method=="Dine-in"){
        //   $method="Dine in";
        // }
        // else if($method=="To-go"){
        //   $method="Togo";
        // }
        // else{
        //   $method="Both";
        // }

        $venue_id = $input['venue_id'];
        $section = DB::table('menu_section')->select('id','name')->WHERE('venue_id',$venue_id)->whereNull("deleted_at")->get()->toArray();
        if(!empty($section)){
          foreach ($section as $k => $vv) {
            $section_id = $vv->id;
            $menu = DB::table('menu_item')->select('id','name','description','price','cost','is_guest_invities','age_restriction','offer_type','method','menu_image','from_time','to_time','standard_package_day','from_date','to_date')
                  ->where([['section_id',$section_id],['status',1]]);
                  //if($date!=''){

                   /* $menu = $menu->whereDate("from_date","<=",$date)->whereDate("to_date",">=",$date);*/

                   $menu = $menu->where(
                   function($menu) use ($date) {
                     if($date){
                       $menu = $menu->whereDate("from_date","<=",$date)->whereDate("to_date",">=",$date)->orWhere("offer_type","=",2);
                     }else{
                      $menu = $menu->orWhere("offer_type","=",2);
                     }
                   }

                   );

                  //}
                  // if($method!=''){
                  //   /*$menu = $menu->where(DB::raw("method = '$method' or method ='Both'"));*/
                  //   $menu = $menu->where(
                  //           function($menu) use($method){
                  //         $menu =  $menu->where('method', '=',$method)->orWhere('method', '=','Both');
                  //           }
                  //           );
                  // }
                  /*$menu = $menu->where("method","=",$method)->whereNull('deleted_at')->get()->toArray();*/
               $menu = $menu->whereNull('deleted_at')->get()->toArray();
                  // dd($menu);
            //print_r($menu);die;
            $guest = DB::table("guest")->select("id_proof","idproof_aproved")->where("userid","=",$input["userid"])->first();
           $is_idproof_uploaded = "0";
           $is_idproof_verified = "0";
           $is_idproof_upload_message  = isset($csvData['is_idproof_upload_message']) ? $csvData['Invalid_userid'] : "To buy this product must upload your id.";
           $is_idproof_verified_message = isset($csvData['is_idproof_verified_message']) ? $csvData['is_idproof_verified_message'] : "Your id proof has not been verified.";
           if(!empty($guest->id_proof)){
           $is_idproof_uploaded = "1";
           }
           if($guest->idproof_aproved=="1"){
           $is_idproof_verified  = "1";
           }



            if(!empty($menu)){
              foreach ($menu as $key => $v) {
                if (!empty($v->menu_image)) {
                  $menu[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                }
                if($v->age_restriction=="None"){
                  $menu[$key]->age_restriction="";
                }
                $menu[$key]->venue_id  = $input["venue_id"];
                $menu[$key]->is_idproof_uploaded=$is_idproof_uploaded;
                $menu[$key]->is_idproof_verified =$is_idproof_verified;
                $menu[$key]->is_idproof_notverified_message=$is_idproof_verified_message;
                $menu[$key]->is_idproof_upload_message=$is_idproof_upload_message;
                $menu[$key]->warning_age_restriction="You need to be above 18 year of age for this product?";
                if(!empty($time)){
                  $count = substr_count($time, '-');



                  if($v->offer_type!=2){

                  if($count<1){
                 $time_menu = date("H:i:s",strtotime($v->to_time));
                 $fromtime_menu = date("H:i:s",strtotime($v->from_time));
                  $d1 = new DateTime($v->from_date." ".$fromtime_menu);
                  if($date){
                  $d2 = new DateTime($v->to_date." ".$time_menu);
                  $usertime  = new DateTime($date." ".$format_time);
                  if($usertime<$d1 || $usertime>$d2){
                   unset($menu[$key]);

                  }
                }
                    }
                    else{
                       $exp = explode('-',$time);
                       $current_time = date("H:i:s");
                       $tdate = date("Y-m-d");
                       $fromtime_menu = date("H:i:s",strtotime($exp[0]));
                       $time_menu = date("H:i:s",strtotime($exp[1]));
                       if($date){
                      if($tdate==$date){

                      if($current_time>$time_menu){

                      unset($menu[$key]);
                     }
                   }
                  }
                    }
                    }
                    else{


                      $getvenueTimng = DB::table("venue_timing")->select("time_value")->where("venue_id","=",$input["venue_id"])->first();


                if(!empty($v->standard_package_day)){
                $package_day = explode(",",$v->standard_package_day);

                $day = date("N",strtotime($input["date"]));

              if(in_array($day,$package_day)){

              $day = date("N",strtotime($input["date"]));
              $day = $day-1;
              $day_arr = array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
              $venue_time = json_decode($getvenueTimng->time_value,true);

                $venue_time =$venue_time[$day_arr[$day]];

                $explode = explode("-",$venue_time);

               //if($count<1){
                $ctime = date("H:i:s",strtotime($time));
                //}
                //else{
                 //$ctime = date("H:i:s");
                //}


                $fromtime_menu = date("H:i:s",strtotime($explode[0]));
                $time_menu = date("H:i:s",strtotime($explode[1]));
                $tdate = date("Y-m-d");

                     if($tdate==$date){
                      if($count<1){

                     if($ctime>$time_menu){

                      unset($menu[$key]);
                     }


                     if($format_time<$fromtime_menu || $format_time>$time_menu){

                      unset($menu[$key]);
                     }
                   }
                   else{

                   $ctime  = date("H:i:s");

                    if($ctime>$time_menu){

                      unset($menu[$key]);
                     }

                   }

                   }
                   else{
                      if($count<1){
                    if($format_time<$fromtime_menu || $format_time>$time_menu){

                      unset($menu[$key]);
                     }
                   }

                   }
              }
              else{

                 unset($menu[$key]);
              }
              }
              else{

               unset($menu[$key]);
              }
              }
                   }
                   else{

                $package_day = explode(",",$v->standard_package_day);

                $day = date("N",strtotime($input["date"]));

              if(!in_array($day,$package_day)){
                  if($v->offer_type==2){
                 unset($menu[$key]);
               }

              }


                   }

              }
              $section[$k]->menu = array_values($menu);
            }else{
              $section[$k]->menu = array();

            }
          }
          $data['section'] = $section;
          $data['tax']     = array("key"=>"Fees and Estimated Tax","currency"=>"$","value"=>"7.5");

          return parent::api_response($data,true,"success", 200);
        }else{
          return parent::api_response((object)[],false,"Menu is not available", 200);
        }
      }
      /*}
      catch(\Exception $e){
        $data = [];
        $res_msg = $e->getMessage();;
        return parent :: api_response((object)$data,false,$res_msg, 200);
      }*/
    }

    public function getMenuOld(Request $request){
      try{
      $input      = $request->all();
      $validator  = Validator::make($input,[
                      'userid'=>'required',
                      'venue_id'=>'required',
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
          $csvData = $lang_data['csvData'];
        }

        $current_date = date("Y-m-d");
        $date   = $input['date'];
        $time   = $input['time'];
        if($date<$current_date){
           return parent::api_response((object)[],false,"Date should not be less than current date.", 200);
        }

        $request_timestamp = $date.' '.$time;

        $method = $input['method'];
        if($method=="Dine-in"){
          $method="Dine in";
        }
        else if($method=="To-go"){
          $method="Togo";
        }
        else{
          $method="Both";
        }

        $venue_id = $input['venue_id'];
        $section = DB::table('menu_section')->select('id','name')->WHERE('venue_id',$venue_id)->whereNull("deleted_at")->get()->toArray();
        if(!empty($section)){
          foreach ($section as $k => $vv) {
            $section_id = $vv->id;
            $menu = DB::table('menu_item')->select('id','name','description','price','cost','is_guest_invities','age_restriction','offer_type','method','menu_image','from_time','to_time','standard_package_day')
                  ->where([['section_id',$section_id],['status',1]]);
                  //if($date!=''){

                   /* $menu = $menu->whereDate("from_date","<=",$date)->whereDate("to_date",">=",$date);*/

                   $menu = $menu->where(
                   function($menu) use ($date) {
                   $menu = $menu->whereDate("from_date","<=",$date)->whereDate("to_date",">=",$date)->orWhere("offer_type","=",2);
                   }

                   );

                  //}
                  if($method!=''){
                    /*$menu = $menu->where(DB::raw("method = '$method' or method ='Both'"));*/
                    $menu = $menu->where(
                            function($menu) use($method){
                          $menu =  $menu->where('method', '=',$method)->orWhere('method', '=','Both');
                            }
                            );
                  }
                  /*$menu = $menu->where("method","=",$method)->whereNull('deleted_at')->get()->toArray();*/
               $menu = $menu->whereNull('deleted_at')->get()->toArray();

            //print_r($menu);die;
            $guest = DB::table("guest")->select("id_proof","idproof_aproved")->where("userid","=",$input["userid"])->first();
           $is_idproof_uploaded = "0";
           $is_idproof_verified = "0";
           $is_idproof_upload_message  = isset($csvData['is_idproof_upload_message']) ? $csvData['Invalid_userid'] : "To buy this product must upload your id.";
           $is_idproof_verified_message = isset($csvData['is_idproof_verified_message']) ? $csvData['is_idproof_verified_message'] : "Your id proof has not been verified.";
           if(!empty($guest->id_proof)){
           $is_idproof_uploaded = "1";
           }
           if($guest->idproof_aproved=="1"){
           $is_idproof_verified  = "1";
           }



            //print_r($menu);die;
            if(!empty($menu)){
              foreach ($menu as $key => $v) {
                $menu[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                if($v->age_restriction=="None"){
                  $menu[$key]->age_restriction="";
                }
                $menu[$key]->venue_id  = $input["venue_id"];
                $menu[$key]->is_idproof_uploaded=$is_idproof_uploaded;
                $menu[$key]->is_idproof_verified =$is_idproof_verified;
                $menu[$key]->is_idproof_notverified_message=$is_idproof_verified_message;
                $menu[$key]->is_idproof_upload_message=$is_idproof_upload_message;
                $menu[$key]->warning_age_restriction="You need to be above 18 year of age for this product?";
                if(!empty($time)){
                  $count = substr_count($time, '-');



                  if($v->offer_type!=2){
                  if($count<1){

                   $time_menu = date("H:i:s",strtotime($v->to_time));

                  $fromtime_menu = date("H:i:s",strtotime($v->from_time));
                  $ctime = date("H:i:s",strtotime($time));
                      $tdate = date("Y-m-d");
                      if($tdate==$date){
                      $current_time = date("H:i:s");
                     if($current_time<$fromtime_menu || $current_time>$time_menu){

                      unset($menu[$key]);
                     }
                   }
                    }
                    else{

                       $exp = explode('-',$time);

                       $current_time = date("H:i:s");
                       $tdate = date("Y-m-d");
                       $fromtime_menu = date("H:i:s",strtotime($exp[0]));
                       $time_menu = date("H:i:s",strtotime($exp[1]));
                      if($tdate==$date){
                      if($current_time>$fromtime_menu){

                      unset($menu[$key]);
                     }
                   }
                    }
                    }
                    else{

                      $getvenueTimng = DB::table("venue_timing")->select("time_value")->where("venue_id","=",$input["venue_id"])->first();

                if(!empty($v->standard_package_day)){
                $package_day = explode(",",$v->standard_package_day);

                $day = date("N",strtotime($input["date"]));

              if(in_array($day,$package_day)){

              $day = date("N",strtotime($input["date"]));
              $day = $day-1;
              $day_arr = array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
              $venue_time = json_decode($getvenueTimng->time_value,true);

                $venue_time =$venue_time[$day_arr[$day]];

                $explode = explode("-",$venue_time);

                $ctime = date("H:i:s",strtotime($time));
                $fromtime_menu = date("H:i:s",strtotime($explode[0]));
                $time_menu = date("H:i:s",strtotime($explode[1]));
                $tdate = date("Y-m-d");

                     if($tdate==$date){

                     if($ctime<$fromtime_menu || $ctime>$time_menu){

                      unset($menu[$key]);
                     }
                   }
              }
              else{

                 unset($menu[$key]);
              }
              }
              }
                   }
                   else{

                $package_day = explode(",",$v->standard_package_day);

                $day = date("N",strtotime($input["date"]));

              if(!in_array($day,$package_day)){
                  if($v->offer_type==2){
                 unset($menu[$key]);
               }

              }


                   }

              }
              $section[$k]->menu = array_values($menu);
            }else{
              $section[$k]->menu = array();

            }
          }
          $data['section'] = $section;
          $data['tax']     = array("key"=>"Fees and Estimated Tax","currency"=>"$","value"=>"7.5");

          return parent::api_response($data,true,"success", 200);
        }else{
          return parent::api_response((object)[],false,"Menu is not available", 200);
        }
      }
      }
      catch(\Exception $e){
        $data = [];
        $res_msg = $e->getMessage();;
        return parent :: api_response((object)$data,false,$res_msg, 200);
      }
    }

    public function save_booking(Request $request){
      $input = $request->all();
      $validator  = Validator::make($input,[
                      'userid'       => 'required',
                      'venue_id'     => 'required',
                      'menu_item'    => 'required',
                      'sub_total'    => 'required',
                      'booking_date' => 'required',
                      /*'booking_time' => 'required',*/
                      'total_amount' => 'required',
                      'price_category' =>'required',
                      'booking_method' => 'required'
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{

        if(!isset($input['booking_time'])){
           $input['booking_time']="";
        }


        //
        //Menu Items : [{"item_id":"6","name":"Malal","price":"15","qty":"2"},{"item_id":"4","name":"Arjeect","price":"1500","qty":"4"}]
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData']) ) {
          $csvData  = $lang_data['csvData'];
        }

        //check venue status
        $venueStatus = DB::table("venue")->where("id","=",$input["venue_id"])->first();
        if($venueStatus->status!=1 || $venueStatus->deleted_at!=""){
          $res_msg = isset($csvData['venue_not_available']) ? $csvData['venue_not_available'] : 'venue not availble for booking.';
          return parent::api_response([],true,$res_msg, 200);
        }
        $tax = isset($input['tax'])?$input['tax']:0;
        $tip = isset($input['tip'])?$input['tip']:0;
        $instruction = isset($input['instruction'])?$input['instruction']:'';
        $validate_items = $this->validate_order($input);
        if($validate_items===true){

          $booking_code = $this->generateRandomBookingCode();
          $booking_data = array(
                            "venue_id"      => $input['venue_id'],
                            "userid"        => $input['userid'],
                            "booking_date"  => $input['booking_date'],
                            "booking_time"  => $input['booking_time'],
                            "booking_status"=> 'Pending',
                            "booking_amount"=> $input['sub_total'],
                            "tax_amount"    => $tax,
                            "tip_amount"    => $tip,
                            "total_amount"  => $input['total_amount'],
                            "currency"      => $input['price_category'],
                            "booking_method"=> $input['booking_method'],
                            "instructions"  => $instruction,
                            "created_at"    => date("Y-m-d H:i:s")
                           /* "booking_code"  => $booking_code*/
                          );
          DB::beginTransaction();
          //try {
              $booking_id = DB::table('booking')->insertGetId($booking_data);
              $bookingcode = $booking_code."#".$booking_id."#".$input['userid']."#1";
              $update_book = DB::table("booking")->where("id","=",$booking_id)->update(["booking_code"=>$bookingcode]);
              $booking_items = array();
              foreach($input['menu_item'] as $k =>$mi){
                //code to check inventory
                if(!empty($mi['item_id'])){

                $check_inventory =$this->check_inventory($mi['item_id']);
                if($check_inventory<$mi['qty']){
                $res_msg = isset($csvData['item_not_available']) ? $csvData['item_not_available'] : "Sorry item ".$mi['name']." is not available.";
                return parent::api_response([],false,$res_msg, 200);
                }
                }

                $booking_items[]  = array(
                            'booking_id'=>$booking_id,
                            'item_id'=>$mi['item_id'],
                            'name'=>$mi['name'],
                            'price'=>$mi['price'],
                            'qty'=>$mi['qty']
                          );
              }
              $save_items =  DB::table('booking_items')->insert($booking_items);

              $qr_data['booking_code'] = $bookingcode;
              $qr_data['booking_id']  = $booking_id;
              $qr_data['user_id']     = $input['userid'];
              $qr_image = \QrCode::format('png')
                         ->size(200)->errorCorrection('H')
                         ->generate(json_encode($qr_data));
              $b = "data:image/png;base64,".base64_encode($qr_image);
              $qr_image   = imagecreatefrompng($b);
              $image_name = "qr_".$booking_code.".png";
              imagepng($qr_image, "public/uploads/qrcode/".$image_name);

              $saveQrCode = DB::table('booking')->where('id',$booking_id)->update(['qr_code'=>$image_name]);
              DB::commit();
              $data['booking'] = array("booking_id" =>$booking_id,"amount"=>$input['total_amount']);

              //$this->reserve_inventory($input['menu_item']);


              $res_msg = "";

              return parent::api_response($data,true,$res_msg, 200);
              // all good
          /*} catch (\Exception $e) {
              DB::rollback();
              return parent::api_response((object)[],false,$e->getMessage(), 200);
          }*/
        }else{
          // print_r($validate_items);die();
          return parent::api_response((object)[],false,$validate_items, 200);
        }
      }
    }

    public function add_menu_items(Request $request){
      $input = $request->all();
      $validator  = Validator::make($input,[
                      'userid'       => 'required',
                      'menu_item'    => 'required',
                      'total_amount' => 'required',
                      'bookingid' => 'required'
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{
        $checkbooking = DB::table("booking")->where("id","=",$input["bookingid"])->first();
        if (!empty($checkbooking)) {
          $booking_items = array();
          if (!empty($input['menu_item']) && count($input['menu_item']) > 0) {
            foreach($input['menu_item'] as $k =>$mi){
              $booking_items[]  = array(
                'booking_id'=>$input['bookingid'],
                "user_id"=> $input['userid'],
                'item_id'=>$mi['item_id'],
                'name'=>$mi['name'],
                'price'=>$mi['price'],
                'qty'=>$mi['qty']
              );
            }
            $save_items =  DB::table('booking_items')->insert($booking_items);
          }
          $tax_amount = $checkbooking->tax_amount + $input['tax'];
          $tip_amount = $checkbooking->tip_amount + $input['tip'];
          $total_amount = $checkbooking->total_amount + $input['total_amount'];
          DB::table("booking")->where("id", "=", $checkbooking->id)->update(["tax_amount" => $tax_amount, "tip_amount" =>$tip_amount,"total_amount" =>$total_amount]);
          $booking_details = DB::table('booking')
                                      ->where('id',$checkbooking->id)->first();
          $venue_detail = DB::table('venue')
                                      ->where('id',$checkbooking->venue_id)->first();
          $booking_items_detail = DB::table("booking_items as bi")
                              ->select("bi.item_id","bi.user_id","bi.qty","bi.price as booking_items_price","bi.name","bi.id as booking_items_id","mi.id as menu_item_id","mi.name as menu_item_name",'mi.*')
                              ->leftJoin("menu_item as mi","bi.item_id","mi.id")
                              ->where("bi.booking_id","=",$checkbooking->id)
                              ->get();
          $data['booking_detail'] = $booking_details;
          $data['booking_items'] = $booking_items_detail;
          $data['venue_detail'] = $venue_detail;
          $res_msg = "";
          return parent::api_response($data,true,$res_msg, 200);
        }else {
          // dd('no');
          $res_msg = "Booking Not Found";
          return parent::api_response((object)[],false,$res_msg, 200);
        }
      }
    }

    public function save_booking_v1(Request $request){
      $input = $request->all();
      $validator  = Validator::make($input,[
                      'userid'       => 'required',
                      'venue_id'     => 'required',
                      // 'menu_item'    => 'required',
                      'sub_total'    => 'required',
                      'booking_date' => 'required',
                      /*'booking_time' => 'required',*/
                      'total_amount' => 'required',
                      'price_category' =>'required',
                      'booking_method' => 'required'
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{

        if(!isset($input['booking_time'])){
           $input['booking_time']="";
        }
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData']) ) {
          $csvData  = $lang_data['csvData'];
        }

        //check venue status
        $venueStatus = DB::table("venue")->where("id","=",$input["venue_id"])->first();
        if($venueStatus->status!=1 || $venueStatus->deleted_at!=""){
          $res_msg = isset($csvData['venue_not_available']) ? $csvData['venue_not_available'] : 'venue not availble for booking.';
          return parent::api_response([],true,$res_msg, 200);
        }
        $tax = isset($input['tax'])?$input['tax']:0;
        $tip = isset($input['tip'])?$input['tip']:0;
        $instruction = isset($input['instruction'])?$input['instruction']:'';
        if (!empty($input['menu_item']) && count($input['menu_item']) > 0) {
        $validate_items = $this->validate_order($input);
        if($validate_items===true){
           if(isset($input['bookingid']) && !empty($input['bookingid'])){
             $bookingid=$input['bookingid'];
           }else{
             $bookingid="";
           }
         }else{
           return parent::api_response((object)[],false,$validate_items, 200);
         }
       }else {
         if(isset($input['bookingid']) && !empty($input['bookingid'])){
           $bookingid=$input['bookingid'];
         }else{
           $bookingid="";
         }
       }
       if(isset($input['invite_user_id']) && !empty($input['invite_user_id'])){
         $invitefriendid=$input['invite_user_id'];
       }else{
         $invitefriendid="";
       }
       if(isset($input['delivery_fees']) && !empty($input['delivery_fees'])){
         $deliveryFees=$input['delivery_fees'];
       }else{
         $deliveryFees="";
       }
       if(isset($input['booking_fees']) && !empty($input['booking_fees'])){
         $bookingFees=$input['booking_fees'];
       }else{
         $bookingFees="";
       }
        if(isset($input['promocode']) && !empty($input['promocode'])){
          $promocode=$input['promocode'];
          $is_offer_applied='Yes';
          $validateoffer = DB::table("offers")->where("offer_code","=",$input["promocode"])->where("offer_status","=","1")->first();
          if(!empty($validateoffer)){
            $offer_id = $validateoffer->offer_id;
          }else{
             $offer_id="";
          }
        }else{
           $promocode="";
           $is_offer_applied='No';
           $offer_id="";
        }

        if(isset($input['booking_slot_id']) && !empty($input['booking_slot_id'])){
          $booking_slot_id=$input['booking_slot_id'];
          $updatebookingslots = DB::table("venue_booking_slots")->where("booking_slot_id","=",$booking_slot_id)->update(["status"=>2]);
        }
        else{
           $booking_slot_id="";
        }
         //for time slot
         $count = substr_count($input['booking_time'], '-');

         if($count>0){
          $explodeTime = explode("-",$input['booking_time']);
          $input['booking_time'] = date("H:i:s",strtotime($explodeTime[0]));

         }


        if(empty($bookingid)){
          $booking_code = $this->generateRandomBookingCode();
          $booking_data = array(
                            "venue_id"      => $input['venue_id'],
                            "userid"        => $input['userid'],
                            "booking_date"  => $input['booking_date'],
                            "booking_time"  => $input['booking_time'],
                            "booking_status"=> 'Pending',
                            "booking_amount"=> $input['sub_total'],
                            "tax_amount"    => $tax,
                            "tip_amount"    => $tip,
                            "total_amount"  => $input['total_amount'],
                            "currency"      => $input['price_category'],
                            "booking_method"=> $input['booking_method'],
                            "address_id"=> $input['address_id'],
                            "pay_all"=> $input['pay_all'],
                            "party_members"=> $input['party_members'],
                            "instructions"  => $instruction,
                            "offer_id"      => $offer_id,
                            "booking_slot_id"=> $booking_slot_id,
                            "is_user_invite"=> $input['is_user_invite'],
                            "created_at"    => date("Y-m-d H:i:s"),
                            "promocode"=> $promocode,
                            "is_offer_applied"=> $is_offer_applied,
                            "party_organize"=> 'true',
                            "delivery_fees"=> $deliveryFees,
                            "booking_fees"=> $bookingFees,
                           /* "booking_code"  => $booking_code*/
                          );
          $slot_data = array(
                            "venue_id"      => $input['venue_id'],
                            "time"        => $input['slot_time'],
                            "date"  => $input['booking_date'],
                            "members_booked"  => $input['party_members'],
                            "total_members"  => $input['party_members'],
                            "status"=> '1',
                            "created_at"=> date("Y-m-d H:i:s")
                          );
          DB::beginTransaction();

          //try {
              $booking_id = DB::table('booking')->insertGetId($booking_data);
              $bookingcode = $booking_code."#".$booking_id."#".$input['userid']."#1";
              $update_book = DB::table("booking")->where("id","=",$booking_id)->update(["booking_code"=>$bookingcode]);
              if (empty($getslots)) {
                $slot_id = DB::table('venue_booking_slots')->insertGetId($slot_data);
              }else {
                $totalsum = $input['party_members'] + $getslots->members_booked;
                $updatebookingslots = DB::table("venue_booking_slots")->where("booking_slot_id","=",$getslots->booking_slot_id)->update(["members_booked"=> $totalsum, "status"=>2]);
              }
              $booking_items = array();
              if (!empty($input['menu_item']) && count($input['menu_item']) > 0) {
                foreach($input['menu_item'] as $k =>$mi){
                  //code to check inventory
                  // if(!empty($mi['item_id'])){
                  //
                  //   $check_inventory =$this->check_inventory($mi['item_id']);
                  //   if($check_inventory<$mi['qty']){
                  //     $res_msg = isset($csvData['item_not_available']) ? $csvData['item_not_available'] : "Sorry item ".$mi['name']." is not available.";
                  //     return parent::api_response([],false,$res_msg, 200);
                  //
                  //   }
                  // }

                  $booking_items[]  = array(
                    'booking_id'=>$booking_id,
                    "user_id"=> $input['userid'],
                    'item_id'=>$mi['item_id'],
                    'name'=>$mi['name'],
                    'price'=>$mi['price'],
                    'qty'=>$mi['qty']
                  );
                }
                $save_items =  DB::table('booking_items')->insert($booking_items);
              }

              $qr_data['booking_code'] = $bookingcode;
              $qr_data['booking_id']  = $booking_id;
              $qr_data['user_id']     = $input['userid'];
              $qr_image = \QrCode::format('png')
                         ->size(200)->errorCorrection('H')
                         ->generate(json_encode($qr_data));
              $b = "data:image/png;base64,".base64_encode($qr_image);
              $qr_image   = imagecreatefrompng($b);
              $image_name = "qr_".$booking_code.".png";
              imagepng($qr_image, "public/uploads/qrcode/".$image_name);

              $saveQrCode = DB::table('booking')->where('id',$booking_id)->update(['qr_code'=>$image_name]);
              DB::commit();
              $booking_details = DB::table('booking')
                                          ->where('id',$booking_id)->first();
              $venue_detail = DB::table('venue')
                                          ->where('id',$input['venue_id'])->first();
              $booking_items_detail = DB::table("booking_items as bi")
                                  ->select("bi.item_id","bi.user_id","bi.qty","bi.price as booking_items_price","bi.name","bi.id as booking_items_id","mi.id as menu_item_id","mi.name as menu_item_name",'mi.*')
                                  ->leftJoin("menu_item as mi","bi.item_id","mi.id")
                                  ->where("bi.booking_id","=",$booking_id)
                                  ->get();
              foreach ($booking_items_detail as $key => $v) {
                if(!empty($v)){
                  $booking_items_detail[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                }
              }
              if(!empty($venue_detail->other_img)){
                $venue_detail->other_img = asset($this->uploadsFolder.'/venue/other_image/'.$venue_detail->other_img);
              }
              if(!empty($venue_detail->map_icon)){
                $venue_detail->map_icon = asset($this->uploadsFolder.'/venue/map_icone/'.$venue_detail->map_icon);
              }
              $offer = DB::table('offers')->select('offers.*')
                          ->join('booking','offers.offer_id','booking.offer_id')
                          ->where('offers.offer_id',$booking_details->offer_id)
                          ->get()->toArray();
              $booking_details->offer = $offer;
              $data['booking_detail'] = $booking_details;
              $data['booking_items'] = $booking_items_detail;
              $data['venue_detail'] = $venue_detail;
              $data['booking'] = array("booking_id" =>$booking_id,"amount"=>$input['total_amount']);

              //$this->reserve_inventory($input['menu_item']);
              if($input["sub_total"]==0){
                $this->freebooking($booking_id);
              }
              $res_msg = "";
              return parent::api_response($data,true,$res_msg, 200);
            }
            else{
               $booking_code = $this->generateRandomBookingCode();
          $booking_data = array(
                            "venue_id"      => $input['venue_id'],
                            "userid"        => $input['userid'],
                            "booking_date"  => $input['booking_date'],
                            "booking_time"  => $input['booking_time'],
                            "booking_status"=> 'Pending',
                            "booking_amount"=> $input['sub_total'],
                            "tax_amount"    => $tax,
                            "tip_amount"    => $tip,
                            "total_amount"  => $input['total_amount'],
                            "currency"      => $input['price_category'],
                            "booking_method"=> $input['booking_method'],
                            "instructions"  => $instruction,
                            "created_at"    => date("Y-m-d H:i:s"),
                            "offer_id"      => $offer_id,
                            "booking_slot_id"=> $booking_slot_id,
                            "delivery_fees"=> $deliveryFees,
                            "booking_fees"=> $bookingFees,
                           /* "booking_code"  => $booking_code*/
                          );
          DB::beginTransaction();
          //try {
              $booking_id = DB::table('booking')->insertGetId($booking_data);
              $bookingcode = $booking_code."#".$booking_id."#".$input['userid']."#1";
              $update_book = DB::table("booking")->where("id","=",$booking_id)->update(["booking_code"=>$bookingcode]);
              $booking_items = array();
              if (!empty($input['menu_item']) && count($input['menu_item']) > 0) {
                foreach($input['menu_item'] as $k =>$mi){
                  //code to check inventory
                  // if(!empty($mi['item_id'])){
                  //     $check_inventory =$this->check_inventory($mi['item_id']);
                  //     if($check_inventory<$mi['qty']){
                  //     $res_msg = isset($csvData['item_not_available']) ? $csvData['item_not_available'] : "Sorry item ".$mi['name']." is not available.";
                  //     return parent::api_response([],false,$res_msg, 200);
                  //     }
                  // }
                  $checkpayall = DB::table("booking")->where("id","=",$booking_id)->first();
                  if (!empty($input['invite_user_id']) && $checkpayall->pay_all == '1' && isset($input['invite_user_id'])) {
                    // $booking_items[]  = array(
                    //             'booking_id'=>$bookingid,
                    //             'user_id'=>$invitefriendid,
                    //             'item_id'=>$mi['item_id'],
                    //             'name'=>$mi['name'],
                    //             'price'=>$mi['price'],
                    //             'qty'=>$mi['qty']
                    //           );
                     $booking_items2[]  = array(
                                'booking_id'=>$booking_id,
                                'user_id'=>$invitefriendid,
                                'item_id'=>$mi['item_id'],
                                'name'=>$mi['name'],
                                'price'=>$mi['price'],
                                'qty'=>$mi['qty']
                              );
                  }else {
                    // $booking_items[]  = array(
                    //   'booking_id'=>$bookingid,
                    //   "user_id"=> $input['userid'],
                    //   'item_id'=>$mi['item_id'],
                    //   'name'=>$mi['name'],
                    //   'price'=>$mi['price'],
                    //   'qty'=>$mi['qty']
                    // );
                    $booking_items2[]  = array(
                      'booking_id'=>$booking_id,
                      "user_id"=> $input['userid'],
                      'item_id'=>$mi['item_id'],
                      'name'=>$mi['name'],
                      'price'=>$mi['price'],
                      'qty'=>$mi['qty']
                    );
                  }
                }
                // $save_items =  DB::table('booking_items')->insert($booking_items);
                $save_items =  DB::table('booking_items')->insert($booking_items2);
              }

              $qr_data['booking_code'] = $bookingcode;
              $qr_data['booking_id']  = $booking_id;
              $qr_data['user_id']     = $input['userid'];
              $qr_image = \QrCode::format('png')
                         ->size(200)->errorCorrection('H')
                         ->generate(json_encode($qr_data));
              $b = "data:image/png;base64,".base64_encode($qr_image);
              $qr_image   = imagecreatefrompng($b);
              $image_name = "qr_".$booking_code.".png";
              imagepng($qr_image, "public/uploads/qrcode/".$image_name);

              $saveQrCode = DB::table('booking')->where('id',$booking_id)->update(['qr_code'=>$image_name]);
              DB::commit();
              $booking_details = DB::table('booking')
                                          ->where('id',$booking_id)->first();
              $venue_detail = DB::table('venue')
                                          ->where('id',$input['venue_id'])->first();
            $booking_items_detail = [];
            if (!empty($input['menu_item']) && count($input['menu_item']) > 0) {
              $booking_items_detail = DB::table("booking_items as bi")
              ->select("bi.item_id","bi.user_id","bi.qty","bi.price as booking_items_price","bi.name","bi.id as booking_items_id","mi.id as menu_item_id","mi.name as menu_item_name",'mi.*')
              ->leftJoin("menu_item as mi","bi.item_id","mi.id")
              ->where("bi.booking_id","=",$booking_id)
              ->get();
              foreach ($booking_items_detail as $key => $v) {
                if(!empty($v)){
                  $booking_items_detail[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                }
              }
            }
              if(!empty($venue_detail->other_img)){
                $venue_detail->other_img = asset($this->uploadsFolder.'/venue/other_image/'.$venue_detail->other_img);
              }
              if(!empty($venue_detail->map_icon)){
                $venue_detail->map_icon = asset($this->uploadsFolder.'/venue/map_icone/'.$venue_detail->map_icon);
              }
              $offer = DB::table('offers')->select('offers.*')
                          ->join('booking','offers.offer_id','booking.offer_id')
                          ->where('offers.offer_id',$booking_details->offer_id)
                          ->get()->toArray();
              $booking_details->offer = $offer;
              $data['booking_detail'] = $booking_details;
              $data['booking_items'] = $booking_items_detail;
              $data['venue_detail'] = $venue_detail;
              $data['booking'] = array("booking_id" =>$booking_id,"amount"=>$input['total_amount']);

              if($input["sub_total"]==0){

                $this->freebooking($booking_id);
              }

              $res_msg = "";

              return parent::api_response($data,true,$res_msg, 200);

            }
      }
    }

   public function  freebooking($booking_id){

            //$message = "Amount should be greater than Zero.";
            //return parent::api_response([], false, $message, 200);
            //for free
            $charge_id = "NA";
            DB::table("booking")->where("id", "=",$booking_id)->update(["transaction_id" => $charge_id, "booking_status" =>'Accepted']);
               //reduce inventorycode
                 $get_book_item = DB::table("booking_items as bi")->select("bi.item_id","bi.qty","bi.price","mi.inventory_id")->leftJoin("menu_item as mi","bi.item_id","mi.id")->where("bi.booking_id","=",$booking_id)->get();



                   if(!empty($get_book_item[0])){
                    //get inventory count
                    foreach($get_book_item as $inv){
                    $inv_count = DB::table("inventory")->select("count_hand")->where("id","=",$inv->inventory_id)->first();
                    if(!empty($inv_count->count_hand) && $inv_count->count_hand>0){
                      $count =0;
                      $count =  $inv_count->count_hand - $inv->qty;
                      if($count>=0){
                      $upd_inventory = DB::table("inventory")->where("id","=",$inv->inventory_id)->update(["count_hand"=>$count]);

                      }


                    }

                    }
                   }

               //

                //send notification to venue

              $get_venueadmin = DB::table("booking as b")->select("u.device_type","u.device_token","g.name","g.last_name","u.id as venueadminid","v.name as venue_name")->leftJoin("venue as v","b.venue_id","v.id")->leftJoin("guest as g","g.userid","b.userid")->leftJoin("users as u","u.id","v.teamadmin_id")->where("b.id","=",$booking_id)->first();


                if(!empty($get_venueadmin)){
                        $subject =isset($csvData['new_booking']) ? $csvData['new_booking'] : "You have a new Booking!";
                        $name =$get_venueadmin->name." ".$get_venueadmin->last_name;
                        $message= $name." has made a purchase at ".$get_venueadmin->venue_name."! Send them a chat to thank them for their business!";
                        $sub_notify_msg =isset($csvData['new_booking_message']) ? $csvData['new_booking_message'] :$message;
                         $notify_data = array(
                           "notification_key" =>1
                         );
                        $json_notify_data = json_encode($notify_data);
                        if($get_venueadmin->device_type==1){

                        $res_notification = Helper:: sendNotification($get_venueadmin->device_type , $get_venueadmin->device_token, $sub_notify_msg, $subject , $json_notify_data,"venueapp");

                        }
                        else{
                        $notificationPayload = array(
                             "body"=>$sub_notify_msg,
                             "titile"=> $subject
                            );

            $dataPayload = array(
                "body" => $sub_notify_msg,
                "title"=> $subject,
                "notification_key" =>1

            );

            $notify_data = array(
                "to" => $get_venueadmin->device_token,
                "notification"=>$notificationPayload,
                "data"=>$dataPayload
            );
                       //$json_notify_data = json_encode($notify_data);
                       $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "venueapp");

                        }
                        $insert = DB::table('user_notification')->insert([

                            ['message' => $sub_notify_msg, 'user_id' => $get_venueadmin->venueadminid, 'subject' => $subject, "device_type" => $get_venueadmin->device_type, "notification_key" =>1, "data" => $json_notify_data,
                              "user_type" =>2
                           ]
                        ]);

                       }
                return true;



   }

    public function save_booking_v1old(Request $request){
      $input = $request->all();
      $validator  = Validator::make($input,[
                      'userid'       => 'required',
                      'venue_id'     => 'required',
                      'menu_item'    => 'required',
                      'sub_total'    => 'required',
                      'booking_date' => 'required',
                      /*'booking_time' => 'required',*/
                      'total_amount' => 'required',
                      'price_category' =>'required',
                      'booking_method' => 'required'
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{

        if(!isset($input['booking_time'])){
           $input['booking_time']="";
        }

        if(isset($input['bookingid']) && !empty($input['bookingid'])){
           $bookingid=$input['bookingid'];
        }
        else{
           $bookingid="";
        }


        //
        //Menu Items : [{"item_id":"6","name":"Malal","price":"15","qty":"2"},{"item_id":"4","name":"Arjeect","price":"1500","qty":"4"}]
        $lang_data  = parent::getLanguageValues($request);($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData']) ) {
          $csvData  = $lang_data['csvData'];
        }

        //check venue status
        $venueStatus = DB::table("venue")->where("id","=",$input["venue_id"])->first();
        if($venueStatus->status!=1 || $venueStatus->deleted_at!=""){
          $res_msg = isset($csvData['venue_not_available']) ? $csvData['venue_not_available'] : 'venue not availble for booking.';
          return parent::api_response([],true,$res_msg, 200);
        }
        $tax = isset($input['tax'])?$input['tax']:0;
        $tip = isset($input['tip'])?$input['tip']:0;
        $instruction = isset($input['instruction'])?$input['instruction']:'';
        $validate_items = $this->validate_order($input);
        if($validate_items===true){

          $booking_code = $this->generateRandomBookingCode();
          if(empty($bookingid)){

          $booking_data = array(
                            "venue_id"      => $input['venue_id'],
                            "userid"        => $input['userid'],
                            "booking_date"  => $input['booking_date'],
                            "booking_time"  => $input['booking_time'],
                            "booking_status"=> 'Pending',
                            "booking_amount"=> $input['sub_total'],
                            "tax_amount"    => $tax,
                            "tip_amount"    => $tip,
                            "total_amount"  => $input['total_amount'],
                            "currency"      => $input['price_category'],
                            "booking_method"=> $input['booking_method'],
                            "instructions"  => $instruction,
                            "created_at"    => date("Y-m-d H:i:s")
                           /* "booking_code"  => $booking_code*/
                          );
          DB::beginTransaction();
          //try {
              $booking_id = DB::table('booking')->insertGetId($booking_data);
              $bookingcode = $booking_code."#".$booking_id."#".$input['userid']."#1";
              $update_book = DB::table("booking")->where("id","=",$booking_id)->update(["booking_code"=>$bookingcode]);
            }
            else{
              $booking_id=$bookingid;
            }
              $booking_items = array();
              foreach($input['menu_item'] as $k =>$mi){
                //code to check inventory
                if(!empty($mi['item_id'])){

                $check_inventory =$this->check_inventory($mi['item_id']);
                if($check_inventory<$mi['qty']){
                $res_msg = isset($csvData['item_not_available']) ? $csvData['item_not_available'] : "Sorry item ".$mi['name']." is not available.";
                return parent::api_response([],false,$res_msg, 200);

                }
                }

                $booking_items[]  = array(
                            'booking_id'=>$booking_id,
                            'item_id'=>$mi['item_id'],
                            'name'=>$mi['name'],
                            'price'=>$mi['price'],
                            'qty'=>$mi['qty']
                          );
              }
              $save_items =  DB::table('booking_items')->insert($booking_items);
               if(empty($bookingid)){
              $qr_data['booking_code'] = $bookingcode;
              $qr_data['booking_id']  = $booking_id;
              $qr_data['user_id']     = $input['userid'];
              $qr_image = \QrCode::format('png')
                         ->size(200)->errorCorrection('H')
                         ->generate(json_encode($qr_data));
              $b = "data:image/png;base64,".base64_encode($qr_image);
              $qr_image   = imagecreatefrompng($b);
              $image_name = "qr_".$booking_code.".png";
              imagepng($qr_image, "public/uploads/qrcode/".$image_name);

              $saveQrCode = DB::table('booking')->where('id',$booking_id)->update(['qr_code'=>$image_name]);
              DB::commit();
             }
              $data['booking'] = array("booking_id" =>$booking_id,"amount"=>$input['total_amount']);

              //$this->reserve_inventory($input['menu_item']);


              $res_msg = "";

              return parent::api_response($data,true,$res_msg, 200);
              // all good
          /*} catch (\Exception $e) {
              DB::rollback();
              return parent::api_response((object)[],false,$e->getMessage(), 200);
          }*/
        }else{
          // print_r($validate_items);die();
          return parent::api_response((object)[],false,$validate_items, 200);
        }
      }
    }

    public function reserve_inventory($item_id){

      foreach($item_id as $k =>$mi){
        $get_inventory = DB::table("menu_item as item")->select("iv.name","iv.count_hand","iv.is_renewable")->leftJoin("inventory as iv","item.inventory_id","iv.id")->where("item.id","=",$item_id)->first();
         $item_cnt = $get_inventory->count_hand;
         if($item_cnt>0){
            $item_cnt = $item_cnt-1;
            $update_inventory  = DB::table("inventory")->update(["count_hand"=>$item_cnt]);
         }
      }

    }

    function check_inventory($item_id){

     $get_inventory = DB::table("menu_item as item")->select("iv.name","iv.count_hand","iv.is_renewable")->leftJoin("inventory as iv","item.inventory_id","iv.id")->where("item.id","=",$item_id)->first();

       //if($get_inventory->is_renewable=="2"){
       /*if($get_inventory->count_hand<1){
       return 0;
       }
       else{
       return 1;
       }*/
       return $get_inventory->count_hand;
                //}
    }

    function validate_order($input){
      $items = $input['menu_item'];

      $booking_date = $input['booking_date'];
      $booking_at   = strtotime($booking_date);
      //sort arry according to item id
      usort($items, function($a, $b) {
          return $a['item_id'] <=> $b['item_id'];
        });
      $ids          = array_column($items, 'item_id');
      if(!empty($ids)){
        $items_dt = DB::table('menu_item')
                ->select('menu_item.id','menu_item.name','price','offer_type','from_date','to_date','is_recurrence','recurrence_id')
                ->WHEREIN('menu_item.id',$ids)->get()->toArray();
        if(count($ids)==count($items_dt)){
          $total_price = 0;

          foreach ($items_dt as $key => $value) {
            $price          = $value->price;
            $offer_type     = $value->offer_type;
            $from_date      = $value->from_date;
            $to_date        = $value->to_date;
            $is_recurrence  = $value->is_recurrence;
            $recurrence_id  = $value->recurrence_id;

            /*if($price != (float)$items[$key]['price']){
              $message = "Item ".$value->name."- price is not match";
              return $message;
              exit;
            }*/
            if($offer_type ==1){
              //if(($booking_at>=$from_date) && ($booking_at<=$to_date)){
                $total_price=$total_price+($price*$items[$key]['qty']);

              /*}else{
                $message = "Oops! Item " .$value->name. " is not available on selected date and time";
                return $message;
                exit;
              }*/
            }else{
              $total_price=$total_price + ($price*$items[$key]['qty']);

            }
          }

          // echo $total_price."==".$input['sub_total'];

          if(trim($total_price)==trim($input['sub_total'])){
            //die("T");
            return true;
          }else{
            //die("F");
            return true;
            //beloq line should be uncomment when next build
            /*$message = "Oops! Booked Item total price is not matched. Its should be ".$input['price_category']. $total_price;
            return $message;
            exit;*/
          }
        } else{
          $message = "Oops! some Items is not matched with this venue menu";
          return $message;
          exit;
        }
      }else{
        $message = "Please select atleast one menu item";
        return $message;
        exit;
      }
    }

    public function generateRandomBookingCode($club_id = 0){
      $pool = '123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
      $rand = substr(str_shuffle(str_repeat($pool, 6)), 0,6);
      $randomstring = $rand;
      return $randomstring;
    }

    public function createBookingPayment(Request $request) {
      try{
        $input      =   $request->all();
        $validator  =   Validator::make($input, [
                            "userid"     => "required",
                            "booking_id" => "required",
                        ]);

        if ($validator->fails()) {
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray(), $request);
          return parent :: api_response((object) [], false, $err_msg, 200);
        } else {
          $get_booking = DB::table("booking")
                          ->select("id", "total_amount","booking_status","guest.customer_stripId")
                          ->join('guest','guest.userid','booking.userid')
                          ->where([["id",$input["booking_id"]],['booking_status','Pending']])
                          ->first();
          if(empty($get_booking)){
            $err_msg = "This Booking id does not exist.";
            return parent :: api_response((object) [], false, $err_msg, 200);
          }
          elseif ($get_booking->booking_status != 'Pending') {
            $err_msg = "This booking is not in pending status.";
            return parent :: api_response((object) [], false, $err_msg, 200);
          }
          elseif ($get_booking->customer_stripId=="") {
            $err_msg = "You have not save any payment option.Please add debit or credit card.";
            return parent :: api_response((object) [], false, $err_msg, 200);
          }

          $amount = $get_booking->total_amount;
          $customer_stripId = $get_booking->customer_stripId;
          if ($amount == 0) {
            //$message = "Amount should be greater than Zero.";
            //return parent::api_response([], false, $message, 200);
            //for free
            $charge_id = "NA";
            DB::table("booking")->where("id", "=", $input['booking_id'])->update(["transaction_id" => $charge_id, "booking_status" =>'Accepted']);
               //reduce inventorycode
                 $get_book_item = DB::table("booking_items as bi")->select("bi.item_id","bi.qty","bi.price","mi.inventory_id")->leftJoin("menu_item as mi","bi.item_id","mi.id")->where("bi.booking_id","=",$input['booking_id'])->get();
                   if(!empty($get_book_item[0])){
                    //get inventory count
                    foreach($get_book_item as $inv){
                    $inv_count = DB::table("inventory")->select("count_hand")->where("id","=",$inv->inventory_id)->first();
                    if(!empty($inv_count->count_hand) && $inv_count->count_hand>0){
                      $count =0;
                      $count =  $inv_count->count_hand - $inv->qty;
                      if($count>=0){
                      $upd_inventory = DB::table("inventory")->where("id","=",$inv->inventory_id)->update(["count_hand"=>$count]);

                      }
                    }
                    }
                   }

               //

                //send notification to venue
               $booking_id = $input["booking_id"];
              $get_venueadmin = DB::table("booking as b")->select("u.device_type","u.device_token","g.name","g.last_name","u.id as venueadminid","v.name as venue_name")->leftJoin("venue as v","b.venue_id","v.id")->leftJoin("guest as g","g.userid","b.userid")->leftJoin("users as u","u.id","v.teamadmin_id")->where("b.id","=",$booking_id)->first();


                if(!empty($get_venueadmin)){
                        $subject =isset($csvData['new_booking']) ? $csvData['new_booking'] : "You have a new Booking!";
                        $name =$get_venueadmin->name." ".$get_venueadmin->last_name;
                        $message= $name." has made a purchase at ".$get_venueadmin->venue_name."! Send them a chat to thank them for their business!";
                        $sub_notify_msg =isset($csvData['new_booking_message']) ? $csvData['new_booking_message'] :$message;
                         $notify_data = array(
                           "notification_key" =>1
                         );
                         $this->sendConfirmBookingEmail($input['booking_id']);
                        $json_notify_data = json_encode($notify_data);
                        if($get_venueadmin->device_type==1){

                        $res_notification = Helper:: sendNotification($get_venueadmin->device_type , $get_venueadmin->device_token, $sub_notify_msg, $subject , $json_notify_data,"venueapp");

                        }
                        else{
                        $notificationPayload = array(
                             "body"=>$sub_notify_msg,
                             "titile"=> $subject
                            );

            $dataPayload = array(
                "body" => $sub_notify_msg,
                "title"=> $subject,
                "notification_key" =>1

            );

            $notify_data = array(
                "to" => $get_venueadmin->device_token,
                "notification"=>$notificationPayload,
                "data"=>$dataPayload
            );
                       //$json_notify_data = json_encode($notify_data);
                       $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "venueapp");

                        }
                        $insert = DB::table('user_notification')->insert([

                            ['message' => $sub_notify_msg, 'user_id' => $get_venueadmin->venueadminid, 'subject' => $subject, "device_type" => $get_venueadmin->device_type, "notification_key" =>1, "data" => $json_notify_data,
                              "user_type" =>2
                           ]
                        ]);

                       }

                $msg = "Booked successfully.";
                $data["booking_id"] = $input['booking_id'];
                return parent::api_response($data, true, $msg, 200);
          } else {
            $secret_key = Config::get('constants.stripe_secret_key');
            // $secret_key = 'sk_test_S29zxfz6R3Fvb8KbpebPen1400XNfwwEiQ';
            $public_key = Config::get('constants.stripe_public_key');
            \Stripe\Stripe::setApiKey($secret_key);
            try{
              $charge  =  \Stripe\Charge::create([
                            'amount'      => $amount*100,
                            'currency'    => 'usd',
                            'customer'    => $customer_stripId,
                            "description" => 'Charge for booking id'.$input['booking_id'],
                            "metadata" => ["booking_id"=>$input['booking_id']]
                          ]);
              $charge_id  = $charge->id;
              $status     = $charge->status;
              $fingerprint = $charge->source->fingerprint;

              $transaction_data = array(
                            'userid'          =>  $input['userid'],
                            'booking_id'      =>  $input['booking_id'],
                            'transaction_id'  =>  $charge_id,
                            'trans_type'      =>  'Cr',
                            'trans_status'    =>  $status,
                            'amount'          =>  $amount,
                            'card_fingerprint'=>$fingerprint
                          );
              DB::table('transaction_history')->insert($transaction_data);
              if ($status == "succeeded") {
                DB::table("booking")->where("id", "=", $input['booking_id'])->update(["transaction_id" => $charge_id, "booking_status" =>'Accepted']);
               //reduce inventorycode
                 $get_book_item = DB::table("booking_items as bi")->select("bi.item_id","bi.qty","bi.price","mi.inventory_id")->leftJoin("menu_item as mi","bi.item_id","mi.id")->where("bi.booking_id","=",$input['booking_id'])->get();
                   if(!empty($get_book_item[0])){
                    //get inventory count
                    foreach($get_book_item as $inv){
                    $inv_count = DB::table("inventory")->select("count_hand")->where("id","=",$inv->inventory_id)->first();
                    if(!empty($inv_count->count_hand) && $inv_count->count_hand>0){
                      $count =0;
                      $count =  $inv_count->count_hand - $inv->qty;
                      if($count>=0){
                      $upd_inventory = DB::table("inventory")->where("id","=",$inv->inventory_id)->update(["count_hand"=>$count]);

                      }


                    }

                    }
                   }

               //

                //send notification to venue
               $booking_id = $input["booking_id"];
              $get_venueadmin = DB::table("booking as b")->select("u.device_type","u.device_token","g.name","g.last_name","u.id as venueadminid","v.name as venue_name")->leftJoin("venue as v","b.venue_id","v.id")->leftJoin("guest as g","g.userid","b.userid")->leftJoin("users as u","u.id","v.teamadmin_id")->where("b.id","=",$booking_id)->first();

                if(!empty($get_venueadmin)){
                        $subject =isset($csvData['new_booking']) ? $csvData['new_booking'] : "You have a new Booking!";
                        $name =$get_venueadmin->name." ".$get_venueadmin->last_name;
                        $message= $name." has made a purchase at ".$get_venueadmin->venue_name."! Send them a chat to thank them for their business!";
                        $sub_notify_msg =isset($csvData['new_booking_message']) ? $csvData['new_booking_message'] :$message;
                         $notify_data = array(
                           "notification_key" =>1
                         );
                         $this->sendConfirmBookingEmail($input['booking_id']);
                        $json_notify_data = json_encode($notify_data);
                        if($get_venueadmin->device_type==1){

                        $res_notification = Helper:: sendNotification($get_venueadmin->device_type , $get_venueadmin->device_token, $sub_notify_msg, $subject , $json_notify_data,"venueapp");

                        }
                        else{
                        $notificationPayload = array(
                             "body"=>$sub_notify_msg,
                             "titile"=> $subject
                            );

            $dataPayload = array(
                "body" => $sub_notify_msg,
                "title"=> $subject,
                "notification_key" =>1

            );

            $notify_data = array(
                "to" => $get_venueadmin->device_token,
                "notification"=>$notificationPayload,
                "data"=>$dataPayload
            );
                       //$json_notify_data = json_encode($notify_data);
                       $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "venueapp");
                        }
                        $insert = DB::table('user_notification')->insert([

                            ['message' => $sub_notify_msg, 'user_id' => $get_venueadmin->venueadminid, 'subject' => $subject, "device_type" => $get_venueadmin->device_type, "notification_key" =>1, "data" => $json_notify_data,
                              "user_type" =>2
                           ]
                        ]);

                       }
                 $booking_items_detail = DB::table("booking_items as bi")
                                     ->select("bi.item_id","bi.user_id","bi.qty","bi.price as booking_items_price","bi.name","bi.id as booking_items_id","mi.id as menu_item_id","mi.name as menu_item_name",'mi.*')
                                     ->leftJoin("menu_item as mi","bi.item_id","mi.id")
                                     ->where("bi.booking_id","=",$booking_id)
                                     ->get();
                 foreach ($booking_items_detail as $key => $v) {
                   if(!empty($v)){
                     $booking_items_detail[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                   }
                 }
                 $get_venue_id = DB::table("booking")->select("id","venue_id")->where("id","=",$booking_id)->first();

                 $venue_detail = DB::table('venue')
                                             ->where('id',$get_venue_id->venue_id)->first();

                 if(!empty($venue_detail->other_img)){
                   $venue_detail->other_img = asset($this->uploadsFolder.'/venue/other_image/'.$venue_detail->other_img);
                 }
                 if(!empty($venue_detail->map_icon)){
                   $venue_detail->map_icon = asset($this->uploadsFolder.'/venue/map_icone/'.$venue_detail->map_icon);
                 }
                $data['booking_detail'] = DB::table('booking')
                                             ->where('id',$booking_id)->first();
                $msg = "Booked successfully.";
                $data['booking_items'] = $booking_items_detail;
                $data['venue_detail'] = $venue_detail;
                $data["booking_id"] = $input['booking_id'];
                return parent::api_response($data, true, $msg, 200);
              }else{
                $msg = "Oops! Your booking is not complete yet due to transaction is not complete. Tranasaction status is ".$status;
                $data["booking_id"] = $input['booking_id'];
                return parent::api_response($data, true, $msg, 200);
              }
            }
            catch (\Stripe\Exception\InvalidRequestException $e) {
              $data["booking_id"] = $input['booking_id'];
              $msg = $e->getMessage();
              return parent :: api_response($data, false, $msg, 200);
            }
          }
        }
      }
      catch (\Exception $e) {
        $data["booking_id"] = $input['booking_id'];
        $msg = $e->getMessage();
        return parent :: api_response($data, false, $msg, 200);
      }
    }

    function sendConfirmBookingEmail($booking_id){
      $get_venueadmin = DB::table("booking as b")
                    ->select("b.*","u.*","v.*","u.device_type","u.email as user_email","u.device_token","u.id as venueadminid","v.name as venue_name")
                    ->leftJoin("venue as v","b.venue_id","v.id")
                    ->leftJoin("users as u","u.id","b.userid")
                    ->where("b.id","=",$booking_id)
                    ->first();
      if(!empty($get_venueadmin) && isset($get_venueadmin->user_email) && !empty($get_venueadmin->user_email)){
        $get_booking_item = DB::table('booking_items as b')
                                ->select('b.*','m.*','b.price as booking_price','b.qty as booking_qty')
                                ->join('menu_item as m','m.id','b.item_id')
                                ->where('b.booking_id',$booking_id)
                                ->get();
        $subject = "You have a new Booking";
        $objDemo = new \stdClass();

        $objDemo->booking_detail = $get_venueadmin;
        $objDemo->get_booking_item = $get_booking_item;
        $objDemo->sender      =  Config::get('constants.SENDER_EMAIL');
        $objDemo->sender_name =  Config::get('constants.SENDER_NAME');
        $objDemo->website     =  Config::get('constants.SENDER_WEBSITE');
        $objDemo->receiver    = $get_venueadmin->user_email;
        $objDemo->receiver_name = $get_venueadmin->first_name .' '.$get_venueadmin->last_name;
        $objDemo->subject     = $subject;
        // return view('mails.booking',['demo'=>$objDemo]);
        Mail::to($get_venueadmin->user_email)->send(new BookingEmail($objDemo));
      }
    }

    function sendBookingEmail(){

      $club_admin_rec = DB::table('club as c')
                          ->leftJoin('admin as a', 'c.teamadmin_id', '=', 'a.id')
                          ->select('a.email','a.name','a.username')
                          ->where('c.club_id','=',$package_data[0]->club_id)
                          ->get()->toArray();

      if(isset($club_admin_rec[0]->email) && !empty($club_admin_rec[0]->email)){
        $objDemo = new \stdClass();
        $objDemo->demo_one = '<pre>
                              Booking Details :
                              username            - '.$username.'
                              package name        - '.$package_name.'
                              package description - '.$package_description.'
                              club name           - '.$club_name.'
                              club address        - '.$club_address.'
                              club city           - '.$club_city.'
                              no of person        - '.$no_person.'
                              package amount      - '.$amount.'
                              party date          - '.$party_date.'
                              </pre>';

        $objDemo->sender      =  Config::get('constants.SENDER_EMAIL');
        $objDemo->sender_name =  Config::get('constants.SENDER_NAME');
        $objDemo->website     =  Config::get('constants.SENDER_WEBSITE');
        $objDemo->receiver    = $club_admin_rec[0]->email;
        $objDemo->receiver_name = $club_admin_rec[0]->name;
        $objDemo->subject     = "User Booked a Package";
        Mail::to($club_admin_rec[0]->email)->send(new DemoEmail($objDemo));
      }
    }

    public function checkIn(Request $request){
      try{
        $input = $request->all();
        $validator  = Validator::make($input,[
          'booking_id'      => "required"
        ]);

        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response([],false,$err_msg, 200);
        }else{
          $booking_id = $input['booking_id'];
          $check_booking = DB::table('booking')->select('*')->where('id','=',$booking_id)->first();
          if (!empty($check_booking)) {
            $check_checkin = DB::table('booking')->select('*')->where('id','=',$booking_id)->where('checked_in' ,'=', NULL)->first();
            if (!empty($check_checkin)) {
              $current_time = Carbon::now();
              $formated_time = $current_time->toDateTimeString();
              $updatetime = DB::table('booking')->where('id', $booking_id)->update(['checked_in' => $formated_time]);
              // $get_booking = DB::table('booking')->select('*')->where('id','=',$booking_id)->first();
              $data['booking_id'] = $booking_id;
              return parent::api_response($data,true,"Checkin successfully", 200);
            }else {
              $res_msg = isset($check_checkin['Already checkedin for this booking']) ? $check_checkin['Already checkedin for this booking'] : "Already checkedin for this booking.";
              return parent::api_response((object)[],false,$res_msg, 200);
            }
          }else {
            $res_msg = isset($check_booking['No_record_found']) ? $check_booking['No_record_found'] : "No record found.";
            return parent::api_response((object)[],false,$res_msg, 200);
            }
          }
      }catch(\Exception $e){
        return parent :: api_response([],false,$e->getMessage(),'200');
      }
    }

    public function checkOut(Request $request){
      try{
        $input = $request->all();
        $validator  = Validator::make($input,[
          'booking_id'      => "required"
        ]);

        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response([],false,$err_msg, 200);
        }else{
          $booking_id = $input['booking_id'];
          if (!empty($input['tip'])) {
            $tip = $input['tip'];
          }
          $check_booking = DB::table('booking')->select('*')->where('id','=',$booking_id)->first();
          if (!empty($check_booking)) {
            $check_checkout = DB::table('booking')->select('*')->where('id','=',$booking_id)->where('check_out','=',NULL)->where('checked_in','!=',NULL)->first();
            if (!empty($check_checkout)) {
              $current_time = Carbon::now();
              $formated_time = $current_time->toDateTimeString();
              if (!empty($tip)) {
                $updatetime = DB::table('booking')->where('id', $booking_id)
                ->update(['check_out' => $formated_time, 'tip' => $tip]);
              }else {
                $updatetime = DB::table('booking')->where('id', $booking_id)
                ->update(['check_out' => $formated_time]);
              }
              $data['booking_id'] = $booking_id;
              return parent::api_response($data,true,"Checkout successfully", 200);
            }else {
              $res_msg = isset($check_checkout['Already checkout or checkin first']) ? $check_checkout['Already checkout or checkin first'] : "Already checkout or checkin first.";
              return parent::api_response((object)[],false,$res_msg, 200);
            }
          }else {
            $res_msg = isset($check_booking['No_record_found']) ? $check_booking['No_record_found'] : "No record found.";
            return parent::api_response((object)[],false,$res_msg, 200);
            }
          }
      }catch(\Exception $e){
        return parent :: api_response([],false,$e->getMessage(),'200');
      }
    }

    public function getbookings(Request $request){
      try{
        $input      = $request->all();
        $validator  = Validator::make($input,[
                        "userid"=>"required"
                      ]);
        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }else{
          $userid = $input['userid'];
          $lang_data  = parent :: getLanguageValues($request);
          $csvData    = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $booking_list = DB::table('booking as b')->select('b.id','b.venue_id','b.order_status','v.name','v.menu_type','b.booking_date','b.booking_time','b.created_at','v.venue_home_image','b.booking_status')
                    ->join('venue as v','v.id','b.venue_id')
                    ->WHERE('b.userid',$userid)
                    ->where('b.booking_status','!=','Pending')
                    ->get()->toArray();

          $booking_invite = DB::table('booking_invite_list as bi')->select('bi.booking_id as id','b.venue_id','v.name','v.menu_type','b.booking_date','b.booking_time','v.venue_home_image','b.booking_status','b.created_at')
                   ->join('booking as b','b.id','bi.booking_id')
                    ->join('venue as v','v.id','b.venue_id')
                    ->WHERE('bi.friend_id',$userid)
                    ->where('bi.status','=','A')
                    ->where('b.booking_status','!=','Pending')
                    ->get()->toArray();

           $booking=array_merge($booking_list,$booking_invite);
          $bookings = array_column($booking, 'booking_date');

        array_multisort($bookings, SORT_DESC, $booking);


          if(!empty($booking)){
            foreach ($booking as $key => $value) {
              if($value->menu_type!=1){
              $booking[$key]->booking_time = date('h:i A',strtotime($value->booking_time));
              }
              else{
               $booking[$key]->booking_time = date('h:i A',strtotime($value->created_at));
              }

                $booking[$key]->booking_date = date('d-M',strtotime($value->booking_date));
              if(!empty($value->venue_home_image)){
                $club_picture = explode(',',$value->venue_home_image);
                foreach ($club_picture as $k => $v) {
                  $booking[$key]->venue_home_image = asset($this->uploadsFolder.'/venue/home_image/'.$v);
                }
              }
            }
            $data['bookings'] = $booking;
            return parent::api_response($data,true,"success", 200);
          }else{
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "No record found.";
            return parent::api_response((object)[],false,$res_msg, 200);
          }
        }
      }catch(\Exception $e){
        return parent::api_response((object)[],false,$e->getMessage(), 200);
      }
    }

    public function getBookingDetails(Request $request){
      try{
        $input = $request->all();
        $validator = Validator::make($input,[
                      "userid"      => "required",
                      "booking_id"  => "required"
                    ]);
        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }else{
          $userid = $input['userid'];
          $lang_data  = parent :: getLanguageValues($request);
          $csvData    = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $booking_det = DB::table('booking as b')
                        ->select('b.id as booking_id','b.userid','b.created_at','b.order_status','b.pay_all','b.party_members','b.is_user_invite','b.waiter_id','b.checked_in','b.check_out','b.tip','b.promocode','b.is_offer_applied','b.party_organize','b.delivery_fees','b.booking_fees','total_amount','currency','booking_method','instructions','b.offer_id','booking_slot_id','v.id as venue_id','v.name','v.address','v.venue_home_image','v.description','v.menu_type','b.booking_date','b.booking_time','b.qr_code','b.booking_code','g.name as purchased_by','g.last_name')
                        ->join('venue as v','v.id','b.venue_id')
                        ->join('guest as g','g.userid','b.userid')
                        ->where('b.id',$input['booking_id'])
                        ->get()->first();
          if(!empty($booking_det)) {

              $booking_det->total_amount = Helper::numberFormat($booking_det->total_amount);

              if($booking_det->booking_time=="00:00:00"){
               $booking_det->booking_time_txt= "";
              }
              else{
              $booking_det->booking_time_txt=$booking_det->booking_time;
              }

              $booking_det->booking_date_txt=$booking_det->booking_date;

               if($booking_det->menu_type!=1){

             $bookingdateTime = new DateTime($booking_det->booking_date." ".$booking_det->booking_time);

             $currentDateTime = new DateTime(date("Y-m-d")." ".date("H:i:s"));
             }
             else{
              $bookingdateTime = new DateTime($booking_det->booking_date);

             $currentDateTime = new DateTime(date("Y-m-d"));
             }


               if($booking_det->menu_type!=1){
              $booking_det->booking_time = date('D',strtotime($booking_det->booking_date)).'-'.date('h:i A',strtotime($booking_det->booking_time));
              }
              else{

               $booking_det->booking_time = date('D',strtotime($booking_det->created_at)).'-'.date('h:i A',strtotime($booking_det->created_at));
              }

              if(empty($booking_det->instructions)){
                 $booking_det->instructions = "NA";
              }

              $booking_det->booking_date = date('m-d-Y',strtotime($booking_det->booking_date));

              if($booking_det->userid==$input["userid"]){

              $booking_det->qr_code =  asset($this->uploadsFolder.'qrcode/'.$booking_det->qr_code);
              }
              else{

               //if invited
                $get_code = DB::table("booking_invite_list")->select("qr_code","booking_code")->where("friend_id","=",$input["userid"])->where("booking_id","=",$booking_det->booking_id)->first();

                if(!empty($get_code->qr_code)){
                 $booking_det->qr_code = asset($this->uploadsFolder.'qrcode/'.$get_code->qr_code);
                  if(!empty($get_code->booking_code)){
                  $booking_det->booking_code=$get_code->booking_code;
                  }
                  else{
                  $booking_det->booking_code="";
                  }
                }
                else{
                  $booking_det->qr_code ="";
                }

              }

              $booking_det->purchased_by = $booking_det->purchased_by.' '.$booking_det->last_name;
              if(!empty($booking_det->venue_home_image)){
                $club_picture = explode(',',$booking_det->venue_home_image);
                foreach ($club_picture as $k => $v) {
                  $booking_det->venue_home_image = asset($this->uploadsFolder.'/venue/home_image/'.$v);
                }
              }
            $menu = DB::table('booking_items')->select('menu_item.id','booking_items.name','booking_items.user_id','qty','booking_items.price','menu_item.menu_image','menu_item.description')
                        ->join('menu_item','menu_item.id','booking_items.item_id')
                        ->where('booking_id',$input['booking_id'])
                        ->get()->toArray();
            $offer = DB::table('offers')->select('offers.*')
                        ->join('booking','offers.offer_id','booking.offer_id')
                        ->where('offers.offer_id',$booking_det->offer_id)
                        ->get()->toArray();

              foreach ($menu as $key => $v) {
                $menu[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                 $menu[$key]->price = Helper::numberFormat($menu[$key]->price);
              }
              $invitedUsers = DB::table('booking_invite_list')
                          ->where('booking_id','=',$input['booking_id'])
                          ->where('status','!=','R')
                          ->select('friend_id')
                          ->count();
              $booking_det->totalInvited_guest = $invitedUsers;

              $OwnUsers = DB::table('guest')
                          ->select('userid','firebase_id','username','name','last_name','profile')
                          ->where('userid',$booking_det->userid)
                          ->get();
              // $OwnUsers = DB::table('booking_invite_list')
              //             ->leftJoin('guest','booking_invite_list.user_id','guest.userid')
              //             ->where('booking_id','=',$input['booking_id'])
              //             ->where('booking_invite_list.status','A')
              //             ->select('guest.userid','guest.firebase_id','guest.username','guest.name','guest.last_name','guest.profile')
              //             ->take(1)->get();

              foreach ($OwnUsers as &$v) {
                    $v->profile = url("public/uploads/user/customer/" . $v->profile);
              }

              $booking_det->myfirebaseid = $OwnUsers;

              $acceptedUsers = DB::table('booking_invite_list')
                          ->leftJoin('guest','booking_invite_list.friend_id','guest.userid')
                          ->where('booking_id','=',$input['booking_id'])
                          ->where('booking_invite_list.status','A')
                          ->select('guest.userid','guest.firebase_id','guest.username','guest.name','guest.last_name','guest.profile')
                          ->get();

              foreach ($acceptedUsers as &$v) {
                    $v->profile = url("public/uploads/user/customer/" . $v->profile);
              }

              $booking_det->firebaseUsers = $acceptedUsers;

              $VenueUsers = DB::table('users')
                          ->select('id','firebase_id','username','first_name','last_name','profile_image','venue_id')
                          ->where('venue_id',$booking_det->venue_id)
                          ->get();

              foreach ($VenueUsers as &$v) {
                    $v->profile_image = url("public/uploads/user/customer/" . $v->profile_image);
              }

              $booking_det->venueUsers = $VenueUsers;

              $booking_det->allow_invite="0";
              if($input["userid"]==$booking_det->userid){
                $booking_det->allow_invite="1";
              }

              $booking_det->allow_add_on="1";
              // dd($bookingdateTime);
              if($currentDateTime>$bookingdateTime){
                $booking_det->allow_invite="0";
                // $booking_det->allow_add_on="0";
              }

              if($booking_det->checked_in == null){
                $booking_det->checked_in = "false";
              }
              if($booking_det->check_out == null){
                $booking_det->check_out = "false";
              }
              if($booking_det->tip == null){
                $booking_det->tip = "";
              }
              if($booking_det->waiter_id == null){
                $booking_det->waiter_id = "";
              }

               // if($input["userid"]==$booking_det->userid){
               //    $booking_det->allow_add_on="0";
               // }

              $booking_det->menus = $menu;
              $booking_det->offer = $offer;
              $data['booking']    = $booking_det;
              return parent :: api_response($data,true,'success','200');
          }else{
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "Booking is not found";
            return parent :: api_response((object)[],false,$res_msg,'200');
          }
        }
      }catch(\Exception $e){
        return parent::api_response((object)[],false,$e->getMessage(), 200);
      }
    }



    public function getDeliveryPrice(Request $request){
      try{
        $input = $request->all();
        $validator = Validator::make($input,[
                      "userid"      => "required",
                      "venue_id"  => "required",
                      "ad_id"  => "required"
                    ]);
        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }else{
          $userid = $input['userid'];
          // dd($userid);
          $lang_data  = parent :: getLanguageValues($request);
          $checkaddress = DB::table('address_users')->select('*')->where('userid',$input['userid'])->where('ad_id',$input['ad_id'])->first();
          if ($checkaddress) {
            function get_lat_long($address) {
              $address = str_replace(" ", "+", $address);
              $json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=AIzaSyC-rrvM1xaHf0yFymoOmcv6cvZenb58U0M");
              $json = json_decode($json);
              $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
              $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
              return $lat . ',' . $long;
            }
          $location = $checkaddress->address1.' '.$checkaddress->address2.' '.$checkaddress->state.' '.$checkaddress->add_zipcode;
          $latlong = get_lat_long($location); // create a function with the name "get_lat_long" given as below
          $map = explode(',', $latlong);

          // function to get  the address
          // echo $map[0];
          // echo '<br/>';
          // dd($map[1]);
          $updateaddress = DB::table('address_users')->where('ad_id', $input['ad_id']) ->update(['latitude' => $map[0],'longitude' => $map[1]]);
          $findaddress = DB::table('address_users')->select('ad_id','latitude','longitude')->where('userid',$input['userid'])->where('ad_id',$input['ad_id'])->first();
          // dd($findaddress);
          $checkvenue = DB::table('venue')->select('id','latitude','longitude','deliver_upto_1km','deliver_upto_5km','deliver_upto_10km','delivery_radius')->where('id',$input['venue_id'])->first();

          $latitude1 = '23.0443433';
          $longitude1 = '72.5146297' ;
          $latitude2 = '22.9925393';
          $longitude2 = '72.5432203';
          function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'miles') {
            $theta = $longitude1 - $longitude2;
            $distance = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
            $distance = acos($distance);
            $distance = rad2deg($distance);
            $distance = $distance * 60 * 1.1515;
            switch($unit) {
              case 'miles':
                break;
              case 'kilometers' :
                $distance = $distance * 1.609344;
            }
            return (round($distance,2));
          }

          $findaddressDistance = DB::table('address_users')
          ->select(
            DB::raw(
                'ROUND((6371 *
                acos(cos(radians(' . $checkvenue->latitude . ')) *
                cos(radians(`latitude`)) *
                cos(radians(`longitude`) -
                radians(' . $checkvenue->longitude . ')) +
                sin(radians(' . $checkvenue->latitude . ')) *
                sin(radians(`latitude`)))
                ),1) as distance'
            ))
          ->where('userid',$input['userid'])
          ->where('ad_id',$input['ad_id'])
          ->first();
          // dd($checkvenue->delivery_radius);
          // dd($findaddressDistance->distance);
          if ($findaddressDistance->distance <= $checkvenue->delivery_radius) {
            if ($findaddressDistance->distance <=1) {
              $new = $checkvenue->deliver_upto_1km;
              $data['DeliveryPrice']    = $new;
              return parent :: api_response($data,true,'success','200');
            }elseif($findaddressDistance->distance <=5) {
              // dd($findaddressDistance->distance);
              $new = $checkvenue->deliver_upto_5km;
              $data['DeliveryPrice']    = $new;
              return parent :: api_response($data,true,'success','200');
            }else {
              $new = $checkvenue->deliver_upto_10km;
              $data['DeliveryPrice']    = $new;
              return parent :: api_response($data,true,'success','200');
            }
            }else {
              $res_msg = "Invalid Address";
              return parent :: api_response((object)[],false,$res_msg,'200');
            }
          }else {
            $res_msg = "Address not found";
            return parent :: api_response((object)[],false,$res_msg,'200');
          }
        }
      }catch(\Exception $e){
        return parent::api_response((object)[],false,$e->getMessage(), 200);
      }
    }


    public function getfinalCheckout(Request $request){
      try{
        $input = $request->all();
        $validator = Validator::make($input,[
                      "userid"      => "required",
                      "booking_id"  => "required"
                    ]);
        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }else{
          $userid = $input['userid'];
          $lang_data  = parent :: getLanguageValues($request);
          $csvData    = array();
          if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $booking_det = DB::table('booking as b')
                        ->select('b.id as booking_id','b.userid','b.created_at','b.order_status','b.pay_all','b.party_members','b.is_user_invite','b.waiter_id','b.check_out','b.tip','b.promocode','b.is_offer_applied','b.party_organize','total_amount','currency','booking_method','instructions','b.offer_id','booking_slot_id','v.id as venue_id','v.name','v.address','v.venue_home_image','v.description','v.menu_type','b.booking_date','b.booking_time','b.qr_code','b.booking_code','g.name as purchased_by','g.last_name')
                        ->join('venue as v','v.id','b.venue_id')
                        ->join('guest as g','g.userid','b.userid')
                        ->where('b.id',$input['booking_id'])
                        ->get()->first();
          if(!empty($booking_det)) {
              $booking_det->total_amount = Helper::numberFormat($booking_det->total_amount);
              if($booking_det->booking_time=="00:00:00"){
               $booking_det->booking_time_txt= "";
              }
              else{
              $booking_det->booking_time_txt=$booking_det->booking_time;
              }
              $booking_det->booking_date_txt=$booking_det->booking_date;
              if($booking_det->menu_type!=1){
               $bookingdateTime = new DateTime($booking_det->booking_date." ".$booking_det->booking_time);
               $currentDateTime = new DateTime(date("Y-m-d")." ".date("H:i:s"));
              }
             else{
              $bookingdateTime = new DateTime($booking_det->booking_date);
              $currentDateTime = new DateTime(date("Y-m-d"));
             }
               if($booking_det->menu_type!=1){
              $booking_det->booking_time = date('D',strtotime($booking_det->booking_date)).'-'.date('h:i A',strtotime($booking_det->booking_time));
              }
              else{
               $booking_det->booking_time = date('D',strtotime($booking_det->created_at)).'-'.date('h:i A',strtotime($booking_det->created_at));
              }

              if(empty($booking_det->instructions)){
                 $booking_det->instructions = "NA";
              }
              $booking_det->booking_date = date('m-d-Y',strtotime($booking_det->booking_date));

              if($booking_det->userid==$input["userid"]){

              $booking_det->qr_code =  asset($this->uploadsFolder.'qrcode/'.$booking_det->qr_code);
              }
              else{

               //if invited
                $get_code = DB::table("booking_invite_list")->select("qr_code","booking_code")->where("friend_id","=",$input["userid"])->where("booking_id","=",$booking_det->booking_id)->first();

                if(!empty($get_code->qr_code)){
                 $booking_det->qr_code = asset($this->uploadsFolder.'qrcode/'.$get_code->qr_code);
                  if(!empty($get_code->booking_code)){
                  $booking_det->booking_code=$get_code->booking_code;
                  }
                  else{
                  $booking_det->booking_code="";
                  }
                }
                else{
                  $booking_det->qr_code ="";
                }

              }

              $booking_det->purchased_by = $booking_det->purchased_by.' '.$booking_det->last_name;
              if(!empty($booking_det->venue_home_image)){
                $club_picture = explode(',',$booking_det->venue_home_image);
                foreach ($club_picture as $k => $v) {
                  $booking_det->venue_home_image = asset($this->uploadsFolder.'/venue/home_image/'.$v);
                }
              }
            $menu = DB::table('booking_items')->select('menu_item.id','booking_items.name','qty','booking_items.price','menu_item.menu_image','menu_item.description')
                        ->join('menu_item','menu_item.id','booking_items.item_id')
                        ->where('booking_id',$input['booking_id'])
                        ->get()->toArray();
            $offer = DB::table('offers')->select('offers.*')
                        ->join('booking','offers.offer_id','booking.offer_id')
                        ->where('offers.offer_id',$booking_det->offer_id)
                        ->get()->toArray();

              foreach ($menu as $key => $v) {
                $menu[$key]->menu_image = asset($this->uploadsFolder.'/my_menu/'.$v->menu_image);
                 $menu[$key]->price = Helper::numberFormat($menu[$key]->price);
              }
              $invitedUsers = DB::table('booking_invite_list')
                          ->where('booking_id','=',$input['booking_id'])
                          ->where('status','!=','R')
                          ->select('friend_id')
                          ->count();
              $booking_det->totalInvited_guest = $invitedUsers;
              $booking_det->allow_invite="0";
              if($input["userid"]==$booking_det->userid){
                $booking_det->allow_invite="1";
              }
              $booking_det->allow_add_on="1";

              if($currentDateTime>$bookingdateTime){
                $booking_det->allow_invite="0";
                $booking_det->allow_add_on="0";
              }

               if($input["userid"]==$booking_det->userid){
                  $booking_det->allow_add_on="0";
               }
              $booking_det->menus = $menu;
              $booking_det->offer = $offer;
              $data['booking']    = $booking_det;
              return parent :: api_response($data,true,'success','200');
          }else{
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "Booking is not found";
            return parent :: api_response((object)[],false,$res_msg,'200');
          }
        }
      }catch(\Exception $e){
        return parent::api_response((object)[],false,$e->getMessage(), 200);
      }
    }

    public function getFriendsForPartyInvite(Request $request){
      try{
        $lang_data  = parent :: getLanguageValues($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
          $csvData = $lang_data['csvData'];
        }
        $input      = $request->all();
        $validator  = Validator::make($input,[
                        "userid"      => "required",
                        "booking_id"  => "required",
                      ]);

        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
       }else{
          $booking_id = $input['booking_id'];
          $check_user = Guest::where('userid','=',$input['userid'])->select('userid','name','email','status')->get();

          if(empty($check_user[0]->userid)){
            $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";
            return parent :: api_response((object)[],false,$res_msg, 200);
          }elseif((int)$check_user[0]->status == 2){
            $res_msg = isset($csvData['Your_account_is_not_active_yet_please_contact_admin']) ? $csvData['Your_account_is_not_active_yet_please_contact_admin'] : "";
            return parent :: api_response((object)[],false,$res_msg, 200);
          }

          $limit = 10;
          $page_no   = isset($input['page_no']) && !empty($input['page_no']) ? $input['page_no'] : 0;
          $offset    = $limit * $page_no;
          if(isset($input['name'])&&!empty($input['name'])){
            $users = DB::table('guest as g')
                ->Join('user_friends as uf', 'g.userid', '=', 'uf.friend_id')
                ->leftjoin('booking_invite_list AS bil',function($join) use ($booking_id){
                      $join->on('bil.friend_id','uf.friend_id');
                      $join->where('bil.status', '!=','R');
                      $join->where('bil.booking_id',$booking_id);
                });

                //->where('uf.status','=','A')
                $users =$users->where('g.userid','!=',$input["userid"])->where('g.status','=',1)->whereNull("g.deleted_at")
                ->where('uf.is_friend','=',1);
                //if(isset($input['name'])&&!empty($input['name'])){
                  $users = $users->where('g.name','LIKE','%'.$input['name'].'%');
                //}

          $users =  $users->skip($offset)->take($limit)
                        ->select('g.userid','g.name','g.profile','g.status','g.dob','g.longitude','g.latitude','uf.is_friend','uf.status as friend_status',DB::raw('floor(DATEDIFF(CURDATE(),g.dob) /365) as age'),'bil.id AS is_invited' )
                    ->groupBy("g.userid")->get()->toArray();

          }
          else{

          //code to show user freing
          $users = DB::table('guest as g')
                ->Join('user_friends as uf', 'g.userid', '=', 'uf.friend_id')
                ->leftjoin('booking_invite_list AS bil',function($join) use ($booking_id){
                      $join->on('bil.friend_id','uf.friend_id');
                      $join->where('bil.status', '!=','R');
                      $join->where('bil.booking_id',$booking_id);
                })

                ->where('uf.status','=','A')->where('g.userid','!=',$input["userid"])->where('g.status','=',1)->whereNull("g.deleted_at")
                ->where('uf.is_friend','=',1);

                //if(isset($input['name'])&&!empty($input['name'])){
                  //$users = $users->where('g.name','LIKE','%'.$input['name'].'%');
                //}
                //else{
                $users =  $users->where('uf.user_id','=',$input['userid']);
                //}
          $users =  $users->skip($offset)->take($limit)
                        ->select('g.userid','g.name','g.profile','g.status','g.dob','g.longitude','g.latitude','uf.is_friend','uf.status as friend_status',DB::raw('floor(DATEDIFF(CURDATE(),g.dob) /365) as age'),'bil.id AS is_invited' )
                    ->get()->toArray();
                    //code to show all freind

                  /*  $users = DB::table('guest as g')
                ->Join('user_friends as uf', 'g.userid', '=', 'uf.friend_id')
                ->leftjoin('booking_invite_list AS bil',function($join) use ($booking_id){
                      $join->on('bil.friend_id','uf.friend_id');
                      $join->where('bil.status', '!=','R');
                      $join->where('bil.booking_id',$booking_id);
                });

                //->where('uf.status','=','A')
                $users =$users->where('g.userid','!=',$input["userid"])->where('g.status','=',1)->whereNull("g.deleted_at")
                ->where('uf.is_friend','=',1);
                //if(isset($input['name'])&&!empty($input['name'])){
                  $users = $users->where('g.name','LIKE','%'.$input['name'].'%');
                //}

          $users =  $users->skip($offset)->take($limit)
                        ->select('g.userid','g.name','g.profile','g.status','g.dob','g.longitude','g.latitude','uf.is_friend','uf.status as friend_status',DB::raw('floor(DATEDIFF(CURDATE(),g.dob) /365) as age'),'bil.id AS is_invited' )
                    ->groupBy("g.userid")->get()->toArray();*/

            }


          // print_r($users);die();
          if(empty($users)){
            $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
            return parent :: api_response((object)[],false,$res_msg, 200);
          }else{
            foreach ($users as $key => $user) {
              /*code for profile goes here*/
              $ouser     = $user->userid;
              $loginuser = $input["userid"];
              $block_user = DB::table("blocked_user")->select("id");
              $block_user =$block_user->where(
              function($block_user) use ($ouser,$loginuser){
                $block_user = $block_user->where("user_id","=",$loginuser)->Where("blocked_user_id","=",$ouser);
                $block_user = $block_user->orwhere("user_id","=",$ouser)->Where("blocked_user_id","=",$loginuser);
              }
              );
              $block_user=$block_user->where("blocked_user_type","=","4")->whereNull("deleted_at")->first();



              $profile = "";
              if(empty($block_user->id)){
              if(!empty($user->profile)){
                $profile =  asset("public/uploads/user/customer/".$user->profile);
              }
              else{
                $profile = asset("public/default.png");
              }
              $is_relate = "";

              if(empty($user->is_invited)){
                $user->is_invited = 0;
              }else{
                $user->is_invited = 1;
              }
              $users[$key]->profile = $profile;
               $users[$key]->booking_id =$booking_id;
              $users[$key]->is_relate = $is_relate;
              }
              else{
                unset($users[$key]);
             }
            }
            $data["user"]=array_values($users);
            //$data["booking_id"]=$booking_id;
            $res_msg = isset($csvData['User_friend_list_retrieved_successfully']) ? $csvData['User_friend_list_retrieved_successfully'] : "";
            return parent :: api_response($data,true,$res_msg, 200);
          }
        }
      }
      catch(\Exception $e){
        $res_msg = $e->getMessage();
        return parent :: api_response((object)[],true,$res_msg, 200);
      }
    }
    public function sendPartyInvitation(Request $request){
      try{
        $lang_data  = parent :: getLanguageValues($request);
        $csvData    = array();
        if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
          $csvData = $lang_data['csvData'];
        }
        $input      = $request->all();
        $validator  = Validator::make($input,[
          'booking_id'      => "required",
          'friend_id'       => "required"
        ]);

        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response([],false,$err_msg, 200);
        }else{
          $check_user = Guest::where('userid','=',$input['userid'])->select('userid','name','email','status')->first();
          $check_friend_user = Guest::where('userid','=',$input['friend_id'])->select('userid','name','email','status','device_type','device_id','push_notification')->first();

          $noti_msg = $check_user->name." has sent you an invite to a Party!";
          $subject = "New Party Invite!";

          $booking_data = DB::table('booking as b')
                          ->select('b.id as booking_id','prl.status','prl.id')
                          ->leftJoin('booking_invite_list as prl', 'b.id', '=', 'prl.booking_id')
                          ->where('b.userid',$input['userid'])
                          ->where('prl.friend_id',$input['friend_id'])
                          ->where('b.id',$input['booking_id'])
                          ->first();

          if(isset($booking_data->id) && !empty($booking_data->id)){
            //if Invitation has rejected in previous
            if($booking_data->status == "P"){
              $res_msg = isset($csvData['You_already_send_party_invitaion_to_user']) ? $csvData['You_already_send_party_invitaion_to_user'] : "";
                return parent :: api_response([],false,$res_msg,'200');
            }
            elseif($booking_data->status == "A"){
              $res_msg = isset($csvData['User_accepted_your_party_invitation']) ? $csvData['User_accepted_your_party_invitation'] : "";
              return parent :: api_response([],false,$res_msg,'200');
            }
             DB::table('booking_invite_list')->where('id', $booking_data->id)
                ->update(['status' => 'P']);

            $this->sendPartyInviteNotification($request, $input['booking_id'], $input['userid'], $input['friend_id'] ,$check_friend_user->name ,$check_friend_user->device_type , $check_friend_user->device_id,$check_friend_user->push_notification);

            $res_msg = isset($csvData['Party_invitation_send_successfully']) ? $csvData['Party_invitation_send_successfully'] : "";

            return parent :: api_response([],true,$res_msg,'200');
          }else{
           $invId = DB::table('booking_invite_list')->insertGetId(
                  ['user_id' => $input['userid'], 'friend_id' => $input['friend_id'],'booking_id'=> $input['booking_id'] ]
                 );
            $this->sendPartyInviteNotification($request, $input['booking_id'], $input['userid'], $input['friend_id'] ,$check_user->name ,$check_friend_user->device_type , $check_friend_user->device_id,$check_friend_user->push_notification,$invId);

            $res_msg = isset($csvData['Party_invitation_send_successfully']) ? $csvData['Party_invitation_send_successfully'] : "";
            return parent :: api_response([],true,$res_msg,'200');
          }
        }
      }catch(\Exception $e){
        return parent :: api_response([],false,$e->getMessage(),'200');
      }
    }

    public function sendPartyInviteNotification($request, $booking_id, $sender_id , $user_id ,$name ,$device_type , $device_id, $is_push_notification,$id=False)
     {
        //get venue
        $get_venue = DB::table("booking as b")->select("v.name")->leftJoin("venue as v","b.venue_id","v.id")->where("b.id","=",$booking_id)->first();
        $venue_name = "";
        if(!empty($get_venue->name)){
         $venue_name = $get_venue->name;
        }

        $language_code  = "";
        $langCodeData   = parent :: getUserCurrentLanguage($user_id);
        if( isset($langCodeData) && !empty($langCodeData)){
          $language_code = $langCodeData['iso2_code'];
        }else{
          $language_code = "en";
        }
        $friend_lang_data = parent :: getLanguageValues($request , $language_code);
        $friendsCsvData   = array();
        if( ($friend_lang_data['status'] == 1) && !empty($friend_lang_data['csvData'])){
          $friendsCsvData = $friend_lang_data['csvData'];
        }
        $msg = isset($friendsCsvData['Notify_msg_send_party_invite'])?$friendsCsvData['Notify_msg_send_party_invite'] : " has invited you to join them at (VenueName)!";

        $noti_msg =  str_replace("@#$#",$name,$msg);
        $noti_msg =  str_replace("%%",$venue_name,$noti_msg);
        $subject = isset($friendsCsvData['Notify_sub_msg_send_party_invite']) ? $friendsCsvData['Notify_sub_msg_send_party_invite'] : "You've Been Invited!";
        $notify_data =  array(
                          "booking_id"       => $booking_id,
                          "owner_id"         => $sender_id,
                          "user_id"          => $user_id,
                          "notification_key" => 5
                        );
        $json_notify_data = json_encode($notify_data);
        $device_type1 = ($device_type == 1 ) ? "ios" : "android";
        if((int)$is_push_notification == 1){
          if($device_type==1){
            $res_notification = Helper::sendNotification($device_type,$device_id, $noti_msg,$subject,$json_notify_data,"userapp");
          }
          else{
            $payload  = array(
                          "body"=>$noti_msg,
                          "titile"=> $subject
                        );
            $dataPayload  = array(
                              "body" => $noti_msg,
                              "title"=> $subject,
                              "booking_id"       => $booking_id,
                              "owner_id"         => $sender_id,
                              "user_id"          => $user_id,
                              "notification_key" => 5

                            );
            $notify_data  = array(
                            "to" => $device_id,
                            "notification"=>$payload,
                            "data"=>$dataPayload
                          );
            $send_notification = Helper::fcmNotification($noti_msg, $notify_data, "userapp");
          }
        }
        DB::table('user_notification')->insert([
            ['message' => $noti_msg, 'user_id' => $user_id, 'subject'=>$subject, "device_type"=>$device_type1, "notification_key"=> 5, "data" => $json_notify_data,"notification_type"=>"1",'user_type'=>4,"request_id" =>$id]
        ]);
        return 1;
      }

     public function sendPartyInviteOld(Request $request){
         $lang_data = parent :: getLanguageValues($request);
         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'booking_id'      => "required",
             'friend_id'       => "required"
         ]);

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{

             $check_user = DB::table("guest")->where('userid','=',$input['userid'])->select('userid','name','email','status')->get();

             $check_friend_user = DB::table("guest")->where('userid','=',$input['friend_id'])->select('userid','name','email','status','device_type','device_id','push_notification')->get();


             $noti_msg = $check_user[0]->name." has sent you an invite to a Party!";

             $subject = "New Party Invite!";


             $booking_data = DB::table('order_history as oh')
              ->leftJoin('party_req_list as prl', 'oh.id', '=', 'prl.booking_id')
              ->where('oh.user_id',$input['userid'])
              ->where('prl.friend_id',$input['friend_id'])
              ->where('oh.id',$input['booking_id'])
              ->select('oh.id as booking_id','prl.status','prl.id')
              ->get()->toArray();


            if( isset($booking_data[0]->id) && !empty($booking_data[0]->id) ){

                if(strtolower($booking_data[0]->status) == "p"){
                    $res_msg = isset($csvData['You_already_send_party_invitaion_to_user']) ? $csvData['You_already_send_party_invitaion_to_user'] : "";
                    return parent :: api_response([],false,$res_msg,'200');
                }

                if(strtolower($booking_data[0]->status) == "a"){
                    $res_msg = isset($csvData['User_accepted_your_party_invitation']) ? $csvData['User_accepted_your_party_invitation'] : "";

                    return parent :: api_response([],false,$res_msg,'200');
                }

                DB::table('party_req_list')
                ->where('id', $booking_data[0]->id)
                ->update(['status' => 'P']);

                $this->sendPartyInviteNotification($request, $input['booking_id'], $input['userid'], $input['friend_id'] ,$check_friend_user[0]->name ,$check_friend_user[0]->device_type , $check_friend_user[0]->device_id,$check_friend_user[0]->push_notification);

                /*$res_notification = parent :: sendNotification($check_friend_user[0]->device_type , $check_friend_user[0]->device_id, $noti_msg, $subject);

                DB::table('user_notification')->insert([

                        ['message' => $noti_msg, 'user_id' => $input['friend_id'], 'subject'=>$subject]
                ]);*/

                $res_msg = isset($csvData['Party_invitation_send_successfully']) ? $csvData['Party_invitation_send_successfully'] : "";

                return parent :: api_response([],true,$res_msg,'200');
            }else{

                DB::table('party_req_list')->insert(
                     ['user_id' => $input['userid'], 'friend_id' => $input['friend_id'],'booking_id'=> $input['booking_id'] ]
                 );

                $this->sendPartyInviteNotification($request, $input['booking_id'], $input['userid'], $input['friend_id'] ,$check_user[0]->name ,$check_friend_user[0]->device_type , $check_friend_user[0]->device_id,$check_friend_user[0]->push_notification);

               /* $res_notification = parent :: sendNotification($check_friend_user[0]->device_type , $check_friend_user[0]->device_id, $noti_msg, $subject);

                DB::table('user_notification')->insert([
                     ['message' => $noti_msg, 'user_id' => $input['friend_id'], 'subject'=>$subject]
                ]);
              */
                $res_msg = isset($csvData['Party_invitation_send_successfully']) ? $csvData['Party_invitation_send_successfully'] : "";

                return parent :: api_response([],true,$res_msg,'200');
              }
         }
     }

    public function unsendPartyInvite(request $request){
      //try{
          $input = $request->all();
          $validator  = Validator :: make($input,[
             'userid'      => "required",
             'booking_id'      => "required",
             'friend_id'       => "required"
         ]);
          $subject =


        $guest_det = DB::table("guest")->where("userid","=",$input["friend_id"])->first();

        $guest_det_login = DB::table("guest")->where("userid","=",$input["userid"])->first();
          $notify_data = array();
          $json_notify_data = json_encode($notify_data);
         if(!empty($guest_det->device_id)){

         if(!empty($guest_det->device_type=="1")){
          $subject          = "Your freind ".$guest_det_login->name." has cancelled your party invitation.";

          $res_notification = Helper::sendNotification($guest_det->device_type ,$guest_det[0]->device_id, $subject, $subject , $json_notify_data,"userapp");
         }
         else{
          $notificationPayload = array(
                             "body"=>$subject,
                             "titile"=> $subject
                            );

            $dataPayload = array(
                "body" => $subject,
                "title"=> $subject,

            );

            $notify_data = array(
                "to" => $guest_det_login->device_id,
                "notification"=>$notificationPayload,
                "data"=>$dataPayload
            );
                       //$json_notify_data = json_encode($notify_data);
                       $send_notification = Helper::fcmNotification($noti_msg, $notify_data, "userapp");
                       }

         }
            $insert = DB::table('user_notification')->insert([

                            ['message' => $subject, 'user_id' => $guest_det_login->userid, 'subject' => $subject, "device_type" => $guest_det->device_type, "notification_key" =>9, "data" => $json_notify_data]
                        ]);

          $res_msg = isset($csvData[' Party_invitation_unsend_successfully']) ? $csvData['Party_invitation_unsend_successfully'] : "you have successfully uninvited this freind.";
            return parent :: api_response([],true,$res_msg,'200');
        /* }
         catch(Exception $e){
           return parent :: api_response([],false,$e->getMessage(),'200');
      }*/
    }

    public function sendPartyInvite(Request $request){
       try{
         // dd($request->all());
         $lang_data = parent :: getLanguageValues($request);
         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'booking_id'      => "required",

          ]);

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{
        $checkfreind = parent::checkKeyExist("friend_id",$input);
        if(!empty($checkfreind)){
          $err_msg = $checkfreind;
            return parent :: api_response([], false, $err_msg, 200);
        }

        // $checkunfriend_id = parent::checkKeyExist("unfriend_id",$input);
        // if(!empty($checkunfriend_id)){
        //   $err_msg = $checkunfriend_id;
        //     return parent :: api_response([], false, $err_msg, 200);
        // }
                $explode  = explode(",",$input["friend_id"]);
                // $explode_unfreind  = explode(",",$input["unfriend_id"]);

                if(!empty($explode)){
                 $check_user = DB::table("guest")->where('userid','=',$input['userid'])->select('userid','name','email','status')->get();


                foreach($explode as $v){
                $invite_id =DB::table('booking_invite_list')
                ->insertGetId(['user_id' =>$input['userid'],"friend_id"=>$v,"booking_id"=>$input["booking_id"],"status"=>"P"]);


                $check_friend_user = DB::table("guest")->where('userid','=',$v)->select('userid','name','email','status','device_type','device_id','push_notification')->get();

                if(!empty($check_friend_user[0]->userid)){
                $this->sendPartyInviteNotification($request, $input['booking_id'], $input['userid'], $v ,$check_user[0]->name ,$check_friend_user[0]->device_type , $check_friend_user[0]->device_id,$check_friend_user[0]->push_notification,$invite_id);
                 }

                }
               }//code to send invitation end

               //code to undo notification start
      //          if(!empty($explode_unfreind)){
      //          foreach($explode_unfreind as $v){
      //           $unfreind = DB::table("booking_invite_list")->where("friend_id","=",$v)->where("booking_id","=",$input['booking_id'])->delete();
      //           $guest_det = DB::table("guest")->where("userid","=",$v)->first();
      //           $guest_det_login = DB::table("guest")->where("userid","=",$input["userid"])->first();
      //            $notify_data = array();
      //     $json_notify_data = json_encode($notify_data);
      //     if(!empty($guest_det->device_id)){
      //     $subject          = "Your freind ".$guest_det_login->name." has cancelled your party invitation.";
      //    if(!empty($guest_det->device_type=="1")){
      //
      //
      //     $res_notification = Helper::sendNotification($guest_det->device_type ,$guest_det->device_id, $subject, $subject , $json_notify_data,"userapp");
      //    }
      //    else{
      //     $notificationPayload = array(
      //                        "body"=>$subject,
      //                        "titile"=> $subject
      //                       );
      //
      //       $dataPayload = array(
      //           "body" => $subject,
      //           "title"=> $subject,
      //
      //       );
      //
      //       $notify_data = array(
      //           "to" => $guest_det->device_id,
      //           "notification"=>$notificationPayload,
      //           "data"=>$dataPayload
      //       );
      //                  //$json_notify_data = json_encode($notify_data);
      //                  $send_notification = Helper::fcmNotification($subject, $notify_data, "userapp");
      //                  }
      //
      //
      //     $insert = DB::table('user_notification')->insert([
      //
      //                       ['message' => $subject, 'user_id' => $guest_det_login->userid, 'subject' => $subject, "device_type" => $guest_det->device_type, "notification_key" =>9, "data" => $json_notify_data]
      //                   ]);
      //     }
      //   }
      // }
       //code for unfreind end
        $res_msg = isset($csvData['Party_invitation_update_successfully']) ? $csvData['Party_invitation_update_successfully'] : "Party invitation updated successfully.";

                return parent :: api_response([],true,$res_msg,'200');
      }
      }
         catch(Exception $e){
           return parent :: api_response([],false,$e->getMessage(),'200');
      }

     }

    public function acceptInvitation(Request $request){
      //try{
        $input      = $request->all();
        $validator  = Validator::make($input,[
                          'userid'     => "required",
                          'booking_id' => "required"
                      ]);

        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }else{
          $lang_data  = parent::getLanguageValues($request);
          $csvData    = array();
          if(($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $check_request = DB::table('booking_invite_list')->WHERE('booking_id',$input['booking_id'])->WHERE('friend_id',$input['userid'])->get();

          if($check_request->count()>0){
            $invite_data = $check_request->first();
            if($invite_data->status == 'A'){
              $res_msg = isset($csvData['You_already_accept_this_invitaion']) ? $csvData['You_already_accept_this_invitaion'] : "You have already accept this invitation";
              return parent :: api_response((object)[],false,$res_msg,'200');
            }elseif($invite_data->status == 'R'){
              $res_msg = isset($csvData['You_already_reject_this_invitaion']) ? $csvData['You_already_reject_this_invitaion'] : "You have already reject this invitation";
              return parent :: api_response((object)[],false,$res_msg,'200');
            }else{
              $update = DB::table('booking_invite_list')
                          ->where('id',$invite_data->id)
                          ->update(['status'=>'A']);

              $type = "1";
             $deletereq= Helper::DeleteNotification($type,$invite_data->id);

              $booking_det = DB::table("booking")->where("id","=",$input["booking_id"])->first();
              //qr code
              $booking_code = $this->generateRandomBookingCode();
              $bookingcode =$booking_code."#".$input["booking_id"]."#".$input["userid"]."#2";
              $qr_data['booking_code'] = $bookingcode;
              $qr_data['booking_id']  =  $booking_det->id;
              $qr_data['user_id']     = $input['userid'];
              $qr_image = \QrCode::format('png')
                         ->size(200)->errorCorrection('H')
                         ->generate(json_encode($qr_data));
              $b = "data:image/png;base64,".base64_encode($qr_image);
              $qr_image   = imagecreatefrompng($b);
              $image_name = "qr_".$booking_code.".png";
              imagepng($qr_image, "public/uploads/qrcode/".$image_name);

              $saveQrCode = DB::table('booking_invite_list')->where('booking_id',$booking_det->id)->where('id',$invite_data->id)->update(['qr_code'=>$image_name,"booking_code"=>$bookingcode]);

              //
              if($update){
                $receiver_rec = DB::table('guest')->select("userid","name","device_type","device_id","push_notification")->where("userid","=",$invite_data->user_id)->first();

                $user_rec = DB::table('guest')->select("name")->where('userid','=',$input['userid'])->first();

                //get venue
        $get_venue = DB::table("booking as b")->select("v.name","b.userid")->leftJoin("venue as v","b.venue_id","v.id")->where("b.id","=",$input['booking_id'])->first();
        $venue_name = "";
        if(!empty($get_venue->name)){
         $venue_name = $get_venue->name;
        }

        $language_code = "";
            $langCodeData = parent :: getUserCurrentLanguage($get_venue->userid);
            if( isset($langCodeData) && !empty($langCodeData)){
              $language_code = $langCodeData['iso2_code'];
            }else{
              $language_code = "en";
            }

            $friend_lang_data = parent :: getLanguageValues($request , $language_code);

                $msg = isset($friend_lang_data['accept_party_invite_message'])? $friend_lang_data['accept_party_invite_message'] : "@#$# has Accepted your invite to %%%! Send them a Memory and show them your excited face!";

                $noti_msg =  str_replace("@#$#",$user_rec->name,$msg);
                $noti_msg =  str_replace("%%%",$venue_name,$noti_msg);

                $sub_notify_msg = isset($friend_lang_data['accept_party_invite']) ? $friend_lang_data['accept_party_invite'] : "Invite Accepted!";

                $subject = str_replace("@#$#",$user_rec->name ,$sub_notify_msg);

                $notify_data = array(
                                "user_id"      => $receiver_rec->userid,
                                "notification_key" =>200
                              );
                $json_notify_data = json_encode($notify_data);
                $device_type  = ($receiver_rec->device_type == 1 ) ? "ios" : "android";
                $device_id    = $receiver_rec->device_id;

                if($receiver_rec->push_notification == 1){
                  if($receiver_rec->device_type==1){
                    $res_notification = Helper:: sendNotification($receiver_rec->device_type , $device_id, $noti_msg, $subject , $json_notify_data,"userapp");
                  }
                  else{
                    $notificationPayload = array(
                                            "body"=>$noti_msg,
                                            "titile"=>$subject
                                          );

                    $dataPayload = array(
                                    "user_id" => $receiver_rec->userid,
                                    "notification_key" =>200,
                                    "body"=>$noti_msg,
                                    "titile"=>$subject
                                  );

                    $notify_data = array(
                                    "to" => $device_id,
                                    "notification"=>$notificationPayload,
                                    "data"=>$dataPayload,
                                    "notification_type"=>"1"
                                  );
                    $send_notification = Helper::fcmNotification($noti_msg, $notify_data, "userapp");
                  }
                }
                DB::table('user_notification')->insert([
                  ['message' => $noti_msg, 'user_id' => $receiver_rec->userid, 'subject'=>$subject, "device_type"=>$device_type, "notification_key"=>8, "data" => $json_notify_data,"notification_type"=>"1","user_type"=>4]
                ]);
                $res_msg = isset($csvData['Party_invitation_accepted_successfully']) ? $csvData['Party_invitation_accepted_successfully'] : "";
                return parent :: api_response((object)[],true,$res_msg,'200');

              }else{
                $res_msg = isset($csvData['Status_is_not_update']) ? $csvData['Status_is_not_update'] : "Status is not update.Please try again";
                return parent :: api_response((object)[],false,$res_msg,'200');
              }
            }
          }else{
            $res_msg = isset($csvData['You_have_not_invited']) ? $csvData['You_have_not_invited'] : "You have not invited";
            return parent :: api_response((object)[],false,$res_msg,'200');
          }
        }
      /*}catch(\Exception $e){
        $res_msg =$e->getMessage();
        return parent::api_response((object)[],false,$res_msg,'200');
      }*/
    }

    public function rejectInvitation(Request $request){
      try{
        $input      = $request->all();
        $validator  = Validator::make($input,[
                          'userid'     => "required",
                          'booking_id' => "required"
                      ]);

        if($validator->fails()){
          $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
          return parent :: api_response((object)[],false,$err_msg, 200);
        }else{
          $lang_data  = parent::getLanguageValues($request);
          $csvData    = array();
          if(($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
          }

          $check_request = DB::table('booking_invite_list')->WHERE('booking_id',$input['booking_id'])->WHERE('friend_id',$input['userid'])->get();

          if($check_request->count()>0){
            $invite_data = $check_request->first();
            if($invite_data->status == 'A'){
              $res_msg = isset($csvData['You_already_accept_this_invitaion']) ? $csvData['You_already_accept_this_invitaion'] : "You have already accept this invitation";
              return parent :: api_response((object)[],false,$res_msg,'200');
            }elseif($invite_data->status == 'R'){
              $res_msg = isset($csvData['You_already_reject_this_invitaion']) ? $csvData['You_already_reject_this_invitaion'] : "You have already reject this invitation";
              return parent :: api_response((object)[],false,$res_msg,'200');
            }else{


             $type = "1";
             $deletereq= Helper::DeleteNotification($type,$invite_data->id);


              $update = DB::table('booking_invite_list')
                          ->where('id',$invite_data->id)
                          ->update(['status'=>'R']);




              if($update){
                $receiver_rec = DB::table('guest')->select("userid","name","device_type","device_id","push_notification")->where("userid","=",$invite_data->user_id)->first();

                $user_rec = DB::table('guest')->select("name")->where('userid','=',$input['userid'])->first();

                $msg = isset($csvData['reject_party_invite'])?$csvData['reject_party_invite'] : "@#$# has rejected your party invitation.";

                $noti_msg =  str_replace("@#$#",$user_rec->name,$msg);

                $sub_notify_msg = isset($csvData['reject_party_invite']) ? $csvData['reject_party_invite'] : "@#$# has rejected your party invitation.";

                $subject = str_replace("@#$#",$user_rec->name ,$sub_notify_msg);

                $notify_data = array(
                                "user_id"      => (!empty($receiver_rec->userid) ? $receiver_rec->userid :'' ),
                                "notification_key" =>201
                                );

                $json_notify_data = json_encode($notify_data);
                $device_type  = ($receiver_rec->device_type == 1 ) ? "ios" : "android";
                $device_id    = $receiver_rec->device_id;

                if($receiver_rec->push_notification == 1){
                  if($receiver_rec->device_type==1){
                    $res_notification = Helper:: sendNotification($receiver_rec->device_type , $device_id, $noti_msg, $subject , $json_notify_data,"userapp");
                  }
                  else{
                    $notificationPayload = array(
                                            "body"=>$noti_msg,
                                            "title"=>$subject
                                          );

                    $dataPayload = array(
                                    "user_id" => $receiver_rec->userid,
                                    "notification_key" =>201,
                                    "body"=>$noti_msg,
                                    "title"=>$subject
                                  );

                    $notify_data = array(
                                    "to" => $device_id,
                                    "notification"=>$notificationPayload,
                                    "data"=>$dataPayload,
                                    "notification_type"=>"1"
                                  );
                    $send_notification = Helper::fcmNotification($noti_msg, $notify_data, "userapp");
                  }
                }
                DB::table('user_notification')->insert([
                  ['message' => $noti_msg, 'user_id' => $receiver_rec->userid, 'subject'=>$subject, "device_type"=>$device_type, "notification_key"=>9, "data" => $json_notify_data,"notification_type"=>"1","user_type"=>4]
                ]);
                $res_msg = isset($csvData['Party_invitation_rejected_successfully']) ? $csvData['Party_invitation_rejected_successfully'] : "";
                return parent :: api_response((object)[],true,$res_msg,'200');

              }else{
                $res_msg = isset($csvData['Status_is_not_update']) ? $csvData['Status_is_not_update'] : "Status is not update.Please try again";
                return parent :: api_response((object)[],false,$res_msg,'200');
              }
            }
          }else{
            $res_msg = isset($csvData['You_have_not_invited']) ? $csvData['You_have_not_invited'] : "You have not invited";
            return parent :: api_response((object)[],false,$res_msg,'200');
          }
        }
      }catch(\Exception $e){
        $res_msg =$e->getMessage();
        return parent::api_response((object)[],false,$res_msg,'200');
      }
    }

     public function getBookingQrCode(Request $request){
         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'booking_id'      => "required"
         ]);

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() ,$request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{
                $final_response = array();

                $booking_data = DB::table('party_data')
                                ->where('u_id',$input['userid'])
                                ->where('booking_id',$input['booking_id'])
                                ->where('is_delete','=','0')
                                ->select('booking_code','qr_code')
                                ->get()->toArray();

                if( isset($booking_data[0]->booking_code) && !empty($booking_data[0]->booking_code) ){

                      $qr_code = "";
                      if( isset($booking_data[0]->qr_code) && !empty($booking_data[0]->qr_code) ){

                         $qr_code = url($this->uploadsFolder)."/qrcode/".$booking_data[0]->qr_code;
                      }

                      $final_response = array(
                                            "title"=>trans("message.api_response_messages.Qr_code_screen_title"),
                                            "sub_title"=>trans("message.api_response_messages.Qr_code_screen_sub_title"),
                                            "booking_code"=>$booking_data[0]->booking_code,
                                            "qr_code"=>$qr_code,
                                            "bottom_text"=>trans("message.api_response_messages.Qr_code_screen_bottom_text"),
                                    );
                   $res_msg = isset($csvData['Booking_data_retrieved_successfully']) ? $csvData['Booking_data_retrieved_successfully'] : "";
                   return parent :: api_response($final_response,true,$res_msg,'200');
                }else{
                    $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                    return parent :: api_response([],false,$res_msg,'200');
                }
         }
    }









     public function userPartyInvitesold(Request $request){

         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required"
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{
            $current_date = date('Y-m-d');

            $booking_details = DB::table('booking_invite_list as prl')
              ->leftJoin('order_history as oh', 'oh.id', '=', 'prl.booking_id')
              ->leftJoin('package as p', 'oh.package_id', '=', 'p.id')
              ->leftJoin('user as u', 'oh.user_id', '=', 'u.userid')
              ->where('prl.friend_id',$input['userid'])
              ->where('prl.status','=','P')
              ->whereDate('oh.party_date', '>= ', $current_date)
              ->select('u.name','u.profile','oh.party_date','oh.club_id','oh.start_time','prl.booking_id',"prl.user_id")
              ->get()->toArray();
              //print_r($booking_details);die;

            if( isset($booking_details) && !empty($booking_details) ){

              foreach ($booking_details as $key => $value) {

                $club_name = "";
                $club_address = "";
                $club_city = "";
                $club_state = "";
                $club_country = "";

                $club_rec = DB::table('club as c')
                            ->leftJoin('form_language as fl', 'c.club_id', '=', 'fl.type_id')
                            ->leftJoin('language as l', 'fl.language_id', '=', 'l.id')
                            ->select('meta_key','meta_value')
                            ->where('c.club_id','=',$value->club_id)
                            ->where('fl.type_id','=',$value->club_id)
                            ->where('fl.type','=','5')
                            ->where('l.iso2_code','=',$language_code)
                            ->get()->toArray();

                 foreach ($club_rec as $k1 => $val1) {

                      if($val1->meta_key == "club_name"){
                        $club_name = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_address"){
                        $club_address = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_state"){
                         $club_state = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_city"){
                         $club_city = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_country"){
                         $club_country = $val1->meta_value;
                      }
                  }

                  $profile = "";

                  if(!empty($value->profile)){
                       $exp = substr($value->profile,0,8);
                       //if($exp!="https://"){
                        // $profile = url($this->uploadsFolder)."/user/".$value->profile;
                      // }else{
                         $profile =  $value->profile;
                      // }

                  }

                  $booking_details[$key]->club_name = $club_name;
                  $booking_details[$key]->club_address = $club_address;

                  $booking_details[$key]->profile = $profile;

                  $booking_details[$key]->start_time = date('h:i A',strtotime($value->start_time));

                  $booking_details[$key]->party_date = $value->party_date;

              }
              if(!empty($booking_details)){

                $res_msg = isset($csvData['Party_invites_fetched_successfully']) ? $csvData['Party_invites_fetched_successfully'] : "";

                return parent :: api_response($booking_details,true,$res_msg,'200');
              }else{
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response([],false,$res_msg,'200');
              }

            }else{
              $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
              return parent :: api_response([],false,$res_msg,'200');
            }
         }
     }

     public function userPartyInvites(Request $request){
          try{
         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required"
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response((object)[],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{
            $current_date = date('Y-m-d');

            $booking_details = DB::table('booking_invite_list as prl')
              ->leftJoin('booking as b', 'b.id', '=', 'prl.booking_id')

              ->leftJoin('guest as u', 'b.userid', '=', 'u.userid')
              ->leftJoin('venue as v', 'b.venue_id', '=', 'v.id')
              ->where('prl.friend_id',$input['userid'])
              ->where('prl.status','=','P')
              ->whereDate('b.booking_date', '>= ', $current_date)
              ->select('u.name','u.profile','b.booking_date','b.venue_id','prl.booking_id','v.name as venue_name','v.address','prl.user_id')
              ->get()->toArray();
              //print_r($booking_details);die;

            if( isset($booking_details) && !empty($booking_details) ){

              foreach ($booking_details as $key => $value) {



                  $profile = "";

                  if(!empty($value->profile)){
                      $profile = url("public/uploads/user/customer/".$value->profile);
                      }
                  else{
                      $profile = url("public/default.png");
                  }

                  $booking_details[$key]->venue_name = $value->venue_name;
                  $booking_details[$key]->venue_address = $value->address;
                   $booking_details[$key]->user_id =(string) $value->user_id;

                  $booking_details[$key]->profile = $profile;



                  $booking_details[$key]->party_date = $value->booking_date;

              }
              if(!empty($booking_details)){

                $res_msg = isset($csvData['Party_invites_fetched_successfully']) ? $csvData['Party_invites_fetched_successfully'] : "";
                $data['booking_details'] = $booking_details;
                return parent :: api_response($data,true,$res_msg,'200');
              }else{
                $data['booking_details'] =[];
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response($data,false,$res_msg,'200');
              }

            }else{
              $data['booking_details'] =[];
              $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
              return parent :: api_response($data,false,$res_msg,'200');
            }
         }
          }
          catch(\Exception $e){
              $res_msg = $e->getMessage();
              return parent :: api_response((object)[],false,$res_msg,'200');

          }
     }



     public function getGuestList(Request $request){
         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
            $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'booking_id'      => "required"
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{

              $guestList = DB::table('order_history as oh')
              ->leftJoin('booking_invite_list as prl', 'oh.id', '=', 'prl.booking_id')
              ->leftJoin('user as u', 'prl.friend_id', '=', 'u.userid')
              ->where('prl.status','=','A')
              ->where('oh.id','=',$input['booking_id'])
              ->select('u.name','u.profile','u.userid')
              ->get()->toArray();

              if( isset($guestList) && !empty($guestList) ){
                foreach ($guestList as $key => $value) {

                    $profile = "";

                    if(!empty($value->profile)){
                           $exp = substr($value->profile,0,8);
                           //if($exp!="https://"){
                             //$profile = url($this->uploadsFolder)."/user/".$value->profile;
                           //}else{
                             $profile =  $value->profile;
                           //}
                    }

                    $guestList[$key]->profile = $profile;
                }

                $res_msg = isset($csvData['Guest_list_fetched_successfully']) ? $csvData['Guest_list_fetched_successfully'] : "";
                return parent :: api_response($guestList,true,$res_msg,'200');
              }else{
                  $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                  return parent :: api_response([],false,$res_msg,'200');
              }
         }
     }

     public function userPastParties(Request $request){
         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required",
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{

            $current_date = date('Y-m-d');

            $booking_details = DB::table('party_data as pd')
              ->leftJoin('order_history as oh', 'oh.id', '=', 'pd.booking_id')
              ->leftJoin('package as p', 'oh.package_id', '=', 'p.id')
              ->leftJoin('club as c', 'c.club_id', '=', 'pd.club_id')
              ->where('pd.u_id',$input['userid'])
              ->whereDate('oh.party_date',' < ',$current_date)
              ->select('oh.party_date','oh.club_id','p.start_time','pd.booking_id','c.venue_home_image')
              ->get()->toArray();

            if( isset($booking_details) && !empty($booking_details) ){

              foreach ($booking_details as $key => $value) {

                $club_name = "";
                $club_address = "";
                $club_city = "";
                $club_state = "";
                $club_country = "";

                $club_rec = DB::table('club as c')
                            ->leftJoin('form_language as fl', 'c.club_id', '=', 'fl.type_id')
                            ->leftJoin('language as l', 'fl.language_id', '=', 'l.id')
                            ->select('meta_key','meta_value')
                            ->where('c.club_id','=',$value->club_id)
                            ->where('fl.type_id','=',$value->club_id)
                            ->where('fl.type','=','5')
                            ->where('l.iso2_code','=',$language_code)
                            ->get()->toArray();

                 foreach ($club_rec as $k1 => $val1) {

                      if($val1->meta_key == "club_name"){
                        $club_name = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_address"){
                        $club_address = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_state"){
                         $club_state = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_city"){
                         $club_city = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_country"){
                         $club_country = $val1->meta_value;
                      }
                  }

                  if(!empty($value->venue_home_image)){
                      $booking_details[$key]->venue_home_image = url($this->uploadsFolder)."/club/home/".$value->venue_home_image;
                  }

                  $booking_details[$key]->club_name = $club_name;
                  $booking_details[$key]->club_address = $club_address;

                  $booking_details[$key]->party_date = date('d M,l',strtotime($value->party_date));

                  $booking_details[$key]->start_time = date('h:i A',strtotime($value->start_time));
              }

              $res_msg = isset($csvData['Past_parties_fetched_successfully']) ? $csvData['Past_parties_fetched_successfully'] : "";
              return parent :: api_response($booking_details,true,$res_msg,'200');

            }else{
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response([],false,$res_msg,'200');
            }
         }
     }

     public function userUpcomingParties(Request $request){

         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required",
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{

            $current_date = date('Y-m-d');
            $current_time = date("H:i:s");

            $booking_details = DB::table('party_data as pd')
              ->leftJoin('order_history as oh', 'oh.id', '=', 'pd.booking_id')
              ->leftJoin('package as p', 'oh.package_id', '=', 'p.id')
              ->leftJoin('club as c', 'c.club_id', '=', 'pd.club_id')
              ->where('pd.u_id',$input['userid'])
              ->whereDate('oh.party_date',' >= ',$current_date)
              ->groupBy('pd.booking_id')
              ->select('oh.user_id','oh.party_date','oh.end_time','oh.club_id','oh.start_time','pd.booking_id','c.venue_home_image')
              ->get()->toArray();

            if( isset($booking_details) && !empty($booking_details) ){

              foreach ($booking_details as $key => $value) {

                $club_name = "";
                $club_address = "";
                $club_city = "";
                $club_state = "";
                $club_country = "";

                $club_rec = DB::table('club as c')
                            ->leftJoin('form_language as fl', 'c.club_id', '=', 'fl.type_id')
                            ->leftJoin('language as l', 'fl.language_id', '=', 'l.id')
                            ->select('meta_key','meta_value')
                            ->where('c.club_id','=',$value->club_id)
                            ->where('fl.type_id','=',$value->club_id)
                            ->where('fl.type','=','5')
                            ->where('l.iso2_code','=',$language_code)
                            ->get()->toArray();

                 foreach ($club_rec as $k1 => $val1) {

                      if($val1->meta_key == "club_name"){
                        $club_name = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_address"){
                        $club_address = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_state"){
                         $club_state = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_city"){
                         $club_city = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_country"){
                         $club_country = $val1->meta_value;
                      }
                  }

                  $booking_details[$key]->venue_home_image = "";

                  if(!empty($value->venue_home_image)){
                      $booking_details[$key]->venue_home_image = url($this->uploadsFolder)."/club/home/".$value->venue_home_image;
                  }

                  $booking_details[$key]->club_name = $club_name;
                  $booking_details[$key]->club_address = $club_address;

                  $booking_details[$key]->party_date = date('d M,l',strtotime($value->party_date));

                  $booking_details[$key]->start_time = date('h:i A',strtotime($value->start_time));

                  $is_party_owner = 0;



                  if( (int)$booking_details[$key]->user_id == (int)$input['userid']){

                      $is_party_owner = 1;
                  }

                  $booking_details[$key]->is_party_owner = $is_party_owner;
                  $party_date = date("Y-m-d",strtotime($booking_details[$key]->party_date));
                  if($party_date<=$current_date){
                  if($booking_details[$key]->end_time<$current_time){
                  unset($booking_details[$key]);
                  }
                  }
              }

              $res_msg = isset($csvData['Upcoming_parties_fetched_successfully']) ? $csvData['Upcoming_parties_fetched_successfully'] : "";
              return parent :: api_response(array_values($booking_details),true,$res_msg,'200');

            }else{
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response([],false,$res_msg,'200');
            }
         }
     }

      public function userUpcomingParties_V2(Request $request){
         try{
         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required",
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response((object)[],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{

            $current_date = date('Y-m-d');
            $current_time = date("H:i:s");

            $booking_details = DB::table('party_data as pd')
              ->leftJoin('order_history as oh', 'oh.id', '=', 'pd.booking_id')
              ->leftJoin('package as p', 'oh.package_id', '=', 'p.id')
              ->leftJoin('club as c', 'c.club_id', '=', 'pd.club_id')
              ->where('pd.u_id',$input['userid'])
              ->whereDate('oh.party_date',' >= ',$current_date)
              ->groupBy('pd.booking_id')
              ->select('oh.user_id','oh.party_date','oh.end_time','oh.club_id','oh.start_time','pd.booking_id','c.venue_home_image')
              ->get()->toArray();

            if( isset($booking_details) && !empty($booking_details) ){

              foreach ($booking_details as $key => $value) {

                $club_name = "";
                $club_address = "";
                $club_city = "";
                $club_state = "";
                $club_country = "";

                $club_rec = DB::table('club as c')
                            ->leftJoin('form_language as fl', 'c.club_id', '=', 'fl.type_id')
                            ->leftJoin('language as l', 'fl.language_id', '=', 'l.id')
                            ->select('meta_key','meta_value')
                            ->where('c.club_id','=',$value->club_id)
                            ->where('fl.type_id','=',$value->club_id)
                            ->where('fl.type','=','5')
                            ->where('l.iso2_code','=',$language_code)
                            ->get()->toArray();

                 foreach ($club_rec as $k1 => $val1) {

                      if($val1->meta_key == "club_name"){
                        $club_name = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_address"){
                        $club_address = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_state"){
                         $club_state = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_city"){
                         $club_city = $val1->meta_value;
                      }

                      if($val1->meta_key == "club_country"){
                         $club_country = $val1->meta_value;
                      }
                  }

                  $booking_details[$key]->venue_home_image = "";

                  if(!empty($value->venue_home_image)){
                      $booking_details[$key]->venue_home_image = url($this->uploadsFolder)."/club/home/".$value->venue_home_image;
                  }

                  $booking_details[$key]->club_name = $club_name;
                  $booking_details[$key]->club_address = $club_address;

                  $booking_details[$key]->party_date = date('d M,l',strtotime($value->party_date));

                  $booking_details[$key]->end_time = date('h:i A',strtotime   ($value->end_time));
                  $booking_details[$key]->start_time = date('h:i A',strtotime($value->start_time));

                  $is_party_owner = 0;



                  if( (int)$booking_details[$key]->user_id == (int)$input['userid']){

                      $is_party_owner = 1;
                  }

                  $booking_details[$key]->is_party_owner = $is_party_owner;
                  $party_date = date("Y-m-d",strtotime($booking_details[$key]->party_date));
                  if($party_date<=$current_date){
                  if($booking_details[$key]->end_time<$current_time){
                  unset($booking_details[$key]);
                  }
                  }
              }

              $data["upcoming_parties"] = array_values($booking_details);
              $res_msg = isset($csvData['Upcoming_parties_fetched_successfully']) ? $csvData['Upcoming_parties_fetched_successfully'] : "";
              return parent :: api_response($data,true,$res_msg,'200');

            }else{
                $data["upcoming_parties"] =[];
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response($data,false,$res_msg,'200');
            }
         }
         }
         catch(\Exception $e){

             $res_msg = $e->getMessage();
                return parent :: api_response((object)[],false,$res_msg,'200');
         }
     }


     public function getBookingList(Request $request){
         $lang_data = parent :: getLanguageValues($request);

         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required",
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response([],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{
            $current_date = date('Y-m-d');
            $end_time = date('H:i:s');
            //echo $end_time;die;
            $booking_details = DB::table('order_history as oh')
              ->leftJoin('package as p', 'oh.package_id', '=', 'p.id')
              ->leftJoin('club as c', 'c.club_id', '=', 'oh.club_id')
              ->leftJoin('party_data as pd', 'pd.booking_id', '=', 'oh.id')
              ->where('pd.u_id',$input['userid'])

              ->whereDate('pd.party_date','>=',$current_date)
              //->whereDate('p.end_time','>=',$end_time)
              ->select('oh.user_id','oh.party_date','oh.club_id','oh.start_time','oh.end_time','oh.id','p.id as package_id','pd.qr_code','pd.booking_code','p.price')
              ->orderBy('oh.party_date', 'DESC')
              ->get()->toArray();


            if( isset($booking_details) && !empty($booking_details) ){

                foreach ($booking_details as $b_key => $booking) {
                    $club_name = "";
                    $club_data  = DB::table('club as c')
                    ->leftJoin('form_language as fl', 'c.club_id', '=', 'fl.type_id')
                    ->leftJoin('language as l', 'fl.language_id', '=', 'l.id')
                    ->where('fl.type','=','5')
                    ->where('fl.type_id','=',$booking->club_id)
                    ->where('fl.meta_key','=','club_name')
                    ->where('l.iso2_code','=',$language_code)
                    ->select('c.club_id','fl.meta_value as name')
                    ->get()->toArray();

                    if( isset($club_data[0]->name) && !empty($club_data[0]->name) ){
                        $club_name = $club_data[0]->name;
                    }

                    $booking_details[$b_key]->club_name = $club_name;

                    $date_time = "";
                    $date_time = date('m/d/Y',strtotime($booking->party_date)).", ".date('h:i A',strtotime($booking->start_time));

                    $booking_details[$b_key]->date_time = $date_time;

                    if(!empty($booking->qr_code)){
                        $booking_details[$b_key]->qr_code = url($this->uploadsFolder)."/qrcode/".$booking->qr_code;
                    }

                    $currency_data = DB::table('currency')
                    ->where('status','=',1)
                    ->where('id','=',"2")
                    ->select('title','symbol_left','symbol_right','value')
                    ->get()->toArray();

                   $symbol_left = "";
                   $symbol_right = "";
                   $currency_value = "1";

                   if(isset($currency_data[0]) && !empty($currency_data[0])){

                      $symbol_left    = $currency_data[0]->symbol_left;
                      $symbol_right   = $currency_data[0]->symbol_right;
                      $currency_value = !empty($currency_data[0]->value) ? $currency_data[0]->value : "1";
                   }

                  $package_price = 0;
                  $package_price = $booking->price * $currency_value;

                  $booking_details[$b_key]->price = $package_price;
                  $booking_details[$b_key]->symbol_left = $symbol_left;
                  $booking_details[$b_key]->symbol_right = $symbol_right;

                  if($booking_details[$b_key]->party_date<=$current_date){
                      if($booking_details[$b_key]->end_time<$end_time){
                          unset($booking_details[$b_key]);
                      }

                  }

                }

               $res_msg = isset($csvData['Booking_list_fetched_successfully']) ? $csvData['Booking_list_fetched_successfully'] : "";
               return parent :: api_response(array_values($booking_details),true,$res_msg,'200');

            }else{
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response([],false,$res_msg,'200');
            }
         }
     }

     public function getBookingList_V2(Request $request){
         try{
         $lang_data = parent :: getLanguageValues($request);
         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
             'userid'      => "required",
         ]);

         $headers =   $request->headers->all();
         $language_code = $headers['language-code'][0];

         if($validator->fails()){
           $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
           return parent :: api_response((object)[],false,$err_msg, 200);
           //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{
            $current_date = date('Y-m-d');
            $end_time = date('H:i:s');
            //echo $end_time;die;
            $booking_details = DB::table('order_history as oh')
              ->leftJoin('package as p', 'oh.package_id', '=', 'p.id')
              ->leftJoin('club as c', 'c.club_id', '=', 'oh.club_id')
              ->leftJoin('party_data as pd', 'pd.booking_id', '=', 'oh.id')
              ->where('pd.u_id',$input['userid'])
              ->where('oh.transaction_status','=','succeeded')
              ->whereDate('pd.party_date','>=',$current_date)
              //->whereDate('p.end_time','>=',$end_time)
              ->select('oh.user_id','oh.party_date','oh.club_id','oh.start_time','oh.end_time','oh.id','p.id as package_id','pd.qr_code','pd.booking_code','p.price')
              ->orderBy('oh.party_date', 'DESC')
              ->get()->toArray();


            if( isset($booking_details) && !empty($booking_details) ){

                foreach ($booking_details as $b_key => $booking) {
                    $club_name = "";
                    $club_data  = DB::table('club as c')
                    ->leftJoin('form_language as fl', 'c.club_id', '=', 'fl.type_id')
                    ->leftJoin('language as l', 'fl.language_id', '=', 'l.id')
                    ->where('fl.type','=','5')
                    ->where('fl.type_id','=',$booking->club_id)
                    ->where('fl.meta_key','=','club_name')
                    ->where('l.iso2_code','=',$language_code)
                    ->select('c.club_id','fl.meta_value as name')
                    ->get()->toArray();

                    if( isset($club_data[0]->name) && !empty($club_data[0]->name) ){
                        $club_name = $club_data[0]->name;
                    }

                    $booking_details[$b_key]->club_name = $club_name;

                    $date_time = "";
                    $date_time = date('m/d/Y',strtotime($booking->party_date)).", ".date('h:i A',strtotime($booking->start_time));

                    $booking_details[$b_key]->date_time = $date_time;

                    if(!empty($booking->qr_code)){
                        $booking_details[$b_key]->qr_code = url($this->uploadsFolder)."/qrcode/".$booking->qr_code;
                    }

                    $currency_data = DB::table('currency')
                    ->where('status','=',1)
                    ->where('id','=',"2")
                    ->select('title','symbol_left','symbol_right','value')
                    ->get()->toArray();

                   $symbol_left = "";
                   $symbol_right = "";
                   $currency_value = "1";

                   if(isset($currency_data[0]) && !empty($currency_data[0])){

                      $symbol_left    = $currency_data[0]->symbol_left;
                      $symbol_right   = $currency_data[0]->symbol_right;
                      $currency_value = !empty($currency_data[0]->value) ? $currency_data[0]->value : "1";
                   }

                  $package_price = 0;
                  $package_price = $booking->price * $currency_value;

                  $booking_details[$b_key]->price = $package_price;
                  $booking_details[$b_key]->symbol_left = $symbol_left;
                  $booking_details[$b_key]->symbol_right = $symbol_right;

                  if($booking_details[$b_key]->party_date<=$current_date){
                      if($booking_details[$b_key]->end_time<$end_time){
                          unset($booking_details[$b_key]);
                      }

                  }

                }
               $data['booking_data']=array_values($booking_details);
               $res_msg = isset($csvData['Booking_list_fetched_successfully']) ? $csvData['Booking_list_fetched_successfully'] : "";
               return parent :: api_response($data,true,$res_msg,'200');

            }else{
                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response((object)[],false,$res_msg,'200');
            }
         }
         }
         catch(\Exception $e){
             $res_msg =$e->getMessage();
             return parent :: api_response((object)[],false,$res_msg,'200');
         }
     }


     public function generateRandomBookingCode_old($club_id = 0){

        //$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pool = '123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $rand = substr(str_shuffle(str_repeat($pool, 5)), 0, 5);
        $randomstring = $rand;

        $response = DB::table('party_data')
            ->where('club_id','=',$club_id)
            //->where('package_id','=',$randomstring)
            ->where('booking_code','=',$randomstring)
            ->select('party_id')
            ->get()->toArray();

        if(isset($response) && !empty($response) ){
            $this->generateRandomBookingCode($club_id);
        }else{
            return $randomstring;
        }
    }

    /*public function getUserRelate($user_id = 0, $friend_id = 0){

     $users = DB::table('user_friends')
          ->where('user_id','=',$user_id)
          ->where('friend_id','=',$friend_id)
          ->select('is_friend','status as friend_status')
          ->get()->toArray();

      if(isset($users) && !empty($users)){

            if( ($users[0]->friend_status == 'A') && ( (int)$users[0]->is_friend == 1 ) ){
                  return 4;
            }

            if( ($users[0]->friend_status == 'B') && ( (int)$users[0]->is_friend == 0 ) ){
                  return 5;
            }

            if( ( ($users[0]->friend_status == 'C') || ($users[0]->friend_status == 'D') || ($users[0]->friend_status == 'U') ) && ( (int)$users[0]->is_friend == 0 ) ){

              return 1;
            }

            if( ($users[0]->friend_status == 'P') && ( (int)$users[0]->is_friend == 0 ) ){
               $userFriendRelateData = DB::table('friend_req_list')
                ->where(function ($query) use ($user_id , $friend_id) {
                        $query->where('user_id', '=', $user_id)
                              ->orWhere('user_id', '=', $friend_id);
                    })
                ->where(function ($query)  use ($user_id , $friend_id) {
                        $query->where('friend_id', '=', $user_id)
                              ->orWhere('friend_id', '=', $friend_id);
                    })
                ->where('status','=',0)
                ->select('*')
                ->get()->toArray();

                if( $userFriendRelateData[0]->user_id == $user_id){
                   return 2;
                }elseif( $userFriendRelateData[0]->user_id == $friend_id){
                   return 3;
                }
            }
      }else{
        return 1;
      }
    }
*/
    public function getPackageRelateData($club_id , $package_date){

        $day = date('l', strtotime($package_date) );

        $dress_id = 0;
        $music_id = 0;

        $dress_setting = DB::table('dress_setting')
            ->where('club_id','=',$club_id)
            ->whereNull('deleted_at')
            ->select('dress_value')
            ->get()->toArray();


        if( isset($dress_setting[0]->dress_value) && !empty($dress_setting[0]->dress_value) ){

            $dress_array = json_decode($dress_setting[0]->dress_value,true);

            if( array_key_exists($day , $dress_array) ){

               $dress_id = $dress_array[$day];
            }
        }


        $music_setting = DB::table('music_setting')
            ->where('club_id','=',$club_id)
            ->whereNull('deleted_at')
            ->select('music_value')
            ->get()->toArray();

        if( isset($music_setting[0]->music_value) && !empty($music_setting[0]->music_value) ){

            $music_array = json_decode($music_setting[0]->music_value,true);
            if( array_key_exists($day , $music_array) ){

               $music_id = $music_array[$day];
            }
        }

        $response_data = array();

        if( ( (int)$dress_id == 0 ) || ( (int)$music_id == 0 ) ){

            return $response_data;
        }

        $response_data = array(
                                "dress_id"=>$dress_id,
                                "music_id"=>$music_id
                              );

        return $response_data;
    }



     public function checkIn_customer(request $request){
      dd($request->all());
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

          $getlistowner = DB::table("booking as b")->select("g.userid","g.name","g.last_name","g.profile","b.is_checkin","b.checked_in as checkin_date")->leftJoin("guest as g","g.userid","b.userid")->where("b.id","=",$input["bookingid"])->get()->toArray();

          $getlist = DB::table("booking_invite_list as b")->select("g.userid","g.name","g.last_name","g.profile","b.is_checkin","b.checkin_date")->leftJoin("guest as g","g.userid","b.friend_id")->where("b.booking_id","=",$input["bookingid"])->get()->toArray();

           $get_list=array_merge($getlistowner,$getlist);
          $bookings = array_column($get_list, 'checkin_date');

        array_multisort($bookings, SORT_DESC, $get_list);

          if(empty($get_list[0])){
            $res_msg ="No guest found.";
            return parent :: api_response([],false,$res_msg, 200);
          }

           foreach($get_list as $key=>$v){

            if($v->userid!=$input["userid"]){

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
         else{
            unset($get_list[$key]);
         }

         }
            $res_msg ="checkin list fetched successfully.";
            return parent :: api_response(array_values($get_list),true,$res_msg, 200);
         }
      }


    public function getClubCalendar(Request $request){
         $lang_data = parent :: getLanguageValues($request);
         $csvData = array();

         if( ($lang_data['status'] == 1) && !empty($lang_data['csvData'])){
              $csvData = $lang_data['csvData'];
         }

         $input = $request->all();

         $validator  = Validator :: make($input,[
              'venue_id'         => "required"
          ]);

         if($validator->fails()){

            $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
            return parent :: api_response((object)[],false,$err_msg, 200);
            //return parent :: api_response([],false,$validator->errors()->first(), 200);
         }else{

            $club_time_data = DB::table('venue as v')
              ->leftJoin('venue_timing as ct', 'ct.venue_id', '=', 'v.id')
              ->where('v.status','=',1)
              ->where('v.id','=',$input['venue_id'])
              ->whereNull('v.deleted_at')
              ->select('v.id','ct.time_value','ct.club_working_value')
              ->get()->toArray();

              if(isset($club_time_data[0]) && !empty($club_time_data[0])){
                $final_response = array();

                if(empty($club_time_data[0]->time_value) || empty($club_time_data[0]->club_working_value) ){

                  $res_msg = isset($csvData['Unable_to_fetch_days_and_time_plz_contact_admin']) ? $csvData['Unable_to_fetch_days_and_time_plz_contact_admin'] : "";

                  return parent :: api_response((object)[],false,$res_msg, 200);
                }

               $open_times = json_decode($club_time_data[0]->time_value,true);
               $days = [];

               $days[] = array("value"=>"monday","is_open" => 0,'dd'=> date( 'd', strtotime( 'monday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'monday this week' ) ) );

               $days[] = array("value"=>"tuesday","is_open" => 0,'dd'=> date( 'd', strtotime( 'tuesday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'tuesday this week' ) ) );

               $days[] = array("value"=>"wednesday","is_open" => 0,'dd'=> date( 'd', strtotime( 'wednesday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'wednesday this week' ) ) );

               $days[] = array("value"=>"thursday","is_open" => 0,'dd'=> date( 'd', strtotime( 'thursday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'thursday this week' ) ) );

               $days[] = array("value"=>"friday","is_open" => 0,'dd'=> date( 'd', strtotime( 'friday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'friday this week' ) ) );

               $days[] = array("value"=>"saturday","is_open" => 0,'dd'=> date( 'd', strtotime( 'saturday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'saturday this week' ) ) );

               $days[] = array("value"=>"sunday","is_open" => 0,'dd'=> date( 'd', strtotime( 'sunday this week' ) ) ,'date'=> date( 'Y-m-d', strtotime( 'sunday this week' ) ) );

               $working_days = explode(",",$club_time_data[0]->club_working_value);

               foreach ($days as $k1 => $day) {
                    if(in_array($k1,$working_days) ){
                       $days[$k1]['is_open'] = 1;
                    }
               }

               $day_values = array_column($days,'value');

               foreach ($open_times as $key => $time) {
                    $open_close_time = [];
                    $open_close_time = explode('-',$time);
                    $k_value = array_search(strtolower($key) , $day_values);
                    $is_open = "0";
                    $date = "";
                    $dd = "";
                    if($k_value !== false){
                      $is_open = $days[$k_value]['is_open'];
                      $date = $days[$k_value]['date'];
                      $dd = $days[$k_value]['dd'];
                    }

                    $club_times [] =  array(
                      "value" => strtolower($key),
                      "opening_time"=>$open_close_time[0],
                      "closing_time" =>$open_close_time[1],
                      "is_open" => $is_open,
                      "dd" => $dd,
                      "date" => $date
                    );


                 if($is_open=="1"){
                     $current_date = date("Y-m-d");
                     if($date<$current_date){
                       $date =  date( 'Y-m-d', strtotime(strtolower($key). ' next week' ));
                     }

                     $day_data[] =array(
                     "value" => ucfirst($key),
                     "date" => $date
                    );
                 }
               }
               //$calender['calender']=$club_times;
               //$day_data_arr['day_data']=$day_data;
               $club_times_arr['calender']=$club_times;
               $club_times_arr['day_data']=$day_data;
               $res_msg = isset($csvData['Timings_fetched_successfully']) ? $csvData['Timings_fetched_successfully'] : "";

                return parent :: api_response($club_times_arr,true,$res_msg, 200);
              }else{

                $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                return parent :: api_response((object)[],false,$res_msg, 200);
              }
         }
     }

  public function bookingPdf(Request $request){
      $input = $request->all();
      $validator  = Validator::make($input,[
                      'booking_id'     => 'required',
                      'user_id'    => 'required',
                    ]);
      if($validator->fails()){
        $err_msg = parent :: getErrorMsg($validator->errors()->toArray() , $request);
        return parent :: api_response((object)[],false,$err_msg, 200);
      }else{

        $bookingDetail = DB::table("booking as b")
                            ->leftJoin("users as u","u.id","b.userid")
                            ->where('b.id',$input['booking_id'])
                            ->where('b.userid',$input['user_id'])
                            ->first();
        if($bookingDetail){
          $userDetail = DB::table("users")
                            ->where('id',$input['user_id'])
                            ->first();
          if($userDetail){
            $bookingItemDetail = DB::table("booking_items as bi")
                              ->select('bi.*','mi.*','bi.price as booking_items_price','bi.qty as booking_items_qty')
                              ->leftJoin("menu_item as mi","mi.id","bi.item_id")
                              ->where('bi.booking_id',$bookingDetail->id)
                              ->get();
            $venueDetail = DB::table("venue")
                              ->where('id',$bookingDetail->venue_id)
                              ->first();
            $base_path = ($this->uploadsFolder);
            $fileName = strtotime(now()).'.pdf';
            $pdf = PDF::loadView('backend.booking_pdf',compact('bookingDetail','bookingItemDetail','userDetail','venueDetail'));
              // ->save($base_path.'/report_pdf/'.$fileName);


            // $dom_pdf = $pdf->getDomPDF();
            // $canvas = $dom_pdf ->get_canvas();
            // $canvas->page_text(520, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
            $pdf->save($base_path.'/report_pdf/'.$fileName);

            // return $pdf->download($fileName);

            $result['report_url']=url($this->uploadsFolder).'/report_pdf/'.$fileName ;
            $res_msg = 'Report';
            return parent :: api_response($result,true,$res_msg, 200);
          }else{
            $err_msg = "This user id does not exist.";
            return parent :: api_response((object) [], false, $err_msg, 200);
          }
        }else{
          $err_msg = "This Booking id does not exist.";
          return parent :: api_response((object) [], false, $err_msg, 200);
        }

      }
    }
}
