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
use RZP\Models\Merchant;
use RZP\Models\Payment;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'billdesk';

    protected $response;
    const CHECKSUM_ATTRIBUTE = 'Checksum';

    protected $tpv;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

        $request = $this->getRequestArrayForAuthorize($content);

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

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);

        // We should ensure once that AuthStatus is 0300 and
        // RefundStatus is null.

        // assert ($payment['RefStatus'] === null);
        assert ($gatewayPayment['AuthStatus'] === AuthStatus::SUCCESS);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input
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
                        $content['CustomerID'], Action::AUTHORIZE);

        $content['received'] = 1;
        $gatewayPayment->fill($content);
        $this->repo->saveOrFail($gatewayPayment);

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

        $refund = $this->createGatewayPaymentEntity($response);

        if ($response['ProcessStatus'] !== 'Y')
        {
            $alreadyRefunded = $this->checkIfAlreadyRefunded($response, $input);

            if ($alreadyRefunded === true)
            {
                $this->trace->warning(
                    TraceCode::GATEWAY_ALREADY_REFUNDED,
                    [
                        'error_code'        => $response['ErrorCode'],
                        'process_status'    => $response['ProcessStatus'],
                        'response'          => $response,
                        'input'             => $input,
                    ]);

                return;
            }

            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                $response);

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

    protected function checkIfAlreadyRefunded(array $response, array $input)
    {
        //
        // NOTE: Billdesk is NOT going to throw this error if the
        // attempted refund is less than [transaction_amount - {refunds so far}]
        // It will, instead, do an actual refund.
        // This error is thrown only when the total refund
        // equals/exceeds the total payment.
        //
        if ($response['ErrorCode'] === 'ERR_REF010')
        {
            return $this->validateAlreadyRefundedByApi($input);
        }

        if ($response['ErrorCode'] === 'ERR_REF009')
        {
            return $this->validateAutoRefundedByBilldesk($response, $input);
        }

        return false;
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
        $now = Carbon::now('Asia/Kolkata')->format('Ymd0His');

        $input = $verify->input;

        $content = [
            'RequestType'   => '0122',
            'Merchant ID'   => $input['terminal']['gateway_merchant_id'],
            'Customer ID'   => $input['payment']['id'],
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
        $date = Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata');
        $date = $date->format('Ymd');

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmddhhmmss,
        // hh is in 24 hrs
        $now = Carbon::now('Asia/Kolkata')->format('YmdHis');

        $refundAmount = (float) ($input['refund']['amount']);

        // The amount should have exact two decimal places, otherwise billdesk gives error
        $refundAmount = (string) number_format($refundAmount/100, 2, '.', '');
        $txnAmount = (string) number_format($payment['TxnAmount'], 2, '.', '');

        $content = [
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

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [$response->body]);

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

    protected function getContentAfterChecksumVerification($responseBody)
    {
        $fields = $this->getFieldsForAction($this->action);

        $this->trace->info(
            TraceCode::GATEWAY_CHECKSUM_VERIFY,
            [$responseBody]);

        $content = explode('|', $responseBody);

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
            'Unknown6'                  => 'NA',
            'Unknown7'                  => 'NA',
            'Unknown8'                  => 'NA',
            'Unknown9'                  => 'NA',
            'Unknown10'                 => 'NA',
            'Unknown11'                 => 'NA',
            'RU'                        => $input['callbackUrl'],
        ];

        // Change Content for Merchants with TPV Required
        if ($this->isTPVEnabled())
        {
            if (isset($input['order']['account_number']) === false)
            {
                throw new Exception\LogicException(
                    'Bank account number should have been present');
            }

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
        $this->repo->saveOrFail($payment);

        $this->setTpv($payment);

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
        if ($this->isTPVEnabled())
        {
            return $this->config['live_access_code_sec'];
        }

        return $this->config['live_access_code'];
    }

    public function getSecret()
    {
        if ($this->isTPVEnabled())
        {
            $this->trace->info(TraceCode::GATEWAY_TERMINAL_TPV);

            return $this->config['live_hash_secret_sec'];
        }

        return $this->config['live_hash_secret'];
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
                assert ($this->input['terminal']->isTpv() === true);

                return true;
            }

            // If merchant is not tpv then terminal should also not be tpv
            assert ($this->input['terminal']->isNotTpv() === true);
        }

        return false;
    }

    protected function setTpv(Entity $gatewayPayment)
    {
        $this->tpv = $gatewayPayment->isTpv();
    }

    public function isPaymentTpvEnabled(Entity $gatewayPayment, Payment\Entity $payment)
    {
        if (($gatewayPayment->isTpv()))
        {
            return true;
        }

        return false;
    }
}
