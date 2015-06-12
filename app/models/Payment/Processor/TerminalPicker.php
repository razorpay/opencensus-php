<?php

namespace Models\Payment\Processor;

use Constants\Mode;
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
    protected $mode;

    /**
     * Terminal selected for the transaction
     * @var Terminal\Entity
     */
    protected $terminal;

    /**
     * Terminal repository
     * @var Terminal\Repository
     */
    protected $repo;

    /**
     * Payment for which terminal has to be picked
     * @var Models\Payment\Entity
     */
    protected $payment;

    protected $merchant;

    public function __construct()
    {
        $this->repo = new Terminal\Repository;
    }

    public function selectTerminal($payment, $mode)
    {
        $this->payment = $payment;
        $this->merchant = $payment->merchant;
        $this->mode = $mode;

        $terminals = $this->getTerminals($payment->merchant);

        $this->validateCount($terminals, $payment->merchant);

        $terminal = $this->pickOneTerminal($payment, $terminals);
//$terminal = null;
        if ($terminal === null)
        {
            $terminal = $this->getSharedTerminal($payment);
        }

        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $payment->toArrayAdmin()]);
        }

        $payment->terminal()->associate($terminal);

        $payment->setGateway($terminal->getGateway());

        return $terminal;
    }

    public function hasCardTerminal($merchant)
    {
        $this->merchant = $merchant;

        $terminals = $this->getTerminals($merchant);

        $this->validateCount($terminals, $merchant);

        $gatewayTerms = $this->getGatewayTerminals($terminals);

        $card = false;

        foreach ($gatewayTerms as $gateway => $terminal)
        {
            $card = (Payment\Gateway::isMethodSupported('card', $gateway));

            if ($card === true)
            {
                break;
            }
        }

        return $card;
    }

    protected function pickOneTerminal($payment, $terminals)
    {
        $terminal = null;

        $merchant = $payment->merchant;

        $method = $payment->getMethod();

        $gatewayTerms = $this->getGatewayTerminals($terminals);

        if ($method === Payment\Method::CARD)
        {
            $terminal = $this->pickTerminalForCardMethod($gatewayTerms);
        }
        else if ($method === Payment\Method::NETBANKING)
        {
            $terminal = $this->pickTerminalForNetbankingMethod($gatewayTerms);
        }
        else
        {
            throw new Exception\LogicException(
                'Unrecognized payment method ' . $method .
                ' Merchant Id: ' . $merchant->getId());
        }

        return $terminal;
    }

    protected function pickTerminalForCardMethod($gatewayTerms)
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

        if (isset($gatewayTerms[Gateway::AXIS_MIGS]))
        {
            return $gatewayTerms[Gateway::AXIS_MIGS];
        }

        if (isset($gatewayTerms[Gateway::AXIS_GENIUS]))
        {
            return $gatewayTerms[Gateway::AXIS_GENIUS];
        }

        if (isset($gatewayTerms[Gateway::HDFC]))
        {
            return $gatewayTerms[Gateway::HDFC];
        }

        if ($this->mode === Mode::TEST)
        {
            // In test mode paytm supports only cards
            // but in live only netbanking.
            if (isset($gatewayTerms[Payment\Gateway::PAYTM]) === true)
            {
                return $gatewayTerms[Payment\Gateway::PAYTM];
            }
        }

        return $terminal;
    }

    protected function pickTerminalForNetbankingMethod($gatewayTerms)
    {
        $terminal = null;

        $bank = $this->payment->getBank();

        if ($bank === 'HDFC')
        {
            if (isset($gatewayTerms[Payment\Gateway::NETBANKING_HDFC]) === true)
            {
                return $gatewayTerms[Payment\Gateway::NETBANKING_HDFC];
            }
        }

        if (isset($gatewayTerms[Payment\Gateway::BILLDESK]) === true)
        {
            return $gatewayTerms[Payment\Gateway::BILLDESK];
        }

        if (isset($gatewayTerms[Payment\Gateway::PAYTM]) === true)
        {
            return $gatewayTerms[Payment\Gateway::PAYTM];
        }

        if (isset($gatewayTerms[Payment\Gateway::ATOM]) === true)
        {
            return $gatewayTerms[Payment\Gateway::ATOM];
        }

        return $terminal;
    }

    protected function getSharedTerminal($payment)
    {
        $terminal = null;

        $method = $payment->getMethod();

        $bank = $this->payment->getBank();

        if ($method === Payment\Method::CARD)
        {
            if ($payment->card->getNetwork() === Network::$fullName[Network::RUPAY])
            {
                if ($this->terminalExists(Shared::KOTAK_RAZORPAY_TERMINAL))
                {
                    return $this->terminal;
                }
            }

            if ($this->terminalExists(Shared::HDFC_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }

            if ($this->terminalExists(Shared::AXIS_MIGS_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }

            if ($this->terminalExists(Shared::AXIS_GENIUS_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }

            if ($this->mode === Mode::TEST)
            {
                // In test mode paytm supports only cards
                // but in live only netbanking.
                if ($this->terminalExists(Shared::PAYTM_RAZORPAY_TERMINAL))
                {
                    return $this->terminal;
                }
            }
        }
        else if ($method === Payment\Method::NETBANKING)
        {
            if ($bank === 'HDFC')
            {
                if ($this->terminalExists(Shared::NETBANKING_HDFC_TERMINAL))
                {
                    return $this->terminal;
                }
            }

            if ($this->terminalExists(Shared::BILLDESK_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }

            if ($this->terminalExists(Shared::PAYTM_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }
        }

        if ($this->terminalExists(Shared::ATOM_RAZORPAY_TERMINAL))
        {
            return $this->terminal;
        }

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

        if ($count > Terminal\Entity::MAX_TERMINALS_COUNT)
        {
            throw new Exception\LogicException(
                'Terminals count not reasonable: ' . $count .
                ' Merchant Id: ' . $merchant->getId());
        }
    }

    protected function getTerminals($merchant)
    {
        return $this->repo->getByMerchantId($merchant->getId());
    }

    protected function filterTerminalsByMethod($terminals, $method)
    {
        $terminals->filter(function($item)
        {
            return ($item[$method] === '1');
        });
    }

    protected function terminalExists($terminal)
    {
        $this->terminal = $this->repo->find($terminal);

        return $this->terminal;
    }
}