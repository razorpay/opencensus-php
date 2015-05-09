<?php

namespace Gateway\Paytm;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Paytm;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'paytm';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = array(
//            'REQUEST_TYPE'              => 'DEFAULT',
            'MID'                       => $input['terminal']['gateway_merchant_id'],
            'ORDER_ID'                  => $input['payment']['id'],
            'TXN_AMOUNT'                => $input['payment']['amount'] / 100,
            'CUST_ID'                   => $input['payment']['email'],
            'CHANNEL_ID'                => 'WEB',
//            'EMAIL'                     => $input['payment']['email'],
  //          'MOBILE_NO'                 => $input['payment']['contact'],
            'BANK_CODE'                 => $this->getBankCode($input),
            'PAYMENT_TYPE_ID'           => 'NB',
  //          'INDUSTRY_TYPE_ID'          => 'Retail',
            'WEBSITE'                   => 'razorpay.com',
            'CALLBACK_URL'              => $input['callbackUrl'],
            'PAYMENT_MODE_ONLY'         => 'Yes',
            'AUTH_MODE'                 => 'USERPWD',
        );
//sd($content);
        $this->addMerchantIdAndOtherDetails($content, $input['terminal']);

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $request = array(
            'url' => $this->getUrl('pay'),
            'content' => $content,
            'method' => 'post');

        return $request;
    }

    public function callback(array $input)
    {
        sd($input);
    }

    protected function getBankCode($input)
    {
        $codes = BankCodes::$bankCodeMap;
        $bank = $input['payment']['bank'];

        return $codes[$bank];
        return constant(__NAMESPACE__.'::BankCodes::'.$input['payment']['bank']);
    }

    protected function addMerchantIdAndOtherDetails(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['MID'] = $this->config['test_merchant_id'];
            $content['WEBSITE'] = 'Razorweb';
            $content['INDUSTRY_TYPE_ID'] = 'Retail';
        }
        else
        {
            $content['MID'] = $terminal['gateway_merchant_id'];
            $content['INDUSTRY_TYPE_ID'] = 'Retail';
            $content['WEBSITE'] = 'https://api.razorpay.com';
        }
    }

    protected function getHashOfArray($content, $secret)
    {
        return Checksum::getChecksumFromArray($content, $secret);
    }

    protected function getUrl($type)
    {
        $url = $this->getUrlDomain();

        $type = strtoupper($type);
        $url .= $this->getRelativeUrl($type);

        return $url;
    }

    protected function getUrlDomain()
    {
        return ($this->mode === MODE::LIVE) ? Url::LIVE_DOMAIN : Url::TEST_DOMAIN;
    }

    protected function getRelativeUrl($type)
    {
        return constant(__NAMESPACE__.'\Url::'.$type);
    }
}