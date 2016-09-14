<?php

namespace RZP\Models\Payment\Processor;

use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Exception;

trait FraudDetector
{
    protected function validateFraudDetection($payment, $input)
    {
        if ($payment->isMethod(Payment\Method::CARD) === true)
        {
            $riskFields = $this->getRiskDetectionField($payment, $input);

            if ((isset($riskFields) === true) and
                ($riskFields['riskScore'] > 50))
            {
                throw new Exception\GatewayException(
                        ErrorCode::BAD_REQUEST_PAYMENT_REJECTED_FRAUD_DETECTED);
            }
        }
    }

    protected function getRiskDetectionField($payment, $input)
    {
        $maxMindInput = array(
            'domain'            => $this->getEmailDomain($payment),
            'custPhone'         => $payment->getContact(),
            'emailMD5'          => md5($payment->getEmail()),
            'bin'               => $input['card']['iin'],
            'txnID'             => $payment->getId(),
            'order_amount'      => $this->getFormattedAmount($payment),
            'order_currency'    => $payment->getCurrency(),
            'txn_type'          => $this->getTxnType($input['card'])
        );

        $response = $this->app['maxmind']->query($maxMindInput);

        return $response;
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