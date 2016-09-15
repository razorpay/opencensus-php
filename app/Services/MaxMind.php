<?php

namespace RZP\Services;

use CreditCardFraudDetection;
use RZP\Trace\TraceCode;
use RZP\Trace\Trace;
use RZP\Models\Card;

class MaxMind
{
    const LICENSE_KEY = 'license_key';

    protected $licenseKey;

    protected $config;

    protected $trace;

    protected $maxmind;

    protected $request;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->request = $app['request'];

        $this->config = $app['config']->get('applications.maxmind');

        $this->licenseKey = $this->config['secret'];

        $this->maxmind = new CreditCardFraudDetection;
    }

    public function query($payment)
    {
        $card = $payment->card;

        $input = array(
            "license_key"       => $this->licenseKey,
            "i"                 => $this->request->getRealClientIp(),
            'user_agent'        => $this->request->header('User-Agent'),
            'accept_language'   => $this->request->header('Accept-Language'),
            'domain'            => $this->getEmailDomain($payment),
            'custPhone'         => $payment->getContact(),
            'emailMD5'          => md5($payment->getEmail()),
            'bin'               => $payment->card->getIin(),
            'txnID'             => $payment->getId(),
            'order_amount'      => $this->getFormattedAmount($payment),
            'order_currency'    => $payment->getCurrency(),
            'txn_type'          => Card\Type::getMaxmindCardType($card->getType())
        );

        $this->maxmind->input($input);
        $this->maxmind->query();

        $response = $this->maxmind->output();

        $this->trace->info(TraceCode::MAXMIND_RESPONSE, [
                'input' => $input,
                'payment_id' => $payment->getId(),
                'merchant_id' => $payment->getMerchantId(),
                'response' => $response]);

        return $response;
    }

    protected function getFormattedAmount($payment)
    {
        $amount = $payment->getAmount();

        return number_format($amount, 2, '.', '');
    }

    protected function getEmailDomain($payment)
    {
        $email = $payment->getEmail();

        $emailDomain = explode('@', $email, 2);

        return $emailDomain[1];
    }
}