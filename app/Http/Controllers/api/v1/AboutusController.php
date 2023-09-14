<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Aboutus;
use App\AESCrypt;

class AboutusController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function index(Request $request) {

        $request->merge([
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);

        $fetch_record = Aboutus::select("a_id", "a_title", "a_description");

        $total_fetch_record = $fetch_record->count();
        $fetch_record = $fetch_record->orderBy("a_title", "ASC")->get();

        $fetch_record_list = array();
        $response = array();
        if (count($fetch_record) > 0) {
            foreach ($fetch_record as $record) {

                $fetch_record_list[] = array("a_id" => AESCrypt::encryptString($record->a_id),
                    "a_title" => AESCrypt::encryptString($record->a_title),
                    "a_description" => AESCrypt::encryptString($record->a_description)
                );
            }

            $message = AESCrypt::encryptString("About us found successfully.");
            $status = true;
        } else {
            $message = AESCrypt::encryptString("No data found.");
            $status = false;
        }

        $response["result"] = $fetch_record_list;
        $response["total_count"] = $total_fetch_record;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

}
