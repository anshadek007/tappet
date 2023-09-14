<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Countries;

class CountriesController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'countries';
        $this->module_singular_name = 'Country';
        $this->module_plural_name = 'Countries';

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

        $sidx = !empty($request->get("order")[0]['column']) ? $request->get("order")[0]['column'] : 1;
        $sord = !empty($request->get("order")[0]['dir']) ? $request->get("order")[0]['dir'] : 'ASC';

        $title = !empty($request->get("title")) ? $request->get("title") : '';
        $status = !empty($request->get("status")) ? $request->get("status") : '';

        if ($sidx == 0) {
            $sidx = 'c_name';
        } else {
            $sidx = 'c_id';
        }

        $list_query = Countries::select("*");
        if (!empty($title)) {
            $list_query = $list_query->where("c_name", "LIKE", "%" . $title . "%");
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
                $all_records[$index]['title'] = $value->c_name;
                $checked = '';
                if ($value->c_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                                <input type="checkbox" ' . $checked . ' data-id="' . $value->c_id . '" class="change_status custom-switch-input">
                                                                <span class="custom-switch-indicator"></span>
                                                              </label>';

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
            "country_name" => "required|unique:" . $this->route_name . ",c_name,NULL,c_id,c_deleted_at,NULL",
        ]);

        $add_new = array(
            "c_name" => $request->get("country_name"),
        );
        
        $added = Countries::create($add_new);
        if ($added) {
            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Added Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Countries::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            if (!empty($find_record->cities) && $find_record->cities->count() > 0) {
                foreach ($find_record->cities as $city) {
                    $city->city_status = $status;
                    $city->save();
                }
            }

            $find_record->c_status = $status;
            $find_record->save();
            $message = $this->module_singular_name . " status changes successfully.";
            $response['success'] = true;
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Countries $country) {
        return view($this->route_name . ".show", compact('country'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Countries  $country
     * @return \Illuminate\Http\Response
     */
    public function edit(Countries $country) {
        return view($this->route_name . ".edit")->with(array("country" => $country));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Countries  $country
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Countries $country) {
        $request->validate([
            "country_name" => "required|unique:" . $this->route_name . ",c_name,$country->c_id,c_id,c_deleted_at,NULL",
        ]);

        $country->c_name = $request->post("country_name");
        
        $updated = $country->update();
        if ($updated) {
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
        $find_record = Countries::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            if (!empty($find_record->cities) && $find_record->cities->count() > 0) {
                foreach ($find_record->cities as $city) {
                    $city->city_status = 9;
                    $city->save();
                    $city->delete();
                }
            }

            $find_record->c_status = 9;
            $find_record->save();
            $find_record->delete();

            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

}
