<?php

namespace Gateway\Axis;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Axis;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    public function __construct()
    {
        parent::__construct();

        $app = \App::getFacadeRoot();
        $this->config = $app['config']->get('gateway.axis');
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = array(
            'vpc_Command'               => Command::PAY,
            'vpc_Amount'                => $input['payment']['amount'],
            'vpc_Currency'              => $input['payment']['currency'],
            'vpc_MerchTxnRef'           => $input['payment']['id'],
        );

        $this->createGatewayPaymentEntity($attributes);

        $content = array(
            'vpc_Version'           => '1',
            'vpc_ReturnURL'         => $input['callbackUrl'],
            'vpc_Locale'            => 'en',
            'vpc_gateway'           => 'ssl',
            'vpc_Card'              => $input['card']['network'],
            'vpc_CardNum'           => $input['card']['number'],
            'vpc_CardExp'           => $this->getFormattedCardExpiryDate($input),
            'vpc_CardSecurityCode'  => $input['card']['cvv'],
//            'vpc_OrderInfo'             => 'testinfo',
        );

        $content = array_merge($attributes, $content);

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $content['vpc_SecureHash'] = $this->generateHash($content);

        $request = $this->getAuthRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        $payment = (new Axis\Repository)->findByMerchantTxnRef(
            $input['gateway']['vpc_MerchTxnRef']);

        $this->verifySecretHash($input);

        $payment->fill($input);
        $payment->saveOrFail();

        $this->verifyPaymentResponse($input);

        return;
    }

    public function capture(array $input)
    {
        return;

        $payment = (new Axis\Repository)->findByMerchantTxnRef($input['payment']['id']);

        $content = array(
            'vpc_Command'       => Command::CAPTURE,
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
            'vpc_Amount'        => $input['amount']
        );

        $response = $this->postAmaTransactionRequest($content);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = (new Axis\Repository)->findByMerchantTxnRef($input['payment']['id']);

        $attributes = array(
            'vpc_Command'       => Axis\Command::REFUND,
            'vpc_Amount'        => $input['refund']['amount'],
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
        );

        $response = $this->postAmaTransactionRequest($content);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $payment = (new Axis\Repository)->findByMerchantTxnRef($input['payment']['id']);

        $attributes = array(
            'vpc_Command'       => Axis\Command::QUERY,
            'vpc_Amount'        => $input['payment']['amount'],
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
        );

        $response = $this->postAmaTransactionRequest($content);
    }

    protected function addAmaTransactionFields(array & $content)
    {
        $content['vpc_Version'] = 1;

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $content['vpc_User'] = '';
        $content['vpc_Password'] = '';

        $content['vpc_SecureHash'] = $this->generateHash($content);
    }

    protected function getAuthRequestArray($content)
    {
        $request = array(
            'url'       => $this->getUrl(Command::PAY),
            'content'   => $content,
            'method'    => 'post');

        return $request;
    }

    protected function getAmaRequestArray()
    {
        $request = array(
            'url'       => $this->getUrl(),
            'content'   => $content,
            'method'    => 'post');

        return $request;
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['vpc_MerchTxnRef']);

        $payment->fill($attributes);

        $payment->saveOrFail();
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Axis\Entity;
    }

    protected function postAmaTransactionRequest(array & $content)
    {
        $this->addAmaTransactionFields($content);

        $request = $this->getAmaRequestArray();

        // send the request and get response
        $response = $this->postRequest($request);

        return $response;
    }

    public function postRequest($request)
    {
//        $request['options'] = $this->getRequestOptions();

        $this->response = $this->sendGatewayRequest($request);

        return $this->response;
    }

    public function generateHash($content)
    {
        $md5HashData = $this->config['test_hash_secret'];

        ksort($content);

        foreach($content as $key => $value)
        {
            //
            // create the md5 input and URL leaving
            // out any fields that have no value
            //
            if (strlen($value) > 0)
            {
                $md5HashData .= $value;
            }
        }

        return strtoupper(md5($md5HashData));
    }

    protected function verifySecretHash($input)
    {
        unset($input['payment']);
        $hash = $input['gateway']['vpc_SecureHash'];
        unset($input['gateway']['vpc_SecureHash']);

        $generatedHash = $this->generateHash($input['gateway']);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException('Failed checksum verification');
        }
    }

    protected function addMerchantIdAndAccessCode(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['vpc_Merchant'] = $this->config['test_merchant_id'];
            $content['vpc_AccessCode'] = $this->config['test_access_code'];
        }
        else
        {
            $content['vpc_Merchant'] = $input['terminal']['gateway_merchant_id'];
            $content['vpc_AccessCode'] = $input['terminal']['gateway_terminal_password'];
        }
    }

    protected function verifyPaymentResponse($input)
    {
        if ((isset($input['gateway']['vpc_TxnResponseCode']) === true) and
            ($input['gateway']['vpc_TxnResponseCode'] === '0'))
        {
            return; // Payment succeeds
        }

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    null,
                    $input['gateway']['vpc_Message']);
    }

    protected function addTestCardDetailsInTestMode(array & $content)
    {
        assert ($this->mode === Mode::TEST);

        if ($content['vpc_CardNum'] === '4111111111111111')
        {
            return;
        }

        $content['vpc_Card'] = 'MasterCard';
        $content['vpc_CardNum'] = '5123456789012346';
        $content['vpc_CardExp'] = '1705';
        $content['vpc_CardSecurityCode'] = '333';
    }

    protected function getUrl($type)
    {
        $test = Url::DOMAIN;

        $live = Url::DOMAIN;

        $url = ($this->mode === MODE::LIVE) ? $live : $test;

        $type = strtoupper($type);
        $url .= constant(__NAMESPACE__.'\Url::'.$type);

        return $url;
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = substr($input['card']['expiry_year'], 2,2) . $expiryMonth;

        return $cardExp;
    }
}