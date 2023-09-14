<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRoles;

class UserRolesController extends Controller {

    public $types_of_permission;
    public $can_view_other_data;

    public function __construct() {

        $this->middleware("checkmodulepermission", ['except' => ['all_role_list_for_select']]);

        $this->types_of_permission = array(
            1 => array(
                "key" => "can_view_other_data",
                "value" => "Can view Other User Data"
            ),
            2 => array(
                "key" => "create",
                "value" => "Add"
            ),
            3 => array(
                "key" => "edit",
                "value" => "Edit"
            ),
            4 => array(
                "key" => "destroy",
                "value" => "Delete"
            ),
            5 => array(
                "key" => "index",
                "value" => "View"
            ),
            6 => array(
                "key" => "import",
                "value" => "Can Import"
            ),
            7 => array(
                "key" => "export",
                "value" => "Can Export"
            )
        );

        $module_permissions = \Session::get("user_access_permission");
        $module_permission = !empty($module_permissions['user-roles']) ? $module_permissions['user-roles'] : array();
        $this->can_view_other_data = !empty($module_permission['can_view_other_data']) ? true : false;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view("user_roles.index");
    }

    public function load_data_in_table(Request $request) {

        $page = !empty($request->get("start")) ? $request->get("start") : 0;
        $rows = !empty($request->get("length")) ? $request->get("length") : 10;
        $draw = !empty($request->get("draw")) ? $request->get("draw") : 1;

        $sidx = !empty($request->get("order")[0]['column']) ? $request->get("order")[0]['column'] : 0;
        $sord = !empty($request->get("order")[0]['dir']) ? $request->get("order")[0]['dir'] : 'ASC';

        $role_name = !empty($request->get("role_name")) ? $request->get("role_name") : '';
        $role_status = !empty($request->get("role_status")) ? $request->get("role_status") : '';

        if ($sidx == 0) {
            $sidx = 'role_name';
        } else if ($sidx == 1) {
            $sidx = 'role_created_date';
        } else if ($sidx == 2) {
            $sidx = 'role_created_date';
        }

        $list_of_all_user_roles = UserRoles::select("*");
        if (!$this->can_view_other_data) {
            $list_of_all_user_roles = $list_of_all_user_roles->where("role_added_by_u_id", \Auth::user()->a_id);
        }

        if (!empty($role_name)) {
            $list_of_all_user_roles = $list_of_all_user_roles->where("role_name", "LIKE", "%" . $role_name . "%");
        }
        if (!empty($role_status)) {
            $list_of_all_user_roles = $list_of_all_user_roles->where("role_status", "=", $role_status);
        }

        $total_rows = $list_of_all_user_roles->count();
        $list_of_all_user_roles_array = array();
        if ($total_rows > 0) {
            $list_of_all_user_roles_data = $list_of_all_user_roles->skip($page)
                    ->orderBy($sidx, $sord)
                    ->take($rows)
                    ->get();
            $index = 0;
            foreach ($list_of_all_user_roles_data as $user_role) {
                $list_of_all_user_roles_array[$index]['name'] = $user_role->role_name;
                $parent_role = $user_role->parentRole;
                if ($parent_role) {
                    $list_of_all_user_roles_array[$index]['parent_role'] = $parent_role->role_name;
                } else {
                    $list_of_all_user_roles_array[$index]['parent_role'] = "None";
                }
                $added_by = $user_role->addedBy;
                $list_of_all_user_roles_array[$index]['added_by'] = $added_by->u_first_name . " " . $added_by->u_last_name;
                $checked = '';
                if ($user_role->role_status == 1) {
                    $checked = 'checked="checked"';
                }
                $list_of_all_user_roles_array[$index]['status'] = '
                    <label class="custom-switch mt-2">
                        <input type="checkbox" ' . $checked . ' data-id="' . $user_role->role_id . '" class="change_status custom-switch-input">
                        <span class="custom-switch-indicator"></span>
                    </label>
                ';
                $list_of_all_user_roles_array[$index]['edit'] = '
                    <a href="' . route("user-roles.edit", $user_role->role_id) . '" class="btn btn-light">Edit</a>
                ';
                if ($user_role->role_id == 1) {
                    $list_of_all_user_roles_array[$index]['delete'] = '';
                } else {
                    $list_of_all_user_roles_array[$index]['delete'] = '
                    <button type="button" class="btn btn-danger delete_data_button" data-id="' . $user_role->role_id . '">Delete</button>
                ';
                }

                $index++;
            }
        }

        $response = array();
        $response['draw'] = (int) $draw;
        $response['recordsTotal'] = (int) $total_rows;
        $response['recordsFiltered'] = (int) $total_rows;
        $response['data'] = $list_of_all_user_roles_array;

        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $all_avilable_role_permissions = \App\UserRolesGroups::where("urpg_status", 1)->get();

        return view("user_roles.add")
                        ->with(array(
                            "all_avilable_role_permissions" => $all_avilable_role_permissions,
                            "types_of_permission" => $this->types_of_permission
        ));
    }

    public function all_role_list_for_select(Request $request) {
        $type = $request->get("selected_type");
        $list_all_roles = UserRoles::where("role_type", $type)->where("role_status", 1)->select("role_id", "role_name")->get();

        $response = array(
            "success" => false
        );
        if ($list_all_roles) {
            $response['success'] = true;
            $response['data'] = $list_all_roles;
        }

        return $response;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $request->validate([
            'role_name' => 'required',
//            'role_type' => 'required',
            "role_permissions" => "required"
        ]);

        $role_name = $request->get("role_name");
//        $role_type = $request->get("role_type");
//        $parent_role = $request->get("parent_role");
        $role_permissions = $request->get("role_permissions");

        $add_new_role = array(
            "role_type" => 1,
            "role_name" => $role_name,
            "role_permissions" => json_encode($role_permissions),
            "role_added_by_u_id" => \Auth::user()->a_id,
            "role_parent_role_id" => 1,
        );

        $added_role = UserRoles::create($add_new_role);
        if ($added_role) {
            return redirect()->route("user-roles.index")->with("success", "Role Added Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $role_id = $request->get("role_id");
        $status = $request->get("status");
        $user_role = UserRoles::find($role_id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($user_role) {
            $user_role->role_status = $status;
            $user_role->save();

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
    public function show($id) {
        return redirect()->route("user-roles.index");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  UserRoles  $user_role
     * @return \Illuminate\Http\Response
     */
    public function edit(UserRoles $user_role) {
        $all_avilable_role_permissions = \App\UserRolesGroups::where("urpg_status", 1)->get();

        $list_of_other_roles = UserRoles::where("role_id", "!=", $user_role->role_id)
                ->select("role_id", "role_name")
                ->where("role_status", "=", 1)
                ->get();

        $selected_permissions = json_decode($user_role->role_permissions, true);
        return view("user_roles.edit")
                        ->with(array(
                            "all_avilable_role_permissions" => $all_avilable_role_permissions,
                            "user_role" => $user_role,
                            "list_of_other_roles" => $list_of_other_roles,
                            "types_of_permission" => $this->types_of_permission
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  UserRoles  $user_role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserRoles $user_role) {
        $request->validate([
            'role_name' => 'required',
//            'role_type' => 'required',
            "role_permissions" => "required"
        ]);

        $user_role->role_name = $request->get("role_name");
//        $user_role->role_type = $request->get("role_type");
//        $user_role->role_parent_role_id = $request->get("parent_role");
        $user_role->role_permissions = json_encode($request->get("role_permissions"));
        $added_role = $user_role->save();
        if ($added_role) {
            return redirect()->route("user-roles.index")->with("success", "Role Updated Successfully");
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
        $role_id = $request->get("id");
        $user_role = UserRoles::find($role_id);
        $response = array("success" => false, "message" => "Problem while delete this role");
        if ($user_role) {
            $user_role->delete();

            $response['success'] = true;
            $response['message'] = "Role deleted successfully";
        }

        return $response;
    }

}
