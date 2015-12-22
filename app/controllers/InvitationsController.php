<?php

use Models\Invitation;
use Http\AppResponse;

class InvitationController extends BaseController
{
    /**
     * Send an invitation for the given merchant.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendMerchantInvitation()
    {
        $input = Input::all();

        list($error, $data) = (new Invitation\Service)->sendInvitation($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function acceptMerchantInvitation($inviteId)
    {
        list($error, $data) = (new Invitation\Service)->acceptInvitation($inviteId);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Destroy the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function destroyMerchantInvitationForUser($inviteId)
    {
        list($error, $data) = (new Invitation\Service)->removeInvitation($inviteId);

        return AppResponse::jsonResponse($error);
    }
}