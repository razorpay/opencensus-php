<?php
namespace App\Http\Controllers;

use App\Invitation;
use App\Http\AppResponse;
use Auth;
use Input;

class InvitationsController extends Controller
{
    public function __construct()
    {
        $this->service = new Invitation\Service;
    }

    /**
     * Send an invitation for the given merchant.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSendMerchantInvitation()
    {
        $input = Input::all();

        list($error, $data) = (new Invitation\Service)->sendInvitation($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Resend the invitation for the given merchant.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function getResendMerchantInvitation($inviteId)
    {
        $user = Auth::user();

        list($error, $data) = $this->service->resendInvitationForUser($inviteId, $user);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function postAcceptMerchantInvitation($inviteId)
    {
        $user = Auth::user();

        $error = $this->service->acceptInvitationForUser($inviteId, $user);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function deleteRejectMerchantInvitation($inviteId)
    {
        $user = Auth::user();

        $error = $this->service->rejectInvitationForUser($inviteId, $user);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Accept the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function removeMerchantInvitation($inviteId)
    {
        $user = Auth::user();

        $error = $this->service->removeInvitationForUser($inviteId, $user);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Update the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function updateMerchantInvitation($inviteId)
    {
        $input = Input::all();

        $user = Auth::user();

        $error = $this->service->updateInvitationForUser($inviteId, $user, $input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Destroy the given merchant invitation.
     *
     * @param  string  $inviteId
     * @return \Illuminate\Http\Response
     */
    public function deleteMerchantInvitationForUser($inviteId)
    {
        $user = Auth::user();

        $error = $this->service->removeInvitationForUser($inviteId, $user);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Get all of the pending invitations for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPendingInvitationsForUser()
    {
        $user = Auth::user();

        list($error, $data) = $this->service->getPendingInvitationsForUser($user);

        return AppResponse::jsonResponse($error, $data);
    }
}
