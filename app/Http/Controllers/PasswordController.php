<?php

namespace App\Http\Controllers;

use App\Http\AppResponse;

use Input;
use Password;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Lang;
use DB;
use Hash;
use App\Merchant;
use App\Generic;
use App\Admin;

class PasswordController extends Controller
{
    /**
     * Handle a POST request to remind a user of their password.
     *
     * @return Response
     */
    public function postRemind()
    {
        list($error, $org) = (new Admin\Service)->getOrg(Input::get('hostname'));

        view()->composer('emails.auth.reminder', function($view) use($org) {
            $view->with([
                'org'   =>  $org
            ]);
        });

        $credentials = Input::only('email');
        // Lowercasing emails for consistency
        if (isset($credentials['email']))
        {
            $credentials['email'] = mb_strtolower($credentials['email']);
        }

        $response = Password::sendResetLink($credentials, function($message){
            $message->subject('Razorpay - Password Reset Request');
        });

        switch ($response)
        {
            case Password::INVALID_USER:
                return Response::json(array('success' => false, 'errors' => array(Lang::get($response))));

            case Password::RESET_LINK_SENT:
                return Response::json(array('success' => true));
        }
    }

    /**
     * Handle a POST request to reset a user's password.
     *
     * @return Response
     */
    public function postReset()
    {
        $credentials = Input::only(
            'email', 'password', 'password_confirmation', 'token'
        );
        // Lowercasing emails for consistency
        if (isset($credentials['email']))
        {
            $credentials['email'] = mb_strtolower($credentials['email']);
        }

        $response = Password::reset($credentials, function($user, $password)
        {
            DB::transaction(function() use ($user, $password)
            {
                $user->password = Hash::make($password);
                $user->save();
            });
        });

        switch ($response)
        {
            case Password::INVALID_PASSWORD:
            case Password::INVALID_TOKEN:
            case Password::INVALID_USER:
                return Response::json(array('success' => false, 'errors' => array(Lang::get($response))));

            case Password::PASSWORD_RESET:
                return Response::json(array('success' => true));
        }
    }

    public function forgotAdminPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->forgotPassword($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function resetAdminPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->resetPassword($input);

        return AppResponse::jsonResponse($error, $data);
    }

}
