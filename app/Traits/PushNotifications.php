<?php

namespace App\Traits;

trait PushNotifications {

    public function send_push_notification($fields) {
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
            \Log::info(curl_error($ch));
            //throw new Exception("Curl failed: " . curl_error($http2ch));
        }
        
        //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //\Log::info($status);
        
        curl_close($ch);

        $resultArr = json_decode($result, true);
        
        //        \Log::info($resultArr);

        if (!empty($resultArr) && $resultArr['success'] == 1) {
            return true;
        }

        return true;
    }

    public function send_notification_android($registration_ids, $message, $fcm_key = '') {
        if (empty($fcm_key)) {
            $fcm_key = env("PUSH_ANDROID_KEY");
        }
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array(
            'Authorization: key=' . $fcm_key,
            'Content-Type: application/json'
        );

        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $message
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);

        $resultArr = json_decode($result, true);
        if ($resultArr['success'] == 1) {
            return true;
        }

        return false;
    }

    public function send_notification_ios($user_device_token, $body = array(), $pem_file_path = '', $app_is_live = '') {
        if (empty($pem_file_path)) {
            $pem_file_path = public_path() . "/uploads/pem/" . env("PUSH_IOS_PEM");
        }
        if (empty($app_is_live)) {
            $app_is_live = env("PUSH_IOS_ENV");
        }

        $push_pem_password = env("PUSH_IOS_PASS");
//        echo $pem_file_path;exit;
        //Setup stream (connect to Apple Push Server)
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $pem_file_path);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $push_pem_password);
        if ($app_is_live == 'true') {
            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
        } else {
            $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
        }
        stream_set_blocking($fp, 0);

        if (!$fp) {
            return TRUE;
        } else {
            $apple_expiry = time() + (90 * 24 * 60 * 60); //Keep push alive (waiting for delivery) for 90 days
            $device_token = '';
            //$ci = &get_instance();
            if (is_array($user_device_token)) {
                foreach ($user_device_token as $key => $value) {
                    $apple_identifier = $key;
                    $device_token = $value;

                    $payload = json_encode($body);
                    //Enhanced Notification
                    $msg = pack("C", 1) . pack("N", $apple_identifier) . pack("N", $apple_expiry) . pack("n", 32) . pack('H*', str_replace(' ', '', $device_token)) . pack("n", strlen($payload)) . $payload;
                    //SEND PUSH
                    fwrite($fp, $msg);
                }
            } else {
                $apple_identifier = 1;
                $device_token = $user_device_token;

                $payload = json_encode($body);
                //Enhanced Notification
                $msg = pack("C", 1) . pack("N", $apple_identifier) . pack("N", $apple_expiry) . pack("n", 32) . pack('H*', str_replace(' ', '', $device_token)) . pack("n", strlen($payload)) . $payload;
                //SEND PUSH
                fwrite($fp, $msg);
            }
            fclose($fp);
            //$res = send_feedback_request();
        }
        return true;
    }

    public function send_notification_for_add($data){

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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);

        if ($result === FALSE) {
            \Log::info(curl_error($ch));
        }
        curl_close($ch);

        $resultArr = json_decode($result, true);
        
        if (!empty($resultArr) && $resultArr['success'] == 1) {
            return true;
        }

        return true;

    }
}
