<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Countries;
use App\AESCrypt;

class CountriesController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function index(Request $request) {
        $fetch_record = Countries::select("c_id", "c_name")->where('c_status', 1);

        $total_fetch_record = $fetch_record->count();
        $fetch_record = $fetch_record->orderBy("c_name", "ASC")->get();

        $fetch_record_list = array();
        $response = array();
        if (count($fetch_record) > 0) {
            foreach ($fetch_record as $record) {
                $fetch_record_list[] = array(
                    "c_id" => $record->c_id,
                    "c_name" => $record->c_name,
                );
            }

            $message = "";
            $status = true;
        } else {
            $message = "No data found.";
            $status = false;
        }

        $response["result"] = $fetch_record_list;
        $response["total_count"] = $total_fetch_record;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

}
