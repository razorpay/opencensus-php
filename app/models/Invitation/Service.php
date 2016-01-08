<?php

namespace Models\Invitation;

use Auth;
use Mail;
use Models\Base;
use Models\Merchant;
use Models\User;
use Models\Invitation;

class Service extends Base\Service
{
    /**
     * Send an invitation for the given merchant.
     *
     * @return array ($error, $data)
     */
    public function sendInvitation($input)
    {
        $validation = (new Validator)->validateInput('sendInvitation', $input);

        if ($validation->fails())
        {
            return array($validation->messages(), null);
        }

        $user = Auth::user()->user();

        $merchant = $user->merchants()
                         ->where('email', $user->email)
                         ->where('role', 'owner')
                         ->first();

        if ($merchant->invitations()->where('email', $input['email'])->exists())
        {
            return array(array('An invitation has already been sent to the user.'),array());
        }

        $invitation = $merchant->inviteUserByEmailWithRole($input['email'],$input['role']);

        return array(null, $merchant->toArray());
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

        if(!$invitation)
        {
            $error = 'The invitation is invalid.';
            return array($error, null);
        }

        $view = $invitation->user_id
                        ? 'emails.invitations.existing'
                        : 'emails.invitations.new';

        Mail::send($view, compact('invitation'), function ($m) use ($invitation)
        {
            $m->to($invitation->email)->subject('New Invitation!');
        });

        return array($error, $invitation->toArray());
    }

    /**
     * Resend the invitation for the given merchant.
     *
     * @return array ($error, $data)
     */
    public function removeInvitationForUser($inviteId, $user)
    {
        $error = array();

        $invitation = $user->currentMerchant->invitations()->find($inviteId);

        if(!$invitation)
        {
            $error = 'The invitation is invalid.';
            return array($error, null);
        }

        $invitation->delete();

        return array($error, $invitation->toArray());
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

        if(!$invitation)
        {
            return array('The invitation is invalid.');
        }

        $user->joinMerchantByIdWithRole($invitation->merchant_id, $invitation->role);

        $invitation->delete();

        return array();
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
        $error = array();

        $validation = (new Invitation\Validator)->validateInput('updateInvitation', $input);

        if($validation->fails())
        {
            return $validation->messages();
        }

        $invitation = $user->currentMerchant()->invitations()->find($inviteId);

        if(!$invitation)
        {
            return array('The invitation is invalid.');
        }

        $invitation->role = $input['role'];
        $invitation->save();

        return $error;
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

        if(is_null($invitation))
        {
            return array('The invitation is invalid.');
        }

        $invitation->delete();

        return array();
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

        if($invitation)
        {
            return array($error, $invitation);
        }

        $error = array('The invitation is invalid.');
        return array($error, null);
    }

    /**
     * Get the pending invitations for the given user.
     *
     * @param \Models\User\Entity $user
     * @return \Models\Invitation\Entity[]
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
}
