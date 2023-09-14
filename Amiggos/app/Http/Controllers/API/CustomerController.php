<?php

namespace App\Http\Controllers\API;

use DateTime;
use Stripe;
use Config;
use VideoThumbnail;
use App\User;
use App\Address;
use App\Guest;
use App\Music;
use App\Venue_type;
use App\Club;
use App\Card_detail;
use App\User_story;
use App\Mail\DemoEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Helper;

class CustomerController extends ApiController {

    protected $uploadsFolder = 'public/uploads/';

    public function __construct() {
        //$secret_key = Config::get('constants.stripe_secret_key');
        //Stripe\Stripe::setApiKey($secret_key);
    }

    public function register(request $request) {
        $data = [];

        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                        "email" => "nullable|email",
                        "password" => "required",
                        "username" => "required",
                        "date_of_birth" => "required",
                        "device_type" => "required",
                        "device_id" => "required",
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                //check for username
                $lang_data = parent::getLanguageValues($request);
                $csvData = array();

                if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                    $csvData = $lang_data['csvData'];
                }

                $check_username = DB::table("guest")->where("username", "=", $input["username"])->whereNull("deleted_at")->first();

                if (isset($input["email"]) && !empty($input["email"])) {
                    $check_email = DB::table("guest")->where("email", "=", $input["email"])->whereNull("deleted_at")->first();
                    if (!empty($check_email->email)) {
                        $message = "Email already exist.";
                        return parent::api_response($data, false, $message, 200);
                    }
                }

                if (empty($check_username->userid)) {
                    $uri = $request->path();
                    $log_path = storage_path('app_log') . '/' . date('Y_m_d') . '_apiCall.log';
                    $message1 = "\n" . date('Y-m-d H:i:s') . " Step 1 : " . $uri . "  Api call with input " . json_encode($input);
                    error_log($message1, 3, $log_path);
                    $api_token = Str::random(40);
                    $user_timestamp = time();
                    $city_id = !empty($input["city_id"]) ? $input["city_id"] : "";

                    if (!empty($city_id)) {
                        $city = DB::table("cities")->where("name", "LIKE", '%' . $city_id . '%')->first();
                        if (!empty($city) && !empty($city->id)) {
                            $city_id = $city->id;
                        }
                    }

                    $referral = $input["username"];
                    $referredBy = "";
                    $referral_valid_till = "";
                    $referred_type = "";
                    $friendId = "";
                    $savedate = "";
                    $otpCode = rand(1000, 9999);

                    if (isset($input["referred_by"]) && !empty($input["referred_by"])) {
                        $validatereferralcode = DB::table("guest")->where("referral_code", "=", $input["referred_by"])->first();
                        $checkvenuereferral = DB::table("venue")->where("venue_referral", "=", $input["referred_by"])->first();
                        if (!empty($validatereferralcode)) {
                            $referredBy = $validatereferralcode->userid;
                            $friendId = $validatereferralcode->userid;
                            $referral_bonus_validation = DB::table("setting")->where("key", "referral_validity")->first();
                            $dt = \Carbon\Carbon::now()->addMonths($referral_bonus_validation->value);
                            $current_date = $dt->toDateString();
                            $savedate = date('d-m-Y', strtotime($current_date));
                            $referral_valid_till = $current_date;
                            $referred_type = 1;
                        } elseif (empty($validatereferralcode) && !empty($checkvenuereferral)) {
                            $referredBy = $checkvenuereferral->id;
                            $referral_bonus_validation = DB::table("setting")->where("key", "referral_validity")->first();
                            $dt = \Carbon\Carbon::now()->addMonths($referral_bonus_validation->value);
                            $current_date = $dt->toDateString();
                            $savedate = date('d-m-Y', strtotime($current_date));
                            $referral_valid_till = $current_date;
                            $referred_type = 2;
                            $friendId = "";
                        }
                    }

                    $data = array(
                        "email" => $input["email"],
                        "password" => Hash::make($input["password"]),
                        "username" => $input["username"],
                        "dob" => $input["date_of_birth"],
                        // "name" 		=> $input["first_name"],
                        // "last_name" => $input["last_name"],
                        // "state" 	=> $input["state"],
                        "city" => $city_id,
                        // "phone" 	=> $input["phone_number"],
                        "device_type" => $input["device_type"],
                        "device_id" => $input["device_id"],
                        "latitude" => !empty($input["latitude"]) ? $input["latitude"] : '',
                        "longitude" => !empty($input["longitude"]) ? $input["longitude"] : '',
                        "api_token" => $api_token,
                        "status" => 1,
                        "is_active" => 0,
                        "is_profile_complete" => 0,
                        "unique_timestamp" => $user_timestamp,
                        'referral_code' => $referral,
                        'referred_by' => $referredBy,
                        'referral_valid_till' => $referral_valid_till,
                        'referred_type' => $referred_type,
                        'otp' => $otpCode
                    );
                    $last_id = DB::table("guest")->insertGetId($data);
                    if (!empty($referredBy)) {
                        if ($referred_type == 1) {
                            $addfriend = array(
                                "user_id" => $last_id,
                                "friend_id" => $friendId,
                                "status" => 'A',
                                "is_friend" => 1,
                                "is_favourite" => 1
                            );
                            DB::table('user_friends')->insert($addfriend);

                            $addfriendnew = array(
                                "user_id" => $friendId,
                                "friend_id" => $last_id,
                                "status" => 'A',
                                "is_friend" => 1,
                                "is_favourite" => 1
                            );
                            DB::table('user_friends')->insert($addfriendnew);
                        } else {
                            $addvenueFavourite = array(
                                "user_id" => $last_id,
                                "club_id" => $referredBy,
                                "status" => 1,
                            );
                            DB::table('user_favorite_venue')->insert($addvenueFavourite);
                        }
                    }
                    //logic to set all ctegory
                    $get_category = DB::table("category")->select("id")->where("parent_id", "!=", "0")->whereNull("deleted_at")->get()->toArray();

                    $lifestyle = array();
                    foreach ($get_category as $v) {
                        array_push($lifestyle, $v->id);
                    }

                    $implode = implode(",", $lifestyle);

                    //set Default preference
                    $default_preference = array(
                        "user_id" => $last_id,
                        "age_filter_from" => "18",
                        "age_filter_till" => "25",
                        "distance_filter_from" => 0,
                        "distance_filter_to" => 20,
                        "language_id" => isset($input['language_id']) ? $input['language_id'] : 1,
                        "lifestyle" => $implode
                    );

                    DB::table('user_preference')->insert($default_preference);
                    if (!empty($last_id)) {
                        $data["userid"] = $last_id;
                        $data['type'] = "4";
                        $data['profile'] = url("public/default.png");
                        $data["age"] = "0";
                        $data["referral_valid_till"] = $savedate;
                        $data["referred_by"] = $referredBy;

                        if (!empty($input['date_of_birth'])) {
                            $bdate = date("d.m.Y", strtotime($input['date_of_birth']));
                            $bday = new DateTime($bdate); // Your date of birth
                            $today = new Datetime(date('m.d.y'));
                            $diff = $today->diff($bday);

                            $useryear = $diff->y;
                            $usermonth = $diff->m;
                            $userdate = $diff->d;

                            if ($usermonth > 0 || $userdate > 0) {
                                $useryear = $useryear + 1;
                                $data["age"] = $useryear;
                            } else {
                                $data["age"] = $useryear;
                            }
                        }

                        $data["photo_id"] = "false";
                        unset($data["password"]);
                        //code to send an email
                        if (!empty($input['email'])) {
                            try {
                                $objDemo = new \stdClass();
                                $objDemo->demo_one = 'Congratulations, You are successfully registered with ' . env('APP_NAME');
                                $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                                $objDemo->website = Config::get('constants.SENDER_WEBSITE');
                                $objDemo->sender_name = Config::get('constants.SENDER_NAME');
                                $objDemo->receiver_name = $input["username"];
                                $objDemo->referral_code = $referral;
                                $objDemo->email = $input['email'];
                                $objDemo->receiver = $input['email'];
                                $objDemo->password = $input["password"];
                                $objDemo->otp = $otpCode;
                                $objDemo->subject = env('APP_NAME') . " : Your account has been created successfully.";
                                Mail::to($input['email'])->send(new DemoEmail($objDemo));
                            } catch (Exception $e) {
                                return parent::api_response($data, false, $e->getMessage(), 200);
                            }
                        }

                        //code to send notification
                        if (!empty($input["device_id"])) {
                            $subject = isset($csvData['subject_registrtion']) ? $csvData['subject_registrtion'] : "Welcome to Amiggos!";
                            $sub_notify_msg = isset($csvData['message_registrtion']) ? $csvData['message_registrtion'] : "Set your Lifestyle Preferences, book your first event, record a memory and invite your friends!";
                            $notify_data = array();
                            $json_notify_data = json_encode($notify_data);
                            if ($input["device_type"] == 1) {
                                $res_notification = Helper:: sendNotification($input["device_type"], $input['device_id'], $sub_notify_msg, $subject, $json_notify_data, "userapp");
                            } else {
                                $notificationPayload = array(
                                    "body" => $sub_notify_msg,
                                    "titile" => $subject
                                );

                                $dataPayload = array(
                                    "body" => $sub_notify_msg,
                                    "title" => $subject,
                                );

                                $notify_data = array(
                                    "to" => $input['device_id'],
                                    "notification" => $notificationPayload,
                                    "data" => $dataPayload
                                );
                                //$json_notify_data = json_encode($notify_data);
                                $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "userapp");
                            }

                            $insert = DB::table('user_notification')->insert([
                                ['message' => $sub_notify_msg, 'user_id' => $last_id, 'subject' => $subject, "device_type" => $input['device_type'], "notification_key" => 1, "data" => $json_notify_data]
                            ]);
                        }

                        // //code to send notification for referral
                        // if (!empty($validatereferralcode->userid) &&!empty($validatereferralcode->device_id)) {
                        //     $subject = "User Referral!";
                        //     // $sub_notify_msg = ".'$validatereferralcode->username'. has used your referral code. You will get referral bonus";
                        //     $sub_notify_msg = "User has used your referral code. You will get referral bonus";
                        //     $notify_data = array();
                        //     $json_notify_data = json_encode($notify_data);
                        //     if ($validatereferralcode->device_type == 1) {
                        //         $res_notification = Helper:: sendNotification($validatereferralcode->device_type, $validatereferralcode->device_id, $sub_notify_msg, $subject, $json_notify_data, "userapp");
                        //     } else {
                        //         $notificationPayload = array(
                        //             "body" => $sub_notify_msg,
                        //             "titile" => $subject
                        //         );
                        //
                        //         $dataPayload = array(
                        //             "body" => $sub_notify_msg,
                        //             "title" => $subject,
                        //         );
                        //
                        //         $notify_data = array(
                        //             "to" => $validatereferralcode->device_id,
                        //             "notification" => $notificationPayload,
                        //             "data" => $dataPayload
                        //         );
                        //         //$json_notify_data = json_encode($notify_data);
                        //         $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "userapp");
                        //     }
                        //
                        //     $insert = DB::table('user_notification')->insert([
                        //         ['message' => $sub_notify_msg, 'user_id' => $validatereferralcode->userid, 'subject' => $subject, "device_type" => $input['device_type'], "notification_key" => 1, "data" => $json_notify_data]
                        //     ]);
                        // }

                        $message = isset($csvData['new_user_registrtion']) ? $csvData['new_user_registrtion'] : "User registered successfully.";
                        return parent::api_response($data, true, $message, 200);
                    } else {
                        $data["userid"] = [];
                        $message = "something went wrong.";
                        return parent::api_response($data, false, $message, 200);
                    }
                } else {
                    $message = "Username already exist.";
                    return parent::api_response($data, false, $message, 200);
                }
            }
        } catch (\Exception $e) {
            return parent::api_response($data, false, $e->getMessage(), 200);
        }
    }

    public function verifyOTP(Request $request) {
        $input = $request->all();
        $validator = Validator:: make($input, [
                    'email' => 'required',
                    'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            if ($input['email']) {
                $checkUserExists = Guest::where('email', $input['email'])->whereNull("deleted_at")
                        ->first();
                if (empty($checkUserExists)) {
                    $message = ["status" => false, "code" => "email_not_register", "message" => "This email address is not registered", "data" => (object) array()];
                    return response()->json($message, 200);
                }
            } else {
                return parent::api_response('Please enter valid credential');
            }
            if ($checkUserExists) {
                if (trim($checkUserExists['otp']) == trim($input['otp'])) {
                    $update_guest = DB::table("guest")->where("userid", "=", $checkUserExists->userid)->update([
                        "otp" => Null,
                        "is_email_verified" => '1',
                    ]);
                    // return parent::api_response($update_guest, true, $message, 200);
                    $message = ["status" => true, "code" => "success", "message" => "OTP verified successfully", "data" => $update_guest];
                    return response()->json($message, 200);
                } else {
                    $message = ["status" => false, "code" => "incorrect_otp", "message" => "The OTP you've entered is incorrect. Please try again.", "data" => (object) array()];
                    return response()->json($message, 200);
                }
            } else {
                $message = ["status" => false, "code" => "wrong_email", "message" => "Email address is not registered.", "data" => (object) array()];
                return response()->json($message, 200);
            }
        }
    }

    public function resendOTP(Request $request) {
        $input = $request->all();
        $validator = Validator:: make($input, [
                    'email' => 'required',
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            if ($input['email']) {
                $checkUserExists = Guest::where('email', $input['email'])
                        ->first();
                if (empty($checkUserExists)) {
                    $message = ["status" => false, "code" => "wrong_email", "message" => "This email address is not registered", "data" => (object) array()];
                    return response()->json($message, 200);
                }
            } else {
                $message = ["status" => false, "message" => "Please enter valid input", "data" => (object) array()];
                return response()->json($message, 200);
            }
            if ($checkUserExists) {
                $otpCode = rand(1000, 9999);
                $checkUserExists->otp = $otpCode;
                if ($checkUserExists->save()) {
                    //code to send an email
                    if (!empty($checkUserExists['email'])) {
                        try {
                            $objDemo = new \stdClass();
                            $objDemo->demo_one = 'Below is your OTP to verify your email address';
                            $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                            $objDemo->website = Config::get('constants.SENDER_WEBSITE');
                            $objDemo->sender_name = Config::get('constants.SENDER_NAME');
                            $objDemo->receiver = $checkUserExists->email;
                            $objDemo->receiver_name = $checkUserExists->username;
                            $objDemo->otp = $otpCode;
                            $objDemo->subject = env('APP_NAME') . " : Resend OTP.";
                            Mail::to($input['email'])->send(new DemoEmail($objDemo));
                        } catch (Exception $e) {
                            return parent::api_response($data, false, $e->getMessage(), 200);
                        }
                    }
                    $message = ["status" => true, "code" => "success", "message" => "OTP send succesfully", "data" => 1];
                    return response()->json($message, 200);
                } else {
                    return parent::api_response(__("Please try again something went wrong"));
                }
                // if(trim($checkUserExists['otp']) == trim($input['otp']) || trim($input['otp']) == '1234'){
                //   $update_guest = DB::table("guest")->where("userid", "=", $checkUserExists->userid)->update([
                //       "otp" => Null,
                //       "is_email_verified" => '1',
                //   ]);
                //     $message = "OTP verified successfully";
                //     return parent::api_response($update_guest, true, $message, 200);
                // }else{
                //     return parent::api_response(__("The OTP you've entered is incorrect. Please try again."));
                // }
            } else {
                $message = ["status" => false, "code" => "email_notregister", "message" => "Email address is not registered", "data" => (object) array()];
                return response()->json($message, 200);
            }
        }
    }

    public function login(Request $request) {
        $input = $request->all();
        $validator = Validator:: make($input, [
                    "username" => "required",
                    "password" => "required",
                    "device_id" => "required",
                    "device_type" => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            $check_user = DB::table("guest")->select("userid", "username", "password", 'email', 'dob', 'name', 'last_name', 'state', 'city', 'phone', 'api_token', 'profile', 'device_type', 'device_id', 'pronouns', 'is_profile_complete', 'push_notification', 'visible_map', 'message_receive', 'firebase_id', 'deleted_at', "unique_timestamp", 'referral_code', 'referred_by', 'referral_valid_till', 'is_email_verified', 'job_profile')->where("username", "=", $input["username"])->where("status", "=", "1")->orderBy("userid", "desc")->first();
            if (!empty($check_user->deleted_at)) {
                $data = [];
                $message = "Your account has been deleted please contact to amiggo admin.";
                return parent::api_response((object) $data, false, $message, 200);
            }
            if (isset($check_user) && $check_user->is_email_verified == 0) {
                $data = [];
                $message = ["status" => false, "code" => "verify_email", "message" => "Please verify your email to login", "data" => (object) array()];
                return response()->json($message, 200);
            }

            if (!empty($check_user->userid)) {
                if (Hash::check($input["password"], $check_user->password)) {
                    $check_user->profile = url("public/uploads/user/customer/" . $check_user->profile);
                    $check_user->phone = (!empty($check_user->phone) ? $check_user->phone : "");
                    if (empty($check_user->unique_timestamp)) {
                        $check_user->unique_timestamp = "";
                    }

                    $device_id = (!empty($input["device_id"]) ? $input["device_id"] : "" );
                    $api_token = Str::random(40);
                    $update_device_det = DB::table("guest")->where("userid", "=", $check_user->userid)->update([
                        "device_type" => $input['device_type'],
                        "device_id" => $device_id,
                        "api_token" => $api_token,
                        "is_active" => 1,
                        "last_logged_out_time" => NULL
                    ]);
                    unset($check_user->password);
                    unset($check_user->deleted_at);
                    $check_user->api_token = $api_token;
                    $check_user->type = "4";
                    $check_user->age = "0";
                    if (!empty($check_user->dob)) {
                        $bdate = date("d.m.Y", strtotime($check_user->dob));
                        $bday = new DateTime($bdate); // Your date of birth
                        $today = new Datetime(date('m.d.y'));
                        $diff = $today->diff($bday);

                        $useryear = $diff->y;
                        $usermonth = $diff->m;
                        $userdate = $diff->d;

                        if ($usermonth > 0 || $userdate > 0) {
                            $useryear = $useryear + 1;
                            $check_user->age = $useryear;
                        } else {
                            $check_user->age = $useryear;
                        }
                    }

                    $data["photo_id"] = "false";
                    if (!empty($check_username->id_proof)) {
                        $data["photo_id"] = "true";
                    }
                    if (!empty($check_username->referral_valid_till)) {
                        $data["referral_valid_till"] = date('d-m-Y', strtotime($check_username->referral_valid_till));
                    }
                    $data = $check_user;
                    $message = ["status" => true, "code" => "success", "message" => "Login successfully", "data" => $data];
                    return response()->json($message, 200);
                    // $message = "Login successfully.";
                    // return parent::api_response($data, true, $message, 200);
                } else {
                    $data = [];
                    $message = "Invalid password.";
                    return parent::api_response((object) $data, false, $message, 200);
                }
            } else {
                $data = [];
                $message = "Account does not exist.";
                return parent::api_response((object) $data, false, $message, 200);
            }
        }
    }

    public function getProfile(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required"
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $user = DB::table('guest as u')
                        ->where('u.userid', '=', $input['userid'])
                        ->where('u.status', '=', 1)
                        ->leftJoin('states as st', 'u.state', 'st.id')
                        ->leftJoin('cities as c', 'u.city', 'c.id')
                        ->select('u.userid', 'username', 'email', 'u.name', "u.last_name", 'u.dob', 'u.city as city_id', 'u.state as state_id', 'u.phone', 'u.job_profile', 'api_token', 'device_type', 'firebase_id', 'pronouns', 'u.profile', DB::raw('floor(DATEDIFF(CURDATE(),dob) /365) as age'), 'st.name as state', 'c.name as city', 'is_profile_complete', 'other_images', 'visible_map', 'message_receive', 'push_notification')
                        ->first();
                $profile = "";
                if (!empty($user->profile)) {
                    $profile = url($this->uploadsFolder) . "/user/customer/" . $user->profile;
                }
                if (!empty($user->other_images)) {
                    $other_images = explode(",", $user->other_images);
                    foreach ($other_images as $oi => $v) {
                        $other_images[$oi] = url($this->uploadsFolder) . "/user/customer/" . $v;
                    }
                    $user->other_images = $other_images;
                } else {
                    $user->other_images = array();
                }
                if (!empty($user->dob)) {
                    $user->dob = date("d-m-Y", strtotime($user->dob));
                }
                $user->profile = $profile;
                $user->address = $user->state . ',' . $user->city;
                $user->type = "4";

                $user->age = "0";
                if (!empty($user->dob)) {
                    $bdate = date("d.m.Y", strtotime($user->dob));
                    $bday = new DateTime($bdate); // Your date of birth
                    $today = new Datetime(date('m.d.y'));
                    $diff = $today->diff($bday);

                    $useryear = $diff->y;
                    $usermonth = $diff->m;
                    $userdate = $diff->d;

                    if ($usermonth > 0 || $userdate > 0) {
                        $useryear = $useryear + 1;
                        $user->age = $useryear;
                    } else {
                        $user->age = $useryear;
                    }
                }
                $data["user"] = $user;

                $res_msg = isset($csvData['User_profile_data_fetched_successfully']) ? $csvData['User_profile_data_fetched_successfully'] : "";
                return parent::api_response($user, true, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function getProfile_v1(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required"
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $user = DB::table('guest as u')
                        ->where('u.userid', '=', $input['userid'])
                        ->where('u.status', '=', 1)
                        ->leftJoin('countries as cn', 'u.country', 'cn.id')
                        ->leftJoin('states as st', 'u.state', 'st.id')
                        ->leftJoin('cities as c', 'u.city', 'c.id')
                        ->select('u.userid', 'username', 'email', 'u.referral_code', 'u.referred_by', 'u.referral_valid_till', 'u.name', "u.last_name", 'u.dob', 'u.job_profile', 'u.city as city_id', 'u.state as state_id', 'u.country as country_id', 'u.phone', 'api_token', 'device_type', 'firebase_id', 'pronouns', 'u.profile', DB::raw('floor(DATEDIFF(CURDATE(),dob) /365) as age'), 'st.name as state', 'c.name as city', 'cn.name as country', 'is_profile_complete', 'other_images', 'visible_map', 'message_receive', 'push_notification')
                        ->first();


                $profile = "";
                if (!empty($user->profile)) {
                    $profile = url($this->uploadsFolder) . "/user/customer/" . $user->profile;
                }
                if (!empty($user->other_images)) {
                    $other_images = explode(",", $user->other_images);
                    /* echo "<pre>";
                      print_r( $other_images);die; */

                    foreach ($other_images as $oi => $v) {
                        $other_images = url($this->uploadsFolder) . "/user/customer/" . $v;
                        $otherimg[] = array("id" => $v, "image" => $other_images);
                    }

                    $user->other_images = $otherimg;
                    $user->profile_name = $user->profile;
                } else {
                    $user->other_images = array();
                    $user->profile_name = "";
                }
                if (!empty($user->dob)) {
                    $user->dob = date("m/d/Y", strtotime($user->dob));
                }
                $user->profile = $profile;
                /* $user->address = $user->state . ',' . $user->city; */
                $user->address = $user->city . ',' . $user->state;
                $user->type = "4";
                $user->age = "0";
                if (!empty($user->dob)) {
                    $bdate = date("d.m.Y", strtotime($user->dob));
                    $bday = new DateTime($bdate); // Your date of birth
                    $today = new Datetime(date('m.d.y'));
                    $diff = $today->diff($bday);

                    $useryear = $diff->y;
                    $usermonth = $diff->m;
                    $userdate = $diff->d;

                    if ($usermonth > 0 || $userdate > 0) {
                        $useryear = $useryear + 1;
                        $user->age = $useryear;
                    } else {
                        $user->age = $useryear;
                    }
                }
                if (!empty($user->referral_valid_till)) {
                    $data["referral_valid_till"] = date('d-m-Y', strtotime($user->referral_valid_till));
                }
                $data["user"] = $user;
                $res_msg = isset($csvData['User_profile_data_fetched_successfully']) ? $csvData['User_profile_data_fetched_successfully'] : "";
                return parent::api_response($user, true, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function updateProfile(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "first_name" => "required",
                        "last_name" => "required",
                        "date_of_birth" => "required",
                        "location" => "required"
                            // "country" => "required",
                            // "state" => "required",
                            // "city" => "required",
            ]);
            $location = json_decode($input["location"]);
            $countryId = "";
            $stateId = "";
            $cityId = "";
            if (isset($input['job_profile'])) {
                $JobProfile = $input['job_profile'];
            } else {
                $JobProfile = "";
            }
            if (isset($location->country) && $location->country != "") {
                $countryId = Helper::getCountryIdFromName($location->country);
                if (!$countryId && $countryId <= 0) {
                    $message = "This country is not available in the databse.";
                    return parent::api_response((object) [], false, $message, 200);
                }
            } else {
                $message = "Invalid location, Country is not available";
                return parent::api_response((object) [], false, $message, 200);
            }
            if (isset($input['username']) && $input['username'] != "") {
                $checkusername = DB::table("guest")->where("username", $input['username'])->where("userid", "!=", $input["userid"])->first();
                if (empty($checkusername)) {
                  $username = $input['username'];
                }else {
                  $message = "Username is not available";
                  return parent::api_response((object) [], false, $message, 200);
                }
            }

            if (isset($location->state) && $location->state != "") {
                $stateId = Helper::getStateIdFromName($location->state, $countryId);
            }

            if (isset($location->city) && $location->city != "") {
                $cityId = Helper::getCityIdFromName($location->city);
            }

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                //check for username
                $userid = $input["userid"];
                $data = array(
                    "dob" => date("Y-m-d", strtotime($input["date_of_birth"])),
                    "name" => $input["first_name"],
                    "last_name" => $input["last_name"],
                    "country" => $countryId,
                    "state" => $stateId,
                    "city" => $cityId,
                    "job_profile" => $JobProfile,
                    "pronouns" => isset($input['pronouns']) ? $input['pronouns'] : "",
                    "is_profile_complete" => 1
                );

                if (isset($input['phone_number'])) {
                    $data['phone'] = $input['phone_number'];
                }
                if (isset($username)) {
                    $data['username'] = $username;
                }

                if (isset($input['image'])) {
                    $file = $input['image'];
                    $newName = 'user_' . time() . '.' . $file->getClientOriginalExtension();
                    $folder = 'user/customer/';
                    $file->move($this->uploadsFolder . $folder, $newName);
                    $image = $newName;
                    $data['profile'] = $image;
                }

                $data['isNotificationsent'] = 1;

                $getuser = DB::table("guest")->where("userid", $userid)->first();

                $update = DB::table("guest")->where("userid", "=", $userid)->update($data);

                if (!empty($getuser->referral_code)) {
                    $findreferralUser = DB::table("guest")->where("userid", $getuser->referred_by)->first();
                    //code to send notification for referral
                    if (!empty($findreferralUser->userid) && !empty($findreferralUser->device_id) && $getuser->is_profile_complete != 1) {
                        $subject = "User Referral!";
                        // $sub_notify_msg = ".'$findreferralUser->username'. has used your referral code. You will get referral bonus";
                        $sub_notify_msg = "User has used your referral code. You will get referral bonus";
                        $notify_data = array();
                        $json_notify_data = json_encode($notify_data);
                        if ($findreferralUser->device_type == 1) {
                            $res_notification = Helper:: sendNotification($findreferralUser->device_type, $findreferralUser->device_id, $sub_notify_msg, $subject, $json_notify_data, "userapp");
                        } else {
                            $notificationPayload = array(
                                "body" => $sub_notify_msg,
                                "titile" => $subject
                            );

                            $dataPayload = array(
                                "body" => $sub_notify_msg,
                                "title" => $subject,
                            );

                            $notify_data = array(
                                "to" => $findreferralUser->device_id,
                                "notification" => $notificationPayload,
                                "data" => $dataPayload
                            );
                            //$json_notify_data = json_encode($notify_data);
                            $send_notification = Helper::fcmNotification($sub_notify_msg, $notify_data, "userapp");
                        }

                        $insert = DB::table('user_notification')->insert([
                            ['message' => $sub_notify_msg, 'user_id' => $findreferralUser->userid, 'subject' => $subject, "device_type" => $findreferralUser->device_type, "notification_key" => 1, "data" => $json_notify_data]
                        ]);
                    }
                }

                if (!empty($getuser) && $getuser->isNotificationsent != 1) {
                    $this->send_notification_to_nearest_users($userid);
                }
                $message = "Profile updated successfully.";
                return parent::api_response((object) [], true, $message, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function updateProfile_image(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "image" => "required",
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                //check for username
                $userid = $input["userid"];
                $data = array();

                if (isset($input['image'])) {
                    $file = $input['image'];
                    $newName = 'user_' . time() . '.' . $file->getClientOriginalExtension();
                    $folder = 'user/customer/';
                    $file->move($this->uploadsFolder . $folder, $newName);
                    $image = $newName;
                    $data['profile'] = $image;
                }
                $update = DB::table("guest")->where("userid", "=", $userid)->update($data);
                $message = "Profile image updated successfully.";
                return parent::api_response((object) [], true, $message, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function set_defaultprofileOld(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "image_name" => "required",
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {

                $update_image = DB::table("guest")->where("userid", "=", $input["userid"])->update(["profile" => $input["image_name"]]);

                $message = "Image updated successfully.";
                return parent::api_response((object) [], true, $message, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function set_defaultprofile(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "image_name" => "required",
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {

                //check for
                $get_image = DB::table("guest")->where("userid", "=", $input["userid"])->select("profile", "other_images")->first();
                $img = explode(",", $get_image->other_images);
                if (!empty($get_image->profile)) {

                    array_push($img, $get_image->profile);
                }
                $arr = array_diff($img, array($input["image_name"]));
                $arr_implode = implode(",", $arr);
                $update_image = DB::table("guest")->where("userid", "=", $input["userid"])->update(["other_images" => $arr_implode]);




                $update_image = DB::table("guest")->where("userid", "=", $input["userid"])->update(["profile" => $input["image_name"]]);

                $message = "Image updated successfully.";
                return parent::api_response((object) [], true, $message, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function updateFirebaseId(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "firebase_id" => "required"
            ]);
            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            } else {
                $lang_data = parent::getLanguageValues($request);
                $csvData = array();
                if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                    $csvData = $lang_data['csvData'];
                }
                $check_user = Guest::where('userid', '=', $input['userid'])->select('userid')->get();
                if (empty($check_user[0]->userid)) {
                    $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";
                    return parent::api_response([], false, $res_msg, 200);
                }
                Guest::where('userid', '=', $input['userid'])
                        ->update(['firebase_id' => $input['firebase_id']]);

                $res_msg = isset($csvData['Firebase_Id_Updated']) ? $csvData['Firebase_Id_Updated'] : "";
                return parent::api_response([], true, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function get_country(request $request) {
        $input = $request->all();

        $get_state = DB::table("countries")->get();
        if (!empty($get_state) && !empty($get_state[0]->id)) {
            $data["country_list"] = $get_state;
            $message = "Country list fetched successfully.";
        } else {
            $data["country_list"] = [];
            $message = "No record found.";
        }
        return parent::api_response($data, true, $message, 200);
    }

    public function get_state(request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "country_id" => "required"
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            $country_id = $input['country_id'];
            $get_state = DB::table("states")->where("country_id", "=", $country_id)->get();
            if (!empty($get_state[0]->id)) {
                $data["states_list"] = $get_state;
                $message = "State list fetched successfully.";
            } else {
                $data["states_list"] = [];
                $message = "No record found.";
            }
            return parent::api_response($data, true, $message, 200);
        }
    }

    public function get_city(request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "state_id" => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            $state_id = $input['state_id'];
            $get_city = DB::table("cities")->where("state_id", "=", $state_id)->get();
            if (!empty($get_city[0]->id)) {
                $data["city_list"] = $get_city;
                $message = "City list fetched successfully.";
            } else {
                $data["city_list"] = [];
                $message = "No record found.";
            }
        }
        return parent::api_response($data, true, $message, 200);
    }

    public function forgotPassword(request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    'email' => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            try {
                $lang_data = parent::getLanguageValues($request);
                $csvData = array();
                if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                    $csvData = $lang_data['csvData'];
                }
                $user_data = DB::table('guest')
                                ->where('email', '=', $input['email'])
                                ->whereNull("deleted_at")
                                ->select('userid', 'name')
                                ->get()->first();

                if (!empty($user_data)) {
                    $new_password = rand(111111, 9999999);
                    DB::table('guest')
                            ->where('userid', $user_data->userid)
                            ->update(['password' => Hash::make($new_password)]);

                    /* email functionality goes here */
                    $objDemo = new \stdClass();
                    $objDemo->demo_one = 'You have recently requested a new password. </br> If you didn\'t request this password change then please let us know at ' . Config::get('constants.SENDER_EMAIL');
                    $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                    $objDemo->website = Config::get('constants.SENDER_WEBSITE');
                    $objDemo->sender_name = Config::get('constants.SENDER_NAME');
                    $objDemo->receiver = $input['email'];
                    $objDemo->password = $new_password;
                    $objDemo->receiver_name = $user_data->name;
                    $objDemo->subject = env('APP_NAME') . " : Forgot Password";

                    Mail::to($input['email'])->send(new DemoEmail($objDemo));
                    $res_msg = isset($csvData['Your_password_reset_successfullly_check_mail']) ? $csvData['Your_password_reset_successfullly_check_mail'] : "";
                    /* email functionality goes here */
                    return parent::api_response((object) [], true, $res_msg, 200);
                } else {
                    $res_msg = isset($csvData['Please_insert_vaild_email']) ? $csvData['Please_insert_vaild_email'] : "";
                    return parent::api_response((object) [], false, $res_msg, 200);
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                return parent::api_response((object) [], false, $message, 200);
            }
        }
    }

    public function logout(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    'userid' => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response((object) [], false, $err_msg, 200);
        } else {
            $input = $request->all();
            $user_id = $input['userid'];
            $check_user = DB::table('guest')->where('userid', '=', $user_id)->get();
            if (!empty($check_user)) {
                $update_data = DB::table('guest')->where('userid', '=', $user_id)->update(array("is_active" => "0", "api_token" => "", "last_logged_out_time" => date('Y-m-d H:i:s')));
                $res_msg = isset($csvData['logout_seccess']) ? $csvData['logout_seccess'] : "Logout success.";
                return parent::api_response([], true, $res_msg, 200);
            } else {
                $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";
                return parent::api_response([], true, $res_msg, 200);
            }
        }
    }

    //for launguage constant
    public function getLanguageConstant(Request $request) {
        $input = $request->all();



        $data = parent::getLanguageValues($request);

        $csvData = array();

        $res_msg = "";

        if ($data['status'] == 1) {

            if (isset($data['csvData']['Constants_fetched_successfully']) && !empty($data['csvData']['Constants_fetched_successfully'])) {

                $res_msg = $data['csvData']['Constants_fetched_successfully'];
            }

            $csvData = $data['csvData'];
        } else {
            $res_msg = "This language will available soon on next update. (please select another language)";
        }

        return parent::api_response($csvData, true, $res_msg, '200');
    }

    public function getLanguageConstant_v1(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    'type' => "required"
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {

            $type = $input["type"];
            $data = parent::getLanguageValues_v1($request);

            $csvData = array();

            $res_msg = "";

            if ($data['status'] == 1) {

                if (isset($data['csvData']['Constants_fetched_successfully']) && !empty($data['csvData']['Constants_fetched_successfully'])) {

                    $res_msg = $data['csvData']['Constants_fetched_successfully'];
                }

                $csvData = $data['csvData'];
            } else {
                $res_msg = "This language will available soon on next update. (please select another language)";
            }

            return parent::api_response($csvData, true, $res_msg, '200');
        }
    }

    public function updateLatLangUser(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "latitude" => "required",
                    "longitude" => "required",
                    "userid" => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
            //return parent::api_response([],false,$validator->errors()->first(), 200);
        } else {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $user_detail = Guest::where('userid', '=', $input['userid'])->select('userid', 'status')->get();
            if (empty($user_detail[0]->userid)) {
                $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";
                return parent::api_response([], false, $res_msg, 200);
            }
            $user_update = Guest::find($input['userid']);
            $user_update->is_active = '1';
            $user_update->latitude = $input['latitude'];
            $user_update->longitude = $input['longitude'];
            $user_update->save();
            $res_msg = isset($csvData['Location_updated_successfully']) ? $csvData['Location_updated_successfully'] : "";
            return parent::api_response([], true, $res_msg, 200);
        }
    }

    public function user_apptime(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "actiontype" => "required",
                    "userid" => "required"
        ]);

        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
            //return parent::api_response([],false,$validator->errors()->first(), 200);
        } else {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $user_detail = Guest::where('userid', '=', $input['userid'])->select('userid', 'status')->get();
            if (empty($user_detail[0]->userid)) {

                $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";
                return parent::api_response([], false, $res_msg, 200);
            }

            $time = time();
            $insert = DB::table("user_apptime")->insert(["userid" => $input["userid"], "type" => $input["actiontype"], "time" => date("H:i")]);
            $res_msg = "time inserted successfully.";
            return parent::api_response([], true, $res_msg, 200);
        }
    }

    public function validateAppVersion(Request $request) {

        /* update_type : 0-updated version,1-force update, 2-normal update */
        $input = $request->all();
        $validator = Validator::make($input, [
                    //"version" => "required",
                    "version_code" => "required",
                    "device_type" => "required"
        ]);
        $data = array();
        $status = 200;
        $payload = array();
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            $payload['status'] = false;
            $payload['update_type'] = 0;
            $payload['message'] = $err_msg;
            $payload['data'] = (object) $data;
            return Response::json($payload, $status);
        } else {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $data['age'] = "0";
            $data['status'] = "0";
            $data['Status_message'] = isset($csvData['User_not_registered']) ? $csvData['User_not_registered'] : "";
            $data['invite_friend_count'] = "0";
            $data['no_day_register'] = "0";
            $data['is_freind_invities'] = "0";


            if (isset($input['userid'])) {
                $user_rec = Guest::where('userid', '=', $input['userid'])->select("userid", "dob", "status", "freinds_agegroup", "invite_friend_count", "tot_invities", "idproof_aproved", "created_at")->first();
                if (!empty($user_rec)) {
                    $earlier = new DateTime($user_rec->created_at);
                    $later = new DateTime(date("Y-m-d"));
                    $diff = $later->diff($earlier)->format("%a");
                    $data['no_day_register'] = $diff;
                    $data['invite_friend_count'] = $user_rec->invite_friend_count;
                    $data['status'] = $user_rec->status;
                    $dt = \Carbon\Carbon::now();
                    $current_date = $dt->toDateString();
                    if (!empty($user_rec[0]->dob)) {
                        $to = \Carbon\Carbon::createFromFormat('Y-m-d', $current_date);
                        $from = \Carbon\Carbon::createFromFormat('Y-m-d', $user_rec->dob);
                        $data['age'] = $to->diffInYears($from);
                    }
                }
            }


            $device_type = $input["device_type"];
            $version_code = $input["version_code"];


            if ($device_type == 1) {  //ios
                if ($version_code >= 1) {
                    $payload['status'] = true;
                    $payload['update_type'] = 0;
                    $payload['message'] = isset($csvData['Updated_version']) ? $csvData['Updated_version'] : "";
                    $payload['data'] = $data;
                    return Response::json($payload, $status);
                } else {
                    $payload['status'] = true;
                    $payload['update_type'] = 2;
                    $payload['message'] = "Version is not updated";
                    $payload['data'] = $data;
                    return Response::json($payload, $status);
                }
            } else {
                //android
                if ($version_code >= 1.8) {
                    $payload['status'] = true;
                    $payload['update_type'] = 0;
                    $payload['message'] = isset($csvData['Updated_version']) ? $csvData['Updated_version'] : "";
                    $payload['data'] = $data;
                    return Response::json($payload, $status);
                } else {
                    $payload['status'] = true;
                    $payload['update_type'] = 1;
                    $payload['message'] = "Version is not updated.";
                    $payload['data'] = $data;
                    return Response::json($payload, $status);
                }
            }
        }
    }

    public function addImage(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "userid" => "required",
                    "image" => "required"
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {
            $imgarr = array();
            $lang_data = parent::getLanguageValues($request);
            $get_previmage = DB::table("guest")->select("other_images")->where("userid", "=", $input["userid"])->first();

            if (!empty($get_previmage->other_images)) {
                $explode = explode(",", $get_previmage->other_images);
                $imgarr = $explode;
            }


            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            /* $getPreImage = Guest::find($input['userid'])->select('userid','other_images')->first()->toArray();  */
            $pre_other_image = $imgarr;
            $new_other_images = array();
            for ($i = 0; $i < count($input['image']); $i++) {
                $file = $input['image'][$i];
                $newName = 'user_' . time() . $i . '.' . $file->getClientOriginalExtension();
                $folder = 'user/customer/';
                $file->move($this->uploadsFolder . $folder, $newName);
                $new_other_images[] = $newName;
            }




            if (!empty($imgarr)) {
                //$pre_other_images   = explode(',', $imgarr);
                $new_other_images = array_merge($imgarr, $new_other_images);
            } else {
                $pre_other_images = array();
            }


            $other_images = implode(",", $new_other_images);
            //array_push($imgarr,$other_images);
            //$implode_images = implode(",",$imgarr);
            //$implode_images = implode(",",$other_images);
            $guest = Guest::find($input['userid']);

            $guest->other_images = $other_images;
            $update = $guest->save();

            if ($update) {
                foreach ($new_other_images as $oi => $v) {
                    $url = url($this->uploadsFolder) . "/user/customer/" . $v;
                    $new_other_images[$oi] = array("image" => $url, "id" => $v);
                }
                $data['other_images'] = $new_other_images;
                $res_msg = isset($csvData['success']) ? $csvData['success'] : "";
                return parent::api_response($data, true, $res_msg, 200);
            } else {
                foreach ($pre_other_images as $oi => $v) {
                    $url = url($this->uploadsFolder) . "/user/customer/" . $v;
                    $pre_other_images[$oi] = array("image" => $url, "id" => $v);
                }
                $data['other_images'] = $pre_other_images;
                $res_msg = isset($csvData['not_update']) ? $csvData['not_update'] : "Sorry! Image is not added. Please try again";
                return parent::api_response($data, false, $res_msg, 200);
            }
        }
    }

    public function removeOtherImage(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "userid" => "required",
                    "image" => "required"
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {

            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $getPreImage = DB::table("guest")->where("userid", "=", $input['userid'])->select("userid", "other_images")->first();

            $pre_other_image = $getPreImage->other_images;

            if (!empty($pre_other_image)) {
                $pre_other_images = explode(',', $pre_other_image);
            } else {
                $pre_other_images = array();
            }


            $removeOtherImage = array($input['image']);
            $new_other_images = array_diff($pre_other_images, $removeOtherImage);
            $other_images = implode(",", $new_other_images);
            $guest = Guest::find($input['userid']);
            $guest->other_images = $other_images;
            $update = $guest->save();
            if ($update) {
                foreach ($new_other_images as $oi => $v) {
                    $url = url($this->uploadsFolder) . "/user/customer/" . $v;
                    $new_other_images[$oi] = array("image" => $url, "id" => $v);
                }
                $data['other_images'] = $new_other_images;
                $res_msg = isset($csvData['success']) ? $csvData['success'] : "";
                return parent::api_response($data, true, $res_msg, 200);
            } else {
                foreach ($pre_other_images as $oi => $v) {
                    $url = url($this->uploadsFolder) . "/user/customer/" . $v;
                    $pre_other_images[$oi] = array("image" => $url, "id" => $v);
                }
                $data['other_images'] = $pre_other_images;
                $res_msg = isset($csvData['not_update']) ? $csvData['not_update'] : "Sorry! Image is not added. Please try again";
                return parent::api_response($data, false, $res_msg, 200);
            }
        }
    }

    public function notification_setting(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "userid" => "required",
                    "visible_map" => "required|digits:1",
                    "message" => "required|digits:1",
                    "push_notification" => "required|digits:1",
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $data_arr = array(
                "visible_map" => $input['visible_map'],
                "message_receive" => $input['message'],
                "push_notification" => $input['push_notification'],
            );
            $user_update = Guest::where('userid', $input['userid']);
            $update_rec = $user_update->update($data_arr);

            $data["legal"] = "";
            $data["terms_service"] = "";
            $data["privacy_policy"] = "";
            if ($update_rec) {
                $res_msg = isset($csvData['Notification_settings_updated_successfully']) ? $csvData['Notification_settings_updated_successfully'] : "";
                return parent::api_response($data, true, $res_msg, 200);
            } else {
                $res_msg = isset($csvData['Notification_settings_not_updated']) ? $csvData['Notification_settings_not_updated'] : "Oops! Setting is not update. Please try again";
                return parent::api_response($data, true, $res_msg, 200);
            }
        }
    }

    public function my_photoid(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "userid" => "required",
                    "action_type" => "required|in:1,2",
                    "image" => "required_if:action_type,1|image|mimes:jpeg,png,jpg,gif,svg",
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {
            $action_type = $input['action_type'];  //1=save,2=delete
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $check_user = Guest::find($input['userid'])->select('userid', 'name', 'email', 'status')->first();
            if (isset($input['image'])) {
                $file = $input['image'];
                $newName = 'photoid_' . time() . '.' . $file->getClientOriginalExtension();
                $folder = 'photo_id';
                $file->move($this->uploadsFolder . $folder, $newName);
                $image = $newName;
            } else {
                $image = "";
            }

            $data_arr = array(
                "id_proof" => $image,
                "idproof_aproved" => 0,
                "is_idproof" => "1"
            );
            $update_rec = Guest::where("userid", "=", $input['userid'])->update($data_arr);
            /* Email to admin code goes here */
            $admin_data = DB::table('users')
                    ->select('email', 'first_name', 'username')
                    ->where('id', 1)
                    ->first();

            if (isset($admin_data->email) && !empty($admin_data->email) && $action_type == 1) {

                $objDemo = new \stdClass();
                $objDemo->demo_one = $check_user->name . ' rised there id proof for verification, please verify and update.';
                $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                $objDemo->sender_name = Config::get('constants.SENDER_NAME');
                $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                $objDemo->website = Config::get('constants.SENDER_WEBSITE');
                $objDemo->receiver_name = $admin_data->first_name;
                $objDemo->receiver = $admin_data->email;
                $objDemo->subject = "Document Verification";
                // Mail::to($admin_data->email)->send(new DemoEmail($objDemo));
            }

            /* Email to admin code goes here */
            $res_msg = isset($csvData['Photo_id_updated_successfully']) ? $csvData['Photo_id_updated_successfully'] : "Photo ID has been successfully updated";
            return parent::api_response([], true, $res_msg, 200);
        }
    }

    public function delete_photoid(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "userid" => "required",
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {
            //1=save,2=delete
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $data_arr = array(
                "id_proof" => "",
                "idproof_aproved" => 0,
                "is_idproof" => "0"
            );

            $update_guest = DB::table("guest")->where("userid", "=", $input["userid"])->update($data_arr);
            /* Email to admin code goes here */
            $res_msg = isset($csvData['Photo_id_deleted_successfully']) ? $csvData['Photo_id_deleted_successfully'] : "Photo ID deleted successfully.";
            return parent::api_response([], true, $res_msg, 200);
        }
    }

    public function get_photoid(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    "userid" => "required",
        ]);
        if ($validator->fails()) {
            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
        } else {
            //get photo id

            $guest = DB::table("guest")->select("id_proof")->where("userid", "=", $input["userid"])->first();
            if (!empty($guest->id_proof)) {
                $guest->photo_id = url("public/uploads/photo_id/" . $guest->id_proof);
                $guest->image_available = "true";
            } else {
                $guest->photo_id = url("public/default.png");
                $guest->image_available = "false";
            }
            return parent::api_response($guest, true, 'success', 200);
        }
    }

    public function mylifestyle(Request $request) {
        try {
            $input = $request->all();
            $validator = Valilifedator::make($input, [
                        'userid' => 'required'
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            }
            // SELECT i.id,i.name,i.cat_image,GROUP_CONCAT(c.id) AS children FROM category i LEFT JOIN category c ON (i.id IN (c.parent_id)) WHERE i.parent_id = 0 AND i.deleted_at IS null GROUP BY i.id

            $category = DB::table('category as i')->select('i.id', 'i.name', 'i.cat_image', DB::raw('GROUP_CONCAT(c.id) AS children'))
                            ->Join("category as c", function($join) {
                                $join->whereRaw(DB::raw("i.id IN (c.parent_id)"));
                            })
                            ->WHERE('i.parent_id', 0)
                            ->whereNull('i.deleted_at')
                            ->GROUPBY('i.id')
                            ->get()->toArray();
            $preference = DB::table('user_preference')->select('lifestyle')->where('user_id', $input['userid'])->first();
            $lifestyle = $preference->lifestyle;
            foreach ($category as $key => $value) {
                $category[$key]->cat_image = asset('public/uploads/category/' . $value->cat_image);
                if (empty($lifestyle)) {
                    $category[$key]->selected_subCategory = 0;
                } else {
                    $subCategory = explode(',', $value->children);
                    $setlife_style = explode(',', $lifestyle);
                    $matched = array_intersect($subCategory, $setlife_style);
                    $category[$key]->selected_subCategory = count($matched);
                }
            }
            $data['lifestyle'] = $category;
            return parent::api_response($data, true, 'success', 200);
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function mylifestyle_v1(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        'userid' => 'required'
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            }
            // SELECT i.id,i.name,i.cat_image,GROUP_CONCAT(c.id) AS children FROM category i LEFT JOIN category c ON (i.id IN (c.parent_id)) WHERE i.parent_id = 0 AND i.deleted_at IS null GROUP BY i.id

            $category = DB::table('category as i')->select('i.id', 'i.name', 'i.cat_image', DB::raw('GROUP_CONCAT(c.id) AS children'))
                            ->Join("category as c", function($join) {
                                $join->whereRaw(DB::raw("i.id IN (c.parent_id)"));
                            })
                            ->WHERE('i.parent_id', 0)
                            ->whereNull('i.deleted_at')
                            ->GROUPBY('i.id')
                            ->get()->toArray();

            $preference = DB::table('user_preference')->select('lifestyle')->where('user_id', $input['userid'])->first();
            $lifestyle = $preference->lifestyle;
            foreach ($category as $key => $value) {

                $category[$key]->cat_image = asset('public/uploads/category/' . $value->cat_image);
                if (empty($lifestyle)) {
                    $category[$key]->selected_subCategory = 0;
                } else {
                    $subCategory = explode(',', $value->children);
                    $setlife_style = explode(',', $lifestyle);
                    $matched = array_intersect($subCategory, $setlife_style);
                    $category[$key]->selected_subCategory = count($matched);
                }
                $category[$key]->children_count = count(explode(",", $value->children));
            }

            $data['lifestyle'] = $category;
            return parent::api_response($data, true, 'success', 200);
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function mylifestyleCategory(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        'userid' => 'required',
                        'catid' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            }
            $category = DB::table('category')->select('id', 'name', 'parent_id')
                            ->where('parent_id', $input['catid'])->whereNull("deleted_at")->get()->toArray();
            if (!empty($category)) {
                $preference = DB::table('user_preference')->select('lifestyle')->where('user_id', $input['userid'])->first();
                $lifestyle = $preference->lifestyle;
                foreach ($category as $key => $value) {
                    $category[$key]->selected = 0;
                    $setlife_style = explode(',', $lifestyle);
                    $matched = in_array($value->id, $setlife_style);
                    if ($matched) {
                        $category[$key]->selected = 1;
                    }
                }
                $data['lifestyle'] = $category;
                return parent::api_response($data, true, 'success', 200);
            } else {
                return parent::api_response([], false, 'Category is not found', 200);
            }
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function setMylifestyle(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "catid" => "required_without:remove_catid",
                        "remove_catid" => "required_without:catid"
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            }
            $preference = DB::table('user_preference')->select('lifestyle')->where('user_id', $input['userid'])->first();
            $lifestyle = $preference->lifestyle;
            //if lifestyle is not selected
            if (empty($lifestyle)) {
                $update = DB::table('user_preference')->where('user_id', $input['userid'])->update(['lifestyle' => $input['catid']]);
            } else {
                $settedlife_style = explode(',', $lifestyle);
                if ($input['remove_catid'] != "") {
                    $remove_catid = explode(',', $input['remove_catid']);
                    $settedlife_style = array_diff($settedlife_style, $remove_catid);
                }
                if ($input['catid'] != "") {
                    $new_catid = explode(',', $input['catid']);
                    $settedlife_style = array_merge($settedlife_style, $new_catid);
                }
                // print_r($settedlife_style);die();
                $settedlife_style = array_unique($settedlife_style);
                $setlife_style = implode(',', $settedlife_style);
                $update = DB::table('user_preference')->where('user_id', $input['userid'])->update(['lifestyle' => $setlife_style]);
            }
            if ($update) {
                $message = "Life style has been successfully saved";
                return parent::api_response([], true, $message, 200);
            } else {
                $message = "Oops! Life style is not update. Please try again";
                return parent::api_response([], false, $message, 200);
            }
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function setMylifestyle_v1(Request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "catid" => "required_without:remove_catid"
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            }

            $preference = DB::table('user_preference')->select('id', 'lifestyle')->where('user_id', $input['userid'])->first();
            if (empty($preference->id)) {
                $message = "User preference is not available.";
                return parent::api_response([], false, $message, 200);
            }

            $lifestyle = $preference->lifestyle;
            //if lifestyle is not selected
            //if(empty($lifestyle)){
            $update = DB::table('user_preference')->where('user_id', $input['userid'])->update(['lifestyle' => $input['catid']]);
            /* }else{
              $settedlife_style  = explode(',',$lifestyle);
              if($input['remove_catid']!=""){
              $remove_catid =  explode(',',$input['remove_catid']);
              $settedlife_style = array_diff($settedlife_style,$remove_catid);
              }
              if($input['catid']!=""){
              $new_catid =  explode(',',$input['catid']);
              $settedlife_style = array_merge($settedlife_style,$new_catid);
              }
              // print_r($settedlife_style);die();
              $settedlife_style = array_unique($settedlife_style);
              $setlife_style = implode(',', $settedlife_style);
              $update = DB::table('user_preference')->where('user_id',$input['userid'])->update(['lifestyle'=>$setlife_style]);
              } */
            if ($update) {
                $message = "Life style has been successfully saved";
                return parent::api_response([], true, $message, 200);
            } else {
                $message = "Oops! Life style is not update. Please try again";
                return parent::api_response([], false, $message, 200);
            }
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function dashboard_old(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "latitude" => "required",
                        "longitude" => "required",
            ]);

            if ($validator->fails()) {

                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $latitude = $input["latitude"];
                $longitude = $input["longitude"];


                if (!empty($input["search"])) {
                    $search = $input["search"];
                } else {
                    $search = "";
                }

                if (!empty($input["category_id"])) {
                    $category_id = $input["category_id"];
                    //get category
                    $get_cat = DB::table("category")->select("name")->where("id", "=", $category_id)->first();
                    $cat_name = $get_cat->name;
                } else {
                    $category_id = "";
                    $cat_name = "";
                }
                //for nearby venue
                $user_preference = DB::table("user_preference")->where("user_id", "=", $input["userid"])->first();
                $venue_record = DB::table('venue as v')->select(DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'v.id', 'v.latitude', 'v.longitude', 'v.map_icon as image', 'v.agelimit', 'v.name', 'v.state_id', "v.city_id", "v.venue_type", "v.is_amigo_club", "v.reservation", "v.go_order", 'v.teamadmin_id');
                if (!empty($search)) {
                    $venue_record = $venue_record->where('v.name', 'LIKE', $search . '%');
                }
                if (!empty($category_id)) {
                    $venue_record = $venue_record->whereRaw(DB::raw("find_in_set('" . $category_id . "',venue_type)"));
                }
                $venue_record = $venue_record->whereNull('v.deleted_at');
                if (!empty($venue_type)) {
                    $venue_record = $venue_record->whereRaw('FIND_IN_SET("' . $category_id . '",v.venue_type)');
                }
                if (isset($user_preference->distance_filter_from) && isset($user_preference->distance_filter_to)) {

                    $venue_record = $venue_record->having('distance_from_mylocation', '>=', $user_preference->distance_filter_from)
                            ->having('distance_from_mylocation', '<=', $user_preference->distance_filter_to);
                }

                $venue_record = $venue_record->orderBy("distance_from_mylocation", "Asc")->where("status", "=", "1")->get()->toArray();


                //for nearby user
                $near_user = DB::table('guest as g')->select(DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'g.userid as id', 'g.latitude', 'g.longitude', 'g.profile as image', 'g.name');
                if (!empty($search)) {
                    $near_user = $near_user->where('g.name', 'LIKE', $search . '%');
                }
                $near_user = $near_user->whereNull('g.deleted_at');
                if (!empty($venue_type)) {
                    $near_user = $near_user->whereRaw('FIND_IN_SET("' . $venue_type . '",g.venue_type)');
                }
                if (isset($user_preference->distance_filter_from) && isset($user_preference->distance_filter_to)) {

                    $near_user = $near_user->having('distance_from_mylocation', '>=', $user_preference->distance_filter_from)
                            ->having('distance_from_mylocation', '<=', $user_preference->distance_filter_to);
                }

                $near_user = $near_user->where("userid", "!=", $input["userid"])->where("is_profile_complete", "=", "1")->orderBy("distance_from_mylocation", "Asc")->where("status", "=", "1")->get()->toArray();


                if (!empty($venue_record)) {
                    foreach ($venue_record as $key => $val) {
                        $nearuser = $this->current_venue_user($val->latitude, $val->longitude);

                        if ($val->reservation == 2 && $val->go_order == 2) {
                            $venue_record[$key]->near_by_count = 0;
                        } else if (empty($val->teamadmin_id)) {
                            $venue_record[$key]->near_by_count = 0;
                        } else {
                            $venue_record[$key]->near_by_count = $nearuser;
                        }


                        $venue_record[$key]->type = "venue";
                        //get state
                        $get_state = DB::table("states")->where("id", "=", $val->state_id)->first();
                        $get_city = DB::table("cities")->where("id", "=", $val->city_id)->first();
                        $get_cat = DB::table("category")->where("id", "=", $val->venue_type)->first();

                        $venue_record[$key]->city = (!empty($get_city->name) ? $get_city->name : "" );
                        $venue_record[$key]->state = (!empty($get_state->name) ? $get_state->name : "" );

                        $venue_record[$key]->address = $venue_record[$key]->city . "," . $venue_record[$key]->state;

                        $venue_record[$key]->venue_category = (!empty($get_cat->name) ? $get_cat->name : "" );
                        if (!empty($val->image)) {
                            $venue_record[$key]->image = url("public/uploads/venue/map_icone") . "/" . $val->image;
                        } else {
                            $venue_record[$key]->image = url("public/default.png");
                        }

                        $venue_record[$key]->is_google_venue = "0";
                        if ($val->is_amigo_club == 0) {
                            $venue_record[$key]->is_google_venue = "1";
                        }
                    }
                }

                if (!empty($near_user)) {
                    foreach ($near_user as $key => $val) {
                        $near_user[$key]->type = "user";
                        $near_user[$key]->near_by_count = "1";
                        $near_user[$key]->city = "";
                        $near_user[$key]->state = "";
                        $near_user[$key]->address = "";
                        $near_user[$key]->venue_category = "";
                        $near_user[$key]->is_google_venue = "0";

                        if (!empty($val->image)) {
                            $near_user[$key]->image = url("public/uploads/user/customer") . "/" . $val->image;
                        } else {
                            $near_user[$key]->image = url("public/default.png");
                        }
                    }
                }

                $venue_arr = array_merge($venue_record, $near_user);

                $success = "venue detail fetch successfully.";

                return parent::api_response(["venue" => $venue_arr], true, $success, 200);
            }
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function dashboard(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "latitude" => "required",
                        "longitude" => "required",
            ]);

            if ($validator->fails()) {

                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $latitude = $input["latitude"];
                $longitude = $input["longitude"];


                if (!empty($input["search"])) {
                    $search = $input["search"];
                } else {
                    $search = "";
                }

                if (!empty($input["category_id"])) {
                    $category_id = $input["category_id"];
                    //get category
                    $get_cat = DB::table("category")->select("name")->where("id", "=", $category_id)->first();
                    $cat_name = $get_cat->name;
                } else {
                    $category_id = "";
                    $cat_name = "";
                }

                //for images
                if (!empty($category_id)) {
                    if ($category_id == "186") {
                        $icon = "home1.png";
                    } else if ($category_id == "194") {
                        $icon = "home2.png";
                    } else if ($category_id == "193") {

                        $icon = "home4.png";
                    } else if ($category_id == "195") {
                        $icon = "home3.png";
                    } else if ($category_id == "196") {
                        $icon = "home5.png";
                    } else if ($category_id == "197") {
                        $icon = "home6.png";
                    } else if ($category_id == "198") {
                        $icon = "home7.png";
                    }
                }


                //for nearby venue
                $user_preference = DB::table("user_preference")->where("user_id", "=", $input["userid"])->first();

                $venue_record = DB::table('venue as v')->select(DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'v.id', 'v.latitude', 'v.longitude', 'v.map_icon as image', 'v.agelimit', 'v.name', 'v.state_id', "v.city_id", "v.venue_type", "v.is_amigo_club", "v.reservation", "v.go_order", "v.teamadmin_id");
                if (!empty($search)) {
                    $venue_record = $venue_record->where('v.name', 'LIKE', $search . '%');
                }
                /* if(!empty($search)) {
                  $venue_record = $venue_record->where('v.name', 'LIKE', $search . '%');
                  } */
                if (!empty($category_id)) {
                    $venue_record = $venue_record->whereRaw(DB::raw("find_in_set('" . $category_id . "',venue_type)"));
                }
                $venue_record = $venue_record->whereNull('v.deleted_at');
                if (!empty($venue_type)) {
                    $venue_record = $venue_record->whereRaw('FIND_IN_SET("' . $category_id . '",v.venue_type)');
                }

                $distance_setting = DB::table("setting")->where("key", "distance_filter_to")->first();
                $distance_filter_from = 0;
                $distance_filter_to = $distance_setting->value;

                if (isset($distance_filter_from) && isset($distance_filter_to) && empty($search)) {

                    $venue_record = $venue_record->having('distance_from_mylocation', '>=', $distance_filter_from)
                            ->having('distance_from_mylocation', '<=', $distance_filter_to);
                }

                if (isset($user_preference->lifestyle) && empty($search) && empty($category_id)) {
                    $lifestyle = explode(",", $user_preference->lifestyle);
                    /* $venue_record = $venue_record->whereRaw(DB::raw("find_in_set('".$user_preference->lifestyle."',venue_type)")); */
                    $venue_record = $venue_record->where(function($venue_record) use($lifestyle) {
                        foreach ($lifestyle as $lifestyles) {
                            $venue_record = $venue_record->orwhereRaw('FIND_IN_SET("' . $lifestyles . '",v.venue_type)');
                        };
                    });
                }
                //if(empty($search)){
                $venue_record = $venue_record->orderBy("distance_from_mylocation", "Asc")->where("status", "=", "1")->whereNull("deleted_at")->get()->toArray();



                /* echo "<pre>";
                  print_r($venue_record);die; */
                /* }
                  else{
                  $venue_record = $venue_record->orderBy("id", "DESC")->where("status","=","1")->whereNull("deleted_at")
                  ->get()->toArray();

                  } */


                //for nearby user
                /* if(empty($search)) { */
                $near_user = DB::table('guest as g')->select(DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'g.userid as id', 'g.latitude', 'g.longitude', 'g.profile as image', 'g.name', 'g.state', 'g.city');
                if (!empty($search)) {
                    $near_user = $near_user->where('g.name', 'LIKE', $search . '%');
                }
                /* if(!empty($search)) {
                  $near_user = $near_user->where('g.name', 'LIKE', $search . '%');
                  } */
                $near_user = $near_user->whereNull('g.deleted_at')->where("visible_map", "=", 1);
                if (!empty($venue_type)) {
                    $near_user = $near_user->whereRaw('FIND_IN_SET("' . $venue_type . '",g.venue_type)');
                }
                if (isset($distance_filter_from) && isset($distance_filter_to) && empty($search)) {

                    $near_user = $near_user->having('distance_from_mylocation', '>=', $distance_filter_from)
                            ->having('distance_from_mylocation', '<=', $distance_filter_to);
                }

                //if(empty($search)){
                $near_user = $near_user->where("is_profile_complete", "=", "1")->where("status", "=", "1")->orderBy("distance_from_mylocation", "Asc")->get()->toArray();
                //}
                /* else{
                  $near_user = $near_user->where("userid","!=",$input["userid"])->where("is_profile_complete","=","1")->where("status","=","1")->orderBy("id", "DESC")
                  ->get()->toArray();

                  } */






                if (!empty($venue_record)) {
                    foreach ($venue_record as $key => $val) {
                        $nearuser = $this->active_user_venue($val->id);


                        if ($val->reservation == 2 && $val->go_order == 2) {
                            $venue_record[$key]->near_by_count = 0;
                        } else if (empty($val->teamadmin_id)) {

                            $venue_record[$key]->near_by_count = 0;
                        } else {

                            $venue_record[$key]->near_by_count = $nearuser;
                        }



                        $venue_record[$key]->type = "venue";
                        //get state
                        $get_state = DB::table("states")->where("id", "=", $val->state_id)->first();
                        $get_city = DB::table("cities")->where("id", "=", $val->city_id)->first();
                        $get_cat = DB::table("category")->where("id", "=", $val->venue_type)->first();

                        $venue_record[$key]->city = (!empty($get_city->name) ? $get_city->name : "" );
                        $venue_record[$key]->state = (!empty($get_state->name) ? $get_state->name : "" );

                        $venue_record[$key]->address = $venue_record[$key]->city . "," . $venue_record[$key]->state;

                        $venue_record[$key]->venue_category = (!empty($get_cat->name) ? $get_cat->name : "" );
                        if (!empty($val->image)) {
                            $venue_record[$key]->image = url("public/uploads/venue/map_icone") . "/" . $val->image;
                        } else {
                            if (!empty($category_id)) {
                                $venue_record[$key]->image = url("public/icon/" . $icon);
                            } else {
                                $venue_record[$key]->image = url("public/default.png");
                            }
                        }

                        $venue_record[$key]->is_google_venue = "0";
                        if ($val->is_amigo_club == 0) {
                            $venue_record[$key]->is_google_venue = "1";
                        }

                        /* if reservation and goorder is on than convert to amiggo venue */
                        if ($val->reservation == 1 || ($val->go_order == 1)) {
                            $venue_record[$key]->is_google_venue = "0";
                            $venue_record[$key]->is_amigo_club = "1";
                        }

                        if (empty($val->latitude)) {
                            $val->latitude = "";
                        }

                        if (empty($val->longitude)) {
                            $val->longitude = "";
                        }
                    }
                }

                if (!empty($near_user)) {
                    foreach ($near_user as $key => $val) {
                        $near_user[$key]->type = "user";
                        $near_user[$key]->near_by_count = "1";
                        $get_state = DB::table("states")->where("id", "=", $val->state)->first();
                        $get_city = DB::table("cities")->where("id", "=", $val->city)->first();
                        $near_user[$key]->city = (!empty($get_city->name) ? $get_city->name : "" );
                        $near_user[$key]->state = (!empty($get_state->name) ? $get_state->name : "" );
                        $near_user[$key]->address = "";
                        $near_user[$key]->venue_category = "";
                        $near_user[$key]->is_google_venue = "0";

                        if (empty($val->latitude)) {
                            $val->latitude = "";
                        }

                        if (empty($val->longitude)) {
                            $val->longitude = "";
                        }

                        if (!empty($val->image)) {
                            $near_user[$key]->image = url("public/uploads/user/customer") . "/" . $val->image;
                        } else {
                            $near_user[$key]->image = url("public/default.png");
                        }
                    }
                }

                $venue_arr = array_merge($venue_record, $near_user);
                /* $booking=array_merge($booking_list,$booking_invite); */
                $record = array_column($venue_arr, 'distance_from_mylocation');

                array_multisort($record, $venue_arr);

                $success = "venue detail fetch successfully.";

                return parent::api_response(["venue" => $venue_arr], true, $success, 200);
            }
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function dashboard_v1(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "latitude" => "required",
                        "longitude" => "required",
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $latitude = $input["latitude"];
                $longitude = $input["longitude"];

                if (!empty($input["search"])) {
                    $search = $input["search"];
                } else {
                    $search = "";
                }

                if (!empty($input["birthday_offer_status"])) {
                    $birthday_offer_status = $input["birthday_offer_status"];
                } else {
                    $birthday_offer_status = "";
                }

                if (!empty($input["category_id"])) {
                    $category_id = $input["category_id"];
                    //get category
                    $get_cat = DB::table("category")->select("name")->where("id", "=", $category_id)->first();
                    $cat_name = $get_cat->name;
                } else {
                    $category_id = "";
                    $cat_name = "";
                }


                //for images
                if (!empty($category_id)) {
                    if ($category_id == "186") {
                        $icon = "home1.png";
                    } else if ($category_id == "194") {
                        $icon = "home2.png";
                    } else if ($category_id == "193") {
                        $icon = "home3.png";
                    } else if ($category_id == "195") {
                        $icon = "home4.png";
                    } else if ($category_id == "196") {
                        $icon = "home5.png";
                    } else if ($category_id == "197") {
                        $icon = "home6.png";
                    } else if ($category_id == "198") {
                        $icon = "home7.png";
                    } else if ($category_id == "226") {
                        $icon = "home8.jpeg";
                    }
                }

                $userid = !empty($input["userid"]) ? (int) $input["userid"] : 0;
                $filter_by_preference = !empty($input["filter_by_preference"]) ? (int) $input["filter_by_preference"] : 0;

                //for nearby venue
                $user_preference = DB::table("user_preference")->where("user_id", "=", $input["userid"])->first();
//                \DB::enableQueryLog();
                $venue_record = DB::table('venue as v')->select(DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'v.id', 'v.latitude', 'v.longitude', 'v.venue_home_image as image', 'v.agelimit', 'v.name', 'v.birthday_offer_status', 'v.state_id', "v.city_id", "v.venue_type", "v.is_amigo_club", "v.reservation", "v.go_order", DB::raw('(SELECT count(user_favorite_venue.id) as ids FROM user_favorite_venue WHERE user_favorite_venue.user_id=' . $userid . ' AND user_favorite_venue.club_id=v.id AND user_favorite_venue.status=1) as is_favorite_venue')
                );

                if (!empty($search) && $search != "") {
                    $venue_record = $venue_record->where('v.name', 'LIKE', "%" . $search . "%");
                }

                if (!empty($category_id)) {
                    $venue_record = $venue_record->whereRaw(DB::raw("find_in_set('" . $category_id . "',venue_type)"));
                }

                if (!empty($birthday_offer_status)) {
                    $venue_record = $venue_record->where("v.birthday_offer_status", "=", $birthday_offer_status);
                }

                $distance_setting = DB::table("setting")->where("key", "distance_filter_to")->first();
                $distance_filter_from = 0;
                $user_radius = $distance_setting->value;
                $venue_radius = $distance_setting->value;

                if (!empty($request->venue_radius)) {
                    $venue_radius = $request->venue_radius;
                }

                $zoom_value = 0;
                $zoom_setting = DB::table("setting")->where("key", "zoom_value")->first();
                $zoom_value = $zoom_setting->value;

                if (isset($distance_filter_from) && isset($venue_radius) && empty($search)) {
                    $venue_record = $venue_record->having('distance_from_mylocation', '>=', $distance_filter_from)
                            ->having('distance_from_mylocation', '<=', $venue_radius);
                }

                //if(isset($user_preference->lifestyle) && empty($search) && empty($category_id)){
                if (isset($user_preference->lifestyle)) {
                    $lifestyle = explode(",", $user_preference->lifestyle);

                    if (!empty($category_id)) {
                        //$lifestyle[] = $category_id;
                    }

                    $venue_record = $venue_record->where(function($venue_query) use($lifestyle) {
                        foreach ($lifestyle as $lifestyles) {
                            $venue_query = $venue_query->orWhereRaw('FIND_IN_SET("' . $lifestyles . '",v.venue_type)');
                        }
                    });
                }

                //if(empty($search)){
                $venue_record = $venue_record->orderBy("distance_from_mylocation", "Asc")->where("v.status", 1)->whereNull("v.deleted_at");

//                dd($venue_record->toSql());
                //$venue_record = $venue_record->orderBy("distance_from_mylocation", "Asc")->where("status","=","1")->whereNull("deleted_at")->toSql();
//                \Log::info($venue_record->toSql());

                $venue_record = $venue_record->get()->toArray();
//                dd($venue_record);
//                dd(\DB::getQueryLog());
//                DB::enableQueryLog();
                //for nearby user
                /* if(empty($search)) { */
                $near_user = DB::table('guest as g')->select(
                                DB::raw('(SELECT count(user_friends.id) as ids FROM user_friends WHERE user_id=' . $userid . ' AND friend_id=g.userid AND status="A" AND is_friend=1) as is_real_friend'),
                                //DB::raw("DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),`dob`)), '%Y')+0 as userAge"),
                                DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'g.userid as id', 'g.latitude', 'g.longitude', 'g.profile as image', 'g.name','g.last_name', 'g.state', 'g.city', 'g.dob')
                        ->join('user_preference', 'g.userid', '=', 'user_preference.user_id');

                if (!empty($search)) {
                    $near_user = $near_user->where(function ($query) use ($search) {
                        $query->where(DB::raw("CONCAT(g.name, ' ', g.last_name)"), 'like', '%' . $search . '%')
                                ->orWhere('g.username', 'like', '%' . $search . '%')
                                ->orWhere('g.email', 'like', '%' . $search . '%');
                    });
                }

                /* if(!empty($search)) {
                  $near_user = $near_user->where('g.name', 'LIKE', $search . '%');
                  } */
                $near_user = $near_user->whereNull('g.deleted_at')->where("visible_map", "=", 1);

                if (!empty($venue_type)) {
                    $near_user = $near_user->whereRaw('FIND_IN_SET("' . $venue_type . '",g.venue_type)');
                }

//                dd($user_preference->lifestyle);
                if (isset($user_preference->lifestyle) && $filter_by_preference == 1) {
                    $lifestyle = explode(",", $user_preference->lifestyle);

                    $near_user = $near_user->where(function($near_user_query) use($lifestyle) {
                        foreach ($lifestyle as $lifestyles) {
                            $near_user_query = $near_user_query->orWhereRaw('FIND_IN_SET("' . $lifestyles . '",user_preference.lifestyle)');
                        }
                    });
                }

                if (!empty($request->user_radius)) {
                    $user_radius = (int) $request->user_radius;
                }

                if (!empty($request->age_range)) {
                    $age = explode('-', $request->age_range);
                    if (!empty($age) && count($age) == 2) {
                        $near_user = $near_user->whereRaw("DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),`dob`)), '%Y')+0 BETWEEN " . trim($age[0]) . " AND " . trim($age[1]));
                    }
                }

                if (isset($distance_filter_from) && isset($user_radius) && empty($search)) {
                    $near_user = $near_user->having('distance_from_mylocation', '>=', $distance_filter_from)
                            ->having('distance_from_mylocation', '<=', $user_radius);
                }

                //if(empty($search)){
                $near_user = $near_user->where("is_profile_complete", "=", "1")
                        ->where('g.userid', '!=', $userid)
                        ->where('user_preference.user_id', '!=', $userid)
                        ->whereNotNull("device_id")
                        ->where("device_id", "!=", "")
                        ->where("status", "=", "1")
                        ->orderBy("distance_from_mylocation", "Asc")
                        ->get()
                        ->toArray();
                //}
                /* else{
                  $near_user = $near_user->where("userid","!=",$input["userid"])->where("is_profile_complete","=","1")->where("status","=","1")->orderBy("id", "DESC")
                  ->get()->toArray();

                  } */
//                dd(DB::getQueryLog()[0]['query']);

                if (!empty($venue_record)) {
                    foreach ($venue_record as $key => $val) {

                        $nearuser = $this->active_user_venue($val->id);

                        if ($val->reservation == 2 && $val->go_order == 2) {
                            $venue_record[$key]->near_by_count = 0;
                        } else if (empty($val->teamadmin_id)) {
                            $venue_record[$key]->near_by_count = 0;
                        } else {
                            $venue_record[$key]->near_by_count = $nearuser;
                        }



                        $venue_record[$key]->type = "venue";
                        //get state
                        $get_state = DB::table("states")->where("id", "=", $val->state_id)->first();
                        $get_city = DB::table("cities")->where("id", "=", $val->city_id)->first();
                        $get_cat = DB::table("category")->where("id", "=", $val->venue_type)->first();

                        $get_sub_preferance = DB::table("category")
                                ->whereIn("id", explode(",", $val->venue_type))
                                ->where("parent_id", "!=", 0)
                                ->get();

                        $venue_record[$key]->city = (!empty($get_city->name) ? $get_city->name : "" );
                        $venue_record[$key]->state = (!empty($get_state->name) ? $get_state->name : "" );

                        $venue_record[$key]->address = $venue_record[$key]->city . "," . $venue_record[$key]->state;

                        $venue_record[$key]->venue_category = (!empty($get_cat->name) ? $get_cat->name : "" );

                        $venue_record[$key]->venue_sub_preference = !empty($get_sub_preferance) ? $get_sub_preferance : array();

//                        if (!empty($val->image)) {
//                            $venue_record[$key]->image = url("public/uploads/venue/map_icone") . "/" . $val->image;
//                        } else {
//                            if (!empty($category_id) && !empty($icon)) {
//                                $venue_record[$key]->image = url("public/icon/" . $icon);
//                            } else {
//                                $venue_record[$key]->image = url("public/default.png");
//                            }
//                        }

                        if (!empty($val->image)) {
                            $explode = explode(",", $val->image);  //code to bring image from cloud front
                            $cloud_url = Config::get('constants.Cloud_url');
                            @$check_image = file_get_contents($cloud_url . "/uploads/club/home/" . $explode[0]);
                            if (!empty($check_image)) {
                                $venue_record[$key]->image = $cloud_url . "/uploads/club/home/" . $val->image;
                            } else {
                                $venue_record[$key]->image = url($this->uploadsFolder) . "/venue/home_image/" . $explode[0];
                            }
                        } else {
                            $venue_record[$key]->image = url("public/default.png");
                        }

                        $venue_record[$key]->is_google_venue = "0";
                        if ($val->is_amigo_club == 0) {
                            $venue_record[$key]->is_google_venue = "1";
                        }

                        /* if reservation and goorder is on than convert to amiggo venue */
                        if ($val->reservation == 1 || ($val->go_order == 1)) {
                            $venue_record[$key]->is_google_venue = "0";
                            $venue_record[$key]->is_amigo_club = "1";
                        }

                        $venue_record[$key]->is_favorite_venue = !empty($val->is_favorite_venue) && $val->is_favorite_venue > 0 ? true : false;

                        /* $venue_record[$key]->default_zoom_value=$zoom_value; */
                    }
                }

                // dd($venue_record);
//                dd($near_user);

                if (!empty($near_user)) {
                    foreach ($near_user as $key => $val) {

                        $near_user[$key]->type = "user";
                        $near_user[$key]->near_by_count = "1";
                        $get_state = DB::table("states")->where("id", "=", $val->state)->first();
                        $get_city = DB::table("cities")->where("id", "=", $val->city)->first();
                        $near_user[$key]->city = (!empty($get_city->name) ? $get_city->name : "" );
                        $near_user[$key]->state = (!empty($get_state->name) ? $get_state->name : "" );
                        $near_user[$key]->dob = (!empty($val->dob) ? $val->dob : "" );
                        $near_user[$key]->is_real_friend = !empty($val->is_real_friend) && $val->is_real_friend > 0 ? true : false;
                        $near_user[$key]->address = "";
                        $near_user[$key]->venue_category = "";
                        $near_user[$key]->is_google_venue = "0";
                        /* $near_user[$key]->default_zoom_value=$zoom_value; */

                        if (!empty($val->image)) {
                            $near_user[$key]->image = url("public/uploads/user/customer") . "/" . $val->image;
                        } else {
                            $near_user[$key]->image = url("public/default.png");
                        }
                    }
                }

//              dd($near_user);
                $venue_arr = array_merge($venue_record, $near_user);
//                $venue_arr =$near_user;
//                $venue_arr =$venue_record;
                /* $booking=array_merge($booking_list,$booking_invite); */
                $record = array_column($venue_arr, 'distance_from_mylocation');

                array_multisort($record, $venue_arr);

                $success = "venue detail fetch successfully.";

                return parent::api_response(["venue" => $venue_arr, "default_zoom_value" => $zoom_value], true, $success, 200);
            }
        } catch (Exception $e) {
            return parent::api_response([], false, $e->getMessage(), 200);
        }
    }

    public function active_user_venue($venue_id) {
        $distance = 1;
        $cnt = 0;
        //get venue
        $venue = DB::table("venue")->select("latitude", "longitude")->where("id", "=", $venue_id)->first();
        $latitude = (!empty($venue->latitude) ? $venue->latitude : "" );
        $longitude = (!empty($venue->longitude) ? $venue->longitude : "" );
        if (!empty($latitude) && !empty($longitude)) {
            $get_user = DB::table("guest")->select(DB::raw("(((acos(sin((" . $latitude . "*pi()/180)) * sin((`latitude`*pi()/180))+cos((" . $latitude . "*pi()/180)) * cos((`latitude`*pi()/180)) * cos(((" . $longitude . "- `longitude`)*pi()/180))))*180/pi())*60*1.1515*1.609344) as distance"));
            $get_user = $get_user->havingRaw("distance<=$distance")->whereNull("deleted_at")->where("status", "=", "1")->get();
            $cnt = count($get_user);
        }
        return $cnt;
    }

    public function current_venue_user($latitude, $longitude) {
        $range = "2";
        if (!empty($latitude) && !empty($longitude)) {
            /* $currentUser =  DB::table("guest")->select(DB::raw("111.045 * DEGREES(ACOS(COS(RADIANS('.$latitude.')) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS('.$longitude.')) + SIN(RADIANS('.$latitude.')) * SIN(RADIANS(latitude)))) as distance"))->where("distance","<=",$range)->get();

              return count($currentUser);
              }
              else{
              return "0";
              } */
        }
        return "0";
    }

    public function getAllNotification(Request $request) {
        try {

            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "user_type" => ['required', Rule::in(['2', '3', '4'])]
            ]);
            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {

                $limit = 20;
                $page_no = isset($input['page_no']) && !empty($input['page_no']) ? $input['page_no'] : 0;

                $notification_type = isset($input['notification_type']) && !empty($input['notification_type']) ? $input['notification_type'] : "";

                $offset = $limit * $page_no;
                $user_notifications = DB::table('user_notification')
                        ->where('status', '=', 1)
                        ->where('user_id', '=', $input['userid'])
                        ->where('user_type', '=', $input['user_type'])
                        ->skip($offset)
                        ->take($limit)
                        ->select('id', 'message', 'is_read', 'subject', 'device_type', 'notification_key', 'data', 'notification_type', 'request_id')
                        ->orderBy('id', 'DESC');
                if (!empty($notification_type)) {
                    $user_notifications = $user_notifications->where("notification_type", "=", $notification_type);
                }

                $user_notifications = $user_notifications->get()->toArray();



                if (isset($user_notifications) && !empty($user_notifications)) {
                    $unreadNotifyIdArray = array();
                    foreach ($user_notifications as $key => $notify) {
                        if ((int) $notify->is_read == 0) {
                            $unreadNotifyIdArray[] = $notify->id;
                        }

                        $memory_data = json_decode($notify->data);

                        $user_notifications[$key]->data = $memory_data;

                        if (!empty($memory_data->our_story_id)) {
                            $check_our_story_file = DB::table('our_stories_files as osf')
                                            ->leftJoin('guest as u', 'u.userid', '=', 'osf.user_id')
                                            ->where("osf.status", "=", 1)
                                            ->where("osf.our_story_id", "=", $memory_data->our_story_id)
                                            ->select('osf.*', 'u.name', 'u.profile', 'osf.user_id')
                                            //->orderBy('osf.id', 'DESC')
                                            ->get()->toArray();

                            $detail = array();
                            foreach ($check_our_story_file as $k1 => $story_file) {

                                $type = "";
                                $url = "";
                                $thumb_url = "";
                                if ($story_file->file_type == "1") {
                                    //$type = "video";

                                    /* $url = url($this->uploadsFolder)."/user_story/video/".$story_file->story_file; */
                                    $url = $this->viewMemoryUser($input["userid"], $story_file->our_story_id, $story_file->id);

                                    $thumb_url = url($this->uploadsFolder) . "/user_story/video/" . $story_file->thumb_video;
                                } else {

                                    //$type = "image";

                                    /* $url = url($this->uploadsFolder)."/user_story/image/".$story_file->story_file; */
                                    $url = $this->viewMemoryUser($input["userid"], $story_file->our_story_id, $story_file->id);
                                    $thumb_url = $url;
                                }

                                $is_user_file = 0;

                                if ($input['userid'] == $story_file->user_id) {
                                    $is_user_file = 1;
                                }

                                $profile = "";

                                if (!empty($story_file->profile)) {

                                    $exp = substr($story_file->profile, 0, 8);
                                    //if ($exp!="https://") {
                                    // $profile = url($this->uploadsFolder)."/user/".$story_file->profile;
                                    //} else {
                                    $profile = url("public/uploads/user/customer/" . $story_file->profile);
                                    //}
                                } else {
                                    $profile = url("public/default.png");
                                }

                                $viewCount = 0;

                                $viewCount = DB::table('user_memory_views as umv')
                                        ->where('memory_id', '=', $story_file->our_story_id)
                                        ->where('image_id', '=', $story_file->id)
                                        ->where('memory_type', '=', 2)
                                        ->where('usertype', '=', 4)
                                        ->where('user_id', '!=', $check_our_story_file[0]->user_id)
                                        ->count();

                                if ($viewCount == 0 && $check_our_story_file[0]->user_id != $input['userid']) {
                                    $viewCount = 1;
                                }

                                //->get()->toArray();

                                $our_story_files[] = array(
                                    "id" => $story_file->id,
                                    "user_id" => $story_file->user_id,
                                    "profile" => $profile,
                                    "name" => $story_file->name,
                                    "viewCount" => $viewCount,
                                    //"our_story_id"=>$story_file->our_story_id,
                                    "is_user_file" => $is_user_file,
                                    "file_type" => $story_file->file_type,
                                    "story_file" => $url,
                                    "venue_id" => "0",
                                    "featured_brand_id" => "",
                                    "thumb_video" => $thumb_url,
                                    "api_url" => url('/api/viewMemoryUser/'),
                                    "created_at" => $story_file->created_at,
                                    "story_user_type" => 4,
                                    "api_url" => url("api/viewMemoryUser"),
                                    "tagged" => array()
                                );

                                $detail = array("venue_id" => $story_file->user_id, "name" => $story_file->name, "profile" => $profile, "thumb_image" => $thumb_url);
                            }



                            /* $memory['story_file']="";
                              $memory['id']="";
                              $memory['file_type']="";
                              $memory['venue_id']="";
                              $memory['featured_brand_id']="";
                              $memory['created_at']="";
                              $memory['thumb_video']="";
                              $memory['story_user_type']="";
                              $memory['name']="";
                              $memory['profile']="";
                              $memory['user_id']="";
                              $memory['viewCount']="";
                              $memory['video_thumb']="";
                              $memory['api_url']="";
                              $memory['tagged']=[]; */

                            if (!empty($our_story_files)) {
                                $memory = $our_story_files;
                            } else {
                                $memory = [];
                            }

                            if (!empty($detail)) {
                                /* $detail['venue_id'] = "";
                                  $detail['name']     = "";
                                  $detail['profile']  = "";
                                  $detail['thumb_image']=""; */

                                $user_notifications[$key]->memories_list[] = array("venue_id" => $detail['venue_id'], "our_story_id" => $story_file->our_story_id, "name" => $detail['name'], "profile" => $detail['profile'], "thumb_image" => $detail['thumb_image'], "memory" => $memory);
                            } else {
                                $user_notifications[$key]->memories_list = array();
                            }
                        } else {
                            $user_notifications[$key]->memories_list = array();
                        }

                        if (!empty($notify->notification_type)) {
                            $current_time = date("Y-m-d H:i:s");
                            if ($notify->notification_type == 1) {
                                $get_book = DB::table("booking_invite_list as bil")->select("bil.id", "b.booking_date", "b.booking_time")->leftJoin("booking as b", "b.id", "bil.booking_id")->where("bil.id", $notify->request_id)->first();


                                if (!empty($get_book->id)) {
                                    $booking_time = $get_book->booking_date . " " . $get_book->booking_time;
                                    if ($current_time > $booking_time) {

                                        unset($user_notifications[$key]);
                                    }
                                }
                            } else if ($notify->notification_type == 3) {
                                $get_mem = DB::table("our_stories_req_list as req")->select("req.id", "st.ends_at")->leftJoin("user_my_stories as st", "st.id", "req.our_story_id")->where("req.id", $notify->request_id)->first();

                                if (!empty($get_mem->id)) {
                                    $end_time = $get_mem->ends_at;
                                    if ($current_time > $end_time) {

                                        unset($user_notifications[$key]);
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($unreadNotifyIdArray)) {
                        DB::table('user_notification')
                                ->whereIn('id', $unreadNotifyIdArray)
                                ->update(['is_read' => 1]);
                    }
                    $data["user_notification"] = array_values($user_notifications);


                    $res_msg = isset($csvData['Notification_list_fetched_successfully']) ? $csvData['Notification_list_fetched_successfully'] : "";

                    return parent::api_response($data, true, $res_msg, '200');
                } else {
                    if ($page_no != 0) {
                        $res_msg = isset($csvData['No_more_notification_found_for_user']) ? $csvData['No_more_notification_found_for_user'] : "";
                        $data["user_notification"] = array();
                        return parent::api_response($data, true, $res_msg, '200');
                    }
                    $data["user_notification"] = array();
                    $res_msg = isset($csvData['No_notification_found']) ? $csvData['No_notification_found'] : "";
                    return parent::api_response($data, false, $res_msg, '200');
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function viewMemoryUser($userid = '', $memory_id = '', $image_id = 0) {

        // Api For  redirecting to urls and view count
        /*
          $memory_type for seprating our and my memory
          2:our_memory and 1:my_memory
         */

        $memory_type = 0;

        if (isset($image_id) && !empty($image_id)) {

            $memory_type = 2;

            $story_data = DB::table('our_stories_files')
                            ->where('status', '=', '1')
                            ->where('our_story_id', '=', $memory_id)
                            ->where('id', '=', $image_id)
                            ->select('*')
                            ->get()->toArray();
        } else {

            $memory_type = 1;

            $story_data = DB::table('user_my_stories')
                            ->where('status', '=', '1')
                            ->where('id', '=', $memory_id)
                            ->select('userid', 'story_file', 'id', 'status', 'file_type', 'created_at')
                            ->get()->toArray();
        }


        //echo url('api/viewMemoryUser');
        // Record not found case handel krna h.
        if (isset($story_data) && !empty($story_data)) {

            if ($story_data[0]->file_type == 2) {

                /* $cloud_url = Config::get('constants.Cloud_url');

                  @$check_image = file_get_contents($cloud_url."/uploads/image/".$story_data[0]->story_file); */

                if (!empty($check_image)) {

                    $url = $cloud_url . "/uploads/image/" . $story_data[0]->story_file;
                } else {

                    $url = url($this->uploadsFolder) . "/user_story/image/" . $story_data[0]->story_file;
                }

                //user_memory_views
                //My memory url eg.  : localhost/Amiggos_new/api/viewMemoryUser/50/133
                //Our memory url eg. : localhost/Amiggos_new/api/viewMemoryUser/50/73/142
                // 1:$userid, 2:$memory_id, 3:$image_id

                $userViewData = DB::table('user_memory_views')
                                ->where('user_id', '=', $userid)
                                ->where('memory_id', '=', $memory_id)
                                ->where('image_id', '=', $image_id)
                                ->where('memory_type', '=', $memory_type)
                                ->select('id', 'user_id')
                                ->get()->toArray();


                if (empty($userViewData)) {

                    DB::table('user_memory_views')->insert([
                        ['user_id' => $userid, 'memory_id' => $memory_id, 'image_id' => $image_id, 'memory_type' => $memory_type]
                    ]);
                }
            } else {
                $cloud_url = Config::get('constants.Cloud_url');

                @$check_video = file_get_contents($cloud_url . "/uploads/user_story/video/" . $story_data[0]->story_file);

                if (!empty($check_video)) {

                    $url = $cloud_url . "/uploads/user_story/video/" . $story_data[0]->story_file;
                } else {

                    $url = url($this->uploadsFolder) . "/user_story/video/" . $story_data[0]->story_file;
                }
                /* $ctype ="video/mp4"; */
            }

            $userViewData = DB::table('user_memory_views')
                            ->where('user_id', '=', $userid)
                            ->where('memory_id', '=', $memory_id)
                            ->where('image_id', '=', $image_id)
                            ->where('memory_type', '=', $memory_type)
                            ->select('id', 'user_id')
                            ->get()->toArray();


            if (empty($userViewData)) {

                DB::table('user_memory_views')->insert([
                    ['user_id' => $userid, 'memory_id' => $memory_id, 'image_id' => $image_id, 'memory_type' => $memory_type]
                ]);
            }
        } else {

            $url = url($this->uploadsFolder) . "/user_story/expired.png";
        }

        //header("Content-type: {$ctype}");
        //header("Location:".$url);
        //exit;
        return $url;
    }

    public function userFreindsList(Request $request) {
        try {

            $lang_data = parent::getLanguageValues($request);

            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "page_no" => "required"
            ]);

            $total_count = "0";

            if (isset($_post["booking_id"])) {
                $booking_id = $_post["booking_id"];
            } else {
                $booking_id = "";
            }

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
                //return parent::api_response([],false,$validator->errors()->first(), 200);
            } else {
                $check_user = DB::table("guest")->where('userid', '=', $input['userid'])->select('userid', 'name', 'email', 'status', 'unique_timestamp as unique_user_id')->whereNull("deleted_at")->get();


                if (empty($check_user[0]->userid)) {
                    $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";

                    return parent::api_response([], false, $res_msg, 200);
                } elseif ((int) $check_user[0]->status == 2) {
                    $res_msg = isset($csvData['Your_account_is_not_active_yet_please_contact_admin']) ? $csvData['Your_account_is_not_active_yet_please_contact_admin'] : "";

                    return parent::api_response([], false, $res_msg, 200);
                }


                $limit = 10;
                $page_no = isset($input['page_no']) && !empty($input['page_no']) ? $input['page_no'] : 0;
                $offset = $limit * $page_no;
                try {
                    $users = DB::table('guest as u')
                            ->leftJoin('user_friends as uf', 'u.userid', '=', 'uf.friend_id')
                            ->where('uf.user_id', '=', $input['userid'])
                            ->where('uf.status', '=', 'A')
                            ->where('u.status', '=', 1)
                            ->where('uf.is_friend', '=', 1);

                    if (isset($input['our_story_id']) && !empty($input['our_story_id'])) {
                        $users = $users->whereNotIn('userid', [$story_creator_id]);
                    }

                    if (isset($input['name']) && !empty($input['name'])) {
                        $users = $users->where('u.name', 'LIKE', $input['name'] . '%');
                    }

                    $users = $users->whereNull("u.deleted_at")->skip($offset)
                                    ->take($limit)
                                    ->select('u.userid', 'u.name', 'u.profile', 'u.state', 'u.city', 'u.last_name', 'u.firebase_id', 'u.unique_timestamp as unique_user_id', 'uf.id as user_freind_id')
                                    ->get()->toArray();
                } catch (\Illuminate\Database\QueryException $e) {
                    $res_msg = "No record found.";
                    return parent::api_response((object) [], false, $res_msg, '200');
                }


                if (empty($users)) {
                    $data["total_count"] = $total_count;
                    $data["real_freind"] = [];
                    $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                    return parent::api_response($data, false, $res_msg, 200);
                } else {



                    foreach ($users as $key => $user) {
                        /* code to find user my freind */

                        $user_freind = DB::table("user_friends")->select("id")->where("user_id", "=", $user->userid)->where("friend_id", "=", $input['userid'])->where("status", "=", "A")->first();
                        $isMyFriend = "false";
                        if (!empty($user_freind->id)) {
                            $isMyFriend = "true";
                        }

                        /* code for blocked freind */
                        $isMyFriendBlocked = "false";
                        $user_freind_block = DB::table("blocked_user")->select("id")->where("user_id", "=", $input['userid'])->where("blocked_user_id", "=", $user->userid)->whereNull("deleted_at")->first();
                        $user_freind_block2 = DB::table("blocked_user")->select("id")->where("user_id", "=", $user->userid)->where("blocked_user_id", "=", $input['userid'])->whereNull("deleted_at")->first();
                        if (!empty($user_freind_block->id)) {
                            $isMyFriendBlocked = "true";
                        }
                        if (!empty($user_freind_block2->id)) {
                            $isMyFriendBlocked = "true";
                        }


                        /* check code for our story case */
                        $profile = "";

                        if (!empty($user->profile)) {
                            $profile = url("public/uploads/user/customer/" . $user->profile);
                        } else {
                            $profile = url("public/default.png");
                        }

                        //update status for seen
                        $update_seen = DB::table("user_friends")->where("id", $user->user_freind_id)->where("is_seen", "0")->update(["is_seen" => "1"]);

                        $users[$key]->profile = $profile;
                        $users[$key]->isMyFriend = $isMyFriend;
                        $users[$key]->isMyFriendBlocked = $isMyFriendBlocked;

                        $users[$key]->is_invited = "0";
                        //check if invited
                        $alreadyinvite = DB::table("booking_invite_list")->select("status")->where("booking_id", "=", $input)->where("friend_id", "=", $user->userid)->first();

                        if (!empty($alreadyinvite->status) && $alreadyinvite->status != "R") {
                            $users[$key]->is_invited = "1";
                        }

                        $freint_object = new FriendController();
                        $real_freind_count = $freint_object->real_freind_count($user->userid);
                        $users[$key]->real_freind_count = $real_freind_count;
                        $address = "";
                        if (!empty($user->state)) {
                            $address = $user->state . ",";
                        }
                        if (!empty($user->city)) {
                            $address .= $user->city;
                        }
                        $users[$key]->address = $address;

                        $lname = "";
                        if (!empty($users[$key]->last_name)) {
                            $users[$key]->name = $users[$key]->name . ' ' . $lname;
                        }

                        unset($users[$key]->state);
                        unset($users[$key]->city);
                        unset($users[$key]->last_name);
                        /* code for profile goes here */
                        /* code for get location by lat_lang goes here */
                    }

                    if (!empty($users[0]->userid)) {
                        $res_msg = isset($csvData['User_friend_list_retrieved_successfully']) ? $csvData['User_friend_list_retrieved_successfully'] : "";
                        $data["total_count"] = count($users);
                        $data["real_freind"] = $users;
                        return parent::api_response($data, true, $res_msg, 200);
                    } else {

                        $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                        $data["total_count"] = $total_count;
                        $data["real_freind"] = [];

                        return parent::api_response($data, false, $res_msg, 200);
                    }
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function userFreindsList_memory(Request $request) {
        try {

            $lang_data = parent::getLanguageValues($request);

            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "page_no" => "required"
            ]);
            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
                //return parent::api_response([],false,$validator->errors()->first(), 200);
            } else {
                $check_user = DB::table("guest")->where('userid', '=', $input['userid'])->select('userid', 'name', 'email', 'status')->get();


                if (empty($check_user[0]->userid)) {
                    $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";

                    return parent::api_response([], false, $res_msg, 200);
                } elseif ((int) $check_user[0]->status == 2) {
                    $res_msg = isset($csvData['Your_account_is_not_active_yet_please_contact_admin']) ? $csvData['Your_account_is_not_active_yet_please_contact_admin'] : "";

                    return parent::api_response([], false, $res_msg, 200);
                }


                $limit = 10;
                $page_no = isset($input['page_no']) && !empty($input['page_no']) ? $input['page_no'] : 0;
                $offset = $limit * $page_no;
                $search = "";
                $story_creator_id = "";
                if (isset($input['our_story_id']) && !empty($input['our_story_id'])) {
                    $get_story_user = DB::table("user_my_stories")->select("userid")->where("id", $input['our_story_id'])->first();
                    $story_creator_id = $get_story_user->userid;
                }
                if (isset($input["search"]) && !empty($input["search"])) {

                    $search = $input["search"];
                }

                if (empty($search)) {
                    $users = DB::table('guest as u')
                            ->leftJoin('user_friends as uf', 'u.userid', '=', 'uf.friend_id')
                            ->where('uf.user_id', '=', $input['userid'])
                            ->where('uf.status', '=', 'A')
                            ->where('u.status', '=', 1)
                            ->where('uf.is_friend', '=', 1);

                    if (isset($input['our_story_id']) && !empty($input['our_story_id'])) {
                        $users = $users->whereNotIn('userid', [$story_creator_id]);
                    }



                    $users = $users->skip($offset)
                                    ->take($limit)
                                    ->select('u.userid', 'u.name', 'u.profile', 'u.state', 'u.city', 'u.last_name')
                                    ->get()->toArray();
                } else {
                    $users = DB::table('guest as u')
                            ->where('u.status', '=', 1);

                    if (isset($input['our_story_id']) && !empty($input['our_story_id'])) {
                        $users = $users->whereNotIn('userid', [$story_creator_id]);
                    }


                    $users = $users->where('u.name', 'LIKE', $search . '%');


                    $users = $users->skip($offset)
                                    ->take($limit)
                                    ->select('u.userid', 'u.name', 'u.profile', 'u.state', 'u.city', 'u.last_name')
                                    ->get()->toArray();
                }

                if (empty($users)) {
                    $data["real_freind"] = [];
                    $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                    return parent::api_response($data, false, $res_msg, 200);
                } else {


                    foreach ($users as $key => $user) {
                        /* code to find user my freind */

                        $user_freind = DB::table("user_friends")->select("id")->where("user_id", "=", $user->userid)->where("friend_id", "=", $input['userid'])->where("status", "=", "A")->first();
                        $isMyFriend = "false";
                        if (!empty($user_freind->id)) {
                            $isMyFriend = "true";
                        }

                        /* code for blocked freind */
                        $isMyFriendBlocked = "false";
                        $user_freind_block = DB::table("blocked_user")->select("id")->where("user_id", "=", $input['userid'])->where("blocked_user_id", "=", $user->userid)->whereNull("deleted_at")->where("blocked_user_type", "=", "4")->first();
                        //echo $user_freind_block->id;die;
                        $user_freind_block2 = DB::table("blocked_user")->select("id")->where("user_id", "=", $user->userid)->where("blocked_user_id", "=", $input['userid'])->whereNull("deleted_at")->where("blocked_user_type", "=", "4")->first();
                        if (!empty($user_freind_block->id)) {
                            $isMyFriendBlocked = "true";
                        }
                        if (!empty($user_freind_block2->id)) {
                            $isMyFriendBlocked = "true";
                        }

                        //check if already invited
                        if (isset($input['our_story_id']) && !empty($input['our_story_id'])) {
                            $check_already_invited = DB::table('our_stories_req_list')->select("id")->where('our_story_id', '=', $input['our_story_id'])->where('friend_id', '=', $user->userid)->where('status', '=', 1)->first();
                        }


                        /* check code for our story case */
                        $profile = "";

                        if (!empty($user->profile)) {
                            $profile = url("public/uploads/user/customer/" . $user->profile);
                        } else {
                            $profile = url("public/default.png");
                        }
                        $users[$key]->profile = $profile;
                        $users[$key]->isMyFriend = $isMyFriend;
                        $users[$key]->isMyFriendBlocked = $isMyFriendBlocked;


                        $freint_object = new FriendController();
                        $real_freind_count = $freint_object->real_freind_count($user->userid);
                        $users[$key]->real_freind_count = $real_freind_count;
                        $address = "";
                        if (!empty($user->state)) {
                            $address = $user->state . ",";
                        }
                        if (!empty($user->city)) {
                            $address .= $user->city;
                        }
                        $users[$key]->address = $address;
                        $lname = "";
                        if (!empty($users[$key]->last_name)) {
                            $users[$key]->name = $users[$key]->name . ' ' . $lname;
                        }

                        unset($users[$key]->state);
                        unset($users[$key]->city);
                        unset($users[$key]->last_name);
                        if (!empty($user_freind_block->id) || !empty($user_freind_block2->id) || !empty($check_already_invited->id)) {
                            unset($users[$key]);
                        }
                        /* code for profile goes here */
                        /* code for get location by lat_lang goes here */
                    }

                    $users = array_values($users);
                    if (!empty($users[0]->userid)) {
                        $res_msg = isset($csvData['User_friend_list_retrieved_successfully']) ? $csvData['User_friend_list_retrieved_successfully'] : "";
                        $data["real_freind"] = $users;
                        return parent::api_response($data, true, $res_msg, 200);
                    } else {

                        $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                        $data["real_freind"] = [];

                        return parent::api_response($data, false, $res_msg, 200);
                    }
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function clearNotification(Request $request) {
        try {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "user_type" => ['required', Rule::in(['2', '3', '4'])]
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response([], false, $err_msg, 200);
            } else {
                $notification = DB::table('user_notification');
                if (isset($input['notification_id']) && !empty($input['notification_id'])) {
                    $notification = $notification->where('id', $input['notification_id']);
                }
                $notification = $notification->where('user_id', $input['userid'])->where('user_type', $input['user_type'])
                        ->update(['status' => 2]);
                if ($notification) {
                    $res_msg = isset($csvData['Notification_list_updated_successfully']) ? $csvData['Notification_list_updated_successfully'] : "";
                    return parent::api_response([], true, $res_msg, '200');
                } else {
                    $res_msg = isset($csvData['Notification_list_not_updated']) ? $csvData['Notification_list_not_updated'] : "Notification list is not update.";
                    return parent::api_response([], false, $res_msg, '200');
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function getFreindProfile(Request $request) {
        try {
            $lang_data = parent::getLanguageValues($request);

            $csvData = array();

            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "freind_id" => "required"
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $friend_id = $input['freind_id'];
                $user = DB::table('guest as u')
                        ->where('u.userid', '=', $friend_id)
                        ->where('u.status', '=', 1)
                        ->leftJoin('states as st', 'u.state', 'st.id')
                        ->leftJoin('cities as c', 'u.city', 'c.id')
                        ->select('u.userid', 'u.name', "u.last_name", 'u.city as city_id', 'u.state as state_id', 'u.profile', 'c.name as city', 'st.name as state', 'firebase_id', 'u.unique_timestamp')
                        ->first();



                $profile = "";
                if (!empty($user->profile)) {

                    $profile = url($this->uploadsFolder) . "/user/customer/" . $user->profile;
                } else {
                    $profile = url("public/default.png");
                }


                /* code to find user my freind */
                $user_freind = DB::table("user_friends")->select("id", "is_favourite")->where("user_id", "=", $input["freind_id"])->where("friend_id", "=", $input['userid'])->where("status", "=", "A")->first();
                $isMyFriend = "false";
                if (!empty($user_freind->id)) {
                    $isMyFriend = "true";
                }

                $user->isMyFriend = $isMyFriend;
                //code to check favorite freind
                $check_favorite_freind = DB::table("user_favorite_freind")->where("userid", "=", $input["userid"])->where("freind_id", "=", $input["freind_id"])->first();
                $user->is_favourite = "0";
                if (!empty(!empty($check_favorite_freind->id)) && $check_favorite_freind->status == "1") {
                    $user->is_favourite = "1";
                }

                /* code for blocked freind */
                $isMyFriendBlocked = "false";
                $user_freind_block = DB::table("blocked_user")->select("id")->where("user_id", "=", $input['userid'])->where("blocked_user_id", "=", $input["freind_id"])->whereNull("deleted_at")->first();
                if (!empty($user_freind_block->id)) {
                    $isMyFriendBlocked = "true";
                }
                $user->isMyFriendBlocked = $isMyFriendBlocked;
                $freint_object = new FriendController();
                $real_freind_count = $freint_object->real_freind_count($input["freind_id"]);
                $user->real_freind_count = $real_freind_count;

                $user->profile = $profile;
                $address = "";

                if (!empty($user->city)) {
                    $address .= $user->city . ",";
                }
                if (!empty($user->state)) {
                    $address .= $user->state;
                }


                //$address = $user->state . ',' . $user->city;
                $user->address = $address;
                unset($user->state);
                unset($user->city);
                unset($user->state_id);
                unset($user->city_id);
                /* get locatuion from lat long */
                /*
                  $url1 = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$user[0]->latitude.",".$user[0]->longitude."&key=AIzaSyB4vDx-SsCPFBAUY3nfk77M0SQYndjk_4c";

                  $response = file_get_contents($url1);
                  $json = array();
                  $json = json_decode($response,TRUE); //generate array object from the response from the web
                  $address_arry=array();
                  $address_arry = isset($json['results'][0]['address_components']) ? $json['results'][0]['address_components'] : array();
                  $col = array_column($address_arry, 'types');

                  $key_array = array();
                  foreach ($col as $k1 => $value) {

                  if(in_array("locality", $value)){
                  $key_array[] = $k1;
                  }elseif(in_array("neighborhood", $value)){
                  $key_array[] = $k1;
                  }elseif(in_array("sublocality", $value)){
                  $key_array[] = $k1;
                  }elseif(in_array("route", $value)){
                  $key_array[] = $k1;
                  }
                  }


                  $location = "";

                  foreach ($key_array as $k) {
                  $location .= $address_arry[$k]['long_name'].",";
                  }

                  $location =trim($location,',');
                 * */
                //$location ="";
                //$user[0]->location = $location;
                /* get locatuion from lat long */
                $data["user"] = $user;
                $res_msg = isset($csvData['User_profile_data_fetched_successfully']) ? $csvData['User_profile_data_fetched_successfully'] : "";
                return parent::api_response($user, true, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            ;
            return parent::api_response((object) [], false, $res_msg, 200);
        }
    }

    public function getreferralFriend(Request $request) {
        try {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();

            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required"
            ]);

            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $getuserferrral = DB::table("guest")->select("userid", "referral_code")->where("userid", $input["userid"])->first();
                $findreferralUser = DB::table("guest")->select("userid", "referred_by", "referred_type")->where("userid", $input["userid"])->first();
                $user = DB::table('guest as u')
                        ->where('u.referred_by', '=', $getuserferrral->userid)
                        ->where('u.status', '=', 1)
                        ->leftJoin('states as st', 'u.state', 'st.id')
                        ->leftJoin('cities as c', 'u.city', 'c.id')
                        ->select('u.userid', 'u.name', "u.last_name", "u.username", "u.email", "u.phone", "u.gender", 'u.city as city_id', 'u.state as state_id', 'u.profile', 'c.name as city', 'st.name as state', 'firebase_id', 'u.unique_timestamp', 'u.created_at', 'u.referred_type', 'u.referral_valid_till')
                        ->get();

                foreach ($user as $key => $v) {
                    if (!empty($v)) {
                        $user[$key]->profile = url("public/uploads/user/customer/" . $v->profile);
                    }
                }

                if ($findreferralUser->referred_type == 1) {
                    $userreferral = DB::table('guest as u')
                            ->where('u.userid', '=', $findreferralUser->referred_by)
                            ->where('u.status', '=', 1)
                            ->leftJoin('states as st', 'u.state', 'st.id')
                            ->leftJoin('cities as c', 'u.city', 'c.id')
                            ->select('u.userid', 'u.name', "u.last_name", "u.username", "u.email", "u.phone", "u.gender", 'u.city as city_id', 'u.state as state_id', 'u.profile', 'c.name as city', 'st.name as state', 'firebase_id', 'u.unique_timestamp', 'u.created_at', 'u.referred_type', 'u.referral_valid_till')
                            ->first();
                    $profile = "";
                    if (!empty($userreferral->profile)) {
                        $profile = url("public/uploads/user/customer/" . $userreferral->profile);
                    }
                    $userreferral->profile = $profile;
                } elseif ($findreferralUser->referred_type == 2) {
                    $userreferral = DB::table('venue as v')
                            ->select('v.id as club_id', 'v.name', 'v.teamadmin_id', 'v.menu_type', 'v.user_set_time', 'v.is_amigo_club', 'vt.time_value', 'vt.club_working_value', 'ot.time_value as ot_time_value', 'ot.club_working_value as ot_club_working_value', 'v.venue_home_image as home_image', 'v.address', 'v.agelimit', 'v.dress_code', 'v.reservation', 'v.go_order', 'v.dine_in', 'v.delivery', 'v.phone', 'cn.name as club_country', 'v.other_img', 'v.deliver_upto_1km', 'v.deliver_upto_5km', 'v.deliver_upto_10km', 'v.venue_referral', 's.name as club_state', 'c.name as club_city', 'description as club_description', 'reservation', 'go_order', 'dine_in', 'delivery', 'mask_req', 'user_set_time', 'menu_type.name as menu_type_name', 'menu_type', 'latitude', 'longitude', 'zipcode', 'price_category.price_category', 'tax')
                            ->leftJoin('venue_timing as vt', 'vt.venue_id', '=', 'v.id')
                            ->leftJoin('operational_hour as ot', 'ot.venue_id', '=', 'v.id')
                            ->leftJoin('countries as cn', 'cn.id', '=', 'v.country_id')
                            ->leftJoin('states as s', 's.id', '=', 'v.state_id')
                            ->leftJoin('cities as c', 'c.id', '=', 'v.city_id')
                            ->leftJoin('menu_type', 'menu_type.id', '=', 'v.menu_type')
                            ->leftJoin('price_category', 'price_category.id', '=', 'v.price_category')
                            ->where('v.status', '=', 1)
                            ->where('v.id', '=', $findreferralUser->referred_by)
                            ->whereNull('v.deleted_at')
                            ->first();
                }

                // foreach ($userreferral as $key => $v) {
                //   if(!empty($v)){
                //     $userreferral[$key]->referral_valid_till = date('d-m-Y', strtotime($userreferral->referral_valid_till));
                //   }
                // }
                // $user->profile = $profile;
                if (!empty($userreferral)) {
                    $data['referred_by'] = $userreferral;
                }
                $data['referred_to'] = $user;
                if (!empty($data)) {
                    $res_msg = "Referral Friends And Referred Data Fetched";
                    return parent::api_response($data, true, $res_msg, 200);
                } else {
                    $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                    return parent::api_response($data, true, $res_msg, 200);
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, 200);
        }
    }

    public function freindRealFreind(Request $request) {
        try {
            $lang_data = parent::getLanguageValues($request);

            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required",
                        "freind_id" => "required",
                        "page_no" => "required"
            ]);
            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
                //return parent::api_response([],false,$validator->errors()->first(), 200);
            } else {
                $check_user = DB::table("guest")->where('userid', '=', $input['freind_id'])->select('userid', 'name', 'email', 'status')->get();

                if (empty($check_user[0]->userid)) {
                    $res_msg = isset($csvData['Invalid_userid']) ? $csvData['Invalid_userid'] : "";

                    return parent::api_response([], false, $res_msg, 200);
                } elseif ((int) $check_user[0]->status == 2) {
                    $res_msg = isset($csvData['Your_account_is_not_active_yet_please_contact_admin']) ? $csvData['Your_account_is_not_active_yet_please_contact_admin'] : "";

                    return parent::api_response([], false, $res_msg, 200);
                }


                $limit = 10;
                $page_no = isset($input['page_no']) && !empty($input['page_no']) ? $input['page_no'] : 0;
                $offset = $limit * $page_no;

                $users = DB::table('guest as u')
                        ->leftJoin('user_friends as uf', 'u.userid', '=', 'uf.friend_id')
                        ->where('uf.user_id', '=', $input['freind_id'])
                        ->where('uf.status', '=', 'A')
                        ->where('u.status', '=', 1)
                        ->where('uf.is_friend', '=', 1);

                if (isset($input['our_story_id']) && !empty($input['our_story_id'])) {
                    $users = $users->whereNotIn('userid', [$story_creator_id]);
                }

                if (isset($input['name']) && !empty($input['name'])) {
                    $users = $users->where('u.name', 'LIKE', $input['name'] . '%');
                }

                $users = $users->skip($offset)
                                ->take($limit)
                                ->select('u.userid', 'u.name', 'u.profile', 'u.state', 'u.city', 'u.last_name')
                                ->get()->toArray();

                if (empty($users)) {
                    $data["real_freind"] = [];
                    $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                    return parent::api_response($data, false, $res_msg, 200);
                } else {


                    foreach ($users as $key => $user) {


                        /* check code for our story case */
                        $profile = "";
                        if (!empty($user->profile)) {
                            $profile = url("public/uploads/user/customer/$user->profile");
                            //}
                        } else {
                            $profile = url("public/default.png");
                        }
                        $users[$key]->profile = $profile;
                        $freint_object = new FriendController();
                        $real_freind_count = $freint_object->real_freind_count($user->userid);
                        $users[$key]->real_freind_count = $real_freind_count;
                        $address = "";
                        if (!empty($user->city)) {
                            $address .= $user->city . ",";
                        }
                        if (!empty($user->state)) {
                            $address = $user->state;
                        }

                        $users[$key]->address = $address;
                        $lname = "";
                        if (!empty($users[$key]->last_name)) {
                            $users[$key]->name = $users[$key]->name . ' ' . $lname;
                        }

                        $users[$key]->is_favorite_freind = "0";

                        unset($users[$key]->state);
                        unset($users[$key]->city);
                        unset($users[$key]->last_name);
                        /* code for profile goes here */
                        /* code for get location by lat_lang goes here */
                    }

                    if (!empty($users[0]->userid)) {
                        $res_msg = isset($csvData['User_friend_list_retrieved_successfully']) ? $csvData['User_friend_list_retrieved_successfully'] : "";
                        $data["real_freind"] = $users;
                        return parent::api_response($data, true, $res_msg, 200);
                    } else {

                        $res_msg = isset($csvData['No_record_found']) ? $csvData['No_record_found'] : "";
                        $data["real_freind"] = [];

                        return parent::api_response($data, true, $res_msg, 200);
                    }
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function get_Freinffavorite_venue(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        "freind_id" => "required"
            ]);
            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                $userid = $input['userid'];

                $freind_id = $input['freind_id'];
                $get_venue = DB::table("user_favorite_venue as uv")->select("c.venue_home_image as image", "uv.club_id", "c.name")->leftJoin("venue as c", "uv.club_id", 'c.id')->groupBy("uv.club_id")->where('uv.user_id', "=", $freind_id)->get();

                foreach ($get_venue as $k => $v) {
                    if (!empty($v->image)) {
                        $explode = explode(",", $v->image);  //code to bring image from cloud front
                        $cloud_url = Config::get('constants.Cloud_url');
                        @$check_image = file_get_contents($cloud_url . "/uploads/club/home/" . $explode[0]);
                        if (!empty($check_image)) {
                            $get_venue[$k]->image = $cloud_url . "/uploads/club/home/" . $v->image;
                        } else {
                            $get_venue[$k]->image = url($this->uploadsFolder) . "/venue/home_image/" . $explode[0];
                        }
                    } else {
                        $get_venue[$k]->image = url("public/default.png");
                    }
                }

                if (!empty($get_venue[0]->club_id)) {
                    $data['favorite_venue'] = $get_venue;

                    $message = isset($csvData['Club_fetched_successfully']) ? $csvData['Club_fetched_successfully'] : "";
                } else {
                    $data['favorite_venue'] = [];
                    $message = isset($csvData['No_nearest_club_found']) ? $csvData['No_nearest_club_found'] : "No favorite venue found.";
                }
                return parent::api_response($data, true, $message, 200);
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function getSettingDetails(request $request) {
        try {
            $lang_data = parent::getLanguageValues($request);
            $csvData = array();

            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $input = $request->all();
            $validator = Validator::make($input, [
                        "userid" => "required"
            ]);
            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                //get user record
                $get_guest = DB::table("guest")->where("userid", "=", $input["userid"])->first();

                $final_response = [];

                $final_response['0'] = array(
                    "id" => 1,
                    "title" => isset($csvData['visible_on_map']) ? $csvData['visible_on_map'] : "",
                    "is_set" => $get_guest->visible_map,
                    "type" => "toggle"
                );

                $final_response['1'] = array(
                    "id" => 2,
                    "title" => isset($csvData['messages']) ? $csvData['messages'] : "",
                    //$data['message']           = $get_guest->message_receive;
                    "is_set" => $get_guest->message_receive,
                    "type" => "toggle"
                );

                $final_response['2'] = array(
                    "id" => 3,
                    "title" => isset($csvData['push_notification']) ? $csvData['push_notification'] : "",
                    "is_set" => $get_guest->push_notification,
                    "type" => "toggle"
                );

                $final_response['3'] = array(
                    "id" => 4,
                    "title" => isset($csvData['Legal']) ? $csvData['Legal'] : "",
                    "url" => url("settingLegal"),
                    "type" => "url"
                );



                $final_response['4'] = array(
                    "id" => 6,
                    "title" => isset($csvData['Privacy_policy']) ? $csvData['Privacy_policy'] : "",
                    "url" => url("settingPrivacyPolicy"),
                    "type" => "url"
                );


                $res_msg = "Settings fetched successfully.";
                return parent::api_response(["setting" => $final_response], true, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response((object) [], false, $res_msg, '200');
        }
    }

    public function updateSettingValue(Request $request) {

        $lang_data = parent::getLanguageValues($request);

        $csvData = array();

        if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
            $csvData = $lang_data['csvData'];
        }

        $input = $request->all();

        $validator = Validator::make($input, [
                    'userid' => "required",
                    'setting_id' => "required",
                    'setting_value' => "required"
        ]);

        if ($validator->fails()) {

            $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
            return parent::api_response([], false, $err_msg, 200);
            //return parent::api_response([],false,$validator->errors()->first(), 200);
        } else {

            $user_update = Guest::where('userid', '=', $input['userid']);

            if ($input['setting_id'] == '1') {

                $user_update = $user_update->update(['visible_map' => $input['setting_value']]);
            }

            if ($input['setting_id'] == '2') {

                $user_update = $user_update->update(['message_receive' => $input['setting_value']]);
            }

            if ($input['setting_id'] == '3') {

                $user_update = $user_update->update(['push_notification' => $input['setting_value']]);
            }

            $res_msg = isset($csvData['setting_details_data_updated_successfully']) ? $csvData['setting_details_data_updated_successfully'] : "";

            return parent::api_response([], true, $res_msg, 200);
        }
    }

    public function userFreindsList_chat(request $request) {
        try {
            $input = $request->all();
            $lang_data = parent::getLanguageValues($request);
            ($request);
            $csvData = array();
            if (($lang_data['status'] == 1) && !empty($lang_data['csvData'])) {
                $csvData = $lang_data['csvData'];
            }

            $validator = Validator::make($input, [
                        'userid' => "required",
            ]);
            if ($validator->fails()) {

                $err_msg = $validator->errors()->first();
                return parent::api_response([], false, $err_msg, 200);
            } else {

                $search = "";
                if (!empty($input["search"])) {
                    $search = $input["search"];
                }

                if (empty($search)) {
                    $get_guest = DB::table("guest as g")->select("g.userid", "g.name as first_name", "g.last_name", "g.profile as profile_image", "g.status", "g.firebase_id", "g.unique_timestamp as unique_user_id")->where("g.userid", "!=", $input["userid"])->leftJoin('user_friends as uf', 'g.userid', '=', 'uf.friend_id')
                                    ->where('uf.user_id', '=', $input['userid'])
                                    ->where('uf.status', '=', 'A')
                                    ->where('g.status', '=', 1)
                                    ->where('uf.is_friend', '=', 1)->whereNull("deleted_at")->get();
                } else {

                    $get_guest = DB::table("guest as g")->select("g.userid", "g.name as first_name", "g.last_name", "g.profile as profile_image", "g.status", "g.firebase_id", "g.unique_timestamp as unique_user_id")->where("g.userid", "!=", $input["userid"])->leftJoin('user_friends as uf', 'g.userid', '=', 'uf.friend_id')
                                    ->where('uf.user_id', '=', $input['userid'])
                                    ->where('uf.status', '=', 'A')
                                    ->where('g.status', '=', 1)
                                    ->where('uf.is_friend', '=', 1)->whereNull("deleted_at")->where("g.name", "LIKE", "%$search%")->get();
                }

                if (!empty($get_guest)) {
                    foreach ($get_guest as $keys => $vals) {
                        if (!empty($vals->profile_image)) {
                            $get_guest[$keys]->profile_image = url("public/uploads/user/customer/" . $vals->profile_image);
                        } else {
                            $get_guest[$keys]->profile_image = url("public/default.png");
                        }
                        if (empty($vals->firebase_id)) {
                            $get_guest[$keys]->firebase_id = "";
                        }
                    }

                    //$get_staff[$keys]->venue_staff_list = $get_staff;


                    $succ_msg = isset($csvData['freind_list_fetched_successfully']) ? $csvData['freind_list_fetched_successfully'] : "Freind list fetched successfully.";
                    return parent::api_response(["guest_list" => $get_guest], true, $succ_msg, 200);
                } else {
                    $succ_msg = isset($csvData['freind_list_fetched_successfully']) ? $csvData['freind_list_fetched_successfully'] : "No record found.";
                    return parent::api_response(["guest_list" => $get_guest], false, $succ_msg, 200);
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    public function check_blockStatus_old(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        'userid' => "required",
                        'freind_id' => "required"
            ]);
            if ($validator->fails()) {

                $err_msg = $validator->errors()->first();
                return parent::api_response([], false, $err_msg, 200);
            } else {
                $ouser = $input["freind_id"];
                $loginuser = $input["userid"];
                $block_user = DB::table("blocked_user")->select("id", "user_id", "blocked_user_id");
                $block_user = $block_user->where(
                        function($block_user) use ($ouser, $loginuser) {
                    $block_user = $block_user->where("user_id", "=", $loginuser)->Where("blocked_user_id", "=", $ouser);
                    $block_user = $block_user->orwhere("user_id", "=", $ouser)->Where("blocked_user_id", "=", $loginuser);
                }
                );
                $block_user = $block_user->where("blocked_user_type", "=", "4")->whereNull("deleted_at")->first();

                if (!empty($block_user->id)) {

                    if ($block_user->user_id != $input["userid"]) {
                        $succ_msg = isset($csvData['block_user_status']) ? $csvData['block_user_status'] : "Unable to send message as user is blocked you.";
                    } else {
                        $succ_msg = isset($csvData['block_user_status_owner']) ? $csvData['block_user_status_owner'] : "Unable to send message because you have blocked this user.";
                    }
                    return parent::api_response([], false, $succ_msg, 200);
                } else {
                    $succ_msg = "";
                    return parent::api_response([], true, $succ_msg, 200);
                }
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    public function check_blockStatus(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        'userid' => "required",
                        'freind_id' => "required"
            ]);
            if ($validator->fails()) {

                $err_msg = $validator->errors()->first();
                return parent::api_response([], false, $err_msg, 200);
            } else {

                //get amiggo is from timestamp
                $check_guest = DB::table("guest")->select("message_receive", "userid")->where("unique_timestamp", "=", $input["freind_id"])->first();

                $ouser = ((!empty($check_guest->userid)) ? $check_guest->userid : $input["freind_id"]);
                $loginuser = $input["userid"];
                $block_user = DB::table("blocked_user")->select("id", "user_id", "blocked_user_id");
                $block_user = $block_user->where(
                        function($block_user) use ($ouser, $loginuser) {
                    $block_user = $block_user->where("user_id", "=", $loginuser)->Where("blocked_user_id", "=", $ouser);
                    $block_user = $block_user->orwhere("user_id", "=", $ouser)->Where("blocked_user_id", "=", $loginuser);
                }
                );
                $block_user = $block_user->where("blocked_user_type", "=", "4")->whereNull("deleted_at")->first();

                //check for message
                //$check_guest = DB::table("guest")->select("message_receive")->where("userid","=",$input["freind_id"])->first();

                $data["is_user_blocked"] = 1;
                $data["blocked_user_message"] = "";
                $data["is_message_blocked"] = 1;
                $data["message_blocked_message"] = "";
                if (!empty($block_user->id)) {

                    if ($block_user->user_id != $input["userid"]) {
                        $succ_msg = isset($csvData['block_user_status']) ? $csvData['block_user_status'] : "Unable to send message as user is blocked you.";
                    } else {
                        $succ_msg = isset($csvData['block_user_status_owner']) ? $csvData['block_user_status_owner'] : "Unable to send message because you have blocked this user.";
                    }
                    $data["is_user_blocked"] = 0;
                    $data["blocked_user_message"] = $succ_msg;
                }

                if ($check_guest->message_receive != 1) {
                    $data["is_message_blocked"] = 0;
                    $data["message_blocked_message"] = "You cannot send message to this user because user has disabled message setting";
                }


                return parent::api_response($data, true, "", 200);
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    public function batchcount_customer(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        'userid' => "required",
                        'latitude' => "required",
                        'longitude' => "required",
            ]);
            if ($validator->fails()) {

                $err_msg = $validator->errors()->first();
                return parent::api_response([], false, $err_msg, 200);
            } else {
                $data['near_by_count_batch'] = (string) Helper::get_near_guest($input['latitude'], $input['longitude'], $input['userid']);

                $data['memory_count_batch'] = "0";
                $data['booking_count_batch'] = "0";

                $data['notification_count_batch'] = "0";
                $data['real_freind'] = Helper::total_real_freind_req($input['userid']);
                $data['request'] = Helper::total_request_count($input['userid']);

                $notification = DB::table("user_notification")->where("user_id", "=", $input["userid"])->where("is_read", "=", 0)->where("user_type", "=", 4);
                $notification = $notification->where(function ($notification) {
                    $notification->where('notification_type', '=', 1)->orWhere('notification_type', '=', 2)->orWhere('notification_type', '=', 3);
                });
                $notification = $notification->count("id");
                if ($notification > 0) {
                    $data['notification_count_batch'] = (string) $notification;
                }
                $res_msg = "Batch count fetched successfully.";
                return parent::api_response($data, true, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    public function check_user_status_by_phone(request $request) {
        try {
            $input = $request->all();

            $data = array();
            if (!empty($input['contacts']) && count($input['contacts']) > 0) {
                // \Log::info($input['contacts']);
                $id = (int) $input["userid"];
                foreach ($input['contacts'] as $value) {
                    $phone = preg_replace("/[^0-9]/", "", $value["phone"]);

                    $results = DB::select("SELECT
                                                userid as uId,profile as profile_image, RIGHT(replace(replace(replace(replace(replace(phone,'(',''),')',''),' ',''),'-',''),'+',''),10) as old_phone,
                                                (SELECT
                                                    userid
                                                FROM
                                                    guest
                                                WHERE
                                                    old_phone = '" . $phone . "' LIMIT 1) as check_phone
                                            FROM
                                                guest
                                            WHERE
                                                deleted_at IS NULL
                                            ORDER BY
                                                check_phone DESC
                                            LIMIT 1");
                    $exists = false;
                    $userid = "";
                    $photo = url("public/default.png");
                    $isFriend = false;
                    $friendRequestSent = false;
                    if ((!empty($results) && !empty($results[0]) && $results[0]->check_phone == 1)) {
                        $exists = true;
                        $userid = $results[0]->uId;

                        if (!empty($results[0]->profile_image)) {
                            $photo = url("public/uploads/user/customer/" . $results[0]->profile_image);
                        }

                        $check_friend = DB::table("user_friends")
                                ->where(function ($query) use($id, $userid) {
                                    $query->where('user_id', $id)
                                    ->where('friend_id', $userid);
                                })->orWhere(function ($query) use($id, $userid) {
                                    $query->where('friend_id', $id)
                                    ->where('user_id', $userid);
                                })
                                ->first();

                        if (!empty($check_friend)) {
                            if (!empty($check_friend->status) && $check_friend->status == 'P') {
                                $friendRequestSent = true;
                            }

                            if (!empty($check_friend->status) && $check_friend->status == 'A' && $check_friend->is_friend == 1) {
                                $isFriend = true;
                            }
                        }
                    }


                    $data[] = [
                        'name' => $value['name'],
                        'phone' => $value['phone'],
                        'exists' => $exists,
                        'userid' => $userid,
                        'photo' => $photo,
                        'isFriend' => $isFriend,
                        'friendRequestSent' => $friendRequestSent,
                    ];
                }

                if (count($data) > 0) {
                    usort($data, function($i, $j) {
                        return $j['exists'] <=> $i['exists'];
                    });
                }
            }

            $res_msg = "Success";
            return parent::api_response($data, true, $res_msg, 200);
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    /**
     *
     * @param type $user_id
     * @param type $city_id
     * @param type $name
     * @param type $lifestyle
     */
    public function send_notification_to_users($user_id, $city_id = null, $name = "New User", $lifestyle = array()) {
        $get_all_data = DB::table("guest")
                ->join('user_preference', 'guest.userid', '=', 'user_preference.user_id')
                ->select('guest.userid', 'guest.device_type', 'guest.device_id')
                ->where('userid', '!=', $user_id)
                ->where('user_preference.user_id', '!=', $user_id);

        if (!empty($city_id)) {
            $get_all_data = $get_all_data->where('guest.city', $city_id);
        }

        $get_all_data = $get_all_data->where('guest.push_notification', 1)
                ->where('guest.status', 1)
                // ->where('guest.is_active',1)
                ->whereNotNull('guest.device_id')
                ->whereIn('guest.device_type', [1, 2])
                ->whereNull('guest.deleted_at')
                ->whereNull('user_preference.deleted_at');

        if (!empty($lifestyle)) {
            $get_all_data = $get_all_data->where(function($query) use($lifestyle) {
                foreach ($lifestyle as $lifestyles) {
                    $query = $query->orWhereRaw('FIND_IN_SET("' . $lifestyles . '",user_preference.lifestyle)');
                }
            });
        }

        //\Log::info($get_all_data->toSql());

        $get_all_data = $get_all_data->get();

        if (!empty($get_all_data)) {


            $message = $name . " added in your city.";
            $title = $message;
            foreach ($get_all_data as $guest) {
                $device_type = $guest->device_type;
                $devicetoken = $guest->device_id;

                $params = array(
                    "new_user_id" => $user_id,
                    "city_id" => $city_id,
                    "user_id" => $guest->userid,
                );

                if ($device_type == 2) {
                    $notificationPayload = array(
                        "body" => $message,
                        "title" => $title
                    );
                    $dataPayload = array(
                        "body" => $message,
                        "title" => $title,
                        "new_user_id" => $user_id,
                        "city_id" => $city_id,
                        "user_id" => $guest->userid,
                    );
                    $notify_data = array(
                        "to" => $devicetoken,
                        "notification" => $notificationPayload,
                        "data" => $dataPayload
                    );
                    $to = "userapp";
                    $send_notification[] = Helper::fcmNotification($message, $notify_data, $to);
                } elseif ($device_type == 1) {

                    $key = 11;
                    $notify_data = array(
                        "notification_key" => $key
                    );

                    $json_notify_data = json_encode($notify_data);


                    $send_notification[] = Helper::sendNotification($device_type, $devicetoken, $message, $title, $json_notify_data, "userapp");
                }

                $notifications[] = array(
                    "message" => $message,
                    "user_id" => $guest->userid,
                    "subject" => $title,
                    "device_type" => $guest->device_type,
                    "notification_key" => 1,
                    "data" => json_encode($params),
                    "user_type" => 4,
                    "notification_type" => 6 //5:New User
                );
            }

            if (!empty($notifications) && count($notifications) > 0) {
                $insert_pushNotification = DB::table("user_notification")->insert($notifications);
            }
        }
    }

    public function send_notification_to_nearest_users($user_id = null) {
        $user = DB::table("guest")->where("userid", $user_id)->whereNull("deleted_at")
                ->whereNotNull("latitude")
                ->whereNotNull("longitude")
                ->where("latitude", '!=', '')
                ->where("longitude", '!=', '')
                ->first();

        if (!empty($user)) {
            $name = !empty($user->username) ? ucwords($user->username) : 'New user';
            $latitude = !empty($user->latitude) ? $user->latitude : '';
            $longitude = !empty($user->longitude) ? $user->longitude : '';

            $get_all_data = DB::table("guest")
                    ->select(DB::raw('3959 * acos (
                          cos ( radians(' . $latitude . ') )
                          * cos( radians( latitude ) )
                          * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin ( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) as distance_from_mylocation'), 'guest.userid', 'guest.device_type', 'guest.device_id', 'guest.latitude', 'guest.longitude', 'guest.profile')
                    ->having('distance_from_mylocation', '<=', 20)
                    ->where('userid', '!=', $user_id);

            $get_all_data = $get_all_data->where('guest.push_notification', 1)
                    ->where('guest.status', 1)
                    ->whereNotNull('guest.device_id')
                    ->whereIn('guest.device_type', [1, 2])
                    ->whereNull('guest.deleted_at');

            //\Log::info($get_all_data->toSql());

            $get_all_data = $get_all_data->get();

            // dd($get_all_data);
            $notification_key = 206;
            if (!empty($get_all_data)) {
                $message = $name . " added in your city.";
                $title = $message;
                foreach ($get_all_data as $guest) {

                    $device_type = $guest->device_type;
                    $devicetoken = $guest->device_id;
                    $photo = '';
                    if (!empty($guest->profile)) {
                        $photo = url("public/uploads/user/customer/" . $guest->profile);
                    }

                    $params = array(
                        "new_user_id" => $user_id,
                        "user_id" => $guest->userid,
                    );

                    if ($device_type == 2) {
                        $notificationPayload = array(
                            "body" => $message,
                            "title" => $title
                        );
                        $dataPayload = array(
                            "body" => $message,
                            "title" => $title,
                            "new_user_id" => $user_id,
                            "user_id" => $guest->userid,
                            "notification_key" => $notification_key,
                            "photo" => $photo,
                        );
                        $notify_data = array(
                            "to" => $devicetoken,
                            "notification" => $notificationPayload,
                            "data" => $dataPayload
                        );
                        $to = "userapp";
                        $send_notification[] = Helper::fcmNotification($message, $notify_data, $to);
                    } elseif ($device_type == 1) {
                        $notify_data = array(
                            "notification_key" => $notification_key,
                            "new_user_id" => $user_id,
                            "user_id" => $guest->userid,
                            "photo" => $photo,
                        );

                        $json_notify_data = json_encode($notify_data);

                        $send_notification[] = Helper::sendNotification($device_type, $devicetoken, $message, $title, $json_notify_data, "userapp");
                    }

                    $notifications[] = array(
                        "message" => $message,
                        "user_id" => $guest->userid,
                        "subject" => $title,
                        "device_type" => $guest->device_type,
                        "notification_key" => $notification_key,
                        "data" => json_encode($params),
                        "user_type" => 4,
                        "notification_type" => $notification_key //206:New User
                    );
                }

                if (!empty($notifications) && count($notifications) > 0) {
                    $insert_pushNotification = DB::table("user_notification")->insert($notifications);
                }
            }
        }
    }

    public function delete_account(request $request) {
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                        'userid' => "required",
            ]);
            if ($validator->fails()) {
                $err_msg = $validator->errors()->first();
                return parent::api_response([], false, $err_msg, 200);
            } else {
                $check_guest = Guest::find($input['userid']);
                if (!empty($check_guest)) {
                    $id = $input['userid'];
                    DB::table("blocked_user")
                            ->where("user_id", $id)
                            ->orWhere("blocked_user_id", $id)
                            ->delete();

                    DB::table('user_preference')
                            ->where('user_id', $id)
                            ->delete();

                    DB::table('user_favorite_venue')
                            ->where('user_id', $id)
                            ->delete();

                    DB::table('user_notification')
                            ->where('user_id', $id)
                            ->delete();

                    DB::table('banner_click')
                            ->where('userid', $id)
                            ->delete();

                    DB::table('memory_approval')
                            ->where('userid', $id)
                            ->delete();

                    $booking = DB::table('booking')
                            ->where('userid', $id)
                            ->get();

                    if (!empty($booking)) {
                        foreach ($booking as $value) {
                            DB::table('booking_items')
                                    ->where('booking_id', $value->id)
                                    ->delete();
                        }
                    }

                    DB::table('booking')
                            ->where('userid', $id)
                            ->delete();

                    DB::table('user_favorite_venue')
                            ->where('user_id', $id)
                            ->delete();

                    DB::table('user_friends')
                            ->where('user_id', $id)
                            ->orWhere('friend_id', $id)
                            ->delete();

                    DB::table('booking_invite_list')
                            ->where('user_id', $id)
                            ->orWhere('friend_id', $id)
                            ->delete();

                    $check_guest->delete();
                    $res_msg = "Account has been deleted successfully";
                    return parent::api_response([], true, $res_msg, 200);
                }
                $res_msg = "Failed to delete account";
                return parent::api_response([], false, $res_msg, 200);
            }
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    public function delete_notification(request $request) {
        try {
            $input = $request->all();


            if (!empty($input['id'])) {
                DB::table('user_notification')
                        ->where('id', $input['id'])
                        ->delete();
            } else if (!empty($input['userid'])) {
                DB::table('user_notification')
                        ->where('user_id', $input['userid'])
                        ->delete();
            }

            $res_msg = "Notification removed.";
            return parent::api_response([], true, $res_msg, 200);
        } catch (\Exception $e) {
            $res_msg = $e->getMessage();
            return parent::api_response([], false, $res_msg, 200);
        }
    }

    public function AddressAdd(request $request) {
        try {
            $input = $request->all();
            if ($input['type'] == 3) {
                //Delete condition
                $validator = Validator::make($input, [
                            "userid" => "required",
                            "type" => "required",
                            "addressid" => "required",
                ]);
            } else if ($input['type'] == 4) {
                //Delete condition
                $validator = Validator::make($input, [
                            "userid" => "required",
                            "type" => "required",
                ]);
            } else {
                $validator = Validator::make($input, [
                            "userid" => "required",
                            "phone" => "required",
                            "address1" => "required",
                            "state" => "required",
                            "zipcode" => "required",
                            "title" => "required",
                            "type" => "required",
                                // "addressid" => "required",
                ]);
            }


            if ($validator->fails()) {
                $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
                return parent::api_response((object) [], false, $err_msg, 200);
            } else {
                if ($input['type'] == 1) {
                    $data = array();
                    $address = new Address;
                    $address->userid = $input['userid'];
                    $address->address_phone = $input['phone'];
                    $address->address1 = $input['address1'];
                    $address->address2 = $input['address2'];
                    $address->state = $input['state'];
                    $address->add_zipcode = $input['zipcode'];
                    $address->address_title = $input['title'];
                    $address->latitude = $input['latitude'];
                    $address->longitude = $input['longitude'];
                    $address->save();
                    $data["user"] = $address;

                    $res_msg = "Address Added successfully!";
                    return parent::api_response($address, true, $res_msg, 200);
                } else if ($input['type'] == 2 && $input['addressid'] != 0) {//EDIT
                    $addressid = $input["addressid"];
                    $userid = $input["userid"];
                    $data = array(
                        "address_phone" => $input["phone"],
                        "address1" => $input["address1"],
                        "address2" => $input["address2"],
                        "state" => $input['state'],
                        "add_zipcode" => $input['zipcode'],
                        "address_title" => $input['title'],
                        "latitude" => $input['latitude'],
                        "longitude" => $input['longitude']
                    );
                    $update = DB::table("address_users")->where("userid", "=", $userid)->where("ad_id", "=", $addressid)->update($data);
                    $message = "Address updated successfully.";
                    return parent::api_response((object) [], true, $message, 200);
                } else if ($input['type'] == 3 && $input['addressid'] != 0) {//Delete
                    $id = $input['addressid'];
                    $userid = $input['userid'];
                    DB::table("address_users")
                            ->where("ad_id", $id)
                            ->where("userid", $userid)
                            ->delete();
                    $message = "Address deleted successfully.";
                    return parent::api_response((object) [], true, $message, 200);
                } else if ($input['type'] == 4) {//listing
                    $userid = $input['userid'];
                    $get_address = DB::table("address_users")->select("*")->where("userid", "=", $userid)->whereNull("ad_deleted_at")->get()->toArray();
                    $res_msg = "Address found successfully!";
                    return parent::api_response($get_address, true, $res_msg, 200);
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function send_group_chat_notification(Request $request) {
        try {
            $input = $request->all();

            if (!empty($input) && !empty($input['firebase_ids'])) {
                $all_firebase_ids = json_decode($input['firebase_ids']);
                if (!empty($all_firebase_ids) && count($all_firebase_ids) > 0) {
                    $notifications = [];
                    $booking_id = !empty($input['booking_id']) ? (int) $input['booking_id'] : 0;
                    $notification_key = 213;
                    $title = "New Group created";
                    $message = !empty($input['message']) ? $input['message'] : "Check group now!";

                    foreach ($all_firebase_ids as $value) {

                        // check it's user?
                        $guest = Guest::where("firebase_id", $value)
                                ->whereNotNull('device_id')
                                ->where('push_notification', 1)
                                ->whereIn('device_type', [1, 2])
                                ->where('status', 1)
                                ->where('is_profile_complete', 1)
                                ->first();

                        if (!empty($guest)) {
                            $device_type = $guest->device_type;
                            $devicetoken = $guest->device_id;

                            $params = array(
                                "booking_id" => $booking_id,
                                "user_id" => $guest->userid,
                            );

                            if ($device_type == 2) {
                                $notificationPayload = array(
                                    "body" => $message,
                                    "title" => $title
                                );
                                $dataPayload = array(
                                    "body" => $message,
                                    "title" => $title,
                                    "booking_id" => $booking_id,
                                    "user_id" => $guest->userid,
                                    "notification_key" => $notification_key,
                                );
                                $notify_data = array(
                                    "to" => $devicetoken,
                                    "notification" => $notificationPayload,
                                    "data" => $dataPayload
                                );
                                $to = "userapp";
                                $send_notification[] = Helper::fcmNotification($message, $notify_data, $to);
                            } elseif ($device_type == 1) {
                                $notify_data = array(
                                    "notification_key" => $notification_key,
                                    "booking_id" => $booking_id,
                                    "user_id" => $guest->userid,
                                );

                                $json_notify_data = json_encode($notify_data);

                                $send_notification[] = Helper::sendNotification($device_type, $devicetoken, $message, $title, $json_notify_data, "userapp");
                            }

                            $notifications[] = array(
                                "message" => $message,
                                "user_id" => $guest->userid,
                                "subject" => $title,
                                "device_type" => $guest->device_type,
                                "notification_key" => $notification_key,
                                "data" => json_encode($params),
                                "user_type" => 4,
                                "notification_type" => $notification_key //213:Group Chat
                            );
                        } else {
                            //if empty
                            //Then check it's venue user from users table?
                            $guest = User::where("firebase_id", $value)
                                    ->whereNotNull('device_token')
                                    ->whereIn('device_type', [1, 2])
                                    ->where('status', 1)
//                                    ->where('is_venuepartner', 1)
                                    ->first();

                            if (!empty($guest)) {
                                $device_type = $guest->device_type;
                                $devicetoken = $guest->device_token;

                                $params = array(
                                    "booking_id" => $booking_id,
                                    "user_id" => $guest->id,
                                );

                                if ($device_type == 2) {
                                    $notificationPayload = array(
                                        "body" => $message,
                                        "title" => $title
                                    );
                                    $dataPayload = array(
                                        "body" => $message,
                                        "title" => $title,
                                        "booking_id" => $booking_id,
                                        "user_id" => $guest->id,
                                        "notification_key" => $notification_key,
                                    );
                                    $notify_data = array(
                                        "to" => $devicetoken,
                                        "notification" => $notificationPayload,
                                        "data" => $dataPayload
                                    );
                                    $to = "venueapp";
                                    $send_notification[] = Helper::fcmNotification($message, $notify_data, $to);
                                } elseif ($device_type == 1) {
                                    $notify_data = array(
                                        "notification_key" => $notification_key,
                                        "booking_id" => $booking_id,
                                        "user_id" => $guest->id,
                                    );

                                    $json_notify_data = json_encode($notify_data);

                                    $send_notification[] = Helper::sendNotification($device_type, $devicetoken, $message, $title, $json_notify_data, "venueapp");
                                }

                                $notifications[] = array(
                                    "message" => $message,
                                    "user_id" => $guest->id,
                                    "subject" => $title,
                                    "device_type" => $guest->device_type,
                                    "notification_key" => $notification_key,
                                    "data" => json_encode($params),
                                    "user_type" => 2, //venue user
                                    "notification_type" => $notification_key //213:Group Chat
                                );
                            }
                        }
                    }

                    if (!empty($notifications) && count($notifications) > 0) {
                        DB::table("user_notification")->insert($notifications);
                    }
                }
            }

            $res_msg = "Push sent successfully";
            return parent::api_response((object) [], true, $res_msg, 200);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return parent::api_response((object) [], false, $message, 200);
        }
    }

    public function changeEmail(request $request) {
       try {
           $input = $request->all();
           $validator = Validator::make($input, [
              "email" => "required",
           ]);

          if ($validator->fails()) {
               $err_msg = parent::getErrorMsg($validator->errors()->toArray(), $request);
               return parent::api_response((object) [], false, $err_msg, 200);
           } else {
               $userid = $input["userid"];
               $otpCode = rand(1000, 9999);
               $data = array();
               if (isset($input['email'])) {
                 $findownemail = DB::table("guest")->where("email","=",$input['email'])->where("userid", "=", $userid)->first();
                 if (!empty($findownemail)) {
                   $message = "You can not enter same email address";
                   return parent::api_response((object) [], false, $message, 200);
                 }else {
                   $findemail = DB::table("guest")->where("email","=",$input['email'])->where("userid", "!=", $userid)->first();
                   if (empty($findemail)) {
                     $data = array(
                       "email" => $input["email"],
                       "is_email_verified" => "0",
                       "otp" => $otpCode
                     );
                     $update = DB::table("guest")->where("userid", "=", $userid)->update($data);
                     if (!empty($input['email'])) {
                       try {
                         $objDemo = new \stdClass();
                         $objDemo->demo_one = 'Email Change Request in ' . env('APP_NAME');
                         $objDemo->sender = Config::get('constants.SENDER_EMAIL');
                         $objDemo->website = Config::get('constants.SENDER_WEBSITE');
                         $objDemo->sender_name = Config::get('constants.SENDER_NAME');
                         $objDemo->receiver_name = "";
                         $objDemo->email = $input['email'];
                         $objDemo->receiver = "";
                         $objDemo->otp = $otpCode;
                         $objDemo->subject = env('APP_NAME') . " : Your account have email change request.";
                         Mail::to($input['email'])->send(new DemoEmail($objDemo));
                       } catch (Exception $e) {
                         return parent::api_response($data, false, $e->getMessage(), 200);
                       }
                     }
                     $message = "Email changed successfully.";
                     return parent::api_response((object) [], true, $message, 200);
                 }else {
                   $message = "Email is already register with different account";
                   return parent::api_response((object) [], false, $message, 200);
                 }
                   }
               }
           }
       } catch (\Exception $e) {
           $message = $e->getMessage();
           return parent::api_response((object) [], false, $message, 200);
       }
   }

}
