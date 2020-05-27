<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Axis\Status;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Gateway\Netbanking\Axis\AESCrypto;
use RZP\Gateway\Netbanking\Base\BankingType;
use RZP\Gateway\Netbanking\Axis\RequestFields;
use RZP\Gateway\Netbanking\Axis\ResponseFields;

class Server extends Base\Mock\Server
{
    use EmandateTrait;

    protected $config;

    protected $bankingType = BankingType::RETAIL;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('gateway.netbanking_axis');
    }

    public function authorize($input)
    {
        parent::authorize($input);

        if (isset($input[Emandate\RequestFields::DATA]) === true)
        {
            return $this->handleEmandateAuthFlow($input);
        }

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->getDecryptedMockAuthData($input);

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

        $content = ['type' => 'retail'];

        $this->content($content, 'verify');

        if (isset($input[Emandate\RequestFields::DATA]) === true)
        {
            return $this->handleEmandateVerifyFlow($input);
        }

        $response = $this->getVerifyXml($input, $content['type']);

        return $this->makeResponse($response);
    }

    protected function getDecryptedMockAuthData($input)
    {
        $data = $this->getDecryptedDataForBankingType($input);

        // Since there is no way to identify based on parameter if the
        // corporate netbanking was to be employed, this can be checked
        // by trying to decrypt the payment using the corporate key
        // Moving to an isset based check as decryption sometimes fails
        // with garbage value
        if (isset($data['PRN']) === false)
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

    protected function getDecryptedData(string $decryptedString): array
    {
        $decryptedString = str_replace('|', '&', $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

    protected function getDecryptedDataForBankingType($input, $bankingType = BankingType::RETAIL)
    {
        $this->bankingType = $bankingType;

        $masterKey = $this->getSecret();

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
        $content = $this->getBaseResponseForBankingType($data);

        // for test cases
        $this->content($content, 'authorize');

        $masterKey = $this->getSecret();

        // Make sure this is correct, there is some lack of clarity here
        $query = http_build_query($content);

        $crypto = new AESCrypto($masterKey);

        $encryptedString = urlencode($crypto->encryptString($query));

        $response[ResponseFields::ENCRYPTED_STRING] = $encryptedString;

        return $response;
    }

    protected function getBaseResponseForBankingType($data)
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

        if ($this->bankingType === 'corporate')
        {
            $response[ResponseFields::PAID] = Status::YES;
        }

        return $response;
    }

    public function getS2sResponseForPayment($gatewayPayment)
    {
        $tranDateTime = Carbon::createFromTimestamp(
                        $gatewayPayment['created_at'] + 10,
                        Timezone::IST)
                        ->format('d/m/y+h:m:s');

        $content = [
            ResponseFields::BANK_REFERENCE_ID  => $gatewayPayment['bank_payment_id'],
            ResponseFields::PAID               => Status::YES,
            ResponseFields::TRAN_DATE_TIME     => $tranDateTime,
            ResponseFields::AMOUNT             => $gatewayPayment['amount'],
            ResponseFields::ITEM_CODE          => $gatewayPayment['reference1'],
            ResponseFields::MERCHANT_REFERENCE => $gatewayPayment['payment_id'],
        ];

        return $content;
    }

    protected function getVerifyXml($input, $type)
    {
        if ($type === 'corporate')
        {
            $this->bankingType = BankingType::CORPORATE;

            $this->validateActionInput($input, 'corporate_verify');

            $decryptedString = $this->getDataFromEncryptedVerifyInput($input[RequestFields::VERIFY_ENCDATA]);

            $input = $this->getDecryptedData($decryptedString);

            $input = array_change_key_case($input, CASE_LOWER);
        }
        else
        {
            $this->bankingType = BankingType::RETAIL;

            $this->validateActionInput($input);

            $decryptedString = $this->getDataFromEncryptedVerifyInput($input[RequestFields::VERIFY_ENCDATA]);

            $input = $this->getDecryptedData($decryptedString);
        }

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
        $this->content($response, 'verify_content');

        // For null verify response
        if ($response === "")
        {
            return $response;
        }

        $xml = new \SimpleXMLElement('<DataSet/>');

        $xml->addChild('Table1');

        $this->arrayToXml($response, $xml->Table1);

        $this->content($xml, 'multiple_tables');

        $xmlString = $xml->asXML();

        $encryptedXml = $this->getVerifyEncryptedStringResponse($xmlString);

        return $encryptedXml;
    }

    protected function arrayToXml($array, &$xml)
    {
        foreach ($array as $key => $value)
        {
            if (is_array($value) === true)
            {
                if (is_numeric($key) === true)
                {
                    $subnode = $xml->addChild("item" . $key);

                    array_to_xml($value, $subnode);
                }
                else
                {
                    $subnode = $xml->addChild($key);

                    array_to_xml($value, $subnode);
                }
            }
            else
            {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    protected function getDataFromEncryptedVerifyInput($encryptedString)
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $decryptedString = $crypto->decryptString($encryptedString);

        return $decryptedString;
    }

    protected function getVerifyEncryptedStringResponse(string $data)
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $encryptedString = $crypto->encryptString($data);

        return $encryptedString;
    }

    protected function getSecret()
    {
        if ($this->bankingType === BankingType::RETAIL)
        {
            if ($this->action === Action::VERIFY)
            {
                return $this->config['verify_test_hash_secret'];
            }

            return $this->config['test_hash_secret_new'];
        }
        else
        {
            if ($this->action === Action::VERIFY)
            {
                $key = $this->config['test_hash_secret_corporate_verify'];

                return substr($key, 0, 16);
            }
            else
            {
                return $this->config['test_hash_secret_corporate'];
            }
        }
    }
}
