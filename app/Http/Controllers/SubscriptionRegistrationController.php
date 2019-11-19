<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;

class SubscriptionRegistrationController extends Controller
{
    public function listTokens()
    {
        $input = Request::all();

        $data = $this->service()->listTokens($input);

        return ApiResponse::json($data);
    }

    public function listAuthLinks()
    {
        $input = Request::all();

        $data = $this->service()->listAuthLinks($input);

        return ApiResponse::json($data);
    }

    public function createAuthLink()
    {
        $input = Request::all();

        $data = $this->service()->createAuthLink($input);

        return ApiResponse::json($data);
    }

    public function fetchAuthLink(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->fetchAuthLink($id, $input);

        return ApiResponse::json($invoice);
    }

    public function fetchToken(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->fetchToken($id, $input);

        return ApiResponse::json($invoice);
    }

    public function deleteToken(string $id)
    {
        $invoice = $this->service()->deleteToken($id);

        return ApiResponse::json($invoice);
    }

    public function chargeToken(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->chargeToken($id, $input);

        return ApiResponse::json($invoice);
    }

    public function postProcessAutoCharges()
    {
        $input = Request::all();

        $summary = $this->service()->processAutoCharges($input);

        return ApiResponse::json($summary);
    }

    public function associateToken(string $id)
    {
        $input = Request::all();

        $data = $this->service()->associateToken($id, $input);

        return ApiResponse::json($data);
    }

    public function authenticateTokens()
    {
        $input = Request::all();

        $data = $this->service()->authenticateTokens($input);

        return ApiResponse::json($data);
    }

    public function sendNotification(string $id, string $medium)
    {
        $data = $this->service()->sendNotification($id, $medium);

        return ApiResponse::json($data);
    }

    public function cancelAuthLink(string $id)
    {
        $invoice = $this->service()->cancelAuthLink($id);

        return ApiResponse::json($invoice);
    }

    public function cancelAuthLinksOfBatch(string $batchId)
    {
        $this->service(Entity::INVOICE)->cancelInvoicesOfBatch($batchId);

        return ApiResponse::json([]);
    }

    public function paperMandateAuthenticate()
    {
        $data = $this->service()->paperMandateAuthenticate($this->input);

        return ApiResponse::json($data);
    }

    public function paperMandateValidate()
    {
        $data = $this->service()->paperMandateValidate($this->input);

        return ApiResponse::json($data);
    }

    public function getUploadedPaperMandateForm()
    {
        $data = $this->service()->getUploadedPaperMandateForm($this->input);

        return ApiResponse::json($data);
    }

    public function fetchAuthLinkInternal(string $id)
    {
        $data = $this->service()->fetchAuthLinkInternal($id, $this->input);

        return ApiResponse::json($data);
    }
}
