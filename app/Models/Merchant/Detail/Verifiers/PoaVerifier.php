<?php

namespace RZP\Models\Merchant\Detail\Verifiers;

use Requests;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Constants;

class PoaVerifier extends AbstractVerifier
{
    protected $mockStatus = Constants::AADHAR_FRONT;

    public function verifyDetails()
    {
        $signedUrl = $this->input[Constants::SIGNED_URL];

        $content = [
            'url'         => $signedUrl,
            'maskAadhaar' => "false",
            'conf'        => "true"
        ];

        $request = [
            'url'     => $this->config['url'] . 'capital/total_kyc/v1/ocr',
            'method'  => Requests::POST,
            'content' => $content,
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
            ]
        ];

        $response = $this->createAndSendRequest($request);

        return new PoaVerifierResponse($response);
    }
}
