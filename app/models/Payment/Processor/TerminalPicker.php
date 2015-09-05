<?php

namespace Models\Payment\Processor;

use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Bank\IFSC;
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

        $func = 'pickTerminalFor'.ucfirst($method).'Method';

        return $this->$func($gatewayTerms, $payment);
    }

    protected function pickTerminalForCardMethod($gatewayTerms, $payment)
    {
        $terminal = null;

        $international = $payment->merchant->isInternational();

        $network = $payment->card->getNetworkCode();

        $gatewayOrder = array(
            Gateway::HDFC,
            Gateway::AXIS_MIGS,
            Gateway::KOTAK);

        foreach ($gatewayOrder as $gateway)
        {
            if ((isset($gatewayTerms[$gateway])) and
                (Gateway::isCardNetworkSupported($network, $gateway)))
            {
                return $gatewayTerms[$gateway];
            }
        }

        // if (isset($gatewayTerms[Gateway::AXIS_GENIUS]))
        // {
        //     return $gatewayTerms[Gateway::AXIS_GENIUS];
        // }

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

    protected function pickTerminalForNetbankingMethod($gatewayTerms, $payment)
    {
        $terminal = null;

        $bank = $this->payment->getBank();

        if ($bank === IFSC::HDFC)
        {
            if (isset($gatewayTerms[Payment\Gateway::NETBANKING_HDFC]) === true)
            {
                return $gatewayTerms[Payment\Gateway::NETBANKING_HDFC];
            }

            return;
        }

        // if ((isset($gatewayTerms[Payment\Gateway::BILLDESK]) === true) and
        //     (Netbanking::isBilldeskSupportedBank($bank)))
        // {
        //     return $gatewayTerms[Payment\Gateway::BILLDESK];
        // }

        if ($this->mode === Mode::TEST)
        {
            if ((isset($gatewayTerms[Payment\Gateway::PAYTM]) === true) and
                (Netbanking::isPaytmSupportedBank($bank)))
            {
                return $gatewayTerms[Payment\Gateway::PAYTM];
            }

            if (isset($gatewayTerms[Payment\Gateway::ATOM]) === true)
            {
                return $gatewayTerms[Payment\Gateway::ATOM];
            }
        }

        return $terminal;
    }

    protected function pickTerminalForWalletMethod($gatewayTerms)
    {
        $terminal = null;

        $wallet = $this->payment->getWallet();

        if ((isset($gatewayTerms[Payment\Gateway::PAYTM]) === true) and
            ($wallet === 'paytm'))
        {
            return $gatewayTerms[Payment\Gateway::PAYTM];
        }

        if ((isset($gatewayTerms[Payment\Gateway::MOBIKWIK]) === true) and
            ($wallet === 'mobikwik'))
        {
            return $gatewayTerms[Payment\Gateway::MOBIKWIK];
        }
    }

    protected function getSharedTerminal($payment)
    {
        $terminal = null;

        $method = $payment->getMethod();

        if ($method === Payment\Method::CARD)
        {
            $terminal = $this->getSharedTerminalForCard($payment);
        }
        else if ($method === Payment\Method::NETBANKING)
        {
            $terminal = $this->getSharedTerminalForNetbanking($payment);
        }
        else if ($method === Payment\Method::WALLET)
        {
            $terminal = $this->getSharedTerminalForWallet($payment);
        }

        if ($terminal !== null)
        {
            return $terminal;
        }

        if ($this->terminalExists(Shared::ATOM_RAZORPAY_TERMINAL))
        {
            return $this->terminal;
        }

        if ($this->terminalExists(Shared::SHARP_RAZORPAY_TERMINAL))
        {
            if ($this->mode !== Mode::TEST)
            {
                throw new Exception\LogicException(
                    'Sharp gateway terminal can only be selected in test mode');
            }

            return $this->terminal;
        }

        return $terminal;
    }

    protected function getSharedTerminalForCard($payment)
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

    protected function getSharedTerminalForNetbanking($payment)
    {
        $bank = $this->payment->getBank();

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

    protected function getSharedTerminalForWallet($payment)
    {
        $wallet = $payment->getWallet();

        if ($wallet === 'paytm')
        {
            if ($this->terminalExists(Shared::PAYTM_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }
        }

        if ($wallet === 'mobikwik')
        {
            if ($this->terminalExists(Shared::MOBIKWIK_RAZORPAY_TERMINAL))
            {
                return $this->terminal;
            }
        }
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
        return $this->repo->fetch([], $merchant->getId());
    }

    protected function filterTerminalsByMethod($terminals, $method)
    {
        $terminals->filter(function($item) use($method)
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