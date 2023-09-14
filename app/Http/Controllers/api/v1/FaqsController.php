<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Faqs;
use App\AESCrypt;

class FaqsController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function index(Request $request) {

        $request->merge([
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);

        $faqs = Faqs::select("faq_id", "faq_title", "faq_description");

        $total_faqs = $faqs->count();
        $faqs = $faqs->orderBy("faq_title", "ASC")->get();

        $faqs_list = array();
        $response = array();
        if (count($faqs) > 0) {
            foreach ($faqs as $faq) {

                $faqs_list[] = array("faq_id" => AESCrypt::encryptString($faq->faq_id),
                    "faq_title" => AESCrypt::encryptString($faq->faq_title),
                    "faq_description" => AESCrypt::encryptString($faq->faq_description)
                );
            }

            $message = AESCrypt::encryptString("Faqs found successfully.");
            $status = true;
        } else {
            $message = AESCrypt::encryptString("No faq found.");
            $status = false;
        }

        $response["result"] = $faqs_list;
        $response["total_faqs"] = $total_faqs;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

}
