<?php

namespace App\Traits;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

trait ResetsApiPasswords
{
    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return json response
     */
    public function apiReset(Request $request)
    {
        $getMessage = $this->validator($request->all())->errors()->first();

        if($getMessage != "") {

            $message = ["result" => (object)null, "message" => $getMessage,
                        "status" => "error", "code" => 60
                ];
            return response()->json($message, 200);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetApiPassword($user, $password);
            }
        );

        

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? $this->sendApiResetResponse($response)
                    : $this->sendApiResetFailedResponse($response);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetApiPassword($user, $password)
    {
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param  string  $response
     * @return json
     */
    protected function sendApiResetResponse($response)
    {
        $message = ["result" => (object)null, "message" => trans($response),
                    "status" => "success", "code" => 0
                ];

        return response()->json($message, 200);
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  string  $response
     * @return json
     */
    protected function sendApiResetFailedResponse($response)
    {
        $message = ["result" => (object)null, "message" => trans($response),
                    "status" => "error", "code" => 60
                ];

        return response()->json($message, 200);
    }

    
      /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker("users");
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard("api");
    }
}