<?php

namespace Gateway\Billdesk;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
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

        $this->createGatewayPaymentEntity($content);

        return $this->getRequestArray($content);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $msg = $input['gateway']['msg'];

        $content = $this->getContentAfterChecksumVerification($msg);

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

        $gatewayPaymentSuccess = ($content['AuthStatus'] === AuthStatus::SUCCESS);
        $apiPaymentSuccess = ($payment['AuthStatus'] === AuthStatus::SUCCESS);

        if (($gatewayPaymentSuccess !== $apiPaymentSuccess) or
            ($amountRefunded !== $input['payment']['amount_refunded']))
        {
            $res = array(
                'match' => false,
                'payment' => [$payment->toArray()],
                'gateway_data' => $content,
                'payment_id' => $input['payment']['id'],
                'gateway' => $input['payment']['gateway'],
            );

            throw new Exception\PaymentVerificationException($res);
        }
    }

    protected function postRequest($content)
    {
        $request = $this->getRequestArrayWithProxy($content);

        try
        {
            $response = $this->sendGatewayRequest($request);
        }
        catch (\Requests_Exception $e)
        {
            sd($e);
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