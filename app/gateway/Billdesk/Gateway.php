<?php

namespace Gateway\Billdesk;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\Billdesk;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;

    protected $gateway = 'billdesk';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $bankId = BankCodes::$bankCodeMap[$input['payment']['bank']];

        $content = array(
            'MerchantID'                => $input['terminal']['gateway_merchant_id'],
            'CustomerID'                => $input['payment']['id'],
            'Unknown1'                  => 'NA',
            'TxnAmount'                 => $input['payment']['amount'] / 100,
            'BankID'                    => $bankId,
            'Unknown2'                  => 'NA',
            'Unknown3'                  => 'NA',
            'CurrencyType'              => 'INR',
            'ItemCode'                  => 'DIRECT',
            'TypeField1'                => 'R',
            'SecurityID'                => $this->config['live_access_code'],
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

        if ($this->mode === Mode::TEST)
        {
            $content['MerchantID'] = $this->getTestMerchantId();
            $content['SecurityID'] = $this->getTestAccessCode();
            $content['TxnAmount'] = '5.00';
        }

        $payment = $this->createGatewayPaymentEntity($content);

        return $this->getRequestArray($content);
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

        assert($content['CustomerID'] === $input['payment']['id']);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
                                $input['payment']['id'], Action::AUTHORIZE);

        // Format YYYYMMDD
        $date = Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata');
        $date = $date->format('Ymd');

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmddhhmmss,
        // hh is in 24 hrs
        $now = Carbon::now('Asia/Kolkata')->format('YmdHis');

        $refundAmount = (float) ($input['refund']['amount']);

        // The amount should have exact two decimal places
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

        $content = $this->postRequest($content);

        $content['refund_id'] = $input['refund']['id'];
        $content['CurrencyType'] = 'INR';
        $refund = $this->createGatewayPaymentEntity($content);

        if ($content['ProcessStatus'] !== 'Y')
        {
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

        $content = $this->getPaymentGatewayVerifyContent($input);

        $status = $this->verifyPaymentFields($input, $content);

        if ($status === VerifyResult::STATUS_MATCH)
        {
            return;
        }

        $res = array(
            'match' => false,
            'status' => $status,
            'gateway_data' => $content,
            'gateway' => $input['payment']['gateway'],
        );

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $res);

        throw new Exception\PaymentVerificationException($res);
    }

    protected function verifyPaymentFields($input, $content)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);

        $amountRefunded = (int) ($content['TotalRefundAmount'] * 100);

        if ($content['QueryStatus'] !== 'Y')
        {
            $res = array(
                'match' => 'unknown',
                'gateway_data' => $content);

            throw new Exception\PaymentVerificationException($res);
        }

        //
        // Check for success
        //

        $status = VerifyResult::STATUS_MATCH;

        if ($payment['AuthStatus'] === AuthStatus::SUCCESS)
        {
            if ($content['AuthStatus'] === AuthStatus::SUCCESS)
            {
                // Check that refund amount matches.
                if ($amountRefunded !== $input['payment']['amount_refunded'])
                {
                    $status = VerifyResult::REFUND_AMOUNT_MISMATCH;
                }
            }
            else
            {
                $status = VerifyResult::STATUS_MISMATCH;
            }
        }
        else
        {
            //
            // If payment is not marked as success then it shouldn't be success
            // on billdesk end as well.
            //

            if ($content['AuthStatus'] === AuthStatus::SUCCESS)
            {
                // It's marked as success, in this case, if it's totally refunded,
                // then that means billdesk refunded the payment on it's own end
                // and we don't need to worry.

                if ($amountRefunded === $input['payment']['amount'])
                    return $status;

                $status = VerifyResult::STATUS_MISMATCH;
            }
        }

        return $status;
    }

    protected function getPaymentGatewayVerifyContent($input)
    {
        // Format yyyymmdd24hhmmss (in docs), actually yyyymmdd0hhmmss
        $now = Carbon::now('Asia/Kolkata')->format('Ymd0His');

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

        $content = $this->postRequest($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        return $content;
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

        $content = $this->getContentAfterChecksumVerification($response->body);

        return $content;
    }

    protected function getContentAfterChecksumVerification($msg)
    {
        $fields = $this->getFieldsForAction($this->action);

        $content = explode('|', $msg);

        $content = array_combine($fields, $content);

        $this->verifySecureHash($content);

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

    protected function getRequestArray($content)
    {
        $msg = $this->getMessageStringWithHash($content);

        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => ['msg' => $msg],
        );

        return $request;
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }
}