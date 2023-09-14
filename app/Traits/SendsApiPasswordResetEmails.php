<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\AESCrypt;
use DB;
use Carbon\Carbon;
use Hash;

trait SendsApiPasswordResetEmails {

    public function sendResetApiLinkEmail(Request $request) {

        $request->merge(['u_email' => $request->u_email]);
        $u_email = $request->u_email;
        $user = User::where('u_email', $u_email)
                ->where('u_status', '!=', 9);
        if (!empty($request->u_user_type) && $request->u_user_type == 4) {
            $user = $user->where('u_user_type', 4);
        }
        $user = $user->first();
        if (!$user) {
            return $this->sendResetApiLinkFailedResponse('Email address does not exist in system');
        }
        $token = str_random(60);
        
        // $this->validateRequestEmail($request);
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
                $request->only('u_email')
        );
        $passreset = DB::table('password_resets')->where('email',null)->first();
        if($passreset){
            DB::table('password_resets')
                ->where('email',null)
                ->update([
                    'email' => $request->u_email,
                ]);
        } else {
            $passreset = DB::table('password_resets')->insert([
                'email' => $request->u_email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);
        }
        return $response == Password::RESET_LINK_SENT ? $this->sendResetApiLinkResponse($response) : $this->sendResetApiLinkFailedResponse($request, $response);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  string  $response
     * @return json
     */
    protected function sendResetApiLinkResponse($response) {
        $message = ["result" => (object) null, "message" => trans($response), "status" => true, "code" => 0];
        return response()->json($message, 200);
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  string  $response
     * @return json
     */
    protected function sendResetApiLinkFailedResponse($response) {
        $message = ["result" => (object) null, "message" => trans($response), "status" => false, "code" => 60];
        return response()->json($message, 200);
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard() {
        return Auth::guard('api');
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker() {
        return Password::broker('users');
    }

}
