<?php

namespace App\Http\Controllers\API\Venue;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
class ApiController extends Controller
{
    //
    /**
     * API response function to be used by all endpoints
     * @param array $body
     * @param null $message
     * @param bool $success
     * @param int $status
     * @return JsonResponse response
     */
    public function api_response($data, $success = true, $message = null, $status = 200,$languageId=''){
     
       if(empty($data)){
          $data = (object)$data;
       }
       
         $payload=[
                'status' => $success,
                'message' => $message,
                'data'=>$data
         ];


        return Response::json($payload,$status);
    }

   public function checkKeyExist($key,$input){
        $err_msg="";
        if(!array_key_exists($key,$input)){
           $err_msg = "Please provide key ".$key.".";
          
          }
         return $err_msg; 
   }

    public function getUserCurrentLanguage( $user_id ){

        $language_data = DB::table('user_preference as up')
                  ->leftJoin('language as l', 'l.id', '=', 'up.language_id')
                  ->where('up.user_id','=',$user_id)
                  ->select('up.language_id','l.iso2_code')
                  ->get()->toArray();  
       if( isset($language_data) && !empty($language_data)){
          return array(
                        "language_id" => $language_data[0]->language_id,
                        "iso2_code"   => $language_data[0]->iso2_code
                      );
       }else{

        return array();
       }
    }

    public function getLanguageValues( $request , $language_code = ""){
       /*
        $req =   $request->headers->all();
         if(!empty($req['language-code'][0])){
             $language_code = $req['language-code'][0];
         }
        
        */
        if(!empty($language_code)){
          
            $local_head = $language_code;
            
        }else{

          $req =   $request->headers->all();

          if(isset($req['language-code'][0]) && !empty($req['language-code'][0])){
              $local_head = $req['language-code'][0];
          }else{
              $local_head = 'en';
          }
        }

        $data = array();
        $csvData = array();

        $language_data = DB::table('language')
                  ->where('status','=',1)
                  ->where('iso2_code','=',$local_head)
                  ->whereNull('deleted_at') 
                  ->select('csv_file')
                  ->get()->toArray();  
                 
        if(isset($language_data[0]->csv_file) && !empty($language_data[0]->csv_file) ){
          
             $fileName = "./public/uploads/language_file/".$language_data[0]->csv_file;
             
             if(!file_exists($fileName)){
                $data['status'] = 0;
                $data['csvData'] = $csvData;

                return $data;
             }

             if (($handle = fopen($fileName, "r")) !== FALSE) {
                 fgetcsv($handle); //To skip the first line from the csv file

                 while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                     $csvData[$data[0]] = str_replace("<br/>","\n",$data[1]);
                 }
                 
                 fclose($handle);
             }

             $data['status'] = 1;
             $data['csvData'] = $csvData;
             foreach($data['csvData'] as $k=>$v){
                     $data['csvData'][$k]=mb_convert_encoding($v, 'UTF-8', 'UTF-8');
                 }
                
        }else{
             $data['status'] = 0;
             $data['csvData'] = $csvData;
        }
       
        return $data;
    }

    public function getErrorMsg($err_arr = array() , $request){
          
       
        $data = $this->getLanguageValues($request);
        
        $csvData = array();

        if($data['status'] == 1){

             if( isset($data['csvData']) && !empty($data['csvData']) ){
                
                $csvData = $data['csvData'];
             }

        }else{
            
            $err_msg = "This language will available soon on next update. (please select another language)";

            return $err_msg;
        }
    
        $first_key = array_keys($err_arr);
          $is_require = 1;
        if($err_arr[$first_key[0]][0] == "The image must be an image."){
          
          $first_key[0] ='image1'; 
          $is_require = 0;
        }
        
        if($err_arr[$first_key[0]][0] == "The image must be a file of type: jpeg, png, jpg, gif, svg."){

          $first_key[0] ='image2'; 
          $is_require = 0; 
        }

        if (strpos($err_arr[$first_key[0]][0],"digits.") !== false) {
            $is_require = 0;
            $err_msg = $data['csvData']["validate_digit_error"];
            $err_msg = str_replace("@#@$",$first_key[0],$err_msg);
            return $err_msg;  
        }

        if($is_require == 0){

            return $data['csvData'][$first_key[0]];
        }else{
          $err_msg = $data['csvData']["validate_require_error"];
          $err_msg = str_replace("@#@$",$first_key[0],$err_msg);
          return $err_msg;
        }
    }
    
    public function sendNotification($device_type , $devicetoken, $noti_msg, $subject, $notify_data = ""){
              
            $deviceToken = $devicetoken;
            $json = [];

            if($device_type == '1'){
                //stream_context_set_option($ctx, 'ssl', 'local_cert', 'public/uploads/'."/PEM/AmiggosDevlopment.pem"); (Developement Pem ) 
                $ctx = stream_context_create();
                // ck.pem is your certificate file
                //for production
                //stream_context_set_option($ctx, 'ssl', 'local_cert', 'public/uploads/'."/PEM/AmiggosDistribution.pem");
                //for developement
                stream_context_set_option($ctx, 'ssl', 'local_cert', 'public/uploads/'."/PEM/AmiggosDevlopment.pem");

                stream_context_set_option($ctx, 'ssl', 'passphrase', 'tekzee@123');
                // Open a connection to the APNS server
                //ssl://gateway.push.apple.com:2195 <-- production uRL
                 //ssl://gateway.push.apple.com:2195 <-- development uRL
                $fp = stream_socket_client(
                  'ssl://gateway.sandbox.push.apple.com:2195', $err,
                  $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
                if (!$fp)
                  exit("Failed to connect: $err $errstr" . PHP_EOL);
                // Create the payload body
                $body['aps'] = array(
                  'alert' => array(
                      'title' => $subject,
                      'body'  =>  $noti_msg,
                      'data'  =>  $notify_data
                   ),
                  'sound' => 'coin.aiff'
                );
                // Encode the payload as JSON
                $payload = json_encode($body);
                // Build the binary notification
                $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                // Send it to the server
                $result = fwrite($fp, $msg, strlen($msg));
                
                // Close the connection to the server
                fclose($fp);
                if (!$result){
                  $json['success'] = false;
                  $json['message'] = 'Message not delivered' . PHP_EOL;
                }else{
                  $json['success'] = true;
                  $json['message'] = 'Message successfully delivered' . PHP_EOL;
                }

           }else{

              $registrationIds = $deviceToken;
              #prep the bundle
              $msg = array(
                  'title' => $subject,
                  'message' => $noti_msg,
                  'timestamp' => date('Y-m-d H:i:s')
              );


              $fields = array(
                          'to' => $registrationIds,
                          'data' => $msg
                        );


              $headers = array(
                          'Authorization: key=AIzaSyCJmCpQ-WTG6wBpqzUR9rGispWb_gRhJ5c',
                          'Content-Type: application/json'
              );
              #Send Reponse To FireBase Server  
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
              curl_setopt($ch, CURLOPT_POST, true);
              curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
              $result = curl_exec($ch);
              curl_close($ch);
              #Echo Result Of FireBase Server
              $result = json_decode($result);

              if ($result){
                  if($result->success){
                    // $json["success"] = true;
                    $json["success"] = true;
                    $json["message"] = "Notication not send". PHP_EOL;
                      
                  }else{
                      $json["success"] = FALSE;
                      $json["message"] = "Notication not send". PHP_EOL;
                  }
              }else{
                  $json["success"] = FALSE;
                  $json["message"] = "Something went wrong". PHP_EOL;
              } 
           }

           return $json;
    }

    public function sendNotification_old($device_type , $devicetoken, $noti_msg, $subject , $data = "" ){
            
              /*echo $device_type." , ".$devicetoken.", ".$noti_msg.", ".$subject." , ".$data ;die;*/
            $deviceToken = $devicetoken;
            $json = [];
            
            if($device_type == '1'){

                $ctx = stream_context_create();
                // ck.pem is your certificate file
                stream_context_set_option($ctx, 'ssl', 'local_cert', 'public/uploads/PEM/AmiggosDevlopment.pem');
                 
//  stream_context_set_option($ctx, 'ssl', 'local_cert', 'public/uploads/PEM/AmiggosDevlopment.pem');
                stream_context_set_option($ctx, 'ssl', 'passphrase', 'tekzee@123');
                // Open a connection to the APNS server
                //ssl://gateway.push.apple.com:2195 <-- production uRL
                //ssl://gateway.sandbox.push.apple.com:2195 <-- development uRL
                $fp = stream_socket_client(
                  'ssl://gateway.push.apple.com:2195', $err,
                  $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
                if (!$fp)
                  exit("Failed to connect: $err $errstr" . PHP_EOL);
                // Create the payload body
                $body['aps'] = array(
                  'alert' => array(
                      'title' => $subject,
                      'body'  =>  $noti_msg,
                      'data'  => $data
                   ),
                  'sound' => 'default'
                );
                // Encode the payload as JSON
                $payload = json_encode($body);
                // Build the binary notification
                $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                //$msg = chr(0) . pack('n', 32) . strtr(rtrim(base64_encode(pack('H*', sprintf('%u', CRC32($deviceToken)))))) . pack('n', strlen($payload)) . $payload;
                

                // Send it to the server
                $result = fwrite($fp, $msg, strlen($msg));
                
                // Close the connection to the server
                fclose($fp);
                if (!$result){
                  $json['success'] = false;
                  $json['message'] = 'Message not delivered' . PHP_EOL;
                }else{
                  $json['success'] = true;
                  $json['message'] = 'Message successfully delivered' . PHP_EOL;
                }

           }else{

              $registrationIds = $deviceToken;
              #prep the bundle
              $msg = array(
                  'title'     => $subject,
                  'message'   => $noti_msg,
                  'timestamp' => date('Y-m-d H:i:s'),
                  'data'      => $data
              );


              $fields = array(
                          'to' => $registrationIds,
                          'data' => $msg
                        );


              $headers = array(
                          'Authorization: key=AIzaSyCJmCpQ-WTG6wBpqzUR9rGispWb_gRhJ5c',
                          'Content-Type: application/json'
              );
              #Send Reponse To FireBase Server  
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
              curl_setopt($ch, CURLOPT_POST, true);
              curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
              $result = curl_exec($ch);
              curl_close($ch);
              #Echo Result Of FireBase Server
              $result = json_decode($result);

              if ($result){
                  if($result->success){
                    // $json["success"] = true;
                    $json["success"] = true;
                    $json["message"] = "Notication not send". PHP_EOL;
                      
                  }else{
                      $json["success"] = FALSE;
                      $json["message"] = "Notication not send". PHP_EOL;
                  }
              }else{
                  $json["success"] = FALSE;
                  $json["message"] = "Something went wrong". PHP_EOL;
              } 
           }

           return $json;
    }
    
     public function fcmNotification($msg, $fields, $to) {
       #API access key from Google API's Console
       /*  define( 'API_ACCESS_KEY', 'YOUR-SERVER-API-ACCESS-KEY-GOES-HERE' ); */
       //$fields["timeToLive"]=60;

       if ($to == 'userapp') {
           //define('API_ACCESS_KEY','');
           // $key = "AAAAuIvKrEM:APA91bEix38_XugJjJfB3HregtLFzg0xhMJ_0-PMvIzTca7SrdNFs2eBcKYAdq0lY0oiDKmW0ccDoMCsp-nNhkVT90kwJMpAxs2kCmjlcZebEK4VhQK9Qi1-aqIJcxpAc9dcIEebamsEZG5rUHdW1LIk-MDcBF5Xqg";
           //live
           $key = "AAAAUYm6Qvo:APA91bH64ki0WzDhtLmeOB3-rP5NuJsouLaPOBrlZpDU34326qsVzdrPuEP4FNqJ654GzKlcOsk2qmAxV_UXxxBx3bC31kb6dVBJnPDBmBaIxZXvKLLdcJ22dp4uqqcppiJ2mrRRXdEr";

       } else if ($to == 'customerapp') {
           //customer app
           // $key = "AAAAk5ejpl4:APA91bHrLr9l6rKfceoEK_lECAh3rkyQhFN9MthlN2u7EissI5UIt3UuWD3wf6bXyJBmt6OUfE32vTr67NT_nZmVXsKPDiEinXessPsLdM6W3mYj6aMi6yCj7aRjBbXuQLGfHkEdQX6iVKGUA5Xb5cZtRLOrBQhIlA";

           $key = "AAAAji3qdWk:APA91bGq1dWOjVHLiDZt9JXOorasxGtuAKyT49yjyHc0ShlNuptQ7KUNuf4k15dtWPg_ePXvgNCJdPGL6j7owl3qKROehPzbSXkInpGS_bTnNOMGy4yJYaH4jjQNgINgr9BJnnT7c8Uk";



       }
       //define('API_ACCESS_KEY',$key);  
       $headers = array
           (
           'Authorization: key=' . $key,
           'Content-Type: application/json'
       );
           //print_r($fields);die;
       unset($fields['notification']);    
       #Send Reponse To FireBase Server    
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $result = curl_exec($ch);
       curl_close($ch);
     
       #Echo Result Of FireBase Server
   }
}
