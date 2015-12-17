<?php

namespace Models\User;

use Auth;
use Models\Base;
use Models\Merchant;

class Service extends Base\Service
{
    public function login(array $input)
    {
        $error = (new Validator)->validateInput('login', $input)->messages();

        if (empty($error) === false)
        {
            return [['Email or password is invalid.'], null];
        }

        $credentials = array(
            'email'     => $input['email'],
            'password'  => $input['password']
        );

        if(Auth::user()->validate($credentials) === false)
        {
            // Checks credentials but doesn't login the user, throws error if invalid
            $error = ['Email or password is invalid.'];
        }
        else if (Auth::user()->attempt($credentials + array('confirm_token' => null)) === false)
        {
            // Tries to login user if confirmed, throws error if user is not confirmed
            $error = ['not activated'];
        }

        return [$error, null];
    }

    public function fetch($user_id)
    {
        $user = Entity::findOrFail($user_id)->toArray();

        return $user;
    }

    public function changePassword(array $input)
    {
        $user = Auth::user()->user();

        if ($user->currentMerchant->isTestAccount()) {
            return [["Password change forbidden on this account"], null];
        }

        $error = $user->changePassword($input);
        $user->save();
        
        return [$error, null];
    }
}
