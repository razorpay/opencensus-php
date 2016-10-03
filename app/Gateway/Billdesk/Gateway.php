<?php

namespace RZP\Gateway\Billdesk;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Billdesk;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'billdesk';

    const CHECKSUM_ATTRIBUTE = 'Checksum';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $payment = $this->createGatewayPaymentEntity($content);

        $request = $this->getRequestArrayForAuthorize($content, $input);

        $this->traceGatewayPaymentRequest($request, $input);

        // Ideally, we could have returned the request array from
        // here only.
        //
        // However, we prevent one network call on client side by
        // doing it on the server side here.

        $request = $this->makeRequestAndGetFormData($request);

        return $request;
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $payment = $this->repo->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);

        // We should ensure once that AuthStatus is 0300 and
        // RefundStatus is null.

        // assert ($payment['RefStatus'] === null);
        assert ($payment['AuthStatus'] === AuthStatus::SUCCESS);
    }

    public function callback(array $input)
    {
        parent::callback($input);

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

        $payment = $this->repo->findByPaymentIdAndAction(
                        $content['CustomerID'], Action::AUTHORIZE);

        $content['received'] = 1;
        $payment->fill($content);
        $payment->saveOrFail();

        if ($content['AuthStatus'] !== AuthStatus::SUCCESS)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    $content['AuthStatus'],
                    '');
        }

        assertTrue($content['CustomerID'] === $input['payment']['id']);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->repo->findByPaymentIdAndAction(
                                $input['payment']['id'], Action::AUTHORIZE);

        $content = $this->getPaymentRefundRequestContent($payment, $input);

        $content = $this->postRequest($content);

        $content['refund_id'] = $input['refund']['id'];
        $content['CurrencyType'] = 'INR';
        $content['received'] = 1;
        $refund = $this->createGatewayPaymentEntity($content);

        if ($content['ProcessStatus'] !== 'Y')
        {
            //
            // For very very few transactions, the payment status on billdesk changes
            // after 1 whole day. These are automatically refunded by billdesk.
            // So, the AuthStatus changes to 0300 but RefundStatus also changes to 0699.
            // In that case, we need to let the refund go ahead.

            $refundAmount = (int) ($payment['RefAmount'] * 100);

            if (($content['ErrorCode'] === 'ERR_REF009') and
                ($payment['RefStatus'] === RefundStatus::CANCELLED) and
                ($refundAmount === $input['payment']['amount']))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_REFUND,
                    [
                        'message' => 'Payment was already cancelled at this point by billdesk',
                        'payment_id' => $input['payment']['id']
                    ]);

                return;
            }

            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                [$content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED);
        }
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

        return $content['CustomerID'];
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

        if ($gatewayRefundEntity === null)
        {
            $applicable = true;

            list($refunded, $verifyResponse) = $this->verifyIfRefunded($input);

            if ($refunded === true)
            {
                $refundContent = $this->getRefundContentForGatewayEntity($input, $verifyResponse);

                $this->createGatewayPaymentEntity($refundContent);

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

                $this->trace->error(
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
     * the one with 15 fails due to some server issue on Billdesk side and that times out on our end.
     * Now, since the one with 15 was timed out, we mark it as refunded in API and run the following flow.
     * This below function will return back with TRUE because the refund amount totals 15. We will end up
     * creating a refund entity on the gateway side even when we are not supposed to!
     *
     * @param array $input
     * @return bool
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
            ($verifyResponse['CustomerID'] !== $input['payment'][Payment\Entity::ID]))
        {
            return [false, $verifyResponse];
        }

        $gatewayRefundAmount = (int) ($verifyResponse['RefAmount'] * 100);

        return [($gatewayRefundAmount === $input['payment']['refunded_amount']), $verifyResponse];
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

        $txnDate = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT], 'Asia/Kolkata');
        $txnDate = $txnDate->format('Ymd');

        $refDate = Carbon::createFromTimestamp($input['refund'][Payment\Refund\Entity::CREATED_AT], 'Asia/Kolkata');
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
            //'ErrorStatus'       => $verifyResponse['ErrorStatus'],
            //'ErrorDescription'  => $verifyResponse['ErrorDescription'],
            'ProcessStatus'     => $verifyResponse['ProcessStatus'],
            'TxnDate'           => $txnDate,
            'RefDateTime'       => $refDate,
        ];

        return $refundContent;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $status = VerifyResult::STATUS_MATCH;

        if ($content['QueryStatus'] !== QueryStatus::Y)
        {
            $this->verifyPaymentNonExistentCase($content, $verify, $payment);
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
            $payment->saveOrFail();
        }

        return $status;
    }

    protected function verifyPaymentNonExistentCase($content, $verify, $payment)
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
            $refAmount = (int) $content['RefAmount'] * 100;

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

            $amountRefunded = (int) ($content['RefAmount'] * 100);

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
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $content = $this->postRequest($content);

        unset($content['Checksum']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $verify->verifyResponse = $this->response;
        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestContentArray($verify)
    {
        // Format yyyymmdd24hhmmss (in docs), actually yyyymmdd0hhmmss
        $now = Carbon::now('Asia/Kolkata')->format('Ymd0His');

        $input = $verify->input;

        $content = array(
            'RequestType'   => '0122',
            'Merchant ID'   => $input['terminal']['gateway_merchant_id'],
            'Customer ID'   => $input['payment']['id'],
            'Current Date/ Timestamp' => $now,
        );

        if ($this->mode === Mode::TEST)
        {
            $content['Merchant ID'] = $this->getTestMerchantId();
        }

        return $content;
    }

    protected function getPaymentRefundRequestContent($payment, $input)
    {
        // Format YYYYMMDD
        $date = Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata');
        $date = $date->format('Ymd');

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmddhhmmss,
        // hh is in 24 hrs
        $now = Carbon::now('Asia/Kolkata')->format('YmdHis');

        $refundAmount = (float) ($input['refund']['amount']);

        // The amount should have exact two decimal places, otherwise billdesk gives error
        $refundAmount = (string) number_format($refundAmount/100, 2, '.', '');
        $txnAmount = (string) number_format($payment['TxnAmount'], 2, '.', '');

        $content = array(
            'RequestType'       => '0400',
            'MerchantID'        => $input['terminal']['gateway_merchant_id'],
            'TxnReferenceNo'    => $payment['TxnReferenceNo'],
            'TxnDate'           => $date,
            'CustomerID'        => $input['payment']['id'],
            'TxnAmount'         => $txnAmount,
            'RefAmount'         => $refundAmount,
            'RefDateTime'       => $now,
            'MerchantRefNo'     => $input['refund']['id'],
            'Filler1'           => 'NA',
            'Filler2'           => 'NA',
            'Filler3'           => 'NA',
        );

        if ($this->mode === Mode::TEST)
        {
            $content['MerchantID'] = $this->getTestMerchantId();
        }

        return $content;
    }

    protected function makeRequestAndGetFormData($request)
    {
        $response = $this->sendGatewayRequestForBilldeskAuthorize($request);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [$response->body]);

        $crawler = new Crawler($response->body, $request['url']);

        $formCrawler = $crawler->filter('form');

        if ($formCrawler->count() === 0)
        {
            throw new Exception\GatewayTimeoutException('Gateway Timed Out', null, true);
        }

        $form = $formCrawler->form();

        $method = $form->getMethod();

        $request = array(
            'url' => $form->getUri(),
            'method' => strtolower($method),
            'content' => $form->getValues(),
        );

        return $request;
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
        $request['options']['timeout'] = 30;

        try
        {
            $response = $this->sendGatewayRequest($request);
        }
        catch (\Requests_Exception $e)
        {
            throw new Exception\RuntimeException(
                'Billdesk payment verification request failed.', null, $e);
        }

        $this->response = $response;

        $statusCode = $response->status_code;
        if ($statusCode !== 200)
        {
            if ($statusCode === 504)
            {
                throw new Exception\GatewayTimeoutException(
                    'Http status code - 504');
            }

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                'Wrong status code: ' . $response->status_code);
        }

        $content = $this->getContentAfterChecksumVerification($response->body);

        return $content;
    }

    protected function getContentAfterChecksumVerification($msg)
    {
        $fields = $this->getFieldsForAction($this->action);

        $this->trace->info(
            TraceCode::GATEWAY_CHECKSUM_VERIFY,
            [$msg]);

        $content = explode('|', $msg);

        $content = array_combine($fields, $content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [$content]);

        $this->verifySecureHash($content);

        return $content;
    }

    protected function getAuthRequestContentArray($input)
    {
        $bankId = BankCodes::$bankCodeMap[$input['payment']['bank']];

        $content = array(
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
            'Unknown6'                  => 'NA',
            'Unknown7'                  => 'NA',
            'Unknown8'                  => 'NA',
            'Unknown9'                  => 'NA',
            'Unknown10'                 => 'NA',
            'Unknown11'                 => 'NA',
            'RU'                        => $input['callbackUrl'],
        );

        // Change Content for Merchants with TPV Required
        if ($input['merchant']->isTPVRequired())
        {
            $content['AccountNumber'] = $input['order']['account_number'];
        }

        if ($this->mode === Mode::TEST)
        {
            $content['MerchantID'] = $this->getTestMerchantId();
            $content['SecurityID'] = $this->getTestAccessCode();
            $content['TxnAmount'] = '5.00';
        }

        return $content;
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['CustomerID']);

        $payment->fill($attributes);
        $payment->setAction($this->action);
        $payment->saveOrFail();

        return $payment;
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

        $request['options']['proxy'] = 'https://splunk.razorpay.com:8888';

        return $request;
    }

    protected function getRequestArrayForAuthorize($content, $input)
    {
        $request = $this->getRequestArray($content, $input);

        $request['content']['hidRequestId'] = 'PGIME1000';
        $request['content']['hidOperation'] = 'ME100';

        return $request;
    }

    protected function getRequestArray($content, $input = null)
    {
        $msg = $this->getMessageStringWithHash($content);

        $this->trace->info(
            TraceCode::GATEWAY_CHECKSUM_VERIFY_REQUEST,
            [$msg]);

        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => ['msg' => $msg],
        );

        return $request;
    }

    protected function getSecurityId()
    {
        if ($this->input['merchant']->isTPVRequired())
        {
            return $this->config['live_access_code_sec'];
        }

        return $this->config['live_access_code'];
    }

    protected function getLiveSecret()
    {
        if ($this->input['merchant']->isTPVRequired())
        {
            return $this->config['live_hash_secret_sec'];
        }

        return $this->config['live_hash_secret'];
    }
}
