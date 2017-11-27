<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use Razorpay\Api\Request;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    public function authorize($input)
    {
        parent::authorize($input);

        $request = $this->decrypt($input);

        $this->validateAuthorizeInput($request);

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

        $content = $this->getVerifyResponseContent($input);

        $this->content($content, 'verify');

        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($content),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId()
        ];

        return $this->makeResponse($content);
    }

    public function encrypt(array $response)
    {
        $aes = $this->getGatewayInstance()->getAesCrypto();

        $json = json_encode($response, JSON_FORCE_OBJECT);

        return $aes->encryptString($json);
    }

    public function decrypt(string $json, $messageKey = RequestFields::REQUEST_MESSAGE)
    {
        $array = json_decode($json, true);

        $aes = $this->getGatewayInstance()->getAesCrypto();

        $decryptedString = $aes->decryptString($array[$messageKey]);

        return json_decode($decryptedString, true);
    }

    protected function getVerifyResponseContent(array $input)
    {
        $paymentId = $input[RequestFields::REQUEST_INFO][RequestFields::PSP_REFERENCE_NO];

        $gatewayPayment = $this->getRepo()->findByPaymentId($paymentId)->first();

        $response = [
            ResponseFields::PSP_REFERENCE_NO       => $paymentId,
            ResponseFields::UPI_TRANS_REFERENCE_NO => $gatewayPayment->getGatewayPaymentId(),
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => $gatewayPayment->getGatewayPaymentId(),
            ResponseFields::AMOUNT                 => $gatewayPayment->getAmount() / 100,
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

    protected function getAsyncCallbackResponseArray(array $upiEntity, array $payment)
    {
        $pspRefNo = Payment\Entity::stripDefaultSign($payment[Payment\Entity::ID]);

        $vpa = $payment[Payment\Entity::VPA];

        $response = [
            ResponseFields::PSP_REFERENCE_NO       => $pspRefNo,
            ResponseFields::UPI_TRANS_REFERENCE_NO => $upiEntity[Entity::NPCI_REFERENCE_ID],
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => $upiEntity[Entity::GATEWAY_PAYMENT_ID],
            ResponseFields::AMOUNT                 => $payment[Payment\Entity::AMOUNT] / 100,
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::RESPONSE_CODE          => '00',
            ResponseFields::APPROVAL_NUMBER        => random_int(100000, 999999),
            ResponseFields::STATUS                 => 'S',
            ResponseFields::STATUS_DESCRIPTION     => 'Payment Successful',
            ResponseFields::ADDITIONAL_INFO        => [],
            ResponseFields::PAYER_VPA              => $vpa,
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        if ($vpa === 'rejectedcollect@sbi')
        {
            $response[ResponseFields::STATUS] = 'R';
            $response[ResponseFields::STATUS_DESCRIPTION] = 'Collect request rejected';
        }

        return [ResponseFields::API_RESPONSE => $response];
    }

    protected function getAuthorizeResponseArray(array $input)
    {
        $vpa = $input[RequestFields::PAYER_TYPE][RequestFields::VIRTUAL_ADDRESS];

        $content = [
            ResponseFields::PSP_REFERENCE_NO       => $input[RequestFields::REQUEST_INFO][RequestFields::PSP_REFERENCE_NO],
            ResponseFields::UPI_TRANS_REFERENCE_NO => random_int(100000, 999999),
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => random_int(100000000000, 999999999999),
            ResponseFields::AMOUNT                 => $input[RequestFields::AMOUNT],
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::STATUS                 => 'S',
            ResponseFields::STATUS_DESCRIPTION     => 'Transaction Pending waiting for response',
            ResponseFields::ADDITIONAL_INFO        => [],
            ResponseFields::PAYER_VPA              => $vpa,
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        if ($vpa === 'failedcollect@sbi')
        {
            $content[ResponseFields::STATUS] = 'F';
            $content[ResponseFields::STATUS_DESCRIPTION] = 'Payment failed';
        }

        $this->content($content, 'auth_decrypted');

        return [ResponseFields::API_RESPONSE => $content];
    }

    /**
     * @override
     * @return string
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @override
     * @return mixed
     */
    protected function getRepo()
    {
        $class = 'RZP\Gateway\Upi\Base\Repository';

        return new $class;
    }
}
