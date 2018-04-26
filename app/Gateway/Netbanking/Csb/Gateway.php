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

class Gateway extends Base\Gateway
{
    const PAYEE_ID = 'Razorpay';

    const CALLBACK_URL = 'https://www.api.razorpay.com';

    protected $gateway = PG::NETBANKING_CSB;

    /**
     * This array is modified while getting the authorize request.
     * It is used to create the gateway netbanking entity.
     * @see getAuthorizeRequest
     * @var array
     */
    private $gatewayAttributes = [];

    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::CHNPGCODE     => Base\Entity::MERCHANT_CODE,
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

        $this->createGatewayPaymentEntity([RequestFields::AMOUNT => $input['payment']['amount']]);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::BANK_REF_NUM]);

        $this->assertAmount($input['payment']['amount'] / 100, $content[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->checkCallbackSuccess($input['gateway'], $gatewayPayment);

        //
        // We verify the callback response before doing anything else with the response,
        // this is so that we ensure the response is for the right payment id and amount
        //
        $this->verifyCallback($gatewayPayment, $input);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function getHashOfArray($content)
    {
        $hashString = $this->getStringToHash($content, '|');

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($content, $glue = '')
    {
        $content[] = $this->getSecret();

        return implode($glue, $content);
    }

    protected function getHashOfString($str): string
    {
        return (string) hexdec(hash(HashAlgo::CRC32, $str));
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        $response = $this->sendGatewayRequest($request);

        $data = [
            'gateway'         => $this->gateway,
            'response'        => $response->body,
            'payment_id'      => $verify->input['payment']['id']
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE, $data);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);
    }

    protected function verifyPayment(Verify $verify)
    {
        //
        // we won't be setting amountMismatch here because verify response doesn't contain amount
        //

        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     *
     * @param Base\Entity $gatewayPayment
     * @param array $input
     * @throws GatewayErrorException
     */
    protected function verifyCallback(Base\Entity $gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If the status in callback and verify does not match
        //
        if ($verify->gatewaySuccess !== true)
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

    protected function updateGatewayPaymentEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true): Entity
    {
        $attributes = $this->getMappedAttributes($attributes);

        // Since we get the amount in Rs in the callback, we convert to paise before saving
        $attributes[Base\Entity::AMOUNT] = $attributes[Base\Entity::AMOUNT] * 100;

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
    protected function assertAmount($expectedAmount, $actualAmount)
    {
        $expectedAmount = $this->formatAmount($expectedAmount);
        $actualAmount = $this->formatAmount($actualAmount);

        parent::assertAmount($expectedAmount, $actualAmount);
    }

    protected function throwExceptionIfCallbackFailure(bool $callbackSuccess, array $content)
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
     * @return array
     */
    protected function getVerifyRequestData(Verify $verify): array
    {
        $content = [
            $this->getMerchantId(),
            $this->getMerchantId2(),
            self::PAYEE_ID,
            $verify->input['payment']['id'],
            $verify->input['payment']['amount'] / 100,
            self::CALLBACK_URL,
            '',
            Mode::VERIFY_WO_TID
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'data'       => $content,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]
        );

        $contentToEncode = $this->computeStringToEncode($content);

        $content = [RequestFields::POST_DATA => base64_encode($contentToEncode)];

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'encrypted'  => true,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]
        );

        return $request;
    }

    protected function parseVerifyResponse(string $responseString): array
    {
        $response = simplexml_load_string($responseString);

        //
        // Converting all elements of $response xml into an array
        //
        $responseArray = json_decode(json_encode($response), true);

        return $responseArray;
    }

    protected function getVerifyStatus(Verify $verify): string
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

    protected function checkCallbackSuccess(array $content, $gatewayPayment)
    {
        if ((empty($content[ResponseFields::STATUS]) === false) and
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->updateGatewayPaymentEntity($gatewayPayment, $content);

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

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = Status::FAILURE;

        if (isset($content[ResponseFields::VERIFICATION]) === true)
        {
            $status = trim($content[ResponseFields::VERIFICATION]);
        }
        elseif (isset($content[ResponseFields::STATUS_UCFIRST]) === true)
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

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $contentToSave = [];

        if ((empty($gatewayPayment[Base\Entity::STATUS]) === true) or
            ($gatewayPayment[Base\Entity::STATUS] !== Status::SUCCESS))
        {
            $contentToSave[ResponseFields::STATUS] = $content[ResponseFields::VERIFICATION] ??
                                                     $content[ResponseFields::STATUS_UCFIRST];
        }

        return parent::updateGatewayPaymentEntity($gatewayPayment, $contentToSave);
    }

    /**
     * This method gets the required authorize request as per API contract.
     * @see https://docs.google.com/document/d/153ypkOhWNIetN3kV153gevKz2EIBO4aGj4XjIguLB0Y/edit#
     *
     * @param array $input
     * @return array
     */
    protected function getAuthorizeRequest(array $input): array
    {
        $contentToEncrypt = [
            RequestFields::CHNPGSYN     => $this->getMerchantId(),
            RequestFields::CHNPGCODE    => $this->getMerchantId2(),
            RequestFields::PAYEE_ID     => self::PAYEE_ID,
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

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
            ['encrypted' => true]
        );

        return $request;
    }

    protected function formatAmount(float $amount): string
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
     * @param array $content
     * @return string
     */
    protected function computeStringToEncode(array $content): string
    {
        $checksum = $this->getHashOfArray($content);

        array_push($content, $checksum);

        return implode('|', $content);
    }

    protected function getMerchantId(): string
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
    protected function getMerchantId2(): string
    {
        $merchantId2 = $this->config['test_merchant_id_2'];

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId2 = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
        }

        return $merchantId2;
    }
}
