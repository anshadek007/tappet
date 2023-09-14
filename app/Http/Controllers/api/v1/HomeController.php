<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Categories;
use App\AESCrypt;

class HomeController extends APIController {

    public function __construct(Request $request) {
        parent::__construct($request);
    }

    public function categories(Request $request) {
        
        $request->merge([
            'page' => AESCrypt::decryptString($request->page),
            'popular' => AESCrypt::decryptString($request->popular),
            'searchkey' => AESCrypt::decryptString($request->searchkey),
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);

        $popular = !empty($request->popular) ? intval($request->popular) : '';
        $searchkey = $request->searchkey;
        $limit = 5;
        $page = isset($request->page) ? intval($request->page) : 1;
        $offset = ($page - 1) * $limit;
        
        $categories = Categories::select("c_id", "c_name", "c_name_ar", "c_description", "c_image");

        if (!empty($searchkey)) {
            
            $search_field = 'c_name';
            if(getLanguage() == "ar"){
                $search_field = 'c_name_ar';
            }
            
            $categories = $categories->where($search_field, "LIKE", "%" . $searchkey . "%");
        }

        $categories = $categories->where("c_status", 1)
                ->where("c_parent_id", NULL);

        $total_categories = $categories->count();

        if ($popular == 1) {
            $categories = $categories->orderBy("c_popular", "DESC")->orderBy("c_created_at", "DESC");
        } else {
            $categories = $categories->orderBy("c_created_at", "DESC");
        }

        $categories = $categories->offset($offset)
                ->limit($limit)
                ->get();

        /*$categories = $categories->get();*/
        
        $total_page = ceil(intval($total_categories) / intval($limit));
        $categories_list = array();
        $response = array();
        if (count($categories) > 0) {
            foreach ($categories as $category) {
                //$sub_categories = $category->subCategory->take(config('constants.SUBCATEGORY_COUNT'));
                $sub_categories = $category->subCategory;
                
                $subcategories_list = array();
                foreach ($sub_categories as $sub_category) {
                    
                    $subcatgory_name = $sub_category->c_name;
                    if(getLanguage() == "ar"){
                        $subcatgory_name = $sub_category->c_name_ar;
                    }
                    
                    $subcategory_image = getPhotoURL('categories', $sub_category->c_id, $sub_category->c_image);
                    $subcategory_image = str_replace(url('/public/uploads/')."/", "", $subcategory_image);
                    $subcategories_list[] = array("c_id" => AESCrypt::encryptString($sub_category->c_id),
                                                  "c_name" => AESCrypt::encryptString($subcatgory_name),
                                                  "c_image" => AESCrypt::encryptString($subcategory_image),
                                                  "brand_count" => count($sub_category->Brands)
                                                 );
                }

                $catgory_name = $category->c_name;
                if(getLanguage() == "ar"){
                    $catgory_name = $category->c_name_ar;
                }
                
                $category_image = getPhotoURL('categories', $category->c_id, $category->c_image);
                $category_image = str_replace(url('/public/uploads/')."/", "", $category_image);
                $categories_list[] = array("c_id" => AESCrypt::encryptString($category->c_id),
                                           "c_name" => AESCrypt::encryptString($catgory_name),
                                           "c_image" => AESCrypt::encryptString($category_image),
                                           "sub_categories" => $subcategories_list
                                        );
            }

            $message = AESCrypt::encryptString("Categories found successfully.");
            $status = true;
        } else {
            $message = AESCrypt::encryptString("No category found.");
            $status = false;
        }

        $banners = Banner::select("b_id","b_image")->get()->take(5);

        $banners_list = array();
        foreach ($banners as $banner) {
            $banner_image = getPhotoURL('banners', $banner->b_id, $banner->b_image);
            $banner_image = str_replace(url('/public/uploads/')."/", "", $banner_image);
            $banners_list[] = array("image"=>AESCrypt::encryptString($banner_image),
                                    "url"=>AESCrypt::encryptString($banner_image)
                                   );
        }
        
        //Get max price from storage
        $adv_maxprice = Advertisements::max('adv_price');
        
        $response["result"] = $categories_list;

        if ($page == 1) {
            $response["banners"] = $banners_list;
        }

        $response["per_page"] = $limit;
        $response["total_pages"] = $total_page;
        $response["current_page"] = intval($page);
        $response["adv_maxprice"] = AESCrypt::encryptString($adv_maxprice);
        $response["message"] = $message;
        $response["status"] = $status;

        return response()->json($response, 200);
    }

}
