<?php

namespace App\Http\Middleware;

use Closure;
use Config;
use App\User;
use App\Http\Controllers\API\ApiController;
use Illuminate\Support\Facades\DB;

class PartnerApikey extends ApiController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      
         $req = $request->headers->all();
         $input= $request->all();
         $uri  = $request->path();

         if(empty($input['userid'])){
            return parent :: api_response((object)[],false,"Please enter userid.",200);
         }
          if(!array_key_exists("api-key",$req)){
           $err_msg = "Please provide key api-key in header .";
           return parent :: api_response([],false,$err_msg,200);
          }
         if(empty($req['api-key'][0])){
           return parent :: api_response((object)[],false,"Please enter value in api-key in header.",200);
             
         }
         $check_userToken  = DB::table("users")->where("id","=",$input['userid'])->where("api_key","=",$req['api-key'][0])->get();

        
         if(empty($check_userToken[0]->id)){
          return parent :: api_response([],false,"Your session has been expired please login again.",400);
             
         }         
        $log_path = Config::get('constants.apiLog_path'); 
        $message1 = "\n".date('Y-m-d H:i:s')." Step 1 : ".$uri."  Api call with input ".json_encode($input);
        error_log($message1,3,$log_path);
        return $next($request);
    }
}
