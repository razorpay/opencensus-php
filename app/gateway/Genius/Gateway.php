<?php

namespace Gateway\Genius;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Genius;
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
        return parent::authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

    }

    public function capture(array $input)
    {
        return;

        $payment = (new Axis\Repository)->findByMerchantTxnRef($input['payment']['id']);

        $content = array(
            'vpc_Command'       => Command::CAPTURE,
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_ReceiptNo'     => $payment['vpc_ReceiptNo'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
            'vpc_Amount'        => $input['amount']
        );

        $response = $this->postAmaTransactionRequest($content);
        sd($response->getContent());
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = (new Axis\Repository)->findByMerchantTxnRef($input['payment']['id']);

        $attributes = array(
            'vpc_Command'       => Axis\Command::REFUND,
            'vpc_Amount'        => $input['refund']['amount'],
            'vpc_Currency'      => $input['refund']['currency'],
            'vpc_ReceiptNo'     => $payment['vpc_ReceiptNo'],
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
            'vpc_Amount'        => $input['refund']['amount'],
            'vpc_Currency'      => $input['refund']['currency'],
            'vpc_ReceiptNo'     => $payment['vpc_ReceiptNo'],
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
        );

        $response = $this->postAmaTransactionRequest($content);
    }

    protected function addAmaTransactionFields(array & $content)
    {
        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $content['vpc_SecureHash'] = $this->generateHash($content);
    }

    protected function getAmaRequestArray()
    {
        $request = array(
            'url'       => $this->getUrl(),
            'content'   => $content,
            'method'    => 'post');

        return $request;
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
        $hash = $input['vpc_SecureHash'];
        unset($input['vpc_SecureHash']);

        $generatedHash = $this->generateHash($input);

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
        if ((isset($input['vpc_TxnResponseCode']) === true) and
            ($input['vpc_TxnResponseCode'] === '0'))
        {
            return; // Payment succeeds
        }

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    null,
                    $input['vpc_Message']);
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
        $test = Url::TEST_DOMAIN;

        $live = Url::LIVE_DOMAIN;

        $url = ($this->mode === MODE::LIVE) ? $live : $test;

        $type = strtoupper($type);
        $url .= constant(__NAMESPACE__.'\Url::'.$type);

        return $url;
    }
}