<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    protected $ns;

    protected $repo;

    public function __construct()
    {
        parent::__construct();

        // TODO: Verify this
        $this->ns = $this->ns ?? __NAMESPACE__;

        $this->repo = $this->app['repo']->upi;
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

    public function verify($input)
    {
        parent::verify($input);

        $input = $this->decrypt($input);

        $this->validateActionInput($input, 'verify');

        $this->validateActionInput($input[RequestFields::REQUEST_INFO], 'verify_request_info');

        // TODO: Ensure that this is correct
        $content = $this->getVerifyResponseContent($input);

        $this->content($content, 'verify');

        // TODO: Double check this
        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($content),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId()
        ];

        return $this->makeResponse($content);
    }

    public function refund($input)
    {
        parent::refund($input);

        $input = $this->decrypt($input);

        $this->validateSbiUpiRefundInput($input);

        $content = $this->getRefundResponseContent($input);

        $this->content($content, 'refund');

        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($content),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId()
        ];

        return $this->makeResponse($content);
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

    protected function getVerifyResponseContent(array $input)
    {
        $paymentId = $input[RequestFields::REQUEST_INFO][RequestFields::PSP_REFERENCE_NO];

        $gatewayPayment = $this->repo->findByPaymentId($paymentId)->first();

        $response = [
            ResponseFields::PSP_REFERENCE_NO       => $paymentId,
            ResponseFields::UPI_TRANS_REFERENCE_NO => $gatewayPayment->getGatewayPaymentId(),
            ResponseFields::NPCI_TRANSACTION_ID    => $gatewayPayment->getNpciReferenceId(),
            ResponseFields::CUSTOMER_REFERENCE_NO  => $gatewayPayment->getCustomerReferenceId(),
            ResponseFields::AMOUNT                 => $gatewayPayment->getAmount(),
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::RESPONSE_CODE          => '00',
            ResponseFields::APPROVAL_NUMBER        => random_int(100000, 999999),
            ResponseFields::STATUS                 => 'S',
            ResponseFields::STATUS_DESCRIPTION     => 'Payment Successful',
            ResponseFields::ADDITIONAL_INFO        => [],
            ResponseFields::PAYER_VPA              => $gatewayPayment->getVpa(),
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        return [ResponseFields::API_RESPONSE => $response];
    }

    protected function getRefundResponseContent(array $input)
    {
        // TODO: Ensure all the variables below are correct

        $paymentId = $input[RequestFields::REFUND_TRANSACTION_DETAIL][RequestFields::ORG_ORDER_NUMBER];

        $gatewayPayment = $this->repo->findByPaymentId($paymentId)->first();

        $refundTransactionDetails = $input[RequestFields::REFUND_TRANSACTION_DETAIL];

        $response = [
            ResponseFields::REQUEST_INFO              => $input[RequestFields::REQUEST_INFO],
            ResponseFields::REFUND_STATUS             => 'S',
            ResponseFields::REFUND_DATE               => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::RESPONSE_CODE             => '00',
            ResponseFields::APPROVAL_NUMBER           => random_int(100000, 999999),
            ResponseFields::CUSTOMER_REFERENCE_NO     => $gatewayPayment->getCustomerReferenceId(),
            ResponseFields::REFUND_TRANSACTION_DETAIL => [
                ResponseFields::ORDER_NUMBER               => $refundTransactionDetails[RequestFields::ORDER_NUMBER],
                ResponseFields::ORG_ORDER_NUMBER           => $refundTransactionDetails[RequestFields::ORG_ORDER_NUMBER],
                ResponseFields::ORG_CUSTOMER_REF_NUMBER    => $refundTransactionDetails[RequestFields::ORG_CUSTOMER_REF_NUMBER],
                ResponseFields::ORG_TRANSACTION_REF_NUMBER => $refundTransactionDetails[RequestFields::ORG_TRANSACTION_REF_NUMBER],
                ResponseFields::TRANSACTION_REMARKS        => $refundTransactionDetails[RequestFields::TRANSACTION_REMARKS],
                ResponseFields::CURRENCY_CODE              => $refundTransactionDetails[ResponseFields::CURRENCY_CODE],
                ResponseFields::PAYMENT_TYPE               => $refundTransactionDetails[ResponseFields::PAYMENT_TYPE],
                ResponseFields::NPCI_TRANSACTION_ID_LC     => $gatewayPayment->getNpciReferenceId(),
                ResponseFields::PAYER_INFO                 => [
                    ResponseFields::VIRTUAL_ADDRESS => $gatewayPayment->getVpa(),
                    ResponseFields::NAME            => 'Merchant name', // TODO: work on this
                    ResponseFields::ACCOUNT_NUMBER  => 12121121, // TODO: Work on this
                    ResponseFields::IFSC_CODE       => 'SBIN000000013', // TODO: Work on this
                ],
                ResponseFields::PAYEE_INFO                 => [
                    ResponseFields::VIRTUAL_ADDRESS => self::DEFAULT_PAYEE_VPA,
                ],
            ],
            ResponseFields::ADDITIONAL_INFO           => $input[RequestFields::ADDITIONAL_INFO],
        ];

        return [ResponseFields::API_RESPONSE => $response];
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
            ResponseFields::RESPONSE_CODE          => '00',
            ResponseFields::APPROVAL_NUMBER        => random_int(100000, 999999),
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
            ResponseFields::UPI_TRANS_REFERENCE_NO => random_int(100000, 999999),
            ResponseFields::NPCI_TRANSACTION_ID    => random_int(100000000000, 999999999999),
            ResponseFields::CUSTOMER_REFERENCE_NO  => random_int(100000000000, 999999999999),
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

    protected function validateSbiUpiRefundInput(array $input)
    {
        $this->validateRefundInput($input);

        $this->validateActionInput($input[RequestFields::ADDITIONAL_INFO], 'refund_additional_info');

        $this->validateActionInput($input[RequestFields::REFUND_TRANSACTION_DETAIL], 'refund_txn_detail');

        $this->validateActionInput($input[RequestFields::REQUEST_INFO], 'refund_request_info');
    }
}