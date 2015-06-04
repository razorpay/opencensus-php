<?php

namespace Gateway\Paytm;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
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
            'INDUSTRY_TYPE_ID'          => $input['terminal']['gateway_terminal_id'],
            'WEBSITE'                   => $input['merchant']['website'],
            'CALLBACK_URL'              => $input['callbackUrl'],
            'PAYMENT_MODE_ONLY'         => 'Yes',
            'MOBILE_NO'                 => $input['payment']['contact'],
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
            $content['PAYMENT_TYPE_ID'] = Type::CC;
        }
        else if ($method === 'netbanking')
        {
            $content['BANK_CODE'] = $this->getBankCode($input);
            $content['PAYMENT_TYPE_ID'] = Type::NB;
            $content['AUTH_MODE'] = 'USRPWD';
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

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['gateway']['ORDERID'], Action::AUTHORIZE);

        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
    }

    protected function getRepo()
    {
        return new Paytm\Repository;
    }

    protected function getBankCode($input)
    {
        $codes = BankCodes::$bankCodeMap;
        $bank = $input['payment']['bank'];

        return $codes[$bank];
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = [];

        foreach ($attributes as $key => $value)
        {
            $attr[strtolower($key)] = $value;
        }

        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attr['order_id']);
        $payment->setAction($this->action);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Paytm\Entity;
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
        $checksum = $input['gateway']['CHECKSUMHASH'];
        unset($input['gateway']['CHECKSUMHASH']);

        $secret = $this->getSecret();

        $res = Checksum::verifychecksum_e($input['gateway'], $secret, $checksum);

        if ($res === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
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
}