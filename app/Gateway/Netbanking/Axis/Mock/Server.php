<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Axis\Status;
use RZP\Gateway\Netbanking\Axis\AESCrypto;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Gateway\Netbanking\Axis\RequestFields;
use RZP\Gateway\Netbanking\Axis\ResponseFields;

class Server extends Base\Mock\Server
{
    protected $bankingType = 'retail';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->getDecryptedData($input);

        $decryptedData = $this->setTestData($decryptedData);

        $response = $this->createResponse($decryptedData);

        $callbackUrl = $input[RequestFields::RETURN_URL] . '?' .
                        http_build_query($response);

        return $callbackUrl;
    }

    protected function setTestData($decryptedData)
    {
        if ($decryptedData['AMT'] === '300')
        {
            $decryptedData[RequestFields::MERCHANT_REFERENCE] = "123";
        }

        return $decryptedData;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $response = $this->getVerifyXml($input);

        return $this->makeResponse($response);
    }

    protected function getDecryptedData($input)
    {
        $data = $this->getDecryptedDataForBankingType($input);

        // Since there is no way to identify based on parameter if the
        // corporate netbanking was to be employed, this can be checked
        // by trying to decrypt the payment using the corporate key
        if (empty($data) === true)
        {
            $dataCorporate = $this->getDecryptedDataForBankingType($input, 'corporate');

            if (empty($dataCorporate) === false)
            {
                $this->bankingType = 'corporate';

                $data = $dataCorporate;
            }
        }

        return $data;
    }

    protected function getDecryptedDataForBankingType($input, $bankingType = null)
    {
        $masterKey = $this->getGatewayInstance($bankingType)->getSecret();

        $crypto = new AESCrypto($masterKey);

        $decryptedString = $crypto->decryptString($input[RequestFields::ENCRYPTED_STRING]);

        $toReplace   = ['~', '$'];
        $willReplace = ['=', '&'];

        $decryptedString = str_replace($toReplace, $willReplace, $decryptedString);

        parse_str($decryptedString, $data);

        return $data;
    }

    protected function createResponse($data)
    {
        $response =  [
            ResponseFields::STATUS             => Status::YES,
            ResponseFields::MERCHANT_REFERENCE => $data[RequestFields::MERCHANT_REFERENCE],
            ResponseFields::BANK_REFERENCE_ID  => 9999999999,
            ResponseFields::ITEM_CODE          => $data[RequestFields::ITEM_CODE],
            ResponseFields::AMOUNT             => $data[RequestFields::AMOUNT],
            ResponseFields::CURRENCY_CODE      => Currency::INR,
            ResponseFields::FLAG               => Status::SUCCESS,
        ];

        // for test cases
        $this->content($response);

        $masterKey = $this->getMasterKeyFromGateway();

        // Make sure this is correct, there is some lack of clarity here
        $query = http_build_query($response);

        $crypto = new AESCrypto($masterKey);

        $encryptedString = $crypto->encryptString($query);

        $content[ResponseFields::ENCRYPTED_STRING] = $encryptedString;

        return $content;
    }

    protected function getMasterKeyFromGateway()
    {
        return $this->getGatewayInstance($this->bankingType)->getSecret();
    }

    protected function getVerifyXml($input)
    {
        $response = [
            ResponseFields::PAYEE_ID            => $input[RequestFields::VERIFY_PAYEE_ID],
            ResponseFields::ITEM_CODE           => $input[RequestFields::VERIFY_ITC],
            ResponseFields::MERCHANT_REFERENCE  => $input[RequestFields::VERIFY_PRN],
            // Converting response amount to string as array_flip needs string
            ResponseFields::VERIFY_RESPONSE_AMT => (string) $input[RequestFields::VERIFY_AMT],
            ResponseFields::DATE                => $input[RequestFields::VERIFY_DATE],
            ResponseFields::BANK_REFERENCE_ID   => '',
            ResponseFields::PAYMENT_STATUS      => Status::SUCCESS,
        ];

        // for test cases
        $this->content($response);

        // For null verify response
        if ($response === "")
        {
            return $response;
        }

        $response = array_flip($response);

        $xml = new \SimpleXMLElement('<DataSet/>');
        $xml->addChild('Table1');

        array_walk_recursive($response, array ($xml->Table1, 'addChild'));

        $this->content($xml, 'multiple_tables');

        return $xml->asXML();
    }
}
