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
use Carbon\Carbon;
use App\Http\AppResponse;
use App\Mailers\MiscMailer;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;

class PasswordController extends Controller
{
    const EXPIRY_WINDOW = 86400; // 24 hours
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
            return Response::json(['success' => true]);
        }

        $expiryTime = Carbon::now()->timestamp + self::EXPIRY_WINDOW;

        $resetToken = $this->generateToken($user['id'], $expiryTime);

        $mailer = new MiscMailer();

        $mailer->sendForgetPasswordEmail($credentials['email'], $org, $resetToken, $expiryTime)
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
            'email', 'password', 'password_confirmation', 'token', 'expiryTime'
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
                            'errors'  => ['Token is invalid or expired.']
                        ]);
        }

        $resetToken = $this->generateToken($user['id'], $credentials['expiryTime']);

        if (hash_equals($credentials['token'], $resetToken) === false)
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

    protected function generateToken($userId, $time)
    {
        return hash_hmac(self::SHA256, 'password.reset' . '_' . $userId . '_' . $time, config('app.key'));
    }
}
