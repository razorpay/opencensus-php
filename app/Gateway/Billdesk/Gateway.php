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
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'billdesk';

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
        parent::callback($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
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

        $payment = $this->getRepo()->findByPaymentIdAndAction(
                        $content['CustomerID'], Action::AUTHORIZE);

        $content['received'] = 1;
        $payment->fill($content);
        $payment->saveOrFail();

        if ($content['AuthStatus'] !== AuthStatus::SUCCESS)
        {
            // Payment fails, throw exception
            $e = new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    $content['AuthStatus'],
                    '');

            // Set 2fa error if 2fa failed
            if ($this->getTwoFaStatus($content['AuthStatus']) === Payment\TwoFaStatus::FAILED)
            {
                $e->markTwoFaError();
            }

            throw $e;
        }

        assert($content['CustomerID'] === $input['payment']['id']);

        return $this->getCallbackResponseData($content);
    }

    protected function getCallbackResponseData(array $content)
    {
        $twoFaStatus = $this->getTwoFaStatus($content['AuthStatus']);

        $data = array(Payment\Entity::TWO_FA_STATUS => $twoFaStatus);

        return $data;
    }

    protected function getTwoFaStatus($authStatus)
    {
        return AuthStatus::getTwoFaStatus($authStatus);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
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
            TraceCode::GATEWAY_CHECKSUM_VERIFY,
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

    protected function verifySecureHash($content)
    {
        $hash = $content['Checksum'];
        unset($content['Checksum']);

        $generatedHash = $this->getHashOfArray($content);

        if ($generatedHash !== $hash)
        {
            $this->trace->info(
                TraceCode::GATEWAY_CHECKSUM_VERIFY,
                [$content, $hash, $generatedHash]);

            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
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
            TraceCode::GATEWAY_CHECKSUM_VERIFY,
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
