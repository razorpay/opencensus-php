<?php

namespace App\Invitation;

use Auth;
use Mail;
use Session;
use App\Base;
use App\User;
use App\Merchant;
use App\Invitation;
use App\Mailers\MiscMailer;
use App\Providers\GenericUser;
use App\Generic;

class Service extends Base\Service
{
    public function __construct()
    {
        $this->loggedInUser = Auth::user();
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @param  $user
     * @return \Illuminate\Http\Response
     */
    public function acceptInvitationForUser($inviteId, $user)
    {
        $body = [
            'user_id' => $user->id
        ];

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($body)->send("invitations/$inviteId/accept", 'POST');

        if (empty($error) === true)
        {
            list($error, $genericUser) = (new User\Service)->getUserFromApi($user->id);

            if (empty($error) === true)
            {
                Session::put('dashboard_user_payload', $genericUser);
            }
        }

        return [$error, $data];
    }
}
