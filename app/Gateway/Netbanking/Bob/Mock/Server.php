<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Bob\RequestFields;
use RZP\Gateway\Netbanking\Bob\ResponseFields;
use RZP\Gateway\Netbanking\Bob\Constants;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const CUSTOMER_ACCOUNT_NUMBER = '123000000345678';
    const BANK_REF_NUMBER         = 'AB1234';

    public function authorize($input)
    {
        parent::authorize($input);

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $content = $encryptor->decryptData($input[RequestFields::ENCRYPTED_DATA]);

        $this->validateAuthorizeInput($content);

        $authResponseContent = $this->getAuthResponseContent($content);

        $redirectUrl = $content[RequestFields::CALLBACK_URL];

        $params = http_build_query($authResponseContent);

        return \Redirect::to($redirectUrl . '?' . $params);
    }

    public function verify($input)
    {
        $id = $input[RequestFields::PAYMENT_ID];

        $content = [
            ResponseFields::BANK_REF_NUMBER => self::BANK_REF_NUMBER,
            ResponseFields::PAYMENT_ID => $id,
            ResponseFields::STATUS => Constants::STATUS_SUCCESS,
        ];

        $this->content($content, 'verify');

        $content = $this->prepareVerifyResponse($content);

        return $this->makeResponse($content);
    }

    protected function getAuthResponseContent($content)
    {
        $data = [
            ResponseFields::AMOUNT => $content[RequestFields::AMOUNT],
            ResponseFields::BILLER_NAME => $content[RequestFields::BILLER_NAME],
            ResponseFields::PAYMENT_ID => $content[RequestFields::PAYMENT_ID],
            ResponseFields::STATUS => Constants::STATUS_SUCCESS,
            ResponseFields::BANK_REF_NUMBER => self::BANK_REF_NUMBER,
            ResponseFields::CUSTOMER_ACCOUNT_NUMBER => self::CUSTOMER_ACCOUNT_NUMBER,
        ];

        $this->content($data, 'authorize');

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        return [
            ResponseFields::ENCRYPTED_DATA => $encryptor->encryptData($data)
        ];
    }

    protected function prepareVerifyResponse($content)
    {
        $array = [];

        foreach ($content as $key => $value)
        {
            $array[] = implode(Constants::VERIFY_KEY_VALUE_SEPARATOR, [$key, $value]);
        }

        return implode(Constants::VERIFY_PAIR_SEPARATOR, $array);
    }
}
