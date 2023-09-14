<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Faqs;

class FaqsController extends Controller
{
    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'faqs';
        $this->module_singular_name = 'Faq';
        $this->module_plural_name = 'Faqs';

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

        $title = !empty($request->get("title")) ? $request->get("title") : '';
        $status = !empty($request->get("status")) ? $request->get("status") : '';

        if ($sidx == 0) {
            $sidx = 'faq_title';
        } else if ($sidx == 2) {
            $sidx = 'faq_description';
        } else {
            $sidx = 'faq_id';
        }

        $list_query = Faqs::select("*");
        if (!empty($title)) {
            $list_query = $list_query->where("faq_title", "LIKE", "%" . $title . "%");
        }
        if (!empty($status)) {
            $list_query = $list_query->where("faq_status", "=", $status);
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
                $all_records[$index]['title'] = '<a href=' . route($this->route_name . ".show", $value->faq_id) . ' class="font-weight-600">' . $value->faq_title . '</a>';

                $checked = '';
                if ($value->faq_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                                <input type="checkbox" ' . $checked . ' data-id="' . $value->faq_id . '" class="change_status custom-switch-input">
                                                                <span class="custom-switch-indicator"></span>
                                                              </label>';

                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->faq_id) . '" class="btn btn-light">Edit</a>';

                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->faq_id . '">Delete</button>';

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
            'title' => 'required',
            'description' => 'required',
        ]);


        $add_new_faq = array(
            "faq_title" => $request->get("title"),
            "faq_description" => $request->get("description"),
        );

        $added_faq = Faqs::create($add_new_faq);
        if ($added_faq) {
            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Added Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Faqs::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->faq_status = $status;
            $find_record->save();

            $message = "Faq status changes successfully.";
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
    public function show(Faqs $faq) {
        return view($this->route_name . ".show", compact('faq'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Faqs  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit(Faqs $faq) {
        
        return view($this->route_name . ".edit")
                        ->with(
                                array(
                                    "faq" => $faq
                                )
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Faqs  $faq
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Faqs  $faq) {

        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);
        
        $faq->faq_title = $request->post("title");
        $faq->faq_description = $request->post("description");
        
        $added_faq = $faq->update();
        if ($added_faq) {
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
        $find_record = Faqs::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->delete();

            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }
}
