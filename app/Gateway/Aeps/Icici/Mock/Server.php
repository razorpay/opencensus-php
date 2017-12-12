<?php

namespace RZP\Gateway\Aeps\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Aeps\Icici;

class Server extends Base\Mock\Server
{
    public function refund($input)
    {
        $input = json_decode($input, true);

        $encryptor = new Icici\Encryptor(2, $this->getGatewayInstance()->getIv(), true);

        $sessionKey = $encryptor->decryptSessionKey($input[Icici\RequestConstants::REFUND_REQUEST_ENCRYPTEDKEY]);

        $decryptedData = $encryptor->decryptUsingSessionKey(
            $input[Icici\RequestConstants::REFUND_REQUEST_ENCRYPTEDDATA],
            $sessionKey
        );

        $data = json_decode($decryptedData);

        $responseData = $this->getRefundResponse($data);

        return $this->prepareResponse($responseData);
    }

    protected function getRefundResponse($data)
    {
        $data = [
            Icici\ResponseConstants::REFUND_SUCCESS       => 'true',
            Icici\ResponseConstants::REFUND_RESPONSE      => '11',
            Icici\ResponseConstants::REFUND_MESSAGE       => 'Success',
            Icici\ResponseConstants::REFUND_BANKRRN       => '732516577130',
            Icici\ResponseConstants::REFUND_UPITRANLOGID  => '577130',
            Icici\ResponseConstants::REFUND_USERPROFILE   => '723',
            Icici\ResponseConstants::REFUND_SEQNO         => Icici\RequestConstants::REFUND_DATA_SEQ_NO,
            Icici\ResponseConstants::REFUND_MOBILEAPPDATA => 'MobileAppData',
        ];

        $this->content($data, 'refund');

        return $data;
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
