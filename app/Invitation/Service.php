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

class Service extends Base\Service
{
    const NO_MERCHANTS_OWNED_BY_USER = "You don't own any merchants";
    const SELF_INVITE_NOT_ALLOWED = "You can't invite yourself";
    const ALREADY_INVITED = 'An invitation has already been sent to the user.';
    const INVALID_INVITE = 'The invitation is invalid.';
    const ALREADY_A_MEMBER = 'A user with this email is already a team member.';

    public function __construct()
    {
        $this->loggedInUser = Auth::user();
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @param  \Models\User\Entity  $user
     * @return \Illuminate\Http\Response
     */
    public function acceptInvitationForUser($inviteId, $user)
    {
        list($error, $response) = $this->acceptInvitationOnApi($inviteId, $user->id);

        if (empty($error) === true)
        {
            $user = User\Entity::find($user->id);

            $user->joinMerchantByIdWithRole($response['merchant_id'], $response['role']);

            list($error, $genericUser) = (new User\Service)->getUserFromApi($user->id);

            if (empty($error) === true)
            {
                Session::put('dashboard_user_payload', $genericUser);
            }
        }
    }

    public function acceptInvitationOnApi(string $id, $userId)
    {
        $error = $response = [];

        $this->setApiCredentials();

        try
        {
            $params = ['user_id' => $userId];

            $response = $this->api
                             ->invitation
                             ->accept($id, $params)
                             ->toArray();
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $response];
    }
}
