<?php
namespace RZP\Gateway\Netbanking\Equitas\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Equitas\Status;
use RZP\Gateway\Netbanking\Equitas\Constants;
use RZP\Gateway\Netbanking\Equitas\RequestFields;
use RZP\Gateway\Netbanking\Equitas\ResponseFields;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const TRANSACTION_ID = 'AB1234';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = $this->getCallbackResponseData($input);

        $this->content($content, 'authorize');

        $request = [
            'url'       => $input[RequestFields::RETURN_URL],
            'content'   => $content,
            'method'    => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateVerifyInput($input);

        $dataArray = $this->getVerifyResponseData();

        $response = $this->createXmlResponse($dataArray);

        return $response;
    }

    protected function validateAuthorizeInput($input)
    {
        $this->validateActionInput($input, 'auth');
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::PAYMENT_ID              => $input[RequestFields::PAYMENT_ID],
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            RequestFields::RETURN_URL               => $input[RequestFields::RETURN_URL],
            ResponseFields::ACCOUNT_NUMBER          => $input[RequestFields::ACCOUNT_NUMBER],
            ResponseFields::MODE                    => $input[RequestFields::MODE],
            ResponseFields::DESCRIPTION             => $input[RequestFields::DESCRIPTION],
            ResponseFields::BANK_PAYMENT_ID         => self::TRANSACTION_ID,
            ResponseFields::AUTH_STATUS             => Status::YES,
        ];

        $this->content($data, Base\Action::CALLBACK);

        $checkSum = $this->generateHash($data);

        $data[ResponseFields::CHECKSUM]         = $checkSum;
        $data[ResponseFields::ERROR_CODE]       = Constants::UNDEFINED;
        $data[ResponseFields::ERROR_MESSAGE]    = Constants::UNDEFINED;

        unset($data[RequestFields::RETURN_URL]);

        return $data;
    }

    protected function getVerifyResponseData()
    {
        $data = [
            ResponseFields::VERIFICATION            => ResponseFields::VERIFY_STATUS . '=' . Status::YES,
            ResponseFields::VERIFY_CHECKSUM_STATUS  => Constants::TRUE,
        ];

        $this->content($data, Base\Action::VERIFY);

        return $data;
    }

    protected function createXmlResponse(array $dataArray)
    {
        $dom = new \DOMDocument();

        $dom->formatOutput = true;

        $root = $dom->createElement('XML');

        $root = $dom->appendChild($root);

        foreach ($dataArray as $key => $value)
        {
            $title = $dom->createElement($key);

            $title = $root->appendChild($title);

            $text = $dom->createTextNode($value);

            $text = $title->appendChild($text);
        }

        $xml = $dom->saveXML();

        $this->content($xml, 'verifyXml');

        $response = parent::makeResponse($xml);

        return $response;
    }

    protected function validateVerifyInput($input)
    {
        $this->validateActionInput($input, 'verify');
    }
}
