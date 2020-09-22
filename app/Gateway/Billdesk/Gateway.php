<?php

namespace RZP\Gateway\Billdesk;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Billdesk;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Gateway\Base\ScroogeResponse;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'billdesk';

    protected $response;
    const CHECKSUM_ATTRIBUTE = 'Checksum';

    protected $accountType;

    protected $tpv;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($content, $input);

        $request = $this->getRequestArrayForAuthorize($content);

        $this->traceGatewayPaymentRequest($request, $input);

        // Ideally, we could have returned the request array from
        // here only.
        //
        // However, we prevent one network call on client side by
        // doing it on the server side here.

        $request = $this->makeRequestAndGetFormData($request);

        $this->checkForErrors($input, $request);

        $this->updateUrlInCacheAndPushMetric($input, $request['url']);

        return $request;
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);

        // We should ensure once that AuthStatus is 0300 and
        // RefundStatus is null.

        // assert ($payment['RefStatus'] === null);
        assertTrue ($gatewayPayment['AuthStatus'] === AuthStatus::SUCCESS);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway' => 'billdesk',
                'payment_id' => $input['payment']['id'],
                'content' => $input['gateway'],
            ]
        );

        $msg = $input['gateway']['msg'];

        $content = $this->getContentAfterChecksumVerification($msg);

        if ($content['CustomerID'] === 'NA')
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    $content['AuthStatus'],
                    '');
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);

        $content['received'] = 1;
        $gatewayPayment->fill($content);
        $this->repo->saveOrFail($gatewayPayment);

        if ($content['AuthStatus'] !== AuthStatus::SUCCESS)
        {
            $errorCode = Billdesk\ErrorCode::getMappedCode(
                $content['ErrorStatus'],
                $content['ErrorDescription']);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    $errorCode,
                    $content['AuthStatus'],
                    $content['ErrorDescription']);
        }

        if ($input['terminal']['procurer'] === 'merchant')
        {
            $this->assertPaymentId($input['payment']['id'], $content['AdditionalInfo3']);
        }
        else
        {
            $this->assertPaymentId($input['payment']['id'], $content['CustomerID']);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($content['TxnAmount'], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $gatewayPayment->getBankPaymentId()
            ]
        ];
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                                $input['payment']['id'], Action::AUTHORIZE);

        $this->setTpv($gatewayPayment);

        $requestContent = $this->getPaymentRefundRequestContent($gatewayPayment, $input);

        // This may throw a gateway timeout exception or
        // gateway request exception. These exceptions bubble up to api's
        // refund processor and are handled there.
        $response = $this->postRequest($requestContent);

        $response['refund_id'] = $input['refund']['id'];
        $response['CurrencyType'] = 'INR';
        $response['received'] = 1;

        $this->createGatewayPaymentEntity($response, $input);

        if ($response['ProcessStatus'] !== 'Y')
        {
            $alreadyRefunded = $this->checkIfAlreadyRefunded($response, $input);

            if ($alreadyRefunded === true)
            {
                $this->trace->warning(
                    TraceCode::GATEWAY_ALREADY_REFUNDED,
                    [
                        'error_code'     => $response['ErrorCode'],
                        'process_status' => $response['ProcessStatus'],
                        'response'       => $response,
                        'refund_id'      => $input['refund']['id'],
                    ]);

                return [
                    Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
                ];
            }

            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                $response);

            $errorCode   = $response[Fields::ERROR_CODE] ?? null;
            $errorReason = $response[Fields::ERROR_REASON] ?? null;

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $errorCode,
                $errorReason,
                [
                    Payment\Gateway::GATEWAY_RESPONSE   => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS       => $this->getGatewayData($response)
                ]);
        }

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
        ];
    }

    protected  function getGatewayData(array $refundFields)
    {
        if (empty($refundFields) === false)
        {
            return [
                Fields::REFUND_ID          => $refundFields[Fields::REFUND_ID] ?? null,
                Fields::REF_STATUS         => $refundFields[Fields::REF_STATUS] ?? null,
                Fields::ERROR_CODE         => $refundFields[Fields::ERROR_CODE] ?? null,
                Fields::MERCHANT_ID        => $refundFields[Fields::MERCHANT_ID] ?? null,
                Fields::REQUEST_TYPE       => $refundFields[Fields::REQUEST_TYPE] ?? null,
                Fields::ERROR_REASON       => $refundFields[Fields::ERROR_REASON] ?? null,
                Fields::PROCESS_STATUS     => $refundFields[Fields::PROCESS_STATUS] ?? null,
                Fields::TXN_REFERENCE_NO   => $refundFields[Fields::TXN_REFERENCE_NO] ?? null,
            ];
        }

        return [];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function getPaymentIdFromServerCallback($input)
    {
        $msg = $input['msg'];

        $fields = $this->getFieldsForAction('callback');

        $content = explode('|', $msg);

        $content = array_combine($fields, $content);

        // in case of terminals procurred by merchant, we sent payment id in AdditionalInfo3.
        // Additionalinfo1 and customerID will have details specific to merchant
        // In normal flow we send payment id in both Additionalinfo1 and CustomerID
        if ($content['AdditionalInfo1'] !== $content['CustomerID'])
        {
            $paymentId = $content['AdditionalInfo3'];
        }
        else
        {
            $paymentId = $content['CustomerID'];
        }

        return $paymentId;
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $refundedEntities = $this->repo->findRefundByRefundId($refundId);

        if ($refundedEntities->count() === 0)
        {
            return false;
        }

        $refundEntity = $refundedEntities->first();

        $refundEntityPaymentId = $refundEntity->getPaymentId();
        $refundEntityRefundAmount = (int) ($refundEntity->getRefundAmount() * 100);
        $processStatus = $refundEntity->getProcessStatus();
        $refundStatus = $refundEntity->getRefStatus();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input' => $input,
                'refund_payment_id' => $refundEntityPaymentId,
                'gateway_refund_amount' => $refundEntityRefundAmount,
                'process_status' => $processStatus,
                'refund_status' => $refundStatus,
            ]);

        if (($refundEntityPaymentId !== $paymentId) or
            ($refundEntityRefundAmount !== $refundAmount) or
            ($processStatus !== 'Y') or
            (($refundStatus !== RefundStatus::REFUNDED) and
             ($refundStatus !== RefundStatus::CANCELLED)))
        {
            return false;
        }

        return true;
    }

    /**
     * This only handles for payments which have exactly one refund (either full or partial).
     * Currently, I don't see a way where we can handle this for multiple partial refunds too.
     * And since timeouts are a rare case, I think this is fine.
     * The worst that can happen is we don't have a corresponding entity in the gateway.
     *
     * @param array $input
     * @return array
     */
    public function createRefundRecord(array $input)
    {
        $refundId = $input['refund'][Payment\Refund\Entity::ID];

        $paymentId = $input['payment'][Payment\Entity::ID];

        $gatewayRefundEntity = $this->repo->findByRefundId($refundId);

        $applicable = false;
        $success = null;

        // temporary fix: do not run this for payments done through nbplus service
        // once new service is live, refunds will go through scrooge
        if (($gatewayRefundEntity === null) and
            ($input['payment'][Payment\Entity::CPS_ROUTE] === Payment\Entity::API))
        {
            $applicable = true;

            $this->action = Action::VERIFY;
            list($refunded, $verifyResponse) = $this->verifyIfRefunded($input);

            if ($refunded === true)
            {
                $refundContent = $this->getRefundContentForGatewayEntity($input, $verifyResponse);

                $this->action = Action::REFUND;
                $this->createGatewayPaymentEntity($refundContent, $input);

                $success = true;

                $this->trace->info(
                    TraceCode::GATEWAY_REFUND_RECORD_CREATED,
                    [
                        'payment_id' => $paymentId,
                        'refund_id'  => $refundId
                    ]);
            }
            else
            {
                $success = false;

                // It should have been refunded on the gateway side also. But, verify returned
                // false in the verify response for refund.

                $this->trace->critical(
                    TraceCode::GATEWAY_REFUND_ABSENT,
                    [
                        'refund_id'         => $refundId,
                        'payment_id'        => $paymentId,
                        'verify_response'   => $verifyResponse,
                    ]);
            }
        }

        return [
            'applicable'    => $applicable,
            'success'       => $success,
            'refund_id'     => $refundId,
            'payment_id'    => $input['payment'][Payment\Entity::ID]
        ];
    }

    protected function checkIfAlreadyRefunded(array $response, array $input)
    {
        // Billdesk returns ERR_REF013 when a duplicate reference id
        // is used to do any refund. This way we can identify if a
        // refund has already been processed or not
        if ($response['ErrorCode'] === Billdesk\ErrorCode::ERR_REF013)
        {
            return true;
        }
        //
        // NOTE: Billdesk is NOT going to throw this error if the
        // attempted refund is less than [transaction_amount - {refunds so far}]
        // It will, instead, do an actual refund.
        // This error is thrown only when the total refund
        // equals/exceeds the total payment.
        //
        if ($response['ErrorCode'] === Billdesk\ErrorCode::ERR_REF010)
        {
            return $this->validateAlreadyRefundedByApi($input);
        }

        if ($response['ErrorCode'] === Billdesk\ErrorCode::ERR_REF009)
        {
            return $this->validateAutoRefundedByBilldesk($response, $input);
        }

        return false;
    }

    /**
     * Conditions which we use to determine if a payment has been refunded by Billdesk
     * If the query status is not Y, return false.
     * If auth status is not success, return false.
     * If ref status is neither refunded nor cancelled, return false.
     * If ref amount is not equal to api's ref amount, return false.
     *
     * DISCLAIMER: Will not work as expected in the following case:
     * There are 3 partial refunds with amounts 5, 10 and 15.
     * The refunds with 5 and 10 go through successfully and
     * the one with 15 fails due to some server issue on Billdesk side and that times out (db lock) on our end.
     * Now, since the one with 15 was timed out, we mark it as refunded in API and run the following flow.
     * This below function will return back with TRUE because the refund amount totals 15. We will end up
     * creating a refund entity on the gateway side even when we are not supposed to!
     *
     * @param array $input
     *
     * @return array
     */
    protected function verifyIfRefunded(array $input)
    {
        $verify = new Base\Verify($this->gateway, $input);

        $verify->payment = $this->repo->findByPaymentIdAndAction(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $verifyResponse = $this->sendPaymentVerifyRequest($verify);

        if (($verifyResponse['QueryStatus'] !== QueryStatus::Y) or
            ($verifyResponse['AuthStatus'] !== AuthStatus::SUCCESS) or
            (($verifyResponse['RefStatus'] !== RefundStatus::REFUNDED) and
             ($verifyResponse['RefStatus'] !== RefundStatus::CANCELLED)) or
            ($verifyResponse['CustomerID'] !== $verify->payment->getCustomerId()))
        {
            return [false, $verifyResponse];
        }

        $gatewayRefundAmount = (int) ($verifyResponse['RefAmount'] * 100);

        $totalApiRefundAmount = $input['payment'][Payment\Entity::AMOUNT_REFUNDED];

        return [($gatewayRefundAmount === $totalApiRefundAmount), $verifyResponse];
    }

    /**
     * Constructs the refund data, whatever is available from the input and the verifyResponse.
     *
     * NOTE: We do not have any way to get the Billdesk Refund ID currently. Verify response
     * does not contain the refund ID. Same with ErrorCode and ErrorReason.
     *
     * @param array $input
     * @param array $verifyResponse
     * @return array
     */
    protected function getRefundContentForGatewayEntity(array $input, array $verifyResponse)
    {
        $refStatus = $verifyResponse['RefStatus'];

        $txnDate = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT], Timezone::IST);
        $txnDate = $txnDate->format('Ymd');

        $refDate = Carbon::createFromTimestamp($input['refund'][Payment\Refund\Entity::CREATED_AT], Timezone::IST);
        $refDate = $refDate->format('YmdHis');

        $refundContent = [
            'payment_id'        => $input['payment'][Payment\Entity::ID],
            'refund_id'         => $input['refund'][Payment\Refund\Entity::ID],
            'received'          => 0,
            'CurrencyType'      => 'INR',
            'CustomerID'        => $verifyResponse['CustomerID'],
            'MerchantID'        => $verifyResponse['MerchantID'],
            'refund_status'     => RefundStatus::$statusMap[$refStatus],
            'RefStatus'         => $refStatus,
            // The refund request's request type is 0400. But, when we get the response back,
            // it's 0410. We store that in the normal refund flow.
            'RequestType'       => '0410',
            'TxnAmount'         => $verifyResponse['TxnAmount'],
            'TxnReferenceNo'    => $verifyResponse['TxnReferenceNo'],
            'RefAmount'         => $input['refund'][Payment\Refund\Entity::AMOUNT],
            // The below two fields are not sent as part of refund response, but we get it in the verify response.
            // 'ErrorStatus'       => $verifyResponse['ErrorStatus'],
            // 'ErrorDescription'  => $verifyResponse['ErrorDescription'],
            // This is not received in verify response. This indicates whether refund was successful.
            'ProcessStatus'     => 'Y',
            'TxnDate'           => $txnDate,
            'RefDateTime'       => $refDate,
        ];

        return $refundContent;
    }

    /**
     * It is possible that a refund was successful on Billdesk and
     * we even created a record in the Billdesk Entity, but, due to some reason,
     * it failed on the API side and we don't have a record of it.
     * Billdesk sends an error code of ERR_REF010 when we try to refund it again.
     *
     * @param array $input
     * @return bool
     */
    protected function validateAlreadyRefundedByApi(array $input)
    {
        $refundAmount = $input['amount'];

        // We check whether we have a refund record for this particular
        // payment already in the Billdesk entity.

        $refundRecords = $this->repo->getSuccessfulRefundRecordForThePayment(
            $input['payment'][Payment\Entity::ID]);

        if (empty($refundRecords) === true)
        {
            return false;
        }

        foreach ($refundRecords as $refundRecord)
        {
            $recordedRefundAmount = $refundRecord->getRefundAmount() * 100;

            if ($recordedRefundAmount === $refundAmount)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * For very very few transactions, the payment status on billdesk changes
     * after 1 whole day. These are automatically refunded by billdesk.
     * So, the AuthStatus changes to 0300 but RefundStatus also changes to 0699.
     * In that case, we need to let the refund go ahead.
     *
     * @param array $response
     * @param array $input
     * @return bool
     */
    protected function validateAutoRefundedByBilldesk(array $response, array $input)
    {
        $refundAmount = (int) ($response['RefAmount'] * 100);

        if (($response['RefStatus'] === RefundStatus::CANCELLED) and
            ($refundAmount === $input['amount']))
        {
            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_REFUND,
                [
                    'message' => 'Payment was already cancelled at this point by billdesk',
                    'payment_id' => $input['payment']['id']
                ]);

            return true;
        }

        return false;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

        $status = VerifyResult::STATUS_MATCH;

        if ($content['QueryStatus'] !== QueryStatus::Y)
        {
            $this->verifyPaymentNonExistentCase($verify, $payment);
        }
        else if ($content['AuthStatus'] === AuthStatus::SUCCESS)
        {
            $this->verifyPaymentReconcileWithGatewaySuccessResponse($content, $verify, $status);
        }
        else
        {
            $this->verifyPaymentReconcileWithGatewayFailureResponse($content, $verify, $status);
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        if (($payment['received'] === false) or
            ($payment['AuthStatus'] !== $content['AuthStatus']))
        {
            unset(
                $content['TxnAmount'],
                $content['BankID'],
                $content['ItemCode']);

            $payment->fill($content);
            $this->repo->saveOrFail($payment);
        }

        return $status;
    }

    protected function verifyPaymentNonExistentCase($verify, $payment)
    {
        // Could be the case where the transaction didn't even hit billdesk
        if (($payment['received'] === false) and
            (($payment['AuthStatus'] === null) or
             ($payment['AuthStatus'] === AuthStatus::NA)))
        {
            $verify->apiSuccess = false;
            $verify->gatewaySuccess = false;
        }
    }

    protected function verifyPaymentReconcileWithGatewaySuccessResponse($content, $verify, & $status)
    {
        $verify->gatewaySuccess = true;
        $payment = $verify->payment;
        $input = $verify->input;

        if (($payment['AuthStatus'] !== AuthStatus::SUCCESS) or
            ($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $refAmount = (int) ($content['RefAmount'] * 100);

            if (($content['RefStatus'] === RefundStatus::CANCELLED) and
                ($refAmount === $input['payment']['amount']))
            {
                //
                // This is the case where payment actually succeeded
                // when billdesk reconciled on the next day and those payments
                // are automatically cancelled by billdesk as well,
                // meaning it's been automatically refunded.
                //

                $verify->gatewaySuccess = false;
                $verify->apiSuccess = false;
                $status = VerifyResult::STATUS_MATCH;
            }
            else
            {
                $verify->apiSuccess = false;
                $status = VerifyResult::STATUS_MISMATCH;
            }
        }
        else
        {
            $verify->apiSuccess = false;

            $amountRefunded = intval($content['RefAmount']) * 100;

            // Check that refund amount matches.
            if ($amountRefunded !== $verify->input['payment']['amount_refunded'])
            {
                $status = VerifyResult::REFUND_AMOUNT_MISMATCH;
            }
        }
    }

    protected function verifyPaymentReconcileWithGatewayFailureResponse($content, $verify, & $status)
    {
        $verify->apiSuccess = false;
        $verify->gatewaySuccess = false;

        //
        // If payment is not marked as success then it shouldn't be success
        // on billdesk end as well.
        //

        if ($content['AuthStatus'] === AuthStatus::SUCCESS)
        {
            // It's marked as success, in this case, if it's totally refunded,
            // then that means billdesk refunded the payment on it's own end
            // and we don't need to worry.

            if ($amountRefunded === $verify->input['payment']['amount'])
            {
                $status = VerifyResult::STATUS_MATCH;
            }
            else
            {
                $verify->gatewaySuccess = true;
                $status = VerifyResult::STATUS_MISMATCH;
            }
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getPaymentVerifyRequestContentArray($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $content);

        $content = $this->postRequest($content);

        unset($content['Checksum']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $content);

        $verify->verifyResponse = $this->response;
        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestContentArray($verify)
    {
        // Format yyyymmdd24hhmmss (in docs), actually yyyymmdd0hhmmss
        $now = Carbon::now(Timezone::IST)->format('Ymd0His');

        $input = $verify->input;

        $content = [
            'RequestType'   => '0122',
            'Merchant ID'   => $input['terminal']['gateway_merchant_id'],
            'Customer ID'   => $verify->payment->getCustomerId(),
            'Current Date/ Timestamp' => $now,
        ];

        if ($this->mode === Mode::TEST)
        {
            $content['Merchant ID'] = $this->getTestMerchantId();
        }

        $this->setTpv($verify->payment);

        return $content;
    }

    protected function getPaymentRefundRequestContent($payment, $input)
    {
        // Format YYYYMMDD
        $txnDate = Carbon::createFromFormat('d-m-Y H:i:s', $payment['TxnDate'], Timezone::IST);
        $txnDate = $txnDate->format('Ymd');

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmddhhmmss,
        // hh is in 24 hrs
        $now = Carbon::now(Timezone::IST)->format('YmdHis');

        $refundAmount = (float) ($input['refund']['amount']);

        // The amount should have exact two decimal places, otherwise billdesk gives error
        $refundAmount = (string) number_format($refundAmount / 100, 2, '.', '');
        $txnAmount = (string) number_format($payment['TxnAmount'], 2, '.', '');

        $content = [
            'RequestType'       => '0400',
            'MerchantID'        => $input['terminal']['gateway_merchant_id'],
            'TxnReferenceNo'    => $payment['TxnReferenceNo'],
            'TxnDate'           => $txnDate,
            'CustomerID'        => $payment->getCustomerId(),
            'TxnAmount'         => $txnAmount,
            'RefAmount'         => $refundAmount,
            'RefDateTime'       => $now,
            'MerchantRefNo'     => $input['refund']['id'],
            'Filler1'           => 'NA',
            'Filler2'           => 'NA',
            'Filler3'           => 'NA',
        ];

        if ($this->mode === Mode::TEST)
        {
            $content['MerchantID'] = $this->getTestMerchantId();
        }

        return $content;
    }

    protected function makeRequestAndGetFormData($request)
    {
        $response = $this->sendGatewayRequestForBilldeskAuthorize($request);

        $crawler = new Crawler($response->body, $request['url']);

        $formCrawler = $crawler->filter('form');

        if ($formCrawler->count() === 0)
        {
            throw new Exception\GatewayTimeoutException('Gateway Timed Out', null, true);
        }

        $form = $formCrawler->form();

        $method = $form->getMethod();

        $request = [
            'url'     => $form->getUri(),
            'method'  => strtolower($method),
            'content' => $form->getValues(),
        ];

        return $request;
    }

    protected function checkForErrors($input, $request)
    {
        $merchantId = $input['merchant']->getId();

        // Callback check is only enabled for DEMO and TEST Account for now
        $shouldCheck = (($merchantId === Merchant\Account::DEMO_PAGE_ACCOUNT) or
                        ($merchantId === Merchant\Account::TEST_ACCOUNT));

        if (($shouldCheck === true) and
            ($input['callbackUrl'] === $request['url']))
        {
            $rawMsg = $request['content']['msg'];

            $content = $this->getContentAfterChecksumVerification($rawMsg, 'callback');

            if ($content['AuthStatus'] !== AuthStatus::SUCCESS)
            {
                $errorCode = Billdesk\ErrorCode::getMappedCode(
                                    $content['ErrorStatus'],
                                    $content['ErrorDescription']);

                throw new Exception\GatewayErrorException(
                        $errorCode,
                        $content['AuthStatus'],
                        $content['ErrorDescription']);
            }
        }
    }

    /**
     * This function only purpose is so that it can be overridden
     * during testing.
     */
    protected function sendGatewayRequestForBilldeskAuthorize($request)
    {
        return $this->sendGatewayRequest($request);
    }

    protected function postRequest($content)
    {
        $request = $this->getRequestArrayWithProxy($content);
        $request['options']['timeout'] = 60;

        $this->response = $this->sendGatewayRequest($request);

        $content = $this->getContentAfterChecksumVerification($this->response->body);

        return $content;
    }

    protected function getContentAfterChecksumVerification($responseBody, $action = null)
    {
        $action = $action ?: $this->action;

        $fields = $this->getFieldsForAction($action);

        $this->trace->info(
            TraceCode::GATEWAY_CHECKSUM_VERIFY,
            [$responseBody]);

        $content = explode('|', $responseBody);

        /**
         * If Gateway returns data in invalid format,
         * then field count does not matches expected output format column count
         * throw Gateway unknown error exception
         */
        if (count($fields) !== count($content))
        {
            throw new Exception\GatewayErrorException(
                  ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                  null,
                  null,
                  [
                    Payment\Gateway::GATEWAY_RESPONSE  => $responseBody
                  ]);
        }

        $content = array_combine($fields, $content);

        $this->trace->info(
            TraceCode::GATEWAY_CHECKSUM_VERIFY,
            [
                'gateway' => 'billdesk',
                'content' => $content
            ]);

        $this->verifySecureHash($content);

        return $content;
    }

    protected function getAuthRequestContentArray($input)
    {
        $bankIfsc = $input['payment']['bank'];

        $corporate = $input['terminal']['corporate'];

        $bankId = BankCodes::getBankCode($bankIfsc, $corporate);

        $content = [
            'MerchantID'                => $input['terminal']['gateway_merchant_id'],
            'CustomerID'                => $input['payment']['id'],
            'AccountNumber'             => 'NA',
            'TxnAmount'                 => $input['payment']['amount'] / 100,
            'BankID'                    => $bankId,
            'Unknown2'                  => 'NA',
            'Unknown3'                  => 'NA',
            'CurrencyType'              => 'INR',
            'ItemCode'                  => 'DIRECT',
            'TypeField1'                => 'R',
            'SecurityID'                => $this->getSecurityId(),
            'Unknown4'                  => 'NA',
            'Unknown5'                  => 'NA',
            'TypeField2'                => 'F',
            'AdditionalInfo1'           => $input['payment']['id'],
            'AdditionalInfo2'           => 'NA',
            'AdditionalInfo3'           => 'NA',
            'AdditionalInfo4'           => 'NA',
            'AdditionalInfo5'           => 'NA',
            'AdditionalInfo6'           => 'NA',
            'AdditionalInfo7'           => 'NA',
            'RU'                        => $input['callbackUrl'],
        ];

        if (($this->isMerchantProcuredTerminal($input['terminal']) === true) and
            (isset($input['payment']['description']) === true))
        {
            $content['CustomerID'] = substr($input['payment']['description'], 0, 30);
            $content['AdditionalInfo1'] = 'NA';
            $content['AdditionalInfo3'] = $input['payment']['id'];
        }

        // Change Content for Merchants with TPV Required
        if ($this->isTPVEnabled())
        {
            if (isset($input['order']['account_number']) === false)
            {
                throw new Exception\LogicException(
                    'Bank account number should have been present');
            }

            $content['AccountNumber'] = $input['order']['account_number'];

            if ($this->isMerchantProcuredTerminal($input['terminal']) === true)
            {
                $content = $this->addAdditionalInfoBasedOnMerchant($content, $input);
            }
        }

        if ($this->mode === Mode::TEST)
        {
            $content['MerchantID'] = $this->getTestMerchantId();
            $content['SecurityID'] = $this->getTestAccessCode();
        }

        return $content;
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();
        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->fill($attributes);
        $gatewayPayment->setAction($this->action);
        $this->repo->saveOrFail($gatewayPayment);

        $this->setTpv($gatewayPayment);

        return $gatewayPayment;
    }

    public function getMessageStringWithHash($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $str . '|' . $this->getHashOfString($str);
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return strtoupper(hash_hmac('sha256', $str, $secret, false));
    }

    protected function getRequestArrayWithProxy($content)
    {
        $request = $this->getRequestArray($content);

        if ($this->proxyEnabled === true)
        {
            $request['options']['proxy'] = $this->proxy;
        }

        return $request;
    }

    protected function getRequestArrayForAuthorize($content)
    {
        $request = $this->getRequestArray($content);

        $request['content']['hidRequestId'] = 'PGIME1000';
        $request['content']['hidOperation'] = 'ME100';

        return $request;
    }

    protected function getRequestArray(array $content)
    {
        $msg = $this->getMessageStringWithHash($content);

        $this->trace->info(
            TraceCode::GATEWAY_CHECKSUM_VERIFY_REQUEST,
            [$msg]);

        $request = [
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => ['msg' => $msg],
        ];

        return $request;
    }

    protected function getSecurityId()
    {
        $accountType = $this->accountType;

        switch ($accountType)
        {
            case AccountType::PRIMARY:
                $accessCode = $this->config['live_access_code'];
                break;

            case AccountType::SECONDARY:
                $accessCode = $this->config['live_access_code_sec'];
                break;

            default:
                $accessCode = $this->config['live_access_code'];
                break;
        }

        return $this->getLiveGatewayAccessCode() ?: $accessCode;
    }

    public function getSecret()
    {
        $accountType = $this->accountType;

        switch ($accountType)
        {
            case AccountType::PRIMARY:
                $secret = $this->config['live_hash_secret'];
                break;

            case AccountType::SECONDARY:
                $secret = $this->config['live_hash_secret_sec'];
                break;

            default:
                $secret = $this->config['live_hash_secret'];
                break;
        }

        return $this->getLiveSecret() ?: $secret;
    }

    protected function isTPVEnabled()
    {
        if ($this->tpv === true)
        {
            return true;
        }
        else if (isset($this->input['merchant']))
        {
            // If merchant is tpv then terminal should also be tpv
            if ($this->input['merchant']->isTPVRequired())
            {
                assertTrue ($this->input['terminal']->isTpvAllowed() === true);

                return true;
            }

            // If merchant is not tpv then terminal should also not be tpv
            assertTrue ($this->input['terminal']->isNonTpvAllowed() === true);
        }

        return false;
    }

    protected function setTpv(Entity $gatewayPayment)
    {
        $this->tpv = $gatewayPayment->isTpv();
    }

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->setAccountType($terminal);
    }

    protected function setAccountType($terminal)
    {
        $gatewayMerchantId = $terminal['gateway_merchant_id'];

        $prefix = substr($gatewayMerchantId, 0, 2);

        $this->accountType = AccountType::PRIMARY;

        if (isset(AccountType::ACCOUNT_MAP[$prefix]) === true)
        {
            $this->accountType = AccountType::ACCOUNT_MAP[$prefix];
        }
    }

    public function isPaymentTpvEnabled(Entity $gatewayPayment, Payment\Entity $payment)
    {
        if (($gatewayPayment->isTpv()))
        {
            return true;
        }

        return false;
    }

    /**
     * Calls gateway to verify if a refund has
     * been successfully performed or not.
     *
     * @param array $input
     * @return array
     */
    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new ScroogeResponse();

        list($refunded, $verifyResponse) = $this->verifyIfRefunded($input);

        if ($refunded === true)
        {

            return $scroogeResponse->setSuccess(true)
                                   ->setGatewayVerifyResponse($verifyResponse)
                                   ->toArray();
        }
        else
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setGatewayVerifyResponse($verifyResponse)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING)
                                   ->toArray();
        }
    }

    protected function isMerchantProcuredTerminal($terminal)
    {
        return ($terminal['procurer'] === 'merchant');
    }

    protected function addAdditionalInfoBasedOnMerchant($content, $input)
    {
        if ($input['payment']['merchant_id'] === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $content['AdditionalInfo1'] = $input['order']['notes']['FolioNo'] ?? 'NA';
            $content['AdditionalInfo2'] = $input['order']['account_number'] ?? 'NA';
            $content['AdditionalInfo4'] = $input['order']['notes']['BranchCode'] ?? 'NA';
            $content['AdditionalInfo5'] = $input['order']['notes']['Scheme'] ?? 'NA';
        }

        return $content;
    }
}
