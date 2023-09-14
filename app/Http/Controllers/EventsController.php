<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events;
use App\EventMembers;
use App\EventImages;
use App\EventGroups;

class EventsController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'events';
        $this->module_singular_name = 'Event';
        $this->module_plural_name = 'Events';
        $this->middleware("checkmodulepermission", ['except' => ['delete_member', 'delete_group','delete_event']]);
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
            $sidx = 'event_name';
        } else if ($sidx == 1) {
            $sidx = 'u_user_name';
        } else if ($sidx == 2) {
            $sidx = 'event_location';
        } else if ($sidx == 3) {
            $sidx = 'event_start_date';
        } else if ($sidx == 4) {
            $sidx = 'event_end_date';
        } else if ($sidx == 5) {
            $sidx = 'event_participants';
        } else if ($sidx == 6) {
            $sidx = 'event_status';
        } else {
            $sidx = 'event_id';
        }

        $list_query = Events::select("*", \DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`) as u_user_name"))
                ->leftJoin('users', 'u_id', 'event_owner_id');

        if (!empty($name)) {
            $list_query = $list_query->where(function ($query) use ($name) {
                $query->orWhere('event_name', 'like', '%' . $name . '%')
                        ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $name . '%');
            });
        }

        if (!empty($status)) {
            $list_query = $list_query->where("event_status", "=", $status);
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
                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->event_id) . ' class="font-weight-600"><img src=' . $value->event_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"><label class="mt-1">' . $value->event_name . '</label></a>';
                $all_records[$index]['user_name'] = '<a href=' . route("users.show", $value->u_id) . ' class="font-weight-600"><img src=' . $value->u_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"><label class="mt-1">' . $value->u_user_name . '</label></a>';

                $all_records[$index]['event_location'] = $value->event_location;
                $all_records[$index]['event_start_date'] = date(config('constants.DATE_TIME_US'), strtotime($value->event_start_date . ' ' . $value->event_start_time));
                $all_records[$index]['event_end_date'] = date(config('constants.DATE_TIME_US'), strtotime($value->event_end_date . ' ' . $value->event_end_time));
                $all_records[$index]['event_participants'] = $value->event_participants;

                $checked = '';
                if ($value->event_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2"><input type="checkbox" ' . $checked . ' data-id="' . $value->event_id . '" class="change_status custom-switch-input"><span class="custom-switch-indicator"></span></label>';
                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->event_id) . '" class="btn btn-light">Edit</a>';
                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->event_id . '">Delete</button>';

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
            "event_name" => "required|unique:" . $this->route_name . ",event_name,NULL,event_id,event_deleted_at,NULL|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }

        $add_new_category = array(
            "event_name" => $request->get("event_name"),
        );

        $added_category = Events::create($add_new_category);
        if ($added_category) {
            $category_id = $added_category->event_id;
            $image_name = "";
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $category_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);

                $added_category->event_image = $image_name;
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
        $find_record = Events::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->event_status = $status;
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
    public function show(Events $event) {
        return view($this->route_name . ".show", compact('event'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Events  $event
     * @return \Illuminate\Http\Response
     */
    public function edit(Events $event) {
        $event->event_startdate = $event->event_start_date . ' ' . $event->event_start_time;
        $event->event_enddate = $event->event_end_date . ' ' . $event->event_end_time;
        return view($this->route_name . ".edit")->with(array("event" => $event));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Events  $event
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Events $event) {
        $request->validate([
//            "event_name" => "required|unique:" . $this->route_name . ",event_name,$event->event_id,event_id,event_deleted_at,NULL|max:100",
            "event_name" => "required|max:100",
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg,svg|max:5098"]);
        }
        $event->event_name = $request->get("event_name");
        $event->event_description = $request->get("description");

        if (!empty($request->event_start_date)) {
            $date = explode(' ', $request->event_start_date);
            $event->event_start_date = $date[0];
            $event->event_start_time = $date[1];
        }

        if (!empty($request->event_end_date)) {
            $date = explode(' ', $request->event_end_date);
            $event->event_end_date = $date[0];
            $event->event_end_time = $date[1];
        }

        if (!empty($request->event_participants)) {
            $event->event_participants = $request->event_participants;
        }

        if (!empty($request->event_location)) {
            $event->event_location = $request->event_location;
        }
        if (!empty($request->event_latitude)) {
            $event->event_latitude = $request->event_latitude;
        }
        if (!empty($request->event_longitude)) {
            $event->event_longitude = $request->event_longitude;
        }

        $added_event = $event->update();
        if ($added_event) {
            $event_id = $event->event_id;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '_' . rand(0, 999999) . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $event_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($event->event_image)) {
                    @unlink($destinationPath . "/" . $event->event_image);
                }

                $event->event_image = $image_name;
                $event->update();
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
        $find_record = Events::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->event_status = 9;
            $find_record->save();
            $find_record->delete();
            $find_record->forceDelete();
            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

    public function delete_member($id = null) {
        $find_record = EventMembers::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }

        return redirect()->back()->with("success", "Member Deleted Successfully");
    }

    public function delete_group($id = null) {
        $find_record = EventGroups::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }

        return redirect()->back()->with("success", "Group Deleted Successfully");
    }

    public function delete_event($id = null) {
        $find_record = Events::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }
        return redirect()->back()->with("success", "Event Deleted Successfully");
    }

}
