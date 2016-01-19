<?php

namespace Models\Payment\Processor;

use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Bank\IFSC;
use Models\Card;
use Models\Card\Network;
use Models\Emi;
use Models\Merchant;
use Models\Payment;
use Models\Payment\Method;
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
    protected $payment;

    protected $merchant;

    protected $network;

    public function __construct()
    {
        $this->repo = new Terminal\Repository;
    }

    public function selectTerminal($payment, $mode)
    {
        $this->payment = $payment;
        $this->merchant = $payment->merchant;
        $this->mode = $mode;

        $merchantTerminals = $this->getTerminals($payment->merchant);

        $this->validateCount($merchantTerminals, $payment->merchant);

        $terminals = $this->getTerminalsKeyedByGateway($merchantTerminals);

        $terminal = $this->pickTerminal($terminals, $payment);

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

    protected function pickTerminal($terminals, $payment)
    {
        $method = $payment->getMethod();

        switch ($method)
        {
            case Method::CARD:
                $terminal = $this->pickCardTerminal($terminals, $payment);
                break;

            case Method::NETBANKING:
                $terminal = $this->pickNetbankingTerminal($terminals, $payment);
                break;

            case Method::WALLET:
                $terminal = $this->pickWalletTerminal($terminals, $payment);
                break;

            case Method::EMI:
                $terminal = $this->pickEmiTerminal($terminals, $payment);
                break;

            default:
                throw new Exception\LogicException(
                    'Not a valid method: ' . $method);
        }

        if (($terminal === null) and
            ($this->mode === Mode::TEST))
        {
            if ($this->terminalExists(Shared::SHARP_RAZORPAY_TERMINAL))
            {
                $terminal = $this->terminal;
            }
        }

        return $terminal;
    }

    protected function pickCardTerminal($terminals, $payment)
    {
        $terminal = null;

        $network = $payment->card->getNetworkCode();

        $directCardGateways = Gateway::$directCardGateways;

        $terminal = $this->selectDirectCardGateway($directCardGateways, $terminals, $network);

        if (($this->mode === Mode::TEST) and
            ($terminal === null))
        {
            $directCardGatewaysInTest = Gateway::$directCardGatewaysInTest;

            $terminal = $this->selectDirectCardGateway($directCardGatewaysInTest, $terminals, $network);
        }

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $this->getSharedTerminalForCard($payment);

        if ($terminal === null)
        {
            $this->checkForPartiallySupportedCardNetworks(
                    $terminals, $network);
        }

        return $terminal;
    }

    protected function pickNetbankingTerminal($terminals, $payment)
    {
        $terminal = null;

        $bank = $this->payment->getBank();

        // First check if we have direct tie-up with this bank and fetch it's gateway.
        $terminal = $this->selectDirectNetbankingBankTerminal($terminals, $bank);

        if ($terminal !== null)
        {
            return $terminal;
        }

        // Select terminal from our aggregator tie-ups
        // for netbanking like billdesk, etc.
        $terminal = $this->selectDirectNetbankingGatewayTerminal($terminals, $bank);

        if ($terminal !== null)
        {
            return $terminal;
        }

        return $this->getSharedTerminalForNetbanking($payment);

    }

    protected function pickWalletTerminal($terminals, $payment)
    {
        $terminal = null;

        $wallet = $this->payment->getWallet();

        $gateway = Gateway::getGatewayForWallet($wallet);

        if (isset($terminals[$gateway]) === true)
        {
            return $terminals[$gateway];
        }

        return $this->getSharedTerminalForWallet($payment);
    }

    protected function pickEmiTerminal($terminals, $payment)
    {
        $bank = $this->payment->getBank();

        if ($bank === IFSC::KKBK)
        {
            // for kotak, process as normal card transaction and mail for emi
            return $this->pickCardTerminal($terminals, $payment);
        }

        return $this->getSharedTerminalForEmi($payment);
    }

    protected function getSharedTerminalForCard($payment)
    {
        $terminal = $this->getSharedCategoryTerminalForCard($payment);

        if ($terminal !== null)
        {
            return $terminal;
        }

        return $this->getSharedGenericTerminalForCard($payment);
    }

    protected function getSharedCategoryTerminalForCard($payment)
    {
        $international = $payment->merchant->isInternational();

        $network = $payment->card->getNetworkCode();
        $category = $payment->merchant->getCategory();

        $terminal = $this->repo->getSharedTerminalForGatewayWithCategory(
                                    Gateway::HDFC, $category);

        return $terminal;
    }

    protected function getSharedGenericTerminalForCard($payment)
    {
        $network = $payment->card->getNetworkCode();

        if ($payment->merchant->isInternational())
        {
            $gateways = Gateway::$internationalCardGateways;

            if ($this->selectSharedCardTerminalFromGatewayList($gateways, $network))
            {
                return $this->terminal;
            }
        }

        $gateways = Gateway::$domesticCardGateways;

        if ($this->selectSharedCardTerminalFromGatewayList($gateways, $network))
        {
            return $this->terminal;
        }

        if ($this->mode === Mode::TEST)
        {
            $gateways = Gateway::$domesticCardGatewaysInTest;

            if ($this->selectSharedCardTerminalFromGatewayList($gateways, $network))
            {
                return $this->terminal;
            }
        }
    }

    protected function getSharedTerminalForNetbanking($payment)
    {
        $bank = $this->payment->getBank();

        $terminal = $this->selectSharedDirectNetbankingBankTerminal($bank);

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $this->selectSharedGatewayNetbankingTerminal();

        return $terminal;
    }

    protected function checkForPartiallySupportedCardNetworks($terminals, $network)
    {
        $networks = Gateway::$partiallySupportedCardNetworks;

        if (in_array($network, $networks))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
        }
    }

    protected function getSharedTerminalForWallet($payment)
    {
        $wallet = $payment->getWallet();

        // Payzapp can have category specific terminals. So we need to
        // first check for those.
        if ($wallet === Wallet::PAYZAPP)
        {
            $category = $payment->merchant->getCategory();

            $terminal = $this->getSharedCategoryTerminalForPayzapp($category);

            if ($terminal !== null)
            {
                return $terminal;
            }
        }

        $gateway = Gateway::getGatewayForWallet($wallet);

        $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

        if ($this->terminalExists($sharedTerminal))
        {
            return $this->terminal;
        }
    }

    protected function getTerminalsKeyedByGateway($merchantTerminals)
    {
        $terminals = [];

        foreach ($merchantTerminals->all() as $term)
        {
            $gateway = $term->getGateway();

            Gateway::validateGateway($gateway);

            $terminals[$gateway] = $term;
        }

        return $terminals;
    }

    protected function selectDirectCardGateway($cardGateways, $terminals, $network)
    {
        foreach ($cardGateways as $gateway)
        {
            if ((isset($terminals[$gateway])) and
                (Gateway::isCardNetworkSupported($network, $gateway)))
            {
                return $terminals[$gateway];
            }
        }
    }

    protected function selectDirectNetbankingBankTerminal($termianls, $bank)
    {
        if (Gateway::isNetbankingBankDirectlySupported($bank) === false)
        {
            return;
        }

        $gateway = Gateway::$netbankingToGatewayMap[$bank];

        if (isset($terminals[$gateway]))
        {
            return $terminals[$gateway];
        }
    }

    protected function selectDirectNetbankingGatewayTerminal($terminals, $bank)
    {
        $gateway = Gateway::BILLDESK;

        if ((isset($terminals[$gateway]) === true) and
            (Netbanking::isBankSupportedByGateway($gateway, $bank)))
        {
            return $terminals[$gateway];
        }

        if ($this->mode === Mode::TEST)
        {
            $directNetbankingGatewaysInTest = Gateway::$directNetbankingGatewaysInTest;

            foreach ($directNetbankingGatewaysInTest as $gateway)
            {
                if ((isset($terminals[$gateway]) === true) and
                    (Netbanking::isBankSupportedByGateway($bank, $gateway)))
                {
                    return $terminals[$gateway];
                }
            }
        }
    }

    protected function selectSharedDirectNetbankingBankTerminal($bank)
    {
        if (Gateway::isNetbankingBankDirectlySupported($bank) === false)
        {
            return;
        }

        $gateway = Gateway::$netbankingToGatewayMap[$bank];

        $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

        if ($this->terminalExists($sharedTerminal))
        {
            return $this->terminal;
        }
    }

    protected function selectSharedGatewayNetbankingTerminal()
    {
        $gateways = Gateway::$netbankingGateways;

        foreach ($gateways as $gateway)
        {
            $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

            if ($this->terminalExists($sharedTerminal))
            {
                return $this->terminal;
            }
        }
    }

    protected function getSharedCategoryTerminalForPayzapp($category)
    {
        $terminals = $this->repo->getSharedTerminalForGateway(Gateway::WALLET_PAYZAPP);

        foreach ($terminals as $terminal)
        {
            if ($terminal->getCategory() === $category)
            {
                $this->terminal = $terminal;

                return $terminal;
            }
        }
    }

    protected function getSharedTerminalForEmi($payment)
    {
        $bank = $this->payment->getBank();
        
        $gateway = Payment\Gateway::$emiBankToGatewayMap[$bank];

        $emiPlanId = $this->payment->getEmiPlanId();
        
        $emiPlan = (new Emi\Repository)->findOrFail($emiPlanId);
                
        $emiDuration = $emiPlan->getDuration();

        $terminal = $this->repo->getEmiTerminal(Merchant\Account::SHARED_ACCOUNT, $gateway, $emiDuration);
        
        return $terminal;       
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
        return $this->repo->fetch(['count' => 100], $merchant->getId());
    }

    protected function selectSharedCardTerminalFromGatewayList($gateways, $network)
    {
        foreach ($gateways as $gateway)
        {
            $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

            $terminal = $this->terminalExists($sharedTerminal);

            if (($terminal !== null) and
                (Gateway::isCardNetworkSupported($network, $gateway)))
            {
                return $terminal;
            }
        }
    }

    protected function terminalExists($terminal)
    {
        $this->terminal = $this->repo->find($terminal);

        return $this->terminal;
    }
}
