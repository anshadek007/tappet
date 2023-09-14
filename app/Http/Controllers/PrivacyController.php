<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivacyController extends Controller {
    
    public function index() {
       return view("others.privacy");
    }
    
    public function faq() {
       return view("others.faq");
    }
    
    public function terms(){
       return view("others.terms"); 
    }
}