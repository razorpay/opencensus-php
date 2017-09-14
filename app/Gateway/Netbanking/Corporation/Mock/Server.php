<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Paytm;
use RZP\Gateway\Netbanking\Corporation\RequestFields;
use RZP\Gateway\Netbanking\Corporation\ResponseFields;
use RZP\Gateway\Netbanking\Corporation\ResponseCodeMap;
use RZP\Gateway\Netbanking\Corporation\Constants;

use Carbon\Carbon;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const BANK_REF_NUMBER = 'AB1234';

    public function authorize($input)
    {
        parent::authorize($input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response, 'authorize');

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_corporation');

        $request = [
            'url'       => $callbackUrl,
            'content'   => $response,
            'method'    => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        $data = $this->getVerifyResponseData($input);

        return $this->makeResponse(http_build_query($data));
    }

    protected function getCallbackResponseData(array $input)
    {
        return [
            ResponseFields::MODE_OF_TRANSACTION => 'P',
            ResponseFields::MERCHANT_CODE       => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::PAYMENT_ID          => $input[RequestFields::PAYMENT_ID],
            ResponseFields::CUSTOMER_ID         => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::AMOUNT              => $input[RequestFields::AMOUNT],
            ResponseFields::FUND_TRANSFER       => Constants::FUND_TRANSFER,
            ResponseFields::BANK_REF_NUMBER     => self::BANK_REF_NUMBER,
            ResponseFields::STATUS              => ResponseCodeMap::SUCCESS_CODE,
        ];
    }

    protected function getVerifyResponseData(array $input)
    {
        $merchantId = $input[RequestFields::VERIFY_MERCHANT_CODE];

        $data = $this->getGatewayInstance()->getEncryptor()->decryptData(
            $input[RequestFields::VERIFY_DATA]
        );

        $data = $this->buildVerifyResponseContent($data);

        return [
            ResponseFields::VERIFY_DATA => $data
        ];
    }

    protected function buildVerifyResponseContent($input)
    {
        $datetime = Carbon::now(Timezone::IST)->format('dmY\THis');

        $data = [
            ResponseFields::VERIFY_MERCHANT_CODE     => $input[RequestFields::VERIFY_MERCHANT_CODE],
            ResponseFields::VERIFY_PAYMENT_ID        => $input[RequestFields::VERIFY_PAYMENT_ID],
            ResponseFields::VERIFY_AMOUNT            => $input[RequestFields::VERIFY_AMOUNT],
            ResponseFields::VERIFY_BANK_REF_NUMBER   => self::BANK_REF_NUMBER,
            ResponseFields::VERIFY_RESULT            => ResponseCodeMap::RESULT_SUCCESS,
            ResponseFields::VERIFY_RESULTMESSAGE     => ResponseCodeMap::RESULT_SUCCESS,
            ResponseFields::VERIFY_PAYMENT_DATE_TIME => $datetime,
        ];

        $this->content($data, 'verify');

        return $this->getGatewayInstance()->getEncryptor()->encryptData($data);
    }
}
