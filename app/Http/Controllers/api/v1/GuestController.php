<?php

namespace App\Http\Controllers\api\v1;

use App\GuestUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use App\UserDeviceToken;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\TokenRepository;
use Validator;
use Exception;
use App\Traits\ConversationIdGenerator as zego;

class GuestController extends Controller
{
  


    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required'],
            'email' => ['required', 'email', 'max:255']
        ];
        return Validator::make($data, $rules, $customMessages = []);
    }


    public function sign_up(Request $request)
    {
        try {
            $request->merge([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            $getMessage = $this->validator($request->all())->errors()->first();

            if ($getMessage != "") {
                $message = ["result" => (object) array(), "message" => $getMessage, "status" => false, "code" => 60];
                return response()->json($message, 200);
            }

            $user_data = User::select('u_id','u_email','u_first_name','u_last_name')->where('u_email', $request->email)->first();
            if (empty($user_data)) {
                // $request_data['conversation_id'] = $this->conversation_id_generator();
                $request_data['is_guest'] = 1;
                $request_data['u_email'] = $request->email;
                $request_data['u_first_name'] = $request->name;
                $request_data['u_last_name'] = "";
                $user_data = User::create($request_data);
                $token = $user_data->createToken("app_user")->accessToken;
                $user_data['token'] = $token;
                $message = "Guest user login successfully";
            } else {
                $token = $user_data->createToken("app_user")->accessToken;
                $user_data['token'] = $token;
                $message = "User login successfully";
            }
            $user_id = 'zegouser_'.$user_data->u_id;
            $remainTimeInSecond = 3600 * 24;
            $zego = new zego;
            $zego_key = $zego->zego_key($request->server_secret,$request->app_id,$user_id,$remainTimeInSecond);
            $user_data['zego_token'] =  $zego_key;
            $user_data['zego_token_expiry'] =  $remainTimeInSecond;
            $message = ["result" => $user_data, "message" => $message, "status" => true, "code" => 0];
            return response()->json($message, 200);
        } catch (Exception $e) {
            echo 'Message: ' . $e->getMessage();
        }
    }
}
