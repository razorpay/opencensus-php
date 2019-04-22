<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Bob\RequestFields;
use RZP\Gateway\Netbanking\Bob\ResponseFields;
use RZP\Gateway\Netbanking\Bob\Constants;
use RZP\Gateway\Netbanking\Bob\Status;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const CUSTOMER_ACCOUNT_NUMBER = '123000000345678';
    const BANK_REF_NUMBER         = '12345678';

    public function authorize($input)
    {
        parent::authorize($input);

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $content = $encryptor->decryptData($input[RequestFields::ENCRYPTED_DATA]);

        $this->validateAuthorizeInput($content);

        $authResponseContent = $this->getAuthResponseContent($content);

        $redirectUrl = $content[RequestFields::CALLBACK_URL];

        $request = [
            'url'     => $redirectUrl,
            'content' => $authResponseContent,
            'method'  => 'post',
        ];

        $modifyContent = [
            'request' => $request,
            'content' => $content
        ];

        $this->content($modifyContent, 'cancelPayment');

        $request = $modifyContent['request'];

        if ($request['method'] === 'get')
        {
            return $request['url'];
        }

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        $this->validateActionInput($input, 'verify');

        $id = $input[RequestFields::PAYMENT_ID];

        $content = [
            ResponseFields::BANK_REF_NUMBER => self::BANK_REF_NUMBER,
            ResponseFields::PAYMENT_ID      => $id,
            ResponseFields::STATUS          => Status::SUCCESS,
        ];

        $this->content($content, 'verify');

        $content = http_build_query($content, '', Constants::VERIFY_PAIR_SEPARATOR);

        $this->content($content, 'verifyafterquerybuild');

        return $this->makeResponse($content);
    }

    protected function getAuthResponseContent($content)
    {
        $data = [
            ResponseFields::AMOUNT                  => $content[RequestFields::AMOUNT],
            ResponseFields::BILLER_NAME             => $content[RequestFields::BILLER_NAME],
            ResponseFields::PAYMENT_ID              => $content[RequestFields::PAYMENT_ID],
            ResponseFields::STATUS                  => Status::SUCCESS,
            ResponseFields::BANK_REF_NUMBER         => self::BANK_REF_NUMBER,
            ResponseFields::CUSTOMER_ACCOUNT_NUMBER => self::CUSTOMER_ACCOUNT_NUMBER,
        ];

        $this->content($data, 'authorize');

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        return [
            ResponseFields::ENCRYPTED_DATA => $encryptor->encryptData($data)
        ];
    }
}
