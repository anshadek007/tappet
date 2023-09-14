<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Countries;
use App\Cities;
use App\AESCrypt;

class CitiesController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function show($id = null, Request $request) {
        $request->merge([
            'type' => $request->type
        ]);

        $fetch_record = Cities::select("city_id", "city_name", "c_name")
                ->leftJoin('countries', 'c_id', 'city_country_id')
                ->where('city_status', 1);

        if ($request->type != 1) {
            $fetch_record = $fetch_record->where('city_country_id', $id);
        }

        $total_fetch_record = $fetch_record->count();
        $fetch_record = $fetch_record->orderBy("city_name", "ASC")->get();

        $fetch_record_list = array();
        $response = array();
        if (count($fetch_record) > 0) {
            foreach ($fetch_record as $record) {
                $fetch_record_list[] = array(
                    "city_id" => $record->city_id,
                    "city_name" => $record->city_name,
                    "country_name" => $record->c_name,
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
