<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Password Reset Controller
      |--------------------------------------------------------------------------
      |
      | This controller is responsible for handling password reset requests
      | and uses a simple trait to include this behavior. You're free to
      | explore this trait and override any methods you wish to tweak.
      |
     */

use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/password/success';

    const INVALID_PASSWORD = 'passwords.a_password';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest');
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(\Illuminate\Http\Request $request, $token = null) {
        return view('auth.passwords.reset')->with(['token' => $token, 'a_email' => $request->a_email]);
    }

    public function showResetSuccessForm() {
        return view('auth.passwords.reset-success');
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules() {
        return [
            'token' => 'required',
            'a_email' => 'required|email',
            'a_password' => 'required|confirmed|min:6',
        ];
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password) {
        
        $user->a_password = \Hash::make($password);

        $user->setRememberToken(\Illuminate\Support\Str::random(60));

        $user->save();

        event(new \Illuminate\Auth\Events\PasswordReset($user));

        return redirect('/password/success');
//        return redirect()->back()->with('success', ['your message,here']);
//        return $this->showResetSuccessForm();
//        $this->guard()->login($user);
    }

     /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(\Illuminate\Http\Request $request) {
        return $request->only(
                        'a_email', 'a_password', 'a_password_confirmation', 'token'
        );
    }
}
