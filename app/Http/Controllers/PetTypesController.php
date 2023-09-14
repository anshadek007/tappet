<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PetTypes;

class PetTypesController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'pet_types';
        $this->module_singular_name = 'Pet Type';
        $this->module_plural_name = 'Pet Types';
        $this->middleware("checkmodulepermission", ['except' => []]);
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
            $sidx = 'pt_name';
        } else if ($sidx == 1) {
            $sidx = 'pt_status';
        } else {
            $sidx = 'pt_id';
        }

        $list_query = PetTypes::select("*");
        if (!empty($name)) {
            $list_query = $list_query->where('pt_name', "LIKE", "%" . $name . "%");
        }
        if (!empty($status)) {
            $list_query = $list_query->where("pt_status", "=", $status);
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
                $all_records[$index]['name'] = '<img src=' . $value->pt_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"> ' . $value->pt_name;

                $checked = '';
                if ($value->pt_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2"><input type="checkbox" ' . $checked . ' data-id="' . $value->pt_id . '" class="change_status custom-switch-input"><span class="custom-switch-indicator"></span></label>';
                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->pt_id) . '" class="btn btn-light">Edit</a>';
                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->pt_id . '">Delete</button>';

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
            "pet_type_name" => "required|unique:" . $this->route_name . ",pt_name,NULL,pt_id,pt_deleted_at,NULL|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }

        $add_new_category = array(
            "pt_name" => $request->get("pet_type_name"),
        );

        $added_category = PetTypes::create($add_new_category);
        if ($added_category) {
            $category_id = $added_category->pt_id;
            $image_name = "";
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);

                $added_category->pt_image = $image_name;
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
        $find_record = PetTypes::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->pt_status = $status;
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
    public function show(PetTypes $pet_type) {
        return view($this->route_name . ".show", compact('pet_type'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  PetTypes  $pet_type
     * @return \Illuminate\Http\Response
     */
    public function edit(PetTypes $pet_type) {
        return view($this->route_name . ".edit")->with(array("pet_type" => $pet_type));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  PetTypes  $pet_type
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PetTypes $pet_type) {
        $request->validate([
            "pet_type_name" => "required|unique:" . $this->route_name . ",pt_name,$pet_type->pt_id,pt_id,pt_deleted_at,NULL|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }
        $pet_type->pt_name = $request->get("pet_type_name");

        $added_pet_type = $pet_type->update();
        if ($added_pet_type) {
            $pet_type_id = $pet_type->pt_id;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $pet_type_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($pet_type->pt_image)) {
                    @unlink($destinationPath . "/" . $pet_type->pt_image);
                }

                $pet_type->pt_image = $image_name;
                $pet_type->update();
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
        $find_record = PetTypes::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->pt_status = 9;
            $find_record->save();
            $find_record->delete();
            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

}
