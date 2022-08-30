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
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Models\Payment\Verify\Action as VerifyAction;
use RZP\Gateway\Wallet\Amazonpay\Sdk\PWAINBackendSDK;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields as AmazonResponse;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

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

        //Amazon is sending payment id as "sellerOrderId": "A3MJ8VJGR6SLBL_CjYROn3od7rp7Q  in callback for some
        //random cases.This will help us to fetch the payment id from the sellerOrderId.
        $paymentId = $this->getPaymentIdFromServerCallback($content);

        $this->assertPaymentId($paymentId, $input['payment']['id']);

        // Amazon may return amount as 100 or 100.00
        // Formatting payment amount to number
        $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

        $expectedAmount = $this->formatAmount($input['payment']['amount']);

        $this->assertAmount($actualAmount, $expectedAmount);

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

        $response = $this->getRelevantRefundDetail(
            ResponseFields::REFUND_PAYMENT_RESULT_WRAPPER,
            $parsed,
            $refund->getRefundId()
        );

        if (empty($response) === true)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                Constant::REFUND_NOT_FOUND_ERROR,
                [
                    Payment\Gateway::GATEWAY_RESPONSE => json_encode($parsed),
                    Payment\Gateway::GATEWAY_KEYS => [
                        'refund_id'  => $refund->getRefundId(),
                        'error_desc' => Constant::REFUND_NOT_FOUND_ERROR,
                    ],
                ]
            );
        }

        $this->handleRefundResponse($input, $response, $refund);

        return [
            Payment\Gateway::GATEWAY_RESPONSE => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS     => $this->getGatewayData($response),
        ];
    }

    protected function getLatestGatewayRefund(string $refundId)
    {
        $refunds = $this->repo->findByRefundIdAndAction($refundId, Base\Action::REFUND);

        return $refunds->last();
    }

    public function verifyRefund(array $input)
    {
        parent::action($input, Payment\Action::VERIFY_REFUND);

        $scroogeResponse = new ScroogeResponse();

        $verify = new Verify($this->gateway, $input);

        $refund = $this->getLatestGatewayRefund($verify->input['refund']['id']);

        // We need gateway_refund_id to perform verify action, for which refund action must
        // have been performed before. For gateways on scrooge verify happens before any
        // refund action. Hence assuming to refund is not present on gateway side
        if (empty($refund) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }

        $this->sendRefundVerifyRequest($verify, $refund);

        $response = $this->getRelevantRefundDetail(
            ResponseFields::GET_REFUND_DETAILS_RESULT_WRAPPER,
            $verify->verifyResponseContent,
            $verify->input['refund']['id']
        );

        if (empty($response) === true)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                Constant::REFUND_NOT_FOUND_ERROR,
                [
                    Payment\Gateway::GATEWAY_VERIFY_RESPONSE => json_encode($verify->verifyResponseContent),
                    Payment\Gateway::GATEWAY_KEYS => [
                        'refund_id'  => $verify->input['refund']['id'],
                        'error_desc' => Constant::REFUND_NOT_FOUND_ERROR,
                    ],
                ]
            );
        }

        $scroogeResponse->setGatewayVerifyResponse($response)
                        ->setGatewayKeys($this->getGatewayData($response));

        $refundStatus = $this->verifyRefundResponse($verify, $response);

        if ($refundStatus === Status::COMPLETED)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        if ($refundStatus === Status::DECLINED)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::BAD_REQUEST_REFUND_FAILED)
                                   ->toArray();
        }

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING)
                               ->toArray();
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

    protected function sendRefundVerifyRequest(Verify $verify, Entity $refund)
    {
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

    protected function verifyRefundResponse(Verify $verify, array $response)
    {
        $attributes = $this->getRefundResponseAttributesToSave($response);

        $gatewayRefund = $this->repo->findByRefundId($verify->input['refund']['id']);

        $this->updateGatewayRefundEntity($gatewayRefund, $attributes, false);

        return $attributes[Entity::STATUS_CODE];
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
        $attributesToSave = $this->getRefundResponseAttributesToSave($response);

        $this->updateGatewayRefundEntity($refund, $attributesToSave, false);

        $this->checkRefundStatus($response, Status::PENDING);
    }

    private function getRelevantRefundDetail(string $wrapper, array $response, string $refundId): array
    {
        if ((isset($response[ResponseFields::RESPONSE_METADATA]) === false) or
            ((isset($response[ResponseFields::RESPONSE_METADATA][ResponseFields::REQUEST_ID]) === false) and
             (isset($response[ResponseFields::RESPONSE_METADATA][ResponseFields::REQUEST_UC_ID]) === false)))
        {
            return [];
        }

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
            if ($refundDetail[ResponseFields::REFUND_REF_ID] === $refundId)
            {
                return array_merge($toReturn, $refundDetail);
            }
        }

        return [];
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

        if ((isset($response[ResponseFields::REFUND_STATUS][ResponseFields::REFUND_STATE]) === true) and
        (strtolower($response[ResponseFields::REFUND_STATUS][ResponseFields::REFUND_STATE]) == Status::PENDING))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                Status::PENDING,
                null,
                [
                    Payment\Gateway::GATEWAY_RESPONSE   => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS       => $this->getGatewayData($response)
                ]);
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
            $orderAmount = $content[ResponseFields::ORDER_TOTAL][ResponseFields::ORDER_AMOUNT];

            $actualAmount = number_format($orderAmount, 2, '.', '');

            $expectedAmount = $this->formatAmount($payment['amount']);

            $verify->amountMismatch = ($expectedAmount !== $actualAmount);
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
                                                        ->toISOString(),
            RequestFields::VERIFY_END_TIME      => Carbon::now()
                                                        ->toISOString(),
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
            RequestFields::CURRENCY_CODE     => $input['payment']['currency'],
            RequestFields::ORDER_ID          => $input['payment']['id'],

            // Optional fields
            RequestFields::IS_SANDBOX        => $this->isSandbox() ? 'true' : 'false',
            RequestFields::TXN_TIMEOUT       => Constant::TIMEOUT,

            // Merchant Based ( Max size is 255 char for both )
            RequestFields::SELLER_NOTE       => $input['merchant']->getFilteredDba(),
            RequestFields::SELLER_STORE_NAME => $input['merchant']->getFilteredDba(),
        ];

        // Callback Url needs to whitelisted at Amazon, thus can't use payment's callback url
        $callbackUrl = $this->route->getUrl('gateway_payment_callback_amazonpay');

        // The SDK is used to add a signature to the request, as well as encrypt the request
        // The signature serves as an extra layer of authentication on Amazon's side.
        $relativeUrl = $this->getAmazonPaySdk()->getProcessPaymentUrl($content, $callbackUrl);

        parse_str($relativeUrl, $content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'payment_id' => $input['payment']['id'],
                'gateway'    => $this->gateway,
                'request'    => $content,
                'encrypted'  => $relativeUrl,
            ]);

        return [
            'url'       => $this->getUrl(). '?' . $relativeUrl,
            'method'    => 'get',
            'content'   => [],
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
            Config::SANDBOX     => $this->isSandbox() ? 'true' : 'false',
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

    public function getSecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestSecret();
        }

        return $this->input['terminal']['gateway_terminal_password'];
    }

    protected function isSandbox(): bool
    {
        return ($this->mode === Mode::TEST);
    }

    private function formatAmount($amount): string
    {
        return number_format(floatval($amount / 100), 2, '.', '');
    }

    public function getPaymentIdFromServerCallback($input)
    {
        if (isset($input[ResponseFields::SELLER_ORDER_ID]) === false)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT,
                null,
                null,
                ['input' => $input]);
        }

        $sellerOrderId = substr($input[ResponseFields::SELLER_ORDER_ID],-14);


        return $sellerOrderId;
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

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                ResponseFields::REFUND_TYPE        => $response[ResponseFields::REFUND_TYPE] ?? null,
                ResponseFields::FEE_REFUNDED       => $response[ResponseFields::FEE_REFUNDED] ?? null,
                ResponseFields::REFUND_STATE       => $response[ResponseFields::REFUND_STATUS]
                                                                [ResponseFields::REFUND_STATE] ?? null,
                ResponseFields::REFUND_AMOUNT      => $response[ResponseFields::REFUND_AMOUNT] ?? null,
                ResponseFields::REFUND_REF_ID      => $response[ResponseFields::REFUND_REF_ID] ?? null,
                ResponseFields::AMAZON_REFUND_ID   => $response[ResponseFields::AMAZON_REFUND_ID] ?? null,
                ResponseFields::CREATION_TIMESTAMP => $response[ResponseFields::CREATION_TIMESTAMP] ?? null,
            ];
        }

        return [];
    }
}
