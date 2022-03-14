<?php

namespace RZP\Gateway\Netbanking\Bob;

use phpseclib\Crypt\AES;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Entity as GatewayEntity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_bob';

    protected $bank = 'bob';

    protected $map = [
        RequestFields::BANK_ID                  => NetbankingEntity::MERCHANT_CODE,
        RequestFields::AMOUNT                   => NetbankingEntity::AMOUNT,
        ResponseFields::STATUS                  => NetbankingEntity::STATUS,
        ResponseFields::BANK_REF_NUMBER         => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::CUSTOMER_ACCOUNT_NUMBER => NetbankingEntity::ACCOUNT_NUMBER,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $contentToSave = $this->getContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getCallbackContent($input);

        $traceContent = $content;

        unset($traceContent[ResponseFields::CUSTOMER_ACCOUNT_NUMBER]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'gateway_response'   => $input['gateway'],
                'decrypted_response' => $traceContent,
                'payment_id'         => $input['payment']['id']
            ]
        );

        $this->assertPaymentId($content[ResponseFields::PAYMENT_ID], $input['payment']['id']);

        $this->assertAmount($this->formatAmount($input['payment']['amount']), $content[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->saveCallbackResponse($content, $input);

        $this->checkCallbackStatus($content);

        $this->verifyCallback($input, $gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkVerifyGatewaySuccess($verify);

        //
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // -------------------- Auth helper methods-------------------------

    protected function getAuthorizeRequest($input): array
    {
        $payment = $input['payment'];

        $customerType = (($payment[Payment::BANK] === Netbanking::BARB_R) ?
                            Constants::CUSTOMER_TYPE_RETAIL :
                            Constants::CUSTOMER_TYPE_CORPORATE);
        $content = [
            RequestFields::BANK_ID          => Constants::BANK_ID,
            RequestFields::BANK_FIXED_VALUE => $this->getMerchantId(),
            RequestFields::BILLER_NAME      => Constants::BILLER_NAME,
            RequestFields::AMOUNT           => $this->formatAmount($payment[Payment::AMOUNT]),
            // When a user cancels the payment, they seem to be sending the data
            // via URL params and without adding the '?' separator
            RequestFields::CALLBACK_URL     => $input['callbackUrl'] . '?',
            RequestFields::PAYMENT_ID       => $payment[Payment::ID],
        ];

        $encryptedData = $this->getEncryptor()->encryptData($content);

        $requestData = [
            RequestFields::ENCRYPTED_DATA => $encryptedData,
            RequestFields::CUSTOMER_TYPE  => $customerType,
        ];

        // Since live mode relative URL is different, we set the type of URL to AUTHORIZE_LIVE
        // for LIVE mode and null(which is the default argument passed as type) for test mode
        $type = (($this->mode === Mode::LIVE) ? (strtoupper($this->action . '_' . $this->mode)) : null);

        $request = $this->getStandardRequestArray($requestData, 'post', $type);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'    => $this->gateway,
                'content'    => $content,
                'payment_id' => $payment[Payment::ID],
                'request'    => $request
            ]);

        return $request;
    }

    protected function getContentToSave($payment): array
    {
        return [
            RequestFields::BANK_ID     => $this->getMerchantId(),
            RequestFields::AMOUNT      => $payment[Payment::AMOUNT]
        ];
    }

    // -------------------- Auth helper methods end----------------------

    // -------------------- Callback helper methods----------------------

    protected function getCallbackContent(array $input): array
    {
        // Quickfix for the case where they send the data over query params
        // when the user cancels the payment
        if (isset($input['gateway'][RequestFields::ENCRYPTED_DATA]) === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_CALLBACK,
                [
                    'gateway'            => $this->gateway,
                    'gateway_response'   => $input['gateway'],
                    'payment_id'         => $input['payment']['id']
                ]
            );

            throw new Exception\GatewayErrorException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $encryptedData = $input['gateway'][RequestFields::ENCRYPTED_DATA];

        $content = $this->getEncryptor()->decryptData($encryptedData);

        return $content;
    }

    protected function checkCallbackStatus(array $content)
    {
        if ($this->isGatewaySuccess($content) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse(array $content, array $input)
    {
        if (isset($content[ResponseFields::BANK_REF_NUMBER]) === true)
        {
            $content[ResponseFields::BANK_REF_NUMBER] = trim($content[ResponseFields::BANK_REF_NUMBER]);
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    // -------------------- Callback helper methods end -----------------

    // -------------------- Verify helper methods -----------------------

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content, 'get');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response'   => $response->body,
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $content = $this->parseVerifyResponse($response->body);

        $verify->verifyResponseContent = $this->getMappedAttributes($content);
    }

    protected function verifyPayment($verify)
    {
        $verify->status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkVerifyGatewaySuccess($verify);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        // Their verify response does not have amount. So, we set it to false without the check.
        $verify->amountMismatch = false;

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);
    }

    protected function getVerifyRequestData($verify): array
    {
        $content = [
            RequestFields::PAYMENT_ID => $verify->input['payment']['id']
        ];

        return $content;
    }

    /**
     * @param string $body Body of the response
     * @return array
     */
    protected function parseVerifyResponse(string $body): array
    {
        $content = [];

        if ((strlen($body) === 1) and ($body === 'F'))
        {
            $content[ResponseFields::STATUS] = $body;
        }
        else
        {
            $pairs = explode(Constants::VERIFY_PAIR_SEPARATOR, $body);

            foreach ($pairs as $value)
            {
                $pair = explode(Constants::VERIFY_KEY_VALUE_SEPARATOR, $value, 2);

                $content[$pair[0]] = $pair[1];
            }
        }

        return $content;
    }

    protected function checkVerifyGatewaySuccess($verify)
    {
        // Initially assume gatewaySuccess is false
        $verify->gatewaySuccess = false;

        if (Status::isSuccess($verify->verifyResponseContent[NetbankingEntity::STATUS]) === true)
        {
            $verify->gatewaySuccess = true;
        }
    }

    // -------------------- Verify helper methods end -------------------

    // -------------------- General helper methods ----------------------

    protected function isGatewaySuccess(array $content): bool
    {
        return ($content[ResponseFields::STATUS] === Status::SUCCESS);
    }

    protected function getMerchantId(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    protected function updateGatewayPaymentEntity(
        GatewayEntity $gatewayPayment,
        array $content,
        bool $mapped = true
    )
    {
        if ($mapped === true)
        {
            $content = $this->getMappedAttributes($content);
        }
        // To mark that we have received a response for this request
        $content[NetbankingEntity::RECEIVED] = 1;

        $gatewayPayment->fill($content);

        $gatewayPayment->saveOrFail();
    }

    public function getEncryptor(): AESCrypto
    {
        $secret = $iv = $this->getSecret();

        assert($secret !== null); // nosemgrep : razorpay:assert-fix-false-positives

        return (new AESCrypto(AES::MODE_CBC, $secret, $iv));
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function createGatewayPaymentEntityWithAttributes($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAction($input[NetbankingEntity::ACTION]);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    public function syncGatewayTransactionDataFromCps(array $attributes, array $input)
    {
        $paymentId = $attributes[NetbankingEntity::PAYMENT_ID];
        $action = $input[NetbankingEntity::ACTION];

        $gatewayEntity = $this->repo->findByPaymentIdAndAction($paymentId,$action);

        if (empty($gatewayEntity) === true)
        {
            $gatewayEntity = $this->createGatewayPaymentEntityWithAttributes($attributes, $input);
        }
        else
        {
            $gatewayEntity->setAction($input[NetbankingEntity::ACTION]);

            $this->updateGatewayPaymentEntity($gatewayEntity, $attributes, false);
        }
    }
}
