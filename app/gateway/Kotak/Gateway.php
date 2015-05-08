<?php

namespace Gateway\Kotak;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Kotak;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'kotak';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = array(
            'TxnType'           => Type::PURCHASE,
            'TxnRefNo'          => $input['payment']['id'],
            'OrderInfo'         => $input['payment']['id'],
            'Amount'            => $input['payment']['amount'],
            'Currency'          => 356,
            'ReturnUrl'         => $input['callbackUrl'],
            'CardNumber'        => $input['card']['number'],
            'ExpiryDate'        => $this->getFormattedCardExpiryDate($input),
            'CardSecurityCode'  => $input['card']['cvv'],
            'MCC'               => '4799',
            'MerchantName'      => 'Name',
            'MerchantCity'      => 'Mumbai',
            'MerchantState'     => 'MH',
            'MerchPostalCode'   => 40069,
            'MerchPhone'        => '022241234000',
        );

        $this->addMerchantAndTerminalDetails($attributes, $input);

        $url = '';

        foreach ($attributes as $key => $value)
        {
            $attributes[$key] = urlencode($value);
            $url .= '&'.urlencode($key).'='.urlencode($value);
        }

        $attributes['SecureHash'] = $this->generateHash($attributes);

        $url = $this->getUrl(Base\Action::PURCHASE);
        $url .= '?'.$url;

        $request = array(
            'url' => $url,
            'method' => 'get'
        );
//\Log::info($request['url']);
        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        sd($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);
    }

    protected function addMerchantAndTerminalDetails(array & $content, $input)
    {
        $content['MerchantId'] = $input['terminal']['gateway_merchant_id'];
        $content['PassCode'] = $input['terminal']['gateway_terminal_password'];
        $content['TerminalId'] = $input['terminal']['gateway_terminal_id'];

        if ($this->mode === Mode::TEST)
        {
            $content['MerchantId'] = $this->config['test_merchant_id'];
            $content['PassCode'] = $this->config['test_access_code'];
            $content['TerminalId'] = $this->config['test_terminal_id'];
        }
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = substr($input['card']['expiry_year'], 2,2) . $expiryMonth;

        return $cardExp;
    }

    protected function getHashOfString($str)
    {
        return strtoupper(hash('sha256', $str, false));
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
