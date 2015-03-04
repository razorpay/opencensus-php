<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Payment;
use Models\Terminal;

class TerminalPicker
{
    public function selectTerminal($payment)
    {
        $terminals = $this->getTerminals($payment);

        $this->validateCount($terminals, $payment->merchant);

        $terminal = $this->pickOneTerminal($payment, $terminals);
$terminal  = null;
        if ($terminal === null)
        {
            $terminal = Terminal\Shared::getSharedTerminal();
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

        list($hdfcTerm, $atomTerm) = $this->getHdfcAndAtomTerm($terminals);

        if ($method === Payment\Method::CARD)
        {
            $terminal = $this->pickTerminalForCardMethod($terminals, $hdfcTerm, $atomTerm);
        }
        else if ($method === Payment\Method::NETBANKING)
        {
            if ($atomTerm === null)
            {
                return;

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_NET_BANKING_NOT_ENABLED);
            }

            $terminal = $atomTerm;
        }
        else
        {
            throw new Exception\LogicException(
                'Unrecognized payment method ' . $method .
                ' Merchant Id: ' . $merchant->getId());
        }

        return $terminal;
    }

    protected function pickTerminalForCardMethod($terminals, $hdfcTerm, $atomTerm)
    {
        //
        // For card, terminal deduction is as follows:
        // * Get all terminals for merchant
        // * If 1 terminal, then check card enabled and then off to the races!
        // * If 2 terminals, then check for card enabled for both
        // * If card is enabled for both, then select HDFC and.. off to the races!
        // * Otherwise select for whichever is card enabled.. and off to the races!
        //
        // Note: At the moment, cannot have more than 2 terminals
        //

        $count = $terminals->count();

        $terminal = null;
        if ($count === 1)
        {
            $terminal = $terminals->first();

            if ($terminal->isCardEnabled() === false)
            {
                return;
                throw new Exception\LogicException(
                    'Card not enabled for the merchant. Merchant Id: ' . $terminal->getMerchantId() .
                    ' Terminal Id: ' . $terminal->getId());
            }
        }
        else if ($count === 2)
        {
            if (($hdfcTerm !== null) and
                ($hdfcTerm->isCardEnabled()))
                $terminal = $hdfcTerm;
            else if (($atomTerm !== null) and
                     ($atomTerm->isCardEnabled()))
                $terminal = $atomTerm;
            else
            {
                return;
                throw new Exception\LogicException(
                    'No terminal has card transactions enabled. ' .
                    'Hdfc term id: ' . $hdfcTerm->getId(),
                    'Atom Term id: ' . $atomTerm->getId());
            }
        }

        return $terminal;
    }

    protected function getHdfcAndAtomTerm($terminals)
    {
        $hdfcTerm = $atomTerm = null;

        foreach ($terminals->all() as $term)
        {
            if ($term->isGateway(Payment\Gateway::HDFC))
                $hdfcTerm = $term;
            else if ($term->isGateway(Payment\Gateway::ATOM))
                $atomTerm = $term;
            else
                throw new Exception\LogicException(
                    'Unknown gateway. Terminal Id: ' . $terminal->getId() .
                    ' Gateway: ' . $terminal->getGateway());
        }

        return [$hdfcTerm, $atomTerm];
    }

    protected function validateCount($terminals, $merchant)
    {
        $count = $terminals->count();

        // if (($count > 2) or
        //     ($count === 0))
        if ($count > 2)
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