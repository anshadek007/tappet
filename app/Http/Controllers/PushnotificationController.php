<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PushnotificationController extends Controller {

    public function index() {
        return view('pushnotification.send');
    }

    public function send(Request $request) {
        $request->validate([
            'push_content' => 'required',
            'push_target' => 'required',
        ]);
        
        $push_content = $request->get("push_content");
        $push_target = $request->get("push_target");
        
        $notification_data = new \App\NotificationsData();
        $notification_data->nd_content = $push_content;
        $notification_data->nd_target = $push_target;
        if($notification_data->save()){
            $process = new \Symfony\Component\Process\Process("php artisan sendpush $notification_data->nd_id >>/dev/null 2>&1");
            $process->start();
            
            return redirect()->route("pushnotification.index")->with("success","The system starts sending push notification");
        }else{
            return redirect()->route("pushnotification.index")->with("failer","Failed to send general push notification");
        }
    }

}
