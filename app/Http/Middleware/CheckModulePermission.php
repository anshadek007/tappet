<?php

namespace App\Http\Middleware;

use Closure;

class CheckModulePermission {

    private $abilities = [
        'index' => 'index',
        'show' => 'index',
        'edit' => 'edit',
        'update' => 'edit',
        'create' => 'create',
        'store' => 'create',
        'delete' => 'destroy',
        'import' => "import",
        "export" => "export",
        "load_data_in_table" => "index",
        "change_status" => "edit",
        "load_advertisement_data_in_table" => "index"
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $module_permissions = array();
        $authorize = FALSE;
        if ($request->session()->has("user_access_permission")) {
            $module_permissions = $request->session()->get("user_access_permission");
        }
        if (!empty($module_permissions)) {
            $routeName = explode('.', \Request::route()->getName());
            if (!empty($routeName)) {
                $action = array_get($this->abilities, $routeName[1]);
                if (!empty($module_permissions[$routeName[0]]) && in_array($action, $module_permissions[$routeName[0]])) {
                    $authorize = TRUE;
                }
            }
        }

        if ($authorize) {
            return $next($request);
        } else {
            if (!$request->expectsJson()) {
                return redirect()
                                ->route("dashboard")
                                ->with("error", "You have not permission to access this functionality");
            } else {
                return \Response::json(array(
                            'success' => FALSE,
                            'message' => "You have not permission to access this functionality"
                ));
            }
        }
    }

}
