<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Sbi\Action;
use RZP\Gateway\Upi\Sbi\Status;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Sbi\RequestFields;
use RZP\Gateway\Upi\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    public function authorize($input)
    {
        parent::authorize($input);

        if (isset($input['grant_type']) === true)
        {
            return $this->getOauthToken();
        }

        $inputArray = json_decode($input, true);

        $request = $this->decrypt($inputArray['requestMsg']);

        $this->validateAuthorizeInput($request);

        $this->request($request, 'authorize');

        $response = $this->getAuthorizeResponseArray($request);

        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($response),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId()
        ];

        $this->content($content, 'authorize');

        return $this->makeResponse($content);
    }

    public function validateVpa($input)
    {
        $request = json_decode($input, true);

        $this->request($this->mockRequest, 'validate_vpa');

        $response = $this->getValidateVpaResponseArray($request);

        return $this->makeResponse($response);
    }

    public function getAsyncCallbackContent($upiEntity)
    {
        $response = $this->getAsyncCallbackResponseArray($upiEntity);

        $response = $this->encryptAndGetCallbackContent($response);

       return [
        ResponseFields::MESSAGE => $response->content(),
        //We don't get this in call back actually, but for test as we dont actually hit mozart
        //hence wont be able to decrypt the response sent from here.
        'payment_id' => $upiEntity[Entity::PAYMENT_ID],
        'vpa' => $upiEntity['vpa'],
    ];
    }

    public function getUnexpectedAsyncCallbackContent(string $status, $requiredData = [])
    {
        $response = $this->getUnexpectedAsyncCallbackResponseArray($status, $requiredData);

        $paymentId =  $response['apiResp']['pspRefNo'];
        $vpa = $response['apiResp']['payerVPA'];

        $response = $this->encryptAndGetCallbackContent($response);

        return [
            ResponseFields::MESSAGE => $response->content(),
            //We don't get this in call back actually, but for test as we dont actually hit mozart
            //hence wont be able to decrypt the response sent from here.
            'payment_id' => $upiEntity[Entity::PAYMENT_ID] ?? $paymentId,
            'vpa' => $vpa,
        ];
    }

    protected function encryptAndGetCallbackContent($response)
    {
        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($response),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId(),
        ];

        $response = $this->makeResponse($content);

        return $response;
    }

    public function verify($input)
    {
        parent::verify($input);

        if (isset($input['grant_type']) === true)
        {
            return $this->getOauthToken();
        }

        $input = $this->decrypt(json_decode($input, true)['requestMsg']);

        $this->validateActionInput($input, 'verify');

        $content = $this->getVerifyResponseContent($input);

        $this->content($content, 'verify');

        $content = [
            ResponseFields::RESPONSE       => $this->encrypt($content),
            ResponseFields::PG_MERCHANT_ID => $this->getGatewayInstance()->getMerchantId()
        ];

        return $this->makeResponse($content);
    }

    protected function encrypt(array $content)
    {
        $gateway = $this->getGatewayInstance();

        $json = json_encode($content);

        $hash = $gateway->generateHash($json);

        $pgp = $gateway->getPgpInstance();

        $encryptedHash = $pgp->encrypt($hash);

        $contentWithHash = $encryptedHash . '|' . $json;

        return base64_encode($pgp->encryptSign($contentWithHash));
    }

    public function decryptInput($entities){
        $callbackData = json_decode($entities['gateway']['payload']['msg'], true);

        $encrypted = $callbackData['resp'];

        return $this->decrypt($encrypted);
    }

    protected function decrypt($encrypted)
    {
        $gateway = $this->getGatewayInstance();

        $pgp = $gateway->getPgpInstance();

        $decryptedString = $pgp->decryptVerify(base64_decode($encrypted));

        $encHashResponsePair = explode('|', $decryptedString);

        $hash = $pgp->decrypt($encHashResponsePair[0]);

        $gateway->verifyHash($encHashResponsePair[1], $hash);

        return json_decode($encHashResponsePair[1], true);
    }

    private function getValidateVpaResponseArray(array $input)
    {
        $input = $input["entities"];

        $paymentId = $input['payment']['id'];

        $vpa = $input['payment']['vpa'];

        $response = [
            ResponseFields::REQUEST_INFO => [
                ResponseFields::PSP_REFERENCE_NO   => $paymentId,
            ],
            ResponseFields::PAYEE_TYPE         => [
                ResponseFields::VIRTUAL_ADDRESS => $vpa,
                ResponseFields::NAME            => 'Test User',
            ],
            ResponseFields::STATUS             => Status::AVAILABLE_VPA,
            ResponseFields::STATUS_DESCRIPTION => 'VPA is valid',
            'success' => true,
        ];

        if ($vpa === 'failedvalidate@sbi')
        {
            $response[ResponseFields::STATUS] = Status::UNAVAILABLE_VPA;
            $response[ResponseFields::STATUS_DESCRIPTION] = 'VPA is invalid';
        }

        if ($vpa === 'exception@sbi')
        {
            $response[ResponseFields::STATUS] = 'T';
            $response[ResponseFields::STATUS_DESCRIPTION] = 'Timeout';
        }

        if ($vpa === 'timeout@sbi')
        {
            $response['success'] = false;
        }

        $response["data"]["gateway_response"] = $response;

        return $response;
    }

    private function getVerifyResponseContent(array $input)
    {
        $paymentId = $input[RequestFields::REQUEST_INFO][RequestFields::PSP_REFERENCE_NO];

        $gatewayPayment = $this->getRepo()->findByPaymentId($paymentId)->first();

        $response = [
            ResponseFields::PSP_REFERENCE_NO       => $paymentId,
            ResponseFields::UPI_TRANS_REFERENCE_NO => 99999,
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => '99999999999',
            ResponseFields::AMOUNT                 => $gatewayPayment->getAmount() / 100,
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::RESPONSE_CODE          => '00',
            ResponseFields::APPROVAL_NUMBER        => random_int(100000, 999999),
            ResponseFields::STATUS                 => Status::SUCCESS,
            ResponseFields::STATUS_DESCRIPTION     => 'Payment Successful',
            ResponseFields::ADDITIONAL_INFO        => [
                ResponseFields::ADDITIONAL_INFO2   => '7971807546',
                ResponseFields::STATUS_DESCRIPTION => 'status description in addInfo not expected from gateway, but we
                                                       still need to remove before making database call, because our
                                                       poor database can only take 255 characters and gateway can still
                                                       send a very large data in addInfo, Off course same applies
                                                       for addInfo2, but since this contract is different story we are
                                                       fine with db failure'
            ],
            ResponseFields::PAYER_VPA              => $gatewayPayment->getVpa(),
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        return [ResponseFields::API_RESPONSE => $response];
    }

    private function getAsyncCallbackResponseArray(array $upiEntity)
    {
        $response = [
            ResponseFields::PSP_REFERENCE_NO       => $upiEntity[Entity::PAYMENT_ID],
            ResponseFields::UPI_TRANS_REFERENCE_NO => 12345678901,
            ResponseFields::NPCI_TRANSACTION_ID    => 999999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => '123456789012',
            ResponseFields::AMOUNT                 => $upiEntity[Entity::AMOUNT] / 100,
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::RESPONSE_CODE          => '00',
            ResponseFields::APPROVAL_NUMBER        => random_int(100000, 999999),
            ResponseFields::STATUS                 => Status::SUCCESS,
            ResponseFields::STATUS_DESCRIPTION     => 'Payment Successful',
            ResponseFields::ADDITIONAL_INFO        => [
                ResponseFields::ADDITIONAL_INFO2   => '7971807546',
                ResponseFields::STATUS_DESCRIPTION => 'status description in addInfo not expected from gateway, but we
                                                       still need to remove before making database call, because our
                                                       poor database can only take 255 characters and gateway can still
                                                       send a very large data in addInfo, Off course same applies
                                                       for addInfo2, but since this contract is different story we are
                                                       fine with db failure'
            ],
            ResponseFields::PAYER_VPA              => $upiEntity[Entity::VPA],
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        if ($upiEntity[Entity::VPA] === 'rejectedcollect@sbi')
        {
            $response[ResponseFields::STATUS] = Status::REJECTED;
            $response[ResponseFields::STATUS_DESCRIPTION] = 'Collect request rejected';
        }
        else if ($upiEntity[Entity::VPA] === 'cbsdown@sbi')
        {
            $response[ResponseFields::STATUS] = Status::CBS_DOWN;
            $response[ResponseFields::STATUS_DESCRIPTION] = 'CBS transaction processing timed out';
        }

        return [ResponseFields::API_RESPONSE => $response];
    }

    private function getUnexpectedAsyncCallbackResponseArray($status, $requiredData)
    {
        $response = [
            ResponseFields::PSP_REFERENCE_NO       => str_random(12),
            ResponseFields::UPI_TRANS_REFERENCE_NO => 99999,
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => '99999999999',
            ResponseFields::AMOUNT                 => 2500,
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::RESPONSE_CODE          => '00',
            ResponseFields::APPROVAL_NUMBER        => random_int(100000, 999999),
            ResponseFields::STATUS                 => Status::SUCCESS,
            ResponseFields::STATUS_DESCRIPTION     => 'Payment Successful',
            ResponseFields::ADDITIONAL_INFO        => [
                ResponseFields::ADDITIONAL_INFO2   => '7971807546',
                ResponseFields::STATUS_DESCRIPTION => 'status description in addInfo not expected from gateway, but we
                                                       still need to remove before making database call, because our
                                                       poor database can only take 255 characters and gateway can still
                                                       send a very large data in addInfo, Off course same applies
                                                       for addInfo2, but since this contract is different story we are
                                                       fine with db failure'
            ],
            ResponseFields::PAYER_VPA              => $status === 'success' ? 'success@sbi' : 'failedverify@sbi',
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        foreach ($requiredData as $key => $value)
        {
            $response[$key] = $value;
        }

        return [ResponseFields::API_RESPONSE => $response];
    }

    private function getAuthorizeResponseArray(array $input)
    {
        $vpa = $input[RequestFields::PAYER_TYPE][RequestFields::VIRTUAL_ADDRESS];

        $content = [
            ResponseFields::PSP_REFERENCE_NO       => $input[RequestFields::REQUEST_INFO][RequestFields::PSP_REFERENCE_NO],
            ResponseFields::UPI_TRANS_REFERENCE_NO => 99999,
            ResponseFields::NPCI_TRANSACTION_ID    => 99999999999,
            ResponseFields::CUSTOMER_REFERENCE_NO  => '99999999999',
            ResponseFields::AMOUNT                 => $input[RequestFields::AMOUNT],
            ResponseFields::TRANSACTION_AUTH_DATE  => Carbon::now(Timezone::IST)->toDateTimeString(),
            ResponseFields::STATUS                 => Status::SUCCESS,
            ResponseFields::STATUS_DESCRIPTION     => 'Transaction Pending waiting for response',
            ResponseFields::ADDITIONAL_INFO        => [],
            ResponseFields::PAYER_VPA              => $vpa,
            ResponseFields::PAYEE_VPA              => self::DEFAULT_PAYEE_VPA,
        ];

        if ($vpa === 'failedcollect@sbi')
        {
            $content[ResponseFields::STATUS] = Status::FAILED;
            $content[ResponseFields::STATUS_DESCRIPTION] = 'Payment failed';
        }
        else if ($vpa === 'cbsdown@sbi')
        {
            $content[ResponseFields::STATUS] = Status::CBS_DOWN;
            $content[ResponseFields::STATUS_DESCRIPTION] = 'CBS transaction processing timed out';
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

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getOauthToken()
    {
        $content = [
            'access_token'  => 'test_token',
            'token_type'    => 'bearer',
            'refresh_token' => 'test_refresh',
            'expires_in'    => 179,
        ];

        $this->content($content, 'oauth');

        return $this->makeJsonResponse($content);
    }
}
