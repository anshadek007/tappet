<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Categories;

class CategoriesController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'categories';
        $this->module_singular_name = 'Category';
        $this->module_plural_name = 'Categories';
        $this->middleware("checkmodulepermission", ['except' => ['change_order']]);
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
            $sidx = 'c_name';
        } else if ($sidx == 1) {
            $sidx = 'c_color';
        } else if ($sidx == 2) {
            $sidx = 'c_order';
        } else if ($sidx == 3) {
            $sidx = 'c_status';
        } else {
            $sidx = 'c_id';
        }

        $list_query = Categories::select("*");
        if (!empty($name)) {
            $list_query = $list_query->where('c_name', "LIKE", "%" . $name . "%");
        }
        if (!empty($status)) {
            $list_query = $list_query->where("c_status", "=", $status);
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
                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->c_id) . '><img src=' . getPhotoURL('categories', $value->c_id, $value->c_image) . ' alt="img" width="30" height="30" class="rounded-circle mr-1"> ' . $value->c_name . '</a>';
                $all_records[$index]['cat_color'] = '<label style="color:' . $value->c_color . '">' . $value->c_color . '</label>';
                $all_records[$index]['order'] = '<input type="text" class="form-control category_order" value="' . $value->c_order . '" id="change_order_' . $value->c_id . '" data-value="' . $value->c_order . '" data-id="' . $value->c_id . '">';

                $checked = '';
                if ($value->c_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2"><input type="checkbox" ' . $checked . ' data-id="' . $value->c_id . '" class="change_status custom-switch-input"><span class="custom-switch-indicator"></span></label>';

                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->c_id) . '" class="btn btn-light">Edit</a>';
                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->c_id . '">Delete</button>';

                $index++;
            }
        }
        $response = array();
        $response['draw'] = (int) $draw;
        $response['recordsTotal'] = (int) $total_rows;
        $response['recordsFiltered'] = (int) $total_rows;
        $response['data'] = $all_records;
//        dd($all_records);
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
//        dd($request->all());
        $request->validate([
            'category_name' => 'required|unique:categories,c_name|max:50',
            'category_color' => 'required',
        ]);

        if ($request->hasFile('category_image')) {
            $request->validate(["category_image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }

        if ($request->hasFile('category_trans_image')) {
            $request->validate(["category_trans_image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }

        $add_new_category = array(
            "c_name" => $request->get("category_name"),
            "c_color" => $request->get("category_color"),
            "c_is_eco" => !empty($request->get("c_is_eco")) && $request->get("c_is_eco") == "on" ? 1 : 2,
        );

        $added_category = Categories::create($add_new_category);
        if ($added_category) {
            $category_id = $added_category->c_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('category_image')) {
                $image = $request->file('category_image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
            }
            if (!empty($image_name)) {
                $category = Categories::find($category_id);
                $category->c_image = $image_name;
                $category->update();
            }
            if ($request->hasFile('category_trans_image')) {
                $image = $request->file('category_trans_image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
            }
            if (!empty($image_name)) {
                $category = Categories::find($category_id);
                $category->c_trans_image = $image_name;
                $category->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", "Category Added Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Categories::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            if (!empty($find_record->tours) && $find_record->tours->count() > 0) {
                foreach ($find_record->tours as $tour) {
                    $tour->tour_status = $status;
                    $tour->save();
                }
            }
            $find_record->c_status = $status;
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
    public function show(Categories $category) {
        return view($this->route_name . ".show", compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Categories  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Categories $category) {
        return view($this->route_name . ".edit")->with(array("category" => $category));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Categories  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Categories $category) {

        $request->validate([
            "category_name" => "required|unique:categories,c_name,$category->c_id,c_id,c_deleted_at,NULL|max:50",
            'category_color' => 'required',
        ]);

        if ($request->hasFile('category_image')) {
            $request->validate(["category_image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }
        if ($request->hasFile('category_trans_image')) {
            $request->validate(["category_trans_image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }

        $category->c_name = $request->get("category_name");
        $category->c_color = $request->get("category_color");
        $category->c_is_eco = !empty($request->get("c_is_eco")) && $request->get("c_is_eco") == "on" ? 1 : 2;

        $added_category = $category->update();
        if ($added_category) {
            $category_id = $category->c_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('category_image')) {
                $image = $request->file('category_image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($category->c_image)) {
                    @unlink($destinationPath . "/" . $category->c_image);
                }
            }
            if (!empty($image_name)) {
                $category = Categories::find($category_id);
                $category->c_image = $image_name;
                $category->update();
            }

            if ($request->hasFile('category_trans_image')) {
                $image = $request->file('category_trans_image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($category->c_trans_image)) {
                    @unlink($destinationPath . "/" . $category->c_trans_image);
                }
            }
            if (!empty($image_name)) {
                $category = Categories::find($category_id);
                $category->c_trans_image = $image_name;
                $category->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", "Category Update Successfully");
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
        $find_record = Categories::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            if (!empty($find_record->tours) && $find_record->tours->count() > 0) {
                foreach ($find_record->tours as $tour) {
                    $tour->tour_status = 9;
                    $tour->save();
                    $tour->delete();
                }
            }
            $find_record->c_status = 9;
            $find_record->save();
            $find_record->delete();
            $response['success'] = true;
            $response['message'] = "Category deleted successfully";
        }

        return $response;
    }

    /**
     * 
     * @param Request $request
     * @return string
     */
    public function change_order(Request $request) {
        $id = $request->get("id");
        $category_order = $request->get("category_order");
        $find_record = Categories::find($id);
        $response = array("success" => false, "message" => "Problem while change order");
        if ($find_record) {

            //check the order is assign to another category or not
            $find_catgory = Categories::where("c_id", "!=", $id)
                    ->where("c_order", $category_order)
                    ->get();

            if (count($find_catgory) > 0) {
                $response = array("success" => false, "message" => "This order already assigned to another category");
                return $response;
            }

            $find_record->c_order = $category_order;
            $find_record->save();

            $response['success'] = true;
            $response['message'] = "Category order changed successfully";
        }

        return $response;
    }

}
