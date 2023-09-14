<?php

namespace App\Http\Controllers;

use App\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller {

    protected $route_name;

    public function __construct() {
        $this->route_name = 'feedback';
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

        $name = !empty($request->get("name")) ? $request->get("name") : '';
        $content = !empty($request->get("content")) ? $request->get("content") : '';
        $status = !empty($request->get("status")) ? $request->get("status") : '';

        if ($sidx == 0) {
            $sidx = 'u_user_name';
        } else if ($sidx == 1) {
            $sidx = 'f_content';
        } else if ($sidx == 2) {
            $sidx = 'f_status';
        } else {
            $sidx = 'f_id';
        }

        $list_query = Feedback::select("feedback.*","users.u_user_name")->join("users", "u_id",'f_user_id');
        if (!empty($name)) {
            $list_query = $list_query->where('u_user_name', "LIKE", "%" . $name . "%");
        }
        if (!empty($content)) {
            $list_query = $list_query->where('f_content', "LIKE", "%" . $content . "%");
        }
        if (!empty($status)) {
            $list_query = $list_query->where("f_status", "=", $status);
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
                $all_records[$index]['name'] = '<a href="' . route("users.show", $value->f_user_id) . '">' . $value->u_user_name . '</a>';
                $all_records[$index]['content'] = '<a href="' . route($this->route_name . ".show", $value->f_id) . '">' . $value->f_content . '</a>';

                $checked = '';
                if ($value->f_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                                <input type="checkbox" ' . $checked . ' data-id="' . $value->f_id . '" class="change_status custom-switch-input">
                                                                <span class="custom-switch-indicator"></span>
                                                              </label>';

                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->f_id . '">Delete</button>';

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
     * Display the specified resource.
     *
     * @param  \App\Feedback  $feedback
     * @return \Illuminate\Http\Response
     */
    public function show(Feedback $feedback) {
        return view($this->route_name . ".show", compact('feedback'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Feedback  $feedback
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $id = $request->get("id");
        $find_record = Feedback::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {

            //Delete category
            $find_record->delete();

            $response['success'] = true;
            $response['message'] = "Feedback deleted successfully";
        }

        return $response;
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Feedback::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->f_status = $status;
            $find_record->save();

            $response['success'] = true;
            $response['message'] = "Status changed successfully";
        }

        return $response;
    }

}
