<?php

namespace RZP\Gateway\Netbanking\Allahabad\Mock;

use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Allahabad\Status;
use RZP\Gateway\Netbanking\Allahabad\RequestFields;
use RZP\Gateway\Netbanking\Allahabad\ResponseFields;

class Server extends Base\Mock\Server
{
    protected $bank = IFSC::ALLA;

    public function authorize($input)
    {
        $string_for_validation = $input['parameter_string'];

        $sig = $input['bank_signature'];

        $input['parameter_string'] = str_replace('|','&',$input['parameter_string']);

        parse_str($input['parameter_string'],$input);

        $input['bank_signature']=$sig;

        parent::authorize($input);

        if (isset($input['Action_ShoppingMall_Login_Init']) === true)
        {
            $input[RequestFields::ACTION] = 'Y';
            unset($input['Action_ShoppingMall_Login_Init']);
        }

        $this->validatechecksum($string_for_validation,$input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response, 'authorize');

        $checksum_string = http_build_query($response,null,'|');

        $callback_checksum = $this->getHashOfString($checksum_string);

        $response[ResponseFields::CHECKSUM] = $callback_checksum;

        $this->content($response, 'authorize');

        $callbackUrl = $input[RequestFields::RETURN_URL];

        $callbackUrl .= '?bank_signature='.$response[ResponseFields::CHECKSUM] .'&parameter_string='.$checksum_string;

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
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::BANK_TRANSACTION_ID     => 99999999,
            ResponseFields::ITEM_CODE               => $input[RequestFields::ITEM_CODE],
            ResponseFields::PRODUCT_REF_NUMBER      => $input[RequestFields::PRODUCT_REF_NUMBER],
            ResponseFields::PAID                    => Status::YES,
        ];

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

    protected function validatechecksum($string_for_validation, $input)
    {
        $input_hash = $input['bank_signature'];

        $expected_hash=$this->getHashOfString($string_for_validation);

        if (hash_equals($expected_hash, $input_hash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $sig_str = hash_hmac('sha256',$str,$secret);

        return $sig_str;
    }

}