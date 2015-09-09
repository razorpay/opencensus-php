<?php

namespace Gateway\Sbiepay;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;

    protected $gateway = 'sbiepay';

    public function authorize(array $input)
    {
        parent::authorize($input);
        $method = $input['payment']['method'];

        $requestParameter = array(
            'MerchantId'         => $input['terminal']['gateway_merchant_id'],
            'OperatingMode'      => 'DOM',
            'MerchantCountry'    => 'IN',
            'MerchantCurrency'   => 'INR',
            'PostingAmount'      => $input['payment']['amount'] / 100,
            'OtherDetails'       => 'NA',
            'SuccessURL'         => $input['callbackUrl'],
            'FailURL'            => $input['callbackUrl'],
            'AggregatorId'       => 'SBIEPAY',
            'MerchantOrderNo'    => $input['payment']['id'],
            'MerchantCustomerID' => $input['payment']['email'],
            'Paymode'            => 'NB',
            'Accesmedium'        => 'ONLINE',
            'TransactionSource'  => 'ONLINE'
        );
        if ($this->mode === Mode::TEST)
        {
            $requestParameter['MerchantId'] = $this->getTestMerchantId();
            $requestParameter['PostingAmount'] = '5.00';
        }
        if ($method === 'netbanking')
        {
            $aggGtwmapID = BankCodes::$bankCodeMap[$input['payment']['bank']];
            $paymentDetails = [$aggGtwmapID, "", "", "", "", "", "", ""];
            $requestParameter['Paymode'] = 'NB';
        }

        if ($method === 'card')
        {
            if ($input['card']['type'] == 'credit')
            {
                $requestParameter['Paymode'] = 'CC';
                if ($input['card']['network'] == 'Visa')
                {
                    $aggGtwmapID = 2;
                }
                else if ($input['card']['network'] == 'Master')
                {
                    $aggGtwmapID = 1;
                }


            }
            else
            {
                if ($input['card']['type'] == 'debit')
                {
                    $requestParameter['Paymode'] = 'DB';
                    if ($input['card']['network'] == 'Visa')
                    {
                        $aggGtwmapID = 5;
                    }
                    else if ($input['card']['network'] == 'Master')
                    {
                        $aggGtwmapID = 4;
                    }
                    else if ($input['card']['network'] == 'Maestro')
                    {
                        $aggGtwmapID = 3;
                    }
                    else if ($input['card']['network'] == 'Rupay')
                    {
                        $aggGtwmapID = 55;
                    }


                }
            }
            $paymentDetails = [$aggGtwmapID, $input['card']['number'], $input['card']['cvv'], $input['card']['expiry_year'] . $input['card']['expiry_month'], "NA", "SBIN", strtoupper($input['card']['network']), $input['payment']['contact'], $input['payment']['name']];
        }


        $content = EncryptDecrypt::encryptData(array(
                                                   'EncryptTrans'          => $requestParameter,
                                                   'EncryptpaymentDetails' => $paymentDetails,
                                                   'EncryptbillingDetails' => explode("|", "NA|NA|NA|NA|NA|NA|NA|NA|NA|NA|N"),
                                                   'EncryptshippingDetais' => explode("|", "NA|NA|NA|NA|NA|NA|NA|NA|NA|NA|N")
                                               ));

        $content['merchIdVal'] = $requestParameter['MerchantId'];
        $payment = $this->createGatewayPaymentEntity(array_merge($requestParameter, ['method' => $method]));

        $request = array(
            'url'     => $this->getUrl('pay'),
            'content' => $content,
            'method'  => 'post');

        return $request;
    }

    public
    function callback(array $input)
    {
        parent::callback($input);

        $encData = $input['gateway']['encData'];

        $decryptedContent = EncryptDecrypt::decryptData($encData);

        $content = $this->getContent($decryptedContent);

        if ($content['Status'] === Status::FAILURE)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['Status'],
                '');
        }

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $content['MerchantOrderNo'], Action::AUTHORIZE);

        $content['received'] = 1;
        $payment->fill($content);
        $payment->saveOrFail();

    }

//
//    public function refund(array $input)
//    {
//        parent::refund($input);
//
//        $payment = $this->getRepo()->findByPaymentIdAndAction(
//            $input['payment']['id'], Action::AUTHORIZE);
//
//        $content = $this->getPaymentRefundRequestContent($payment, $input);
//
//        $content = $this->postRequest($content);
//
//        $content['refund_id'] = $input['refund']['id'];
//        $content['CurrencyType'] = 'INR';
//        $content['received'] = 1;
//        $refund = $this->createGatewayPaymentEntity($content);
//
//        if ($content['ProcessStatus'] !== 'Y')
//        {
//            $this->trace->error(
//                TraceCode::PAYMENT_REFUND_FAILURE,
//                [$content]);
//
//            throw new Exception\GatewayErrorException(
//                ErrorCode::BAD_REQUEST_REFUND_FAILED);
//        }
//    }
//
    public
    function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

//
    public
    function authorizeFailed(array $input)
    {
        $e = null;

        try
        {
            $this->verify($input);
        } catch (Exception\PaymentVerificationException $e)
        {
            ;
        }

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not');
        }

        $verify = $e->getVerifyObject();

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true)
        )
        {
            $payment = $verify->payment;
            $payment->fill($verify->verifyResponseContent);
            $payment->saveOrFail();
        }
        else
        {
            throw new Exception\LogicException(
                'Should not have reached here');
        }

        return true;
    }

//
    protected
    function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

//        $amountRefunded = (int)($content['TotalRefundAmount'] * 100);

        $status = VerifyResult::STATUS_MATCH;

        if ($content['Status'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;
            // Could be the case where the transaction didn't even hit mobikwik
            if (($payment['received'] === false) and
                (($payment['Status'] === null) or
                    ($payment['Status'] !== Status::SUCCESS))
            )
            {
                $verify->apiSuccess = false;
            }
            else
            {
                if ($payment['Status'] === Status::SUCCESS)
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->apiSuccess = true;
                }
            }
        }
        else
        {
            if ($content['Status'] === Status::SUCCESS)
            {
                $verify->gatewaySuccess = true;
                //Gateway success , api success
                if ($payment['Status'] === Status::SUCCESS)
                {
                    $verify->apiSuccess = true;
                }
                else
                {
                    if ($payment['Status'] !== Status::SUCCESS)
                    {
                        $verify->status = VerifyResult::STATUS_MISMATCH;
                        $verify->apiSuccess = false;
                    }
                }
            }
        }
        $verify->status = $status;
        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;
        if (($verify->match === true) and
            ($payment['received'] === false)
        )
        {
            $payment->fill($content);
            $payment->saveOrFail();
        }

        return $status;
    }

//
    protected
    function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
    }

//
    protected
    function sendPaymentVerifyRequest($verify)
    {

        $input = $verify->payment;
//        sd($verify->payment);
        $requestParameter = array(
            'Atrn'            => $input['SBIePayReferenceID'],
            'MerchantId'      => $input['MerchantId'],
            'MerchantOrderNo' => $input['MerchantOrderNo'],
            'ReturnURL'       => $this->getUrl($this->action),
        );

        if ($this->mode === Mode::TEST)
        {
            $requestParameter['MerchantId'] = $this->getTestMerchantId();
        }

        $content = EncryptDecrypt::encryptData([
                                                   'encryptQuery' => $requestParameter
                                               ]);

        $content['merchIdVal'] = $requestParameter['MerchantId'];
        $content['aggIdVal'] = 'SBIEPAY';
        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);
        $this->response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;
        sd($verify->verifyResponseBody);
        $verify->verifyResponseContent = $requestParameter;

        return $content;
    }

//
//    protected function getPaymentRefundRequestContent($payment, $input)
//    {
//        // Format YYYYMMDD
//        $date = Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata');
//        $date = $date->format('Ymd');
//
//        // Format yyyymmdd24hhmmss (in docs), actually yyyymmddhhmmss,
//        // hh is in 24 hrs
//        $now = Carbon::now('Asia/Kolkata')->format('YmdHis');
//
//        $refundAmount = (float)($input['refund']['amount']);
//
//        // The amount should have exact two decimal places, otherwise billdesk gives error
//        $refundAmount = (string)number_format($refundAmount / 100, 2, '.', '');
//        $txnAmount = (string)number_format($payment['TxnAmount'], 2, '.', '');
//
//        $content = array(
//            'RequestType'    => '0400',
//            'MerchantID'     => $input['terminal']['gateway_merchant_id'],
//            'TxnReferenceNo' => $payment['TxnReferenceNo'],
//            'TxnDate'        => $date,
//            'CustomerID'     => $input['payment']['id'],
//            'TxnAmount'      => $txnAmount,
//            'RefAmount'      => $refundAmount,
//            'RefDateTime'    => $now,
//            'MerchantRefNo'  => $input['refund']['id'],
//            'Filler1'        => 'NA',
//            'Filler2'        => 'NA',
//            'Filler3'        => 'NA',
//        );
//
//        if ($this->mode === Mode::TEST)
//        {
//            $content['MerchantID'] = $this->getTestMerchantId();
//        }
//
//        return $content;
//    }

    protected
    function postRequest($content)
    {
        $content = http_build_query($content);
        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);
        $response = $this->sendGatewayRequest($request);
    }

    protected
    function getContent($msg)
    {
        $fields = $this->getFieldsForAction($this->action);

        $content = explode('|', $msg);
        if (count($content) == 23 && ($content[count($content) - 1] == '' || $content[count($content) - 1] == null))
        {
            unset($content[count($content) - 1]);
        }
//        var_dump($content);
//        sd($fields);
        $content = array_combine($fields, $content);

        return $content;
    }

    protected
    function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['MerchantOrderNo']);

        $payment->fill($attributes);
        $payment->setAction($this->action);
        $payment->saveOrFail();

        return $payment;
    }

//    protected function verifySecureHash($content)
//    {
//        $hash = $content['Checksum'];
//        unset($content['Checksum']);
//
//        $generatedHash = $this->getHashOfArray($content);
//
//        if ($generatedHash !== $hash)
//        {
//            throw new Exception\BadRequestValidationFailureException(
//                'Failed checksum verification');
//        }
//    }

//    public function getMessageStringWithHash($content)
//    {
//        $str = $this->getStringToHash($content, '|');
//
//        return $str . '|' . $this->getHashOfString($str);
//    }

//    protected function getHashOfArray($content)
//    {
//        $str = $this->getStringToHash($content, '|');
//
//        return $this->getHashOfString($str);
//    }

//    protected function getHashOfString($str)
//    {
//        $secret = $this->getSecret();
//
//        return strtoupper(hash_hmac('sha256', $str, $secret, false));
//    }

//    protected function getRequestArrayWithProxy($content)
//    {
//        $request = $this->getRequestArray($content);
//
//        $request['options']['proxy'] = 'https://splunk.razorpay.com:8888';
//
//        return $request;
//    }


    protected
    function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected
    function getTestMerchantId()
    {
        return '1000109';
    }

}