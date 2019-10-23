<?php

namespace RZP\Models\Merchant\Detail\Verifiers;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Constants;

class PanVerifier extends AbstractVerifier
{
    public function verifyDetails(): PanVerifierResponse
    {
        $content = [
            'pan'     => $this->input[Constants::PAN_NUMBER],
            'consent' => 'Y',
        ];

        $request = [
            'url'     => $this->config['url'] . 'capital/total_kyc/v1/pan_authentication',
            'method'  => 'POST',
            'content' => $content,
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
            ]
        ];

        $response = $this->createAndSendRequest($request);

        return new PanVerifierResponse($response);
    }
}
