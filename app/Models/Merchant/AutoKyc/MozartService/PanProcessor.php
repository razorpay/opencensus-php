<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Detail\Constants;

class PanProcessor extends BaseProcessor
{
    public function process(): Response
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
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new PanProcessorResponse($response, $responseMetaData, $this->input);
    }
}
