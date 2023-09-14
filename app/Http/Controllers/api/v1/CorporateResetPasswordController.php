<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Traits\ResetsApiPasswords;
use Illuminate\Http\Request;
use App\BusinessUser;

class CorporateResetPasswordController extends Controller {
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

    use ResetsApiPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/password/success';

    const INVALID_PASSWORD = 'passwords.u_password';

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
        $get_password_resets = \DB::table('corporate_password_resets')
                        ->where('email', $request->email)
                        ->latest()->first();
        $password_resets = "";

        if (!empty($get_password_resets) && !empty($get_password_resets->token) && \Hash::check(trim($token), $get_password_resets->token)) {
            $password_resets = $get_password_resets;
        }

        return view('api.passwords.reset')->with(['token' => $token, 'email' => $request->email, 'password_resets' => $password_resets,'corporate' => 'true']);
    }

    public function showResetSuccessForm() {
        return view('api.passwords.reset-success');
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules() {
        return [
            'token' => 'required',
            'u_email' => 'required|email',
            'u_password' => 'required|confirmed|min:6',
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

        $user->u_password = \Hash::make($password);

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
                        'u_email', 'u_password', 'u_password_confirmation', 'token'
        );
    }

    public function reset(Request $request) {
        $request->validate([
            'token' => 'required',
            'u_email' => 'required|email',
            'u_password' => 'required|confirmed|min:6',
        ]);

        $get_password_resets = \DB::table('corporate_password_resets')
                        ->where('email', $request->u_email)
                        ->latest()->first();

        $password_resets = "";

        if (empty($get_password_resets) || empty($get_password_resets->token) && !\Hash::check(trim($request->token), $get_password_resets->token)) {
            return back()->withInput();
        }

        $get_user = BusinessUser::select("*")->where("u_email", trim($request->get('u_email')))->first();
        if ($get_user) {
            $user = BusinessUser::find($get_user->u_id);
            $password = $request->get('u_password');
            $user->u_password = \Hash::make($password);
            $user->save();

            \DB::table('corporate_password_resets')->where('email', $request->get('u_email'))->delete();

            return redirect('/password/success');
        } else {
            return back()->withInput();
        }
    }

}
