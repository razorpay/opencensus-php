<?php

namespace App\Http\Controllers;

use Input;
use Password;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Lang;
use DB;
use Hash;

class PasswordController extends Controller
{
    /**
     * Handle a POST request to remind a user of their password.
     *
     * @return Response
     */
    public function postRemind()
    {
        $response = Password::sendResetLink(Input::only('email'), function($message){
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

        $response = Password::reset($credentials, function($user, $password)
        {
            DB::transaction(function() use ($user, $password)
            {
                $user->password = Hash::make($password);
                $user->save();

                if($user->hasMerchants())
                {
                    $merchant = $user->merchants()->where('email', $user->email)->first();
                    if ($merchant)
                    {
                        $merchant->password = $user->password;
                        $merchant->save();
                    }
                }
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

}
