<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller {

    /**
     *
     * @var type 
     */
    protected $adminModel;

    /**
     * Create a new controller instance.
     *
     */
    public function __construct() {
        $this->adminModel = new \App\Admins();

        $this->middleware('guest')->except('logout');
        $this->middleware('guest:admin')->except('logout');
    }

    public function username() {
        return 'a_user_name';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        if (\Auth::check()) {
            return redirect('dashboard');
        } else {
            return view("login");
        }
    }

    public function login(Request $request) {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);


        $email = $request->get("email");
        $password = $request->get("password");
        $remember = !empty($request->get("remember")) && $request->get("remember") == "on" ? true : false;

        $user_data = Auth::guard('admin')->attempt(['a_email' => $email, 'password' => $password, 'a_status' => 1], $remember);


        $is_valid_login = false;
        if ($user_data) {
            $is_valid_login = true;
        } else {
            $user_data = Auth::guard('admin')->attempt(['a_user_name' => $email, 'password' => $password, 'a_status' => 1], $remember);
            if ($user_data) {
                $is_valid_login = true;
            }
        }
        if ($is_valid_login) {
            $user_data = Auth::guard('admin')->user();
            $user_id = $user_data->a_id;
            $user_roles_data = $user_data->getUserRole;
            if (!empty($user_roles_data)) {
                $role_permissions = json_decode($user_roles_data->role_permissions, true);
                $role_types_ids = array();
                if (!empty($role_permissions)) {
                    foreach ($role_permissions as $key => $value) {
                        $role_types_ids[] = $key;
                    }
                }

                //GET USER PERMISSION
                $get_all_permissions_controller_names = \App\UserRolesTypes::whereIn("urpt_id", $role_types_ids)
                        ->select("urpt_id", "urpt_controller_name")
                        ->where("urpt_status", 1)
                        ->get();
                $role_permissions_array = array();
                foreach ($get_all_permissions_controller_names as $sinlge_value) {
                    $role_permissions_array[$sinlge_value->urpt_controller_name] = $role_permissions[$sinlge_value->urpt_id];
                }
                $request->session()->put("user_access_permission", $role_permissions_array);

                //GET USER CHILDS
                $userChilds = $this->adminModel->getChilds($user_id);
                $allowedChildIds = !empty($userChilds) ? $userChilds . "," . $user_id : $user_id;

                $request->session()->put("user_child_ids", $allowedChildIds);

                //UPDATE USER LAST LOGIN
                $updateuser = \App\Admins::find($user_id);
                $updateuser->a_last_login = now();
                $updateuser->save();
            } else {
                $request->session()->put("user_access_permission", array());
            }

            return redirect('dashboard');
        }

        return \Redirect::back()->withErrors(["Invalied email or password"]);
    }

    public function demo() {
        return view("demo");
    }

     public function test_send_email(){
        try{
            $to = "bodarmanish777@gmail.com";
           // $to = "nitinvaghani34@mailinator.com";
            $from = [
                env('MAIL_FROM_ADDRESS') => env('MAIL_FROM_NAME')
            ];
            \Mail::raw('Hello World!', function($msg) use($from, $to){
                $msg->to($to)
                        ->from('test@gmail.com')
                        ->subject('Test Email'); 
            });
        } catch (\Exception $e){
            dd($e->getMessage());
        }
        
        echo 'Mail Sent';die;

    }
}
