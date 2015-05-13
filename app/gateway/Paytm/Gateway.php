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

        $method = $input['payment']['method'];

        $type = RequestType::THEDEFAULT;

        if ($method === 'card')
        {
            $type = RequestType::SEAMLESS;
        }

        $content = array(
            'REQUEST_TYPE'              => $type,
            'MID'                       => $input['terminal']['gateway_merchant_id'],
            'ORDER_ID'                  => $input['payment']['id'],
            'TXN_AMOUNT'                => $input['payment']['amount'] / 100,
            'CUST_ID'                   => $input['payment']['email'],
            'CHANNEL_ID'                => 'WEB',
//            'EMAIL'                     => $input['payment']['email'],
  //          'MOBILE_NO'                 => $input['payment']['contact'],
            'INDUSTRY_TYPE_ID'          => $input['terminal']['gateway_terminal_id'],
            'WEBSITE'                   => $input['merchant']['website'],
            'CALLBACK_URL'              => $input['callbackUrl'],
            'PAYMENT_MODE_ONLY'         => 'Yes',
            'AUTH_MODE'                 => 'USERPWD',
        );

        if ($method === 'card')
        {
            $card = $input['card'];
            $expiryDate = $this->getFormattedCardExpiryDate($input);
            $cardDetails = $card['number'] . '|' . $card['cvv'] .
                '|' . $expiryDate;
            $content['PAYMENT_DETAILS'] = $this->getHashOfString($cardDetails);
            $content['AUTH_MODE'] = '3D';
            $content['PAYMENT_TYPE_ID'] = Type::DC;
        }
        else
        {
            $content['BANK_CODE'] = $this->getBankCode($input);
            $content['PAYMENT_TYPE_ID'] = Type::NB;
        }

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
        parent::callback($input);
return;
        $this->verifySecureHash($input);

        $payment = $this->getRepo()->findByTxnRefAndType(
            $input['gateway']['TxnRefNo'], Type::PURCHASE);

        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
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
    }

    protected function verifySecureHash($input)
    {
        $hash = $input['gateway']['SecureHash'];
        unset($input['gateway']['SecureHash']);

        $generatedHash = $this->generateHash($input['gateway']);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        if ($content['RESPCODE'] !== '1')
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    null,
                    $input['gateway']['RESPMSG']);
        }
    }

    protected function getHashOfArray($content, $secret)
    {
        return Checksum::getChecksumFromArray($content, $secret);
    }

    protected function getHashOfString($str)
    {
        return Checksum::encrypt_e($str, $this->getSecret());
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


    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = $expiryMonth . $input['card']['expiry_year'];

        return $cardExp;
    }
}