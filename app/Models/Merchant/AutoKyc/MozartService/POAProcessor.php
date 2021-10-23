<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Detail\Constants;

class POAProcessor extends BaseProcessor
{
    /**
     * Timeout in Seconds
     */
    protected $timeout = 15;

    public function process(): Response
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
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new POAProcessorResponse($response, $responseMetaData, $this->input);
    }
}
