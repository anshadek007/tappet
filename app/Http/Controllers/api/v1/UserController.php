<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\User;
use App\BusinessUser;
use App\AESCrypt;
use App\UserDeviceToken;
use App\Banks;
use App\UserFriends;
use App\Settings;
use App\UserBlocks;
use App\Notification;
use App\Pets;
use App\Groups;
use App\Events;
use App\UserCallHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\CorporateFollwer;
use App\GuestUser;
use Exception;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Builder;
use App\Traits\ConversationIdGenerator;
class UserController extends APIController
{
    use ConversationIdGenerator;
    protected $userModel;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->userModel = new \App\User();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {

        $request->merge([
            'u_email' => $request->u_email,
            'u_password' => Hash::make($request->u_password),
            'u_user_type' => $request->u_user_type,
            'u_social_id' => $request->u_social_id,
            'device_type' => $request->device_type,
            'device_token' => $request->device_token,
        ]);

        $getMessage = $this->validator($request->all())->errors()->first();
        if ($getMessage != "") {
            $message = ["result" => (object) array(), "message" => $getMessage, "status" => false, "code" => 60];
            return response()->json($message, 200);
        }


        $request_data = $request->all();

        unset($request_data['device_type']);
        unset($request_data['device_token']);
        $code = 0;
        $user_data = User::where('u_email', trim($request->u_email))
        ->where('u_status', 1)
        ->where('is_guest',0)
        ->first();
        
        if (!empty($user_data)) {
            $userdetail_data = $this->get_userdata($user_data);

            if ($user_data->u_is_verified != 1) {
                return $this->respondResult($userdetail_data, 'Email not verified', false, 1000);
            }

            if ($user_data->u_phone_verified != 1) {
                return $this->respondResult($userdetail_data, 'Phone number not verified', false, 2000);
            }
            if ($request->u_user_type == 1) {
                $successMessage = "Your account is already exist, You can access your account via Login";
                $code = 3000;
            } else if ($request->u_user_type != 1) {
                $user_data = User::where('u_social_id', trim($request->u_social_id))
                    ->where('u_user_type', $request->u_user_type)
                    ->where('u_status', 1)
                    ->first();

                if (empty($user_data)) {
                    $user_data = User::create($request_data);
                }
            
                $successMessage = "Registration successfully completed";
                $code = 0;
            }
        } else {
            
            $user_data = array();
            $guest_user_data = User::where('u_email',$request_data['u_email'])
            ->where('is_guest',1)
            ->first();
           
            if(!empty($guest_user_data))
            {
                $request_data['is_guest'] = 0;
                $user_data = User::where('u_email', trim($request->u_email))->update($request_data);
                $user_data =  $guest_user_data;
                
            }else{
               

                if ($request->u_user_type == 1) {
                    $user_data = User::create($request_data);
                } else if ($request->u_user_type != 1) {
                    $user_data = User::where('u_social_id', trim($request->u_social_id))
                        ->where('u_user_type', $request->u_user_type)
                        ->where('u_status', 1)
                        ->first();
    
                    if (empty($user_data)) {
                        $user_data = User::create($request_data);
                    }
                }
            }
           

            $successMessage = "Registration successfully completed";
           
            if (!empty($user_data)) {
                if ($request->u_user_type == 1) {
                    $OTP = rand(1000, 9999);

                    $user_email = $request->u_email;
                    try {
                    $email = array($user_email);
                    $data = array(
                        'otp' => $OTP,
                    );

                    Mail::send('emails.send_otp_in_email', $data, function ($message) use($email) {
                        $message->from(env("MAIL_USERNAME"), config('app.name'));
                        $message->to($email);
                        $message->subject(config('app.name') . " : Verification mail");
                    });
                    } catch (\Exception $e) {
                        return $this->respondWithError($e->getMessage());
                        //return $this->respondWithError("Failed: Problem while sending verification mail, try again!");
                    }

                    $user_data->u_otp = $OTP;
                    $user_data->u_is_verified = 2;
                    $user_data->update();
                }
            }

            // $this->update_device_token($user_data->u_id, $request->device_token, $request->device_type);
        }

        if (!empty($user_data)) {
            $userdetail_data = $this->get_userdata($user_data);

            if ($request->u_user_type != 1) {
                $userdetail_data->token = $user_data->createToken("app_user")->accessToken;
            }
            $guest_user_data = GuestUser::where('email', $request->u_email)->first();
            // if (!empty($guest_user_data)) {

            //     $user = DB::table('users')->where('u_id', $user_data->u_id)->first();
            //     if ($user) {
            //         $conversation_id = $guest_user_data->conversation_id;
            //     }else{
            //         $conversation_id = $this->conversation_id_generator();
            //     }
            // }else{
            //     $conversation_id = $this->conversation_id_generator();
            //     $update_user = DB::table('users')
            //     ->where('u_id', $user_data->u_id)
            //     ->update(['conversation_id' =>  $conversation_id]);
            // }

            // $userdetail_data->conversation_id =  $conversation_id;

            $message = ["result" => $userdetail_data, "message" => $successMessage, "status" => true, "code" => $code];
        } else {
            $message = ["result" => (object) array(), "message" => "Invalid Email / Phone number", "status" => false, "code" => 0];
        }

        return response()->json($message, 200);
    }

    public function login(Request $request)
    {
        try {


            $request->merge([
                'u_user_type' => $request->u_user_type,
                'u_email' => $request->u_email,
                'u_password' => $request->u_password,
                'u_social_id' => $request->u_social_id,
                'device_type' => $request->device_type,
                'device_token' => $request->device_token,
            ]);

            $u_user_type = $request->u_user_type;
            $u_email = $request->u_email;
            $u_password = $request->u_password;
            $u_social_id = $request->u_social_id;


            if (
                ($u_user_type == 1 && (empty($u_email) || empty($u_password))) ||
                ($u_user_type == 2 && empty($u_social_id))
            ) {

                return $this->sendApiFailedLoginResponse($request);
            }

            $user_data = array();

            if ($u_user_type == 1 && !empty($u_email)) {
                $user_data = User::where('u_email', trim($u_email))
                    ->where('u_status', 1)
                    ->first();
                if (!$user_data) {
                    return $this->sendApiFailedLoginResponse($request);
                }
                if (!Hash::check($u_password, $user_data->u_password)) {
                    return $this->sendApiFailedLoginResponse($request);
                }
            } else if ($u_user_type != 1 && !empty($u_social_id)) {
                $user_data = User::where('u_social_id', trim($u_social_id))
                    ->where('u_user_type', $u_user_type)
                    ->where('u_status', 1)
                    ->first();
            }



            $successMessage = "Login successfully";
            if (!empty($user_data)) {

                if ($user_data->u_is_verified != 1) {
                    return $this->respondResult("", 'Email not verified', false, 1000);
                }

                if ($user_data->u_phone_verified != 1) {
                    return $this->respondResult("", 'Phone number not verified', false, 2000);
                }

                $userdetail_data = $this->get_userdata($user_data);

                if (!env('ALLOW_MULTI_LOGIN', false)) {
                    \DB::table('oauth_access_tokens')
                        ->where('name', "app_user")
                        ->where('user_id', $user_data->u_id)
                        ->update(['revoked' => true]);
                }

                $this->update_device_token($user_data->u_id, $request->device_token, $request->device_type);

                $userdetail_data->token = $user_data->createToken("app_user")->accessToken;

                $message = ["result" => $userdetail_data, "message" => $successMessage, "status" => true, "code" => 0];
            } else {
                $message = ["result" => (object) array(), "message" => "Invalid email Or Password", "status" => false, "code" => 0];
            }

            return response()->json($message, 200);
        } catch (Exception $e) {

            echo 'Message: ' . $e->getMessage();
        }
    }

    public function verifyOTP(Request $request)
    {

        $request->merge([
            'u_mobile_number' => $request->u_mobile_number,
            'u_otp' => $request->u_otp,
            'device_token' => $request->device_token,
            'device_type' => $request->device_type
        ]);

        $u_mobile_number = $request->u_mobile_number;
        $u_otp = $request->u_otp;
        $device_token = $request->device_token;
        $device_type = $request->device_type;

        if (empty($u_mobile_number) || empty($u_otp)) {
            return $this->sendApiFailedLoginResponse($request);
        }

        $user_data = array();
        $user_data = User::where('u_mobile_number', trim($u_mobile_number))
            ->where('u_otp', $u_otp)
            ->where('u_status', 1)->first();

        if (!empty($user_data)) {
            $user_data->u_otp = "";
            $user_data->u_phone_verified = 1;
            $user_data->update();

            $userdetail_data = $this->get_userdata($user_data);
            $message = ["result" => $userdetail_data, "message" => "OTP verified successfully.", "status" => true, "code" => 0];
        } else {
            $message = ["result" => (object) array(), "message" => "Invalid OTP", "status" => false, "code" => 0];
        }

        return response()->json($message, 200);
    }

    public function verifyEmailOTP(Request $request)
    {

        $request->merge([
            'u_email' => $request->u_email,
            'u_otp' => $request->u_otp,
            'device_token' => $request->device_token,
            'device_type' => $request->device_type
        ]);

        $u_email = $request->u_email;
        $u_otp = $request->u_otp;
        $device_token = $request->device_token;
        $device_type = $request->device_type;

        if (empty($u_email) || empty($u_otp)) {
            return $this->sendApiFailedLoginResponse($request);
        }

        $user_data = array();
        $user_data = User::where('u_email', trim($u_email))->where('u_otp', $u_otp)->where('u_status', 1)->first();

        if (!empty($user_data)) {
            $user_data->u_otp = "";
            $user_data->u_is_verified = 1;
            $user_data->update();

            $userdetail_data = $this->get_userdata($user_data);
            $userdetail_data->token = $user_data->createToken("app_user")->accessToken;
            $message = ["result" => $userdetail_data, "message" => "Email verified successfully.", "status" => true, "code" => 0];
        } else {
            $message = ["result" => (object) array(), "message" => "Invalid Verification code", "status" => false, "code" => 0];
        }

        return response()->json($message, 200);
    }

    public function sendApiFailedLoginResponse(Request $request)
    {
        $message = [
            "result" => (object) null, "message" => AESCrypt::encryptString(trans('auth.failed')),
            "status" => FALSE, "code" => 60
        ];

        return response()->json($message, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }
        $login_user_id = Auth::user()->u_id;

        $user = $this->userModel->validateUser($id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $userdetail_data = $this->get_userdata($user, $login_user_id);

        $userdetail_data = (array) $userdetail_data;

        $pets = $this->userModel->pets($id)->get();

        if (!empty($pets)) {

            foreach ($pets as &$value) {
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
                }
            }
        }

        $userdetail_data['pets'] = $pets;

        $pet_co_owned = $this->userModel->pet_co_owned($id)->get();

        if (!empty($pet_co_owned)) {

            foreach ($pet_co_owned as &$value) {
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
                }
            }
        }

        $userdetail_data['pet_co_owned'] = $pet_co_owned;

        $friend_invite = UserBlocks::where("user_block_user_id", $login_user_id)
            ->where("user_block_blocked_user_id", $id)
            ->first();


        $follwercount = CorporateFollwer::where('u_id', $userdetail_data['u_id'])->count();
        $userdetail_data['follwerCount'] = $follwercount;

        $userdetail_data['userblockbyme'] = !empty($friend_invite) ? true : false;

        return $this->respondResult($userdetail_data, 'User details found successfully.', true, 200);
    }

    public function get_user_devices(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $devices = $user->user_devices;
        if (!empty($devices) && $devices->count() > 0) {
            return $this->respondResult($devices, 'User device found successfully.', true, 200);
        } else {
            return $this->respondResult("", 'No device found.', true, 200);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = $this->userModel->validateUser($id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $rules = [
            'u_first_name' => ['required', 'string', 'max:255'],
            'u_last_name' => ['required', 'string', 'max:255'],
            'u_mobile_number' => ['required', 'max:20'],
            'u_email' => ['required', 'email', 'max:255', 'unique:users,u_email,' . $user->u_id . ',u_id'],
        ];

        $customMessages = [
            'u_first_name.required' => 'First Name is required field.',
            'u_first_name.string' => 'First Name allows only alphabetical characters.',
            'u_last_name.required' => 'Last Name is required field.',
            'u_last_name.string' => 'Last Name allows only alphabetical characters.',
            'u_mobile_number.required' => 'Phone number is required field.',
            'u_mobile_number.max' => 'Phone number may not be greater than 20 characters.',
            'u_email.required' => 'Email is required field.',
            'u_email.email' => "Email must be a valid email address.",
            'u_email.unique' => "The {$request->u_email} email has already been taken.",
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        $user->u_first_name = $request->u_first_name;
        $user->u_last_name = $request->u_last_name;
        $user->u_mobile_number = $request->u_mobile_number;
        $user->u_email = $request->u_email;
        $user->save();

        return $this->respond($this->userModel->getAuthUser($id));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function updateUserDetail(Request $request)
    {
        $id = !empty($request->u_id) ? $request->u_id : "";
        $user = $this->userModel->validateUser($id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $rules = [
            //            'u_first_name' => ['required', 'string', 'max:255'],
            //            'u_last_name' => ['required', 'string', 'max:255'],
            //            'u_mobile_number' => ['required', 'max:20', 'unique:users,u_mobile_number,' . $user->u_id . ',u_id,u_deleted_at,NULL'],
            //            'u_gender' => ['required'],
            //'u_dob' => ['required'],
        ];

        if (!empty($request->u_mobile_number)) {
            $rules['u_mobile_number'] = ['required', 'max:20', 'unique:users,u_mobile_number,' . $user->u_id . ',u_id,u_deleted_at,NULL'];
        }

        if (!empty($request->file('u_image'))) {
            $rules['u_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
        }

        $customMessages = [
            'u_first_name.required' => 'First Name is required field.',
            'u_first_name.string' => 'First Name allows only alphabetical characters.',
            'u_last_name.required' => 'Last Name is required field.',
            'u_last_name.string' => 'Last Name allows only alphabetical characters.',
            'u_email.required' => 'Email is required field.',
            'u_email.email' => "Email must be a valid email address.",
            'u_email.unique' => "The {$request->u_email} email has already been taken.",
            'u_mobile_number.required' => 'Phone number is required field.',
            'u_mobile_number.max' => 'Phone number may not be greater than 20 characters.',
            'u_mobile_number.unique' => "The {$request->u_mobile_number} phone number has already been taken.",
            'u_latitude.required' => 'Latitude is required field.',
            'u_latitude.max' => 'Latitude may not be greater than 30 characters.',
            'u_longitude.required' => 'Longitude is required field.',
            'u_longitude.max' => 'Latitude may not be greater than 30 characters.',
            'u_image.image' => 'The type of the uploaded file should be an image.',
            'u_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.',
            'u_gender.required' => 'Gender is required field.',
            'u_dob.required' => 'Date of Birth is required field.',
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        if (!empty($request->file('u_image'))) {
            $fileName = $this->uploadFile($request->file('u_image'), $id, 'users');
            if (!$fileName) {
                return $this->respondWithError("Failed to upload profile picture, Try again..!");
            }
            $user->u_image = $fileName;
        }

        if (!empty($request->u_first_name)) {
            $user->u_first_name = $request->u_first_name;
        }

        if (!empty($request->u_last_name)) {
            $user->u_last_name = $request->u_last_name;
        }

        if (!empty($request->u_mobile_number)) {
            if ($request->u_mobile_number != $user->u_mobile_number) {
                // $OTP = (int) rand(1000, 9999);
                $OTP = 1234;
                $user->u_otp = $OTP;
                $user->u_phone_verified = 2;
            }

            $user->u_mobile_number = $request->u_mobile_number;
        }

        if (!empty($request->u_zipcode)) {
            $user->u_zipcode = $request->u_zipcode;
        }

        if (!empty($request->u_address)) {
            $user->u_address = $request->u_address;
        }

        if (!empty($request->u_city)) {
            $user->u_city = $request->u_city;
        }

        if (!empty($request->u_country)) {
            $user->u_country = $request->u_country;
        }

        if (!empty($request->u_dob)) {
            $user->u_dob = $request->u_dob;
        }
        if (!empty($request->u_latitude)) {
            $user->u_latitude = $request->u_latitude;
        }
        if (!empty($request->u_longitude)) {
            $user->u_longitude = $request->u_longitude;
        }

        if (!empty($request->u_gender)) {
            $user->u_gender = $request->u_gender;
        }

        $user->save();

        $userdetail_data = $this->get_userdata($user);

        return $this->respondResult($userdetail_data, 'User details updated successfully.', true, 200);
    }

    public function update_notification_settings(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        if (!empty($request->u_group_message_notification)) {
            $user->u_group_message_notification = (int) $request->u_group_message_notification;
        }
        if (!empty($request->u_post_comment_notification)) {
            $user->u_post_comment_notification = (int) $request->u_post_comment_notification;
        }
        if (!empty($request->u_post_like_notification)) {
            $user->u_post_like_notification = (int) $request->u_post_like_notification;
        }
        if (!empty($request->u_friend_request_notification)) {
            $user->u_friend_request_notification = (int) $request->u_friend_request_notification;
        }
        if (!empty($request->u_event_notification)) {
            $user->u_event_notification = (int) $request->u_event_notification;
        }

        $user->save();

        $userdetail_data = $this->get_userdata($user);

        return $this->respondResult($userdetail_data, 'Notification Alerts updated successfully.', true, 200);
    }

    public function updateProfilePicture(Request $request)
    {

        $request->merge([
            'u_id' => $request->u_id,
        ]);

        $id = !empty($request->u_id) ? $request->u_id : "";
        $user = $this->userModel->validateUser($id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $rules = [
            'u_id' => ['required'],
        ];

        if (!empty($request->file('u_image'))) {
            $rules['u_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
        }

        $customMessages = [
            'u_image.image' => 'The type of the uploaded file should be an image.',
            'u_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        if (!empty($request->file('u_image'))) {
            $fileName = $this->uploadFile($request->file('u_image'), $id, 'users');


            if (!$fileName) {
                return $this->respondWithError("Failed to upload profile picture, Try again..!");
            }
            $user->u_image = $fileName;
        }

        $user->save();

        $userdetail_data = $this->get_userdata($user);

        return $this->respondResult($userdetail_data, 'User detail updeted successfully.', true, 200);
    }

    public function changePassword(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $rules = [
            'current_password' => 'required|min:6',
            'new_password' => 'required|min:6',
        ];

        $customMessages = [
            'current_password.required' => 'Current password is required',
            'current_password.min' => 'Current password needs to have at least 6 characters',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password needs to have at least 6 characters',
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        $current_password = $user->u_password;
        if (\Hash::check($request->current_password, $current_password)) {
            $user->u_password = \Hash::make($request->new_password);
            $user->save();
            return $this->respondResult(null, "Password changed successfully", true, 0);
        } else {
            return $this->respondWithError("Please enter correct current password.");
        }
    }

    public function createPassword(Request $request)
    {
        $id = !empty($request->u_id) ? $request->u_id : "";
        $user = $this->userModel->validateUser($id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }
        $rules = [
            'u_id' => 'required',
            'u_email' => 'required',
            'u_otp' => 'required',
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required|min:6',
        ];

        $customMessages = [
            'u_id.required' => 'User ID is required',
            'u_email.required' => 'Email is required',
            'u_otp.required' => 'OTP is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password needs to have at least 6 characters',
            'password_confirmation.required' => 'Confirm password is required',
            'password_confirmation.min' => 'Confirm password needs to have at least 6 characters',
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        $user_data = User::where('u_email', trim($request->u_email))
            ->where('u_otp', trim($request->u_otp))
            ->where('u_id', trim($request->u_id))
            ->where('u_status', 1)
            ->first();

        $successMessage = "OTP resend successfully";
        if (!empty($user_data)) {
            $user->u_password = \Hash::make($request->password);
            $user->u_otp = "";
            $user->u_updated_at = Carbon::now();
            $user->update();
            return $this->respondResult(null, "Password changed successfully", true, 0);
        }

        return $this->respondResult("", 'Invalid OTP', false, 200);
    }

    public function ForgotPassword(Request $request)
    {
        if (empty($request->u_email)) {
            return $this->respondResult("", 'Email is required', false, 200);
        }

        $user_data = User::where('u_email', trim($request->u_email))
            ->where('u_status', 1)
            ->first();

        if (!empty($user_data)) {

            // $OTP = rand(1000, 9999);
            $OTP = 1234;
            $user_email = $request->u_email;
            try {
                $email = array($user_email);
                $data = array(
                    'name' => $user_data->u_first_name . ' ' . $user_data->u_last_name,
                    'otp' => $OTP,
                );

                Mail::send('emails.send_forgot_password_otp_in_email', $data, function ($message) use ($email) {
                    $message->from(env("MAIL_USERNAME"), config('app.name'));
                    $message->to($email);
                    $message->subject(config('app.name') . " : Forgot password mail");
                });
            } catch (\Exception $e) {
                //                return $this->respondWithError($e->getMessage());
                return $this->respondWithError("Failed: Problem while sending mail, try again!");
            }

            $user_data->u_otp = $OTP;
            $user_data->update();

            $userdetail_data = $this->get_userdata($user_data);

            $message = ["result" => $userdetail_data, "message" => "We have sent a password recover instructions to your email.", "status" => true, "code" => 0];
        } else {
            $message = ["result" => (object) array(), "message" => "This email not registered with us.", "status" => false, "code" => 0];
        }

        return response()->json($message, 200);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'u_user_type' => ['required'],
        ];

        if (isset($data['u_image'])) {
            $rules['u_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
        }

        $customMessages = [
            //            'u_first_name.required' => 'Name is required field.',
            //            'u_first_name.string' => 'Name allows only alphabetical characters.',
            'u_user_type.required' => 'User type is required field.',
            //'u_user_type.integer' => 'Invalid user account type',
            //'u_user_type.max' => 'Invalid user account type',
            //            'u_image.image' => 'The type of the uploaded file should be an image.',
            //            'u_image.mimes' => 'The type of the uploaded file should be an image.',
            //            'u_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
        ];

        $request_email = isset($data['u_email']) ? $data['u_email'] : '';
        $request_phone = isset($data['u_mobile_number']) ? $data['u_mobile_number'] : '';

        if (isset($data['u_user_type']) && $data['u_user_type'] == 1) {
            //            $rules['u_first_name'] = ['required', 'string', 'max:255'];
            $rules['u_email'] = ['required', 'email', 'max:255'];
            //            $rules['u_email'] = ['required', 'email', 'max:255', 'unique:users,u_email,NULL,u_id,u_deleted_at,NULL'];
            //            $rules['u_country_code'] = ['required'];
            //            $rules['u_mobile_number'] = ['required', 'min:10', 'max:20', 'unique:users,u_mobile_number,NULL,u_id,u_deleted_at,NULL'];

            $customMessages['u_email.required'] = "Email is required field.";
            $customMessages['u_email.email'] = "Email must be a valid email address.";
            $customMessages['u_email.unique'] = "The {$request_email} email has already been taken.";
            $customMessages['u_country_code.required'] = "Country Code is required field.";
            $customMessages['u_mobile_number.required'] = "Phone number is required field.";
            $customMessages['u_mobile_number.min'] = "Phone number may not be less than 10 digits.";
            $customMessages['u_mobile_number.max'] = "Phone number may not be greater than 20 digits.";
            $customMessages['u_mobile_number.unique'] = "The {$request_phone} mobile number has already been taken.";
        } else if (isset($data['u_user_type']) && $data['u_user_type'] != 1) {
            $rules['u_social_id'] = ['required', 'max:255'];

            if (!empty($request_email)) {
                $rules['u_email'] = ['required', 'email', 'max:255'];
                //                $rules['u_email'] = ['required', 'email', 'max:255', 'unique:users,u_email,NULL,u_id,u_deleted_at,NULL'];
                $customMessages['u_email.required'] = "Email is required field.";
                $customMessages['u_email.email'] = "Email must be a valid email address.";
                $customMessages['u_email.unique'] = "The {$request_email} email has already been taken.";
            }
            //
            //            if (!empty($request_phone)) {
            //                $rules['u_mobile_number'] = ['required', 'min:10', 'max:20', 'unique:users,u_mobile_number,NULL,u_id,u_deleted_at,NULL'];
            //                $customMessages['u_mobile_number.required'] = "Phone number is required field.";
            //                $customMessages['u_mobile_number.min'] = "Phone number may not be less than 10 digits.";
            //                $customMessages['u_mobile_number.max'] = "Phone number may not be greater than 20 digits.";
            //                $customMessages['u_mobile_number.unique'] = "The {$request_phone} mobile number has already been taken.";
            //            }

            $customMessages['u_social_id.required'] = "Invalid request parameters, Try again...!";
            $customMessages['u_social_id.max'] = "Invalid request parameters, Try again...!";
        }

        return Validator::make($data, $rules, $customMessages);
    }

    public function updateDeviceToken(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $user_id = Auth::user()->u_id;

        $security_token = !empty($request->device_uid) ? trim($request->device_uid) : "";
        $device_token = !empty($request->device_token) ? trim($request->device_token) : "";
        $device_type = !empty($request->device_type) ? trim($request->device_type) : "";
        $device_name = !empty($request->device_name) ? trim($request->device_name) : "";
        $device_model_name = !empty($request->device_model_name) ? trim($request->device_model_name) : "";
        $device_os_version = !empty($request->device_os_version) ? trim($request->device_os_version) : "";
        $app_version = !empty($request->app_version) ? trim($request->app_version) : "";
        $api_version = !empty($request->api_version) ? trim($request->api_version) : "";

        $this->update_device_token($user_id, $device_token, $device_type, $security_token, $device_name, $device_model_name, $device_os_version, $app_version, $api_version);
        return $this->respondResult(array(), 'Device token updated succesfully', true, 200);
    }

    public function resendOTP(Request $request)
    {
        $user_data = array();
        if (!empty($request->u_mobile_number)) {
            $user_data = User::select('u_id', 'u_email', 'u_mobile_number', 'u_otp')->where('u_mobile_number', trim($request->u_mobile_number))->first();
        } else if (!empty($request->u_email)) {
            $user_data = User::select('u_id', 'u_email', 'u_mobile_number', 'u_otp')->where('u_email', trim($request->u_email))->first();
        }

        $successMessage = "Failed to send OTP";
        if (!empty($user_data)) {
            try {
                // $OTP = rand(1000, 9999);
                $OTP = 1234;

                if (!empty($request->u_email)) {
                    $email = array($user_data->u_email);
                    $data = array(
                        'otp' => $OTP,
                    );

                    Mail::send('emails.send_otp_in_email', $data, function ($message) use ($email) {
                        $message->from('Tappet@admin.com', config('app.name'));
                        $message->to($email);
                        $message->subject(config('app.name') . " : Verification mail");
                    });
                }
            } catch (\Exception $e) {
                return $this->respondWithError($e->getMessage());
            }


            if (!empty($request->u_mobile_number)) {
                $user_data->u_otp = $OTP;
                $user_data->u_phone_verified = 2;
                $successMessage = "OTP resend successfully";
            }

            if (!empty($request->u_email)) {
                $user_data->u_otp = $OTP;
                $user_data->u_is_verified = 2;
                $successMessage = "OTP resend successfully";
            }

            $user_data->update();

            $userdetail_data = $this->get_userdata($user_data);

            $message = ["result" => $userdetail_data, "message" => $successMessage, "status" => true, "code" => 0];
        } else {
            $message = ["result" => (object) array(), "message" => "Invalid request", "status" => false, "code" => 0];
        }

        return response()->json($message, 200);
    }

    public function get_userdata($user_data, $login_user_id = 0)
    {

        $userdetail_data = new \stdClass();
        $userdetail_data->u_id = $user_data->u_id;
        $userdetail_data->conversation_id = $user_data->conversation_id;
        $userdetail_data->u_first_name = !empty($user_data->u_first_name) ? $user_data->u_first_name : "";
        $userdetail_data->u_last_name = !empty($user_data->u_last_name) ? $user_data->u_last_name : "";
        $userdetail_data->u_email = !empty($user_data->u_email) ? $user_data->u_email : "";
        $userdetail_data->u_mobile_number = !empty($user_data->u_mobile_number) ? $user_data->u_mobile_number : "";
        $userdetail_data->u_country = !empty($user_data->u_country) ? $user_data->u_country : "";
        $userdetail_data->u_state = !empty($user_data->u_state) ? $user_data->u_state : "";
        $userdetail_data->u_city = !empty($user_data->u_city) ? $user_data->u_city : "";
        $userdetail_data->u_latitude = !empty($user_data->u_latitude) ? $user_data->u_latitude : "";
        $userdetail_data->u_longitude = !empty($user_data->u_longitude) ? $user_data->u_longitude : "";
        $userdetail_data->u_image = $user_data->u_image;
        $userdetail_data->u_country_code = !empty($user_data->u_country_code) ? $user_data->u_country_code : "";
        $userdetail_data->u_gender = !empty($user_data->u_gender) ? $user_data->u_gender : "";
        $userdetail_data->u_dob = !empty($user_data->u_dob) ? $user_data->u_dob : "";
        $userdetail_data->u_zipcode = !empty($user_data->u_zipcode) ? $user_data->u_zipcode : "";
        $userdetail_data->u_address = !empty($user_data->u_address) ? $user_data->u_address : "";
        $userdetail_data->u_is_verified = !empty($user_data->u_is_verified) ? (int) $user_data->u_is_verified : 2;
        $userdetail_data->u_phone_verified = !empty($user_data->u_phone_verified) ? (int) $user_data->u_phone_verified : 2;
        $userdetail_data->u_otp = !empty($user_data->u_otp) ? intval($user_data->u_otp) : "";
        $userdetail_data->u_group_message_notification = !empty($user_data->u_group_message_notification) ? $user_data->u_group_message_notification : 2;
        $userdetail_data->u_post_comment_notification = !empty($user_data->u_post_comment_notification) ? $user_data->u_post_comment_notification : 1;
        $userdetail_data->u_post_like_notification = !empty($user_data->u_post_like_notification) ? $user_data->u_post_like_notification : 1;
        $userdetail_data->u_friend_request_notification = !empty($user_data->u_friend_request_notification) ? $user_data->u_friend_request_notification : 1;
        $userdetail_data->u_event_notification = !empty($user_data->u_event_notification) ? $user_data->u_event_notification : 1;
        $userdetail_data->has_total_friends_count = $this->userModel->has_total_friends_count($user_data->u_id);
        $userdetail_data->has_total_business_count = 0;
        $userdetail_data->has_total_mutual_friends_count = $this->userModel->find_mutual_friends($user_data->u_id, $login_user_id);

        $userdetail_data->friend_request = 0;
        $userdetail_data->friend_request_sent_by_me = false;

        if (!empty($login_user_id)) {
            $invites = 'SELECT
                        tappet_user_friends.*
                    FROM
                        tappet_user_friends
                    LEFT JOIN
                        tappet_users
                    ON
                        `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                    WHERE
                        (`ufr_user_id` = ' . $user_data->u_id . ' AND `ufr_invited_user_id` = ' . $login_user_id . ')
                        OR
                        (`ufr_user_id` = ' . $login_user_id . ' AND `ufr_invited_user_id` = ' . $user_data->u_id . ')
                    AND `u_status` != 9
                    AND `ufr_status` != 9
                    AND `ufr_deleted_at` IS NULL
                    AND `tappet_users`.`u_deleted_at` IS NULL
                    LIMIT 1';

            $check_friend = \DB::select($invites);

            if (!empty($check_friend) && count($check_friend) > 0) {
                $userdetail_data->friend_request = $check_friend[0]->ufr_status;
                $userdetail_data->friend_request_sent_by_me = $check_friend[0]->ufr_user_id == $login_user_id ? true : false;
            }
        }

        return $userdetail_data;
    }

    public function logout(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        //delete the device token of the same user id if exists
        //        UserDeviceToken::where('udt_u_id', Auth::user()->u_id)->delete();

        if (!empty($request->device_uid)) {
            UserDeviceToken::where('udt_security_token', $request->device_uid)->delete();
        }

        $user = Auth::user()->token();
        $user->revoke();
        return $this->respondResult(array(), 'Logout successfully.', true, 200);
    }

    /**
     *
     * @param type $user_id
     * @param type $device_token
     * @param type $device_type
     * @param type $security_token
     * @param type $device_name
     * @param type $device_model_name
     * @param type $device_os_version
     * @param type $app_version
     * @param type $api_version
     */
    public function update_device_token($user_id = 0, $device_token = null, $device_type = null, $security_token = null, $device_name = "", $device_model_name = "", $device_os_version = "", $app_version = "", $api_version = "")
    {
        if (!empty($user_id) && !empty($device_token) && !empty($device_type) && !empty($security_token)) {
            //delete the device token of the same user id if exists
            if (!env('ALLOW_MULTI_LOGIN', false)) {
                UserDeviceToken::where('udt_u_id', $user_id)->delete();
            }

            $result = UserDeviceToken::updateOrCreate([
                'udt_security_token' => $security_token
            ], [
                'udt_u_id' => $user_id,
                'udt_device_token' => $device_token,
                'udt_device_type' => $device_type,
                'udt_device_name' => $device_name,
                'udt_device_model_name' => $device_model_name,
                'udt_device_os_version' => $device_os_version,
                'udt_app_version' => $app_version,
                'udt_api_version' => $api_version,
            ]);
        }
    }

    public function remove_device(Request $request)
    {
        $response["message"] = "Device ID is required";
        if (!empty($request->device_id)) {
            UserDeviceToken::where('udt_id', $request->device_id)->delete();
            $response["message"] = "Device removed successfully";
        }

        $response["status"] = true;
        $response["code"] = 200;

        return response()->json($response, 200);
    }

    public function block_or_unblock_user(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $state = !empty($request->state) ? $request->state : "";

        if (!$state) {
            return $this->respondResult("", 'State is required', false, 200);
        }

        $sender_id = Auth::user()->u_id;
        $reciever_id = !empty($request->user_id) ? $request->user_id : null;

        $friend_invite = UserBlocks::where("user_block_user_id", $sender_id)
            ->where("user_block_blocked_user_id", $reciever_id)
            ->first();

        $message = "User not found in your block list";
        if (empty($friend_invite) && $state == 'Block') {
            $message = "You've blocked user successfully";
            $friend_invite = new UserBlocks();
            $friend_invite->user_block_user_id = $sender_id;
            $friend_invite->user_block_blocked_user_id = $reciever_id;
            $friend_invite->save();
        } else if (!empty($friend_invite) && $state == 'Block') {
            $message = "You've already blocked this user";
        } else if (!empty($friend_invite) && $state == 'Unblock') {
            $message = "You've unblocked user successfully";
            $friend_invite->delete();
            $friend_invite->forceDelete();
        }

        $response["message"] = $message;
        $response["status"] = true;
        $response["code"] = 200;

        return response()->json($response, 200);
    }

    public function get_blocked_users(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }
        $id = Auth::user()->u_id;

        $fetch_record = UserBlocks::select('u_id', \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"), 'u_image')
            ->where("user_block_user_id", $user->u_id)
            ->join('users', 'user_block_blocked_user_id', 'u_id')
            ->where('user_block_status', 1)
            ->where('u_status', 1)
            ->get();


        $fetch_record_list = array();
        $response = array();
        if (count($fetch_record) > 0) {
            foreach ($fetch_record as $value) {
                $invited_user_id = $value->u_id;
                $block_list = [
                    'u_id' => !empty($value->u_id) ? trim($value->u_id) : "",
                    'user_name' => !empty($value->user_name) ? trim($value->user_name) : "",
                    'u_image' => !empty($value->u_image) ? trim($value->u_image) : "",
                    'total_mutual_friends' => $this->userModel->find_mutual_friends($id, $invited_user_id),
                    'total_pets' => Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count()
                ];

                $fetch_record_list[] = $block_list;
            }
            $message = 'Blocked User found successfully.';
        } else {
            $message = "No data found.";
        }

        $response["result"] = $fetch_record_list;
        $response["message"] = $message;
        $response["status"] = true;

        return response()->json($response, 200);
    }

    /**
     * Invite Friends on App
     *
     * @param Request $request
     * @return type
     */
    public function invite_friend(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $id = $user->u_id;

        $request->merge([
            'email' => AESCrypt::decryptString($request->email),
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);

        $rules = [
            'email' => ['required', 'email'],
        ];

        $customMessages = [
            'email.required' => "Email is required field.",
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            return $this->respondWithError(AESCrypt::encryptString($validator->errors()->first()));
        }

        if ($user->u_email == $request->email) {
            return $this->respondResult("", AESCrypt::encryptString('You cannot send invitation to yourself.'), false, 200);
        }

        $req_email = $request->email;

        $invites = 'SELECT
                        ropes_user_friends.*
                    FROM
                        ropes_user_friends
                    LEFT JOIN
                        ropes_users
                    ON
                        `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                    WHERE
                        (
                            ((`ufr_user_id` = ' . $id . ' OR `ufr_invited_user_id` = ' . $id . ') AND `u_email` = "' . $req_email . '")
                        OR
                            ((`ufr_user_id` = ' . $id . ' OR `ufr_invited_user_id` = ' . $id . ') AND `ufr_email` = "' . $req_email . '")
                        )
                    AND `u_status` != 9
                    AND `ufr_status` != 9
                    AND `ufr_deleted_at` IS NULL
                    AND `ropes_users`.`u_deleted_at` IS NULL
                    LIMIT 1';

        Log::channel('userlog')->info("Invite Friend query FIRST =" . $invites);

        $check_friend = \DB::select($invites);

        if (!empty($check_friend) && count($check_friend) > 0) {
            return $this->respondResult("", AESCrypt::encryptString("You've already invited " . $request->email), false, 200);
        }

        $random_invitation_token = str_rand_access_token(64);
        $response_message = AESCrypt::encryptString("Friend invitation sent successfully.");
        $check_is_active_user = User::where('u_email', $request->email)->where("u_status", 1)->first();

        if (!empty($check_is_active_user)) {
            $add_new_request = array(
                "ufr_user_id" => $id,
                "ufr_invited_user_id" => $check_is_active_user->u_id,
                "ufr_email" => $request->email,
                "ufr_token" => $random_invitation_token,
                "ufr_status" => 2
            );

            UserFriends::create($add_new_request);

            $n_message = ucwords($user->u_first_name) . " has sent you friend request.";
            $this->send_friend_invite_push($check_is_active_user->u_id, $id, $n_message, 2, 2, $user);
        } else {
            $add_new_request = array(
                "ufr_user_id" => $id,
                "ufr_email" => $request->email,
                "ufr_token" => $random_invitation_token,
                "ufr_status" => 2
            );

            UserFriends::create($add_new_request);

            $user_email = $request->email;
            try {
                $email = array($user_email);
                $data = array(
                    'invitation_from' => ucwords($user->u_first_name),
                    'invitation_token' => $random_invitation_token,
                );

                Mail::send('emails.invite_friend', $data, function ($message) use ($email) {
                    $message->from(env("MAIL_USERNAME"), config('app.name'));
                    $message->to($email);
                    $message->subject(config('app.name') . " : Friend invitation mail");
                });
            } catch (\Exception $e) {
                return $this->respondWithError(AESCrypt::encryptString("Failed: Problem while sending friend request, try again!"));
            }
        }

        $response["message"] = $response_message;
        $response["status"] = true;
        $response["code"] = 200;

        return response()->json($response, 200);
    }

    /**
     * Get my friend list
     *
     * @param Request $request
     * @return type
     */
    public function my_friends(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $id = Auth::user()->u_id;

        $request->merge([
            'search_text' => AESCrypt::decryptString($request->search_text),
            'page' => !empty($request->page) ? AESCrypt::decryptString($request->page) : 1,
            'limit' => AESCrypt::decryptString($request->limit),
        ]);



        $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
        $page = !empty($request->page) ? $request->page : 1;
        $offset = ($page - 1) * $limit;


        $get_all_records_user = User::select("u_id", "u_first_name", "u_email", "u_image", "city_name", "c_name", "ufr_status")
            ->leftJoin('user_friends', function ($join) {
                $join->on('u_id', '=', 'ufr_invited_user_id')
                    ->orOn('u_id', '=', 'ufr_user_id');
            })
            ->leftJoin('cities', 'city_id', '=', 'u_city')
            ->leftJoin('countries', 'c_id', '=', 'city_country_id')
            ->where(function ($query) use ($id) {
                $query->where('ufr_user_id', $id)
                    ->orWhere('ufr_invited_user_id', $id);
            })
            ->where('ufr_status', "!=", 9)
            ->where('u_id', "!=", $id)
            ->get();


        $get_all_records_email = UserFriends::select("*")->where('ufr_user_id', $id)->where('ufr_status', "!=", 9)->where('ufr_invited_user_id', NULL)->get();

        $get_all_records = new \Illuminate\Support\Collection($get_all_records_user);

        $get_all_records = $get_all_records->merge($get_all_records_email);


        //        dd($get_all_records);
        //        $get_all_records = $this->userModel->friends($id);
        //        if (!empty($request->search_text)) {
        //            $search_keyword = $request->search_text;
        //            $get_all_records = $get_all_records->where(function ($query) use ($search_keyword) {
        //                $query->where('u_first_name', 'like', '%' . $search_keyword . '%');
        //            });
        //        }
        //        $get_all_records = $get_all_records->paginate($limit);
        //        dd(\DB::getQueryLog());
        //        $pagination_data = [
        //            'total' => $get_all_records->total(),
        //            'lastPage' => $get_all_records->lastPage(),
        //            'perPage' => $get_all_records->perPage(),
        //            'currentPage' => $get_all_records->currentPage(),
        //        ];

        $response_data = array();
        if (!empty($get_all_records) && $get_all_records->count() > 0) {
            foreach ($get_all_records as $friend_invite) {

                $invite_user_image = "";
                if (!empty($friend_invite->u_image) && strpos(getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $friend_invite->u_id, $friend_invite->u_image), "uploads") !== false) {
                    $invite_user_image = str_replace(url('/public/uploads/') . "/", "", getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $friend_invite->u_id, $friend_invite->u_image));
                }

                $response_data[] = array(
                    'user_id' => AESCrypt::encryptString($friend_invite->u_id),
                    'user_name' => AESCrypt::encryptString($friend_invite->u_first_name),
                    'user_email' => AESCrypt::encryptString($friend_invite->ufr_email),
                    'user_city' => AESCrypt::encryptString(!empty($friend_invite->city_name) ? $friend_invite->city_name : ""),
                    'user_country' => AESCrypt::encryptString(!empty($friend_invite->c_name) ? $friend_invite->c_name : ""),
                    'user_image' => AESCrypt::encryptString($invite_user_image),
                    'user_invitation_status' => AESCrypt::encryptString($friend_invite->ufr_status),
                );
            }
            $message = AESCrypt::encryptString("Friend list found successfully.");
            $status = true;
        } else {
            $message = AESCrypt::encryptString("No friend data found.");
            $status = false;
        }

        $response["pagination"] = array();
        $response["result"] = $response_data;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

    /**
     * Get user activity
     *
     * @param Request $request
     * @return type
     */
    public function get_user_activity(Request $request)
    {
        $request->merge([
            'u_id' => AESCrypt::decryptString($request->u_id),
            'device_type' => AESCrypt::decryptString($request->device_type),
            'page' => AESCrypt::decryptString($request->page),
            'limit' => AESCrypt::decryptString($request->limit)
        ]);

        $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
        $page = !empty($request->page) ? $request->page : 1;
        $offset = ($page - 1) * $limit;

        $user_id = !empty($request->u_id) ? $request->u_id : "";

        $user = $this->userModel->validateUser($user_id);
        if (!$user) {
            return $this->respondResult("", AESCrypt::encryptString('User Not Found'), false, 200);
        }

        $user_activity_sql = "SELECT
                                '1' as activity_type,
                                `tour_id`,
                                `tour_name`,
                                `tour_user_id`,
                                `tour_city_id`,
                                `tour_category_id`,
                                `tour_cover_image`,
                                ropes_categories.c_name as category_name,
                                city_name,
                                ropes_countries.c_name as country_name,
                                u_first_name,
                                u_image,
                                '' as rating_star,
                                '' as rating_comment,
                                ua_created_at as created_date
                            FROM
                                ropes_user_activities
                            LEFT JOIN
                                ropes_tours
                            ON
                                ropes_user_activities.ua_activity_id=ropes_tours.tour_id
                            LEFT JOIN
                                ropes_users
                            ON
                                ropes_users.u_id=ropes_tours.tour_user_id
                            LEFT JOIN
                                ropes_categories
                            ON
                                ropes_categories.c_id=ropes_tours.tour_category_id
                            LEFT JOIN
                                ropes_cities
                            ON
                                ropes_cities.city_id=ropes_tours.tour_city_id
                            LEFT JOIN
                                ropes_countries
                            ON
                                ropes_countries.c_id=ropes_cities.city_country_id
                            WHERE
                                ua_u_id=" . $user_id . " AND ua_status=1
                            GROUP BY
                                ua_id
                            UNION ALL
                            SELECT
                                '2' as activity_type,
                                `tour_id`,
                                `tour_name`,
                                `tour_user_id`,
                                `tour_city_id`,
                                `tour_category_id`,
                                `tour_cover_image`,
                                ropes_categories.c_name as category_name,
                                city_name,
                                ropes_countries.c_name as country_name,
                                u_first_name,
                                u_image,
                                tr_rating as rating_star,
                                tr_comment as rating_comment,
                                tr_created_at as created_date
                            FROM
                                ropes_tour_ratings
                            LEFT JOIN
                                ropes_tours
                            ON
                                ropes_tour_ratings.tr_tour_id=ropes_tours.tour_id
                            LEFT JOIN
                                ropes_users
                            ON
                                ropes_users.u_id=ropes_tours.tour_user_id
                            LEFT JOIN
                                ropes_categories
                            ON
                                ropes_categories.c_id=ropes_tours.tour_category_id
                            LEFT JOIN
                                ropes_cities
                            ON
                                ropes_cities.city_id=ropes_tours.tour_city_id
                            LEFT JOIN
                                ropes_countries
                            ON
                                ropes_countries.c_id=ropes_cities.city_country_id
                            WHERE
                                tr_user_id=" . $user_id . " AND tr_status=1
                            GROUP BY
                                tr_id";

        $user_activity_count = \DB::select($user_activity_sql);

        $total_pages = count($user_activity_count);

        $totalPages = ceil($total_pages / $limit);

        $response_data = array();
        if (!empty($total_pages)) {
            $user_activity_sql .= " LIMIT " . $offset . ", " . $limit . " ";

            $user_activity = \DB::select($user_activity_sql);

            if (!empty($user_activity)) {
                foreach ($user_activity as $key => $value) {
                    $user_image = "";
                    if (!empty($value->tour_user_id) && !empty($value->u_image) && strpos(getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $value->tour_user_id, $value->u_image), "uploads") !== false) {
                        $user_image = str_replace(url('/public/uploads/') . "/", "", getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $value->tour_user_id, $value->u_image));
                    }

                    $cover_image = "";
                    if (!empty($value->tour_id) && !empty($value->tour_cover_image) && strpos(getPhotoURL(config('constants.UPLOAD_TOURS_FOLDER'), $value->tour_id, $value->tour_cover_image), "uploads") !== false) {
                        $cover_image = str_replace(url('/public/uploads/') . "/", "", getPhotoURL(config('constants.UPLOAD_TOURS_FOLDER'), $value->tour_id, $value->tour_cover_image));
                    }

                    $response_data[] = [
                        'activity_id' => AESCrypt::encryptString($key + 1),
                        'activity_type' => AESCrypt::encryptString($value->activity_type),
                        'tour_id' => AESCrypt::encryptString($value->tour_id),
                        'tour_name' => AESCrypt::encryptString($value->tour_name),
                        'tour_user_id' => AESCrypt::encryptString($value->tour_user_id),
                        'tour_city_id' => AESCrypt::encryptString($value->tour_city_id),
                        'tour_category_id' => AESCrypt::encryptString($value->tour_category_id),
                        'tour_cover_image' => AESCrypt::encryptString($cover_image),
                        'category_name' => AESCrypt::encryptString($value->category_name),
                        'city_name' => AESCrypt::encryptString($value->city_name),
                        'country_name' => AESCrypt::encryptString($value->country_name),
                        'user_name' => AESCrypt::encryptString($value->u_first_name),
                        'user_image' => AESCrypt::encryptString($user_image),
                        'rating_star' => AESCrypt::encryptString($value->rating_star),
                        'rating_comment' => AESCrypt::encryptString($value->rating_comment),
                        'created_date' => AESCrypt::encryptString($value->created_date)
                    ];
                }

                $message = AESCrypt::encryptString("Activity list found successfully.");
                $status = true;
            } else {
                $message = AESCrypt::encryptString("No activity data found.");
                $status = false;
            }
        } else {
            $message = AESCrypt::encryptString("No activity data found.");
            $status = false;
        }


        $pagination_data = [
            'total' => $total_pages,
            'total_page' => $totalPages,
            'perPage' => $limit,
            'currentPage' => $page,
        ];

        $response["pagination"] = $pagination_data;
        $response["result"] = $response_data;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

    /**
     *
     * @param type $n_reciever_id
     * @param type $n_sender_id
     * @param type $n_message
     * @param type $n_notification_type
     * @param type $n_status
     * @param type $tour
     */
    private function send_friend_invite_push($n_reciever_id, $n_sender_id, $n_message, $n_notification_type, $n_status, $user)
    {
        if (!empty($n_reciever_id) && !empty($n_sender_id) && !empty($n_message) && !empty($n_notification_type) && !empty($n_status) && !empty($user)) {
            $notification_data = new Notification();
            $notification_data->n_reciever_id = $n_reciever_id;
            $notification_data->n_sender_id = $n_sender_id;
            $notification_data->n_params = json_encode(["u_id" => $user->u_id]);
            $notification_data->n_message = $n_message;
            $notification_data->n_notification_type = $n_notification_type;
            $notification_data->n_status = $n_status;
            $notification_data->n_created_at = Carbon::now();
            if ($notification_data->save()) {
                $process = new \Symfony\Component\Process\Process("php artisan send_friend_invite_push $notification_data->n_id >>/dev/null 2>&1");
                $process->start();
            }
        }
    }

    public function friend_request_action(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $id = $user->u_id;

        $request->merge([
            'n_id' => AESCrypt::decryptString($request->n_id),
            'state' => AESCrypt::decryptString($request->state),
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);

        $n_id = !empty($request->n_id) ? $request->n_id : '';
        $notification = Notification::find($n_id);


        if (empty($notification)) {
            return $this->respondResult("", AESCrypt::encryptString('Data Not Found'), false, 200);
        }

        $state = !empty($request->state) && $request->state == 1 ? $request->state : 2;

        $friend_invite = UserFriends::where("ufr_user_id", $notification->n_sender_id)
            ->where("ufr_invited_user_id", $notification->n_reciever_id)->first();

        $message = "";
        if (!empty($friend_invite) && $state == 1) {
            $message = AESCrypt::encryptString("You've accepted friend request.");
            $friend_invite->ufr_status = 1;
            $friend_invite->update();
        } else if (!empty($friend_invite) && $state == 2) {
            $message = AESCrypt::encryptString("You've cancelled friend request.");
            $friend_invite->ufr_status = 9;
            $friend_invite->update();
            $friend_invite->delete();
            $friend_invite->forceDelete();
        }

        $notification->n_status = 9;
        $notification->update();
        $notification->delete();
        $notification->forceDelete();

        $response["message"] = $message;
        $response["status"] = true;
        $response["code"] = 200;

        return response()->json($response, 200);
    }

    public function get_user_and_group_details(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $login_user_id = Auth::user()->u_id;

        $user_list = [];
        if (!empty($request->userIds) && is_array($request->userIds) && count($request->userIds) > 0) {
            foreach ($request->userIds as $id) {
                $user = $this->userModel->validateUser($id);
                if (!empty($user)) {
                    $userdetail_data = $this->get_userdata($user, $login_user_id);
                    $user_list[] = (array) $userdetail_data;
                }
            }
        }

        $group_list = [];
        if (!empty($request->groupIds) && is_array($request->groupIds) && count($request->groupIds) > 0) {
            foreach ($request->groupIds as $group_id) {
                $find_record = Groups::find($group_id);

                if (!empty($find_record)) {

                    $value = Groups::with(['addedBy', 'group_members', 'group_members.member'])
                        ->select("*")
                        ->where('group_id', $group_id)
                        ->first();

                    $fetch_record_list = array();
                    $response = array();
                    if (!empty($value)) {
                        foreach ($value->group_members as &$group_value) {
                            $group_value->member->total_pets = Pets::where('pet_owner_id', $group_value->member->u_id)->where('pet_status', 1)->count();
                            $group_value->member->has_total_friends_count = $this->userModel->has_total_friends_count($group_value->member->u_id);
                        }

                        $get_group_events = Events::join('event_groups', 'eg_event_id', 'event_id')
                            ->select('events.*')
                            ->where('eg_group_id', $group_id)
                            ->where('event_status', 1)
                            ->whereNull('event_deleted_at')
                            ->orderBy('event_start_date', 'ASC')
                            ->first();

                        $value->events = !empty($get_group_events) ? $get_group_events : array();

                        $group_list[] = $value;
                    }
                }
            }
        }

        $cor_list = [];
        if (!empty($request->corIds) && is_array($request->corIds) && count($request->corIds) > 0) {
            foreach ($request->corIds as $id) {

                $user = BusinessUser::find($id);
                if (!empty($user)) {
                    // $userdetail_data = $this->get_userdata($user, $login_user_id);
                    $cor_list[] = $user;
                }
            }
        }
        $response['userList'] = $user_list;
        $response['groupList'] = $group_list;
        $response['corList'] = $cor_list;


        return $this->respondResult($response, '', true, 200);
    }

    public function store_call_history(Request $request)
    {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'call_from_user_id' => ['required'],
                'call_to_user_id' => ['required'],
                'call_duration' => ['required'],
                'call_datetime' => ['required'],
                'call_status' => ['required'],
            ];

            $customMessages = [
                'call_from_user_id.required' => 'Call from ID is required field.',
                'call_to_user_id.required' => 'Call to ID is required field.',
                'call_duration.required' => 'Call duration is required field.',
                'call_datetime.required' => 'Call date time is required field.',
                'call_status.required' => 'Call status is required field.',
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $record = new UserCallHistory();
            $record->call_from_user_id = $request->call_from_user_id;
            $record->call_to_user_id = $request->call_to_user_id;
            $record->call_duration = $request->call_duration;
            $record->call_datetime = $request->call_datetime;
            $record->call_history_status = (int) $request->call_status;
            $record->call_history_created_at = \Carbon\Carbon::now();
            $record->save();

            if (!empty($record)) {
                $message = "Call history saved successfully";
            } else {
                $message = "Failed to save call ";
            }

            $response["result"] = $record;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_call_history(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $id = Auth::user()->u_id;

        $fetch_record = User::select("u_id", "u_email", "u_image", "call_history_id", "call_from_user_id", "call_to_user_id", "call_duration", "call_history_status", "call_datetime", \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"))
            ->leftJoin('user_call_histories', function ($join) {
                $join->on('u_id', '=', 'call_to_user_id')
                    ->orOn('u_id', '=', 'call_from_user_id');
            })
            ->where(function ($query) use ($id) {
                $query->where('call_to_user_id', $id)
                    ->orWhere('call_from_user_id', $id);
            })
            ->where('call_history_status', "!=", 9)
            ->where('u_id', "!=", $id)
            ->orderBy('call_datetime', 'DESC')
            ->get();

        $fetch_record_list = array();
        $response = array();
        if (count($fetch_record) > 0) {

            $user_ids = [];
            foreach ($fetch_record as $value) {
                $invited_user_id = $value->u_id;
                if (!in_array($invited_user_id, $user_ids)) {
                    $block_list = [
                        'u_id' => !empty($value->u_id) ? trim($value->u_id) : "",
                        'user_name' => !empty($value->user_name) ? trim($value->user_name) : "",
                        'u_image' => !empty($value->u_image) ? trim($value->u_image) : "",
                        'call_to_user_id' => !empty($value->call_to_user_id) ? trim($value->call_to_user_id) : "",
                        'call_from_user_id' => !empty($value->call_from_user_id) ? trim($value->call_from_user_id) : "",
                        'call_duration' => !empty($value->call_duration) ? trim($value->call_duration) : "",
                        'call_datetime' => !empty($value->call_datetime) ? date("F d, h:i a", strtotime($value->call_datetime)) : "",
                        'call_history_status' => !empty($value->call_history_status) ? trim($value->call_history_status) : "",
                    ];

                    $fetch_record_list[] = $block_list;
                    $user_ids[] = $invited_user_id;
                }
            }
            $message = 'Call History found successfully.';
        } else {
            $message = "No data found.";
        }

        $response["result"] = $fetch_record_list;
        $response["message"] = $message;
        $response["status"] = true;

        return response()->json($response, 200);
    }

    public function get_call_history_by_user_id(Request $request)
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        if (empty($request->other_user_id)) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }
        $other_user_id = (int) $request->other_user_id;
        $other_user = $this->userModel->validateUser($other_user_id);

        if (!$other_user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $user_response = [
            'u_id' => $other_user_id,
            'u_first_name' => $other_user->u_first_name,
            'u_last_name' => $other_user->u_last_name,
            'u_image' => $other_user->u_image,
        ];

        $id = Auth::user()->u_id;
        if (!empty($request->auth_user_id)) {
            $id = (int) $request->auth_user_id;
        }

        //\DB::enableQueryLog();
        $fetch_record = UserCallHistory::select("*")
            ->where(function ($query) use ($id, $other_user_id) {
                $query->where('call_to_user_id', $id)
                    ->where('call_from_user_id', $other_user_id);
            })
            ->orWhere(function ($query) use ($id, $other_user_id) {
                $query->where('call_to_user_id', $other_user_id)
                    ->where('call_from_user_id', $id);
            })
            ->where('call_history_status', "!=", 9)
            ->orderBy('call_datetime', 'DESC')
            ->get();

        $fetch_record_list = array();
        $response = array();
        if (count($fetch_record) > 0) {
            foreach ($fetch_record as $value) {
                $block_list = [
                    'call_history_id' => !empty($value->call_history_id) ? trim($value->call_history_id) : "",
                    'call_from_user_id' => !empty($value->call_from_user_id) ? trim($value->call_from_user_id) : "",
                    'call_to_user_id' => !empty($value->call_to_user_id) ? trim($value->call_to_user_id) : "",
                    'call_duration' => !empty($value->call_duration) ? trim($value->call_duration) : "",
                    'call_datetime' => !empty($value->call_datetime) ? date("F d, h:i a", strtotime($value->call_datetime)) : "",
                    'call_history_status' => !empty($value->call_history_status) ? trim($value->call_history_status) : "",
                ];

                $fetch_record_list[] = $block_list;
            }
            $message = 'Call History found successfully.';
        } else {
            $message = "No data found.";
        }

        $response_data = [
            'user' => $user_response,
            'history' => $fetch_record_list
        ];

        $response["result"] = $response_data;
        $response["message"] = $message;
        $response["status"] = true;

        return response()->json($response, 200);
    }

    public function  get_follwer_list()
    {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $follwerUser = CorporateFollwer::where('u_id', $user['u_id'])->with('coUser')->get();
        if (count($follwerUser) > 0) {
            $message = 'Bussine User Details.';
        } else {
            $message = "No data found.";
        }
        $response["result"] = $follwerUser;
        $response["message"] = $message;
        $response["status"] = true;

        return response()->json($response, 200);
    }
}
