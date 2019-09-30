<?php

namespace RZP\Gateway\Netbanking\Rbl\Mock;

use RZP\Gateway\Base;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Rbl\Status;
use RZP\Gateway\Netbanking\Rbl\RequestFields;
use RZP\Gateway\Netbanking\Rbl\ResponseFields;
use RZP\Models\Currency\Currency;

class Server extends Base\Mock\Server
{
    protected $bank = IFSC::RATN;

    const BANK_REF_NO = 99999999;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedString = $this->getGatewayInstance()
                                ->getDecryptedString($input[RequestFields::QUERY_STRING]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $response = $this->getCallbackResponseData($decryptedData);

        $this->content($response, 'authorize');

        $request = [
            'url'     => $input[RequestFields::RETURN_URL],
            'content' => $response,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        parse_str($input, $attributes);

        $this->validateActionInput($attributes);

        $response = $this->getVerifyResponseData($attributes);

        $this->content($response, 'verify');

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::STATUS             => Status::SUCCESS,
            ResponseFields::BANK_REFERENCE     => self::BANK_REF_NO,
            ResponseFields::MERCHANT_REFERENCE => $input[RequestFields::MERCHANT_REFERENCE],
        ];

        return $data;
    }

    protected function getVerifyResponseData(array $input)
    {
        $content =
        [
            ResponseFields::CURRENCY     => Currency::INR,
            ResponseFields::ENTRY_STATUS => Status::SUCCESS,
            ResponseFields::REFERENCE_ID => self::BANK_REF_NO,
            ResponseFields::AMOUNT       => 'INR|1,00,000.12',
        ];

        $content = array_flip($content);

        $xml =  new \SimpleXMLElement('<xml/>');

        $status = $xml->addChild('RetrieveTransactionStatus');

        $transactionStatus = $status->addChild('RetrieveTransactionStatus_REC');

        array_walk_recursive($content, array($transactionStatus, 'addChild'));

        return $xml->asXml();
    }

    protected function getDecryptedData(string $decryptedString)
    {
        $data = explode('|', $decryptedString);

        $decryptedData = [];

        foreach ($data as $dataItem)
        {
            $keyValue = explode('~', $dataItem);

            $decryptedData[$keyValue[0]] = $keyValue[1];
        }

        return $decryptedData;
    }
}
