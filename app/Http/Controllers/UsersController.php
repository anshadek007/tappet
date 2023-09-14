<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Countries;
use App\Cities;

class UsersController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'users';
        $this->module_singular_name = 'User';
        $this->module_plural_name = 'Users';

        $this->middleware("checkmodulepermission");
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
        $email = !empty($request->get("email")) ? $request->get("email") : '';
        $mobileno = !empty($request->get("mobileno")) ? $request->get("mobileno") : '';
        $status = !empty($request->get("status")) ? $request->get("status") : '';

        if ($sidx == 0) {
            $sidx = 'u_first_name';
        } else if ($sidx == 1) {
            $sidx = 'u_email';
        } else if ($sidx == 2) {
            $sidx = 'u_mobile_number';
        } else {
            $sidx = 'user_id';
        }

        $list_query = User::select("*", \DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`) as u_user_name"));
        if (!empty($name)) {
            $list_query = $list_query->where(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $name . '%');
        }
        if (!empty($email)) {
            $list_query = $list_query->where("u_email", "LIKE", "%" . $email . "%");
        }
        if (!empty($mobileno)) {
            $list_query = $list_query->where("u_mobile_number", "LIKE", "%" . $mobileno . "%");
        }
        if (!empty($status)) {
            $list_query = $list_query->where("u_status", "=", $status);
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
                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->u_id) . ' class="font-weight-600"><img src=' . $value->u_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"> ' . $value->u_user_name . '</a>';
                $all_records[$index]['email'] = $value->u_email;
                $all_records[$index]['phone'] = $value->u_mobile_number;

                $checked = '';
                if ($value->u_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                                <input type="checkbox" ' . $checked . ' data-id="' . $value->u_id . '" class="change_status custom-switch-input">
                                                                <span class="custom-switch-indicator"></span>
                                                              </label>';

                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->u_id) . '" class="btn btn-light">Edit</a>';

                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->u_id . '">Delete</button>';

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
        $all_avilable_user_roles = \App\UserRoles::where("role_status", 1)->select("role_id", "role_name", "role_type")->get();
        return view($this->route_name . ".add")->with(array("all_avilable_user_roles" => $all_avilable_user_roles));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            "email" => "email|unique:" . $this->route_name . ",u_email,u_deleted_at,NULL",
            "user_name" => "required",
            "password" => "required",
        ]);

        if ($request->hasFile('user_image')) {
            $request->validate(["user_image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        $add_new_user = array(
            "u_first_name" => $request->get("first_name"),
            "u_last_name" => $request->get("last_name"),
            "u_user_name" => $request->get("user_name"),
            "u_email" => $request->get("email"),
            "u_password" => bcrypt($request->get("password")),
        );

        $added_user = User::create($add_new_user);
        if ($added_user) {
            $user_id = $added_user->u_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $user_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
            }
            if (!empty($image_name)) {
                $user = User::find($user_id);
                $user->u_image = $image_name;
                $user->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Added Successfully");
        } else {
            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user) {
        return view($this->route_name . ".show", compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user) {
        $all_avilable_user_roles = \App\UserRoles::where("role_status", 1)
                ->select("role_id", "role_name", "role_type")
                ->get();

        $all_country = Countries::where("c_status", 1)->select("c_id", "c_name")->get();
        $all_cities = array();

        if (!empty($user->u_country) && is_numeric($user->u_country)) {
            $all_cities = Cities::where('city_country_id', $user->u_country)->where("city_status", 1)->select("city_id as id", "city_name as name")->get();
        }

        return view($this->route_name . ".edit")
                        ->with(
                                array(
                                    "all_avilable_user_roles" => $all_avilable_user_roles,
                                    "user" => $user,
                                    "all_country" => $all_country,
                                    "all_cities" => $all_cities,
                                )
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user) {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            "email" => "email|unique:" . $this->route_name . ",u_email,$user->u_id,u_id,u_deleted_at,NULL",
            "u_mobile_number" => "required|unique:" . $this->route_name . ",u_mobile_number,$user->u_id,u_id,u_deleted_at,NULL",
        ]);

        if ($request->hasFile('user_image')) {
            $request->validate(["user_image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        $user->u_first_name = $request->get("first_name");
        $user->u_last_name = $request->get("last_name");
        $user->u_email = $request->get("email");
        $user->u_mobile_number = !empty($request->get("u_mobile_number")) ? trim($request->get("u_mobile_number")) : "";
        $user->u_country = !empty($request->get("u_country")) ? trim($request->get("u_country")) : "";
        $user->u_city = !empty($request->get("u_city")) ? trim($request->get("u_city")) : "";

//        $user->u_latitude = !empty($request->get("u_latitude")) ? trim($request->get("u_latitude")) : "";
//        $user->u_longitude = !empty($request->get("u_longitude")) ? trim($request->get("u_longitude")) : "";

        if (!empty($request->get("u_dob"))) {
            $user->u_dob = $request->get("u_dob");
        }

        if (!empty($request->get("u_gender"))) {
            $user->u_gender = $request->get("u_gender");
        }

        if (!empty($request->get("u_address"))) {
            $user->u_address = $request->get("u_address");
        }

        if (!empty($request->get("u_zipcode"))) {
            $user->u_zipcode = $request->get("u_zipcode");
        }

        if (!empty($request->get("password"))) {
            $user->u_password = bcrypt($request->get("password"));
        }

        $added_user = $user->update();

        if ($added_user) {
            $user_id = $user->u_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $user_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($user->u_image)) {
                    @unlink($destinationPath . "/" . $user->u_image);
                }

                $user->u_image = $image_name;
                $user->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Update Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = User::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->u_status = $status;
            $find_record->save();

            if ($status == 1) {
                $message = "User has been unblocked";
            } else {
                $message = "User has been blocked";
            }

            $response['success'] = true;
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $id = $request->get("id");
        $find_record = User::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->delete();

            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

}
