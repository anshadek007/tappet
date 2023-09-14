<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Groups;
use App\GroupMembers;
use App\EventMembers;
use App\Events;
use App\User;
use App\Pets;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;

class GroupsController extends APIController {

    protected $userModel;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\User();
    }

    public function get_all_groups(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $fetch_record = Groups::select("group_id", "group_owner_id", "group_name", "group_image", "group_description", "group_privacy")
                    ->withCount('group_members')
                    ->with(['group_last_two_members', 'group_last_two_members.member'])
                    ->join('group_members', 'group_id', 'gm_group_id')
                    ->where('gm_user_id', $id)
                    ->where('group_status', 1);

            $fetch_record = $fetch_record->orderBy("group_id", "DESC")->get();

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                $message = "Groups found successfully.";
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

    public function get_all_groups_and_friends(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $fetch_record = Groups::select("group_id", "group_owner_id", "group_name", "group_image", "group_description")
                    ->withCount('group_members')
                    ->join('group_members', 'group_id', 'gm_group_id')
                    ->where('gm_user_id', $id)
                    ->where('group_status', 1);

            $fetch_record = $fetch_record->orderBy("group_id", "DESC")
                    ->limit(200)
                    ->get();

            $response_data = array();
            if (!empty($fetch_record) && $fetch_record->count() > 0) {
                foreach ($fetch_record as $value) {
                    $get_last_two_member = $value->group_last_two_only;

                    $last_two_member = array();
                    if (!empty($get_last_two_member)) {
                        foreach ($get_last_two_member as $member_value) {
                            if (!empty($member_value->member)) {
                                $last_two_member[] = array(
                                    'u_id' => $member_value->member->u_id,
                                    'u_first_name' => $member_value->member->u_first_name,
                                    'u_last_name' => $member_value->member->u_last_name,
                                    'u_image' => $member_value->member->u_image
                                );
                            }
                        }
                    }

                    $response_data[] = array(
                        'group_id' => $value->group_id,
                        'group_owner_id' => $value->group_owner_id,
                        'group_name' => $value->group_name,
                        'group_image' => $value->group_image,
                        'group_description' => $value->group_description,
                        'group_members_count' => $value->group_members_count,
                        'get_last_two_member' => $last_two_member,
                    );
                }
            }

            $result_data['groups'] = $response_data;

            $fetch_record = User::select("u_id", "u_first_name", "u_last_name", "u_email", "u_image")
                    ->leftJoin('user_friends', function ($join) {
                        $join->on('u_id', '=', 'ufr_invited_user_id')
                        ->orOn('u_id', '=', 'ufr_user_id');
                    })
                    ->where(function($query) use($id) {
                        $query->where('ufr_user_id', $id)
                        ->orWhere('ufr_invited_user_id', $id);
                    })
                    ->where('u_id', "!=", $id)
                    ->where('ufr_status', 1);

            if (!empty($request->search)) {
                $search = $request->search;
                $fetch_record = $fetch_record->where(function ($query) use ($search) {
                    $query->where('u_email', 'like', '%' . $search . '%')
                            ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $search . '%')
                            ->orWhere('u_mobile_number', 'like', '%' . $search . '%');
                });
            }

            $fetch_record = $fetch_record->limit(200)->get();

            $response_data = array();
            if (!empty($fetch_record) && $fetch_record->count() > 0) {
                foreach ($fetch_record as $value) {
                    $invited_user_id = $value->u_id;
                    $response_data[] = array(
                        'u_id' => $value->u_id,
                        'u_first_name' => $value->u_first_name,
                        'u_last_name' => $value->u_last_name,
                        'u_image' => $value->u_image,
                        'total_mutual_friends' => $this->userModel->find_mutual_friends($id, $invited_user_id),
                        'total_pets' => Pets::where('pet_owner_id', $invited_user_id)->where('pet_status', 1)->count(),
                    );
                }
            }

            $result_data['friends'] = $response_data;

            $response = array();
            $response["result"] = $result_data;
            $response["message"] = "";
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function create_group(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'group_name' => ['required', 'max:100'],
            ];

            if (!empty($request->file('group_image'))) {
                $rules['group_image'] = 'required|mimes:jpeg,jpg,png|max:1024';
            }

            $customMessages = [
                'group_name.required' => "Group Name is required",
                'group_name.max' => "Group Name allows maximum 100 characters only.",
                'group_image.image' => 'The type of the uploaded file should be an image.',
                'group_image.mimes' => 'The type of the uploaded file should be an image.',
                'group_image.uploaded' => 'Failed to upload an image. The image maximum size is 10MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = new Groups();
            $find_record->group_owner_id = $id;
            $find_record->group_name = $request->group_name;

            if (!empty($request->group_description)) {
                $find_record->group_description = $request->group_description;
            }

            $find_record->group_created_at = Carbon::now();
            $find_record->group_image = "";
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('group_image'))) {
                    $fileName = $this->uploadFile($request->file('group_image'), $find_record->group_id, config('constants.UPLOAD_GROUPS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload group image, Try again..!");
                    }
                    $find_record->group_image = $fileName;
                    $find_record->save();
                }

                $group_member = new GroupMembers();
                $group_member->gm_user_id = $id;
                $group_member->gm_group_id = $find_record->group_id;
                $group_member->gm_role = 'Admin';
                $group_member->gm_status = 1;
                $group_member->save();

                if (!empty($request->group_members)) {
                    foreach ($request->group_members as $value) {
                        $group_member = new GroupMembers();
                        $group_member->gm_user_id = $value;
                        $group_member->gm_group_id = $find_record->group_id;
                        $group_member->gm_role = 'User';
                        $group_member->gm_status = 1;
                        $group_member->save();

                        $notification_data = new \App\Notification();
                        $notification_data->n_reciever_id = (int) $value;
                        $notification_data->n_sender_id = $id;
                        $notification_data->n_params = json_encode(["group_id" => $find_record->group_id, "group_name" => $request->group_name]);
                        $notification_data->n_message = "You are added to a group called";
                        $notification_data->n_notification_type = 3;
                        $notification_data->n_status = 2;
                        $notification_data->n_created_at = \Carbon\Carbon::now();
                        $notification_data->save();
                    }

                    $this->send_added_into_group_push();
                }


                $response["result"] = $find_record;
                $response["message"] = "Group saved successfully";
                $response["status"] = true;

                return response()->json($response, 200);
            } else {
                return $this->respondResult("", 'Failed to save group details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function edit_group(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'group_id' => ['required'],
                'group_name' => ['required', 'max:100'],
            ];

            if (!empty($request->file('group_image'))) {
                $rules['group_image'] = 'required|mimes:jpeg,jpg,png|max:1024';
            }

            $customMessages = [
                'group_id.required' => "Group ID is required",
                'group_name.required' => "Group Name is required",
                'group_name.max' => "Group Name allows maximum 100 characters only.",
                'group_image.image' => 'The type of the uploaded file should be an image.',
                'group_image.mimes' => 'The type of the uploaded file should be an image.',
                'group_image.uploaded' => 'Failed to upload an image. The image maximum size is 10MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            if (!empty($request->group_name)) {
                $find_record->group_name = $request->group_name;
            }

            if (!empty($request->group_description)) {
                $find_record->group_description = $request->group_description;
            }

            $find_record->group_updated_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('group_image'))) {
                    $fileName = $this->uploadFile($request->file('group_image'), $find_record->group_id, config('constants.UPLOAD_GROUPS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload group image, Try again..!");
                    }
                    $find_record->group_image = $fileName;
                    $find_record->save();
                }

                return $this->respondResult("", "Group details updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Group details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_group_details(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            if (empty($request->group_id)) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            $group_id = $request->group_id;
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

                $message = "Group details found successfully.";
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

    public function add_group_member(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'group_members' => ['required'],
            ];

            $customMessages = [
                'group_members.required' => "At least One Group Member is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            if (!empty($request->group_members)) {
                foreach ($request->group_members as $key => $value) {
                    $find_member = GroupMembers::where('gm_group_id', $find_record->group_id)
                            ->where('gm_user_id', $value)
                            ->first();

                    if (empty($find_member)) {
                        $group_member = new GroupMembers();
                        $group_member->gm_user_id = $value;
                        $group_member->gm_group_id = $find_record->group_id;
                        $group_member->gm_role = 'User';
                        $group_member->gm_status = 1;
                        $group_member->save();

                        $notification_data = new \App\Notification();
                        $notification_data->n_reciever_id = (int) $value;
                        $notification_data->n_sender_id = $id;
                        $notification_data->n_params = json_encode(["group_id" => $find_record->group_id, "group_name" => $find_record->group_name]);
                        $notification_data->n_message = "You are added to a group called";
                        $notification_data->n_notification_type = 3;
                        $notification_data->n_status = 2;
                        $notification_data->n_created_at = \Carbon\Carbon::now();
                        $notification_data->save();
                    }
                }

                $this->send_added_into_group_push();
            }

            return $this->respondResult("", "Group Member added successfully");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function manage_group_admin(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'group_id' => ['required'],
                'user_id' => ['required'],
                'gm_role' => ['required'],
            ];

            $customMessages = [
                'group_id.required' => "Group is required",
                'user_id.required' => "Member is required",
                'gm_role.required' => "Role is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            if ($find_record->group_owner_id == $request->user_id && strtolower($request->gm_role) == 'user') {
                return $this->respondResult("", 'You can not change group owner role', false, 200);
            }

            $find_member = GroupMembers::where('gm_group_id', $find_record->group_id)
                    ->where('gm_user_id', $request->user_id)
                    ->first();

            if (!empty($find_member)) {
                $find_member->gm_role = ucfirst($request->gm_role);
                $find_member->save();
            }

            return $this->respondResult("", "Group Member role updated");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function manage_group_privacy(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'group_id' => ['required'],
                'group_privacy' => ['required'],
            ];

            $customMessages = [
                'group_id.required' => "Group is required",
                'group_privacy.required' => "Group Privacy Status is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            $find_record->group_privacy = $request->group_privacy;
            $find_record->save();


            return $this->respondResult("", "Group privacy set as " . $request->group_privacy);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function remove_group_member(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'group_id' => ['required'],
                'user_id' => ['required'],
            ];

            $customMessages = [
                'group_id.required' => "Group is required",
                'user_id.required' => "Member is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            if ($find_record->group_owner_id == $request->user_id) {
                return $this->respondResult("", 'You can not remove group owner', false, 200);
            }

            $find_member = GroupMembers::where('gm_group_id', $find_record->group_id)
                    ->where('gm_user_id', $request->user_id)
                    ->first();

            if (!empty($find_member)) {
                $find_member->delete();
                $find_member->forceDelete();
            }

            return $this->respondResult("", "Group Member removed");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function leave_group(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'group_id' => ['required'],
                'user_id' => ['required'],
            ];

            $customMessages = [
                'group_id.required' => "Group is required",
                'user_id.required' => "Member is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            $is_owner = false;
            if ($find_record->group_owner_id == $request->user_id) {
                $is_owner = true;
                //return $this->respondResult("", 'You can not leave group as you are owner, You can DELETE this group..!', false, 200);
            }

            $find_member = GroupMembers::where('gm_group_id', $find_record->group_id)
                    ->where('gm_user_id', $request->user_id)
                    ->first();

            if (!empty($find_member)) {
                $find_member->delete();
                $find_member->forceDelete();

                if ($is_owner) {
                    $find_admin = GroupMembers::where('gm_group_id', $find_record->group_id)
                            ->where('gm_role', 'Admin')
                            ->first();

                    if (empty($find_admin)) {
                        $find_user = GroupMembers::where('gm_group_id', $find_record->group_id)
                                ->orderBy('gm_id', 'ASC')
                                ->first();
                        if (!empty($find_user)) {
                            $find_user->gm_role = 'Admin';
                            $find_user->save();
                        }
                    }
                }
            }

            return $this->respondResult("", "You are no longer participated in this group");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    private function send_added_into_group_push() {
        $process = new \Symfony\Component\Process\Process("php artisan send_added_into_group_push >>/dev/null 2>&1");
        $process->start();
    }

}
