<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Pets;
use App\PetImages;
use App\PetTypes;
use App\PetBreeds;
use App\BusinessUser;
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

class CorPetsController extends APIController {

    protected $userModel;
    protected $week_list;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\BusinessUser();
        $this->week_list = weekList();
    }

    public function get_pet_details(Request $request) {
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

}