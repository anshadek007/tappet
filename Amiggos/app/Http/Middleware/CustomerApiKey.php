<?php

namespace App\Http\Middleware;

use Closure;
use Config;
use App\Guest;
use App\Http\Controllers\Api\ApiController;

class CustomerApiKey extends ApiController
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
         
        $req    = $request->headers->all();
        $input  = $request->all();
        $uri    = $request->path();
        $log_path = Config::get('constants.apiLog_path'); 
        // $log_path = storage_path('app_log').'/'.date('Y_m_d').'_apiCall.log'; 
        $message1 = "\n".date('Y-m-d H:i:s')." Step 1 : ".$uri."  Api call with input ".json_encode($input);
        error_log($message1,3,$log_path);
        
        if(empty($input['userid'])){
            return parent :: api_response((object)[],false,"Unauthrized.",400);
        }
        elseif(empty($req['api-key'][0])){
            return parent :: api_response((object)[],false,"Your session has been expired please login again.",404);
        }


        $check_userToken  = Guest::where("userid","=",$input['userid'])->where("api_token","=",$req['api-key'][0])->get();

        
        if(empty($check_userToken[0]->userid)){
            return parent :: api_response((object)[],false,"Your session has been expired please login again.",404);
        }
         $log_path = Config::get('constants.apiLog_path'); 
        $message1 = "\n".date('Y-m-d H:i:s')." Step 1 : ".$uri."  Api call with input ".json_encode($input);
        error_log($message1,3,$log_path);
        return $next($request);
    }
}
