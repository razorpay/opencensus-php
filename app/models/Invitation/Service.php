<?php

namespace Models\Invitation;

use Auth;
use Models\Base;
use Models\Merchant;
use Models\User;
use Models\Invitation;

class Service extends Base\Service
{
    /**
     * Send an invitation for the given merchant.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendInvitation($input)
    {
        $error = (new Validator)->validateInput('sendInvitation', $input)->messages();

        if (!empty($error))
        {
            return array($error, array());
        }

        $user = Auth::user()->user();

        $merchant = $user->merchants()
                         ->where('email',$user->email)
                         ->where('role','owner')
                         ->first();

        if ($merchant->invitations()->where('email', $input['email'])->exists()) 
        {
            return array(array('An invitation has already been sent to the user.'),array());
        }

        $invitation = $merchant->inviteUserByEmailWithRole($input['email'],$input['role']);

        return array(null, $merchant->toArray());
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function acceptInvitation($inviteId)
    {
        $user = Auth::user()->user();

        $invitation = $user->invitations()->find($inviteId);

        if(!$invitation)
        {
            return array('The invitation is invalid.');
        }

        $user->joinMerchantByIdWithRole($invitation->merchant_id, $invitation->role);

        $invitation->delete();
    }

    /**
     * Destroy the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function removeInvitation($inviteId)
    {
        Auth::user()->user()->invitations()->findOrFail($inviteId)->delete();
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