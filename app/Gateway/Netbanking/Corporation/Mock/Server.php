<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Paytm;
use RZP\Gateway\Netbanking\Corporation\RequestFields;
use RZP\Gateway\Netbanking\Corporation\ResponseFields;
use RZP\Gateway\Netbanking\Corporation\ResponseCodeMap;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $response = $this->getCallbackResponseData($input);

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_corporation');

        $request = array(
            'url' => $callbackUrl,
            'content' => $response,
            'method' => 'post',
        );

        return $this->makePostResponse($request);
    }

    protected function getCallbackResponseData($input)
    {
        return [
            ResponseFields::MODE_OF_TRANSACTION => 'P',
            ResponseFields::MERCHANT_CODE       => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::PAYMENT_ID          => $input[RequestFields::PAYMENT_ID],
            ResponseFields::CUSTOMER_ID         => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::AMOUNT              => $input[RequestFields::AMOUNT],
            ResponseFields::FUND_TRANSFER       => ResponseCodeMap::FUND_TRANSFER,
            ResponseFields::BANK_REF_NUMBER     => 'AB1234',
            ResponseFields::STATUS              => ResponseCodeMap::SUCCESS_CODE,
        ];
    }
}
