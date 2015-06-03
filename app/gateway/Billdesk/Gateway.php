<?php

namespace Gateway\Billdesk;

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
            'SecurityID'                => 'NG-NA',
            'Unknown4'                  => 'NA',
            'Unknown5'                  => 'NA',
            'TypeField2'                => 'F',
            'AdditionalInfo1'           => $input['payment']['id'],
            'Unknown6'                  => 'NA',
            'Unknown7'                  => 'NA',
            'Unknown8'                  => 'NA',
            'Unknown9'                  => 'NA',
            'Unknown10'                  => 'NA',
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

        sd($content);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = []; // Fetch billdesk payment

        // Format YYYYMMDD
        $date = Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata');
        $date = $date->format('Ymd');

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmdd0hhmmss
        $now = Carbon::now('Asia/Kolkata')->format('Ymd0His');

        $content = array(
            'RequestType'       => '0400',
            'MerchantID'        => $input['terminal']['gateway_merchant_id'],
            'TxnReferenceNo'    => $payment['gateway_payment_id'],
            'TxnDate'           => $date,
            'CusotmerID'        => $input['payment']['id'],
            'RefAmount'         => $input['refund']['amount'],
            'RefDateTime'       => $now,
            'MerchantRefNo'     => $input['refund']['id'],
            'Filler1'           => 'NA',
            'Filler2'           => 'NA',
            'Filler3'           => 'NA',
        );

        $request = $this->getRequestArray($content);

        $response = $this->postRequest($request);

        sd($response->body);

        $content = $this->getContentAfterChecksumVerification($str);
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

        $request = $this->getRequestArray($content);

        $response = $this->postRequest($request);

        sd($response->body);

        $content = explode('|', $content);
        $content = array_join(self::$verifyResponseFields, $content);
    }

    protected function getContentAfterChecksumVerification($msg)
    {
        $fields = $this->getFieldsForAction($this->action);

        $content = explode('|', $content);
        $content = array_join(self::$verifyResponseFields, $content);

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

        $generatedHash = $this->generateHash($content);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getRequestMessageString($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $str . '|' . $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return strtoupper(hash_hmac('sha256', $str, $secret, false));
    }

    protected function getRequestArray($content)
    {
        $msg = $this->getRequestMessageString($content);
//s($content, $msg);
        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => ['msg' => $msg],
        );

        return $request;
    }
}