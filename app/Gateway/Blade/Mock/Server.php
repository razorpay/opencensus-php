<?php

namespace RZP\Gateway\Blade\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Blade\Mock\Xml\Response;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $parsedInput = simplexml_load_string($input);

        $parsedInput = json_decode(json_encode($parsedInput), true);

        $response = $this->getResponse($parsedInput);

        return $this->makeResponse($response);
    }

    protected function getResponse(array $input)
    {
        $responseClass = new Response();

        $paymentId = $input['Message']['@attributes']['id'];

        $cardNo = $input['Message']['VEReq']['pan'];

        switch($cardNo)
        {
            case CardNumber::VALID_ENROLL_NUMBER:
                return $responseClass->enrolledValidResponse($paymentId);
            case CardNumber::VALID_NOT_ENROLL_NUMBER:
                return $responseClass->notEnrolledValidResponse($paymentId);
            case CardNumber::INVALID_MEESGAE:
                return $responseClass->differentMessageResponse($paymentId);
            case CardNumber::BLANK_MEESGAE:
                return $responseClass->blankMessageResponse($paymentId);
        }
    }
}
