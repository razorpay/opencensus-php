<?php

namespace RZP\Gateway\Netbanking\Csb;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Constants\Mode as RZPMode;
use RZP\Models\Payment\Gateway as PG;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Models\Payment\Verify\Action as VerifyAction;

/**
 * This gateway was developed as per the API contract shared by the bank.
 * @see https://drive.google.com/file/d/0B1kf6HOmx7JBQVg3dUgtN2tYN3dMN2ZGNjh4VERVbXh4MllB/view?usp=sharing
 *
 * Class Gateway
 * @package RZP\Gateway\Netbanking\Csb
 */
class Gateway extends Base\Gateway
{
    protected $gateway = PG::NETBANKING_CSB;

    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::AMOUNT        => Base\Entity::AMOUNT,

        /**
         * Fields from the authorize response
         */
        ResponseFields::TRAN_REF_NUM => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::STATUS       => Base\Entity::STATUS,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity([RequestFields::AMOUNT => $input['payment']['amount'] / 100]);

        $this->traceGatewayPaymentRequest($request,
                                          $input,
                                          $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
                                          ['encrypted' => true]);

        return $request;
    }

    public final function callback(array $input): array
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::BANK_REF_NUM]);

        $this->assertAmount($input['payment']['amount'] / 100, $content[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        // We check the callback status and set the callbackSuccess property of this class
        $callbackSuccess = $this->checkCallbackSuccess($input['gateway']);

        //
        // We verify the callback response before doing anything else with the response,
        // this is so that we ensure the response is for the right payment id and amount
        // We are eliminating false positives in this case (callback returns success, when it actually a failure).
        // We do not handle the case when callback = failure, and verify callback = success. We do not handle false negatives.
        //
        $this->verifyCallback($gatewayPayment, $input, $callbackSuccess);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->throwExceptionIfCallbackFailure($callbackSuccess, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public final function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public final function sendPaymentVerifyRequest(Verify $verify, bool $verifyCallback = false)
    {
        $request = $this->getVerifyRequestData($verify, $verifyCallback);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            array_merge(
                $request,
                [
                    'encrypted'       => true,
                    'payment_id'      => $verify->input['payment']['id'],
                    'gateway'         => $this->gateway,
                    'verify_callback' => $verifyCallback
                ]));

        $response = $this->sendGatewayRequest($request);

        $data = [
            'gateway'         => $this->gateway,
            'response'        => $response->body,
            'payment_id'      => $verify->input['payment']['id'],
            'verify_callback' => $verifyCallback
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE, $data);

        try
        {
            $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
        }
        catch (\ErrorException $e)
        {
            // We set apiSuccess to true/false and gatewaySuccess to false
            $this->checkApiSuccess($verify);

            $verify->gatewaySuccess = false;

            // If we are unable to parse the verify response, we must move the payment to verify bucket 9
            throw new PaymentVerificationException(
                $data,
                $verify,
                VerifyAction::FINISH,
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                $e);
        }
    }

    public function verifyPayment(Verify $verify)
    {
        //
        // we won't be setting amountMismatch here because verify response doesn't contain amount
        //

        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    /**
     * Exposing this method as a public API for the mock server to access
     *
     * @override
     * @param $str
     * @return string
     */
    public final function getHashOfString($str): string
    {
        return hash(HashAlgo::CRC32, $str);
    }

    /**
     * Overriding this method so that it can be exposed as a public API for the mock server
     *
     * @override
     * @param $actual
     * @param $generated
     */
    public final function compareHashes($actual, $generated)
    {
        parent::compareHashes($actual, $generated);
    }

    public final function computeChecksum(array $content): string
    {
        // Add the secret to the end of the content array to be hashed
        array_push($content, $this->getSecret());

        $contentToHash = implode('|', $content);

        // Remove the last element of the array which is the checksum key
        array_pop($content);

        return (string) hexdec($this->getHashOfString($contentToHash));
    }

    //-----------------------------------------------  Public methods ------------------------------------------------//

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     *
     * @param Base\Entity $gatewayPayment
     * @param array $input
     * @param bool $callbackSuccess
     * @throws GatewayErrorException
     */
    protected final function verifyCallback(Base\Entity $gatewayPayment, array $input, bool $callbackSuccess)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify, true);

        $this->checkGatewaySuccess($verify);

        //
        // If callback returned a success and
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if (($callbackSuccess === true) and
            ($verify->gatewaySuccess === false))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                null,
                null,
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    protected final function updateGatewayPaymentEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true): Entity
    {
        $attributes = $this->getMappedAttributes($attributes);

        $attributes[Base\Entity::RECEIVED] = true;

        return parent::updateGatewayPaymentEntity($gatewayPayment, $attributes, false);
    }

    /**
     * Asserting that payment amount is the same as the amount received in the callback / verify response.
     *
     * @override
     * @param $expectedAmount
     * @param $actualAmount
     */
    protected final function assertAmount($expectedAmount, $actualAmount)
    {
        $expectedAmount = $this->formatAmount($expectedAmount);
        $actualAmount = $this->formatAmount($actualAmount);

        parent::assertAmount($expectedAmount, $actualAmount);
    }

    /**
     * Getting live secret from the config
     * @override
     * @return mixed
     */
    protected function getLiveSecret(): string
    {
        return $this->config['live_hash_secret'];
    }

    //-----------------------------------------------  Private methods -----------------------------------------------//

    private function throwExceptionIfCallbackFailure(bool $callbackSuccess, array $content)
    {
        // callbackSuccess is set during verify callback
        if ($callbackSuccess === false)
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                [
                    'callback_response' => $content,
                    'payment_id'        => $this->input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    /**
     * This method gets the required verify request as per API contract.
     * @see https://docs.google.com/document/d/153ypkOhWNIetN3kV153gevKz2EIBO4aGj4XjIguLB0Y/edit#
     *
     * @param Verify $verify
     * @param bool $verifyCallback
     * @return array
     */
    private function getVerifyRequestData(Verify $verify, bool $verifyCallback = false): array
    {
        $content = [
            Constant::CHNPGSYN,
            $this->getMerchantId(),
            $this->getMerchantId2(),
            $verify->input['payment']['id'],
            $verify->input['payment']['amount'] / 100,
            $this->getCallbackUrl(),
        ];

        //
        // When we are verifying the callback, we want to send the
        // callback response payee_id and amount in the verify request
        //
        if ($verifyCallback === true)
        {
            $content[3] = $verify->input['gateway'][ResponseFields::BANK_REF_NUM];
            $content[4] = $verify->input['gateway'][ResponseFields::AMOUNT];
            $content[5] = $this->getCallbackUrl();
        }

        if (empty($verify->payment->getBankPaymentId()) === false)
        {
            $content = array_merge($content, [$verify->payment->getBankPaymentId(), Mode::VERIFY]);
        }
        else
        {
            $content = array_merge($content, ["", Mode::VERIFY_WO_TID]);
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            array_merge($content, ['not yet encrypted', "verify_callback = $verifyCallback"]));

        // Setting verify request property of $verify
        $verify->verifyRequest = $content;

        $contentToEncode = $this->computeStringToEncode($content);

        $content = [RequestFields::POST_DATA => base64_encode($contentToEncode)];

        return $this->getStandardRequestArray($content);
    }

    private function parseVerifyResponse(string $responseString): array
    {
        return (array) simplexml_load_string($responseString);
    }

    private function getVerifyStatus(Verify $verify): string
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

    private function checkCallbackSuccess(array $content)
    {
        if ((empty($content[ResponseFields::STATUS]) === false) and
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            return false;
        }

        return true;
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = Status::FAILURE;

        if (array_key_exists(ResponseFields::VERIFICATION, $content) === true)
        {
            $status = trim($content[ResponseFields::VERIFICATION]);
        }
        elseif (array_key_exists(ResponseFields::STATUS_UCFIRST, $content) === true)
        {
            // When TID is null, they send verify status inside Status
            $status = trim($content[ResponseFields::STATUS_UCFIRST]);
        }

        // content will contain status 100 or 101
        if ($status === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    private function saveVerifyContent(Verify $verify)
    {
        $wallet = $verify->payment;

        $content = $verify->verifyResponseContent;

        $contentToSave = [];

        if ((empty($wallet[Base\Entity::STATUS]) === true) or
            ($wallet[Base\Entity::STATUS] !== Status::SUCCESS))
        {
            $contentToSave[ResponseFields::STATUS] = $content[ResponseFields::VERIFICATION] ??
                                                     $content[ResponseFields::STATUS_UCFIRST];
        }

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    /**
     * This method gets the required authorize request as per API contract.
     * @see https://docs.google.com/document/d/153ypkOhWNIetN3kV153gevKz2EIBO4aGj4XjIguLB0Y/edit#
     *
     * @param array $input
     * @return array
     */
    private function getAuthorizeRequest(array $input): array
    {
        $contentToEncrypt = [
            RequestFields::CHNPGSYN     => Constant::CHNPGSYN,
            RequestFields::CHNPGCODE    => $this->getMerchantId(),
            RequestFields::PAYEE_ID     => $this->getMerchantId2(),
            RequestFields::BANK_REF_NUM => $input['payment']['id'],
            RequestFields::AMOUNT       => $input['payment']['amount'] / 100,
            RequestFields::RETURN_URL   => $input['callbackUrl'],
            RequestFields::MODE         => Mode::PAY,
        ];

        $this->traceGatewayPaymentRequest(
            $contentToEncrypt,
            $input,
            $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
            ['encrypted' => false]);

        $contentToEncode = $this->computeStringToEncode(array_values($contentToEncrypt));

        $content = [RequestFields::POST_DATA => base64_encode($contentToEncode)];

        return $this->getStandardRequestArray($content);
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * This method does the following:
     * 1. Takes in the array to be hashed
     * 2. Computes the hash of the array
     * 3. Removes the hash key from the array
     * 4. Adds computed checksum to array
     * 4. Implodes into required format string and returns
     *
     *
     * @param array $content
     * @return string
     */
    private function computeStringToEncode(array $content): string
    {
        $checkSum = $this->computeChecksum($content);

        // Add the checksum value to the end of the array
        array_push($content, $checkSum);

        return implode('|', $content);
    }

    private function getMerchantId(): string
    {
        $merchantId = $this->config['test_merchant_id'];

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
        }

        return $merchantId;
    }

    /**
     * Sub merchant.
     * @return string
     */
    private function getMerchantId2(): string
    {
        $merchantId2 = Constant::PID;

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId2 = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
        }

        return $merchantId2;
    }

    private function getCallbackUrl(): string
    {
        return 'https://www.api.razorpay.com';
    }
}
