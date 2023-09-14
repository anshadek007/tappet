<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Settings;
use App\AESCrypt;

class SettingsController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function index(Request $request) {

        $request->merge([
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);

        $filtered_request_array = Settings::where('s_status', 1)->get();
        $response = $fetch_record_list = array();

        if (!empty($filtered_request_array) && $filtered_request_array->count() > 0) {
            foreach ($filtered_request_array as $key => $value) {
                $fetch_record_list[] = array(
                    "s_id" => AESCrypt::encryptString($value->s_id),
                    "s_name" => AESCrypt::encryptString(strtolower($value->s_name)),
                    "s_value" => AESCrypt::encryptString($value->s_value)
                );
            }

            $message = AESCrypt::encryptString("Settings found successfully.");
            $status = true;
        } else {
            $message = AESCrypt::encryptString("No settings data found.");
            $status = false;
        }

        $response["result"] = $fetch_record_list;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

}
