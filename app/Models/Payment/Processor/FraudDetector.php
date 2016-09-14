<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Payment;
use RZP\Models\Card;

trait FraudDetector
{
    protected function validateFraudDetection($payment, $input)
    {
        if ($payment->isMethod(Payment\Method::CARD) === true)
        {
            $request = $this->app['request'];

            $maxMindInput = array(
                "license_key"       => config('applications.maxmind.secret'),
                "i"                 => $request->server('REMOTE_ADDR'),
                'domain'            => $this->getEmailDomain($payment),
                'custPhone'         => $payment->getContact(),
                'emailMD5'          => md5($payment->getEmail()),
                'bin'               => $input['card']['iin'],
                'user_agent'        => $request->header('user-agent'),
                'accept_language'   => $request->header('accept_language'),
                'txnID'             => $payment->getId(),
                'order_amount'      => $this->getFormattedAmount($payment),
                'order_currency'    => $payment->getCurrency(),
                'txn_type'          => $this->getTxnType($input['card'])
            );

        }
    }

    protected function getTxnType($card)
    {
        $type = 'other';

        if ($card['type'] === Card\Type::CREDIT)
        {
            $type = 'creditcard';
        }
        else if ($card['type'] === Card\Type::DEBIT)
        {
            $type = 'debitcard';
        }

        return $type;
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