<?php

namespace RZP\Gateway\Netbanking\Csb;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Constants\Mode as RZPMode;
use RZP\Gateway\Base\VerifyResult;
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
        RequestFields::CHNPGSYN      => Base\Entity::REFERENCE1,
        RequestFields::CHNPGCODE     => Base\Entity::MERCHANT_CODE,
        RequestFields::AMOUNT        => Base\Entity::AMOUNT,

        /**
         * Fields from the authorize response
         */
        ResponseFields::TRAN_REF_NUM => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::STATUS       => Base\Entity::STATUS,
        ResponseFields::NARRATION    => Base\Entity::ERROR_MESSAGE, // TODO: Ensure this is correct
    ];

    /**
     * This array is modified while getting the authorize request.
     * It is used to create the gateway netbanking entity.
     * @see getAuthorizeRequest
     * @var array
     */
    private $gatewayAttributes = [];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity($this->gatewayAttributes);

        // Freeing memory occupied by this instance variable
        $this->gatewayAttributes = [];

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::BANK_REF_NUM]);

        // TODO: Is there a checksum here? If not we should verify.

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->checkResponseStatus($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $data = [
            'gateway'    => $this->gateway,
            'response'   => $response->body,
            'payment_id' => $verify->input['payment']['id'],
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE, $data);

        try
        {
            $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
        }
        catch (\Exception $e)
        {
            throw new PaymentVerificationException(
                $data,
                $verify,
                VerifyAction::RETRY,
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                $e);
        }
    }

    public function verifyPayment(Verify $verify)
    {
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
    public function getHashOfString($str): string
    {
        // TODO: Verify that this is the right way to generate the checksum
        return hash(HashAlgo::CRC32, $str);
    }

    /**
     * Overriding this method so that it can be exposed as a public API for the mock server
     *
     * @override
     * @param $actual
     * @param $generated
     */
    public function compareHashes($actual, $generated)
    {
        parent::compareHashes($actual, $generated);
    }

    protected function updateGatewayPaymentEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        $attributes = $this->getMappedAttributes($attributes);

        $attributes[Base\Entity::RECEIVED] = true;

        return parent::updateGatewayPaymentEntity($gatewayPayment, $attributes, false);
    }

    private function checkResponseStatus(array $content)
    {
        if ((empty($content[ResponseFields::STATUS]) === false) and
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                $content);
        }
    }

    private function getVerifyRequestData(Verify $verify): array
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $content = [
            Constants::CHNPGSYN,
            Constants::CHNPGCODE,
            $this->getMerchantId2(), // TODO: Check this
            $input['payment']['id'],
            $input['payment']['amount'] / 100,
            $this->getCallbackUrl($input['payment']['id']),
        ];

        if ($gatewayPayment->getBankPaymentId() !== "null")
        {
            $content = array_merge($content, [$gatewayPayment->getBankPaymentId(), Mode::VERIFY]);
        }
        else
        {
            array_push($content, Mode::VERIFY_WO_TID);
        }

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

    private function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = trim($content[ResponseFields::VERIFICATION]);

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
            $contentToSave[ResponseFields::STATUS] = $content[ResponseFields::VERIFICATION];
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
        $content = [
            RequestFields::CHNPGSYN     => Constants::CHNPGSYN, // TODO: These are terminal specific
            RequestFields::CHNPGCODE    => Constants::CHNPGCODE, // TODO: These are terminal specific
            RequestFields::PAYEE_ID     => $this->getMerchantId2(),
            RequestFields::BANK_REF_NUM => $input['payment']['id'],
            RequestFields::AMOUNT       => $input['payment']['amount'] / 100,
            RequestFields::RETURN_URL   => $input['callbackUrl'],
            RequestFields::MODE         => Mode::PAY,
        ];

        $this->gatewayAttributes = $content;

        $contentToEncode = $this->computeStringToEncode(array_values($content));

        $content = [RequestFields::POST_DATA => base64_encode($contentToEncode)];

        return $this->getStandardRequestArray($content);
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

    public final function computeChecksum(array $content)
    {
        // Add the secret to the end of the content array to be hashed
        array_push($content, $this->getSecret());

        $contentToHash = implode('|', $content);

        // Remove the last element of the array which is the checksum key
        array_pop($content);

        return (string) hexdec($this->getHashOfString($contentToHash));
    }

    private function getMerchantId2(): string
    {
        return $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
    }

    protected function getMerchantId(): string
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === RZPMode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getTestMerchantId(): string
    {
        // TODO: Ensure this is right
        return $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
    }

    /**
     * Creates the callback url for payment
     * where the gateway can hit back to say payment
     * is finished/authorized.
     *
     * @param string $paymentId
     * @return string Callback url
     */
    private function getCallbackUrl(string $paymentId): string
    {
        $params = $this->getPaymentIdAndHashParams($paymentId);

        return $this->route->getUrlWithPublicCallbackAuth($params);
    }

    private function getPaymentIdAndHashParams(string $paymentId): array
    {
        $publicId = Payment\Entity::getSignedId($paymentId);

        $hash = $this->getHashOf($publicId);

        return ['id' => $publicId, 'hash' => $hash];
    }

    /**
     * Returns a hash of a string.
     *
     * @param string $string
     * @return string Hash of the string
     */
    private function getHashOf(string $string): string
    {
        $secret = $this->app->config->get('app.key');

        return hash_hmac(HashAlgo::SHA1, $string, $secret);
    }
}
