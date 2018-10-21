<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Netbanking\Canara\Constants;
use RZP\Gateway\Netbanking\Canara\AESCrypto;
use RZP\Gateway\Netbanking\Canara\RequestFields;
use RZP\Gateway\Netbanking\Canara\ResponseFields;
use RZP\Gateway\Netbanking\Canara\TransactionType;


class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const BANK_REFERENCE_NUMBER = 'AB1234';

    private $bank_ref_no;

    public function authorize($input)
    {
        parent::authorize($input);

        $input = $this->decryptString($input[RequestFields::ENCRYPTED_DATA]);

        $input = $this->getInputData($input);

        $this->validateAuthorizeInput($input);

        $this->validateChecksum($input);

        $content = $this->getCallbackResponseData($input);

        $this->content($content, 'authorize');

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_canara_get');

        $content = http_build_query($content);

        $checksum = $this->getChecksum($content);

        $queryString = $content . '&checksum=' . $checksum;

        $encrypted = $this->encryptString($queryString);

        $callbackUrl .= '?' . RequestFields::ENCRYPTED_DATA . '=' . $encrypted;

        $request = [
            'url'     => $callbackUrl,
            'content' => [],
            'method'  => 'get',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        $data = $this->getVerifyResponseData($input);

        $response = $this->createXmlResponse($data);

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        $this->bank_ref_no = Base\Entity::generateUniqueId();

        $data = [
            ResponseFields::ACTION                 => TransactionType::AUTHORIZE,
            ResponseFields::MERCHANT_CODE          => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::PAYMENT_ID             => $input[RequestFields::PAYMENT_ID],
            ResponseFields::AMOUNT                 => $input[RequestFields::AMOUNT],
            ResponseFields::CLIENT_CODE            => $input[RequestFields::CLIENT_CODE],
            ResponseFields::CURRENCY               => $input[RequestFields::CURRENCY],
            ResponseFields::SUCCESS_STATIC_FLAG    => $input[RequestFields::SUCCESS_STATIC_FLAG],
            ResponseFields::FAILURE_STATIC_FLAG    => $input[RequestFields::FAILURE_STATIC_FLAG],
            ResponseFields::DATE                   => $input[RequestFields::DATE],
            ResponseFields::SERVICE_CHARGE         => $input[RequestFields::SERVICE_CHARGE],
            ResponseFields::BANK_REFERENCE_NUMBER  => $this->bank_ref_no,
            ResponseFields::CLIENT_ACCOUNT         => $input[RequestFields::CLIENT_ACCOUNT],
            ResponseFields::MESSAGE                => Constants::DEFAULT_MESSAGE,
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
            ResponseFields::VER_BANK_REFERENCE_NUMBER         => $this->bank_ref_no,
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

    public function decryptString(string $encryptedString): string
    {
        $config = $this->app['config']['gateway']['netbanking_canara'];

        $aes = new AESCrypto(Mode::TEST, $config);

        return $aes->decryptString($encryptedString);
    }

    protected function getInputData($input)
    {
        $inputArray = [];

        parse_str($input, $inputArray);

        return $inputArray;
    }

    protected function validateChecksum($content)
    {
        $receivedChecksum = $content[RequestFields::CHECKSUM];

        unset($content[RequestFields::CHECKSUM]);

        $content = http_build_query($content);

        $calculatedChecksum = $this->getChecksum($content);

        if ($receivedChecksum !== $calculatedChecksum)
        {
            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function getChecksum($content)
    {
        return strtoupper(hash(HashAlgo::SHA256, $content));
    }

    protected function encryptString($content)
    {
        $config = $this->app['config']['gateway']['netbanking_canara'];

        $encryptor = new AESCrypto(Mode::TEST, $config);

        return $encryptor->encryptString($content);
    }
}