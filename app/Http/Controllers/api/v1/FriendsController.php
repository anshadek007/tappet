<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Events;
use App\EventImages;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Notification;
use Illuminate\Support\Facades\Log;
use App\UserFriends;
use App\UserBlocks;
use App\Pets;
use App\GroupMembers;
use Illuminate\Support\Facades\DB;

class FriendsController extends APIController {

    protected $userModel;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\User();
    }

    public function recommended_people(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            /**
             * Fetch logged in user
             * get pet breeds
             * Match with other users
             */
            $get_my_pet_breed_ids = Pets::select(\DB::raw('GROUP_CONCAT(pet_breed_ids) as my_pet_breeds'))
                    ->where('pet_owner_id', $id)
                    ->where('pet_status', 1)
                    ->pluck('my_pet_breeds')
                    ->first();

            $column = ["u_id", "u_first_name", "u_last_name", "u_image"];

            $get_same_breed_users = [];
            $get_same_breed_users_ids = [];
            //get other user pets who have same breed pets
            if (!empty($get_my_pet_breed_ids)) {
//                \DB::enableQueryLog();
                $get_same_breed_users = User::select($column)
                        ->join('pets', 'pet_owner_id', 'u_id')
                        ->where('u_id', '!=', $id)
                        ->whereIn('pet_breed_ids', explode(',', $get_my_pet_breed_ids))
                        ->whereNull('pet_deleted_at')
                        ->where('pet_status', 1)
                        ->where('u_status', 1)
                        ->groupBy('u_id')
                        ->limit(100)
                        ->get()
                        ->toArray();

                if (!empty($get_same_breed_users) && count($get_same_breed_users) > 0) {
                    foreach ($get_same_breed_users as $value) {
                        $get_same_breed_users_ids[] = $value['u_id'];
                    }
                }

//                dd(\DB::getQueryLog());
//                dd($fetch_record);
            }
//            dd($get_same_breed_users_ids);
//            dd($get_same_breed_users);

            $latitude = !empty($request->latitude) ? trim($request->latitude) : null;
            $longitude = !empty($request->longitude) ? trim($request->longitude) : null;

            $get_nearest_users = [];
            if (!empty($longitude) && !empty($latitude)) {
                //get nearest user if lat - long pass
                $get_nearest_users = DB::table("users")->where("u_status", 1)
                        ->where("u_is_verified", 1)
                        ->where("u_phone_verified", 1)
                        ->select(DB::raw('3959 * acos (
                                          cos ( radians(' . $latitude . ') )
                                          * cos( radians(u_latitude) )
                                          * cos( radians(u_longitude) - radians(' . $longitude . ') )
                                          + sin ( radians(' . $latitude . ') )
                                          * sin( radians(u_latitude) ) ) as distance_from_mylocation'), "u_id", "u_first_name", "u_last_name", "u_image")
                        ->having('distance_from_mylocation', '<', 20);

                if (!empty($get_same_breed_users_ids) && count($get_same_breed_users_ids) > 0) {
                    $get_nearest_users = $get_nearest_users->whereNotIn('u_id', $get_same_breed_users_ids);
                }
                $get_nearest_users = $get_nearest_users->where("u_id", "!=", $id)
                        ->whereNull("u_deleted_at")
                        ->limit(100)
                        ->get()
                        ->toArray();
            }

//            dd($get_nearest_users);
            //get my friend ids
            $get_my_friends = User::select(DB::raw('GROUP_CONCAT(u_id) as my_friends_ids'))
                    ->leftJoin('user_friends', function ($join) {
                        $join->on('u_id', '=', 'ufr_invited_user_id')
                        ->orOn('u_id', '=', 'ufr_user_id');
                    })
                    ->where(function($query) use($id) {
                        $query->where('ufr_user_id', $id)
                        ->orWhere('ufr_invited_user_id', $id);
                    })
                    ->where('u_id', "!=", $id)
                    ->where('ufr_status', 1)
                    ->limit(100)
                    ->pluck('my_friends_ids')
                    ->first();

            $get_friend_of_friends = [];
            if (!empty($get_my_friends)) {
                $friends_list = explode(',', $get_my_friends);
                //dd($friends_list);
                if (!empty($friends_list) && count($friends_list) > 0) {
                    foreach ($friends_list as $value) {
//                        DB::enableQueryLog();
                        $get_my_friends = User::select($column)
                                ->leftJoin('user_friends', function ($join) {
                                    $join->on('u_id', '=', 'ufr_invited_user_id')
                                    ->orOn('u_id', '=', 'ufr_user_id');
                                })
                                ->where(function($query) use($value) {
                                    $query->where('ufr_user_id', $value)
                                    ->orWhere('ufr_invited_user_id', $value);
                                })
//                                ->where(function($query) use($id) {
//                                    $query->where('ufr_user_id', '!=', $id)
//                                    ->orWhere('ufr_invited_user_id', '!=', $id);
//                                })
                                ->whereNotIn('u_id', $friends_list)
                                ->where('u_id', "!=", $id)
                                ->where('ufr_status', 1)
                                ->limit(20)
                                ->get()
                                ->toArray();

                        if (!empty($get_my_friends) && count($get_my_friends) > 0) {
                            $get_friend_of_friends[] = $get_my_friends;
                        }
                        // dd(DB::getQueryLog());
                    }
                }
            }

            $all_friend_list = [];
            if (!empty($get_friend_of_friends) && count($get_friend_of_friends) > 0) {
                foreach ($get_friend_of_friends as $get_friend_of_friend) {
                    foreach ($get_friend_of_friend as $value) {
                        $all_friend_list[] = $value;
                    }
                }
            }
//            dd($all_friend_list);

            $all_friends = array_merge($get_same_breed_users, $get_nearest_users, $all_friend_list);

//            dd($all_friends);

            /**
             * $get_same_breed_users + $get_nearest_users + $all_friend_list
             */
            $fetch_record_list = array();
            $response = array();
            $all_friend_ids = [];
            if (count($all_friends) > 0) {
                foreach ($all_friends as $value) {

                    $value = (array) $value;

                    if (!in_array($value['u_id'], $all_friend_ids)) {

                        $all_friend_ids[] = $value['u_id'];

                        $friend_request = 0;
                        $invited_user_id = $value['u_id'];

                        $invites = 'SELECT
                            tappet_user_friends.*
                        FROM
                            tappet_user_friends
                        LEFT JOIN 
                            tappet_users 
                        ON 
                            `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                        WHERE
                            (`ufr_user_id` = ' . $id . ' AND `ufr_invited_user_id` = ' . $invited_user_id . ') 
                            OR
                            (`ufr_user_id` = ' . $invited_user_id . ' AND `ufr_invited_user_id` = ' . $id . ')  
                        AND `u_status` != 9 
                        AND `ufr_status` != 9 
                        AND `ufr_deleted_at` IS NULL
                        AND `tappet_users`.`u_deleted_at` IS NULL
                        LIMIT 1';

                        $check_friend = \DB::select($invites);

                        $friend_request_sent_by_me = false;
                        if (!empty($check_friend) && count($check_friend) > 0) {
                            $friend_request = $check_friend[0]->ufr_status;
                            $friend_request_sent_by_me = $check_friend[0]->ufr_user_id == $id ? true : false;
                        }

                        $fetch_record_list[] = array(
                            'u_id' => $value['u_id'],
                            'u_first_name' => $value['u_first_name'],
                            'u_last_name' => $value['u_last_name'],
                            'u_image' => $value['u_image'],
                            'total_pets' => Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count(),
                            'total_mutual_friends' => $this->find_mutual_friends($id, $invited_user_id),
                            'friend_request' => $friend_request,
                            'friend_request_sent_by_me' => $friend_request_sent_by_me,
                        );
                    }
                }
                $message = "People found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function explore_people(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $group_id = !empty($request->group_id) ? (int) $request->group_id : null;
            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $id = Auth::user()->u_id;

            $column = ["u_id", "u_first_name", "u_last_name", "u_email", "u_mobile_number", "u_country_code", "u_country", "u_state", "u_city", "u_latitude", "u_longitude", "u_zipcode", "u_address", "u_image"];
            $fetch_record = User::select($column)
                    ->where('u_id', '!=', $id)
                    ->where('u_user_type',"!=",4)
                    ->where('u_status', 1);

            if (!empty($request->search)) {
                $search = $request->search;
                $fetch_record = $fetch_record->where(function ($query) use ($search) {
                    $query->where('u_email', 'like', '%' . $search . '%')
                            ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $search . '%')
                            ->orWhere('u_mobile_number', 'like', '%' . $search . '%');
                });
            }

            $fetch_record = $fetch_record->orderBy("u_id", "DESC");

            $fetch_record = $fetch_record->paginate($limit);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as &$value) {
                    $value->friend_request = 0;
                    $invited_user_id = $value->u_id;

                    $invites = 'SELECT
                        tappet_user_friends.*
                    FROM
                        tappet_user_friends
                    LEFT JOIN 
                        tappet_users 
                    ON 
                        `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                    WHERE
                        (`ufr_user_id` = ' . $id . ' AND `ufr_invited_user_id` = ' . $invited_user_id . ') 
                        OR
                        (`ufr_user_id` = ' . $invited_user_id . ' AND `ufr_invited_user_id` = ' . $id . ')  
                    AND `u_status` != 9 
                    AND `ufr_status` != 9 
                    AND `ufr_deleted_at` IS NULL
                    AND `tappet_users`.`u_deleted_at` IS NULL
                    LIMIT 1';

                    $check_friend = \DB::select($invites);

                    $value->friend_request_sent_by_me = false;
                    if (!empty($check_friend) && count($check_friend) > 0) {
                        $value->friend_request = $check_friend[0]->ufr_status;
                        $value->friend_request_sent_by_me = $check_friend[0]->ufr_user_id == $id ? true : false;
                    }

                    $value->total_mutual_friends = $this->find_mutual_friends($id, $invited_user_id);
                    $value->total_pets = Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count();
                    if (!empty($group_id)) {
                        $value->is_group_member = !empty(GroupMembers::where('gm_group_id', $group_id)->where('gm_user_id', $invited_user_id)->first()) ? true : false;
                    } else {
                        $value->is_group_member = false;
                    }

                    $fetch_record_list[] = $value;
                }
                $message = "People found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["pagination"] = $pagination_data;
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function search_people(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $pet_id = !empty($request->pet_id) ? $request->pet_id : null;
            $offset = ($page - 1) * $limit;

            $id = Auth::user()->u_id;

            $column = ["u_id", "u_first_name", "u_last_name", "u_image"];
            $fetch_record = User::select($column)
                    ->where('u_id', '!=', $id)
                    ->where('u_user_type',"!=",4)
                    ->where('u_status', 1);

            if (!empty($request->search)) {
                $search = $request->search;
                $fetch_record = $fetch_record->where(function ($query) use ($search) {
                    $query->where('u_email', 'like', '%' . $search . '%')
                            ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $search . '%')
                            ->orWhere('u_mobile_number', 'like', '%' . $search . '%');
                });
            }

            $fetch_record = $fetch_record->orderBy("u_id", "DESC");
            $fetch_record = $fetch_record->paginate($limit);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as &$value) {
                    $invited_user_id = $value->u_id;
                    $value->total_friends = $this->userModel->friends($invited_user_id)->count();
                    $value->total_pets = Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count();
                    if (!empty($pet_id)) {
                        $value->is_pet_co_owner = !empty(\App\PetCoOwners::where('pet_co_owner_owner_id', $invited_user_id)->where('pet_co_owner_pet_id', $pet_id)->where('pet_co_owner_status', 1)->count()) ? true : false;
                    } else {
                        $value->is_pet_co_owner = false;
                    }
                    $fetch_record_list[] = $value;
                }
                $message = "People found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["pagination"] = $pagination_data;
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    /**
     * Invite Friends on App
     * 
     * @param Request $request
     * @return type
     */
    public function add_friend(Request $request) {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $id = $user->u_id;

        $rules = [
            'invited_user_id' => ['required'],
        ];

        $customMessages = [
            'invited_user_id.required' => "User ID is required field.",
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        if ($user->u_id == $request->invited_user_id) {
            return $this->respondResult("", 'You cannot send friend request to yourself.', false, 200);
        }

        $user_blocked = UserBlocks::where("user_block_user_id", $request->invited_user_id)
                ->where("user_block_blocked_user_id", $user->u_id)
                ->first();

        if (!empty($user_blocked)) {
            return $this->respondResult("", 'You cannot send friend request.', false, 200);
        }

        $invited_user_id = $request->invited_user_id;

        $invites = 'SELECT
                        tappet_user_friends.*
                    FROM
                        tappet_user_friends
                    LEFT JOIN 
                        tappet_users 
                    ON 
                        `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                    WHERE
                        (`ufr_user_id` = ' . $id . ' AND `ufr_invited_user_id` = ' . $invited_user_id . ') 
                        OR
                        (`ufr_user_id` = ' . $invited_user_id . ' AND `ufr_invited_user_id` = ' . $id . ')  
                    AND `u_status` != 9 
                    AND `ufr_status` != 9 
                    AND `ufr_deleted_at` IS NULL
                    AND `tappet_users`.`u_deleted_at` IS NULL
                    LIMIT 1';

        //Log::channel('userlog')->info("Invite Friend query FIRST =" . $invites);

        $check_friend = \DB::select($invites);

//        dd($check_friend);

        if (!empty($check_friend) && count($check_friend) > 0) {
            return $this->respondResult("", "You've already send request", false, 200);
        }

        $random_invitation_token = str_rand_access_token(64);
        $response_message = "Friend invitation sent successfully.";
        $check_is_active_user = User::where('u_id', $invited_user_id)->where("u_status", 1)->first();

        if (!empty($check_is_active_user)) {
            $add_new_request = array(
                "ufr_user_id" => $id,
                "ufr_invited_user_id" => $check_is_active_user->u_id,
                "ufr_email" => $check_is_active_user->u_email,
                "ufr_token" => $random_invitation_token,
                "ufr_status" => 2
            );

            UserFriends::create($add_new_request);

            $n_message = " has sent you friend request.";
            $this->send_friend_invite_push($check_is_active_user->u_id, $id, $n_message, 2, 2, $user);
        }

        $response["message"] = $response_message;
        $response["status"] = true;
        $response["code"] = 200;

        return response()->json($response, 200);
    }

    public function friend_request_action(Request $request) {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $state = !empty($request->state) && $request->state == 1 ? $request->state : 3;
        $sender_id = !empty($request->sender_id) ? $request->sender_id : null;
        $reciever_id = !empty($request->reciever_id) ? $request->reciever_id : null;

        $friend_invite = UserFriends::where("ufr_user_id", $sender_id)
                        ->where("ufr_invited_user_id", $reciever_id)->first();

        $message = "Friend request no more exist";
        if (!empty($friend_invite) && $state == 1) {
            $message = "You've accepted friend request.";
            $friend_invite->ufr_status = 1;
            $friend_invite->update();
            
            $check_is_active_user = User::where('u_id', $sender_id)->where("u_status", 1)->first();

            if (!empty($check_is_active_user)) {
                $n_message = ucwords($user->u_first_name) . " has accepted your friend request.";
                $this->send_friend_invite_push($sender_id, $reciever_id, $n_message, 8, 2, $user);
            }
        } else if (!empty($friend_invite) && $state == 3) {
            $message = "You've rejected friend request.";
            $friend_invite->ufr_status = 9;
            $friend_invite->update();
            $friend_invite->delete();
            $friend_invite->forceDelete();
            
             $check_is_active_user = User::where('u_id', $sender_id)->where("u_status", 1)->first();

            if (!empty($check_is_active_user)) {
                $n_message = ucwords($user->u_first_name) . " has rejected your friend request.";
                $this->send_friend_invite_push($sender_id,$reciever_id, $n_message, 9, 2, $user);
            }
        }

        $notification = Notification::where('n_reciever_id', $reciever_id)
                ->where('n_sender_id', $sender_id)
                ->where('n_notification_type', 2)
                ->latest()
                ->first();

        if (!empty($notification)) {
            $notification->n_status = 9;
            $notification->update();
            $notification->delete();
            $notification->forceDelete();
        }

        $response["message"] = $message;
        $response["status"] = true;
        $response["code"] = 200;

        return response()->json($response, 200);
    }

    public function remove_friend(Request $request) {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $sender_id = Auth::user()->u_id;
        $reciever_id = !empty($request->friend_id) ? $request->friend_id : null;

        $friend_invite = UserFriends::
                where(function($query) use($sender_id, $reciever_id) {
                    $query->where('ufr_user_id', $sender_id)
                    ->where('ufr_invited_user_id', $reciever_id);
                })
                ->orWhere(function($query) use($sender_id, $reciever_id) {
                    $query->where('ufr_user_id', $reciever_id)
                    ->where('ufr_invited_user_id', $sender_id);
                })
                ->first();

        $message = "You're not friends";
        if (!empty($friend_invite)) {
            $message = "Friend removed successfully.";
            $friend_invite->ufr_status = 9;
            $friend_invite->update();
            $friend_invite->delete();
            $friend_invite->forceDelete();
        }

        $response["message"] = $message;
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
    public function my_friends(Request $request) {
        if (!empty($request->u_id)) {
            $user = $this->userModel->validateUser($request->u_id);
            $id = $request->u_id;
        } else {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            $id = Auth::user()->u_id;
        }

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
        $page = !empty($request->page) ? $request->page : 1;
        $friend_status = !empty($request->friend_request) ? $request->friend_request : null;
        $offset = ($page - 1) * $limit;

//        \DB::enableQueryLog();
        $fetch_record = User::select("u_id", "u_first_name", "u_last_name", "u_email", "u_image", "ufr_status", "ufr_user_id")
                ->leftJoin('user_friends', function ($join) {
                    $join->on('u_id', '=', 'ufr_invited_user_id')
                    ->orOn('u_id', '=', 'ufr_user_id');
                })
                ->where(function($query) use($id) {
                    $query->where('ufr_user_id', $id)
                    ->orWhere('ufr_invited_user_id', $id);
                })
                ->where('u_id', "!=", $id);

        if ($friend_status == 1) {
            $fetch_record = $fetch_record->where('ufr_status', $friend_status);
        } else if ($friend_status == 2) {
            $fetch_record = $fetch_record->where('ufr_status', $friend_status);
        } else {
            $fetch_record = $fetch_record->where('ufr_status', '!=', 9);
        }


        if (!empty($request->search)) {
            $search = $request->search;
            $fetch_record = $fetch_record->where(function ($query) use ($search) {
                $query->where('u_email', 'like', '%' . $search . '%')
                        ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $search . '%')
                        ->orWhere('u_mobile_number', 'like', '%' . $search . '%');
            });
        }

        $fetch_record = $fetch_record->paginate($limit);


//        $get_all_records_email = UserFriends::select("*")->where('ufr_user_id', $id)->where('ufr_status', "!=", 9)->where('ufr_invited_user_id', NULL)->get();
//        $get_all_records = new \Illuminate\Support\Collection($fetch_record);
//        $get_all_records = $get_all_records->merge($get_all_records_email);
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
        $pagination_data = [
            'total' => $fetch_record->total(),
            'lastPage' => $fetch_record->lastPage(),
            'perPage' => $fetch_record->perPage(),
            'currentPage' => $fetch_record->currentPage(),
        ];

        $response_data = array();
        if (!empty($fetch_record) && $fetch_record->count() > 0) {
            foreach ($fetch_record as $value) {
                $invited_user_id = $value->u_id;

                $response_data[] = array(
                    'u_id' => $value->u_id,
                    'u_first_name' => $value->u_first_name,
                    'u_last_name' => $value->u_last_name,
                    'u_image' => $value->u_image,
                    'friend_request' => $value->ufr_status,
                    'total_mutual_friends' => $this->find_mutual_friends($id, $invited_user_id),
                    'total_pets' => Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count(),
                    'is_blocked_by_me' => UserBlocks::where('user_block_user_id', $id)->where('user_block_blocked_user_id', $invited_user_id)->where('user_block_status', 1)->count(),
                    'friend_request_sent_by_me' => $value->ufr_user_id == $id ? true : false,
                );
            }

            $message = "Friend list found successfully.";
            $status = true;
        } else {
            $message = "No friend data found.";
            $status = false;
        }

        $response["pagination"] = $pagination_data;
        $response["result"] = $response_data;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function get_mutual_friends(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $user_id = !empty($request->user_id) ? $request->user_id : null;
            $friend_id = !empty($request->friend_id) ? $request->friend_id : null;

            if (empty($user_id) || empty($friend_id)) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $get_all_records = $this->userModel->get_mutual_friends_list($user_id, $friend_id);


            $fetch_record_list = array();
            $message = "No data found.";
            $pagination_data = [];
            if (!empty($get_all_records)) {

                $get_all_user_ids = [];
                foreach ($get_all_records as $value) {
                    $get_all_user_ids[] = $value->UserId;
                }

                $column = ["u_id", "u_first_name", "u_last_name", "u_image"];
                $fetch_record = User::select($column)
                        ->where('u_id', '!=', $id)
                        ->whereIn('u_id', $get_all_user_ids)
                        ->where('u_status', 1);

                if (!empty($request->search)) {
                    $search = $request->search;
                    $fetch_record = $fetch_record->where(function ($query) use ($search) {
                        $query->where('u_email', 'like', '%' . $search . '%')
                                ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $search . '%')
                                ->orWhere('u_mobile_number', 'like', '%' . $search . '%');
                    });
                }

                $fetch_record = $fetch_record->orderBy("u_id", "DESC");
                $fetch_record = $fetch_record->paginate($limit);

                $pagination_data = [
                    'total' => $fetch_record->total(),
                    'lastPage' => $fetch_record->lastPage(),
                    'perPage' => $fetch_record->perPage(),
                    'currentPage' => $fetch_record->currentPage(),
                    'currentPage' => $fetch_record->currentPage(),
                ];


                $response = array();
                if (count($fetch_record) > 0) {
                    foreach ($fetch_record as &$value) {
                        $invited_user_id = $value->u_id;

                        $value->total_mutual_friends = $this->find_mutual_friends($user_id, $invited_user_id);
                        $value->total_pets = Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count();

                        $fetch_record_list[] = $value;
                    }
                    $message = "Mutual friend found successfully.";
                }
            }
            $response["pagination"] = !empty($pagination_data) ? $pagination_data : (object) array();
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_other_friends(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $user_id = !empty($request->user_id) ? $request->user_id : null;
            $friend_id = !empty($request->friend_id) ? $request->friend_id : null;

            if (empty($user_id) || empty($friend_id)) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $get_all_records = $this->userModel->get_mutual_friends_list($user_id, $friend_id);
            $get_my_friends = $this->userModel->friends($id)->select('u_id')->get();

            $fetch_record_list = array();
            $message = "No data found.";
            $pagination_data = [];
            if (!empty($get_all_records)) {

                $get_all_user_ids = [];
                foreach ($get_all_records as $value) {
                    $get_all_user_ids[] = $value->UserId;
                }
                if (!empty($get_my_friends) && count($get_my_friends) > 0) {
                    foreach ($get_my_friends as $value) {
                        if (!in_array($value->u_id, $get_all_user_ids)) {
                            $get_all_user_ids[] = $value->u_id;
                        }
                    }
                }

                $column = ["u_id", "u_first_name", "u_last_name", "u_image"];
                $fetch_record = User::select($column)
                        ->where('u_id', '!=', $id)
                        ->whereNotIn('u_id', $get_all_user_ids)
                        ->where('u_status', 1);

                if (!empty($request->search)) {
                    $search = $request->search;
                    $fetch_record = $fetch_record->where(function ($query) use ($search) {
                        $query->where('u_email', 'like', '%' . $search . '%')
                                ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $search . '%')
                                ->orWhere('u_mobile_number', 'like', '%' . $search . '%');
                    });
                }

                $fetch_record = $fetch_record->orderBy("u_id", "DESC");
                $fetch_record = $fetch_record->paginate($limit);

                $pagination_data = [
                    'total' => $fetch_record->total(),
                    'lastPage' => $fetch_record->lastPage(),
                    'perPage' => $fetch_record->perPage(),
                    'currentPage' => $fetch_record->currentPage(),
                    'currentPage' => $fetch_record->currentPage(),
                ];

                $fetch_record_list = array();
                $response = array();
                if (count($fetch_record) > 0) {
                    foreach ($fetch_record as &$value) {
                        $invited_user_id = $value->u_id;

                        $value->total_mutual_friends = $this->find_mutual_friends($user_id, $invited_user_id);
                        $value->total_pets = Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count();
                        $fetch_record_list[] = $value;
                    }
                    $message = "People found successfully.";
                } else {
                    $message = "No data found.";
                }
            }

            $response["pagination"] = !empty($pagination_data) ? $pagination_data : (object) array();
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    /**
     * 
     * @param type $id
     * @param type $invited_user_id
     * @return type
     */
    private function find_mutual_friends($id = 0, $invited_user_id = 0) {
        $find_mutual = 'SELECT UserAFriends.UserId FROM
                                (
                                  SELECT ufr_invited_user_id UserId FROM tappet_user_friends WHERE ufr_user_id = ' . $id . '
                                    UNION 
                                  SELECT ufr_user_id UserId FROM tappet_user_friends WHERE ufr_invited_user_id = ' . $id . '
                                ) AS UserAFriends
                                JOIN  
                                (
                                  SELECT ufr_invited_user_id UserId FROM tappet_user_friends WHERE ufr_user_id = ' . $invited_user_id . '
                                    UNION 
                                  SELECT ufr_user_id UserId FROM tappet_user_friends WHERE ufr_invited_user_id = ' . $invited_user_id . '
                                ) AS UserBFriends 
                                ON  UserAFriends.UserId = UserBFriends.UserId';

        $check_mutual_friend = \DB::select($find_mutual);

        $total_mutual_friends = 0;
        if (!empty($check_mutual_friend) && count($check_mutual_friend) > 0) {
            $total_mutual_friends = count($check_mutual_friend);
        }

        return (int) $total_mutual_friends;
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
    private function send_friend_invite_push($n_reciever_id, $n_sender_id, $n_message, $n_notification_type, $n_status, $user) {
        if (!empty($n_reciever_id) && !empty($n_sender_id) && !empty($n_message) && !empty($n_notification_type) && !empty($n_status) && !empty($user)) {
            $receiver = User::find($n_reciever_id);

            $notification_data = new \App\Notification();
            $notification_data->n_reciever_id = $n_reciever_id;
            $notification_data->n_sender_id = $n_sender_id;
            $notification_data->n_params = json_encode(["u_id" => $user->u_id]);
            $notification_data->n_message = $n_message;
            $notification_data->n_notification_type = $n_notification_type;
            $notification_data->n_status = $receiver->u_friend_request_notification == 2 ? 3 : $n_status;
            $notification_data->n_created_at = Carbon::now();

            if ($notification_data->save() && $receiver->u_friend_request_notification == 1) {
                if($n_notification_type==8){
                    $process = new \Symfony\Component\Process\Process("php artisan send_friend_invite_accept_push $notification_data->n_id >>/dev/null 2>&1");
                }elseif($n_notification_type==9){
                    $process = new \Symfony\Component\Process\Process("php artisan send_friend_invite_reject_push $notification_data->n_id >>/dev/null 2>&1");
                }else{ 
                    $process = new \Symfony\Component\Process\Process("php artisan send_friend_invite_push $notification_data->n_id >>/dev/null 2>&1");
                }
                
                
                $process->start();
            }
        }
    }

}
