<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Closure;
use App\AESCrypt;
use App\ApiLogs;

class ApiDataLogger {

    private $startTime;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        return $next($request);
    }

    public function terminate($request, $response) {

        if (env('API_DATALOGGER', true)) {
            
            $top = ApiLogs::latest()->pluck('al_id')->first();
            ApiLogs::latest()->where('al_id', '<', $top-1000)->delete();
            
            $endTime = microtime(true);
            $logEntry = new ApiLogs();
            
            $request_parameter = array("params"=>$request->all(),
                                       "headers"=>$request->headers->all(),
                                       "attributes"=>$request->attributes->all(),
                                      );
            
            $logEntry->al_response_code = $response->getStatusCode();
            $logEntry->al_api_name = Route::currentRouteName();
            $logEntry->al_api_method = $request->method();
            $logEntry->al_ip_address = $request->ip();
            $logEntry->al_request_data =json_encode($request_parameter);
            $logEntry->al_response_data = $response->getContent();
            $logEntry->al_processing_time = number_format($endTime - LARAVEL_START, 3);
            $logEntry->al_device_type = trim($request->device_type);
            $logEntry->al_authorized = 1;
            
            $logEntry->save();

            /*$filename = 'api_datalogger_' . date('d-m-y') . '.log';
            $dataToLog = 'Time: ' . gmdate("F j, Y, g:i a") . "\n";
            $dataToLog .= 'Duration: ' . number_format($endTime - LARAVEL_START, 3) . "\n";
            $dataToLog .= 'IP Address: ' . $request->ip() . "\n";
            $dataToLog .= 'URL: ' . $request->fullUrl() . "\n";
            $dataToLog .= 'Method: ' . $request->method() . "\n";
            $dataToLog .= 'Input: ' . $request . "\n";
            $dataToLog .= 'Output: ' . $response->getContent() . "\n";
            \File::append(storage_path('logs' . DIRECTORY_SEPARATOR . $filename), $dataToLog . "\n" . str_repeat("=", 20) . "\n\n");*/
        }
    }

}
