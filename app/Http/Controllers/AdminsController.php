<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Admins;

class AdminsController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'admins';
        $this->module_singular_name = 'Admin';
        $this->module_plural_name = 'Admins';
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
        $status = !empty($request->get("status")) ? $request->get("status") : '';

        if ($sidx == 0) {
            $sidx = 'user_name';
        } else if ($sidx == 2) {
            $sidx = 'a_role_id';
        } else {
            $sidx = 'a_id';
        }

        $list_query = Admins::select("*", \DB::raw("CONCAT(a_first_name,' ',a_last_name) as user_name"));
        if (!empty($name)) {
            $list_query = $list_query->where(\DB::raw("CONCAT(a_first_name,' ',a_last_name)"), "LIKE", "%" . $name . "%");
        }

        if (!empty($status)) {
            $list_query = $list_query->where("a_status", "=", $status);
        }

        $total_rows = $list_query->count();
        $list_of_all_records = array();
        if ($total_rows > 0) {
            $get_list_data = $list_query->skip($page)
                    ->orderBy($sidx, $sord)
                    ->take($rows)
                    ->get();
            $index = 0;
            foreach ($get_list_data as $single_record) {
                $list_of_all_records[$index]['name'] = $single_record->user_name;
                $list_of_all_records[$index]['email'] = $single_record->a_email;
                if (!empty($single_record->a_image)) {
                    $image_url = asset("public/uploads/" . $this->route_name . "/" . $single_record->a_id . "/" . $single_record->a_image);
                } else {
                    $image_url = asset("public/assets/img/avatar/avatar-1.png");
                }
                $list_of_all_records[$index]['image'] = "<img alt='" . $list_of_all_records[$index]['name'] . "' class='mr-3' width='50' src='$image_url'>";

                $user_role = $single_record->getUserRole;
                if (!empty($user_role)) {
                    $list_of_all_records[$index]['role'] = $user_role->role_name;
                } else {
                    $list_of_all_records[$index]['role'] = 'None';
                }

                $checked = '';
                if ($single_record->a_status == 1) {
                    $checked = 'checked="checked"';
                }

                $list_of_all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                            <input type="checkbox" ' . $checked . ' data-id="' . $single_record->a_id . '" class="change_status custom-switch-input">
                                                            <span class="custom-switch-indicator"></span>
                                                          </label>';

                $list_of_all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $single_record->a_id) . '" class="btn btn-light">Edit</a>';
                if ($single_record->a_id == 1) {
                    $list_of_all_records[$index]['delete'] = '';
                } else {
                    $list_of_all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $single_record->a_id . '">Delete</button>';
                }

                $index++;
            }
        }
        $response = array();
        $response['draw'] = (int) $draw;
        $response['recordsTotal'] = (int) $total_rows;
        $response['recordsFiltered'] = (int) $total_rows;
        $response['data'] = $list_of_all_records;

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
            "email" => "email|unique:" . $this->route_name . ",a_email,NULL,a_id,a_deleted_at,NULL",
            "user_name" => "required|unique:" . $this->route_name . ",a_user_name,NULL,a_id,a_deleted_at,NULL",
            "password" => "required",
            "user_role" => "required",
        ]);

        if ($request->hasFile('user_image')) {
            $request->validate(["user_image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        $create_record = array(
            "a_first_name" => $request->get("first_name"),
            "a_last_name" => $request->get("last_name"),
            "a_user_name" => $request->get("user_name"),
            "a_email" => $request->get("email"),
            "a_password" => bcrypt($request->get("password")),
            "a_role_id" => $request->get("user_role"),
        );
        

        $created_recored = Admins::create($create_record);
        if ($created_recored) {
            $inserted_id = $created_recored->a_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/uploads/' . $this->route_name . '/' . $inserted_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
            }
            if (!empty($image_name)) {
                $update_record = Admins::find($inserted_id);
                $update_record->a_image = $image_name;
                $update_record->update();
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
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Admins  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(Admins $admin) {
        $all_avilable_user_roles = \App\UserRoles::where("role_status", 1)
                ->select("role_id", "role_name", "role_type")
                ->get();

        return view($this->route_name . ".edit")->with(
                        array("all_avilable_user_roles" => $all_avilable_user_roles,
                            "user" => $admin)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Admins  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Admins $admin) {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            "email" => "email|unique:" . $this->route_name . ",a_email,$admin->a_id,a_id,a_deleted_at,NULL",
            "user_name" => "required|unique:" . $this->route_name . ",a_user_name,$admin->a_id,a_id,a_deleted_at,NULL",
            "user_role" => "required",
        ]);

        if ($request->hasFile('user_image')) {
            $request->validate(["user_image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        $admin->a_first_name = $request->get("first_name");
        $admin->a_last_name = $request->get("last_name");
        $admin->a_user_name = $request->get("user_name");
        $admin->a_email = $request->get("email");
        if (!empty($request->get("password"))) {
            $admin->a_password = bcrypt($request->get("password"));
        }
        $admin->a_role_id = $request->get("user_role");

        $added_user = $admin->update();
        if ($added_user) {
            $admin_id = $admin->a_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/".$this->route_name."/" . $admin_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($admin->a_image)) {
                    @unlink($destinationPath . "/" . $admin->a_image);
                }
            }
            if (!empty($image_name)) {
                $update_record = Admins::find($admin_id);
                $update_record->a_image = $image_name;
                $update_record->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Update Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Admins::find($id);
        $response = array("success" => false, "message" => "Problem while changing status");
        if ($find_record) {
            $find_record->a_status = $status;
            $find_record->save();
            $response['success'] = true;
            $response['message'] = "Status changed successfully";
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
        $find_record = Admins::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this " . $this->module_singular_name);
        if ($find_record) {
            $find_record->delete();

            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

}
