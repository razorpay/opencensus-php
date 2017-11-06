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
        $acceptInvitationForUser = [
            'route_name' => 'invitation_action',
            'url_params' => [
                '{id}'     => $inviteId,
                '{action}' => 'accept'
            ],
            'body' => [
                'user_id' => $user->id
            ]
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $acceptInvitationForUser);

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
