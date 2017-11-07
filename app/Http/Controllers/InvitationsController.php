<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use App\Invitation;
use App\Http\AppResponse;

class InvitationsController extends Controller
{
    public function __construct()
    {
        $this->service = new Invitation\Service;
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

        list($error, $data) = $this->service->acceptInvitationForUser($inviteId, $user);

        return AppResponse::jsonResponse($error);
    }
}
