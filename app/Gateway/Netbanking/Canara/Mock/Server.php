<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use Carbon\Carbon;

use RZP\Constants\Timezone;
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

        $date = $this->changeDateFormat($input[RequestFields::DATE]);

        $data = [
            ResponseFields::ACTION                 => TransactionType::AUTHORIZE,
            ResponseFields::MERCHANT_CODE          => $input[RequestFields::MERCHANT_CODE],
            ResponseFields::PAYMENT_ID             => $input[RequestFields::PAYMENT_ID],
            ResponseFields::AMOUNT                 => $input[RequestFields::AMOUNT],
            ResponseFields::CLIENT_CODE            => $input[RequestFields::CLIENT_CODE],
            ResponseFields::CURRENCY               => $input[RequestFields::CURRENCY],
            ResponseFields::ACK_STATIC_FLAG        => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            ResponseFields::RESPONSE_STATIC_FLAG   => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            ResponseFields::DATE                   => $date,
            ResponseFields::SERVICE_CHARGE         => $input[RequestFields::SERVICE_CHARGE],
            ResponseFields::BANK_REFERENCE_NUMBER  => $this->bank_ref_no,
            ResponseFields::MESSAGE                => Constants::DEFAULT_MESSAGE,
        ];

        return $data;
    }

    protected function getVerifyResponseData(array $input)
    {
        $data = [
            ResponseFields::VER_CLIENT_ACCOUNT                => '',
            ResponseFields::VER_PAYMENT_ID                    => $input[RequestFields::PAYMENT_ID],
            ResponseFields::PUR_DATE                          => $input[RequestFields::PUR_DATE],
            ResponseFields::VER_BANK_REFERENCE_NUMBER         => $this->bank_ref_no,
            ResponseFields::VER_AMOUNT                        => $input[RequestFields::AMOUNT],              // have to verify
            ResponseFields::STATUS                            => [
                                                                   ResponseFields::VERIFY_STATUS => Constants::SUCCESS_VERIFY_STATUS,
                                                                   ResponseFields::RETURN_CODE => Constants::SUCCESS,
                                                                 ]
         ];

        $this->content($data, Base\Action::VERIFY);

        return $data;
    }

    protected function createXmlResponse(array $data)
    {
        $this->content($data,'verify');

        $xml = new \SimpleXMLElement('<VerifyOutput/>');

        $status = $data[ResponseFields::STATUS];

        unset($data[ResponseFields::STATUS]);

        foreach ($data as $key => $value) {
            $xml->addChild($key,$data[$key]);
        }

        $statusTag = $xml->addChild(ResponseFields::STATUS);

        foreach ($status as $key => $value)
        {
            $statusTag->addChild($key, $value);
        }

        $response = $xml->asXML();

        return $response;
    }

    public function decryptString(string $encryptedString): string
    {
        $config = $this->app['config']['gateway']['netbanking_canara'];

        $aes = new AESCrypto($config);

        return $aes->decryptString($encryptedString);
    }

    protected function getInputData($input)
    {
        $inputArray = [];

        $input  = explode('&', $input);

        foreach ($input as $pair)
        {
            list($key, $value) = explode('=', $pair);

            $inputArray[$key] = $value;
        }

        return $inputArray;
    }

    protected function validateChecksum($content)
    {
        $receivedChecksum = $content[RequestFields::CHECKSUM];

        unset($content[RequestFields::CHECKSUM]);

        $calculatedChecksum = $this->getGatewayInstance()->generateHash($content);

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

        $encryptor = new AESCrypto($config);

        return $encryptor->encryptString($content);
    }

    protected function changeDateFormat($dateTime)
    {
        $date = explode('+' , $dateTime)[0];

        $date = Carbon::createFromFormat('d/m/Y', $date, Timezone::IST);

        return $date->format('d-m-Y');
    }
}
