<?php

namespace App\Http\Middleware;

use Route;
use Auth;
use Closure;
use Session;
use App\User\Constants;
use App\Http\AppResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;

class TermsAndConditionsPopUp
{
   const TERMS_AND_CONDITIONS_WHITELIST_ROUTE_NAMES = [
       'user_logout',
   ];
   const SHOW_TNC_POPUP = 'show_tnc_popup';
   const TERMS_AND_CONDITIONS_WHITELIST_ROUTE_PATHS = [
       'merchant/activation',
   ];

   public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->cache = $app['cache'];
    }

   public function handle(Request $request, Closure $next)
    {
        $routeName = Route::currentRouteName();

        $route = Route::current();

        $shouldShowPopUp = Session::get(self::SHOW_TNC_POPUP);

        /**if the shouldShowPopUp is true, then only we will block the requests.*/
       if($shouldShowPopUp === true)
       {
           if(in_array($routeName,self::TERMS_AND_CONDITIONS_WHITELIST_ROUTE_NAMES) === true or
           in_array($route->path,self::TERMS_AND_CONDITIONS_WHITELIST_ROUTE_PATHS) === true)
           {
                return $next($request);
           }

           (new UserController())->getLogout();

           return AppResponse::unauthorizedResponse('Unauthorized.', $routeName, $route->path);
       }

       return $next($request);
    }

    /**Checks if the route is merchant/activation, method is post and response is success. if
     above conditions are satisfied we will change the value of SHOW_TNC_POPUP to false
     */
    public function terminate(Request $request, $response) : void
    {
        $route = Route::current();
        $method = $request->getMethod();

        if(in_array($route->path,self::TERMS_AND_CONDITIONS_WHITELIST_ROUTE_PATHS) === true &&
            $method === "POST")
        {
            if(empty($response->original) === false and
                is_array($response->original) === true and
                $response->original['success']=== true)
            {
                Session::put(self::SHOW_TNC_POPUP,false);
                Session::save();
            }
        }
    }
}
