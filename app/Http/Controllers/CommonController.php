<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\PushNotifications;
use App\Notification;
use App\Settings;
use App\User;
use Illuminate\Support\Facades\Log;

class CommonController extends Controller {

    use PushNotifications;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        
    }

    public function send_added_into_group_push() {
        $get_notification_data = Notification::where(\DB::raw("DATE(n_created_at)"), date('Y-m-d', time()))
                ->where('n_notification_type', 3)
                ->where('n_status', 2)
                ->get();

        if ($get_notification_data) {
            $android_users = array();
            $ios_users = array();
            foreach ($get_notification_data as $value) {
                $push_content = json_decode($value->n_params);
                $push_message = $value->n_message;
                $noty_type = $value->n_notification_type;

                $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                        ->join("user_device_tokens", "udt_u_id", "u_id")
                        ->where("udt_u_id", $value->n_reciever_id)
                        ->whereNotNull('udt_device_token')
                        ->where("u_status", 1)
                        ->orderBy('udt_id', 'DESC')
                        ->first();

                if (!empty($token_data->udt_device_token)) {
                    $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                    if ($token_data->udt_device_type == "ios") {
                        $ios_users = $token_data->udt_device_token;
                        $message = array(
                            'content_available' => true,
                            'priority' => 'high',
                            'to' => $ios_users,
                            'notification' => array(
                                'title' => "Added you on group",
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "user_id" => $value->n_reciever_id,
                                "group_id" => $push_content->group_id,
                                "group_name" => $push_content->group_name,
                                'type' => $noty_type
                            )
                        );

                        $this->send_push_notification($message);
                    } else {
                        $android_users = $token_data->udt_device_token;
                        $message = array(
                            'priority' => 'high',
                            'registration_ids' => array($android_users),
                            'notification' => array(
                                'title' => "Added you on group",
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "user_id" => $value->n_reciever_id,
                                "group_id" => $push_content->group_id,
                                "group_name" => $push_content->group_name,
                                'type' => $noty_type
                            )
                        );

                        $this->send_push_notification($message);
                    }
                }
                $value->n_status = 3;
                $value->update();
            }
        }
    }

    public function send_added_into_event_push() {
        $get_notification_data = Notification::where(\DB::raw("DATE(n_created_at)"), date('Y-m-d', time()))
                ->where('n_notification_type', 4)
                ->where('n_status', 2)
                ->get();

        if ($get_notification_data) {
            $android_users = array();
            $ios_users = array();
            foreach ($get_notification_data as $value) {
                $push_content = json_decode($value->n_params);
                $push_message = $value->n_message;
                $noty_type = $value->n_notification_type;

                $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type", 'u_event_notification')
                        ->join("user_device_tokens", "udt_u_id", "u_id")
                        ->where("udt_u_id", $value->n_reciever_id)
                        ->whereNotNull('udt_device_token')
                        ->where("u_status", 1)
                        ->orderBy('udt_id', 'DESC')
                        ->first();


                if (!empty($token_data->udt_device_token) && $token_data->u_event_notification == 1) {
                    $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                    if ($token_data->udt_device_type == "ios") {
                        $ios_users = $token_data->udt_device_token;
                        $message = array(
                            'content_available' => true,
                            'priority' => 'high',
                            'to' => $ios_users,
                            'notification' => array(
                                'title' => "Invited you on an event",
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "user_id" => $value->n_reciever_id,
                                "event_id" => $push_content->event_id,
                                "event_name" => $push_content->event_name,
                                'type' => $noty_type
                            )
                        );

                        $this->send_push_notification($message);
                    } else {
                        $android_users = $token_data->udt_device_token;
                        $message = array(
                            'priority' => 'high',
                            'registration_ids' => array($android_users),
                            'notification' => array(
                                'title' => "Invited you on an event",
                                'body' => (string) $push_message,
                            ),
                            'data' => array(
                                'unread_count' => (int) $badge_count + 1,
                                'sound' => 'Default',
                                "user_id" => $value->n_reciever_id,
                                "event_id" => $push_content->event_id,
                                "event_name" => $push_content->event_name,
                                'type' => $noty_type
                            )
                        );

                        $this->send_push_notification($message);
                    }
                }
                $value->n_status = 3;
                $value->update();
            }
        }
    }

    public function send_friend_invite_push($n_id) {
        $value = \App\Notification::where("n_id", $n_id)->first();
        if ($value) {
            $android_users = array();
            $ios_users = array();

            $push_content = json_decode($value->n_params);
            $push_message = $value->n_message;
            $noty_type = $value->n_notification_type;

            $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                    ->join("user_device_tokens", "udt_u_id", "u_id")
                    ->where("udt_u_id", $value->n_reciever_id)
                    ->where("u_status", 1)
                    ->whereNotNull('udt_device_token')
                    ->orderBy('udt_id', 'DESC')
                    ->first();

            if (!empty($token_data->udt_device_token)) {
                $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                if ($token_data->udt_device_type == "ios") {
                    $ios_users = $token_data->udt_device_token;
                    $message = array(
                        'content_available' => true,
                        'priority' => 'high',
                        'to' => $ios_users,
                        'notification' => array(
                            'title' => "New friend request",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "sender_user_id" => $value->n_sender_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                } else {
                    $android_users = $token_data->udt_device_token;
                    $message = array(
                        'priority' => 'high',
                        'registration_ids' => array($android_users),
                        'notification' => array(
                            'title' => "New friend request",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "sender_user_id" => $value->n_sender_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                }
            }
            $value->n_status = 3;
            $value->update();
        }
    }

    public function send_friend_invite_accept_push($n_id) {
        $value = \App\Notification::where("n_id", $n_id)->first();
        if ($value) {
            $android_users = array();
            $ios_users = array();

            $push_content = json_decode($value->n_params);
            $push_message = $value->n_message;
            $noty_type = $value->n_notification_type;

            $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                    ->join("user_device_tokens", "udt_u_id", "u_id")
                    ->where("udt_u_id", $value->n_reciever_id)
                    ->where("u_status", 1)
                    ->whereNotNull('udt_device_token')
                    ->orderBy('udt_id', 'DESC')
                    ->first();

            if (!empty($token_data->udt_device_token)) {
                $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                if ($token_data->udt_device_type == "ios") {
                    $ios_users = $token_data->udt_device_token;
                    $message = array(
                        'content_available' => true,
                        'priority' => 'high',
                        'to' => $ios_users,
                        'notification' => array(
                            'title' => "Friend request accepted",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "sender_user_id" => $value->n_sender_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                } else {
                    $android_users = $token_data->udt_device_token;
                    $message = array(
                        'priority' => 'high',
                        'registration_ids' => array($android_users),
                        'notification' => array(
                            'title' => "Friend request accepted",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "sender_user_id" => $value->n_sender_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                }
            }
            $value->n_status = 3;
            $value->update();
        }
    }
    
    public function send_friend_invite_reject_push($n_id) {
        $value = \App\Notification::where("n_id", $n_id)->first();
        if ($value) {
            $android_users = array();
            $ios_users = array();

            $push_content = json_decode($value->n_params);
            $push_message = $value->n_message;
            $noty_type = $value->n_notification_type;

            $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                    ->join("user_device_tokens", "udt_u_id", "u_id")
                    ->where("udt_u_id", $value->n_reciever_id)
                    ->where("u_status", 1)
                    ->whereNotNull('udt_device_token')
                    ->orderBy('udt_id', 'DESC')
                    ->first();

            if (!empty($token_data->udt_device_token)) {
                $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                if ($token_data->udt_device_type == "ios") {
                    $ios_users = $token_data->udt_device_token;
                    $message = array(
                        'content_available' => true,
                        'priority' => 'high',
                        'to' => $ios_users,
                        'notification' => array(
                            'title' => "Friend request rejected",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "sender_user_id" => $value->n_sender_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                } else {
                    $android_users = $token_data->udt_device_token;
                    $message = array(
                        'priority' => 'high',
                        'registration_ids' => array($android_users),
                        'notification' => array(
                            'title' => "Friend request rejected",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "sender_user_id" => $value->n_sender_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                }
            }
            $value->n_status = 3;
            $value->update();
        }
    }

    public function send_post_like_push($n_id) {
        $value = \App\Notification::where("n_id", $n_id)->first();
        if ($value) {
            $android_users = array();
            $ios_users = array();

            $push_content = json_decode($value->n_params);
            $push_message = $value->n_message;
            $noty_type = $value->n_notification_type;

            $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                    ->join("user_device_tokens", "udt_u_id", "u_id")
                    ->where("udt_u_id", $value->n_reciever_id)
                    ->where("u_status", 1)
                    ->whereNotNull('udt_device_token')
                    ->orderBy('udt_id', 'DESC')
                    ->first();

            if (!empty($token_data->udt_device_token)) {
                $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                if ($token_data->udt_device_type == "ios") {
                    $ios_users = $token_data->udt_device_token;
                    $message = array(
                        'content_available' => true,
                        'priority' => 'high',
                        'to' => $ios_users,
                        'notification' => array(
                            'title' => "New likes on your post",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "post_id" => $push_content->post_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                } else {
                    $android_users = $token_data->udt_device_token;
                    $message = array(
                        'priority' => 'high',
                        'registration_ids' => array($android_users),
                        'notification' => array(
                            'title' => "New likes on your post",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "post_id" => $push_content->post_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                }
            }
            $value->n_status = 3;
            $value->update();
        }
    }

    public function send_post_comment_push($n_id) {
        $value = \App\Notification::where("n_id", $n_id)->first();
        if ($value) {
            $android_users = array();
            $ios_users = array();

            $push_content = json_decode($value->n_params);
            $push_message = $value->n_message;
            $noty_type = $value->n_notification_type;

            $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                    ->join("user_device_tokens", "udt_u_id", "u_id")
                    ->where("udt_u_id", $value->n_reciever_id)
                    ->where("u_status", 1)
                    ->whereNotNull('udt_device_token')
                    ->orderBy('udt_id', 'DESC')
                    ->first();

            if (!empty($token_data->udt_device_token)) {
                $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                if ($token_data->udt_device_type == "ios") {
                    $ios_users = $token_data->udt_device_token;
                    $message = array(
                        'content_available' => true,
                        'priority' => 'high',
                        'to' => $ios_users,
                        'notification' => array(
                            'title' => "New comment on post",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "post_id" => $push_content->post_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                } else {
                    $android_users = $token_data->udt_device_token;
                    $message = array(
                        'priority' => 'high',
                        'registration_ids' => array($android_users),
                        'notification' => array(
                            'title' => "New comment on post",
                            'body' => (string) $push_message,
                        ),
                        'data' => array(
                            'unread_count' => (int) $badge_count + 1,
                            'sound' => 'Default',
                            "user_id" => $value->n_reciever_id,
                            "post_id" => $push_content->post_id,
                            'type' => $noty_type
                        )
                    );

                    $this->send_push_notification($message);
                }
            }
            $value->n_status = 3;
            $value->update();
        }
    }

    public function send_push($id) {
        file_put_contents('log.txt', "");
        file_put_contents('log.txt', "inn");
        file_put_contents('log.txt', "Last date =" . $id, FILE_APPEND | LOCK_EX);
        $get_notification_data = \App\NotificationsData::where("nd_id", $id)->first();
        if ($get_notification_data) {
            $push_content = $get_notification_data->nd_content;
            $push_target = $get_notification_data->nd_target;

            $ios_payload = array();
            $ios_payload['aps']['icon'] = 'appicon';
            $ios_payload['aps']['vibrate'] = 'true';
            $ios_payload['aps']['badge'] = 0;
            $ios_payload['aps']['sound'] = "default";
            $ios_payload['aps']['alert'] = (string) $push_content;
            $ios_payload['aps']['type'] = 1;

            $android_payload = array();
            $android_payload['android']['icon'] = 'appicon';
            $android_payload['android']['vibrate'] = 'true';
            $android_payload['android']['badge'] = 1;
            $android_payload['android']['sound'] = "default";
            $android_payload['android']['message'] = (string) $push_content;
            $android_payload['android']['type'] = 1;

            $users_device_tokens_data = \App\User::join("user_device_tokens", "udt_u_id", "u_id")->where("u_status", 1)->select("u_id", "udt_device_token", "udt_device_type");
            if ($push_target == 2) {
                $users_device_tokens_data = $users_device_tokens_data->where("udt_device_type", "android");
            } elseif ($push_target == 3) {
                $users_device_tokens_data = $users_device_tokens_data->where("udt_device_type", "ios");
            }
            $users_device_tokens_data = $users_device_tokens_data->get();
            //dd($users_device_tokens_data);exit;
            $android_users = array();
            $ios_users = array();
            foreach ($users_device_tokens_data as $token_data) {
                if (!empty($token_data->udt_device_token)) {
                    if ($token_data->udt_device_type == "ios") {
                        $ios_users[] = $token_data->udt_device_token;
                    } else {
                        $android_users[] = $token_data->udt_device_token;
                    }
                }

                $notification = new \App\Notification();
                $notification->n_reciever_id = $token_data->u_id;
                $notification->n_nd_id = $get_notification_data->nd_id;
                $notification->save();

                $android_payload['android']['id'] = $notification->n_id;
                $android_payload['android']['date'] = date("Y-m-d H:i:s");
            }
            //echo "<pre>";print_r($ios_users);exit;
            if (!empty($android_users)) {
                $this->send_notification_android($android_users, $android_payload);
            }
            if (!empty($ios_users)) {
                foreach ($ios_users as $token) {
                    $this->send_notification_ios($token, $ios_payload);
                }
            }
        }
    }

    public function send_pending_push($date) {
        $date = $date . " 00:00:00";

        Log::info("-----------------------------------------------------------------------------------------------");
        Log::info("------------------------------- send_pending_push :" . $date . " ------------------------------");
        Log::info("-----------------------------------------------------------------------------------------------");

        $get_notification_data = Notification::where("n_created_at", '>=', $date)->where('n_status', 2)->get();

        Log::info("get_notification_data = " . json_encode($get_notification_data));

        //dd($get_notification_data);

        if ($get_notification_data) {
            $android_users = array();
            $ios_users = array();
            foreach ($get_notification_data as $value) {
                $push_content = $value->n_params;
                $push_message = $value->n_message;
                $noty_type = $value->n_notification_type;

                $token_data = \App\User::select("u_id", "udt_device_token", "udt_device_type")
                        ->join("user_device_tokens", "udt_u_id", "u_id")
                        ->where("udt_u_id", $value->n_reciever_id)
                        ->where("u_status", 1)
                        ->orderBy('udt_id', 'DESC')
                        ->first();

                Log::info("token_data = " . json_encode($token_data));


                if (!empty($token_data->udt_device_token)) {
                    $badge_count = Notification::where("n_reciever_id", $value->n_reciever_id)->where('n_status', 3)->count();
                    if ($token_data->udt_device_type == "ios") {
                        $ios_users = $token_data->udt_device_token;
                        $ios_payload = array();
                        $ios_payload['aps']['icon'] = 'appicon';
                        $ios_payload['aps']['vibrate'] = 'true';
                        $ios_payload['aps']['badge'] = (int) $badge_count + 1;
                        $ios_payload['aps']['sound'] = "default";
                        $ios_payload['aps']['alert'] = (string) $push_message;
                        $ios_payload['aps']['type'] = $noty_type;
                        $ios_payload['aps']['data'] = $push_content;

                        Log::info("ios_users = " . $ios_users);
                        Log::info("ios_payload = " . json_encode($ios_payload));

                        $this->send_notification_ios($ios_users, $ios_payload);
                    } else {
                        $android_users = $token_data->udt_device_token;
                        $android_payload = array();
                        $android_payload['android']['icon'] = 'appicon';
                        $android_payload['android']['vibrate'] = 'true';
                        $android_payload['android']['badge'] = (int) $badge_count + 1;
                        $android_payload['android']['sound'] = "default";
                        $android_payload['android']['message'] = (string) $push_message;
                        $android_payload['android']['type'] = $noty_type;
                        $android_payload['android']['data'] = $push_content;
//                        $this->send_notification_android(array($android_users), $android_payload);
                    }
                }
                $value->n_status = 3;
                $value->update();
            }
        }

        Log::info("-----------------------------------------------------------------------------------------------");
        Log::info("------------------------------- send_pending_push :" . $date . " END ------------------------------");
        Log::info("-----------------------------------------------------------------------------------------------");
    }

    public function test_ios_push($token) {
        $message = array(
            'content_available' => true,
            'priority' => 'high',
            'to' => $token,
            'notification' => array(
                'title' => "Test push title",
                'body' => "Test push body",
            ),
            'data' => array(
                'unread_count' => (int) 1,
                'sound' => 'Default',
                "user_id" => 1,
                "group_id" => 1,
                "group_name" => "Test",
                'type' => 1
            )
        );

        $this->send_push_notification($message);
        echo "Push sent successfully";die;
    }

    public function test_android_push($token) {
        $message = array(
            'priority' => 'high',
            'registration_ids' => array($token),
            'notification' => array(
                'title' => "Test push title",
                'body' => "Test push body",
            ),
            'data' => array(
                'unread_count' => (int) 1,
                'sound' => 'Default',
                "user_id" => 1,
                "group_id" => 1,
                "group_name" => "Test",
                'type' => 1
            )
        );

        $this->send_push_notification($message);
        
        echo "Push sent successfully";die;
    }

}
