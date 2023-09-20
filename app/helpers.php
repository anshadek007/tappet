<?php

use Illuminate\Support\Facades\Route;

/*
  |--------------------------------------------------------------------------
  | Detect Active Route
  |--------------------------------------------------------------------------
  |
  | Compare given route with current route and return output if they match.
  | Very useful for navigation, marking if the link is active.
  |
 */

function isActiveRoute($route, $output = "active") {
    $result = stripos(Route::current()->uri(), $route);
    if ($result !== false)
        return $output;
}

/*
  |--------------------------------------------------------------------------
  | Detect Api OR Web User
  |--------------------------------------------------------------------------
  |
  | Find if user is trying to register/log in through the api or the web.
  |
 */

function getAppUrl() {
    return env('APP_URL') . '/api/' . env('API_VERSION').'/';
}

function isWebUser() {
    return strpos(Route::current()->uri(), "api") === false;
}

function getAppVersion() {
    $request = request();
    $appversion = !empty($request->header('appversion')) ? str_replace('.', '', $request->header('appversion')) : 0;
    return $appversion;
}

function getLanguage() {
    $request = request();
    $lang = !empty(trim($request->header('Accept-Language'))) ? trim($request->header('Accept-Language')) : 'en';

    if ($lang == 'en' || $lang == 'ar' || $lang == 'en_ar') {
        return $lang;
    }
    return 'en';
}

function weekList() {
    $week_array = [
        1 => 'Sunday',
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
        7 => 'Saturday',
    ];
    return $week_array;
}

function my_asset($path) {
    return env('APP_URL', 'https://cdn.bkt.com/') . trim($path);
}

function upload_path($path = "") {
    return public_path('/uploads/' . trim($path));
}

/**
 * 
 * @param type $unix_date
 * @param type $now
 * @return type
 */
function time_ago($unix_date, $now = null) {

//        echo 'unix_date = '.$unix_date;
//        echo '<br>';
//        echo 'now ='.$now;die;

    $unix_date = strtotime($unix_date);
    $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths = array("60", "60", "24", "7", "4.35", "12", "10");

    if (!empty($now)) {
        $now = strtotime($now);
    } else {
        $now = time();
    }

// is it future date or past date
    if ($now > $unix_date) {
        $difference = $now - $unix_date;
        $tense = "ago";
    } else {
        $difference = $unix_date - $now;
        $tense = "from now";
    }
    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
        $difference /= $lengths[$j];
    }
    $difference = round($difference, 0, PHP_ROUND_HALF_UP);
//                $difference = round($difference,2);
    if ($difference != 1) {
        $periods[$j] .= "s";
    }
    return "$difference $periods[$j] {$tense}";
}

/**
 * 
 * @param type $array
 * @param type $request
 * @param type $page
 * @param type $perPage
 * @return \Illuminate\Pagination\LengthAwarePaginator
 */
function arrayPaginator($array, $request, $page = 1, $perPage = 10) {
    $offset = ($page * $perPage) - $perPage;
    return new Illuminate\Pagination\LengthAwarePaginator(array_slice($array, $offset, $perPage, true), count($array), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
}

function getNotificationType($type = "global") {
    $notifcationType = [
        "global" => 1,
        "like_profile" => 2,
        "like_job" => 3,
        "like_video" => 4,
        "upload_video" => 8
    ];
    return $notifcationType[$type];
}

function sendNotificationAndroid($fields, $fcm_key) {

    $url = 'https://fcm.googleapis.com/fcm/send';

    $headers = array(
        'Authorization: key=' . $fcm_key,
        'Content-Type: application/json'
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

function sendNotificationIos($userdeviceToken, $body = array(), $pem_file_path = IOS_PEM_PATH, $app_is_live = APP_IS_LIVE) {

    // Construct the notification payload
    if (!isset($body['aps']['sound'])) {
        $body['aps']['sound'] = "default";
    }

    // End of Configurable Items
    $payload = json_encode($body);
    $ctx = stream_context_create();
    $filename = $pem_file_path;
    if (!file_exists($filename)) {
        return true;
    }

    if (is_array($userdeviceToken) && count($userdeviceToken) > 0) {
        foreach ($userdeviceToken as $key => $token_rec_id) {
            stream_context_set_option($ctx, 'ssl', 'local_cert', $filename);
            if ($app_is_live == 'true')
                $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
            else
                $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

            if (!$fp) {
                continue;
            } else {
                if ($token_rec_id != '') {
                    $msg = chr(0) . pack("n", 32) . pack('H*', str_replace(' ', '', $token_rec_id)) . pack("n", strlen($payload)) . $payload;
                    fwrite($fp, $msg);
                }
            }
            fclose($fp);
        }
    } else {
        stream_context_set_option($ctx, 'ssl', 'local_cert', $filename);
        if ($app_is_live == 'true')
            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
        else
            $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

        if (!$fp) {
            return false;
            //    return "Failed to connect $err $errstr";
        } else {
            $token_rec_id = $userdeviceToken;
            if ($token_rec_id != '') {
                $msg = chr(0) . pack("n", 32) . pack('H*', str_replace(' ', '', $token_rec_id)) . pack("n", strlen($payload)) . $payload;
                fwrite($fp, $msg);
            }
        }
        fclose($fp);
    }
    return true;
    // END CODE FOR PUSH NOTIFICATIONS TO ALL USERS
}

if (!function_exists('getPhotoURL')) {

    function getPhotoURL($type, $id, $photoName) {
        $photoURL = '';
        switch ($type) {
            case 'admins':
                $img_folder = config('constants.UPLOAD_ADMINS_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/img/avatar/avatar-1.png');
                }
                break;
            case 'users':
                $img_folder = config('constants.UPLOAD_USERS_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/img/avatar/avatar-1.png');
                }
                break;
            case 'categories':
                $img_folder = config('constants.UPLOAD_CATEGORIES_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/no-image-placeholder.jpg');
                }
                break;
            case 'countries':
                $img_folder = config('constants.UPLOAD_COUNTRIES_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/default_flag.png');
                }
                break;
            case 'pet_types':
                $img_folder = config('constants.UPLOAD_PET_TYPES_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/no-image-placeholder.jpg');
                }
                break;    
            case 'pets':
                $img_folder = config('constants.UPLOAD_PETS_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/no-image-placeholder.jpg');
                }
                break;
            case 'groups':
                $img_folder = config('constants.UPLOAD_GROUPS_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/no-image-placeholder.jpg');
                }
                break;
            case 'events':
                $img_folder = config('constants.UPLOAD_EVENTS_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/no-image-placeholder.jpg');
                }
                break;
            case 'posts':
                $img_folder = config('constants.UPLOAD_POSTS_FOLDER');
                $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                    $photoURL = asset('uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                } else {
                    $photoURL = asset('assets/images/no-image-placeholder.jpg');
                }
                break;
            case 'advertisements':
                /* $img_folder = config('constants.UPLOAD_ADVERTISEMENTS_FOLDER');
                  $img_path = public_path("uploads/" . $img_folder . "/" . $id . "/" . $photoName);
                  if (!empty($id) && !empty($photoName) && file_exists($img_path)) {
                  $photoURL = asset('public/uploads/' . $img_folder . '/' . $id . '/' . $photoName);
                  } else {
                  $photoURL = asset('public/assets/img/example-image.jpg');
                  } */
                $photoURL = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/SouqBHApp/' . $id . '/' . $photoName;
                break;
        }

        return $photoURL;
    }

}


if (!function_exists('rmdir_recursive')) {

    function rmdir_recursive($dir) {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file)
                continue;
            if (is_dir("$dir/$file"))
                rmdir_recursive("$dir/$file");
            else
                unlink("$dir/$file");
        }

        rmdir($dir);
    }

}


// Function for generate random string for access token generate

if (!function_exists('str_rand_access_token')) {

    function str_rand_access_token($length = 32, $seeds = 'allalphanum') {
        // Possible seeds
        $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
        $seedings['numeric'] = '0123456789';
        $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
        $seedings['allalphanum'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwqyz0123456789';
        $seedings['upperalphanum'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $seedings['alphanumspec'] = 'abcdefghijklmnopqrstuvwqyz0123456789!@#$%^*-_=+';
        $seedings['alphacapitalnumspec'] = 'abcdefghijklmnopqrstuvwqyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#@!*-_';
        $seedings['hexidec'] = '0123456789abcdef';
        $seedings['customupperalphanum'] = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; //Confusing chars like 0,O,1,I not included
        // Choose seed
        if (isset($seedings[$seeds])) {
            $seeds = $seedings[$seeds];
        }

        // Seed generator
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float) $sec + ((float) $usec * 100000);
        mt_srand($seed);

        // Generate
        $str = '';
        $seeds_count = strlen($seeds);

        for ($i = 0; $length > $i; $i++) {
            $str .= $seeds{mt_rand(0, $seeds_count - 1)};
        }

        return $str;
    }

}