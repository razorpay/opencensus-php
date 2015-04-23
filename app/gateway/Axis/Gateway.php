<?php

namespace Gateway\Axis;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\BaseGateway;
use Gateway\Axis;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends BaseGateway
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

        $cardExp = substr($input['card']['expiry_year'], 2,2) .
                    $input['card']['expiry_month'];

        $content = array(
            'vpc_Version'               => '1',
            'vpc_Command'               => 'pay',
            'vpc_MerchTxnRef'           => $input['payment']['public_id'],
            // 'vpc_OrderInfo'             => $input['payment'][''],
            'vpc_Amount'                => $input['payment']['amount'],
            'vpc_Currency'              => 'INR',
            'vpc_ReturnURL'             => $input['callbackUrl'],
            'vpc_Locale'                => 'en',
            'vpc_gateway'               => 'threeDSecure',
            'vpc_CardNum'               => $input['card']['number'],
            'vpc_CardExp'               => $cardExp,
            'vpc_CardSecurityCode'      => $input['card']['cvv'],
            'vpc_Card'                  => 'Visa',
        );

        if ($this->mode === Mode::TEST)
        {
            $content['vpc_Merchant'] = $this->config['test_merchant_id'];
            $content['vpc_AccessCode'] = $this->config['test_access_code'];
            $content['vpc_CardNum'] = '5123456789012346';
            $content['vpc_Card'] = 'Mastercard';
        }

        $content['vpc_SecureHash'] = $this->generateHash($content);

        $request['url'] = 'https://migs.mastercard.com.au/vpcpay';
        $request['content'] = $content;
        $request['method'] = 'post';

        return $request;
    }

    public function refund(array $input)
    {
        parent::refund($input);
    }

    protected function generateHash($content)
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
}