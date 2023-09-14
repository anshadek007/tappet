<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Traits\SendsApiPasswordResetEmails;

class ForgotPasswordController extends Controller {

    use SendsApiPasswordResetEmails;
   
}