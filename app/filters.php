<?php

namespace App;

use Razorpay\Api\Request as ApiRequest;
use App\Http\AppResponse;
use App\Http\SlackResponse;

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
    // Set Cookie for use by angular (Setting in after filter fails selenium tests, hence queuing in before filter)
    Cookie::queue('XSRF-TOKEN', csrf_token(), 0, '/', null, false, false);
});


App::after(function($request, $response)
{
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN', true);
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth.user', function()
{
    if (Auth::user()->guest())
    {
        return Response::json(array('success' => false, 'data' => array()));
    }
    else
    {
        $user = Auth::user();
        ApiRequest::addHeader('X-Dashboard-Merchant', $user->email);

        // Just in case an admin user is performing the action
        $admin = Auth::admin()->user();
        if ($admin)
        {
            $adminUsername = $admin->username;
            ApiRequest::addHeader('X-Dashboard-Username', $adminUsername);
        }
    }
});

Route::filter('slack', function()
{
    $slackToken = Config::get('razorpay.slack.command_token');
    $tokenFromInput = Input::get('token', false);

    if ($slackToken !== $tokenFromInput)
    {
        return SlackResponse::jsonResponse("Invalid Slack Token");
    }
});

Route::filter('auth.admin', function()
{
    if (Auth::admin()->guest())
    {
        return Response::json(array('success' => false, 'data' => array()));
    }
    else
    {
        $adminUsername = Auth::admin()->user()->username;
        ApiRequest::addHeader('X-Dashboard-Username', $adminUsername);
    }
});

Route::filter('auth.superadmin', function()
{
    if (Auth::admin()->user()->isSuperAdmin() === false)
    {
        return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
    }
});

Route::filter('auth.internal', function() use ($app)
{
    if (($_SERVER['PHP_AUTH_USER'] !== \Config::get('api.auth_user')) or
        ($_SERVER['PHP_AUTH_PW'] !== \Config::get('api.auth_pass')))
    {
        return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
    }
});

Route::filter('auth.cron', function() use ($app)
{
    if (($_SERVER['PHP_AUTH_USER'] !== \Config::get('cron.auth_user')) or
        ($_SERVER['PHP_AUTH_PW'] !== \Config::get('cron.auth_pass')))
    {
        return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
    }
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest.user', function()
{
    if (Auth::user()->check())
    {
        return AppResponse::jsonResponse(
            array("You are already logged in, please refresh and try again"));
    }
});

Route::filter('guest.admin', function()
{
    if (Auth::admin()->check())
    {
        return AppResponse::jsonResponse(
            array("You are already logged in, please refresh and try again"));
    }
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
    //
    // Angular sends X-XSRF-TOKEN header with all request because
    // XSRF-TOKEN cookie is set in after filter
    //
    if ((Request::header('X-XSRF-TOKEN') === NULL) or
        (Session::token() !== Crypt::decrypt(Request::header('X-XSRF-TOKEN'))))
    {
        return AppResponse::jsonResponse(
            array('Invalid session. Please refresh the page and try again.'));
    }
});
