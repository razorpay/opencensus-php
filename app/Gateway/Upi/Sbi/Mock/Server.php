<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    protected $ns;

    public function __construct()
    {
        parent::__construct();

        // TODO: Verify this
        $this->ns = $this->ns ?? __NAMESPACE__;
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $request = $this->decrypt($input);

        $this->validateSbiUpiAuthInput($request);

        $response = $this->getAuthorizeResponseArray($request);

        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($response),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId()
        ];

        $this->content($content, 'authorize');

        return $this->makeResponse($content);
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $response = $this->getAsyncCallbackResponseArray($upiEntity, $payment);

        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($response),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId(),
        ];

        $this->content($content, 'async_callback');

        $response = $this->makeResponse($content);

        return [
            ResponseFields::MESSAGE => $response->content()
        ];
    }

    protected function encrypt(array $response)
    {
        $aes = $this->getGatewayInstance()->getAesCrypto();

        $json = json_encode($response, JSON_FORCE_OBJECT);

        return $aes->encryptString($json);
    }

    protected function decrypt(string $json)
    {
        $array = json_decode($json, true);

        $aes = $this->getGatewayInstance()->getAesCrypto();

        $decryptedString = $aes->decryptString($array[RequestFields::REQUEST_MESSAGE]);

        return json_decode($decryptedString, true);
    }

    protected function getAsyncCallbackResponseArray(array $upiEntity, array $payment)
    {
        $pspRefNo = Payment\Entity::stripDefaultSign($payment[Payment\Entity::ID]);

        $response = [
            ResponseFields::PSP_REFERENCE_NO       => $pspRefNo,
            ResponseFields::UPI_TRANS_REFERENCE_NO => $upiEntity[Entity::GATEWAY_PAYMENT_ID],
            ResponseFields::NPCI_TRANSACTION_ID    => $upiEntity[Entity::NPCI_REFERENCE_ID],
            ResponseFields::CUSTOMER_REFERENCE_NO  => $upiEntity[Entity::CUSTOMER_REFERENCE_ID],
            ResponseFields::AMOUNT                 => $payment[Payment\Entity::AMOUNT],
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::STATUS                 => 'S',
            ResponseFields::STATUS_DESCRIPTION     => 'Payment Successful',
            ResponseFields::ADDITIONAL_INFO        => [],
            ResponseFields::PAYER_VPA              => $payment[Payment\Entity::VPA],
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        return [ResponseFields::API_RESPONSE => $response];
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
            ResponseFields::STATUS                 => 'S',
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