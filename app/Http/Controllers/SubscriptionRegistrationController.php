<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Constants\HyperTrace;

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

    public function migrateNach()
    {
        $input = Request::all();

        $data = $this->service()->migrateNach($input);

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

    public function fetchPaperMandateIssues()
    {
        $input = Request::all();

        $paperMandateUpload = $this->service()->fetchPaperMandateUpload($input);

        $response = ApiResponse::json($paperMandateUpload);

        return $response;
    }

    public function approvePaperMandateIssues()
    {
        $input = Request::all();

        $paperMandateUpload = $this->service()->approvePaperMandateIssues($input);

        $response = ApiResponse::json($paperMandateUpload);

        return $response;
    }

    public function deleteToken(string $id)
    {
        $invoice = $this->service()->deleteToken($id);

        return ApiResponse::json($invoice);
    }

    public function chargeToken(string $id)
    {
        $input = Request::all();

        $invoice = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_SERVICE], function () use ($id, $input){
            return $this->service()->chargeToken($id, $input);
        });

        return ApiResponse::json($invoice);
    }

    public function chargeTokenBulk()
    {
        $input = Request::all();

        $response = $this->service()->chargeTokenBulk($input);

        return ApiResponse::json($response);
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

    public function notifyAuthLinksOfBatch(string $batchId)
    {
        $input = Request::all();

        $this->service(Entity::INVOICE)->notifyInvoicesOfBatch($batchId, $input);

        return ApiResponse::json([]);
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

    public function paperMandateAuthenticateProxy()
    {
        $data = $this->service()->paperMandateAuthenticateProxy($this->input);

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

    public function retryPaperMandateToken(string $tokenId)
    {
        $data = $this->service()->retryPaperMandateToken($tokenId);

        return ApiResponse::json($data);
    }

    public function nachRegisterTestPaymentAuthorizeOrFail(string $id)
    {
        $data = $this->service()->nachRegisterTestPaymentAuthorizeOrFail($id, $this->input);

        return ApiResponse::json($data);
    }

    public function downloadNach(string $mode, string $authLinkId)
    {
        $mode === 'test' ? $this->ba->setModeAndDbConnection(Mode::TEST) : $this->ba->setModeAndDbConnection(Mode::LIVE);

        return $this->service()->downloadNach($authLinkId);
    }
}
