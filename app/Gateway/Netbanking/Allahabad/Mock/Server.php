<?php

namespace RZP\Gateway\Netbanking\Allahabad\Mock;

use RZP\Gateway\Base;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Allahabad\Status;
use RZP\Gateway\Netbanking\Allahabad\RequestFields;
use RZP\Gateway\Netbanking\Allahabad\ResponseFields;

class Server extends Base\Mock\Server
{
    protected $bank = IFSC::ALLA;

    public function authorize($input)
    {
        parent::authorize($input);

        $stringForValidation = $input['parameter_string'];

        $sig = $input['bank_signaturte'];

        $input['parameter_string'] = str_replace('|', '&', $input['parameter_string']);

        parse_str($input['parameter_string'],$input);

        $input['bank_signaturte'] = $sig;

        $this->validatechecksum($stringForValidation, $input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response, 'authorize');

        $checksumString = http_build_query($response,null,'|');

        $callbackChecksum = $this->getHashOfString($checksumString);

        $response[ResponseFields::CHECKSUM] = $callbackChecksum;

        $callbackUrl = $input[RequestFields::RETURN_URL];

        $callbackUrl .= '?parameter_string='.$checksumString.'&response_signaturte='.$response[ResponseFields::CHECKSUM];

        $request = [
            'url'     => $callbackUrl,
            'content' => [],
            'method'  => 'get',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $responseArray = $this->createVerifyResponseArray($input);

        $response = $this->createXmlResponse($responseArray);

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::PAID                    => Status::YES,
            ResponseFields::ITEM_CODE               => $input[RequestFields::ITEM_CODE],
            ResponseFields::PRODUCT_REF_NUMBER      => $input[RequestFields::PRODUCT_REF_NUMBER],
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::BANK_TRANSACTION_ID     => 99999,
        ];

        $this->content($data, Base\Action::CALLBACK);

        return $data;
    }

    protected function createXmlResponse(array $responseArray)
    {
        $this->content($responseArray, 'verify');

        if (is_array($responseArray) === false)
        {
            return $responseArray;
        }

        $xml = new \SimpleXMLElement('<XML/>');

        $status = $responseArray[ResponseFields::PAID];

        $str = "PAID = $status";

        $xml->addChild('VERIFICATION',$str,null);

        $response = $xml->asXML();

        return $response;
    }

    protected function createVerifyResponseArray(array $input)
    {
        $responseArray = [
                ResponseFields::PAID    => Status::YES,
        ];

        return $responseArray;
    }

    protected function getStringFromContent($content, $glue = '')
    {
        return implode($glue, $content);
    }

    protected function validatechecksum($stringForValidation, $input)
    {
        $inputHash = $input['bank_signaturte'];

        $expectedHash = $this->getHashOfString($stringForValidation);

        $this->compareHashes($inputHash, $expectedHash);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $sigStr = hash_hmac('sha1',$str,$secret);

        return $sigStr;
    }
}

