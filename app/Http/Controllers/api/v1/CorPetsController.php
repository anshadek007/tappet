<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Pets;
use App\PetImages;
use App\PetTypes;
use App\PetBreeds;
use App\BusinessUser;
use App\User;
use App\PetCoOwners;
use App\PetCollars;
use App\PetSchedules;
use App\PetLocations;
use App\PetActivities;
use App\PetActivityLocations;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Traits\PushNotifications;
use App\Notification;
use App\UserDeviceToken;
use App\PetCheckInOut;

class CorPetsController extends APIController {

    protected $userModel;
    protected $week_list;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\BusinessUser();
        $this->week_list = weekList();
    }

    public function pet_details(Request $request) {
        try {
            $login_user_id = $this->userModel->validateUser(Auth::guard('corporate')->user()->u_id);

            //$user_id = !empty($request->user_id) ? $request->user_id : null;
            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $id = $request->pet_id;
            $value = Pets::with(['images', 'addedBy'])->select("*")
                    ->leftJoin('pet_types', function ($join) {
                        $join->on('pt_id', '=', 'pet_type_id');
                    })
                    ->where('pet_id', $id)
                    ->first();

            $fetch_record_list = array();
            $response = array();
            if (!empty($value)) {
                $value->pet_size = !empty($value->pet_size) ? $value->pet_size : "";
                $value->pet_is_friendly = !empty($value->pet_is_friendly) ? $value->pet_is_friendly : "";
                $pet_breed_percentage = [];
                if (!empty($value->pet_breed_percentage)) {
                    $pet_breed_percentage = explode(",", $value->pet_breed_percentage);
                }
                if (!empty($value->pet_breed_ids)) {
                    $breed = $this->userModel->getBreed($value->pet_breed_ids);
                    

                    if (!empty($breed)) {
                        $i = 0;
                        foreach ($breed as &$breed_value) {
                            $breed_value['breed_percentage'] = !empty($pet_breed_percentage) && !empty($pet_breed_percentage[$i]) ? $pet_breed_percentage[$i] : 0;
                            $i++;
                        }
                    }

                    $value->breed = $breed;
                    $checkin = PetCheckInOut::where([['pet_id',$id],['cor_user_id',$login_user_id->u_id]])->latest()->first();
                    $value->isCheckIn = $checkin ? $checkin->is_check_in : null;

                }


                $co_owners = array();
                // if ($user_id == $value->pet_owner_id) {
                //     $co_owners = PetCoOwners::select("u_id", 'u_first_name', 'u_last_name', 'u_image')
                //             ->join('users', 'pet_co_owner_owner_id', 'u_id')
                //             ->where('pet_co_owner_pet_id', $request->pet_id)
                //             ->where('pet_co_owner_status', 1)
                //             ->get();
                // }

                $value->co_owners = $co_owners;
                $value->pet_collar = !empty($value->collar) && $value->collar != null ? $value->collar : (object) array();

                $value->login_user_type = 'other';

                if ($login_user_id->u_id == $value->pet_owner_id) {
                    $value->login_user_type = 'owner';
                } else {

                    $co_owners = PetCoOwners::select("*")
                            ->where('pet_co_owner_owner_id', $login_user_id->u_id)
                            ->where('pet_co_owner_pet_id', $request->pet_id)
                            ->where('pet_co_owner_status', 1)
                            ->first();
                    if (!empty($co_owners)) {
                        $value->login_user_type = 'co-owner';
                    }
                }

                unset($value->collar);

                $message = "Pet details found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["result"] = $value;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function checkIn(Request $request){
        try {
            $login_user_id = $this->userModel->validateUser(Auth::guard('corporate')->user()->u_id);

            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $petid = $request->pet_id;
            $corid = $login_user_id->u_id;
            
            $check = PetCheckInOut::where([['pet_id',$petid],['cor_user_id',$corid],['is_check_in',0]])->first();
            $checkto = PetCheckInOut::where([['pet_id',$petid],['is_check_in',0]])->first();
            if($check || $checkto){
                $message = "Pet Already Check in.";
                $response["result"] = null;
            } else {
                $check = PetCheckInOut::create([
                    'pet_id' => $petid,
                    'cor_user_id' => $corid,
                    'check_in' => $request->check_in,
                    'is_check_in' => 0,
                ]);
                $message = "Pet Check in successfully.";
                $response["result"] = $check;
            }
            
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function checkOut(Request $request){
        try {
            $login_user_id = $this->userModel->validateUser(Auth::guard('corporate')->user()->u_id);

            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $petid = $request->pet_id;
            $corid = $login_user_id->u_id;
            
            $check = PetCheckInOut::where([['pet_id',$petid],['cor_user_id',$corid],['is_check_in',0]])->first();
            if($check){
                $check->update([
                    'pet_id' => $petid,
                    'cor_user_id' => $corid,
                    'check_out' => $request->check_out,
                    'is_check_in' => 1,
                ]);
                $message = "Pet Check out successfully.";
                $response["result"] = $check;
            } else {
                $message = "Pet Check in data not found!";
                $response["result"] = null;
            }
            
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function petList(Request $request){

        $login_user_id = $this->userModel->validateUser(Auth::guard('corporate')->user()->u_id);

        $corid = $login_user_id->u_id;
        
        $checkdata = PetCheckInOut::query();
        $checkdata = $checkdata->where('cor_user_id',$corid)->with('pet.addedBy');

        if(isset($request->status) && $request->status != null){
            if($request->status==0 || $request->status==1){
                $checkdata = $checkdata->where('is_check_in',$request->status);
            }
        }
        $checkdata = $checkdata->orderBy('id','desc')->get();

        $datas = array();
        if(isset($request->date) && $request->date != null){
            foreach($checkdata as $data){
                if(isset($request->date) && $request->date != null){
                    $checkdate = date('Y-m-d', strtotime($data->check_in));
                    $requestdate = date('Y-m-d', strtotime($request->date));
                    if($checkdate == $requestdate){
                        array_push($datas,$data);
                    }
                } else {
                    $datas = $checkdata;
                }
            }
        } else {
            $datas = $checkdata;
        }
        //
        $durationdata = array();
        if(isset($request->duration) && $request->duration != null){
            foreach($datas as $dudata){
                if(isset($request->duration) && $request->duration != null){
                    $checkintime = date('H:i', strtotime($dudata->check_in));
                    $checkouttime = date('H:i', strtotime($dudata->check_out));

                    $start = Carbon::parse($checkintime);
                    $end = Carbon::parse($checkouttime);
                    $durationtime = $end->diffInHours($start);
                    if($request->duration == 1){
                        if($durationtime > 1){
                            array_push($durationdata,$dudata);
                        }
                    }
                    if($request->duration == 2){
                        if($durationtime < 1){
                            array_push($durationdata,$dudata);
                        }
                    }
                    $durationday = $end->diffInDays($start);
                    if($request->duration == 3){
                        if($durationday > 1){
                            array_push($durationdata,$dudata);
                        }
                    }
                    if($request->duration == 4){
                        if($durationday < 1){
                            array_push($durationdata,$dudata);
                        }
                    }
                } else {
                    $durationdata = $datas;
                }
            }
        } else {
            $durationdata = $datas;
        }
        //
        $timedata = array();
        if(isset($request->check_in_to_time) && $request->check_in_to_time != null){
            foreach($durationdata as $timetodata){
                if(isset($request->check_in_from_time) && $request->check_in_from_time != null){
                    $checkintotime = date('H:i', strtotime($request->check_in_to_time));
                    $checkoutfromtime = date('H:i', strtotime($request->check_in_from_time));
                    $checkintime = date('H:i', strtotime($timetodata->check_in));

                    $start = Carbon::parse(checkoutfromtime);
                    $end = Carbon::parse($$checkintotime);
                    $time = Carbon::parse($checkintime);
                    if ($time->between($checkoutfromtime ,$checkintotime)) {
                        array_push($timedata,$timetodata);
                    }
                    
                } else {
                    $timedata = $durationdata;
                }
            }
        } else {
            $timedata = $durationdata;
        }

        if (count($timedata) < 0) {
            return $this->respondResult("", 'Pet list not found', false, 200);
        }

        $response["result"] = $timedata;
        $response["message"] = "Pet List.";
        $response["status"] = true;

        return response()->json($response, 200);
        
    }

    public function checkInHistory(Request $request){
        try {
            $login_user_id = $this->userModel->validateUser(Auth::guard('corporate')->user()->u_id);

            $corid = $login_user_id->u_id;
            
            $find_record = PetCheckInOut::where([['cor_user_id',$corid],['pet_id',$request->pet_id]])->with('pet.addedBy')->get();

            if (count($find_record) < 0) {
                return $this->respondResult("", 'Pet list not found', false, 200);
            }

            $response["result"] = $find_record;
            
            $response["message"] = "Pet List.";
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function petOwnerDetails(Request $request){
        try {

            $Model = new \App\User();
            $user = $Model->validateUser($request->id);
            if (empty($user)) {
                return $this->respondResult("", 'User Details not found', false, 200);
            }

            $response["result"] = $user;
            $response["message"] = "Owner Details.";
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function dashboard(Request $request){
        $login_user_id = $this->userModel->validateUser(Auth::guard('corporate')->user()->u_id);

        $corid = $login_user_id->u_id;
        $record = array();
        //year 
        if(isset($request->year) && $request->year != null){
            $now = Carbon::create($request->year, 1, 1);
            $startOfYear = $now;
            $endOfYear = new \DateTime('last day of December '.$request->year);
            foreach ($endOfYear as $key => $value) {
                if($key=='date'){
                    $endOfYear =  $value;
                }
            }

            $currentDate = $endOfYear;
            $currmonth = 12;
            $oldmonth = $startOfYear->subMonths(1);
            
            $allpetscount = PetCheckInOut::where('cor_user_id',$corid)->whereBetween('check_in', array($startOfYear, $currentDate))->count();
            $petscheckincount = PetCheckInOut::where([['cor_user_id',$corid],['is_check_in',0]])->whereBetween('check_in', array($startOfYear, $currentDate))->count();
            $petscheckoutcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($startOfYear, $currentDate))->count();

            $monthdata = array();
            for ($j=1; $j <= $currmonth; $j++) {
                $thisdate = $oldmonth->addMonths(1);
                $thismonth = $thisdate->month;
                $thisyear = $thisdate->year;
                $thismonthdata = PetCheckInOut::where('cor_user_id',$corid)->whereYear('check_in',$thisyear)->whereMonth('check_in',$thismonth)->count();
                $newmonthdata = $thismonthdata;
                $mondata['thismonth'] = $j;
                $mondata['thismonthdata'] = $thismonthdata;
                
                array_push($monthdata,$mondata);
                $oldmonth = $thisdate;
            }
            $record['allPetsCount'] = $allpetscount;
            $record['petsCheckinCount'] = $petscheckincount;
            $record['petsCheckoutCount'] = $petscheckoutcount;
            
            $record['thismonthdata'] = $monthdata;

        }
        //month
        if(isset($request->month) && $request->month != null){

            $numberOfDaysInMonth = Carbon::parse($request->month)->daysInMonth;
            $weekcount = floor($numberOfDaysInMonth/7);
            $currmonth = Carbon::parse($request->month)->month;
            $weekdata = array();
            $startdate = Carbon::parse($request->month)->startOfMonth();
            $enddate = Carbon::parse($request->month)->endOfMonth();

            $allpetscount = PetCheckInOut::where('cor_user_id',$corid)->whereBetween('check_in', array($startdate, $enddate))->count();
            $petscheckincount = PetCheckInOut::where([['cor_user_id',$corid],['is_check_in',0]])->whereBetween('check_in', array($startdate, $enddate))->count();
            $petscheckoutcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($startdate, $enddate))->count();

            for ($i=1; $i <= $weekcount; $i++) {
                // $weekStartDate = $startdate->startOfWeek()->format('Y-m-d');
                $weekStartDate = $startdate;
                // $weekEndDate = $startdate->endOfWeek()->format('Y-m-d');
                $weekEndDate = Carbon::parse($weekStartDate)->addDays(6);
                if($i==1){
                    $currentweekcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($weekStartDate, $weekEndDate))->count();
                    $weekco = $currentweekcount;
                    array_push($weekdata,$weekco);
                }
                if($i==2){
                    $startDate = Carbon::parse($weekEndDate)->addDays(1);
                    $secoundendDate = Carbon::parse($startDate)->addDays(6);
                    $currentweekcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($startDate, $secoundendDate))->count();
                    $weekco = $currentweekcount;
                    array_push($weekdata,$weekco);
                }
                if($i==3){
                    $startDate = Carbon::parse($secoundendDate)->addDays(1);
                    $thirdendDate = Carbon::parse($startDate)->addDays(6);
                    $currentweekcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($startDate, $thirdendDate))->count();
                    $weekco = $currentweekcount;
                    array_push($weekdata,$weekco);
                }
                if($i==4){
                    $startDate = Carbon::parse($thirdendDate)->addDays(1);
                    // $fourendDate = Carbon::parse($startDate)->addDays(6);
                    $fourendDate = $enddate;
                    $currentweekcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($startDate, $fourendDate))->count();
                    $weekco = $currentweekcount;
                    array_push($weekdata,$weekco);
                }
            }

            $record['allPetsCount'] = $allpetscount;
            $record['petsCheckinCount'] = $petscheckincount;
            $record['petsCheckoutCount'] = $petscheckoutcount;
            $record['thisweekdata'] = $weekdata;
        }
        //weekdata
        if(isset($request->week) && $request->week != null){
            $userweek = explode(',', $request->week);
            $year = $userweek[0];
            $month = $userweek[1];
            $week = $userweek[2];

            $data = $year.'-'.$month;
            $startdate = Carbon::parse($data)->startOfMonth();
            $enddate = Carbon::parse($request->month)->endOfMonth();

            $formate = Carbon::parse($data)->format('M');
            // $start = date("Y-m-d",strtotime($week.' Sunday'.$formate.$year));
            if($week==1){
                $start = $startdate;
            }
            if($week==2){
                $start = Carbon::parse($startdate)->addDays(6);
            }
            if($week==3){
                $start = Carbon::parse($startdate)->addDays(14);
            }
            if($week==4){
                $start = Carbon::parse($startdate)->addDays(21);
            }
            if($week==4){
                $endweekdate = $enddate;
            } else {
                $endweekdate = Carbon::parse($start)->addDays(6);
            }

            $allpetscount = PetCheckInOut::where('cor_user_id',$corid)->whereBetween('check_in', array($start, $endweekdate))->count();
            $petscheckincount = PetCheckInOut::where([['cor_user_id',$corid],['is_check_in',0]])->whereBetween('check_in', array($start, $endweekdate))->count();
            $petscheckoutcount = PetCheckInOut::where([['cor_user_id',$corid,['is_check_in',1]]])->whereBetween('check_in', array($start, $endweekdate))->count();
            $oneweekdata = array();
            
            $start = Carbon::parse($start)->subDays(1);
            $oldday = $start;
            
            for ($j=1; $j <= 7; $j++) {
                $thisdate = $oldday->addDays(1);
                $thisdaydata = PetCheckInOut::where('cor_user_id',$corid)->whereDate('check_in',$thisdate)->count();
                
                $mondata['day'] = $j;
                $mondata['dayletter'] = date('D', strtotime($thisdate));
                $mondata['thisdaydata'] = $thisdaydata;
                $mondata['thisdate'] = date('d-m-Y', strtotime($thisdate));

                array_push($oneweekdata,$mondata);
                $oldday = $thisdate;
            }
            $record['allPetsCount'] = $allpetscount;
            $record['petsCheckinCount'] = $petscheckincount;
            $record['petsCheckoutCount'] = $petscheckoutcount;
            $record['thisweekdata'] = $oneweekdata;
        }
        if (empty($record)) {
            return $this->respondResult("", 'Data not found', false, 200);
        }
        $response["result"] = $record;
        $response["message"] = "Dashboard Data.";
        $response["status"] = true;

        return response()->json($response, 200);
       
    }
}