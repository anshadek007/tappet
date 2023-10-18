<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Pets;
use App\PetImages;
use App\PetTypes;
use App\PetBreeds;
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
use App\Traits\ConversationIdGenerator;
use App\Notification;
use App\UserDeviceToken;


class PetsController extends APIController {
    use ConversationIdGenerator;
    protected $userModel;
    protected $week_list;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\User();
        $this->week_list = weekList();
    }

    public function get_pet_types(Request $request) {
        try {
            $fetch_record = PetTypes::select("pt_id", "pt_name", "pt_image")->where('pt_status', 1);
            $fetch_record = $fetch_record->orderBy("pt_id", "ASC")->get();

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as $record) {
                    $fetch_record_list[] = array(
                        "pt_id" => $record->pt_id,
                        "pt_name" => $record->pt_name,
                        "pt_image" => $record->pt_image,
                    );
                }

                $message = "Pet types found successfully.";
                $status = true;
            } else {
                $message = "No data found.";
                $status = false;
            }

            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = $status;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_pet_breeds(Request $request) {
        try {
            $fetch_record = PetBreeds::select("pb_id", "pb_name")->where('pb_status', 1);
            $fetch_record = $fetch_record->orderBy("pb_name", "ASC")->get();

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as $record) {
                    $fetch_record_list[] = array(
                        "pb_id" => $record->pb_id,
                        "pb_name" => $record->pb_name,
                    );
                }

                $message = "Pet breed found successfully.";
                $status = true;
            } else {
                $message = "No data found.";
                $status = false;
            }

            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = $status;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_pet_co_owners(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'pet_id' => ['required'],
            ];


            $customMessages = [
                'pet_id.required' => "Pet ID is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $fetch_record = PetCoOwners::select("*")
                    ->join('users', 'pet_co_owner_owner_id', 'u_id')
                    ->where('pet_co_owner_pet_id', $request->pet_id)
                    ->where('pet_co_owner_status', 1)
                    ->get();

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as $record) {

                    $invites = 'SELECT
                        tappet_user_friends.*
                    FROM
                        tappet_user_friends
                    LEFT JOIN
                        tappet_users
                    ON
                        `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                    WHERE
                        (`ufr_user_id` = ' . $user->u_id . ' AND `ufr_invited_user_id` = ' . $record->pet_co_owner_owner_id . ')
                        OR
                        (`ufr_user_id` = ' . $record->pet_co_owner_owner_id . ' AND `ufr_invited_user_id` = ' . $user->u_id . ')
                    AND `u_status` != 9
                    AND `ufr_status` != 9
                    AND `ufr_deleted_at` IS NULL
                    AND `tappet_users`.`u_deleted_at` IS NULL
                    LIMIT 1';

                    $check_friend = \DB::select($invites);

                    $friend_request = false;

                    if (!empty($check_friend) && count($check_friend) > 0) {
                        $friend_request = !empty($check_friend[0]->ufr_status) && $check_friend[0]->ufr_status == 1 ? true : false;
                    }

                    $fetch_record_list[] = array(
                        "u_first_name" => $record->u_first_name,
                        "u_last_name" => $record->u_last_name,
                        "u_image" => $record->u_image,
                        "pet_co_owner_owner_id" => $record->pet_co_owner_owner_id,
                        "pet_co_owner_pet_id" => $record->pet_co_owner_pet_id,
                        "total_friends" => $this->userModel->friends($record->pet_co_owner_owner_id)->count(),
                        "total_pets" => Pets::where('pet_owner_id', $record->pet_co_owner_owner_id)->where('pet_status', 1)->count(),
                        "is_friend" => $friend_request
                    );
                }

                $message = "Pet Co-Owner found successfully.";
                $status = true;
            } else {
                $message = "No data found.";
                $status = false;
            }

            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = $status;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_pet(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $request->merge([
                'pet_type_id' => $request->pet_type_id,
                'pet_name' => $request->pet_name,
                'pet_gender' => $request->pet_gender,
                'pet_dob' => $request->pet_dob,
            ]);

            $rules = [
                'pet_type_id' => ['required'],
                'pet_name' => ['required', 'max:100'],
                'pet_gender' => ['required'],
                'pet_dob' => ['required'],
            ];

            if (!empty($request->file('pet_image'))) {
                $rules['pet_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
            }

            $customMessages = [
                'pet_type_id.required' => "Pet Type is required",
                'pet_name.required' => "Pet Name is required",
                'pet_name.max' => "Pet Name allows maximum 100 characters only.",
                'pet_gender.required' => "Pet Gender is required",
                'pet_dob.required' => "Pet Date Of Birth is required",
                'pet_image.image' => 'The type of the uploaded file should be an image.',
                'pet_image.mimes' => 'The type of the uploaded file should be an image.',
                'pet_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = new Pets();
            $find_record->pet_type_id = $request->pet_type_id;
            $find_record->pet_owner_id = Auth::user()->u_id;
            $find_record->pet_name = $request->pet_name;
            $find_record->pet_gender = $request->pet_gender;
            $find_record->pet_dob = $request->pet_dob;

            if (!empty($request->pet_note)) {
                $find_record->pet_note = $request->pet_note;
            }

            if (!empty($request->pet_size)) {
                $find_record->pet_size = $request->pet_size;
            }

            if (!empty($request->pet_is_friendly)) {
                $find_record->pet_is_friendly = $request->pet_is_friendly;
            }

            if (!empty($request->pet_age)) {
                $find_record->pet_age = $request->pet_age;
            }

            if (!empty($request->pet_breed_ids)) {
                $find_record->pet_breed_ids = $request->pet_breed_ids;
            }

            if (!empty($request->pet_breed_percentage)) {
                $find_record->pet_breed_percentage = $request->pet_breed_percentage;
            }

            $find_record->pet_created_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('pet_image'))) {
                    $fileName = $this->uploadFile($request->file('pet_image'), $find_record->pet_id, config('constants.UPLOAD_PETS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload pet image, Try again..!");
                    }
                    $find_record->pet_image = $fileName;
                    $find_record->save();
                }

                return $this->respondResult("", "Pet saved successfully");
            } else {
                return $this->respondResult("", 'Failed to save Pet details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function edit_pet(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $request->merge([
                'pet_id' => $request->pet_id,
                'pet_type_id' => $request->pet_type_id,
                'pet_name' => $request->pet_name,
                'pet_gender' => $request->pet_gender,
                'pet_dob' => $request->pet_dob,
            ]);

            $rules = [
                'pet_id' => ['required'],
                'pet_type_id' => ['required'],
                'pet_name' => ['required', 'max:100'],
                'pet_gender' => ['required'],
                'pet_dob' => ['required'],
            ];

            if (!empty($request->file('pet_image'))) {
                $rules['pet_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
            }

            $customMessages = [
                'pet_id.required' => "Pet ID is required",
                'pet_type_id.required' => "Pet Type is required",
                'pet_name.required' => "Pet Name is required",
                'pet_name.max' => "Pet Name allows maximum 100 characters only.",
                'pet_gender.required' => "Pet Gender is required",
                'pet_dob.required' => "Pet Date Of Birth is required",
                'pet_image.image' => 'The type of the uploaded file should be an image.',
                'pet_image.mimes' => 'The type of the uploaded file should be an image.',
                'pet_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            if (!empty($request->pet_note)) {
                $find_record->pet_note = $request->pet_note;
            }

            if (!empty($request->pet_type_id)) {
                $find_record->pet_type_id = $request->pet_type_id;
            }

            if (!empty($request->pet_size)) {
                $find_record->pet_size = $request->pet_size;
            }

            if (!empty($request->pet_is_friendly)) {
                $find_record->pet_is_friendly = $request->pet_is_friendly;
            }

            if (!empty($request->pet_name)) {
                $find_record->pet_name = $request->pet_name;
            }

            if (!empty($request->pet_gender)) {
                $find_record->pet_gender = $request->pet_gender;
            }

            if (!empty($request->pet_dob)) {
                $find_record->pet_dob = $request->pet_dob;
            }

            if (!empty($request->pet_age)) {
                $find_record->pet_age = $request->pet_age;
            }

            if (!empty($request->pet_breed_ids)) {
                $find_record->pet_breed_ids = $request->pet_breed_ids;
            }

            if (!empty($request->pet_breed_percentage)) {
                $find_record->pet_breed_percentage = $request->pet_breed_percentage;
            }

            $find_record->pet_updated_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('pet_image'))) {
                    $fileName = $this->uploadFile($request->file('pet_image'), $find_record->pet_id, config('constants.UPLOAD_PETS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload pet image, Try again..!");
                    }
                    $find_record->pet_image = $fileName;
                    $find_record->save();
                }

                return $this->respondResult("", "Pet details updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Pet details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_pet_co_owner(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'pet_id' => ['required'],
                'user_id' => ['required'],
                'status' => ['required'],
            ];


            $customMessages = [
                'pet_id.required' => "Pet ID is required",
                'user_id.required' => "User ID is required",
                'status.required' => "Status is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $co_user = $this->userModel->validateUser($request->user_id);
            if (!$co_user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }
            $token_data = UserDeviceToken::where('udt_u_id',$co_user->u_id)->first();

            if (!empty($find_record)) {
                $status = !empty($request->status) ? $request->status : 1;

                $pet_co_owner = PetCoOwners::where('pet_co_owner_pet_id', $request->pet_id)
                        ->where('pet_co_owner_owner_id', $request->user_id)
                        ->first();

                $message = 'Failed to perform action, Please try again!!';
                if (!empty($pet_co_owner) && $status == 1) {
                    $message = 'Co-Owner already added';
                } else if (!empty($pet_co_owner) && $status == 2) {
                    $pet_co_owner->delete();
                    $pet_co_owner->forceDelete();

                    //notification
                    $badge_count = Notification::where("n_reciever_id", $user->u_id)->where('n_status', 3)->count();
                    $push_title = 'Co-Owner removed';
                    $push_message = 'Co-Owner removed successfully.';
                    
                    if ($token_data->udt_device_type == "ios") {
                        $ios_users = $token_data->udt_device_token;
                        $message = array(
                            'content_available' => true,
                            'priority' => 'high',
                            'to' => $ios_users,
                            'notification' => array(
                                'title' => $push_title,
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "sender_user_id" => (int) $user->u_id,
                                "user_id" => (int) $co_user->u_id,
                                //"group_id" => (int) $group_id,
                                'type' => 7
                            )
                        );
                        //
                        $url = 'https://fcm.googleapis.com/fcm/send';

                        $key = env("PUSH_ANDROID_KEY");
                        $headers = array(
                            'Authorization:key=' . $key,
                            'Content-Type:application/json'
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
                        $result = curl_exec($ch);
                        return $this->respondResult("", $result);

                        //$this->send_push_notification($message);
                    } else {
                        $android_users = $token_data->udt_device_token;
                        $message = array(
                            'priority' => 'high',
                            'to' => $android_users,
                            'notification' => array(
                                'title' => $push_title,
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "sender_user_id" => (int) $user->u_id,
                                "user_id" => (int) $co_user->u_id,
                                //"group_id" => (int) $group_id,
                                'type' => 7
                            )
                        );
                        $this->send_notification_for_add($message);
                    }
                    $message = 'Co-Owner removed successfully';
                } else if (empty($pet_co_owner) && $status == 1) {
                    $new_obj = new PetCoOwners();
                    $new_obj->pet_co_owner_pet_id = $find_record->pet_id;
                    $new_obj->pet_co_owner_owner_id = $request->user_id;
                    $new_obj->save();
                    //notification
                    $badge_count = Notification::where("n_reciever_id", $user->u_id)->where('n_status', 3)->count();
                    $push_title = 'Co-Owner added';
                    $push_message = 'Co-Owner added successfully.';

                    if ($co_user->udt_device_type == "ios") {
                        $ios_users = $co_user->udt_device_token;
                        $message = array(
                            'content_available' => true,
                            'priority' => 'high',
                            'to' => $ios_users,
                            'notification' => array(
                                'title' => $push_title,
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "sender_user_id" => (int) $user->u_id,
                                "user_id" => (int) $co_user->u_id,
                                //"group_id" => (int) $group_id,
                                'type' => 7
                            )
                        );
                        $this->send_push_notification($message);
                    } else {
                        $android_users = $token_data->udt_device_token;
                        $message = array(
                            'priority' => 'high',
                            'to' => $android_users,
                            'notification' => array(
                                'title' => $push_title,
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "sender_user_id" => (int) $user->u_id,
                                "user_id" => (int) $co_user->u_id,
                                //"group_id" => (int) $group_id,
                                'type' => 7
                            )
                        );
                        $this->send_notification_for_add($message);
                    }
                    $message = 'Co-Owner added successfully';
                }

                return $this->respondResult("", $message);
            } else {
                return $this->respondResult("", 'Failed to perform action, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_collar(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'pet_id' => ['required'],
                'collar_device_id' => ['required'],
                'status' => ['required'],
            ];


            $customMessages = [
                'pet_id.required' => "Pet ID is required",
                'collar_device_id.required' => "Callar ID is required",
                'status.required' => "Status is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            if (!empty($find_record)) {
                $status = !empty($request->status) ? $request->status : 1;

                $pet_collar = PetCollars::where('pet_collar_pet_id', $request->pet_id)
                        ->where('pet_collar_device_id', $request->collar_device_id)
                        ->first();

                $message = 'Failed to perform action, Please try again!!';
                if (!empty($pet_collar) && $status == 1) {
                    $message = 'Collar already added';
                } else if (!empty($pet_collar) && $status == 2) {
                    $pet_collar->delete();
                    $pet_collar->forceDelete();

                    $message = 'Collar removed successfully';
                } else if (empty($pet_collar) && $status == 1) {
                    $new_obj = new PetCollars();
                    $new_obj->pet_collar_pet_id = $find_record->pet_id;
                    $new_obj->pet_collar_device_id = $request->collar_device_id;
                    $new_obj->save();
                    $message = 'You have successfully added collar for your pet. You can always remove it by tapping on the collar icon and remove it.';
                }

                return $this->respondResult("", $message);
            } else {
                return $this->respondResult("", 'Failed to perform action, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_pet_images(Request $request) {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $find_record = Pets::find($request->pet_id);

        if (!$find_record) {
            return $this->respondResult("", 'Pet details not found', false, 200);
        }

        // Handle multiple file upload
        $images = $request->file('images');
        if (!empty($images)) {
            foreach ($images as $key => $image) {
                if (!empty($image) && !empty($request->file('images')[$key]) && $request->file('images')[$key]->isValid()) {
                    $image_name = 'images_' . rand(0, 999999) . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                    $destinationPath = public_path("/uploads/" . config('constants.UPLOAD_PETS_FOLDER') . "/" . $find_record->pet_id);
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }
                    $image->move($destinationPath, $image_name);

                    $new_obj = new PetImages();
                    $new_obj->pi_pet_id = $find_record->pet_id;
                    $new_obj->pi_image = $image_name;
                    $new_obj->save();
                }
            }
        }
        return $this->respondResult("", "Pet images added successfully.");
    }

    public function get_pet_details(Request $request) {
        try {
            $login_user_id = Auth::user()->u_id;
            $user_id = !empty($request->user_id) ? $request->user_id : null;
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
                }


                $co_owners = array();
                if ($user_id == $value->pet_owner_id) {
                    $co_owners = PetCoOwners::select("u_id", 'u_first_name', 'u_last_name', 'u_image')
                            ->join('users', 'pet_co_owner_owner_id', 'u_id')
                            ->where('pet_co_owner_pet_id', $request->pet_id)
                            ->where('pet_co_owner_status', 1)
                            ->get();
                }

                $value->co_owners = $co_owners;
                $value->pet_collar = !empty($value->collar) && $value->collar != null ? $value->collar : (object) array();

                $value->login_user_type = 'other';

                if ($login_user_id == $value->pet_owner_id) {
                    $value->login_user_type = 'owner';
                } else {
                    $co_owners = PetCoOwners::select("*")
                            ->where('pet_co_owner_owner_id', $login_user_id)
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

    public function get_pet_schedules(Request $request) {
        try {
            $user_id = !empty($request->user_id) ? $request->user_id : null;
            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $pet_id = $request->pet_id;

            $column = [
                "pet_schedule_id",
                "pet_schedule_pet_id",
                "pet_schedule_name",
                "pet_schedule_start_date",
                "pet_schedule_start_time",
                "pet_schedule_end_date",
                "pet_schedule_end_time",
                "pet_schedule_repeat_on",
                "pet_schedule_reminder",
                "pet_schedule_note"
            ];

            $fetch_record = PetSchedules::select($column)
                    ->where('pet_schedule_pet_id', $pet_id)
                    ->orderBy('pet_schedule_start_date', 'ASC');

            $fetch_record = $fetch_record->paginate($limit);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $response = array();
            if (!empty($fetch_record)) {
                $message = "Pet schedule found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["pagination"] = $pagination_data;
            $response["result"] = !empty($fetch_record->total()) ? $fetch_record->items() : array();
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_pet_all_activities(Request $request) {
        try {
            $user_id = !empty($request->user_id) ? $request->user_id : null;
            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $pet_id = $request->pet_id;

            $column = [
                "*"
            ];

            $fetch_record = PetActivities::select($column)
                    ->with(['activity_locations'])
                    ->where('pet_activity_pet_id', $pet_id);

            if (!empty($request->date)) {
                $fetch_record = $fetch_record->whereDate('pet_activity_start_date_time', $request->date);
            }

            $fetch_record = $fetch_record->orderBy('pet_activity_id', 'DESC')
                    ->paginate($limit);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $response = array();
            if (!empty($fetch_record)) {
                $message = "Pet activity found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["pagination"] = $pagination_data;
            $response["result"] = !empty($fetch_record->total()) ? $fetch_record->items() : array();
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_live_location_pets(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $column = [
                "pet_id", "pet_name", "pet_image", "pet_location_latitude", "pet_location_longitude"
            ];

            $fetch_record = Pets::select($column)
                    ->join('pet_locations', 'pet_location_pet_id', 'pet_id')
                    ->join('users', 'pet_owner_id', 'u_id')
                    ->leftJoin('user_friends', function ($join) {
                        $join->on('u_id', '=', 'ufr_invited_user_id')
                        ->orOn('u_id', '=', 'ufr_user_id')
                        ->where('ufr_status', 1);
                    })
                    ->where(function($query) use($id) {
                        $query->where('ufr_user_id', $id)
                        ->orWhere('ufr_invited_user_id', $id)
                        ->orWhere('pet_owner_id', $id);
                    })
                    ->where('pet_location_status', 'Live')
                    ->where('pet_status', 1)
                    ->groupBy('pet_id')
                    ->get();

            $response = array();
            if (!empty($fetch_record) && $fetch_record->count() > 0) {
                $message = "Pets found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["result"] = $fetch_record;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_pet_schedule_note(Request $request) {
        try {
            $rules = [
                'pet_schedule_note' => ['required', 'max:255'],
                'pet_schedule_id' => ['required'],
            ];

            $customMessages = [
                'pet_schedule_note.required' => "Schedule Note is required",
                'pet_schedule_note.max' => "Schedule Note allows maximum 255 characters only.",
                'pet_schedule_id.required' => "Schedule ID is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = PetSchedules::find($request->pet_schedule_id);

            if (!empty($find_record)) {
                if (!empty($request->pet_schedule_note)) {
                    $find_record->pet_schedule_note = $request->pet_schedule_note;
                }
                $find_record->pet_schedule_updated_at = Carbon::now();
                $find_record->save();
                return $this->respondResult("", "Schedule Note saved successfully");
            } else {
                return $this->respondResult("", 'Schedule not found', false, 200);
            }

            $response["result"] = "";
            $response["message"] = "";
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function start_run(Request $request) {
        try {
            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $pet_id = $request->pet_id;

            $rules = [
                'start_date_time' => ['required'],
            ];

            $customMessages = [
                'start_date_time.required' => "Start Date Time is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_records = new PetActivities();
            $find_records->pet_activity_pet_id = $pet_id;
            $find_records->pet_activity_start_date_time = $request->start_date_time;
            $find_records->pet_activity_status = 2;
            $find_records->pet_activity_created_at = Carbon::now();
            $find_records->save();

            if (!empty($find_records)) {
                return $this->respondResult($find_records, "Pet Activity started successfully.");
            } else {
                return $this->respondResult("", 'Failed to start activity, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function stop_run(Request $request) {
        try {
            $rules = [
                'pet_id' => ['required'],
                'pet_activity_id' => ['required'],
                'end_date_time' => ['required'],
            ];

            $customMessages = [
                'pet_id.required' => "Pet ID is required",
                'pet_activity_id.required' => "Pet Activity ID is required",
                'end_date_time.required' => "End Date Time is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_records = PetActivities::find($request->pet_activity_id);

            if (!$find_records) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_records->pet_activity_end_date_time = $request->end_date_time;
            $find_records->pet_activity_status = 1;
            $find_records->pet_activity_updated_at = Carbon::now();
            $find_records->save();

            if (!empty($find_records)) {
                $find_records->activity_locations = $find_records->activity_locations;

                return $this->respondResult($find_records, "Pet Activity ended successfully.");
            } else {
                return $this->respondResult("", 'Failed to stop activity, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function update_pet_activity_live_location(Request $request) {
        try {
            $rules = [
                'pet_activity_id' => ['required'],
                'latitude' => ['required'],
                'longitude' => ['required'],
            ];

            $customMessages = [
                'pet_activity_id.required' => "Pet Activity ID is required",
                'latitude.required' => "Latitude is required",
                'longitude.required' => "Longitude is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = PetActivities::find($request->pet_activity_id);
            if (!$find_record) {
                return $this->respondResult("", 'Pet Activity details not found', false, 200);
            }

            $new_record = new PetActivityLocations();
            $new_record->pet_activity_location_activity_id = $request->pet_activity_id;
            $new_record->pet_activity_location_latitude = $request->latitude;
            $new_record->pet_activity_location_longitude = $request->longitude;
            $new_record->save();

            if (!empty($new_record)) {
                return $this->respondResult("", "Pet Location updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Pet location, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function update_live_location(Request $request) {
        try {
            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $pet_id = $request->pet_id;

            $rules = [
                'latitude' => ['required'],
                'longitude' => ['required'],
                'status' => ['required'],
            ];

            $customMessages = [
                'latitude.required' => "Latitude is required",
                'longitude.required' => "Longitude is required",
                'status.required' => "Status is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $result = PetLocations::updateOrCreate([
                        'pet_location_pet_id' => $pet_id
                            ], [
                        'pet_location_latitude' => $request->latitude,
                        'pet_location_longitude' => $request->longitude,
                        'pet_location_status' => $request->status,
                        'pet_location_created_at' => Carbon::now(),
                        'pet_location_updated_at' => Carbon::now(),
            ]);

            if (!empty($result)) {
                return $this->respondResult("", "Pet Location updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Pet location, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_pet_schedule(Request $request) {
        try {
            if (empty($request->pet_id)) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $find_record = Pets::find($request->pet_id);

            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            $pet_id = $request->pet_id;

            $rules = [
                'pet_schedule_name' => ['required', 'max:255'],
                'pet_schedule_start_date' => ['required'],
                'pet_schedule_start_time' => ['required'],
                'pet_schedule_end_date' => ['required'],
                'pet_schedule_end_time' => ['required'],
                'pet_schedule_repeat_on' => ['required'],
            ];

            $customMessages = [
                'pet_schedule_name.required' => "Schedule Title is required",
                'pet_schedule_name.max' => "Schedule Title allows maximum 255 characters only.",
                'pet_schedule_start_date.required' => "Schedule Start Date is required",
                'pet_schedule_start_time.required' => "Schedule Start Time is required",
                'pet_schedule_end_date.required' => "Schedule Start Date is required",
                'pet_schedule_end_time.required' => "Schedule Start Time is required",
                'pet_schedule_repeat_on.required' => "Schedule Repeat is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = new PetSchedules();
            $find_record->pet_schedule_pet_id = $pet_id;
            $find_record->pet_schedule_name = $request->pet_schedule_name;
            $find_record->pet_schedule_start_date = $request->pet_schedule_start_date;
            $find_record->pet_schedule_start_time = $request->pet_schedule_start_time;
            $find_record->pet_schedule_end_date = $request->pet_schedule_end_date;
            $find_record->pet_schedule_end_time = $request->pet_schedule_end_time;
            $find_record->pet_schedule_repeat_on = $request->pet_schedule_repeat_on;

            if (!empty($request->pet_schedule_reminder)) {
                $find_record->pet_schedule_reminder = $request->pet_schedule_reminder;
            }
            if (!empty($request->pet_schedule_note)) {
                $find_record->pet_schedule_note = $request->pet_schedule_note;
            }
            if (!empty($request->pet_schedule_recurring)) {
                $find_record->pet_schedule_recurring = $request->pet_schedule_recurring;
            }
            if (!empty($request->pet_schedule_repeating_weekly_every_weekday)) {
                $find_record->pet_schedule_repeating_weekly_every_weekday = $request->pet_schedule_repeating_weekly_every_weekday;
            }
            if (!empty($request->pet_schedule_repeating_day_of_month)) {
                $find_record->pet_schedule_repeating_day_of_month = $request->pet_schedule_repeating_day_of_month;
            }
            if (!empty($request->pet_schedule_repeating_day_of_year)) {
                $find_record->pet_schedule_repeating_day_of_year = $request->pet_schedule_repeating_day_of_year;
            }
            if (!empty($request->pet_schedule_repeating_ends)) {
                $find_record->pet_schedule_repeating_ends = $request->pet_schedule_repeating_ends;
            }
            $find_record->pet_schedule_created_at = Carbon::now();
            //$find_record->save();

            if (!empty($find_record)) {
                if ($find_record->pet_schedule_repeat_on != 'Does not repeat') {
                    $this->manage_recurrence($request, $find_record);
                } else {
                    $find_record->save();
                }
                return $this->respondResult("", "Schedule saved successfully");
            } else {
                return $this->respondResult("", 'Failed to save schedule details, Please try again!!', false, 200);
            }

            $response["result"] = "";
            $response["message"] = "";
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function manage_recurrence($request, $event) {
        $insert_events = array();
        $get_matching_days = $this->get_recurrence_days($request, $event);

        if (!empty($get_matching_days)) {
            foreach ($get_matching_days as $match_days) {
                $insert_events[] = array(
                    'pet_schedule_pet_id' => $event->pet_schedule_pet_id,
                    'pet_schedule_name' => $event->pet_schedule_name,
                    'pet_schedule_start_date' => $match_days,
                    'pet_schedule_end_date' => $match_days,
                    'pet_schedule_start_time' => $event->pet_schedule_start_time,
                    'pet_schedule_end_time' => $event->pet_schedule_end_time,
                    'pet_schedule_reminder' => $event->pet_schedule_reminder,
                    'pet_schedule_note' => $event->pet_schedule_note,
                    'pet_schedule_repeat_on' => $event->pet_schedule_repeat_on,
                    'pet_schedule_created_at' => Carbon::now(),
                );
            }

            if (!empty($insert_events) && count($insert_events) > 0) {
                PetSchedules::insert($insert_events);
            }
        }
    }

    public function get_recurrence_days($request, $event) {
        //the days we'll be returning in Y-m-d format
        $current_date = new \DateTime();
        $start_date = new \DateTime($event->pet_schedule_start_date);
        $end_date = new \DateTime($event->pet_schedule_end_date);
        $matching_days = array();
        switch ($event->pet_schedule_repeat_on) {
            case 'Everyday': //daily
                while ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
                    $matching_days[] = $start_date->format('Y-m-d');
                    $start_date->add(new \DateInterval('P1D'));
                }
                break;
            case 'Every week': //weekly
                $weekdays = $start_date->format('D'); //Sunday
                $count = 0;
                $interval = new \DateInterval('P1D');
                $end_date->add(new \DateInterval('P1D'));
                $period = new \DatePeriod($start_date, $interval, $end_date);
                $matching_days = array();
                foreach ($period as $key => $dt) {
                    if ($current_date->getTimestamp() <= $start_date->getTimestamp() && $current_date->getTimestamp() <= $end_date->getTimestamp()) {
                        if ($dt->format('D') === ucfirst(substr($weekdays, 0, 3))) {
                            $count ++;
                            $matching_days[] = $dt->format('Y-m-d');
                        }
                    }
                }
                break;
            case 'Every month': //monthly
                $end_date->add(new \DateInterval('P1D'));
                $matching_days = [];
                while ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
                    if ($start_date->getTimestamp() <= $end_date->getTimestamp() && $start_date->getTimestamp() >= $current_date->getTimestamp()) {
                        $matching_days[] = $start_date->format('Y-m-d');
                    }
                    $start_date->add(new \DateInterval('P1M'));
                }

                break;
            case 'Every year': //monthly
                $end_date->add(new \DateInterval('P1D'));
                $matching_days = [];
                while ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
                    if ($start_date->getTimestamp() <= $end_date->getTimestamp() && $start_date->getTimestamp() >= $current_date->getTimestamp()) {
                        $matching_days[] = $start_date->format('Y-m-d');
                    }
                    $start_date->add(new \DateInterval('P1Y'));
                }
                break;
            case 'Custom': //monthly
                $matching_days = $this->get_custom_recurrence_days($request, $event);
                break;
        }
        return $matching_days;
    }

    public function get_custom_recurrence_days($request, $event) {
        //the days we'll be returning in Y-m-d format
        $current_date = new \DateTime();
        $start_date = new \DateTime($event->pet_schedule_start_date);

        if ($event->pet_schedule_repeating_ends == "Never") {
            $end_date = new \DateTime($event->pet_schedule_end_date);
        } elseif (is_numeric($event->pet_schedule_repeating_ends)) {
            $end_date = (int) $event->pet_schedule_repeating_ends;
        } else {
            $end_date = new \DateTime($event->pet_schedule_repeating_ends);
        }

        $matching_days = array();
        switch ($event->pet_schedule_recurring) {
            case 'Everyday': //daily
                if (is_numeric($end_date)) {
                    $end_date_obj = new \DateTime($event->pet_schedule_start_date);
                    $end_date = $end_date_obj->add(new \DateInterval('P' . $end_date . 'D'));
                }

                while ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
                    $matching_days[] = $start_date->format('Y-m-d');
                    $start_date->add(new \DateInterval('P1D'));
                }
                break;
            case 'Every week': //weekly
                if (is_numeric($end_date)) {
                    $end_date_obj = new \DateTime($event->pet_schedule_start_date);
                    $end_date = $end_date_obj->add(new \DateInterval('P' . (int) ($end_date * 7) . 'D'));
                }
                $weekdays = $this->week_list[$event->pet_schedule_repeating_weekly_every_weekday]; //Sunday
                $count = 0;
                $interval = new \DateInterval('P1D');
                //$end_date->add(new \DateInterval('P1D'));
                $period = new \DatePeriod($start_date, $interval, $end_date);
                $matching_days = array();
                foreach ($period as $key => $dt) {
                    if ($current_date->getTimestamp() <= $start_date->getTimestamp() && $current_date->getTimestamp() <= $end_date->getTimestamp()) {
                        if ($dt->format('D') === ucfirst(substr($weekdays, 0, 3))) {
                            $count ++;
                            $matching_days[] = $dt->format('Y-m-d');
                        }
                    }
                }
                break;
            case 'Every month': //monthly
                if (is_numeric($end_date)) {
                    $end_date_obj = new \DateTime($event->pet_schedule_start_date);
                    $end_date = $end_date_obj->add(new \DateInterval('P' . (int) ($end_date * 30) . 'D'));
                }
                // $end_date->add(new \DateInterval('P1D'));
                $date_of_month = (int) $event->pet_schedule_repeating_day_of_month;
                if ($date_of_month < 10) {
                    $date_of_month = "0" . $date_of_month;
                }
                $matching_days = [];
                while ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
                    $start_date = new \DateTime($start_date->format('Y') . '-' . $start_date->format('m') . '-' . $date_of_month);
                    if ($start_date->getTimestamp() <= $end_date->getTimestamp() && $start_date->getTimestamp() >= $current_date->getTimestamp()) {
                        $matching_days[] = $start_date->format('Y-m-d');
                    }
                    $start_date->add(new \DateInterval('P1M'));
                    $start_date->modify('first day of ' . $start_date->format('F') . $start_date->format('Y'));
                }
                break;
            case 'Every year': //monthly
                if (is_numeric($end_date)) {
                    $end_date_obj = new \DateTime($event->pet_schedule_start_date);
                    $end_date = $end_date_obj->add(new \DateInterval('P' . (int) ($end_date * 356) . 'D'));
                }
                //$end_date->add(new \DateInterval('P1D'));
                $date_of_year = (int) $event->pet_schedule_repeating_day_of_year;
                if ($date_of_year < 10) {
                    $date_of_year = "0" . $date_of_year;
                }
                $matching_days = [];
                while ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
                    $start_date = new \DateTime($start_date->format('Y') . '-' . $start_date->format('m') . '-' . $date_of_year);
                    if ($start_date->getTimestamp() <= $end_date->getTimestamp() && $start_date->getTimestamp() >= $current_date->getTimestamp()) {
                        $matching_days[] = $start_date->format('Y-m-d');
                    }
                    $start_date->add(new \DateInterval('P1Y'));
                }
                break;
        }
        return $matching_days;
    }

    public function delete_pet(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $find_record = Pets::find($request->pet_id);
            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            if ($find_record->pet_owner_id != $id) {
                $co_owners = PetCoOwners::select("*")
                        ->where('pet_co_owner_owner_id', $id)
                        ->where('pet_co_owner_pet_id', $request->pet_id)
                        ->where('pet_co_owner_status', 1)
                        ->first();

                if (empty($co_owners)) {
                    return $this->respondResult("", 'You have no permission delete this Pet', false, 200);
                }
            }

            if (!empty($find_record)) {

                $find_record->delete();
                $find_record->forceDelete();

                return $this->respondResult("", "Pet deleted successfully");
            } else {
                return $this->respondResult("", 'Failed to delete pet, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function delete_pet_image(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $find_record = Pets::find($request->pet_id);
            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            if (!empty($find_record)) {

                $find_image = PetImages::find($request->pi_id);
                if (!$find_image) {
                    return $this->respondResult("", 'Pet image details not found', false, 200);
                }

                $find_image->delete();
                $find_image->forceDelete();

                return $this->respondResult("", "Pet image deleted successfully");
            } else {
                return $this->respondResult("", 'Failed to delete pet image, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }



    public function get_pet_details_for_public(Request $request) {

        try {

            
           
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
                }


                $co_owners = array();
                if ($value->pet_owner_id) {
                    $co_owners = PetCoOwners::select("u_id", 'u_first_name', 'u_last_name', 'u_image')
                            ->join('users', 'pet_co_owner_owner_id', 'u_id')
                            ->where('pet_co_owner_pet_id', $request->pet_id)
                            ->where('pet_co_owner_status', 1)
                            ->get();
                }

                $value->co_owners = $co_owners;
                $value->pet_collar = !empty($value->collar) && $value->collar != null ? $value->collar : (object) array();

                $value->login_user_type = 'other';

                if ($value->pet_owner_id) {
                    $value->login_user_type = 'owner';
                } else {
                    $co_owners = PetCoOwners::select("*")
                            ->where('pet_co_owner_owner_id', $value->pet_owner_id)
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

}
