<?php

namespace App\Http\Controllers\api\v1;

use App\Feedback;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\AESCrypt;

class FeedbackController extends APIController {

    protected $feedbackModel;
    protected $userModel;

    public function __construct(Request $request) {
        parent::__construct($request);
        $this->feedbackModel = new Feedback();
        $this->userModel = new User();
    }

    public function store(Request $request) {
        $user = $this->userModel->validateUser(Auth::user()->u_id);
        if (!$user) {
            return $this->respondResult("", 'User Not Found', false, 200);
        }

        $request->merge([
            'message' => AESCrypt::decryptString($request->message),
        ]);


        $rules = [
            'message' => ['required', 'max:2000'],
        ];

        $customMessages = [
            'message.required' => "Feedback is required field.",
            'message.max' => "Feedback allows maximum 1000 characters only.",
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            return $this->respondWithError($validator->errors()->first());
        }

        $request->merge([
            'name' => $user->u_user_name,
            'email' => $user->u_email,
            'desc' => $request->message
        ]);
//dd($request->all());
        $feedback = new Feedback();
        $feedback->f_content = $request->message;
        $feedback->f_user_id = Auth::user()->u_id;
        $feedback->save();
        $this->sendmail($request);
        return $this->respondResult("", AESCrypt::encryptString("Your Feedback has been sent."));
    }

    public function sendmail($request) {
        $data = [
            "name" => $request->name,
            "email" => $request->email,
            "desc" => $request->message,
        ];

        $view = "emails.feedback";
        $subject = "New feedback mail";
        $sender = array(env("MAIL_USERNAME", "charlie211091@gmail.com") => env("MAIL_FROM_NAME", "Ropes"));

        Mail::send($view, $data, function($message) use ($sender, $data, $subject) {
            $message->from($sender);
            $message->to($data["email"], $data["name"])->subject($subject);
        });
    }

}
