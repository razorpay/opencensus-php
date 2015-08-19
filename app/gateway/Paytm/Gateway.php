<?php

namespace Gateway\Paytm;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Paytm;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'paytm';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $method = $input['payment']['method'];

        $type = RequestType::THEDEFAULT;

        if ($method === 'card')
        {
            $type = RequestType::SEAMLESS;
        }

        $mobileNo = $this->getMobileNumber($input['payment']['contact']);
        $email = $this->getFormattedEmail($input['payment']['email']);

        $content = array(
            'REQUEST_TYPE'              => $type,
            'MID'                       => $input['terminal']['gateway_merchant_id'],
            'ORDER_ID'                  => $input['payment']['id'],
            'TXN_AMOUNT'                => $input['payment']['amount'] / 100,
            'CUST_ID'                   => $input['payment']['email'],
            'CHANNEL_ID'                => 'WEB',
            'INDUSTRY_TYPE_ID'          => $input['terminal']['gateway_terminal_id'],
            'WEBSITE'                   => $input['terminal']['gateway_access_code'],
            'CALLBACK_URL'              => $input['callbackUrl'],
            'MOBILE_NO'                 => $mobileNo,
            'EMAIL'                     => $input['payment']['email'],
        );

        if ($method === 'card')
        {
            $card = $input['card'];
            $expiryDate = $this->getFormattedCardExpiryDate($input);
            $cardDetails = $card['number'] . '|' . $card['cvv'] .
                '|' . $expiryDate;
            $content['PAYMENT_DETAILS'] = $this->getHashOfString($cardDetails);
            $content['AUTH_MODE'] = '3D';
            $type = $input['card']['type'];

            $cardType = Type::DC;

            if ($type === 'credit')
            {
                $cardType = Type::CC;
            }

            $content['PAYMENT_TYPE_ID'] = $cardType;
            $content['PAYMENT_MODE_ONLY'] = 'Yes';
        }
        else if ($method === 'netbanking')
        {
            $content['BANK_CODE'] = $this->getBankCode($input);
            $content['PAYMENT_TYPE_ID'] = Type::NB;
            $content['AUTH_MODE'] = 'USRPWD';
            $content['PAYMENT_MODE_ONLY'] = 'Yes';
        }
        else if ($method === 'wallet')
        {
            ;
        }

        $this->addMerchantIdAndOtherDetails($content, $input['terminal']);

        $this->createGatewayPaymentEntity($content);

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $request = array(
            'url' => $this->getUrl('pay'),
            'content' => $content,
            'method' => 'post');

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifySecureHash($input);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['gateway']['ORDERID'], Action::AUTHORIZE);

        $values = $this->lowerArrayKeys($input['gateway']);

        $payment->fill($values);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = array(
            'MID'           => $input['terminal']['gateway_merchant_id'],
            'TXNID'         => $payment['txnid'],
            'ORDERID'       => $input['payment']['id'],
            'TXNTYPE'       => Type::REFUND,
            'REFUNDAMOUNT'  => (string) ($input['refund']['amount'] / 100),
        );

        $this->addTestMerchantIdIfTestMode($content);

        $storeContent = $content;
        $storeContent['CUST_ID'] = $payment['cust_id'];
        $storeContent['CHANNEL_ID'] = $payment['channel_id'];
        $storeContent['INDUSTRY_TYPE_ID'] = $payment['industry_type_id'];
        $storeContent['REQUEST_TYPE'] = RequestType::THEDEFAULT;
        $storeContent['TXN_AMOUNT'] = $payment['txn_amount'];
        $storeContent['PAYMENTMODE'] = $payment['paymentmode'];
        $storeContent['PAYMENT_MODE_ONLY'] = $payment['payment_mode_only'];
        $storeContent['AUTH_MODE'] = $payment['auth_mode'];
        $storeContent['PAYMENT_TYPE_ID'] = $payment['payment_type_id'];
        $storeContent['BANK_CODE'] = $payment['bank_code'];

        $refund = $this->createGatewayRefundEntity($storeContent, $input);

        $content['CHECKSUM'] = $this->generateHash($content);

        $content = $this->postRequestToPaytm($content);

        $this->trace->info(TraceCode::MISC_TRACE_CODE, ['paytm' => $content]);

        $attr = $this->lowerArrayKeys($content);
        $refund->fill($attr)->saveOrFail();

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_REFUND_FAILED,
                    $content['RESPCODE'],
                    $content['RESPMSG']);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $data = array(
            'MID'       => $input['terminal']['gateway_merchant_id'],
            'ORDERID'  => $input['payment']['id']);

        $this->addTestMerchantIdIfTestMode($content);

        $content = $this->postRequestToPaytm($data);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

       $this->matchPaymentData($payment, $content, $input);
    }

    protected function postRequestToPaytm($content)
    {
        $content = 'JsonData='.json_encode($content);

        $request = array(
            'url' => $this->getUrl($this->action).'?'.$content,
            'content' => [],
            'method' => 'get');

        $response = $this->runRequestResponseFlow($request);
        $content = json_decode($response->body, true);

        return $content;
    }

    protected function matchPaymentData($payment, $content, $input)
    {
        if ($payment['status'] !== $content['STATUS'])
        {
            $res['match'] = false;
            $res['payment'] = [$payment->toArray()];
            $res['gateway_data'] = $content;
            $res['payment_id'] = $input['payment']['id'];
            $res['gateway'] = $input['payment']['gateway'];

            if ($res['match'] === false)
            {
                throw new Exception\PaymentVerificationException($res);
            }
        }
    }

    protected function runRequestResponseFlow(array $request)
    {
        $request['options']['timeout'] = 30;

        try
        {
            // send the request and get response
            return $this->sendGatewayRequest($request);
        }
        catch(\Requests_Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (\Gateway\Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw $e;
            }
        }
    }

    protected function getBankCode($input)
    {
        $codes = BankCodes::$bankCodeMap;
        $bank = $input['payment']['bank'];

        return $codes[$bank];
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->lowerArrayKeys($attributes);
        $attr['txntype'] = Type::SALE;

        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attr['order_id']);
        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayRefundEntity($attributes, $input)
    {
        $attributes['refund_id'] = $input['refund']['id'];
        $attributes['payment_id'] = $input['payment']['id'];

        $refund = $this->createGatewayEntity($attributes);

        return $refund;
    }

    protected function createGatewayEntity($attributes)
    {
        $attr = $this->lowerArrayKeys($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function lowerArrayKeys(array $array)
    {
        $ar = array();

        foreach ($array as $key => $value)
        {
            $ar[strtolower($key)] = $value;
        }

        return $ar;
    }

    protected function addMerchantIdAndOtherDetails(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['MID'] = $this->config['test_merchant_id'];
            $content['WEBSITE'] = 'Razorweb';
            $content['INDUSTRY_TYPE_ID'] = 'Retail';
        }
    }

    protected function addTestMerchantIdIfTestMode(array & $content)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['MID'] = $this->config['test_merchant_id'];
        }
    }

    protected function verifySecureHash($input)
    {
        $res = false;

        if (isset($input['gateway']['CHECKSUMHASH']) === false)
        {
            $this->trace->error(TraceCode::MISC_TRACE_CODE, $input['gateway']);

            if ($input['gateway']['STATUS'] === Status::FAILURE)
            {
                return;
            }
        }
        else
        {
            $checksum = $input['gateway']['CHECKSUMHASH'];
            unset($input['gateway']['CHECKSUMHASH']);

            $secret = $this->getSecret();

            $res = Checksum::verifychecksum_e($input['gateway'], $secret, $checksum);
        }

        if ($res === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        $code = (int) $input['gateway']['RESPCODE'];

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    $errorCode,
                    $input['gateway']['RESPCODE'],
                    $input['gateway']['RESPMSG']);
        }
    }

    protected function getHashOfArray($content)
    {
        $secret = $this->getSecret();

        return Checksum::getChecksumFromArray($content, $secret);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return Checksum::encrypt_e($str, $secret);
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = $expiryMonth . $input['card']['expiry_year'];

        return $cardExp;
    }

    protected function getFormattedEmail($email)
    {
        //
        // Remove all characters other than alhpanumeric, @ and .
        //
        return preg_replace("/[^a-zA-Z0-9@.]+/", '', $email);
    }

    protected function getMobileNumber($contact)
    {
        $chars = ['+', '(', ')'];
        return str_replace($chars, '', $contact);
    }
}