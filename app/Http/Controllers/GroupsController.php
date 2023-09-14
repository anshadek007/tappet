<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Groups;
use App\GroupMembers;

class GroupsController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'groups';
        $this->module_singular_name = 'Group';
        $this->module_plural_name = 'Groups';
        $this->middleware("checkmodulepermission", ['except' => ['delete_member']]);
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
            $sidx = 'group_name';
        } else if ($sidx == 1) {
            $sidx = 'group_privacy';
        } else if ($sidx == 3) {
            $sidx = 'u_user_name';
        } else if ($sidx == 4) {
            $sidx = 'group_status';
        } else {
            $sidx = 'group_id';
        }

        $list_query = Groups::select("*", \DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`) as u_user_name"))
                ->leftJoin('users', 'u_id', 'group_owner_id');

        if (!empty($name)) {
            $list_query = $list_query->where(function ($query) use ($name) {
                $query->orWhere('group_name', 'like', '%' . $name . '%')
                        ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $name . '%');
            });
        }

        if (!empty($status)) {
            $list_query = $list_query->where("group_status", "=", $status);
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
                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->group_id) . ' class="font-weight-600"><img src=' . $value->group_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"> ' . $value->group_name . '</a>';
                $all_records[$index]['user_name'] = '<a href=' . route("users.show", $value->u_id) . ' class="font-weight-600"><img src=' . $value->u_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"> ' . $value->u_user_name . '</a>';
                $all_records[$index]['privacy'] = $value->group_privacy;
                $all_records[$index]['members'] = !empty($value->group_members) ? $value->group_members->count() : 0;
                $checked = '';
                if ($value->group_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2"><input type="checkbox" ' . $checked . ' data-id="' . $value->group_id . '" class="change_status custom-switch-input"><span class="custom-switch-indicator"></span></label>';
                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->group_id) . '" class="btn btn-light">Edit</a>';
                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->group_id . '">Delete</button>';

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
            "group_name" => "required|unique:" . $this->route_name . ",group_name,NULL,group_id,group_deleted_at,NULL|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:1024"]);
        }

        $add_new_category = array(
            "group_name" => $request->get("group_name"),
        );

        $added_category = Groups::create($add_new_category);
        if ($added_category) {
            $category_id = $added_category->group_id;
            $image_name = "";
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);

                $added_category->group_image = $image_name;
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
        $find_record = Groups::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->group_status = $status;
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
    public function show(Groups $group) {
        return view($this->route_name . ".show", compact('group'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Groups  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Groups $group) {
        return view($this->route_name . ".edit")->with(array("group" => $group));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Groups  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Groups $group) {
        $request->validate([
            "group_name" => "required|unique:" . $this->route_name . ",group_name,$group->group_id,group_id,group_deleted_at,NULL|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:1024"]);
        }
        $group->group_name = $request->get("group_name");
        $group->group_description = $request->get("description");

        if (!empty($request->group_privacy)) {
            $group->group_privacy = $request->group_privacy;
        }

        $added_group = $group->update();
        if ($added_group) {
            $group_id = $group->group_id;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $group_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($group->group_image)) {
                    @unlink($destinationPath . "/" . $group->group_image);
                }

                $group->group_image = $image_name;
                $group->update();
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
        $find_record = Groups::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->group_status = 9;
            $find_record->save();
            $find_record->delete();
            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

    public function delete_member($id = null) {
        $find_record = GroupMembers::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }

        return redirect()->back()->with("success", "Member Deleted Successfully");
    }

}
