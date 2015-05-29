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

        $content = array(
            'MerchantId'                => $input['terminal']['gateway_merchant_id'],
            'CustomerID'                => $input['payment']['id'],
            'Unknown1'                  => 'NA',
            'TxnAmount'                 => $input['payment']['amount'] / 100,
            'BankID'                    => 'IDB',
            'Unknown1'                  => 'NA',
            'Unknown2'                  => 'NA',
            'CurrencyType'              => 'INR',
            'ItemCode'                  => 'DIRECT',
            'TypeField1'                => 'R',
            'SecurityID'                => 'NG_NA',
            'Unknown3'                  => 'NA',
            'Unknown4'                  => 'NA',
            'TypeField2'                => 'F',
            'AdditionalInfo1'           => $input['payment']['id'],
            'Unknown5'                  => 'NA',
            'Unknown6'                  => 'NA',
            'Unknown7'                  => 'NA',
            'Unknown8'                  => 'NA',
            'Unknown9'                  => 'NA',
            'Unknown10'                 => 'NA',
            'RU'                        => $input['callbackUrl'],
        );

        $this->createGatewayPaymentEntity($content);

        return $this->getRequestArray($content);
    }

    protected function callback($input)
    {
        parent::callback($input);

        $msg = $input['gateway']['msg'];

        $content = $this->getContentAfterChecksumVerification($msg);

        sd($content);
    }

    protected function refund($input)
    {
        parent::refund($input);

        $payment = []; // Fetch billdesk payment

        $content = array(
            'RequestType'       => '0400',
            'MerchantID'        => $input['terminal']['gateway_merchant_id'],
            'TxnReferenceNo'    => $payment['gateway_payment_id'],
            'TxnDate'           => '',
            'CusotmerID'        => $input['payment']['id'],
            'RefAmount'         => $input['refund']['amount'],
            'RefDateTime'       => '',
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

    protected function verify($input)
    {
        parent::verify($input);

        // format - 'yyyymmdd24hhmmss'
        $time = date('Ymd24his');

        $content = array(
            'RequestType'   => '0122',
            'Merchant ID'   => $input['terminal']['gateway_merchant_id'],
            'Customer ID'   => $input['payment']['id'],
            'Current Date/ Timestamp' => $time,
        );

        $msg = $this->getRequestMessageString($content);

        $request = array(
            'url' => $this->getUrl(),
            'method' => 'post',
            'content' => ['msg' => $msg],
        );

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

        $payment->saveOrFail();

        return $payment;
    }

    protected function verifySecureHash($content)
    {
        $hash = $content['Checksum'];
        unset($content['CheckSum']);

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

        return 'random';
    }

    protected function getRequestArray($msg)
    {
        $msg = $this->getRequestMessageString($content);

        $request = array(
            'url' => $this->getUrl(),
            'method' => 'post',
            'content' => ['msg' => $msg],
        );

        return $request;
    }
}