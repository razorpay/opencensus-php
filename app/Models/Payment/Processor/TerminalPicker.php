<?php

namespace RZP\Models\Payment\Processor;

use RZP\Constants\Mode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card;
use RZP\Models\Card\Network;
use RZP\Models\Emi;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Shared;
use RZP\Exception;
use RZP\Error\ErrorCode;
use App;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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

    protected $sharedTerminals = [];

    public function __construct()
    {
        $this->repo = new Terminal\Repository;

        $this->app = \App::getFacadeRoot();
    }

    public function selectTerminal($payment, $mode, $options = [])
    {
        $this->payment = $payment;
        $this->merchant = $payment->merchant;
        $this->mode = $mode;

        $merchantTerminals = $this->getTerminals($payment->merchant);

        // Checks if the merchant has more terminals than the maximum allowed.
        $this->validateTerminalCount($merchantTerminals);

        $terminals = $this->getTerminalsKeyedByGateway($merchantTerminals);

        // $this->app['trace']->info(TraceCode::MERCHANT_TERMINALS, [$terminals]);

        $terminal = $this->pickTerminal($terminals, $payment);

        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $payment->toArrayAdmin()]);
        }

        if (isset($options['chance']))
        {
            $terminal = (new Terminal\Binning)->pick($terminal, $options['chance'], ['payment' => $payment]);
        }

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

            case Method::UPI:
                $terminal = $this->pickUpiTerminal($terminals, $payment);
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

        $category = $this->payment->merchant->getCategory();

        $isTPVRequired = $this->payment->merchant->isTPVRequired();

        if ($isTPVRequired)
        {
            $terminal = $this->selectSharedTPVTerminal($category);

            if (($terminal === null) and
                ($this->mode === Mode::LIVE))
            {
                throw new Exception\ServerErrorException(
                    'A terminal with support for third party validation was not found.',
                    ErrorCode::SERVER_ERROR
                );
            }

            return $terminal;
        }

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

        return $this->getSharedTerminalForNetbanking();
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

    protected function pickUpiTerminal($terminals, $payment)
    {
        // Discuss the logic for UPI terminal picker
        $sharedTerminal = Shared::getSharedTerminalForGateway(Gateway::UPI_ICICI);

        if ($this->terminalExists($sharedTerminal))
        {
            return $this->terminal;
        }
    }

    protected function pickEmiTerminal($terminals, $payment)
    {
        $bank = $this->payment->getBank();

        $cardTerminalBanks = Gateway::$emiBanksUsingCardTerminals;

        if (in_array($bank, $cardTerminalBanks))
        {
            // for Kotak, process as normal card transaction and mail for emi
            return $this->pickCardTerminal($terminals, $payment);
        }

        return $this->getSharedTerminalForEmi();
    }

    protected function getSharedTerminalForCard($payment)
    {
        $terminal = $this->getSharedCategoryTerminalForCard($payment);

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $this->getSharedGenericTerminalForCard($payment);

        if ($terminal !== null)
        {
            return $terminal;
        }

        return $this->getSharedGenericTerminalForCard2($payment);
    }

    protected function getSharedCategoryTerminalForCard($payment)
    {
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

    protected function getSharedGenericTerminalForCard2($payment)
    {
        $this->getSharedTerminals();

        $network = $payment->card->getNetworkCode();

        if ($payment->merchant->isInternational())
        {
            $gateways = Gateway::$internationalCardGateways;

            if ($this->selectSharedCardTerminalFromGatewayList2($gateways, $network))
            {
                return $this->terminal;
            }
        }

        $gateways = Gateway::$domesticCardGateways;

        if ($this->selectSharedCardTerminalFromGatewayList2($gateways, $network))
        {
            return $this->terminal;
        }

        if ($this->mode === Mode::TEST)
        {
            $gateways = Gateway::$domesticCardGatewaysInTest;

            if ($this->selectSharedCardTerminalFromGatewayList2($gateways, $network))
            {
                return $this->terminal;
            }
        }
    }

    protected function getSharedTerminalForNetbanking()
    {
        $bank = $this->payment->getBank();

        $terminal = $this->selectSharedDirectNetbankingBankTerminal($bank);

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $this->selectSharedGatewayNetbankingTerminal();

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $this->selectSharedDirectNetbankingBankTerminal2($bank);

        if ($terminal !== null)
        {
            return $terminal;
        }

        $terminal = $this->selectSharedGatewayNetbankingTerminal2();

        return $terminal;
    }

    protected function checkForPartiallySupportedCardNetworks($terminals, $network)
    {
        $networks = Gateway::$partiallySupportedCardNetworks;

        if ((in_array($network, $networks)) and
            ($this->mode === Mode::LIVE))
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

        return null;
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

    protected function selectDirectNetbankingBankTerminal($terminals, $bank)
    {
        if (Gateway::isNetbankingBankDirectlySupported($bank) === false)
        {
            return null;
        }

        $gateway = Gateway::$netbankingToGatewayMap[$bank];

        // $this->app['trace']->info(TraceCode::MERCHANT_TERMINALS,
        // ['bank_mapped_gateway' => $gateway, 'terminals' => $terminals, 'bank'=> $bank]);

        if (isset($terminals[$gateway]))
        {
            return $terminals[$gateway];
        }
    }

    protected function selectDirectNetbankingGatewayTerminal($terminals, $bank)
    {
        $gateway = Gateway::BILLDESK;

        if ((isset($terminals[$gateway]) === true) and
            (Netbanking::isBankSupportedByGateway($bank, $gateway)))
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
            return null;
        }

        $gateway = Gateway::$netbankingToGatewayMap[$bank];

        $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

        if ($this->terminalExists($sharedTerminal))
        {
            return $this->terminal;
        }
    }

    protected function selectSharedDirectNetbankingBankTerminal2($bank)
    {
        if (Gateway::isNetbankingBankDirectlySupported($bank) === false)
        {
            return null;
        }

        $gateway = Gateway::$netbankingToGatewayMap[$bank];

        $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

        $this->getSharedTerminals();

        return $this->sharedTerminalExists($sharedTerminal);
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

    protected function selectSharedGatewayNetbankingTerminal2()
    {
        $this->getSharedTerminals();

        $gateways = Gateway::$netbankingGateways;

        foreach ($gateways as $gateway)
        {
            $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

            $terminal = $this->sharedTerminalExists($sharedTerminal);

            if ($terminal !== null)
            {
                return $terminal;
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

    protected function getSharedTerminalForEmi()
    {
        $bank = $this->payment->getBank();

        $gateway = Payment\Gateway::$emiBankToGatewayMap[$bank];

        $emiPlanId = $this->payment->getEmiPlanId();

        $emiPlan = (new Emi\Repository)->findOrFail($emiPlanId);

        $emiDuration = $emiPlan->getDuration();

        $terminal = $this->repo->getEmiTerminal(Merchant\Account::SHARED_ACCOUNT, $gateway, $emiDuration);

        return $terminal;
    }

    protected function validateTerminalCount($terminals)
    {
        $count = $terminals->count();

        if ($count > Terminal\Entity::MAX_TERMINALS_COUNT)
        {
            throw new Exception\LogicException(
                'Terminals count not reasonable: ' . $count .
                ' Merchant Id: ' . $this->merchant->getId());
        }
    }

    protected function getTerminals($merchant)
    {
        return $this->repo->fetch(['count' => 100], $merchant->getId());
    }

    protected function getSharedTerminals()
    {
        $gatewayTerminals = $this->repo->getSharedTerminalsOnCommonAccount();

        $terminals = [];

        foreach ($gatewayTerminals as $terminal)
        {
            $gateway = $terminal->getGateway();

            if (isset($terminals[$gateway]))
            {
                array_push($terminals[$gateway], $terminal);
            }
        }

        $this->sharedTerminals = $terminals;

        return $this->sharedTerminals;
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

    protected function selectSharedCardTerminalFromGatewayList2($gateways, $network)
    {
        foreach ($gateways as $gateway)
        {
            $sharedTerminal = Shared::getSharedTerminalForGateway($gateway);

            $terminal = $this->sharedTerminalExists($sharedTerminal);

            if (($terminal !== null) and
                (Gateway::isCardNetworkSupported($network, $gateway)))
            {
                return $terminal;
            }
        }
    }

    protected function selectSharedTPVTerminal($category)
    {
        $terminal = null;

        $gateway = Gateway::BILLDESK;

        $sharedTerminal = $this->repo->getSharedTerminalForGatewayWithCategory(
                                    $gateway, $category);

        if (empty($sharedTerminal) === false)
        {
            $terminal = $this->terminalExists($sharedTerminal->getId());
        }

        return $terminal;
    }

    protected function terminalExists($terminal)
    {
        $this->terminal = $this->repo->find($terminal);

        return $this->terminal;
    }

    protected function sharedTerminalExists($terminal)
    {
        $sharedTerminals = $this->sharedTerminals;

        foreach ($sharedTerminals as $shared)
        {
            if ($shared->getId() === $terminal)
            {
                return $terminal;
            }
        }
    }
}
