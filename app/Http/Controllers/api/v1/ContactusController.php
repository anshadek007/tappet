<?php

namespace App\Http\Controllers\api\v1;

use App\Contactus;
use Illuminate\Http\Request;
use App\Http\Controllers\api\v1\APIController;
use Validator;
use Mail;
use App\AESCrypt;

class ContactusController extends APIController {
       
    public function __construct(Request $request) {
        parent::__construct($request);
    }
    
    public function store(Request $request) {
        
        $request->merge([
            'con_title' => AESCrypt::decryptString($request->con_title),
            'con_email' => AESCrypt::decryptString($request->con_email),
//            'con_mobile_number' => AESCrypt::decryptString($request->con_mobile_number),
            'con_msg' => AESCrypt::decryptString($request->con_msg),
            'device_type' => AESCrypt::decryptString($request->device_type)
        ]);
         
        $getMessage = $this->validator($request->all())->errors()->first();
        if ($getMessage != "") {
            $message = ["result" => (object) array(), "message" => AESCrypt::encryptString($getMessage), "status" => false, "code" => 60];
            return response()->json($message, 200);
        }
        
        $request_data = $request->all();
        unset($request_data['device_type']);
        
        $contactus = Contactus::create($request_data);
        if($contactus)
        {
            try {
                $email = array($request->con_email);
                $title = $request->con_title;
                $maildata = array("title"=>$title,
                              "email"=>$request->con_email,
                              //"mobile_number"=>$request->con_mobile_number,
                              "msg"=>$request->con_msg,
                             );
                
                Mail::send('emails.contactus', $maildata, function ($message) use($email,$title) {
                    $message->to(env('MAIL_INFO_USER'));
                    $message->subject(env('APP_NAME')." : Contact Us");
                });
            } catch (\Exception $e) {
                return $this->respondWithError(AESCrypt::encryptString($e->getMessage()));
            }
        }

        $message = ["message" => AESCrypt::encryptString("Thank you for contacting us. We will be in touch with you very soon."), "status" => true, "code" => 0];

        return response()->json($message, 200);
    }
    
        /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data) {

        $rules = [
            'con_title' => ['required', 'string', 'max:255'],
            'con_email' => ['required', 'email', 'max:255'],
            //'con_mobile_number' => ['required', 'max:20'],
            'con_msg' => ['required'],
        ];

        //$request_email = isset($data['u_email']) ? $data['u_email'] : '';
        $customMessages = [
            'con_title.required' => 'Contact title is required field.',
            'con_title.string' => 'Contact title allows only alphabetical characters.',
            //'con_mobile_number.required' => 'Phone number is required field.',
            //'con_mobile_number.max' => 'Phone number may not be greater than 20 characters.',
            'con_email.required' => 'Email is required field.',
            'con_email.email' => "Email must be a valid email address.",
            'con_msg' => 'Message is required field.',
        ];

        return Validator::make($data, $rules, $customMessages);
    }

}
