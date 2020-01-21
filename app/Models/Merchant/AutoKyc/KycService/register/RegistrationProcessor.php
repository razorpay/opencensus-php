<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\register;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\KycService\BaseProcessor;

class RegistrationProcessor extends BaseProcessor
{
    public function process(): Response
    {
        $content = [
            'customer_id' => $this->input[Constants::ENTITY_ID],
        ];
        $request = [
            'url'     => $this->config['url'],
            'method'  => 'POST',
            'content' => $content,
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new RegistrationResponse($response, $responseMetaData);
    }
}
