<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Pnb\Status;
use RZP\Gateway\Netbanking\Pnb\RequestFields;
use RZP\Gateway\Netbanking\Pnb\ResponseFields;
use RZP\Trace\TraceCode;

class Server extends Base\Mock\Server
{
    const MOCK_TRANSACTION_ID = 99999999;
    const SUCCESS_STATUS_DESCRIPTION = 'SUCCESS';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[RequestFields::ENCDATA]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $this->validateActionInput($decryptedData);

        $this->verifyHash($decryptedData);

        $callbackDataArray = $this->getCallbackResponseData($decryptedData);

        $this->content($callbackDataArray, 'authorize');

        $callbackDataArray[ResponseFields::CHECKSUM] = $this->generateHash($callbackDataArray);

        $response = $this->getEncryptedData($callbackDataArray);

        $request = [
            'url'     => $decryptedData[RequestFields::RETURN_URL],
            'content' => [
                RequestFields::ENCDATA => $response
            ],
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[RequestFields::ENCDATA]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $this->validateActionInput($decryptedData);

        $callbackDataArray = $this->getVerifyResponseData($decryptedData);

        $this->content($callbackDataArray, 'verify');

        $callbackDataArray[ResponseFields::CHECKSUM] = $this->generateHash($callbackDataArray);

        $encdata = $this->getEncryptedData($callbackDataArray);

        $html = $this->prepareVerifyResponseHtml($encdata);

        return $this->prepareResponse($html);
    }

    protected function getVerifyResponseData(array $input)
    {
        $date = Carbon::createFromFormat('dmY-His', $input[RequestFields::MERCHANT_DATE], Timezone::IST)->format('d-m-Y');

        $data = [
            ResponseFields::CHALLAN_NUMBER_VERIFY      => $input[RequestFields::CHALLAN_NUMBER],
            ResponseFields::BANK_TRANSACTION_ID_VERIFY => self::MOCK_TRANSACTION_ID,
            ResponseFields::BANK_PAYMENT_DATE_VERIFY   => $date,
            ResponseFields::BANK_AMOUNT_PAID_VERIFY    => $input[RequestFields::MERCHANT_AMOUNT],
            ResponseFields::BANK_PAYMENT_STATUS_VERIFY => Status::SUCCESS,
            ResponseFields::STATUS_DESCRIPTON          => self::SUCCESS_STATUS_DESCRIPTION,
            ResponseFields::ITEM_CODE                  => $input[RequestFields::ITEM_CODE],
        ];

        return $data;
    }

    protected function getCallbackResponseData(array $input)
    {
        $date = Carbon::createFromFormat('dmY-His', $input[RequestFields::MERCHANT_DATE], Timezone::IST)->format('d-m-Y');

        $data = [
            ResponseFields::CHALLAN_NUMBER      => $input[RequestFields::CHALLAN_NUMBER],
            ResponseFields::BANK_TRANSACTION_ID => self::MOCK_TRANSACTION_ID,
            ResponseFields::BANK_PAYMENT_DATE   => $date,
            ResponseFields::BANK_AMOUNT_PAID    => $input[RequestFields::MERCHANT_AMOUNT],
            ResponseFields::BANK_PAYMENT_STATUS => Status::SUCCESS,
            ResponseFields::STATUS_DESCRIPTON   => self::SUCCESS_STATUS_DESCRIPTION,
            ResponseFields::ITEM_CODE           => $input[ResponseFields::ITEM_CODE],
        ];

        return $data;
    }

    protected function verifyHash($decryptedData)
    {
        $actual = $decryptedData[ResponseFields::CHECKSUM];

        unset($decryptedData[ResponseFields::CHECKSUM]);

        $generated = $this->generateHash($decryptedData);

        if (hash_equals($actual, $generated) === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_CHECKSUM_VERIFY_FAILED,
                [
                    'actual'    => $actual,
                    'generated' => $generated
                ]);

            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function getEncryptedData(array $data)
    {
        $dataString = http_build_query($data, null, '|');

        $encryptedString = $this->getGatewayInstance()
            ->encryptString($dataString);

        return $encryptedString;
    }

    protected function getDecryptedData(string $decryptedString): array
    {
        $decryptedString = str_replace('|', '&', $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

    protected function prepareVerifyResponseHtml($content)
    {
        ob_start();

        require ('VerifyResponseHtml.php');

        $html = ob_get_clean();

        $html = str_replace("{{encdata}}", $content, $html);

        return $html;
    }

    protected function prepareResponse($html)
    {
        $response = \Response::make($html);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
