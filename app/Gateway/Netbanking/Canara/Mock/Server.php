<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use Respect\Validation\Rules\SubdivisionCode\ReSubdivisionCode;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Canara\RequestFields;
use RZP\Gateway\Netbanking\Canara\ResponseFields;
use RZP\Gateway\Netbanking\Canara\Constants;


class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const BANK_REFERENCE_NUMBER = 'AB1234';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = $this->getCallbackResponseData($input);

        $this->content($content, 'authorize');

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_canara_get');

        $callbackUrl .= '?' . http_build_query($content);

        $request = [
            'url'     => $callbackUrl,
            'content' => [],
            'method'  => 'get',
        ];

        return $this->makePostResponse($request);

    }

    public function callback($input)
    {
        return $this->verify($input);
    }

    public function verify($input)
    {
        $data = $this->getVerifyResponseData($input);

        $response = $this->createXmlResponse($data);

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::ACTION                 => Constants::MODE_OF_TRANSACTION_PURCHASE,
            ResponseFields::MERCHANT_CODE          => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::PAYMENT_ID             => $input[RequestFields::PAYMENT_ID],
            ResponseFields::AMOUNT                 => $input[RequestFields::AMOUNT],
            ResponseFields::CLIENT_CODE            => $input[RequestFields::CLIENT_CODE],
            ResponseFields::CURRENCY               => $input[RequestFields::CURRENCY],
            ResponseFields::SUCCESS_STATIC_FLAG    => $input[RequestFields::SUCCESS_STATIC_FLAG],
            ResponseFields::FAILURE_STATIC_FLAG    => $input[RequestFields::FAILURE_STATIC_FLAG],
            ResponseFields::DATE                   => $input[RequestFields::DATE],
            ResponseFields::SERVICE_CHARGE         => $input[RequestFields::SERVICE_CHARGE],
            ResponseFields::BANK_REFERENCE_NUMBER  => self::BANK_REFERENCE_NUMBER,
            ResponseFields::CLIENT_ACCOUNT         => $input[RequestFields::CLIENT_ACCOUNT],
            ResponseFields::MESSAGE                => Constants::MESSAGE,
        ];

        $this->content($data, Base\Action::CALLBACK);

        return $data;
    }

    protected function getVerifyResponseData(array $input)
    {
        $data = [
            ResponseFields::VER_CLIENT_ACCOUNT                => '',
            ResponseFields::VER_PAYMENT_ID                    => $input[ResponseFields::PAYMENT_ID],
            ResponseFields::PUR_DATE                          => $input[ResponseFields::PUR_DATE],
            ResponseFields::VER_BANK_REFERENCE_NUMBER         => self::BANK_REFERENCE_NUMBER,
            ResponseFields::VER_AMOUNT                        => $input[ResponseFields::AMOUNT],              // have to verify
            ResponseFields::RETURN_CODE                       => Constants::SUCCESS,
            ResponseFields::VERIFY_STATUS                     => Constants::SUCCESS_VERIFY_STATUS,
         ];

        $this->content($data, Base\Action::VERIFY);

        return $data;
    }

    protected function createXmlResponse(array $data)
    {
        $this->content($data,'verify');

        $xml = new \SimpleXMLElement('<VerifyOutput/>');

        foreach ($data as $key => $value) {
            $xml->addChild($key,$data[$key]);
        }

        $response = $xml->asXML();

        return $response;
    }

}