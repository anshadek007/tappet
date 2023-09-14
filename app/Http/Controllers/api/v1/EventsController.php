<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Groups;
use App\GroupMembers;
use App\Events;
use App\EventImages;
use App\EventMembers;
use App\EventGroups;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;

class EventsController extends APIController {

    protected $userModel;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\User();
    }

    public function get_all_events(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $fetch_record = Events::select("*")
                    ->with(['images', 'addedBy', 'event_members', 'event_members.member','event_has_post'])
                    ->withCount('event_members')
                    ->where('event_status', 1)
                    ->join('event_members', 'event_id', 'em_event_id')
                    ->where('em_user_id', $id);

            if ($request->event_type == 'past') {
                $fetch_record = $fetch_record->where("event_start_date", '<', date('Y-m-d', time()))
                        ->where("event_end_date", '<', date('Y-m-d', time()));
            } else {
                $fetch_record = $fetch_record->where(function ($query) {
                    $query->where("event_end_date", '>', date('Y-m-d', time()))
                            ->orWhereNull("event_end_date");
                });
            }

            $fetch_record = $fetch_record->orderBy("event_id", "DESC")->get();

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                $message = "Events found successfully.";
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

    public function get_all_events_by_group_id(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            if (empty($request->group_id)) {
                return $this->respondResult("", 'Group ID not found', false, 200);
            }

            $find_record = Groups::find($request->group_id);

            if (!$find_record) {
                return $this->respondResult("", 'Group details not found', false, 200);
            }

            $group_id = (int) $request->group_id;

            $fetch_record = Events::select("*")
                    ->with(['images', 'addedBy', 'event_members', 'event_members.member'])
                    ->withCount('event_members')
                    ->where('event_status', 1)
                    ->join('event_groups', 'event_id', 'eg_event_id')
                    ->where('eg_group_id', $group_id);

            if ($request->event_type == 'past') {
                $fetch_record = $fetch_record->whereDate("event_start_date", '<', date('Y-m-d', time()))
                        ->whereDate("event_end_date", '<', date('Y-m-d', time()));
            } else {
                $fetch_record = $fetch_record->where(function ($query) {
                    $query->whereDate("event_end_date", '>', date('Y-m-d', time()))
                            ->orWhereNull("event_end_date");
                });
            }

            $fetch_record = $fetch_record->orderBy("event_id", "DESC")->get();

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                $message = "Events found successfully.";
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

    public function create_event(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'event_name' => ['required', 'max:100'],
                'event_start_date' => ['required'],
                'event_start_time' => ['required'],
            ];

            if (!empty($request->file('event_image'))) {
                $rules['event_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
            }

            $customMessages = [
                'event_name.required' => "Event Name is required",
                'event_start_date.required' => "Event Start Date is required",
                'event_start_time.required' => "Event Start Time is required",
                'event_name.max' => "Event Name allows maximum 100 characters only.",
                'event_image.image' => 'The type of the uploaded file should be an image.',
                'event_image.mimes' => 'The type of the uploaded file should be an image.',
                'event_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = new Events();
            $find_record->event_owner_id = Auth::user()->u_id;
            $find_record->event_name = $request->event_name;
            $find_record->event_start_date = $request->event_start_date;
            $find_record->event_start_time = $request->event_start_time;

            if (!empty($request->event_end_date)) {
                $find_record->event_end_date = $request->event_end_date;
            }
            if (!empty($request->event_end_time)) {
                $find_record->event_end_time = $request->event_end_time;
            }

            if (!empty($request->event_description)) {
                $find_record->event_description = $request->event_description;
            }
            if (!empty($request->event_location)) {
                $find_record->event_location = $request->event_location;
            }
            if (!empty($request->event_latitude)) {
                $find_record->event_latitude = $request->event_latitude;
            }
            if (!empty($request->event_longitude)) {
                $find_record->event_longitude = $request->event_longitude;
            }
            if (!empty($request->event_participants)) {
                $find_record->event_participants = $request->event_participants;
            }

            $find_record->event_image = "";
            $find_record->event_created_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('event_image'))) {
                    $fileName = $this->uploadFile($request->file('event_image'), $find_record->event_id, config('constants.UPLOAD_EVENTS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload event image, Try again..!");
                    }
                    $find_record->event_image = $fileName;
                    $find_record->save();
                }

                $event_invitation_users = [];
                $group_member = new EventMembers();
                $group_member->em_user_id = $id;
                $group_member->em_event_id = $find_record->event_id;
                $group_member->em_status = 1;
                $group_member->save();

                if (!empty($request->event_members)) {
                    foreach ($request->event_members as $value) {
                        $group_member = new EventMembers();
                        $group_member->em_user_id = $value;
                        $group_member->em_event_id = $find_record->event_id;
                        $group_member->em_status = 2;
                        $group_member->save();

                        $notification_data = new \App\Notification();
                        $notification_data->n_reciever_id = (int) $value;
                        $notification_data->n_sender_id = $id;
                        $notification_data->n_params = json_encode(["event_id" => $find_record->event_id, "event_name" => $find_record->event_name]);
                        $notification_data->n_message = $user->user_name . " invited to an event called";
                        $notification_data->n_notification_type = 4;
                        $notification_data->n_status = 2;
                        $notification_data->n_created_at = \Carbon\Carbon::now();
                        $notification_data->save();
                        $event_invitation_users[] = (int) $value;
                    }
                }

                if (!empty($request->event_groups)) {
                    foreach ($request->event_groups as $value) {
                        $group_member = new EventGroups();
                        $group_member->eg_group_id = $value;
                        $group_member->eg_event_id = $find_record->event_id;
                        $group_member->save();

                        if (!empty($group_member)) {
                            $get_group_data = Groups::with(['group_members'])->find($value);

                            if (!empty($get_group_data) && !empty($get_group_data->group_members)) {
                                foreach ($get_group_data->group_members as $group_value) {
                                    if ($id != $group_value->gm_user_id && !in_array($group_value->gm_user_id, $event_invitation_users)) {
                                        $group_member = new EventMembers();
                                        $group_member->em_user_id = $group_value->gm_user_id;
                                        $group_member->em_event_id = $find_record->event_id;
                                        $group_member->em_status = 2;
                                        $group_member->save();

                                        $notification_data = new \App\Notification();
                                        $notification_data->n_reciever_id = (int) $group_value->gm_user_id;
                                        $notification_data->n_sender_id = $id;
                                        $notification_data->n_params = json_encode(["event_id" => $find_record->event_id, "event_name" => $find_record->event_name]);
                                        $notification_data->n_message = $user->user_name . " invited to an event called";
                                        $notification_data->n_notification_type = 4;
                                        $notification_data->n_status = 2;
                                        $notification_data->n_created_at = \Carbon\Carbon::now();
                                        $notification_data->save();

                                        $event_invitation_users[] = (int) $group_value->gm_user_id;
                                    }
                                }
                            }
                        }
                    }
                }

                $this->send_added_into_event_push();

                $event = Events::with([
                            'images',
                            'event_members',
                            'event_members.member',
                            'event_groups',
                            'event_groups.group',
                            'event_groups.group.group_members',
                            'event_groups.group.group_members.member'
                        ])
                        ->select("*")
                        ->join('users', 'event_owner_id', 'u_id')
                        ->where('event_id', $find_record->event_id)
                        ->first();

                return $this->respondResult($event, "Event saved successfully");
            } else {
                return $this->respondResult("", 'Failed to save event details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function invite_friend_or_group(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $find_record = Events::find($request->event_id);
            if (!$find_record) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            $event_invitation_users = [];
            $event_invitation_users[] = (int) $id;
            if (!empty($find_record)) {
                if (!empty($request->event_members)) {
                    foreach ($request->event_members as $value) {
                        $event_invitation_users[] = (int) $value;
                        $group_member = EventMembers::where('em_event_id', $find_record->event_id)->where('em_user_id', $value)->first();
                        if (empty($group_member)) {
                            $group_member = new EventMembers();
                            $group_member->em_user_id = $value;
                            $group_member->em_event_id = $find_record->event_id;
                            $group_member->em_status = 2;
                            $group_member->save();

                            $notification_data = new \App\Notification();
                            $notification_data->n_reciever_id = (int) $value;
                            $notification_data->n_sender_id = $id;
                            $notification_data->n_params = json_encode(["event_id" => $find_record->event_id, "event_name" => $find_record->event_name]);
                            $notification_data->n_message = $user->user_name . " invited to an event called";
                            $notification_data->n_notification_type = 4;
                            $notification_data->n_status = 2;
                            $notification_data->n_created_at = \Carbon\Carbon::now();
                            $notification_data->save();
                        }
                    }
                }

                if (!empty($request->event_groups)) {
                    foreach ($request->event_groups as $value) {
                        $group_member = EventGroups::where('eg_event_id', $find_record->event_id)->where('eg_group_id', $value)->first();
                        if (empty($group_member)) {
                            $group_member = new EventGroups();
                            $group_member->eg_group_id = $value;
                            $group_member->eg_event_id = $find_record->event_id;
                            $group_member->save();

                            if (!empty($group_member)) {
                                $get_group_data = Groups::with(['group_members'])->find($value);

                                if (!empty($get_group_data) && !empty($get_group_data->group_members)) {
                                    foreach ($get_group_data->group_members as $group_value) {
                                        if ($id != $group_value->gm_user_id && !in_array($group_value->gm_user_id, $event_invitation_users)) {
                                            $group_member = EventMembers::where('em_event_id', $find_record->event_id)->where('em_user_id', $group_value->gm_user_id)->first();
                                            if (empty($group_member)) {
                                                $group_member = new EventMembers();
                                                $group_member->em_user_id = $group_value->gm_user_id;
                                                $group_member->em_event_id = $find_record->event_id;
                                                $group_member->em_status = 2;
                                                $group_member->save();

                                                $notification_data = new \App\Notification();
                                                $notification_data->n_reciever_id = (int) $group_value->gm_user_id;
                                                $notification_data->n_sender_id = $id;
                                                $notification_data->n_params = json_encode(["event_id" => $find_record->event_id, "event_name" => $find_record->event_name]);
                                                $notification_data->n_message = $user->user_name . " invited to an event called";
                                                $notification_data->n_notification_type = 4;
                                                $notification_data->n_status = 2;
                                                $notification_data->n_created_at = \Carbon\Carbon::now();
                                                $notification_data->save();

                                                $event_invitation_users[] = (int) $group_value->gm_user_id;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $this->send_added_into_event_push();

                return $this->respondResult("", "Invitation sent successfully");
            } else {
                return $this->respondResult("", 'Failed to send invitation, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function event_invitation_action(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'event_status' => ['required'],
            ];

            $customMessages = [
                'event_status.required' => "Event status is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Events::find($request->event_id);
            if (!$find_record) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            if (!empty($find_record)) {
                $group_member = EventMembers::where('em_event_id', $find_record->event_id)
                        ->where('em_user_id', $id)
                        ->first();

                if (!$group_member) {
                    return $this->respondResult("", 'Event invitation not found', false, 200);
                }

                if (!empty($request->event_status)) {
                    $group_member->em_status = (int) $request->event_status;
                    $group_member->save();

                    return $this->respondResult("", "Your status updated successfully");
                }
            } else {
                return $this->respondResult("", 'Failed to update invitation status, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function Crevent_invitation_action(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'event_status' => ['required'],
            ];

            $customMessages = [
                'event_status.required' => "Event status is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Events::find($request->event_id);
            if (!$find_record) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            if (!empty($find_record)) {
                $group_member = EventMembers::where('em_event_id', $find_record->event_id)
                        ->where('em_user_id', $id)
                        ->first();

                if ($group_member && !empty($request->event_status)) {
                    $group_member->em_status = $request->event_status;
                    $group_member->save();

                    return $this->respondResult("", "Your status updated successfully");
                } else {
                    $group_member = EventMembers::create([
                        'em_user_id' => $id,
                        'em_event_id' => $request->event_id,
                        'em_status' => $request->event_status,
                    ]);

                    return $this->respondResult("", "Your status updated successfully");
                }
            } else {
                return $this->respondResult("", 'Failed to update invitation status, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function delete_event(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $find_record = Events::find($request->event_id);
            if (!$find_record) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            if ($find_record->event_owner_id != $id) {
                return $this->respondResult("", 'You have no permission delete this Event', false, 200);
            }

            if (!empty($find_record)) {

                $find_record->delete();
                $find_record->forceDelete();

                return $this->respondResult("", "Event deleted successfully");
            } else {
                return $this->respondResult("", 'Failed to delete event, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function edit_event(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'event_id' => ['required'],
                'event_name' => ['required', 'max:100'],
                'event_start_date' => ['required'],
                'event_start_time' => ['required'],
            ];

            if (!empty($request->file('event_image'))) {
                $rules['event_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
            }

            $customMessages = [
                'event_id.required' => "Event ID is required",
                'event_name.required' => "Event Name is required",
                'event_start_date.required' => "Event Start Date is required",
                'event_start_time.required' => "Event Start Time is required",
                'event_name.max' => "Event Name allows maximum 100 characters only.",
                'event_image.image' => 'The type of the uploaded file should be an image.',
                'event_image.mimes' => 'The type of the uploaded file should be an image.',
                'event_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Events::find($request->event_id);
            if (!$find_record) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            if (!empty($request->event_name)) {
                $find_record->event_name = $request->event_name;
            }

            if (!empty($request->event_start_date)) {
                $find_record->event_start_date = $request->event_start_date;
            }

            if (!empty($request->event_start_time)) {
                $find_record->event_start_time = $request->event_start_time;
            }

            if (!empty($request->event_end_date)) {
                $find_record->event_end_date = $request->event_end_date;
            }

            if (!empty($request->event_end_time)) {
                $find_record->event_end_time = $request->event_end_time;
            }

            if (!empty($request->event_description)) {
                $find_record->event_description = $request->event_description;
            }

            if (!empty($request->event_location)) {
                $find_record->event_location = $request->event_location;
            }

            if (!empty($request->event_latitude)) {
                $find_record->event_latitude = $request->event_latitude;
            }

            if (!empty($request->event_longitude)) {
                $find_record->event_longitude = $request->event_longitude;
            }

            if (!empty($request->event_participants)) {
                $find_record->event_participants = $request->event_participants;
            }

            $find_record->event_created_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('event_image'))) {
                    $fileName = $this->uploadFile($request->file('event_image'), $find_record->event_id, config('constants.UPLOAD_EVENTS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload event image, Try again..!");
                    }
                    $find_record->event_image = $fileName;
                    $find_record->save();
                }

                $event = Events::with([
                            'images',
                            'event_members',
                            'event_members.member',
                            'event_groups',
                            'event_groups.group',
                            'event_groups.group.group_members',
                            'event_groups.group.group_members.member'
                        ])
                        ->select("*")
                        ->join('users', 'event_owner_id', 'u_id')
                        ->where('event_id', $find_record->event_id)
                        ->first();

                return $this->respondResult($event, "Event details updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Event details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_event_images(Request $request) {
        $id = Auth::user()->u_id;
        $user = $this->userModel->validateUser($id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $find_record = Events::find($request->event_id);

        if (!$find_record) {
            return $this->respondResult("", 'Event details not found', false, 200);
        }

        // Handle multiple file upload
        $images = $request->file('images');
        if (!empty($images)) {
            foreach ($images as $key => $image) {
                if (!empty($image) && !empty($request->file('images')[$key]) && $request->file('images')[$key]->isValid()) {
                    $image_name = 'images_' . rand(0, 999999) . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                    $destinationPath = public_path("/uploads/" . config('constants.UPLOAD_EVENTS_FOLDER') . "/" . $find_record->event_id);
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }
                    $image->move($destinationPath, $image_name);

                    $new_obj = new EventImages();
                    $new_obj->event_image_event_id = $find_record->event_id;
                    $new_obj->event_image_user_id = $id;
                    $new_obj->event_image_image = $image_name;
                    $new_obj->save();
                }
            }
        }
        return $this->respondResult("", "Event Picture added successfully.");
    }

    public function get_event_details(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            if (empty($request->event_id)) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            $find_record = Events::find($request->event_id);

            if (!$find_record) {
                return $this->respondResult("", 'Event details not found', false, 200);
            }

            $event_id = $request->event_id;

            $value = Events::with([
                        'images',
                        'event_members',
                        'event_members.member',
                        'event_has_post',
                        'event_groups',
                        'event_groups.group',
                        'event_groups.group.group_members',
                        'event_groups.group.group_members.member'
                    ])
                    ->select("*")
                    ->join('users', 'event_owner_id', 'u_id')
                    ->where('event_id', $event_id)
                    ->first();

            $fetch_record_list = array();
            $response = array();
            if (!empty($value)) {
                $value->friend_request = 0;
                $value->friend_request_sent_by_me = false;

                if (!empty($id)) {
                    $invites = 'SELECT
                        tappet_user_friends.*
                    FROM
                        tappet_user_friends
                    LEFT JOIN 
                        tappet_users 
                    ON 
                        `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id`
                    WHERE
                        (`ufr_user_id` = ' . $value->event_owner_id . ' AND `ufr_invited_user_id` = ' . $id . ') 
                        OR
                        (`ufr_user_id` = ' . $id . ' AND `ufr_invited_user_id` = ' . $value->event_owner_id . ')  
                    AND `u_status` != 9 
                    AND `ufr_status` != 9 
                    AND `ufr_deleted_at` IS NULL
                    AND `tappet_users`.`u_deleted_at` IS NULL
                    LIMIT 1';

                    $check_friend = \DB::select($invites);

                    if (!empty($check_friend) && count($check_friend) > 0) {
                        $value->friend_request = $check_friend[0]->ufr_status;
                        $value->friend_request_sent_by_me = $check_friend[0]->ufr_user_id == $id ? true : false;
                    }
                }
                $message = "Event details found successfully.";
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

    private function send_added_into_event_push() {
        $process = new \Symfony\Component\Process\Process("php artisan send_added_into_event_push >>/dev/null 2>&1");
        $process->start();
    }

}
