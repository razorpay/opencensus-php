<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Card;
use Models\Card\Network;
use Models\Payment;
use Models\Payment\Gateway;
use Models\Terminal;
use Models\Terminal\Shared;

class TerminalPicker
{
    /**
     * Payment for which terminal has to be picked
     * @var Models\Payment\Entity
     */
    protected $payment;

    public function selectTerminal($payment)
    {
        $this->payment = $payment;

        $terminals = $this->getTerminals($payment);

        $this->validateCount($terminals, $payment->merchant);

        $terminal = $this->pickOneTerminal($payment, $terminals);
//$terminal = null;
        if ($terminal === null)
        {
            $terminal = $this->getSharedTerminal($payment);
        }

        $payment->terminal()->associate($terminal);

        $payment->setGateway($terminal->getGateway());

        return $terminal;
    }

    protected function pickOneTerminal($payment, $terminals)
    {
        $terminal = null;

        $merchant = $payment->merchant;

        $method = $payment->getMethod();

        $gatewayTerms = $this->getGatewayTerminals($terminals);

        if ($method === Payment\Method::CARD)
        {
            $terminal = $this->pickTerminalForCardMethod($terminals, $gatewayTerms);
        }
        else if ($method === Payment\Method::NETBANKING)
        {
            if (isset($gatewayTerms[Payment\Gateway::PAYTM]) === true)
            {
                $terminal = $gatewayTerms[Payment\Gateway::PAYTM];
            }
            else if (isset($gatewayTerms[Payment\Gateway::ATOM]) === true)
            {
                $terminal = $gatewayTerms[Payment\Gateway::ATOM];

                // throw new Exception\BadRequestException(
                //     ErrorCode::BAD_REQUEST_PAYMENT_NET_BANKING_NOT_ENABLED);
            }
        }
        else
        {
            throw new Exception\LogicException(
                'Unrecognized payment method ' . $method .
                ' Merchant Id: ' . $merchant->getId());
        }

        return $terminal;
    }

    protected function pickTerminalForCardMethod($terminals, $gatewayTerms)
    {
        $terminal = null;

        $payment = $this->payment;

        if ($payment->card->getNetwork() === Network::$fullName[Network::RUPAY])
        {
            if (isset($gatewayTerms[Gateway::KOTAK]))
            {
                $terminal = $gatewayTerms[Gateway::KOTAK];
            }

            return $terminal;
        }

        $international = $payment->merchant->isInternational();

        if ($international)
        {
            if (isset($gatewayTerms[Gateway::AXIS_GENIUS]))
            {
                return $gatewayTerms[Gateway::AXIS_GENIUS];
            }
        }

            if (isset($gatewayTerms[Payment\Gateway::PAYTM]) === true)
            {
                return $gatewayTerms[Payment\Gateway::PAYTM];
            }

        if (isset($gatewayTerms[Gateway::AXIS_MIGS]))
        {
            return $gatewayTerms[Gateway::AXIS_MIGS];
        }

        if (isset($gatewayTerms[Gateway::HDFC]))
        {
            return $gatewayTerms[Gateway::HDFC];
        }

        return $terminal;
    }

    protected function getSharedTerminal($payment)
    {
        $terminal = null;

        $repo = new Terminal\Repository;

        $method = $payment->getMethod();

        if ($method === Payment\Method::CARD)
        {
            if ($payment->card->getNetwork() === Network::$fullName[Network::RUPAY])
            {
                $terminal = $repo->find(Shared::KOTAK_RAZORPAY_TERMINAL);

                if ($terminal !== null)
                {
                    return $terminal;
                }
            }

            $terminal = $repo->find(Shared::AXIS_MIGS_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }

            $terminal = $repo->find(Shared::AXIS_GENIUS_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }
        }

        $terminal = $repo->find(Shared::PAYTM_RAZORPAY_TERMINAL);

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $repo->findOrFail(Shared::ATOM_RAZORPAY_TERMINAL);

        return $terminal;
    }

    protected function getGatewayTerminals($terminals)
    {
        $gatewayTerms = [];

        foreach ($terminals->all() as $term)
        {
            $gateway = $term->getGateway();
            Payment\Gateway::validateGateway($gateway);

            $gatewayTerms[$gateway] = $term;
        }

        return $gatewayTerms;
    }

    protected function validateCount($terminals, $merchant)
    {
        $count = $terminals->count();

        // Max count can be 6 currently.
        if ($count > 6)
        {
            throw new Exception\LogicException(
                'Terminals count not reasonable: ' . $count .
                ' Merchant Id: ' . $merchant->getId());
        }
    }

    protected function getTerminals($payment)
    {
        $termRepo = new Terminal\Repository;

        $terminals = $termRepo->getByMerchantId($payment->merchant->getId());

        return $terminals;
    }
}