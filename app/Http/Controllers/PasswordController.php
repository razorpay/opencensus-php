<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use Input;
use Password;
use App\User;
use App\Admin;
use App\Generic;
use App\Merchant;
use App\Http\AppResponse;
use App\Mailers\MiscMailer;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;

class PasswordController extends Controller
{
    const EXPIRY_DATE = 1577836800; // 1st Jan 2020
    const SHA256 = 'sha256';

    /**
     * Handle a POST request to remind a user of their password.
     *
     * @return Response
     */
    public function postRemind()
    {
        list($error, $org) = (new Admin\Service)->getOrg(Input::get('hostname'));

        $credentials = Input::only('email');

        // Lowercasing emails for consistency
        if (isset($credentials['email']))
        {
            $credentials['email'] = mb_strtolower($credentials['email']);
        }

        $user = User\Entity::select(['users.id'])
                                ->where('users.email', $credentials['email'])
                                ->first();

        if ($user === null)
        {
            return Response::json([
                            'success' => true,
                            'errors'  => ['We\'ve sent a forgot password email']
                        ]);
        }

        $resetToken = $this->generateToken($user['id']);

        $mailer = new MiscMailer();

        $mailer->sendForgetPasswordEmail($credentials['email'], $org, $resetToken)
               ->queueAndDeliver();

        return Response::json(['success' => true]);
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

        $user = User\Entity::where('users.email', $credentials['email'])->first();

        if ($user === null)
        {
            return Response::json([
                            'success' => true,
                            'errors'  => ['We\'ve sent a forgot password email']
                        ]);
        }

        $resetToken = $this->generateToken($user['id']);

        if (secure_compare($credentials['token'], $resetToken) === false)
        {
            return Response::json([
                            'success' => false,
                            'errors'  => ['Token is invalid or expired.']
                        ]);
        }

        $user->password = Hash::make($credentials['password']);
        $user->save();

        (new User\Service)->updatePasswordOnApi($user);

        return Response::json(['success' => true]);
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

    protected function generateToken($userId)
    {
        return hash_hmac(self::SHA256, 'password.reset' . '_' . $userId . '_' . self::EXPIRY_DATE, env('APP_KEY'));
    }
}
