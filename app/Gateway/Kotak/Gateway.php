<?php

namespace RZP\Gateway\Kotak;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Kotak;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;

class Gateway extends Base\Gateway
{
    protected $gateway = 'kotak';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = array(
            'TxnType'           => Type::PURCHASE,
            'TxnRefNo'          => $input['payment']['id'],
            'Amount'            => $input['payment']['amount'],
            'Currency'          => 356,
            'ReturnURL'         => $input['callbackUrl'],
            'CardNumber'        => $input['card']['number'],
            'ExpiryDate'        => $this->getFormattedCardExpiryDate($input),
            'CardSecurityCode'  => $input['card']['cvv'],
            'MCC'               => $input['merchant']['category'],
            'MerchantName'      => $input['merchant']['name'],
            'MerchantCity'      => $input['bank_account']['beneficiary_city'],
            'MerchantState'     => $input['bank_account']['beneficiary_state'],
            'MerchPostalCode'   => $input['bank_account']['beneficiary_pin'],
            'MerchPhone'        => $input['bank_account']['beneficiary_mobile'],
        );

        $this->createGatewayPaymentEntity($attributes);

        $this->addMerchantAndTerminalDetails($attributes, $input);

        $attributes['SecureHash'] = $this->generateHash($attributes);

        $baseUrl = $this->getUrl(Base\Action::PURCHASE);

        $url = $baseUrl.'?'.http_build_query($attributes);

        $request = array(
            'url' => $url,
            'method' => 'get'
        );

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        // s($input['gateway']);

        $this->verifySecureHash($input);

        $payment = $this->getRepo()->findByTxnRefAndType(
            $input['gateway']['TxnRefNo'], Type::PURCHASE);

        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndType(
                                $input['payment']['id'], Type::PURCHASE);

        $content = array(
            'TxnRefNo'      => $payment['TxnRefNo'],
            'TxnType'       => Type::REFUND,
            'Amount'        => $payment['amount'],
            'ResponseCode'  => '00',
            'BatchNo'       => $payment['BatchNo'],
            'RetRefNo'      => $payment['RetRefNo'],
            'AuthCode'      => $payment['AuthCode'],
            'RefundAmount'  => $input['refund']['amount'],
        );

        $this->addMerchantAndTerminalDetails($content, $input);

        $attributes['SecureHash'] = $this->generateHash($attributes);

        $baseUrl = $this->getUrl(Base\Action::REFUND);

        $url = $baseUrl.'?'.http_build_query($attributes);

        $request = array(
            'url' => $url,
            'method' => 'get',
        );

        $response = $this->postRequest($request);

        parse_str($response->body, $content);

        $content['payment_id'] = $input['payment']['id'];
        $content['refund_id'] = $input['refund']['id'];

        $payment = $this->createGatewayPaymentEntity($content);

        $this->verifySecureHash($input);
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['TxnRefNo']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
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

    protected function verifySecureHash($input)
    {
        $hash = $input['gateway']['SecureHash'];
        unset($input['gateway']['SecureHash']);

        $generatedHash = $this->generateHash($input['gateway']);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException('Failed checksum verification');
        }
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        if (($content['ResponseCode'] !== '00') and
            ($content['ResponseCode'] !== '0'))
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    null,
                    $input['gateway']['Message']);
        }
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = $expiryMonth . substr($input['card']['expiry_year'], 2,2);

        return $cardExp;
    }

    protected function getHashOfString($str)
    {
        $str = $this->getSecret() . $str;

        return hash(HashAlgo::SHA256, $str, false);
    }
}
