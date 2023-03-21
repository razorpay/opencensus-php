<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class InvitationController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function fetchByToken(string $token)
    {
        $data = $this->service()->fetchByToken($token);

        return ApiResponse::json($data);
    }

    public function list()
    {
        $data = $this->service()->list();

        return ApiResponse::json($data);
    }

    public function postResend(string $id)
    {
        $input = Request::all();

        $data = $this->service()->resend($id, $input);

        return ApiResponse::json($data);
    }

    public function edit(string $id)
    {
        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function delete(string $id)
    {
        $data = $this->service()->delete($id);

        return ApiResponse::json($data);
    }

    public function postAction(string $id, string $action)
    {
        $input = Request::all();

        $input['action'] = $action;

        $data = $this->service()->action($id, $input);

        return ApiResponse::json($data);
    }

    public function sendBankLmsInvitations()
    {
        $input = Request::all();

        $data = $this->service()->createBankLmsUserInvitation($input);

        return ApiResponse::json($data);
    }

    public function sendAxisInvitations()
    {
        $input = Request::all();

        $data = $this->service()->sendAxisInvitations($input);

        return ApiResponse::json($data);
    }

    public function listDraftInvitations()
    {
        $input = Request::all();

        $data = $this->service()->listDraftInvitations($input);

        return ApiResponse::json($data);
    }

    public function acceptDraftInvitations()
    {
        $input = Request::all();

        $data = $this->service()->acceptDraftInvitations($input);

        return ApiResponse::json((array)(string)$data);
    }

    public function createVendorPortalInvitation()
    {
        $input = Request::all();

        $data = $this->service()->createVendorPortalInvitation($this->ba->getMerchant(), $input);

        return ApiResponse::json($data);
    }

    public function resendVendorPortalInvitation()
    {
        $input = Request::all();

        $data = $this->service()->resendVendorPortalInvitation($this->ba->getMerchant(), $input);

        return ApiResponse::json($data);
    }

    public function resendXAccountingIntegrationInvites()
    {
        $input = Request::all();

        $data = $this->service()->resendXAccountingIntegrationInvites($input);

        return ApiResponse::json($data);
    }

}
