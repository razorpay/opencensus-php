<?php

namespace App\Invitation;

use Auth;
use Mail;
use App\Base;
use App\Merchant;
use App\User;
use App\Invitation;
use App\Mailers\MiscMailer;

class Service extends Base\Service
{
    const NO_MERCHANTS_OWNED_BY_USER = "You don't own any merchants";
    const SELF_INVITE_NOT_ALLOWED = "You can't invite yourself";
    const ALREADY_INVITED = 'An invitation has already been sent to the user.';
    const INVALID_INVITE = 'The invitation is invalid.';

    public function __construct()
    {
        if ($user = Auth::user())
        {
            $this->loggedInUser = $user;
        }
    }

    /**
     * Send an invitation for the given merchant.
     *
     * @return array ($error, $data)
     */
    public function sendInvitation($input)
    {
        $errors = [];
        $data = null;

        $validation = (new Validator)->validateInput('sendInvitation', $input);

        if ($validation->fails())
        {
            return array($validation->messages(), null);
        }

        // We need to change this to currentLoggedInMerchant later
        $merchant = $this->loggedInUser->getOwnerMerchant();

        if ($merchant === false)
        {
            $errors[] = static::NO_MERCHANTS_OWNED_BY_USER;
        }
        else
        {
            $data = $merchant->toArray();
        }

        // This is a double check because going ahead once we have roles
        // Users can invite others as well, meaning user->email check would
        // become important.
        if ($merchant->email === $input['email'] or $this->loggedInUser->email === $input['email'])
        {
            $errors[] = static::SELF_INVITE_NOT_ALLOWED;
        }
        else if ($merchant->hasInvitiationForEmail($input['email']))
        {
            $errors[] = static::ALREADY_INVITED;
        }

        if (empty($errors))
        {
            $this->createInviteAndSendEmail($merchant, $input['email'], $input['role']);
        }

        return [$errors, $data];
    }

    /**
     * This is the final method that sends out the invite
     * @param  string $email Email Address of the person to send the invite to
     * @param  string $role  role of the user in the tea
     * @return null
     */
    protected function createInviteAndSendEmail(Merchant\Entity $merchant, $email, $role = 'manager')
    {
        // This only creates a new invitation entity
        $invitation = $merchant->inviteUserByEmailWithRole($email, $role);

        $this->sendInvitationEmail($invitation);
    }

    /**
     * Resend the invitation for the given merchant.
     *
     * @return array ($error, $data)
     */
    public function resendInvitationForUser($inviteId, $user)
    {
        $error = array();

        $invitation = $user->currentMerchant->invitations()->find($inviteId);

        if (! $invitation)
        {
            $error[] = static::INVALID_INVITE;

            return array($error, null);
        }

        $this->sendInvitationEmail($invitation);

        return [$error, $invitation->toArray()];
    }

    /**
     * Resend the invitation for the given merchant.
     *
     * @return array ($error, $data)
     */
    public function removeInvitationForUser($inviteId, $user)
    {
        $error = [];

        $invitation = $user->currentMerchant->invitations()->find($inviteId);

        if (! $invitation)
        {
            $error[] = static::INVALID_INVITE;
        }
        else
        {
            $invitation->delete();
        }

        return $error;
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
        $invitation = $user->invitations()->find($inviteId);

        if (! $invitation)
        {
            return [static::INVALID_INVITE];
        }

        $user->joinMerchantByIdWithRole($invitation->merchant_id, $invitation->role);

        $invitation->delete();
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @param  \Models\User\Entity  $user
     * @return array $error
     */
    public function updateInvitationForUser($inviteId, $user, $input)
    {
        $error = [];

        $validation = (new Invitation\Validator)->validateInput('updateInvitation', $input);

        if ($validation->fails())
        {
            $error[] = $validation->messages();
        }

        $invitation = $user->currentMerchant()->invitations()->find($inviteId);

        if (! $invitation)
        {
            $error[] = [static::INVALID_INVITE];

            return $error;
        }

        $invitation->role = $input['role'];

        $invitation->save();
    }

    /**
     * Destroy the given merchant invitation.
     *
     * @param  string  $inviteId
     * @param  \Models\User\Entity  $user
     * @return \Illuminate\Http\Response
     */
    public function rejectInvitationForUser($inviteId, $user)
    {
        $invitation = $user->invitations()->find($inviteId);

        if (! $invitation)
        {
            return [static::INVALID_INVITE];
        }

        $invitation->delete();
    }

    /**
     * Get the invitation entity from the token
     *
     * @param string $invitationToken
     * @return string $email
     */
    public function getInvitationFromToken($invitationToken)
    {
        $error = array();

        $invitation = (new Invitation\Entity)->where('token', $invitationToken)->first();

        if ($invitation)
        {
            return array($error, $invitation);
        }

        $error = [static::INVALID_INVITE];

        return array($error, null);
    }

    /**
     * Get the pending invitations for the given user.
     *
     * @param \Models\User\Entity $user
     * @return \App\Invitation\Entity[]
     */
    public function getPendingInvitationsForUser($user)
    {
        $invitations = $user->invitations()->with('merchant')->get();

        foreach ($invitations as $invite)
        {
            $invite->setVisible(['id', 'merchant', 'role']);

            $invite->merchant->setVisible(['id','name','email']);
        }

        return array(null, $invitations);
    }

    protected function sendInvitationEmail($invitation)
    {
        $mailer = new MiscMailer();

        $mailer
            ->sendMemberInvitationEmail($invitation, $this->loggedInUser->toArray())
            ->queueAndDeliver();
    }
}
