<?php

namespace RZP\Gateway\Netbanking\Corporation;

use phpseclib\Crypt\AES;

use RZP\Constants\Environment;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Payment\Action;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_corporation';

    protected $bank = 'corporation';

    protected $map = [
        ResponseFields::CUSTOMER_ID      => NetbankingEntity::CUSTOMER_ID,
        ResponseFields::MERCHANT_CODE    => NetbankingEntity::MERCHANT_CODE,
        ResponseFields::AMOUNT           => NetbankingEntity::AMOUNT,
        ResponseFields::BANK_REF_NUMBER  => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::PAYMENT_ID       => NetbankingEntity::PAYMENT_ID,
        ResponseFields::STATUS           => NetbankingEntity::STATUS,
        NetbankingEntity::RECEIVED       => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestDataAndCreateGatewayPayment($input);

        $request = $this->getStandardRequestArray($content, 'get');

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        // For those payments whose amounts are integer values,
        // the amount in the callback is rounded off to 1 decimal place.
        // For those with amount having 1 or 2 decimal places, it is kept as it is.
        // Hence, we need to format the amount in the callback as well before making
        // the amount assertion
        $this->assertAmount(
            $this->formatAmount($input['payment']['amount'] / 100),
            $this->formatAmount($content[ResponseFields::AMOUNT])
        );

        $this->checkCallbackStatus($content);

        $this->verifyCallback($input, $content);

        // Saving callback response only if the verification passes
        $gatewayPayment = $this->saveCallbackResponse($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // -------------------------- Auth helper methods ------------------------------

    protected function getAuthRequestDataAndCreateGatewayPayment($input)
    {
        $merchantName = $input['merchant']->getBillingLabel();

        $data = [
            // Setting this as the merchant code shared with us
            RequestFields::MERCHANT_CODE        => $this->getMerchantId(),
            RequestFields::CUSTOMER_ID          => $merchantName,
            RequestFields::AMOUNT               => $this->formatAmount($input['payment']['amount']),
            RequestFields::PAYMENT_ID           => $input['payment']['id'],
            RequestFields::MODE_OF_TRANSACTION  => Constants::MODE_OF_TRANSACTION_PAYMENT,
            RequestFields::FUND_TRANSFER        => Constants::FUND_TRANSFER,
        ];

        if ($input['merchant']->isTPVRequired())
        {
            $data[RequestFields::ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $traceRequestData = $data;

        unset($traceRequestData[RequestFields::ACCOUNT_NUMBER]);

        $this->traceGatewayPaymentRequest(
            [
                'before_encryption' => $traceRequestData,
                'payment_id'        => $input['payment']['id'],
                'gateway'           => $this->gateway,
            ],
            $input
        );

        //
        // Create gateway payment entity here, since the unencrypted data(base on which
        // we create the gateway payment entity) won't be available outside this function
        //
        $this->createGatewayPaymentEntity($data);

        $encrypted = $this->getEncryptor()->encryptData($data, '=', '&');

        $result = [
            RequestFields::MERCHANT_CODE => $this->getMerchantId(),
            RequestFields::QUERY_STRING  => $encrypted,
        ];

        return $result;
    }

    // -------------------------- Auth helper methods end --------------------------

    // -------------------------- Callback helper methods ------------------------------

    protected function checkCallbackStatus(array $content)
    {
        if ($content[ResponseFields::STATUS] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse($content)
    {
        $content[NetbankingEntity::RECEIVED] = true;

        $gatewayPayment = $this->getRepository()->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::PAYMENT_ID],
                                    Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    // -------------------------- Callback helper methods end --------------------------

    // -------------------------- Verify helper methods ------------------------------

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequest($verify);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'decrypted'  => $verify->verifyResponseContent,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyResponse($verify);
    }

    protected function saveVerifyResponse(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::VERIFY_RESULT];
        }

        if ((isset($content[ResponseFields::VERIFY_BANK_REF_NUMBER]) === true) and
            (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true))
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::VERIFY_BANK_REF_NUMBER];
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus()
    {
        return ResponseCodeMap::SUCCESS_CODE;
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     */
    protected function verifyCallback(array $input, array $callbackContent)
    {
        $verify = new Verify($this->gateway, $input);

        $this->getPaymentToVerify($verify);

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        // We set apiSuccess based on the response from the callback,
        // since we can't check the same from the payment status.
        $apiSuccess = ($callbackContent[ResponseFields::STATUS] === ResponseCodeMap::SUCCESS_CODE);

        if ($verify->gatewaySuccess !== $apiSuccess)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED);
        }
    }

    protected function parseVerifyResponse($content)
    {
        return $this->getEncryptor()->decryptAndFormatData($content);
    }

    /**
     * @param Verify $verify
     * @return array
     * @throws Exception\LogicException
     */
    protected function getVerifyRequest(Verify $verify)
    {
        $input = $verify->input;

        if ($this->action === Action::VERIFY)
        {
            $gatewayPayment = $verify->payment;

            $bankRefNumber = $gatewayPayment['bank_payment_id'] ?? '';
        }
        elseif ($this->action === Action::CALLBACK)
        {
            $bankRefNumber = $input['gateway'][ResponseFields::BANK_REF_NUMBER];
        }
        else
        {
            throw new Exception\LogicException('Verify should be called from either verify or callback actions');
        }

        $data = [
            RequestFields::VERIFY_MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::VERIFY_PAYMENT_ID            => $input['payment']['id'],
            RequestFields::VERIFY_AMOUNT                => $this->formatAmount($input['payment']['amount']),
            RequestFields::VERIFY_BANK_REF_NUMBER       => $bankRefNumber,
            RequestFields::VERIFY_MODE_OF_TRANSACTION   => RequestFields::VERIFY_MODE_OF_TRANSACTION_VALUE,
            RequestFields::FUND_TRANSFER                => Constants::FUND_TRANSFER,
        ];

        $encryptedString = $this->getEncryptor()->encryptData($data);

        $content = [
            RequestFields::VERIFY_MERCHANT_CODE => $this->getMerchantId(),
            RequestFields::VERIFY_DATA          => $encryptedString,
        ];

        $request = $this->getStandardRequestArray($content, 'get', Action::VERIFY);

        // Since they don't have a valid SSL certificate on UAT site.
        if ($this->mode === Mode::TEST)
        {
            $request['options']['verify'] = false;
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'           => $this->gateway,
                'request'           => $request,
                'payment_id'        => $input['payment']['id'],
                'decrypted_content' => $data,
            ]
        );

        return $request;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::VERIFY_RESULT]) === true) and
            ($content[ResponseFields::VERIFY_RESULT] === ResponseCodeMap::RESULT_SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    // -------------------------- Verify helper methods end --------------------------

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return back.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === ResponseCodeMap::SUCCESS_CODE))
        {
            return true;
        }

        if (empty($input['gateway']['gateway_payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                null,
                $input);
        }

        $attributes = [
            Base\Entity::STATUS          => ResponseCodeMap::SUCCESS_CODE,
            Base\Entity::BANK_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
        ];

        $gatewayPayment->fill($attributes);
        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    // -------------------------- General helper methods --------------------------

    public function getEncryptor($secret = null)
    {
        if ($secret === null)
        {
            $secret = $this->getSecret();
        }

        return new Encryptor(AES::MODE_CBC, $secret, $secret);
    }

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    public function preProcessServerCallback($encryptedData): array
    {
        $this->trace->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $encryptedData
            ]
        );

        $secret = $this->getTestSecret();

        if ($this->app->environment() === Environment::PRODUCTION)
        {
            $secret = $this->getLiveSecret();
        }

        $data = $this->getEncryptor($secret)->decryptAndFormatData($encryptedData, '=', '&');

        return $data;
    }

    public function getPaymentIdFromServerCallback($data)
    {
        return $data[ResponseFields::PAYMENT_ID];
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    /**
     * Since $this->mode is not set when calling preProcessServerCallback
     * we can not assert the mode to test here.
     */
    protected function getTestSecret()
    {
        return $this->config['test_hash_secret'];
    }

    // -------------------------- General helper methods end ----------------------
}
