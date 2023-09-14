<?php

namespace App\Http\Controllers\api\v1;

use App\Notifications;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notification;
use App\AESCrypt;
use Illuminate\Support\Facades\Auth;
use App\Traits\PushNotifications;

class NotificationsController extends APIController {

    use PushNotifications;

    protected $userModel;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\User();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $user_id = Auth::user()->u_id;
        $user = $this->userModel->validateUser($user_id);
        $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
        $page = !empty($request->page) ? $request->page : 1;

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $get_all_records = Notification::where("n_reciever_id", $user_id)
                ->join('users', 'n_sender_id', 'u_id')
                ->select("*", \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"))
                ->where('n_status', '!=', 9)
                ->orderBy("n_id", "desc")
                ->paginate($limit);

        $notifications_list = array();
        $response = array();
        $read_notification_ids = array();
        if (count($get_all_records) > 0) {
            foreach ($get_all_records as $notification) {
                $user_name = "";
                if (!empty($notification->user_name)) {
                    $user_name = ucwords($notification->user_name);
                }

                $notifications_list[] = array(
                    "n_id" => $notification->n_id,
                    "n_sender_id" => $notification->n_sender_id,
                    "n_sender_name" => $user_name,
                    "n_sender_image" => $notification->u_image,
                    "n_params" => $notification->n_params,
                    "n_message" => $notification->n_message,
                    "n_notification_type" => $notification->n_notification_type,
                    "n_created_at" => $notification->n_created_at
                );
                if ($notification->n_status != 1) {
                    $read_notification_ids[] = $notification->n_id;
                }
            }

            if (!empty($read_notification_ids)) {
                Notification::whereIn('n_id', $read_notification_ids)->update(array('n_status' => 1));
            }

            $message = "Notifications found successfully.";
        } else {
            $message = "No notifications found.";
        }


        $pagination_data = [
            'total' => $get_all_records->total(),
            'lastPage' => $get_all_records->lastPage(),
            'perPage' => $get_all_records->perPage(),
            'currentPage' => $get_all_records->currentPage(),
            'currentPage' => $get_all_records->currentPage(),
        ];

        $response["pagination"] = $pagination_data;
        $response["result"] = $notifications_list;
        $response["message"] = $message;
        $response["status"] = true;

        return response()->json($response, 200);
    }

    public function send_chat_notifications(Request $request) {
        $user_id = Auth::user()->u_id;
        $user = $this->userModel->validateUser($user_id);

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        if (!empty($request->user_ids)) {
            $android_users = array();
            $ios_users = array();
            $user_ids = explode(',', $request->user_ids);
            foreach ($user_ids as $value) {
                $push_title = !empty($request->title) ? $request->title : "";
                $push_message = !empty($request->message) ? $request->message : "";
                $group_id = !empty($request->group_id) ? $request->group_id : null;

                $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                        ->join("user_device_tokens", "udt_u_id", "u_id")
                        ->where("udt_u_id", $value)
                        ->whereNotNull('udt_device_token')
                        ->where("u_status", 1)
                        ->latest()
                        ->first();


                if (!empty($token_data->udt_device_token)) {
                    $badge_count = Notification::where("n_reciever_id", $value)->where('n_status', 3)->count();
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
                                "sender_user_id" => (int) $user_id,
                                "user_id" => (int) $value,
                                "group_id" => (int) $group_id,
                                'type' => 7
                            )
                        );
                        $this->send_push_notification($message);
                    } else {
                        $android_users = $token_data->udt_device_token;
                        $message = array(
                            'priority' => 'high',
                            'registration_ids' => array($android_users),
                            'notification' => array(
                                'title' => $push_title,
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "sender_user_id" => (int) $user_id,
                                "user_id" => (int) $value,
                                "group_id" => (int) $group_id,
                                'type' => 7
                            )
                        );

                        $this->send_push_notification($message);
                    }
                }
            }

            $message = "Notifications sent successfully.";
        }


        $response["result"] = array();
        $response["message"] = $message;
        $response["status"] = true;

        return response()->json($response, 200);
    }

    /**
     * Remove all notifications for particular user from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $user_id = Auth::user()->u_id;
        $user = $this->userModel->validateUser($user_id);

        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        Notification::where('n_reciever_id', $user_id)->update(array('n_status' => 9));
        Notification::where("n_reciever_id", $user_id)->delete();
        $response = array(
            "status" => true,
            "message" => "Your all notifications are cleared."
        );

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function remove(Request $request) {
        $n_id = !empty($request->n_id) ? $request->n_id : "";
        if (empty($n_id)) {
            return $this->respondResult("", 'Invalid request parameters, Try again...!', false, 200);
        }
        Notification::where('n_id', $n_id)->update(array('n_status' => 9));
        Notification::where("n_id", $n_id)->delete();
        $response = array(
            "status" => true,
            "message" => "Notification removed successfully."
        );

        return response()->json($response, 200);
    }

}
