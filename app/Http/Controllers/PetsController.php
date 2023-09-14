<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Pets;
use App\PetTypes;
use App\PetBreeds;
use App\PetImages;
use App\PetCoOwners;

class PetsController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;

    public function __construct() {
        $this->route_name = 'pets';
        $this->module_singular_name = 'Pet';
        $this->module_plural_name = 'Pets';

        $this->middleware("checkmodulepermission", ['except' => ['delete_member', 'delete_pet']]);
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
            $sidx = 'pet_name';
        } else if ($sidx == 1) {
            $sidx = 'pt_name';
        } else if ($sidx == 2) {
            $sidx = 'u_user_name';
        } else if ($sidx == 3) {
            $sidx = 'pet_gender';
        } else if ($sidx == 4) {
            $sidx = 'pet_status';
        } else {
            $sidx = 'pet_id';
        }

        $list_query = Pets::select("*", \DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`) as u_user_name"))
                ->leftJoin('pet_types', 'pt_id', 'pet_type_id')
                ->leftJoin('users', 'u_id', 'pet_owner_id');

        if (!empty($name)) {
            $list_query = $list_query->where(function ($query) use ($name) {
                $query->orWhere('pet_name', 'like', '%' . $name . '%')
                        ->orWhere('pt_name', 'like', '%' . $name . '%')
                        ->orWhere(\DB::raw("CONCAT(`u_first_name`, ' ', `u_last_name`)"), 'like', '%' . $name . '%');
            });
        }

        if (!empty($status)) {
            $list_query = $list_query->where("pet_status", "=", $status);
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
                $all_records[$index]['name'] = '<a href=' . route($this->route_name . ".show", $value->pet_id) . ' class="font-weight-600"><img src=' . $value->pet_image . ' alt="img" width="60" height="60" class="rounded-circle mr-1"> ' . $value->pet_name . '</a>';
                $all_records[$index]['pet_type'] = $value->pt_name;
                $all_records[$index]['user_name'] = ucwords($value->u_user_name);
                $all_records[$index]['gender'] = $value->pet_gender;

                $checked = '';
                if ($value->pet_status == 1) {
                    $checked = 'checked="checked"';
                }

                $all_records[$index]['status'] = '<label class="custom-switch mt-2">
                                                                <input type="checkbox" ' . $checked . ' data-id="' . $value->pet_id . '" class="change_status custom-switch-input">
                                                                <span class="custom-switch-indicator"></span>
                                                              </label>';

                $all_records[$index]['edit'] = '<a href="' . route($this->route_name . ".edit", $value->pet_id) . '" class="btn btn-light">Edit</a>';

                $all_records[$index]['delete'] = '<button type="button" class="btn btn-danger delete_data_button" data-id="' . $value->pet_id . '">Delete</button>';

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
        $all_avilable_user_roles = \App\PetRoles::where("role_status", 1)->select("role_id", "role_name", "role_type")->get();
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
            "email" => "email|unique:" . $this->route_name . ",u_email,u_deleted_at,NULL",
            "user_name" => "required",
            "password" => "required",
        ]);

        if ($request->hasFile('user_image')) {
            $request->validate(["user_image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        $add_new_user = array(
            "pet_name" => $request->get("first_name"),
            "u_last_name" => $request->get("last_name"),
            "u_user_name" => $request->get("user_name"),
            "u_email" => $request->get("email"),
            "u_password" => bcrypt($request->get("password")),
        );

        $added_user = Pets::create($add_new_user);
        if ($added_user) {
            $pet_id = $added_user->pet_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $pet_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
            }
            if (!empty($image_name)) {
                $user = Pets::find($pet_id);
                $user->u_image = $image_name;
                $user->update();
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
    public function show(Pets $pet) {
        $pet_breed_percentage = [];
        if (!empty($pet->pet_breed_percentage)) {
            $pet_breed_percentage = explode(",", $pet->pet_breed_percentage);
        }


        if (!empty($pet->pet_breed_ids)) {
            $breed = PetBreeds::whereRaw('FIND_IN_SET(pb_id,"' . $pet->pet_breed_ids . '")')
                    ->select('*')
                    ->get();

            if (!empty($breed)) {
                $i = 0;
                foreach ($breed as &$breed_value) {
                    $breed_value['breed_percentage'] = !empty($pet_breed_percentage) && !empty($pet_breed_percentage[$i]) ? $pet_breed_percentage[$i] : 0;
                    $i++;
                }
            }

            $pet->breed = $breed;
        }
        return view($this->route_name . ".show", compact('pet'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Pets  $pet
     * @return \Illuminate\Http\Response
     */
    public function edit(Pets $pet) {
        $pet_types = PetTypes::where("pt_status", 1)->select("pt_id", "pt_name")->get();

        return view($this->route_name . ".edit")
                        ->with(
                                array(
                                    "pet" => $pet,
                                    "pet_types" => $pet_types,
                                )
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Pets  $pet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pets $pet) {
        $request->validate([
            'pet_name' => 'required',
            'pet_type' => 'required',
        ]);

        if ($request->hasFile('image')) {
            $request->validate(["image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        if (!empty($request->get("pet_name"))) {
            $pet->pet_name = $request->get("pet_name");
        }

        if (!empty($request->get("pet_size"))) {
            $pet->pet_size = $request->get("pet_size");
        }
        if (!empty($request->get("pet_is_friendly"))) {
            $pet->pet_is_friendly = $request->get("pet_is_friendly");
        }

        if (!empty($request->get("pet_type"))) {
            $pet->pet_type_id = $request->get("pet_type");
        }

        if (!empty($request->get("pet_dob"))) {
            $pet->pet_dob = $request->get("pet_dob");
        }

        if (!empty($request->get("pet_gender"))) {
            $pet->pet_gender = $request->get("pet_gender");
        }
        if (!empty($request->get("pet_note"))) {
            $pet->pet_note = $request->get("pet_note");
        }

        $added_pet = $pet->update();

        if ($added_pet) {
            $pet_id = $pet->pet_id;
            $image_name = "";
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/" . $this->route_name . "/" . $pet_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($pet->pet_image)) {
                    @unlink($destinationPath . "/" . $pet->pet_image);
                }

                $pet->pet_image = $image_name;
                $pet->update();
            }

            return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Update Successfully");
        } else {
            return back()->withInput();
        }
    }

    public function change_status(Request $request) {
        $id = $request->get("id");
        $status = $request->get("status");
        $find_record = Pets::find($id);
        $response = array("success" => false, "message" => "Problem while change status");
        if ($find_record) {
            $find_record->pet_status = $status;
            $find_record->save();

            if ($status == 1) {
                $message = $this->module_singular_name . " has been unblocked";
            } else {
                $message = $this->module_singular_name . " has been blocked";
            }

            $response['success'] = true;
            $response['message'] = $message;
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
        $find_record = Pets::find($id);
        $response = array("success" => false, "message" => "Problem while deleting this record");
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();

            $response['success'] = true;
            $response['message'] = $this->module_singular_name . " deleted successfully";
        }

        return $response;
    }

    public function delete_pet($id = null) {
        $find_record = Pets::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }
        return redirect()->back()->with("success", "Co-Owner Deleted Successfully");
    }

    public function delete_member($id = null) {
        $find_record = PetCoOwners::find($id);
        if ($find_record) {
            $find_record->delete();
            $find_record->forceDelete();
        }

        return redirect()->back()->with("success", "Co-Owner Deleted Successfully");
    }

}
