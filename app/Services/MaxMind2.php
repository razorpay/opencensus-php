<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Models\Card;
use MaxMind\MinFraud;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity as Payment;

class MaxMind2
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

        $this->maxmind = new MinFraud($config['id'], $config['secretv2']);
    }

    public function query(Payment $payment)
    {
        if (($this->mode === Mode::TEST) or
            ($this->basicauth->isPrivateAuth() === true))
        {
            return;
        }

        $card = $payment->card;

        $request = $this->maxmind->withDevice([
            'ip_address'       => $this->request->getRealClientIp(),
            'user_agent'       => $this->request->header('User-Agent'),
            'accept_language'  => $this->request->header('Accept-Language'),
        ])->withEvent([
            'transaction_id'   => $payment->getId(),
            'shop_id'          => $payment->getMerchantId(),
            'time'             => Carbon::now()->toIso8601String(),
            'type'             => $payment->isRecurring() ? 'recurring_purchase' : 'purchase',
        ])->withEmail([
            'address'          => md5($payment->getEmail()),
            'domain'           => $this->getEmailDomain($payment)
        ])->withBilling([
            'first_name'       => $card->getFirstName(),
            'last_name'        => $card->getLastName(),
        ])->withCreditCard([
            'issuer_id_number' => $card->getIin(),
            'last_4_digits'    => $card->getLast4(),
        ])->withOrder([
            'amount'           => $this->getFormattedAmount($payment),
            'currency'         => $payment->getCurrency(),
        ]);

        $response = $request->score();

        $this->trace->info(TraceCode::MAXMIND_RESPONSE, [
            'payment_id' => $payment->getId(),
            'merchant_id' => $payment->getMerchantId(),
            'merchant' => $payment->merchant->getBillingLabelElseName(),
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