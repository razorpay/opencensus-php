<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use Razorpay\Api\Request;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    public function authorize($input)
    {
        parent::authorize($input);

        $request = $this->decrypt($input);

        $this->validateSbiUpiAuthInput($request);

        $content = $this->getAuthorizeResponseArray($request);

        $this->content($content);

        return $this->makeResponse($content);
    }

    protected function decrypt(string $json)
    {
        $array = json_decode($json, true);

        $aes = $this->getGatewayInstance()->getAesCrypto();

        $decryptedString = $aes->decryptString($array[RequestFields::REQUEST_MESSAGE]);

        return json_decode($decryptedString, true);
    }

    protected function getAuthorizeResponseArray(array $input)
    {
        $content = [
            ResponseFields::PSP_REFERENCE_NO       => $input[RequestFields::REQUEST_INFO][RequestFields::PSP_REFERENCE_NO],
            ResponseFields::UPI_TRANS_REFERENCE_NO => uniqid(), // TODO: Double check this
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999, // TODO: Double check this
            ResponseFields::CUSTOMER_REFERENCE_NO  => 3434343, // TODO: Double check this
            ResponseFields::AMOUNT                 => $input[RequestFields::AMOUNT],
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::STATUS                 => 'P',
            ResponseFields::STATUS_DESCRIPTION     => 'Transaction Pending waiting for response',
            ResponseFields::ADDITIONAL_INFO        => [],
            ResponseFields::PAYER_VPA              => $input[RequestFields::PAYER_TYPE][RequestFields::VIRTUAL_ADDRESS],
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        return [ResponseFields::API_RESPONSE => $content];
    }

    protected function validateSbiUpiAuthInput(array $input)
    {
        $this->validateAuthorizeInput($input);

        $this->validateActionInput($input[RequestFields::ADDITIONAL_INFO], 'auth_additional_info');

        $this->validateActionInput($input[RequestFields::PAYER_TYPE], 'auth_payer_type');

        $this->validateActionInput($input[RequestFields::REQUEST_INFO], 'auth_request_info');
    }
}