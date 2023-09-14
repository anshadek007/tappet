<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contactus;

class ContactusController extends Controller {
       
    protected $route_name;
    
    public function __construct() {
        $this->route_name = 'contactus';
        $this->middleware("checkmodulepermission", ['except' => ['show']]);
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

        $name  = !empty($request->get("name")) ? $request->get("name") : '';
        $email = !empty($request->get("email")) ? $request->get("email") : '';
        $mobile_number  = !empty($request->get("mobile_number")) ? $request->get("mobile_number") : '';
        $status = !empty($request->get("status")) ? $request->get("status") : '';
        
        if ($sidx == 0) {
            $sidx = 'con_title';
        } else if ($sidx == 1) {
            $sidx = 'con_email';
        } else if ($sidx == 2) {
            $sidx = 'con_mobile_number';
        } else {
            $sidx = 'con_id';
        }

        $list_query = Contactus::select("*");
        if (!empty($name)) {
            $list_query = $list_query->where('con_title', "LIKE", "%" . $name . "%");
        }
        if (!empty($email)) {
            $list_query = $list_query->where('con_email', "LIKE", "%" . $email . "%");
        }
        if (!empty($mobile_number)) {
            $list_query = $list_query->where('con_mobile_number', "LIKE", "%" . $mobile_number . "%");
        }
        if (!empty($status)) {
            $list_query = $list_query->where("con_status", "=", $status);
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
                 
                $all_records[$index]['title'] = '<a href="'.route($this->route_name . ".show", $value->con_id).'">'.$value->con_title.'</a>';
                $all_records[$index]['email'] = $value->con_email;
                $all_records[$index]['mobileno'] = $value->con_mobile_number;

                $checked = '';
                if ($value->con_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                                <input type="checkbox" ' . $checked . ' data-id="' . $value->con_id . '" class="change_status custom-switch-input">
                                                                <span class="custom-switch-indicator"></span>
                                                              </label>';

                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->con_id . '">Delete</button>';

                $index++;
            }
        }
        $response = array();
        $response['draw'] = (int) $draw;
        $response['recordsTotal'] = (int) $total_rows;
        $response['recordsFiltered'] = (int) $total_rows;
        $response['data'] = $all_records;
//dd($all_records);
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
        $find_record = Contactus::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {

            //Delete category
            $find_record->delete();

            $response['success'] = true;
            $response['message'] = "Contactus deleted successfully";
        }

        return $response;
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Contactus $contactus) {
        return view($this->route_name . ".show", compact('contactus'));
    }
    
    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Contactus::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->con_status = $status;
            $find_record->save();

            $response['success'] = true;
            $response['message'] = "Status changed successfully";
        }

        return $response;
    }

}
