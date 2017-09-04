<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Exception;
use RZP\Gateway\Base;

use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Icici\Status;
use RZP\Gateway\Netbanking\Icici\Confirmation;
use RZP\Gateway\Netbanking\Base as Netbanking;
use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->decryptData($input);

        $this->validateActionInput($decryptedData, 'auth_decrypted');

        $postData = $this->createPostData($decryptedData);

        $this->content($postData);

        $content = $this->formatResponseData($postData, $input);

        $callbackUrl = $decryptedData['RU'] . '?' . http_build_query($content);

        return $callbackUrl;
    }

    public function getBankingType($input)
    {
        return ($input['PID'] === 'random_pid_corp') ? 'corporate' : 'retail';
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $responseArray = $this->createResponseArray($input);

        $response = $this->createXmlResponse($responseArray);

        return $this->makeResponse($response);
    }

    protected function createPostData(array $input)
    {
        $response = [
            ResponseFields::PAYMENT_ID    => $input[RequestFields::PAYMENT_ID],
            ResponseFields::ITEM_CODE     => strtoupper($input[RequestFields::ITEM_CODE]),
            ResponseFields::AMOUNT        => $input[RequestFields::AMOUNT],
            ResponseFields::CURRENCY_CODE => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::PAID          => Confirmation::YES,
        ];

        if ($input[RequestFields::CONFIRMATION] === Confirmation::YES)
        {
            $response[ResponseFields::BANK_PAYMENT_ID] = 9999999999;
        }

        return $response;
    }

    protected function formatResponseData(array $postData, array $input)
    {
        $httpQuery = http_build_query($postData);

        $bankingType = $this->getBankingType($input);

        $aes = $this->getAesCrypto($input);

        $content['ES'] = base64_encode($aes->encryptString($httpQuery));

        // response as sent back for corporate payment
        if ($bankingType === 'corporate')
        {
            $content['Payopt'] = 'ICI';

            $content['var1']   = 'xyz';
        }

        $this->content($content, 'hash');

        return $content;
    }

    protected function getAesCrypto($input)
    {
        $bankingType = $this->getBankingType($input);

        $masterKey = $this->getGatewayInstance($bankingType)->getSecret();

        return new Base\AESCrypto(AES::MODE_ECB, $masterKey);
    }

    protected function decryptData(array $input)
    {
        $aes = $this->getAesCrypto($input);

        $decryptedString = $aes->decryptString(base64_decode($input['ES']));

        if ($decryptedString === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Encrypted string not decryptable');
        }

        // Removing the %22 tags in the return URL
        $string = str_replace('%22', '', $decryptedString);

        parse_str($string, $decryptedData);

        return $decryptedData;
    }

    protected function createXmlResponse(array $responseArray)
    {
        $this->content($responseArray);

        if (is_array($responseArray) === false)
        {
            return $responseArray;
        }

        $responseArray = array_flip($responseArray);

        $xml = new \SimpleXMLElement('<VerifyOutput/>');

        array_walk_recursive($responseArray, array ($xml, 'addAttribute'));

        $response = $xml->asXML();

        return $response;
    }

    protected function createResponseArray(array $input)
    {
        $bankingType = $this->getBankingType($input);

        if ($bankingType === 'corporate')
        {
            return [
                ResponseFields::BILL_REF_NUM => $input[RequestFields::PAYMENT_ID],
                ResponseFields::PAYMENTID    => '..',
                ResponseFields::CONSUMER_CODE => $input[RequestFields::ITEM_CODE],
                ResponseFields::UC_AMOUNT    => $input[RequestFields::AMOUNT],
                ResponseFields::STATUS       => Status::Y,
                ResponseFields::CURRENCY     => $input[RequestFields::CURRENCY_CODE],
            ];
        }

        return [
            ResponseFields::ITEM_CODE    => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_ID   => $input[RequestFields::PAYMENT_ID],
            ResponseFields::CURRENCY     => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::PAYMENT_DATE => $input[RequestFields::PAYMENT_DATE],
            ResponseFields::AMOUNT       => $input[RequestFields::AMOUNT],
            ResponseFields::STATUS       => Status::SUCCESS,
        ];
    }
}
