<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use App\Posts;
use App\PostImages;
use App\PostLikes;
use App\PostComments;
use App\Events;
use App\EventMembers;
use App\EventGroups;
use App\User;
use App\Groups;
use App\GroupMembers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\DB;

class PostsController extends APIController {

    protected $userModel;
    protected $allowPostExtension;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->userModel = new \App\User();
        $this->allowPostExtension = [
            'video', 'image', 'audio'
            //'video/avi','video/mpeg','video/quicktime',
            //'image/bmp','image/gif','image/ief','image/jpeg','image/png','image/webp',
        ];
    }

    public function get_all_posts(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $id = Auth::user()->u_id;

            //\DB::enableQueryLog();

            //            SELECT
            //  `tappet_posts`.`post_id`,
            //  `u_id`,
            //  `u_first_name`,
            //  `u_last_name`,
            //  `u_image`,
            //  tappet_events.*
            //FROM
            //  `tappet_posts`
            //INNER JOIN
            //  `tappet_users` ON `post_owner_id` = `u_id`
            //LEFT JOIN
            //  `tappet_user_friends` ON `u_id` = `ufr_invited_user_id` OR `u_id` = `ufr_user_id` AND `ufr_status` = 1
            //LEFT JOIN
            //  `tappet_group_members` ON `post_group_id` = `gm_group_id`
            //LEFT JOIN
            //  tappet_events ON `post_event_id` = event_id
            //LEFT JOIN
            //  `tappet_event_members` ON event_id = `em_event_id`
            //WHERE
            //   (
            //       (
            //           event_participants = 'Public' AND (`ufr_user_id` = 2 OR `ufr_invited_user_id` = 2 OR `post_owner_id` = 2 OR `gm_user_id` = 2)
            //       )
            //       OR
            //       (
            //           event_participants = 'Friends & Groups' AND (`post_owner_id` = 2 OR `gm_user_id` = 2 OR (em_user_id=2 AND em_status=1))
            //       )
            //   )    
            //  AND `post_status` = 1
            //  AND `u_status` = 1 
            //  AND `tappet_posts`.`post_deleted_at` IS NULL
            //GROUP BY
            //  `post_id`
            //ORDER BY
            //  `post_id` DESC
            //LIMIT 10 OFFSET 0

            $fetch_record = Posts::select('posts.*', "u_id", "u_first_name", "u_last_name", "u_image",
                    DB::raw("( SELECT ufr_status as friend_status FROM `tappet_user_friends`
                  WHERE (`ufr_user_id` = ".$id." AND `ufr_invited_user_id` = post_owner_id) OR (`ufr_user_id` = post_owner_id AND `ufr_invited_user_id` = ".$id.")
                  LIMIT 1) as friend"))
                    ->join('users', 'post_owner_id', 'u_id')
                    ->with(['post_images', 'post_comments', 'post_comments.user'])
                    ->withCount('post_is_liked')
                    ->withCount('post_likes')
                    ->leftJoin('user_friends', function ($join) {
                        $join->on('u_id', '=', 'ufr_invited_user_id')
                        ->orOn('u_id', '=', 'ufr_user_id')
                        ->where('ufr_status', 1);
                    })
                    ->leftJoin('group_members', function ($join) use($id) {
                        $join->on('post_group_id', 'gm_group_id');
                    })
                    ->leftJoin('events', function ($join) {
                        $join->on('post_event_id', 'event_id')
                        ->where('event_participants', 'Public');
                    })
                    ->where(function($query) use($id) {
                        $query->where('ufr_user_id', $id)
                        ->orWhere('ufr_invited_user_id', $id)
                        ->orWhere('post_owner_id', $id)
                        ->orWhere('gm_user_id', $id);
                        //->where('ufr_status', 1);
                    })
                    ->where('post_status', 1)
                    //->where('friend_status','!=',2)
                    ->where('u_status', 1)
                    ->orderBy('post_id', 'DESC')
                    ->groupBy('post_id');

            $fetch_record = $fetch_record->paginate($limit);

                    //    dd(\DB::getQueryLog());
                    //    dd(\DB::getQueryLog()[0]['query']);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as $value) {
                    
                    if (!empty($value->post_event_id) && !empty($value->event) && !empty($value->event->event_participants) && $value->event->event_participants == 'Friends & Groups') {
                        $check_event_status = EventMembers::where('em_event_id', $value->post_event_id)
                                ->where('em_user_id', $id)
                                ->where('em_status', 1)
                                ->count();
                        
//                        \DB::enableQueryLog();
                        $check_group_status = EventGroups::join('group_members','eg_group_id','=','gm_group_id')
                                ->where('eg_event_id', $value->post_event_id)
                                ->where('eg_group_id', $value->post_group_id)
                                ->where('gm_user_id', $id)
                                ->where('gm_status', 1)
                                ->count();
//                        dd(\DB::getQueryLog());
//                        dd($check_group_status);
                        if($check_event_status ==0 && $check_group_status==0){
                            continue;
                        }
                    }
                    
                    if (!empty($value->post_group_id)) {
                        
                        $check_group_status = GroupMembers::where('gm_group_id', $value->post_group_id)
                                ->where('gm_user_id', $value->post_owner_id)
                                ->where('gm_status', 1)
                                ->count();
                        
                        if($check_group_status==0){
                            continue;
                        }
                    }
                    
                     if(!empty($value->friend) && $value->friend==2){
                        continue;
                    }

                    $value->post_event = !empty($value->post_event_id) && !empty($value->event) ? $value->event : (object) array();
                    $value->post_group = !empty($value->group) ? $value->group : (object) array();
                    $value->post_liked_by_me = !empty($value->post_is_liked_count) ? true : false;

                    unset($value->post_image);
                    unset($value->event);
                    unset($value->friend);
                    unset($value->group);
                    unset($value->post_is_liked_count);
                    $value->u_image = url('/public/uploads/'.config('constants.UPLOAD_USERS_FOLDER').'/'.$value->u_id .'/'. $value->u_image);
                    
                    $fetch_record_list[] = $value;
                }
                $message = "Posts found successfully.";
            } else {
                $message = "No data found.";
            }

            $get_group_count = Groups::select("group_id", "group_owner_id", "group_name", "group_image", "group_description", "group_privacy")
                    ->withCount('group_members')
                    ->with(['group_last_two_members', 'group_last_two_members.member'])
                    ->join('group_members', 'group_id', 'gm_group_id')
                    ->where('gm_user_id', $id)
                    ->where('group_status', 1)
                    ->count();

            $response["group_count"] = $get_group_count;
            $response["pagination"] = $pagination_data;
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function create_post(Request $request) {
        try {

            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                // 'post_name' => ['required', 'max:255'],
                'post_type' => ['required'],
            ];

            $max_file_size = 5;
            $file_type = 'valid';
            //$post_type = explode(',', $request->post_type);
            //1=Photo, 2=Location, 3=Event, 4=Multiple Photos, 5=Audio, 6=Video
//            if (!empty($post_type) && count($post_type) > 0) {
//                if (in_array(1, $post_type) && !empty($request->file('post_image'))) {
//                    $rules['post_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
//                    $max_file_size = 5;
//                    $file_type = 'image';
//                }
//
//                if (in_array(5, $post_type) && !empty($request->file('post_image'))) {
//                    $rules['post_image'] = 'required|mimes:audio/mpeg,mpga,mp3,wav,aac,m4a,ogg|max:10128';
////                    $rules['post_image'] = 'required|mimes:application/octet-stream,audio/mpeg,mpga,mp3,wav,m4a,ogg|max:10128';
////                    $rules['post_image'] = 'required|mimes:mpga,wav|max:10128';
//                    $max_file_size = 10;
//                    $file_type = 'audio';
//                }
//
//                if (in_array(6, $post_type) && !empty($request->file('post_image'))) {
//                    $rules['post_image'] = 'required|max:20128';
////                    $rules['post_image'] = 'required|mimes:mp4,ogx,oga,ogv,ogg,webm,avi,mov,wmv|max:20128';
//                    $max_file_size = 20;
//                    $file_type = 'video';
//                }
//            }

            $customMessages = [
                'post_name.required' => "Post Name is required",
                'post_name.max' => "Post Name allows maximum 255 characters only.",
                'post_type.required' => "Post Type is required",
//                'post_image.image' => 'The type of the uploaded file should be an '.$file_type,
//                'post_image.mimes' => 'The type of the uploaded file should be an '.$file_type,
//                'post_image.max' => "The post file may not be greater than ".$max_file_size."MB."
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = new Posts();
            $find_record->post_owner_id = $id;

            $find_record->post_name = !empty($request->post_name) ? $request->post_name : "";

            if (!empty($request->post_type)) {
                $find_record->post_type = $request->post_type;
            }

            if (!empty($request->post_location)) {
                $find_record->post_location = $request->post_location;
            }
            if (!empty($request->post_latitude)) {
                $find_record->post_latitude = $request->post_latitude;
            }
            if (!empty($request->post_longitude)) {
                $find_record->post_longitude = $request->post_longitude;
            }
            if (!empty($request->post_event_id)) {
                $find_record->post_event_id = $request->post_event_id;
            }
            if (!empty($request->post_group_id)) {
                $find_record->post_group_id = $request->post_group_id;
            }

            $find_record->post_created_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
//                if (!empty($request->file('post_image'))) {
//                    $fileName = $this->uploadFile($request->file('post_image'), $find_record->post_id, config('constants.UPLOAD_POSTS_FOLDER'));
//                    if (!$fileName) {
//                        return $this->respondWithError("Failed to upload post image, Try again..!");
//                    }
//                    $find_record->post_image = $fileName;
//                    $find_record->save();
//                }
                // Handle multiple file upload
                $images = $request->file('images');
                if (!empty($images)) {
                    foreach ($images as $key => $image) {

                        if (strpos($image->getClientMimeType(), 'image') !== false || strpos($image->getClientMimeType(), 'video') !== false || strpos($image->getClientMimeType(), 'audio') !== false || strpos($image->getClientMimeType(), 'application/octet-stream') !== false) {
                            $post_media_type = "";
                            if (strpos($image->getClientMimeType(), 'image') !== false) {
                                $post_media_type = "image";
                            } else if (strpos($image->getClientMimeType(), 'video') !== false) {
                                $post_media_type = "video";
                            } else if (strpos($image->getClientMimeType(), 'audio') !== false || strpos($image->getClientMimeType(), 'application/octet-stream') !== false) {
                                $post_media_type = "audio";
                            }

                            if (!empty($image) && !empty($request->file('images')[$key]) && $request->file('images')[$key]->isValid()) {
                                $image_name = 'post_' . rand(0, 999999) . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                                $destinationPath = public_path("/uploads/" . config('constants.UPLOAD_POSTS_FOLDER') . "/" . $find_record->post_id);
                                if (!file_exists($destinationPath)) {
                                    mkdir($destinationPath, 0777, true);
                                }
                                $image->move($destinationPath, $image_name);

                                $new_obj = new PostImages();
                                $new_obj->post_image_post_id = $find_record->post_id;
                                $new_obj->post_image_image = $image_name;
                                $new_obj->post_media_type = $post_media_type;
                                $new_obj->save();
                            }
                        }
                    }
                }

                return $this->respondResult("", "Post saved successfully");
            } else {
                return $this->respondResult("", 'Failed to save post details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function edit_post(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'post_id' => ['required'],
//                'post_type' => ['required'],
            //    'post_name' => ['required', 'max:100'],
            ];

//            if (!empty($request->file('post_image'))) {
//                $rules['post_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
//            }

            $customMessages = [
                'post_id.required' => "Post ID is required",
                'post_name.required' => "Post Name is required",
                'post_name.max' => "Post Name allows maximum 255 characters only.",
                'post_type.required' => "Post Type is required",
                'post_image.image' => 'The type of the uploaded file should be an image.',
                'post_image.mimes' => 'The type of the uploaded file should be an image.',
                'post_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            $find_record->post_name = !empty($request->post_name) ? $request->post_name : "";

            $find_record->post_location = !empty($request->post_location) ? trim($request->post_location) : "";

            $find_record->post_latitude = !empty($request->post_latitude) ? trim($request->post_latitude) : "";

            $find_record->post_longitude = !empty($request->post_longitude) ? trim($request->post_longitude) : "";

            $find_record->post_event_id = !empty($request->post_event_id) ? trim($request->post_event_id) : "";
            $find_record->post_group_id = !empty($request->post_group_id) ? trim($request->post_group_id) : "";

            $find_record->post_updated_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
//                if (!empty($request->file('post_image'))) {
//                    $fileName = $this->uploadFile($request->file('post_image'), $find_record->post_id, config('constants.UPLOAD_POSTS_FOLDER'));
//                    if (!$fileName) {
//                        return $this->respondWithError("Failed to upload post image, Try again..!");
//                    }
//                    $find_record->post_image = $fileName;
//                    $find_record->save();
//                }
                // Handle multiple file upload
                $images = $request->file('images');
                if (!empty($images)) {
                    foreach ($images as $key => $image) {
                        if (strpos($image->getClientMimeType(), 'image') !== false || strpos($image->getClientMimeType(), 'video') !== false || strpos($image->getClientMimeType(), 'audio') !== false || strpos($image->getClientMimeType(), 'application/octet-stream') !== false) {
                            $post_media_type = "";
                            if (strpos($image->getClientMimeType(), 'image') !== false) {
                                $post_media_type = "image";
                            } else if (strpos($image->getClientMimeType(), 'video') !== false) {
                                $post_media_type = "video";
                            } else if (strpos($image->getClientMimeType(), 'audio') !== false || strpos($image->getClientMimeType(), 'application/octet-stream') !== false) {
                                $post_media_type = "audio";
                            }

                            if (!empty($image) && !empty($request->file('images')[$key]) && $request->file('images')[$key]->isValid()) {
                                $image_name = 'post_' . rand(0, 999999) . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                                $destinationPath = public_path("/uploads/" . config('constants.UPLOAD_POSTS_FOLDER') . "/" . $find_record->post_id);
                                if (!file_exists($destinationPath)) {
                                    mkdir($destinationPath, 0777, true);
                                }
                                $image->move($destinationPath, $image_name);

                                $new_obj = new PostImages();
                                $new_obj->post_image_post_id = $find_record->post_id;
                                $new_obj->post_image_image = $image_name;
                                $new_obj->post_media_type = $post_media_type;
                                $new_obj->save();
                            }
                        }
                    }
                }

                return $this->respondResult("", "Post details updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Post details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_post_details(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            if (empty($request->post_id)) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            $find_record = Posts::find($request->post_id);
            

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            $id = $request->post_id;
            
//            \DB::enableQueryLog();
            $value = Posts::select("*")
                    ->with(['addedBy', 'post_likes', 'post_likes.member','post_images', 'post_comments', 'post_comments.user'])
                    ->withCount('post_is_liked')
                    ->where('post_id', $id)
                    ->first();
//            dd(\DB::getQueryLog());
            
//            dd($value);

            $fetch_record_list = array();
            $response = array();
            if (!empty($value)) {
                foreach ($value->post_likes as &$post_value) {
                    $post_value->member->total_pets = Pets::where('pet_owner_id', $post_value->member->u_id)->where('pet_status', 1)->count();
                    $post_value->member->has_total_friends_count = $this->userModel->has_total_friends_count($post_value->member->u_id);
                }
                $value->post_liked_by_me = !empty($value->post_is_liked_count) ? true : false;
                $value->post_event = !empty($value->post_event_id) && !empty($value->event) ? $value->event : (object) array();
                $value->post_group = !empty($value->group) ? $value->group : (object) array();
                unset($value->event);
                unset($value->group);
                $message = "Post details found successfully.";
            } else {
                $message = "No data found.";
            }

            $response["result"] = $value;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function delete_post(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if ($find_record->post_owner_id != $id) {
                return $this->respondResult("", 'You can not delete post as you are not owner', false, 200);
            }

            if (!empty($find_record)) {
                $find_record->delete();
                $find_record->forceDelete();
            }

            return $this->respondResult("", "Post deleted succesfully");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function delete_pet_image(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $find_record = Pets::find($request->pet_id);
            if (!$find_record) {
                return $this->respondResult("", 'Pet details not found', false, 200);
            }

            if (!empty($find_record)) {

                $find_image = PetImages::find($request->pi_id);
                if (!$find_image) {
                    return $this->respondResult("", 'Pet image details not found', false, 200);
                }

                $find_image->delete();
                $find_image->forceDelete();

                return $this->respondResult("", "Pet image deleted successfully");
            } else {
                return $this->respondResult("", 'Failed to delete pet image, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function delete_post_media(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
                'post_image_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
                'post_image_id.required' => "Post media is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {
                $find_image = PostImages::find($request->post_image_id);
                if (!$find_image) {
                    return $this->respondResult("", 'Post media not found', false, 200);
                }

                $find_image->delete();
                $find_image->forceDelete();
            }

            return $this->respondResult("", "Post media deleted succesfully");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function like_or_unlike_post(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {

                $favourite = !empty($request->favourite) && $request->favourite == 1 ? $request->favourite : 2;

                $fetch_existing_data = PostLikes::where("post_like_post_id", $request->post_id)
                        ->where("post_like_user_id", $id)
                        ->first();

                if (!empty($fetch_existing_data) && $favourite == 2) {
                    $fetch_existing_data->delete();
                } else if ($favourite == 1 && empty($fetch_existing_data)) {
                    $add_record = array(
                        "post_like_user_id" => $id,
                        "post_like_post_id" => $request->post_id,
                    );

                    PostLikes::create($add_record);
                }

                if ($favourite == 1) {
                    $n_message = $user->first_name . ' ' . $user->last_name . ' liked a post you shared.';
                    $this->send_post_like_push($find_record->post_owner_id, $id, $n_message, 5, 2, $user, $request->post_id);
                    $message = "Post has been liked";
                } else {
                    $message = "Post has been unliked";
                }
            }

            return $this->respondResult("", $message);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_comment(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
                'post_comment_text' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
                'post_comment_text.required' => "Comment text is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {

                $add_record = array(
                    "post_comment_user_id" => $id,
                    "post_comment_post_id" => $request->post_id,
                    "post_comment_text" => $request->post_comment_text,
                );

                PostComments::create($add_record);

                if(!empty($find_record->post_owner_id) && $find_record->post_owner_id !=$id){
                    $n_message = $user->first_name . ' ' . $user->last_name . ' commented on a post you shared.';
                    $this->send_post_comment_push($find_record->post_owner_id, $id, $n_message, 6, 2, $user, $request->post_id);
                }
            }

            return $this->respondResult("", "Comment added successfully");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_all_post_comments(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::select("*")
                    ->withCount('post_likes')
                    ->where('post_id', $request->post_id)
                    ->where('post_status', 1)
                    ->first();

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {
                $fetch_comments = PostComments::select("post_comment_id", 'post_comment_text', 'post_comment_created_at', 'u_id', 'u_first_name', 'u_last_name', 'u_image')
                        ->join('users', 'post_comment_user_id', 'u_id')
                        ->where('post_comment_post_id', $request->post_id)
                        ->orderBy("post_comment_id", "ASC")
                        ->get();

                $fetch_likes = PostLikes::select('u_id', 'u_first_name', 'u_last_name', 'u_image')
                        ->join('users', 'post_like_user_id', 'u_id')
                        ->where('post_like_post_id', $request->post_id)
                        ->orderBy("post_like_id", "DESC")
                        ->limit(2)
                        ->get();

                $response = array();
                if (count($fetch_comments) > 0) {
                    $message = "Comments found successfully.";
                } else {
                    $message = "No comments found.";
                }

                $find_record->comment = $fetch_comments;
                $find_record->likes = !empty($fetch_likes) ? $fetch_likes : (object) array();
            }

            $response["result"] = $find_record;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_all_post_likes(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::select("*")
                    ->withCount('post_likes')
                    ->where('post_id', $request->post_id)
                    ->where('post_status', 1)
                    ->first();

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {
                $fetch_likes = PostLikes::select('u_id', 'u_first_name', 'u_last_name', 'u_image')
                        ->join('users', 'post_like_user_id', 'u_id')
                        ->where('post_like_post_id', $request->post_id)
                        ->orderBy("post_like_id", "DESC")
                        ->get();

                $response = array();
                if (count($fetch_likes) > 0) {
                    $message = "Likes found successfully.";
                } else {
                    $message = "No one like this Post.";
                }

                $find_record->likes = !empty($fetch_likes) ? $fetch_likes : (object) array();
            }

            $response["result"] = $find_record;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    private function send_added_into_post_push() {
        $process = new \Symfony\Component\Process\Process("php artisan send_added_into_post_push >>/dev/null 2>&1");
        $process->start();
    }

    /**
     *
     * @param type $n_reciever_id
     * @param type $n_sender_id
     * @param type $n_message
     * @param type $n_notification_type
     * @param type $n_status
     * @param type $user
     */
    private function send_post_like_push($n_reciever_id, $n_sender_id, $n_message, $n_notification_type, $n_status, $user, $post_id) {
        if (!empty($n_reciever_id) && !empty($n_sender_id) && !empty($n_message) && !empty($n_notification_type) && !empty($n_status) && !empty($user) && !empty($post_id)) {
            $receiver = User::find($n_reciever_id);
            $notification_data = new \App\Notification();
            $notification_data->n_reciever_id = $n_reciever_id;
            $notification_data->n_sender_id = $n_sender_id;
            $notification_data->n_params = json_encode(["u_id" => $user->u_id, 'post_id' => $post_id]);
            $notification_data->n_message = $n_message;
            $notification_data->n_notification_type = $n_notification_type;
            $notification_data->n_status = $receiver->u_post_like_notification == 2 ? 3 : $n_status;
            $notification_data->n_created_at = Carbon::now();
            if ($notification_data->save() && $receiver->u_post_like_notification == 1) {
                $process = new \Symfony\Component\Process\Process("php artisan send_post_like_push $notification_data->n_id >>/dev/null 2>&1");
                $process->start();
            }
        }
    }

    /**
     *
     * @param type $n_reciever_id
     * @param type $n_sender_id
     * @param type $n_message
     * @param type $n_notification_type
     * @param type $n_status
     * @param type $user
     */
    private function send_post_comment_push($n_reciever_id, $n_sender_id, $n_message, $n_notification_type, $n_status, $user, $post_id) {
        if (!empty($n_reciever_id) && !empty($n_sender_id) && !empty($n_message) && !empty($n_notification_type) && !empty($n_status) && !empty($user) && !empty($post_id)) {
            $receiver = User::find($n_reciever_id);
            $notification_data = new \App\Notification();
            $notification_data->n_reciever_id = $n_reciever_id;
            $notification_data->n_sender_id = $n_sender_id;
            $notification_data->n_params = json_encode(["u_id" => $user->u_id, 'post_id' => $post_id]);
            $notification_data->n_message = $n_message;
            $notification_data->n_notification_type = $n_notification_type;
            $notification_data->n_status = $receiver->u_post_comment_notification == 2 ? 3 : $n_status;
            $notification_data->n_created_at = Carbon::now();
            if ($notification_data->save() && $receiver->u_post_comment_notification == 1) {
                $process = new \Symfony\Component\Process\Process("php artisan send_post_comment_push $notification_data->n_id >>/dev/null 2>&1");
                $process->start();
            }
        }
    }

    // Corporate Post

    public function create_post_corporate(Request $request) {
        try {

            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_name' => ['required', 'max:255'],
                'post_type' => ['required'],
            ];

            $max_file_size = 5;
            $file_type = 'valid';
            if (!empty($request->post_type)) {
                if ($request->post_type == 'Photo' && !empty($request->file('post_image'))) {
                    $rules['post_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
                    $max_file_size = 5;
                    $file_type = 'image';
                }

                if ($request->post_type == 'Audio' && !empty($request->file('post_image'))) {
                    $rules['post_image'] = 'required|mimes:audio/mpeg,mpga,mp3,wav,aac,m4a,ogg|max:10128';
//                    $rules['post_image'] = 'required|mimes:application/octet-stream,audio/mpeg,mpga,mp3,wav,m4a,ogg|max:10128';
//                    $rules['post_image'] = 'required|mimes:mpga,wav|max:10128';
                    $max_file_size = 10;
                    $file_type = 'audio';
                }

                if ($request->post_type == 'Video' && !empty($request->file('post_image'))) {
                    $rules['post_image'] = 'required|max:20128';
//                    $rules['post_image'] = 'required|mimes:mp4,ogx,oga,ogv,ogg,webm,avi,mov,wmv|max:20128';
                    $max_file_size = 20;
                    $file_type = 'video';
                }
            }

            $customMessages = [
                'post_name.required' => "Post Name is required",
                'post_name.max' => "Post Name allows maximum 255 characters only.",
                'post_type.required' => "Post Type is required",
                'post_image.image' => 'The type of the uploaded file should be an ' . $file_type,
                'post_image.mimes' => 'The type of the uploaded file should be an ' . $file_type,
                'post_image.max' => "The post file may not be greater than " . $max_file_size . "MB."
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = new Posts();
            $find_record->post_owner_id = $id;

            $find_record->post_name = $request->post_name;

            if (!empty($request->post_type)) {
                $find_record->post_type = $request->post_type;
            }

            if (!empty($request->post_location)) {
                $find_record->post_location = $request->post_location;
            }
            if (!empty($request->post_latitude)) {
                $find_record->post_latitude = $request->post_latitude;
            }
            if (!empty($request->post_longitude)) {
                $find_record->post_longitude = $request->post_longitude;
            }
            if (!empty($request->post_event_id)) {
                $find_record->post_event_id = $request->post_event_id;
            }

            $find_record->post_created_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('post_image'))) {
                    $fileName = $this->uploadFile($request->file('post_image'), $find_record->post_id, config('constants.UPLOAD_POSTS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload post image, Try again..!");
                    }
                    $find_record->post_image = $fileName;
                    $find_record->save();
                }

                // Handle multiple file upload
                $images = $request->file('images');
                if (!empty($images)) {
                    foreach ($images as $key => $image) {
                        if (!empty($image) && !empty($request->file('images')[$key]) && $request->file('images')[$key]->isValid()) {
                            $image_name = 'images_' . rand(0, 999999) . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                            $destinationPath = public_path("/uploads/" . config('constants.UPLOAD_POSTS_FOLDER') . "/" . $find_record->post_id);
                            if (!file_exists($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                            $image->move($destinationPath, $image_name);

                            $new_obj = new PostImages();
                            $new_obj->post_image_post_id = $find_record->post_id;
                            $new_obj->post_image_image = $image_name;
                            $new_obj->save();
                        }
                    }
                }

                return $this->respondResult("", "Post saved successfully");
            } else {
                return $this->respondResult("", 'Failed to save post details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function edit_post_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $rules = [
                'post_id' => ['required'],
                'post_type' => ['required'],
                'post_name' => ['required', 'max:100'],
            ];

            if (!empty($request->file('post_image'))) {
                $rules['post_image'] = 'required|mimes:jpeg,jpg,png|max:5098';
            }

            $customMessages = [
                'post_id.required' => "Post ID is required",
                'post_name.required' => "Post Name is required",
                'post_name.max' => "Post Name allows maximum 255 characters only.",
                'post_type.required' => "Post Type is required",
                'post_image.image' => 'The type of the uploaded file should be an image.',
                'post_image.mimes' => 'The type of the uploaded file should be an image.',
                'post_image.uploaded' => 'Failed to upload an image. The image maximum size is 5MB.'
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($request->post_name)) {
                $find_record->post_name = $request->post_name;
            }

            if (!empty($request->post_type)) {
                $find_record->post_type = $request->post_type;
            }

            if (!empty($request->post_location)) {
                $find_record->post_location = $request->post_location;
            }
            if (!empty($request->post_latitude)) {
                $find_record->post_latitude = $request->post_latitude;
            }
            if (!empty($request->post_longitude)) {
                $find_record->post_longitude = $request->post_longitude;
            }
            if (!empty($request->post_event_id)) {
                $find_record->post_event_id = $request->post_event_id;
            }

            $find_record->post_updated_at = Carbon::now();
            $find_record->save();

            if (!empty($find_record)) {
                if (!empty($request->file('post_image'))) {
                    $fileName = $this->uploadFile($request->file('post_image'), $find_record->post_id, config('constants.UPLOAD_POSTS_FOLDER'));
                    if (!$fileName) {
                        return $this->respondWithError("Failed to upload post image, Try again..!");
                    }
                    $find_record->post_image = $fileName;
                    $find_record->save();
                }

                // Handle multiple file upload
                $images = $request->file('images');
                if (!empty($images)) {
                    foreach ($images as $key => $image) {
                        if (!empty($image) && !empty($request->file('images')[$key]) && $request->file('images')[$key]->isValid()) {
                            $image_name = 'images_' . rand(0, 999999) . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                            $destinationPath = public_path("/uploads/" . config('constants.UPLOAD_POSTS_FOLDER') . "/" . $find_record->post_id);
                            if (!file_exists($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                            $image->move($destinationPath, $image_name);

                            $new_obj = new PostImages();
                            $new_obj->post_image_post_id = $find_record->post_id;
                            $new_obj->post_image_image = $image_name;
                            $new_obj->save();
                        }
                    }
                }

                return $this->respondResult("", "Post details updated successfully");
            } else {
                return $this->respondResult("", 'Failed to update Post details, Please try again!!', false, 200);
            }
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function delete_post_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if ($find_record->post_owner_id != $id) {
                return $this->respondResult("", 'You can not delete post as you are not owner', false, 200);
            }

            if (!empty($find_record)) {
                $find_record->delete();
                $find_record->forceDelete();
            }

            return $this->respondResult("", "Post deleted succesfully");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function like_or_unlike_post_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {

                $favourite = !empty($request->favourite) && $request->favourite == 1 ? $request->favourite : 2;

                $fetch_existing_data = PostLikes::where("post_like_post_id", $request->post_id)
                        ->where("post_like_user_id", $id)
                        ->first();

                if (!empty($fetch_existing_data) && $favourite == 2) {
                    $fetch_existing_data->delete();
                } else if ($favourite == 1 && empty($fetch_existing_data)) {
                    $add_record = array(
                        "post_like_user_id" => $id,
                        "post_like_post_id" => $request->post_id,
                    );

                    PostLikes::create($add_record);
                }

                if ($favourite == 1) {
                    $n_message = $user->first_name . ' ' . $user->last_name . ' liked a post you shared.';
                    $this->send_post_like_push($find_record->post_owner_id, $id, $n_message, 5, 2, $user, $request->post_id);
                    $message = "Post has been liked";
                } else {
                    $message = "Post has been unliked";
                }
            }

            return $this->respondResult("", $message);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function add_comment_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
                'post_comment_text' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
                'post_comment_text.required' => "Comment text is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::find($request->post_id);

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {

                $add_record = array(
                    "post_comment_user_id" => $id,
                    "post_comment_post_id" => $request->post_id,
                    "post_comment_text" => $request->post_comment_text,
                );

                PostComments::create($add_record);

                if(!empty($find_record->post_owner_id) && $find_record->post_owner_id !=$id){
                    $n_message = $user->first_name . ' ' . $user->last_name . ' commented on a post you shared.';
                    $this->send_post_comment_push($find_record->post_owner_id, $id, $n_message, 6, 2, $user, $request->post_id);
                }
            }

            return $this->respondResult("", "Comment added successfully");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_all_post_comments_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::select("*")
                    ->withCount('post_likes')
                    ->where('post_id', $request->post_id)
                    ->where('post_status', 1)
                    ->first();

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {
                $fetch_comments = PostComments::select("post_comment_id", 'post_comment_text', 'post_comment_created_at', 'u_id', 'u_first_name', 'u_last_name', 'u_image')
                        ->join('users', 'post_comment_user_id', 'u_id')
                        ->where('post_comment_post_id', $request->post_id)
                        ->orderBy("post_comment_id", "ASC")
                        ->get();

                $fetch_likes = PostLikes::select('u_id', 'u_first_name', 'u_last_name', 'u_image')
                        ->join('users', 'post_like_user_id', 'u_id')
                        ->where('post_like_post_id', $request->post_id)
                        ->orderBy("post_like_id", "DESC")
                        ->limit(2)
                        ->get();

                $response = array();
                if (count($fetch_comments) > 0) {
                    $message = "Comments found successfully.";
                } else {
                    $message = "No comments found.";
                }

                $find_record->comment = $fetch_comments;
                $find_record->likes = !empty($fetch_likes) ? $fetch_likes : (object) array();
            }

            $response["result"] = $find_record;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_all_post_likes_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $id = Auth::user()->u_id;

            $rules = [
                'post_id' => ['required'],
            ];

            $customMessages = [
                'post_id.required' => "Post is required",
            ];

            $validator = Validator::make($request->all(), $rules, $customMessages);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first());
            }

            $find_record = Posts::select("*")
                    ->withCount('post_likes')
                    ->where('post_id', $request->post_id)
                    ->where('post_status', 1)
                    ->first();

            if (!$find_record) {
                return $this->respondResult("", 'Post details not found', false, 200);
            }

            if (!empty($find_record)) {
                $fetch_likes = PostLikes::select('u_id', 'u_first_name', 'u_last_name', 'u_image')
                        ->join('users', 'post_like_user_id', 'u_id')
                        ->where('post_like_post_id', $request->post_id)
                        ->orderBy("post_like_id", "DESC")
                        ->get();

                $response = array();
                if (count($fetch_likes) > 0) {
                    $message = "Likes found successfully.";
                } else {
                    $message = "No one like this Post.";
                }

                $find_record->likes = !empty($fetch_likes) ? $fetch_likes : (object) array();
            }

            $response["result"] = $find_record;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_all_posts_corporate(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $id = Auth::user()->u_id;

            // \DB::enableQueryLog();
            $fetch_record = Posts::select('posts.*', "u_id", "u_first_name", "u_last_name", "u_image", "u_corporation_name")
                    ->join('users', 'post_owner_id', 'u_id')
                    ->with(['post_images', 'post_comments', 'post_comments.user'])
                    ->withCount('post_is_liked')
                    ->withCount('post_likes')
                    ->leftJoin('user_friends', function ($join) {
                        $join->on('u_id', '=', 'ufr_invited_user_id')
                        ->orOn('u_id', '=', 'ufr_user_id')
                        ->where('ufr_status', 1);
                    })
                    ->where(function($query) use($id) {
                        $query->where('ufr_user_id', $id)
                        ->orWhere('ufr_invited_user_id', $id)
                        ->orWhere('post_owner_id', $id);
                        //->where('ufr_status', 1);
                    })
                    ->where('post_status', 1)
                    ->where('u_status', 1)
                    ->orderBy('post_id', 'DESC')
                    ->groupBy('post_id');

            $fetch_record = $fetch_record->paginate($limit);

            //    dd(\DB::getQueryLog()[0]['query']);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as $value) {
                    $value->post_event = !empty($value->event) ? $value->event : (object) array();
                    $value->post_liked_by_me = !empty($value->post_is_liked_count) ? true : false;

                    unset($value->event);
                    unset($value->post_is_liked_count);
                    $fetch_record_list[] = $value;
                }
                $message = "Posts found successfully.";
            } else {
                $message = "No data found.";
            }

            $get_group_count = Groups::select("group_id", "group_owner_id", "group_name", "group_image", "group_description", "group_privacy")
                    ->withCount('group_members')
                    ->with(['group_last_two_members', 'group_last_two_members.member'])
                    ->join('group_members', 'group_id', 'gm_group_id')
                    ->where('gm_user_id', $id)
                    ->where('group_status', 1)
                    ->count();

            $response["group_count"] = $get_group_count;
            $response["pagination"] = $pagination_data;
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    public function get_all_users_posts(Request $request) {
        try {
            $user = $this->userModel->validateUser(Auth::user()->u_id);
            if (!$user) {
                return $this->respondResult("", 'User Not Found', false, 200);
            }

            $limit = !empty($request->limit) ? $request->limit : config('constants.DEFAULT_PAGINATION_LIMIT');
            $page = !empty($request->page) ? $request->page : 1;
            $offset = ($page - 1) * $limit;

            $fetch_record = Posts::select('posts.*', "u_id", "u_first_name", "u_last_name", "u_image")
                    ->join('users', 'post_owner_id', 'u_id')
                    ->with(['post_images', 'post_comments', 'post_comments.user'])
                    ->withCount('post_is_liked')
                    ->withCount('post_likes')
                    ->leftJoin('user_friends', function ($join) {
                        $join->on('u_id', '=', 'ufr_invited_user_id')
                        ->orOn('u_id', '=', 'ufr_user_id')
                        ->where('ufr_status', 1);
                    })
                    ->where('post_status', 1)
                    ->where('u_status', 1)
                    ->orderBy('post_id', 'DESC')
                    ->groupBy('post_id');

            $fetch_record = $fetch_record->paginate($limit);

            $pagination_data = [
                'total' => $fetch_record->total(),
                'lastPage' => $fetch_record->lastPage(),
                'perPage' => $fetch_record->perPage(),
                'currentPage' => $fetch_record->currentPage(),
            ];

            $fetch_record_list = array();
            $response = array();
            if (count($fetch_record) > 0) {
                foreach ($fetch_record as $value) {
                    $value->post_event = !empty($value->event) ? $value->event : (object) array();
                    $value->post_liked_by_me = !empty($value->post_is_liked_count) ? true : false;

                    unset($value->event);
                    unset($value->post_is_liked_count);
                    $fetch_record_list[] = $value;
                }
                $message = "Posts found successfully.";
            } else {
                $message = "No data found.";
            }

            $get_group_count = Groups::select("group_id", "group_owner_id", "group_name", "group_image", "group_description", "group_privacy")
                    ->withCount('group_members')
                    ->with(['group_last_two_members', 'group_last_two_members.member'])
                    ->join('group_members', 'group_id', 'gm_group_id')
                    ->where('group_status', 1)
                    ->count();

            $response["group_count"] = $get_group_count;
            $response["pagination"] = $pagination_data;
            $response["result"] = $fetch_record_list;
            $response["message"] = $message;
            $response["status"] = true;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

}
