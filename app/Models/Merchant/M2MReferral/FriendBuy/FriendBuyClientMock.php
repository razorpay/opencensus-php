<?php

namespace RZP\Models\Merchant\M2MReferral\FriendBuy;


use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class FriendBuyClientMock
{
    private $mockStatus;
    private   $config;

    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;

        $this->config = config(Constants::APPLICATIONS_FRIEND_BUY);
    }

    //authentication
    public function getAuthorization(): AuthToken
    {
        switch ($this->mockStatus)
        {
            case Constants::SUCCESS:

                return $this->getAuthenticationSuccessResponse();

            default:

                return $this->getAuthenticationFailureResponse();
        }
    }

    private function getAuthenticationSuccessResponse(): AuthToken
    {

        $timestamp = Carbon::now()->addMinutes(20)->toW3cString();

        $response = [
            "tokenType" => "Bearer",
            "token"     => "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE2Mjg5NDI2MjIsInVzZXJJZCI6ImVtYWlsOmtha2FybGEudmFzYW50aGlAcmF6b3JwYXkuY29tIiwibWVyY2hhbnRJZCI6IjA1YzNhNTM3LTI1N2MtNDhhZS1hMGY2LTMxOWJmZjNhYzU1ZiIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTYyODk0MTQyMn0.JsNzE9MDp-55JHkQOeY5rejetXpRa0KOrHkP-DDL9aSkpYNdViK4CrjtJAX-jZOUsstSIr0bm7iQyLRKFlu_69XSB-6_vglvF42_G9rqti8bdkBMjMeyJ-OyDV2usUspa75FpwDeV4xbyBEyt1mPxeN-CRN4kHReY1WFpLaVRVMNkYZGcJQAbGh09uH36WrGn1dU5p6lasgvnTM1kH4Eop969C94b59txtW5WEYqL198tdQdmLHKJgUpwkc1HbWvnmGKM0tCn63nJNP9q-NA7Xgxfi9d_fA1nRcihcYIR-uwDyJdt9aQ51eh-W8iqxzSLHULTVymF6JHINXVhI9bPw",
            "expires"   => $timestamp
        ];

        return new AuthToken($response);
    }

    private function getAuthenticationFailureResponse(): AuthToken
    {
        $response = [

            "error"     => "Unauthorized",
            "message"   => "Unauthorized",
            "code"      => 0,
            "reference" => ""

        ];

        return new AuthToken($response);
    }

    //mtu
    public function postEvent(): EventResponse
    {
        switch ($this->mockStatus)
        {
            case Constants::SUCCESS:

                return $this->getEventSuccessResponse();

            default:

                return $this->getEventFailureResponse();
        }

    }

    private function getEventSuccessResponse(): EventResponse
    {
        $response = [
            "eventId"   => "d3d81e33-7ae9-42e5-9035-3225e32a55d5",
            "createdOn" => "2021-08-14T11:48:01.603Z"
        ];

        return new EventResponse($response);
    }

    private function getEventFailureResponse(): EventResponse
    {
        $response = [
            "error"     => "Unauthorized",
            "message"   => "Unauthorized",
            "code"      => 0,
            "reference" => ""
        ];

        return new EventResponse($response);
    }

    public function generateReferralLink(): ReferralLinkResponse
    {
        switch ($this->mockStatus)
        {
            case Constants::SUCCESS:

                return $this->getReferralLinkSuccessResponse();

            default:

                return $this->getReferralLinkFailureResponse();
        }

    }

    private function getReferralLinkSuccessResponse(): ReferralLinkResponse
    {
        $response = [
            "link"         => "https://fbuy.io/05c3a537-257c-48ae-a0f6-319bff3ac55f/mg5xnqzn?share=a321e1ae-13da-4ae7-b001-a900fc68a6e9",
            "referralCode" => "mg5xnqzn",
            "createdOn"    => "2021-10-18T18:59:13.865Z"
        ];

        return new ReferralLinkResponse($response);
    }

    private function getReferralLinkFailureResponse(): ReferralLinkResponse
    {
        $response = [
            "error"     => "Unauthorized",
            "message"   => "Unauthorized",
            "code"      => 0,
            "reference" => ""
        ];

        return new ReferralLinkResponse($response);
    }
    public function validateSignature(FormRequest $request)
    {
        if ($request->hasHeader(Constants::X_FRIENDBUY_HMAC_SHA256) === false)
        {
            return false;
        }

        $secret = $this->config[Constants::WEBHOOK][Constants::HASH_KEY];

        $calculatedHash = utf8_encode(base64_encode(hash_hmac(Constants::SHA256, $request->getContent(), $secret, true)));

        $actualHash = $request->header(Constants::X_FRIENDBUY_HMAC_SHA256);

        if ($actualHash === $calculatedHash)
        {
            return true;
        }
        return false;
    }
}
