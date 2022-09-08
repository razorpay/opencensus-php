<?php

namespace RZP\Models\SalesforceConverge;

class SalesforceConvergeClientMock
{
    public function getAuthorization($request)
    {
        $token = [
            'access_token'      => "00D9I000000HGmj!AQEAQG21MKisvknGUdSYfsMW3x6epWA7lPPMK0ybfEArk23VRAFMrmeZymU2pHXxOhEIbnvAr_svtQ1f0oXAO_7.1HpS7W7O",
            'instance_url'      => "https://razorpay--testingrzp.sandbox.my.salesforce.com",
            'id'                => "https://test.salesforce.com/id/00D9I000000HGmjUAG/0056F000007jBNKQA2",
            'token_type'        => "Bearer",
            'issued_at'         => "1662637009576",
            'signature'         => "2WxglkwDyp7F4dYCVzARU8a55nnKtU5BJLtcyQiTnog="
        ];

        return new AuthToken($token);
    }

    public function pushUpdates($request, $token)
    {
        $sfFormattedRequest = $request->getFormattedRequest();

        return new EventResponse(['success'=>true]);
    }
}