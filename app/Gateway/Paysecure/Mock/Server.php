<?php

namespace RZP\Gateway\Paysecure\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Paysecure;
use RZP\Constants\HashAlgo;

class Server extends Base\Mock\Server
{
    const HKEY = '5629y50g-e743-0022-5i2b-9aw8de632896';

    const TRAN_ID = '100000000000000000000000025236';

    const APPRCODE = '183217';

    protected function getCheckbin2Response($data)
    {
        $response = [
            Paysecure\Fields::STATUS                => Paysecure\StatusCode::SUCCESS,
            Paysecure\Fields::ERROR_CODE            => '0',
            Paysecure\Fields::ERROR_MESSAGE         => '',
            Paysecure\Fields::QUALIFIED_INTERNETPIN => 'TRUE',
            Paysecure\Fields::IMPLEMENTS_REDIRECT   => 'TRUE',
        ];

        // For iFrame flow for this BIN
        if ($data[Paysecure\Fields::CARD_BIN] === '607484990')
        {
            $response[Paysecure\Fields::IMPLEMENTS_REDIRECT] = 'FALSE';
        }

        $this->content($response, 'checkbin2');

        return $response;
    }

    protected function getInitiate2Response($data)
    {
        $this->validateActionInput($data, 'initiate2');

        $this->content($data[Paysecure\Fields::TRANSACTION_TYPE_INDICATOR], 'validate_message_type');

        $this->content($data[Paysecure\Fields::TERMINAL_OWNER_NAME], 'validate_terminal_owner_name');

        $this->content($data[Paysecure\Fields::STAN], 'validate_stan');

        $redirectUrl = $this->route->getUrlWithPublicAuth('mock_paysecure_payment');

        $redirectUrl .= '&AccuCardholderId=89172389132&AccuGuid=6089d50e-e012-1160-8b3b-0ab8de556755'
                      . '&AccuHkey=' . self::HKEY;

        $response = [
            Paysecure\Fields::STATUS                      => Paysecure\StatusCode::SUCCESS,
            Paysecure\Fields::ERROR_CODE                  => '0',
            Paysecure\Fields::ERROR_MESSAGE               => '',
            Paysecure\Fields::TRAN_ID                     => self::TRAN_ID,
            Paysecure\Fields::REDIRECT_URL                => $redirectUrl,
            Paysecure\Fields::AUTHENTICATION_NOT_REQUIRED => 'FALSE',
        ];

        $this->content($response, 'initiate2');

        return $response;
    }

    protected function getInitiateResponse($data)
    {
        $this->validateActionInput($data, 'initiate');

        $response = [
            Paysecure\Fields::STATUS        => Paysecure\StatusCode::SUCCESS,
            Paysecure\Fields::ERROR_CODE    => '0',
            Paysecure\Fields::ERROR_MESSAGE => '',
            Paysecure\Fields::TRAN_ID       => self::TRAN_ID,
            Paysecure\Fields::GUID          => '07222ddf-5215-12d3-9309-da713843d30a',
            Paysecure\Fields::MODULUS       => '99FE9064CD6CD3CBA87C0DF728B31E18B5',
            Paysecure\Fields::EXPONENT      => '010001',
        ];

        $this->content($response, 'initiate');

        return $response;
    }

    protected function getAuthorizeResponse($data)
    {
        $response = [
            Paysecure\Fields::STATUS        => Paysecure\StatusCode::SUCCESS,
            Paysecure\Fields::ERROR_CODE    => '00',
            Paysecure\Fields::ERROR_MESSAGE => '',
            Paysecure\Fields::APPRCODE      => self::APPRCODE,
        ];

        $this->content($response, 'authorize');

        return $response;
    }

    protected function getTransactionstatusResponse($data)
    {
        $response = [
            Paysecure\Fields::STATUS        => Paysecure\StatusCode::SUCCESS,
            Paysecure\Fields::ERROR_CODE    => '00',
            Paysecure\Fields::ERROR_MESSAGE => '',
        ];

        $transactionArray = [
            Paysecure\Fields::STATUS    => Paysecure\StatusCode::TRANSACTION_STATUS_AUTHORIZED,
            Paysecure\Fields::TRAN_ID   => self::TRAN_ID,
            Paysecure\Fields::APPRCODE  => self::APPRCODE,
            Paysecure\Fields::RECURRING => 'FALSE',
            Paysecure\Fields::DATETIME  => '12/31/201219:09:51',
            Paysecure\Fields::AMOUNT    => 5000,
        ];

        $response[Paysecure\Fields::HISTORY][Paysecure\Fields::TRANSACTION] = $transactionArray;

        $this->content($response, 'transaction_status');

        return $response;
    }

    public function authorize($input)
    {
        $response = $this->getAuthResponse($input);

        return $this->makePostResponse($response);
    }

    protected function generateHashOfData($dataToHash)
    {
        $str = implode('&', $dataToHash);

        return hash_hmac(HashAlgo::SHA256, $str, self::HKEY);
    }

    protected function getAuthResponse($input)
    {
        // If redirect flow
        if (isset($input[Paysecure\Fields::ACCU_GUID]) === true)
        {
            $content = [
                Paysecure\Fields::ACCU_GUID          => $input[Paysecure\Fields::ACCU_GUID],
                Paysecure\Fields::SESSION            => $input[Paysecure\Fields::SESSION],
                Paysecure\Fields::ACCU_RESPONSE_CODE => 'ACCU000',
            ];

            $this->content($content, 'auth_response');

            $dataToHash = [
                self::TRAN_ID,
                $input[Paysecure\Fields::ACCU_GUID],
                $input[Paysecure\Fields::SESSION],
                $content[Paysecure\Fields::ACCU_RESPONSE_CODE],
            ];

            $hash = $this->generateHashOfData($dataToHash);

            $content[Paysecure\Fields::ACCU_REQUEST_ID] = base64_encode($hash);
        }
        // For Iframe flow
        else
        {
            $content = [
                Paysecure\Fields::ACCU_RESPONSE_CODE => 'ACCU000',
            ];

            $this->content($content, 'auth_response');
        }

        return [
            'url'     => $input[Paysecure\Fields::ACCU_RETURN_URL],
            'method'  => 'post',
            'content' => $content,
        ];
    }

    protected function getWsdlFile()
    {
        return dirname(__DIR__) . '/Mock/wsdl/rupay.wsdl.test';
    }

    // @codingStandardsIgnoreStart
    public function CallPaySecure($request)
    {
        $data = html_entity_decode($request->strXML);

        // XML to array
        $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);

        $data = Paysecure\XmlSerializer::xmlToArray($xml);

        $functionName = 'get' . camel_case($request->strCommand) . 'Response';

        $response = $this->{$functionName}($data);

        $obj = new \stdClass();

        $xml = new \SimpleXMLElement('<?xml version="1.0"?><paysecure></paysecure>');

        Paysecure\XmlSerializer::arrayToXml($response, $xml);

        $obj->CallPaySecureResult = $xml->asXML();

        return $obj;
    }

    public function RequestorCredentials($request)
    {
        return;
    }
}
