<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
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

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_corporation');

        $url = $callbackUrl . '?' . $response;

        $request = [
            'url'     => $url,
            'content' => [$response => ''],
            'method'  => 'get',
        ];

        return $this->makePostResponse($request);
    }

    // In the callback method, we do a verification call.
    // Because of this, the sendGatewayRequest() internally redirects the call to
    // the callback() method of mock server(since $this->action is callback during
    // this verify call). So, once it reaches here, we redirect the call to the
    // verify() method passing the input.
    public function callback($input)
    {
        return $this->verify($input);
    }

    public function verify($input)
    {
        $data = $this->getVerifyResponseData($input);

        return $this->makeResponse($data);
    }

    protected function getCallbackResponseData(array $input)
    {
        $qs = $input[RequestFields::QUERY_STRING];

        // The encrypted data is not url encoded when sending to the bank
        // But when you get the request in mock server, it gets url decoded and hence "+"s
        // are converted to " ". So, we revert this manually before decrypting the data.
        $encrypted = str_replace(' ', '+', $qs);

        $input = $this->getGatewayInstance()->getEncryptor()->decryptAndFormatData($encrypted, '=', '&');

        $data = [
            ResponseFields::MODE_OF_TRANSACTION => 'P',
            ResponseFields::MERCHANT_CODE       => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::PAYMENT_ID          => $input[RequestFields::PAYMENT_ID],
            ResponseFields::CUSTOMER_ID         => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::AMOUNT              => $input[RequestFields::AMOUNT],
            ResponseFields::FUND_TRANSFER       => Constants::FUND_TRANSFER,
            ResponseFields::BANK_REF_NUMBER     => self::BANK_REF_NUMBER,
            ResponseFields::STATUS              => ResponseCodeMap::SUCCESS_CODE,
        ];

        $this->content($data, Base\Action::CALLBACK);

        $encrypted = $this->getGatewayInstance()->getEncryptor()->encryptData($data, '=', '&');

        return $encrypted;
    }

    protected function getVerifyResponseData(array $input)
    {
        $data = $this->getGatewayInstance()->getEncryptor()->decryptAndFormatData(
            $input[RequestFields::VERIFY_DATA]
        );

        return $this->buildVerifyResponseContent($data);
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

        $this->content($data, Base\Action::VERIFY);

        return $this->getGatewayInstance()->getEncryptor()->encryptData($data);
    }
}
