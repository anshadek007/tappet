<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Categories;
use App\AESCrypt;

class CategoriesController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function allcategoriesList(Request $request) {

        $request->merge([
            'device_type' => AESCrypt::decryptString($request->device_type),
            'type' => AESCrypt::decryptString($request->type)
        ]);

        $categories = Categories::select("c_id", "c_name", "c_color", "c_is_eco", "c_image");

        if ($request->type == '2') {
            $categories = $categories->join('tours', 'tour_category_id', 'c_id')
                    ->where("tour_status", 1);
        }

        $categories = $categories->where("c_status", 1)
                ->groupBy('c_id')
                ->orderBy("c_order", "ASC")
                ->orderBy("c_name", "ASC")
                ->get();

        $categories_list = array();
        $response = array();
        if (count($categories) > 0) {
            foreach ($categories as $category) {
                $image = "";
                if (strpos(getPhotoURL('categories', $category->c_id, $category->c_image), "uploads") !== false) {
                    $image = str_replace(url('/public/uploads/') . "/", "", getPhotoURL('categories', $category->c_id, $category->c_image));
                }

                $categories_list[] = array(
                    "c_id" => AESCrypt::encryptString($category->c_id),
                    "c_name" => AESCrypt::encryptString($category->c_name),
                    "c_color" => AESCrypt::encryptString($category->c_color),
                    "c_is_eco" => $category->c_is_eco == 1 ? AESCrypt::encryptString(1) : AESCrypt::encryptString(2),
                    "c_image" => AESCrypt::encryptString($image),
                );
            }

            $message = AESCrypt::encryptString("Categories found successfully.");
            $status = true;
        } else {
            $message = AESCrypt::encryptString("No category found.");
            $status = false;
        }

        $response["result"] = $categories_list;
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

}
