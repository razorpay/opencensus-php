<?php

namespace RZP\Gateway\Wallet\Amazonpay;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Exception\RuntimeException;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Models\Payment\Verify\Action as VerifyAction;
use RZP\Gateway\Wallet\Amazonpay\Sdk\PWAINBackendSDK;

class Gateway extends Base\Gateway
{
    /**
     * The name of the payment gateway developed in this class.
     * @override
     * @var string
     */
    protected $gateway = Payment\Gateway::WALLET_AMAZONPAY;

    /**
     * This variable is used to map request / response values to Wallet Entity values.
     * @see \RZP\Gateway\Base\Gateway getMappedAttributes
     * @override
     * @var array
     */
    protected $map = [
        // Authorize request parameters
        RequestFields::TOTAL_AMOUNT      => Entity::AMOUNT,
        Entity::GATEWAY_MERCHANT_ID      => Entity::GATEWAY_MERCHANT_ID,

        // Callback response parameters
        ResponseFields::AMAZON_ORDER_ID  => Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::REASON_CODE      => Entity::RESPONSE_CODE,
        ResponseFields::DESCRIPTION      => Entity::RESPONSE_DESCRIPTION,
        ResponseFields::STATUS           => Entity::STATUS_CODE,
        ResponseFields::TRANSACTION_DATE => Entity::DATE,
        ResponseFields::SIGNATURE        => Entity::REFERENCE1,
        Entity::RECEIVED                 => Entity::RECEIVED,
    ];

    /**
     * Singleton of the Amazon SDK class in the SDK folder
     * @var PWAINBackendSDK
     */
    private $amazonPaySdk;

    /**
     * Public methods start below
     */

    /**
     * @param array $input
     * @return array
     */
    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity(
            [
                RequestFields::TOTAL_AMOUNT      => $input['payment']['amount'],
                Entity::GATEWAY_MERCHANT_ID      => $this->getMerchantId(),
            ]);

        return $request;
    }

    public final function callback(array $input): array
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($content[ResponseFields::SELLER_ORDER_ID], $input['payment']['id']);

        // Amazon may return amount as 100 or 100.00
        // Just to be at safe side, we are converting to int
        $this->assertAmount(intval($content[ResponseFields::AMOUNT] * 100), $input['payment']['amount']);

        $this->verifySecureHash($content);

        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
                    $input['payment']['id'],
                    Base\Action::AUTHORIZE);

        $content[Entity::RECEIVED] = 1;

        // We need to unset the amount from getting mapped as it will be in rupees
        // And we have already filled it while creating the gateway entity
        unset($content[ResponseFields::AMOUNT]);

        $this->updateGatewayPaymentEntity($wallet, $content, true);

        $this->checkCallbackResponseStatus($content);

        return $this->getCallbackResponseData($input);
    }

    public final function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public final function refund(array $input)
    {
        parent::refund($input);

        $wallet = $this->repo->findByPaymentIdAndAction(
                    $input['payment']['id'],
                    Base\Action::AUTHORIZE);

        $request = $this->getRefundRequest($input, $wallet);

        $refundData = [
            Entity::PAYMENT_ID          => $input['payment']['id'],
            Entity::AMOUNT              => $input['refund']['amount'],
            Entity::WALLET              => $input['payment']['wallet'],
            Entity::EMAIL               => $input['payment']['email'],
            Entity::CONTACT             => $input['payment']['contact'],
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::REFUND_ID           => $input['refund']['id']
        ];

        $refund = $this->createGatewayRefundEntity($refundData);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request'    => $request,
                'payment_id' => $input['payment']['id'],
                'refund_id'  => $input['refund']['id'],
                'gateway'    => $this->gateway
            ]);

        $response = $this->sendGatewayRequest($request);

        $parsed = $this->parseResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'response'   => $response->body,
                'payment_id' => $input['payment']['id'],
                'refund_id'  => $input['refund']['id'],
                'gateway'    => $this->gateway,
                'parsed'     => $parsed,
            ]);

        $this->handleRefundResponse($input, $parsed, $refund);
    }

    public function verifyRefund(array $input)
    {
        parent::action($input, Payment\Action::VERIFY_REFUND);

        $verify = new Verify($this->gateway, $input);

        $this->sendRefundVerifyRequest($verify);

        $refunded = $this->verifyRefundResponse($verify);

        return $refunded;
    }

    public final function getAmazonPaySdk(): PWAINBackendSDK
    {
        if ($this->amazonPaySdk === null)
        {
            $config = $this->getConfigArray();

            $this->amazonPaySdk = new PWAINBackendSDK($config, $this->mock);
        }

        return $this->amazonPaySdk;
    }

    /**
     * Protected methods start below
     */

    /**
     * @param Verify $verify
     */
    protected final function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify->input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response'   => $response->body,
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
                'parsed'     => $verify->verifyResponseContent,
            ]);
    }

    protected final function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->assertVerifyAmountAndPaymentId($verify);

        $this->saveVerifyContent($verify);
    }

    protected function sendRefundVerifyRequest(Verify $verify)
    {
        $refund = $this->repo->findByRefundId($verify->input['refund']['id']);

        $request = $this->getVerifyRefundRequest($refund);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'response'   => $response->body,
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
                'parsed'     => $verify->verifyResponseContent,
            ]);
    }

    protected function verifyRefundResponse(Verify $verify)
    {
        $response = $this->getRelevantRefundDetail(
                               ResponseFields::GET_REFUND_DETAILS_RESULT_WRAPPER,
                               $verify->verifyResponseContent,
                               $verify->input['refund']['id']);

        $attributes = $this->getRefundResponseAttributesToSave($response);

        $gatewayRefund = $this->repo->findByRefundId($verify->input['refund']['id']);

        $this->updateGatewayRefundEntity($gatewayRefund, $attributes, false);

        return ($attributes[Entity::STATUS_CODE] === Status::COMPLETED);
    }

    /**
     * @param array $content
     * @throws GatewayErrorException
     */
    protected final function verifySecureHash(array $content)
    {
        list($generatedSign, $actualSign) = $this->getAmazonPaySdk()->verifySignature($content);

        if (hash_equals($actualSign, $generatedSign) === false)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
                null,
                null,
                [
                    'actual'    => $actualSign,
                    'generated' => $generatedSign
                ]);
        }
    }

    /**
     * Private functions start below
     */

    /**
     * @param array $content
     * @throws GatewayErrorException
     */
    private function checkCallbackResponseStatus(array $content)
    {
        if (isset($content[ResponseFields::REASON_CODE]) === true)
        {
            if ($content[ResponseFields::REASON_CODE] === ReasonCode::SUCCESS)
            {
                return;
            }

            $gatewayErrorCode = $content[ResponseFields::REASON_CODE];

            $gatewayErrorDesc = $content[ResponseFields::DESCRIPTION];

            $code = ErrorCodes::getInternalErrorCode($gatewayErrorCode);

            throw new GatewayErrorException($code, $gatewayErrorCode, $gatewayErrorDesc, $content);
        }

        throw new GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
            null,
            'Reason code is missing from callback',
            $content);
    }

    private function getRefundRequest(array $input, Entity $wallet): array
    {
        $parameters = [
            RequestFields::AMAZON_TRAN_TYPE => Constant::ORDER_REF_ID,
            RequestFields::AMAZON_TRAN_ID   => $wallet->getGatewayPaymentId(),
            RequestFields::REFUND_REF_ID    => $input['refund']['id'],
            RequestFields::REFUND_AMOUNT    => $this->formatAmount($input['refund']['amount']),
            RequestFields::REFUND_CURRENCY  => $input['refund']['currency'],
        ];

        return [
            'url'     => $this->getAmazonPaySdk()->refund($parameters),
            'method'  => 'get',
            'content' => []
        ];
    }

    private function handleRefundResponse(array $input, array $response, Entity $refund)
    {
        $response = $this->getRelevantRefundDetail(
                               ResponseFields::REFUND_PAYMENT_RESULT_WRAPPER,
                               $response,
                               $refund->getRefundId());

        $attributesToSave = $this->getRefundResponseAttributesToSave($response);

        $this->updateGatewayRefundEntity($refund, $attributesToSave, false);

        $this->checkRefundStatus($response, Status::PENDING);
    }

    private function getRelevantRefundDetail(string $wrapper, array $response, string $refundId): array
    {
        $requestId = $response[ResponseFields::RESPONSE_METADATA][ResponseFields::REQUEST_ID] ??
                     $response[ResponseFields::RESPONSE_METADATA][ResponseFields::REQUEST_UC_ID];

        $toReturn = [ResponseFields::REQUEST_ID => $requestId];

        $refundDetails = array_get($response, $wrapper, []);

        // In case of single refund in details, the head will consist refund_ref_id
        // Note:: PHP Xml to Array make child node as associative array
        if (isset($refundDetails[ResponseFields::REFUND_REF_ID]) === true)
        {
            $refundDetails = [$refundDetails];
        }

        foreach ($refundDetails as $refundDetail)
        {
            // We can validate current refund by refund_reference_id
            if($refundDetail[ResponseFields::REFUND_REF_ID] === $refundId)
            {
                return array_merge($toReturn, $refundDetail);
            }
        }

        throw new GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
            null,
            'Refund not found in response',
            [
                'refund_id' => $refundId,
            ]);
    }

    private function getRefundResponseAttributesToSave(array $response): array
    {
        $statusCode = strtolower($response[ResponseFields::REFUND_STATUS][ResponseFields::REFUND_STATE]);

        return [
            Entity::RECEIVED          => true,
            Entity::STATUS_CODE       => $statusCode,
            Entity::GATEWAY_REFUND_ID => $response[ResponseFields::AMAZON_REFUND_ID],
            Entity::REFERENCE2        => $response[ResponseFields::REQUEST_ID],
        ];
    }

    /**
     * While pulling out the relevant refund detail to process,
     * we either return the first detail in the response details,
     * or we return the only pending detail in the response details,
     * or we throw an exception when there are more 1 pending details.
     * So here we check if the refund status is pending or not.
     *
     * @param array $response
     * @throws GatewayErrorException
     */
    private function checkRefundStatus(array $response, string $status)
    {
        $throwException = false;

        if (isset($response[ResponseFields::REFUND_STATUS][ResponseFields::REFUND_STATE]) === true)
        {
            $refundState = $response[ResponseFields::REFUND_STATUS][ResponseFields::REFUND_STATE];

            if (Status::matches($status, $refundState) === true)
            {
                return true;
            }
        }

        throw new GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
            null,
            null,
            $response);
    }

    private function assertVerifyAmountAndPaymentId(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $payment = $verify->input['payment'];

        if (isset($content[ResponseFields::ORDER_TOTAL][ResponseFields::ORDER_AMOUNT]) === true)
        {
            $actualAmount = intval($content[ResponseFields::ORDER_TOTAL][ResponseFields::ORDER_AMOUNT] * 100);

            $expectedAmount = $payment['amount'];

            $verify->amountMismatch = ($expectedAmount !== $actualAmount);
        }
        else
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                'Invalid Verify Response: Order amount missing',
                null,
                [
                    'content' => $content
                ]);
        }
    }

    private function getVerifyStatus(Verify $verify): string
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            return VerifyResult::STATUS_MISMATCH;
        }

        return VerifyResult::STATUS_MATCH;
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $verify->verifyResponseContent = $this->getVerifyContentToVerify($verify);

        $content = $verify->verifyResponseContent;

        if ((empty($content[ResponseFields::ORDER_REFERENCE_STATUS][ResponseFields::UC_REASON_CODE]) === false) and
            ($content[ResponseFields::ORDER_REFERENCE_STATUS][ResponseFields::UC_REASON_CODE] ===
                ReasonCode::ORDER_REFERENCE_SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    private function getVerifyContentToVerify(Verify $verify): array
    {
        $content = $verify->verifyResponseContent;

        if (empty($content[ResponseFields::ERROR]) === false)
        {
            //
            // When an invalid request is made, the returned response contains the error sub-array
            // Sometimes RequestId is sent, and sometimes RequestID is sent across in the response.
            // We assign the verify request id to its own key in the content array
            //
            $content[ResponseFields::REQUEST_ID] = $content[ResponseFields::REQUEST_ID] ??
                                                       $content[ResponseFields::REQUEST_UC_ID];

            return $content;
        }
        elseif (empty($content) === true)
        {
            // If the response was parsed to an empty array, we return back the empty array
            return [];
        }

        return $this->getSuccessRequestContentToVerify($content);
    }

    private function getSuccessRequestContentToVerify(array $content): array
    {
        // We pull out the request id before manipulating the content array
        $requestId = $content[ResponseFields::RESPONSE_METADATA][ResponseFields::REQUEST_ID];

        if ((empty($content[ResponseFields::ORDER_REFERENCE_RESULT]) === true) or
            (empty($content[ResponseFields::ORDER_REFERENCE_RESULT][ResponseFields::ORDER_REFERENCE_LIST]) === true))
        {
            // When the reference keys are not set, we simply default to $content;
            $content[ResponseFields::REQUEST_ID] = $requestId;

            return $content;
        }

        $orderReferenceLists = $content[ResponseFields::ORDER_REFERENCE_RESULT][ResponseFields::ORDER_REFERENCE_LIST];

        // $numOrders has to be at least 1, or else the method would have returned from the if case above.
        $numOrders = count($orderReferenceLists);

        if ($numOrders > 1)
        {
            // When there is more than one list, use the most relevant
            $listToBeReturned = $this->getRelevantOrderReferenceToVerify($orderReferenceLists);
        }
        else
        {
            // By default, return the only list in the response
            $listToBeReturned = head($orderReferenceLists);
        }

        // Storing the verify request in the list to be returned irrespective of the response case
        $listToBeReturned[ResponseFields::REQUEST_ID] = $requestId;

        return $listToBeReturned;
    }

    private function getRelevantOrderReferenceToVerify(array $orderReferenceLists): array
    {
        $numSuccess = 0;

        // By default, we simply return the head of the list
        $listToBeReturned = head($orderReferenceLists);

        // We find the number of successful lists within the response
        foreach ($orderReferenceLists as $list)
        {
            $orderReferenceStatus = $list[ResponseFields::ORDER_REFERENCE][ResponseFields::ORDER_REFERENCE_STATUS];

            if ((empty($orderReferenceStatus[ResponseFields::UC_REASON_CODE]) === false) and
                ($orderReferenceStatus[ResponseFields::UC_REASON_CODE] === ReasonCode::ORDER_REFERENCE_SUCCESS))
            {
                $numSuccess++;

                $listToBeReturned = $list[ResponseFields::ORDER_REFERENCE];
            }
        }

        //
        // If the number of successful
        // transactions is greater than 1, then this is an error
        // and therefore we throw an exception
        //
        if ($numSuccess > 1)
        {
            $data = [
                'response_array' => $orderReferenceLists,
                'payment_id'     => $this->input['payment']['id'],
                'num_success'    => $numSuccess,
                'gateway'        => $this->gateway,
            ];

            $this->trace->error(TraceCode::MULTIPLE_TABLES_IN_VERIFY_RESPONSE, ['response_data' => $data]);

            throw new PaymentVerificationException(
                $data,
                null,
                VerifyAction::FINISH,
                ErrorCode::SERVER_ERROR_MULTIPLE_SUCCESS_TRANSACTIONS_IN_VERIFY
            );
        }

        // We either return the first list in the response, or the success list in the response.
        return $listToBeReturned;
    }

    private function saveVerifyContent(Verify $verify)
    {
        $verify->payment->fill($this->getVerifyAttributesToSave($verify));

        $this->repo->saveOrFail($verify->payment);
    }

    private function getVerifyAttributesToSave(Verify $verify): array
    {
        $content = $verify->verifyResponseContent;

        if (empty($content[ResponseFields::ERROR]) === false)
        {
            // We will save only the requestId if it is an error
            return [Entity::REFERENCE2 => $content[ResponseFields::REQUEST_ID]];
        }
        else if (empty($content) === true)
        {
            // If content is empty, we simply don't save anything
            return [];
        }

        return $this->getSuccessRequestVerifyAttributesToSave($verify);
    }

    /**
     * This flow happens when the verify request resulted in a API contract based response.
     * @param Verify $verify
     * @return array
     */
    private function getSuccessRequestVerifyAttributesToSave(Verify $verify): array
    {
        $contentToSave = [];

        $content = $verify->verifyResponseContent;

        $wallet = $verify->payment;

        //
        // If apiSuccess !== gatewaySuccess, then we update the status fields.
        // We also don't update the status from success to failed.
        // This case won't ever be called when ListOrderReferenceResult or OrderReferenceList is empty.
        //
        if (($verify->match === false) and
            ($wallet->getStatusCode() !== Status::SUCCESS) and
            (empty($content[ResponseFields::ORDER_REFERENCE_STATUS]) === false))
        {
            // It is necessary that these attributes are set, or they will break the API contract
            $referenceStatus = $content[ResponseFields::ORDER_REFERENCE_STATUS];

            $status = Status::getVerifyReasonCodeMappedToAuthStatus($referenceStatus[ResponseFields::UC_REASON_CODE]);

            $reasonCode = ReasonCode::getVerifyReasonCodeMappedToAuthReasonCode(
                              $referenceStatus[ResponseFields::UC_REASON_CODE]);

            $contentToSave = array_merge(
                $contentToSave,
                [
                    Entity::RESPONSE_CODE        => $reasonCode,
                    Entity::RESPONSE_DESCRIPTION => $referenceStatus[ResponseFields::REASON_DESCRIPTION],
                    Entity::STATUS_CODE          => $status
                ]);
        }

        if (empty($content[ResponseFields::AMAZON_REFERENCE_ID]) === false)
        {
            $contentToSave[Entity::GATEWAY_PAYMENT_ID] = $content[ResponseFields::AMAZON_REFERENCE_ID];
        }

        return array_merge(
            $contentToSave,
            [
                // We save their request id as part of reference 2 parameter
                Entity::REFERENCE2 => $content[ResponseFields::REQUEST_ID]
            ]);
    }

    private function parseResponse(string $body)
    {
        try
        {
            return $this->xmlToArray($body);
        }
        catch (RuntimeException $e)
        {
            //
            // Exception is traced in the implementation of xmlToArray
            // We just return an empty array here
            //
            return [];
        }
    }

    private function getVerifyRequestData(array $input): array
    {
        $request = [
            RequestFields::PAYMENT_DOMAIN       => Constant::PAYMENT_DOMAIN,

            // We search payment by razorpay payment id, we are not using get by id api
            RequestFields::QUERY_ID             => $input['payment']['id'],
            RequestFields::QUERY_ID_TYPE        => ucfirst(RequestFields::ORDER_ID),

            // Amazon puts default time span of 3 days if we do set it explicitly
            RequestFields::VERIFY_START_TIME    => Carbon::createFromTimestamp($input['payment']['created_at'])
                                                         ->subMinute(1)
                                                         ->toIso8601String(),
            RequestFields::VERIFY_END_TIME      => Carbon::now()
                                                         ->toIso8601String(),
        ];

        return [
            'url'     => $this->getAmazonPaySdk()->listOrderReference($request),
            'method'  => 'get',
            'content' => []
        ];
    }

    /**
     * @param array $input
     * @return array
     */
    private function getAuthorizeRequest(array $input): array
    {
        $content = [
            // Mandatory fields
            RequestFields::TOTAL_AMOUNT      => $this->formatAmount($input['payment']['amount']),
            RequestFields::CURRENCY_CODE     => Currency::INR,
            RequestFields::ORDER_ID          => $input['payment']['id'],

            // Optional fields
            RequestFields::IS_SANDBOX        => $this->isSandbox() ? 'true' : 'false',
            RequestFields::TXN_TIMEOUT       => Constant::TIMEOUT,
        ];

        // Callback Url needs to whitelisted at Amazon, thus can't use payment's callback url
        $callbackUrl = $this->route->getUrl('gateway_payment_callback_amazonpay');

        // The SDK is used to add a signature to the request, as well as encrypt the request
        // The signature serves as an extra layer of authentication on Amazon's side.
        $relativeUrl = $this->getAmazonPaySdk()->getProcessPaymentUrl($content, $callbackUrl);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'payment_id' => $input['payment']['id'],
                'gateway'    => $this->gateway,
                'request'    => $content,
                'encrypted'  => $relativeUrl,
            ]);

        return [
            'url'    => $this->getUrl() . '?' . $relativeUrl,
            'method' => 'get'
        ];
    }

    private function getVerifyRefundRequest(Entity $refund)
    {
        $parameters = [
            RequestFields::AMAZON_REFUND_ID => $refund->getGatewayRefundId(),
        ];

        return [
            'url'       => $this->getAmazonPaySdk()->getRefundDetails($parameters),
            'method'    => 'GET',
            'content'   => [],
        ];
    }

    private function getConfigArray(): array
    {
        return [
            Config::MERCHANT_ID => $this->getMerchantId(),
            Config::ACCESS_KEY  => $this->getAccessCode(),
            Config::SECRET_KEY  => $this->getSecret(),
            Config::BASE_URL    => $this->getUrlDomain(),
            Config::SANDBOX     => $this->isSandbox(),
        ];
    }

    private function getMerchantId(): string
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getAccessCode(): string
    {
        $accessCode = $this->input['terminal']['gateway_access_code'];

        if ($this->mode === Mode::TEST)
        {
            $accessCode = $this->config['test_access_code'];
        }

        return $accessCode;
    }

    protected function isSandbox(): bool
    {
        return ($this->mode === Mode::TEST);
    }

    private function formatAmount($amount): string
    {
        return number_format(floatval($amount / 100), 2, '.', '');
    }

    private function parseRefundDetails($input)
    {
        $output = [
            'error'     => true,
            'entity'    => null,
        ];

        if (isset($input['GetRefundDetailsResult']))
        {
            $refund = $input['GetRefundDetailsResult']['RefundDetails'];

            $output['entity'] = [
                Entity::REFUND_ID              => $refund[ResponseFields::REFUND_REF_ID],
                Entity::STATUS_CODE            => $refund[ResponseFields::REFUND_STATUS][ResponseFields::REFUND_STATE],
            ];

            $output['error'] = false;
        }

        return $output;
    }
}
