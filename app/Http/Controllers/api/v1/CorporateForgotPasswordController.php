<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Traits\SendsApiCorporatePasswordResetEmails;
use DB;
use App\BusinessUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Notifiable;

class CorporateForgotPasswordController extends Controller {

    use SendsApiCorporatePasswordResetEmails;
    //use Notifiable;

    // public function sendResetApiLinkEmail(Request $request) {
    //     $user_data = array();
    //     if (!empty($request->u_email)) {
    //         $user_data = BusinessUser::where('u_email', $request->u_email)->first();
    //     }

    //     $request->merge(['u_email' => $request->u_email]);
    //     $u_email = $request->u_email;
    //     $user = BusinessUser::where('u_email', $u_email)
    //             ->where('u_status', '!=', 9);
    //     if (!empty($request->u_user_type) && $request->u_user_type == 4) {
    //         $user = $user->where('u_user_type', 4);
    //     }
    //     $user = $user->first();

    //     if (!$user) {
    //         return $this->sendResetApiLinkFailedResponse('Email address does not exist in system');
    //     }

    //     $successMessage = "Failed to send OTP";
    //     if (!empty($user_data)) {
            
    //         if (!empty($request->u_email)) {
    //             //Create Password Reset Token
    //             $token = str_random(60);
    //             $passreset = DB::table('password_resets')->where('email',$request->u_email)->first();
    //             if($passreset){
    //                 DB::table('password_resets')
    //                     ->where('email',$request->u_email)
    //                     ->update([
    //                         'token' => $token,
    //                         'created_at' => Carbon::now()
    //                     ]);
    //             } else {
    //                 $passreset = DB::table('password_resets')->insert([
    //                     'email' => $request->u_email,
    //                     'token' => $token,
    //                     'created_at' => Carbon::now()
    //                 ]);
    //             }
                
    //             $email = array($user_data->u_email);
    //             $get_password_resets = \DB::table('password_resets')
    //                 ->where('email', $request->u_email)
    //                 ->latest()->first();

    //                 $password_resets = "";

    //                 if (!empty($get_password_resets) && !empty($get_password_resets->token)) {
    //                     $password_resets = $get_password_resets->token;
    //                 }
    //                 //return response()->json($password_resets, 200);

    //                 $data = array(
    //                     'token' => $token,
    //                     'email' => $request->u_email,
    //                     'password_resets' => $password_resets,
    //                 );
    //                 BusinessUser::first()->notify(new ResetPasswordNotification($data));
    //             // Mail::send('api.passwords.reset', $data, function ($message) use($email) {
    //             //     $message->from('Tappet@admin.com', config('app.name'))
    //             //             ->to($email);
    //             //             // ->greeting('Hello Pat Tap member,')
    //             //             // ->subject('Your new password for Tap Pet application')
    //             //             // ->line('Please select a new password using the following link:')
    //             //             // ->action('Reset Password', url('api/password/reset?email=' . $request->u_email, $token))
    //             //             // ->line('If you are unable to open the link by clicking on it, paste the following Internet address into your Internet browser to change your password that way:')
    //             //             // ->line('We hope you enjoy using Tap Pet!');
    //             // });


    //         }
    //         $message = ["result" => (object) array(), "message" => "We have e-mailed your password reset link!", "status" => true, "code" => 0];

    //     } else {
    //         $message = ["result" => (object) array(), "message" => "Invalid request", "status" => false, "code" => 0];
    //     }

    //     return response()->json($message, 200);
    // }

    // protected function sendResetApiLinkResponse($response) {
    //     $message = ["result" => (object) null, "message" => trans($response), "status" => true, "code" => 0];
    //     return response()->json($message, 200);
    // }

    // protected function sendResetApiLinkFailedResponse($response) {
    //     $message = ["result" => (object) null, "message" => trans($response), "status" => false, "code" => 60];
    //     return response()->json($message, 200);
    // }
}