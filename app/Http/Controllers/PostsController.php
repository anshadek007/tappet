<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Posts;
use App\PostComments;
use App\PostLikes;
use App\PostImages;

class PostsController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'posts';
        $this->module_singular_name = 'Post';
        $this->module_plural_name = 'Posts';
        $this->middleware("checkmodulepermission", ['except' => ['delete_comment','delete_post']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view($this->route_name . ".index");
    }

    public function load_data_in_table(Request $request) {

        $page = !empty($request->get("start")) ? $request->get("start") : 0;
        $rows = !empty($request->get("length")) ? $request->get("length") : 10;
        $draw = !empty($request->get("draw")) ? $request->get("draw") : 1;

        $sidx = !empty($request->get("order")[0]['column']) ? $request->get("order")[0]['column'] : 0;
        $sord = !empty($request->get("order")[0]['dir']) ? $request->get("order")[0]['dir'] : 'ASC';

        $name = !empty($request->get("name")) ? $request->get("name") : '';
        $status = !empty($request->get("status")) ? $request->get("status") : '';

        if ($sidx == 0) {
            $sidx = 'post_name';
        } else if ($sidx == 1) {
            $sidx = 'u_user_name';
        } else if ($sidx == 2) {
            $sidx = 'post_location';
        } else if ($sidx == 3) {
            $sidx = 'post_type';
        } else if ($sidx == 4) {
            $sidx = 'post_status';
        } else {
            $sidx = 'post_id';
        }

        $list_query = Posts::select("*", \DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`) as u_user_name"))
                ->leftJoin('users', 'u_id', 'post_owner_id');

        if (!empty($name)) {
            $list_query = $list_query->where(function ($query) use ($name) {
                $query->orWhere('post_name', 'like', '%' . $name . '%')
                        ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $name . '%');
            });
        }

        if (!empty($status)) {
            $list_query = $list_query->where("post_status", $status);
        }

        $total_rows = $list_query->count();
        $all_records = array();
        if ($total_rows > 0) {
            $list_of_all_data = $list_query->skip($page)
                    ->orderBy($sidx, $sord)
                    ->take($rows)
                    ->get();
            $index = 0;
            foreach ($list_of_all_data as $value) {
//                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->post_id) . ' class="font-weight-600"><img src=' . $value->post_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"><label class="mt-1"> ' . $value->post_name . '</label></a>';
                $post_name = !empty($value->post_name) ? $value->post_name : " - - - ";
                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->post_id) . '>' . $post_name . '</a>';
                $all_records[$index]['user_name'] = '<a href=' . route("users.show", $value->u_id) . ' class="font-weight-600"><img src=' . $value->u_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"><label class="mt-1"> ' . $value->u_user_name . '</label></a>';
                $all_records[$index]['post_location'] = !empty($value->post_location) ? $value->post_location : " - - - ";
//                $all_records[$index]['post_type'] = $value->post_type;

                $checked = '';
                if ($value->post_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2"><input type="checkbox" ' . $checked . ' data-id="' . $value->post_id . '" class="change_status custom-switch-input"><span class="custom-switch-indicator"></span></label>';
                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->post_id) . '" class="btn btn-light">Edit</a>';
                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->post_id . '">Delete</button>';

                $index++;
            }
        }
        $response = array();
        $response['draw'] = (int) $draw;
        $response['recordsTotal'] = (int) $total_rows;
        $response['recordsFiltered'] = (int) $total_rows;
        $response['data'] = $all_records;
        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        return view($this->route_name . ".add");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $request->validate([
            "post_name" => "required|unique:" . $this->route_name . ",post_name,NULL,post_id,post_deleted_at,NULL|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }

        $add_new_category = array(
            "post_name" => $request->get("post_name"),
        );

        $added_category = Posts::create($add_new_category);
        if ($added_category) {
            $category_id = $added_category->post_id;
            $image_name = "";
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);

                $added_category->post_image = $image_name;
                $added_category->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Added Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Posts::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->post_status = $status;
            $find_record->save();

            $response['success'] = true;
            $response['message'] = "Status changed successfully";
        }

        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Posts $post) {
        return view($this->route_name . ".show", compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Posts  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Posts $post) {
        return view($this->route_name . ".edit")->with(array("post" => $post));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Posts  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Posts $post) {
        $request->validate([
//            "post_name" => "required|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }
        $post->post_name = !empty($request->post_name) ? $request->post_name : "";

        if (!empty($request->post_type)) {
            $post->post_type = $request->post_type;
        }

        $post->post_location = !empty($request->post_location) ? $request->post_location : "";
        
        if (!empty($request->post_latitude)) {
            $post->post_latitude = $request->post_latitude;
        }
        if (!empty($request->post_longitude)) {
            $post->post_longitude = $request->post_longitude;
        }

        $added_post = $post->update();
        if ($added_post) {
            $post_id = $post->post_id;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $post_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($post->post_image)) {
                    @unlink($destinationPath . "/" . $post->post_image);
                }

                $post->post_image = $image_name;
                $post->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Update Successfully");
        } else {
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $id = $request->get("id");
        $find_record = Posts::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->post_status = 9;
            $find_record->save();
            $find_record->delete();
            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

    public function delete_comment($id = null) {
        $find_record = PostComments::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }

        return redirect()->back()->with("success", "Comment Deleted Successfully");
    }

    public function delete_post($id = null) {
        $find_record = Posts::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }
        return redirect()->back()->with("success", "Post Deleted Successfully");
    }

}
