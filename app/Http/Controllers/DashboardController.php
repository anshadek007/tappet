<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use App\Admins;
use App\User;
use App\PetBreeds;
use App\PetTypes;
use App\Pets;

class DashboardController extends Controller {

    public function index() {
        $total_admins = Admins::where("a_status", 1)->get()->count();
        $total_users = User::where("u_status", 1)->get()->count();
        $total_breed = PetBreeds::where("pb_status", 1)->get()->count();
        $total_types = PetTypes::where("pt_status", 1)->get()->count();
        $total_pets = Pets::where("pet_status", 1)->get()->count();

        return view('dashboard')->with(
                        array(
                            "total_admins" => $total_admins,
                            "total_users" => $total_users,
                            "total_breed" => $total_breed,
                            "total_types" => $total_types,
                            "total_pets" => $total_pets
                        )
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Admins  $user
     * @return \Illuminate\Http\Response
     */
    public function edit_profile() {
        return view("dashboard.edit_profile")->with(array("user" => \Auth::user()));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Admins  $user
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request) {
        $admin = \Auth::user();
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            "email" => "email|unique:admins,a_email,$admin->a_id,a_id",
            "user_name" => "required",
        ]);

        if ($request->hasFile('user_image')) {
            $request->validate(["user_image" => "image|mimes:jpeg,png,jpg|max:5098"]);
        }

        $admin->a_first_name = $request->get("first_name");
        $admin->a_last_name = $request->get("last_name");
        $admin->a_user_name = $request->get("user_name");
        $admin->a_email = $request->get("email");

        $added_user = $admin->update();
        if ($added_user) {
            $admin_id = $admin->a_id;
            $image_name = "";
            //upload file and send welcome email
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path("/uploads/admins/" . $admin_id);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $image->move($destinationPath, $image_name);
                if (!empty($admin->a_image)) {
                    @unlink($destinationPath . "/" . $admin->a_image);
                }
            }
            if (!empty($image_name)) {
                $update_record = \App\Admins::find($admin_id);
                $update_record->a_image = $image_name;
                $update_record->update();
            }

            return redirect()->route("dashboard")->with("success", "Your profile details update Successfully");
        } else {
            return back()->withInput();
        }
    }

    /**
     * Show the form for change password the specified resource.
     *
     * @param  Admins  $user
     * @return \Illuminate\Http\Response
     */
    public function change_password() {
        return view("dashboard.change_password")->with(array("user" => \Auth::user()));
    }

    /**
     * Update password for the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Admins  $user
     * @return \Illuminate\Http\Response
     */
    public function update_password(Request $request) {
        $admin = \Auth::user();
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required|same:new_password',
        ]);


        if (!Hash::check($request->get("old_password"), $admin->a_password)) {
            return back()
                            ->with('error', 'The old password does not match the current password');
        } else {

            $admin->a_password = bcrypt($request->get("new_password"));

            $added_user = $admin->update();
            if ($added_user) {

                return redirect()->route("dashboard")->with("success", "Password update Successfully");
            } else {
                return back()->withInput();
            }
        }
    }

    public function get_city_list(Request $request) {
        $id = $request->get("id");
        $response = array();
        if (empty($id) || !is_numeric($id)) {
            $response['success'] = false;
            $response['message'] = "Invalid request!";
        } else {
            $get_all_records = Cities::select('city_id as id', 'city_name as name')->where('city_country_id', $id)->where('city_status', 1)->orderBy('city_name', 'ASC')->get();
            if (!empty($get_all_records)) {
                $response['all_records'] = $get_all_records->count() > 0 ? $get_all_records : array();
                $response['success'] = true;
                $response['message'] = "List found";
            } else {
                $response['message'] = "No record found.";
            }
        }

        return response()->json($response);
    }

    public function get_foundation_list(Request $request) {
        $id = $request->get("id");
        $city_id = $request->get("city_id");
        $response = array();
        if (empty($id) || !is_numeric($id) || empty($city_id) || !is_numeric($city_id)) {
            $response['success'] = false;
            $response['message'] = "Select valid Tour Category & City";
        } else {
            $get_all_records = \App\Foundations::select('f_id as id', 'f_name as name')
                    ->where('f_category_id', $id)
                    ->where('f_city_id', $city_id)
                    ->where('f_status', 1)
                    ->orderBy('f_name', 'ASC')
                    ->get();

            if (!empty($get_all_records)) {
                $response['all_records'] = $get_all_records->count() > 0 ? $get_all_records : array();
                $response['success'] = true;
                $response['message'] = "List found";
            } else {
                $response['message'] = "No record found.";
            }
        }

        return response()->json($response);
    }

    public function logout(Request $request) {
        $request->session()->flush();
        \Auth::logout();
        return redirect('/');
    }

}
