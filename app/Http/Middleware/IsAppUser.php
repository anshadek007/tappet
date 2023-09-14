<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\User;

class IsAppUser {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $setting = 1;
        if ($setting == 1) {
            $token = !empty($request->header('appaccesstoken')) ? $request->header('appaccesstoken') : null;
            if ($request->ajax() || empty($token) || $token != 'A7UVIN#3943=T@b^Nbdhb7s3Sf_v@p_B') {
                return response()->json([
                            'success' => false,
                            'message' => 'Something went wrong.',
                            'code' => 401,
                            'result' => ''
                                ], 200);
            }
        }

        return $next($request);
    }

}
