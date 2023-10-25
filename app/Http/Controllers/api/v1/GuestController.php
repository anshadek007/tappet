<?php

namespace App\Http\Controllers\api\v1;

use App\GuestUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ConversationIdGenerator;
use App\User;
use App\UserDeviceToken;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\TokenRepository;
use Validator;
use Exception;


class GuestController extends Controller
{
    use ConversationIdGenerator;


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


            //check guest user or permanent user
            $user_data = User::where('u_email', $request->email)->first();
            if (!empty($user_data)) {
                $message = ["result" => (object) array(), "message" => "Email already exists Please login to continue", "status" => false, "code" => 60];
                return response()->json($message, 200);
            }

            //check guest alreday exists or not 

            $user_data = GuestUser::where('email', $request->email)->first();
            if (empty($user_data)) {
                $request_data = $request->all();
                $request_data['conversation_id'] = $this->conversation_id_generator();
                $guest_data = GuestUser::create($request_data);
                $token = $guest_data->createToken("guest_user")->accessToken;
                $guest_data['token'] = $token;
            } else {
                $guest_data =  $user_data;
                $token = $guest_data->createToken("guest_user")->accessToken;
                $guest_data['token'] = $token;
            }

            $message = ["result" => $guest_data, "message" => 'Signup successfully completed', "status" => true, "code" => 0];
            return response()->json($message, 200);
        } catch (Exception $e) {
            echo 'Message: ' . $e->getMessage();
        }
    }
}
