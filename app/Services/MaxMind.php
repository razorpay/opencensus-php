<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Models\Card;
use MaxMind\MinFraud;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity as Payment;

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
        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->request = $app['request'];

        $config = $app['config']->get('applications.maxmind');

        $this->basicauth = $app['basicauth'];

        $this->maxmind = new MinFraud($config['id'], $config['secret']);
    }

    public function query(Payment $payment)
    {
        $card = $payment->card;

        $ip = $this->request->ip();
        $ua = $this->request->header('User-Agent');

        if ($this->basicauth->isPrivateAuth() === true)
        {
            $ip = $payment->getMetadata('ip');
            $ua = $payment->getMetadata('user_agent');
        }

        // We don't use maxmind if payment doesn't have IP and UserAgent
        // in case of s2s integration
        if (($this->mode === Mode::TEST) or
            ($ua === null) or
            ($ip === null))
        {
            return;
        }

        $card = $payment->card;

        $request = $this->maxmind->withDevice([
            'ip_address'       => $ip,
            'user_agent'       => $ua,
            'accept_language'  => $this->request->header('Accept-Language'),
        ])->withEvent([
            'transaction_id'   => $payment->getId(),
            'shop_id'          => $payment->getMerchantId(),
            'time'             => Carbon::now()->toIso8601String(),
            'type'             => $payment->isRecurring() ? 'recurring_purchase' : 'purchase',
        ])->withBilling([
            'first_name'       => $card->getFirstName(),
            'last_name'        => $card->getLastName(),
        ])->withCreditCard([
            'issuer_id_number' => $card->getIin(),
            'last_4_digits'    => $card->getLast4(),
            'token'            => $card->getGlobalFingerPrint(),
        ])->withOrder([
            'amount'           => $this->getFormattedAmount($payment),
            'currency'         => $payment->getCurrency(),
        ]);

        if ($payment->isCustomerMailAbsent() === false)
        {
            $request->withEmail([ // nosemgrep :  php.lang.security.weak-crypto.weak-crypto
                'address' => md5($payment->getEmail()),
                'domain'  => $this->getEmailDomain($payment)
             ]);
        }

        $response = $request->score();

        $this->trace->info(TraceCode::MAXMIND_RESPONSE, [
            'payment_id' => $payment->getId(),
            'merchant_id' => $payment->getMerchantId(),
            'merchant' => $payment->merchant->getBillingLabel(),
            'response' => $response->jsonSerialize()]);

        $jsonResponse = $response->jsonSerialize();

        $jsonResponse['riskScore'] = $jsonResponse['risk_score'];

        return $jsonResponse;
    }

    protected function getFormattedAmount($payment)
    {
        $amount = $payment->getAmount() / 100;

        return number_format($amount, 2, '.', '');
    }

    protected function getEmailDomain($payment)
    {
        $email = $payment->getEmail();

        $emailDomain = explode('@', $email, 2);

        return $emailDomain[1];
    }
}
