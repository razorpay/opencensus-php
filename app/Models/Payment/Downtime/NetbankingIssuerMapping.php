<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Payment\Processor\Netbanking;

class NetbankingIssuerMapping
{
    protected $gateways;

    protected $banks;

    const NETBANKING = 'netbanking';

    public function __construct()
    {
        $this->initializeGatewayIssuerRelations();
    }

    public function addDowntime(string $gateway, string $bank)
    {
        if (($gateway === Downtime\Entity::ALL) and
            (in_array($bank, [Downtime\Entity::ALL, Downtime\Entity::UNKNOWN])))
        {
            $this->addAllGatewayDowntimeForAllBank();
        }
        else if ($gateway === Downtime\Entity::ALL)
        {
            $this->addAllGatewayDowntimeForBank($bank);
        }
        else if (in_array($bank, [Downtime\Entity::ALL, Downtime\Entity::UNKNOWN]))
        {
            $this->addAllBankDowntimeForGateway($gateway);
        }
        else
        {
            $this->addDowntimeForGatewayBank($gateway, $bank);
        }
    }

    public function getUnavailableBanks()
    {
        $unavailableBanks = [];

        foreach ($this->banks as $bank => $gateways)
        {
            if (empty($gateways) === true)
            {
                $unavailableBanks[] = $bank;
            }
        }

        if (count($unavailableBanks) === count($this->banks))
        {
            return [Downtime\Entity::ALL];
        }
        else
        {
            return $unavailableBanks;
        }
    }

    public function getGatewaysSupportingBank(string $bank)
    {
        return $this->banks[$bank];
    }

    // --------------- Helper functions -------------------------------------

    protected function addAllGatewayDowntimeForAllBank()
    {
        foreach ($this->banks as $bank => $gateway)
        {
            $this->banks[$bank] = [];
        }

        foreach ($this->gateways as $gateway => $bank)
        {
            $this->gateways[$gateway] = [];
        }
    }

    protected function addAllGatewayDowntimeForBank(string $bank)
    {
        foreach ($this->banks[$bank] as $gateway)
        {
            array_delete($bank, $this->gateways[$gateway]);
        }

        $this->banks[$bank] = [];
    }

    protected function addAllBankDowntimeForGateway(string $gateway)
    {
        foreach ($this->gateways[$gateway] as $bank)
        {
            array_delete($gateway, $this->banks[$bank]);
        }

        $this->gateways[$gateway] = [];
    }

    protected function addDowntimeForGatewayBank(string $gateway, string $bank)
    {
        array_delete($gateway, $this->banks[$bank]);

        array_delete($bank, $this->gateways[$gateway]);
    }

    // --------------- Initialization ---------------------------------------

    protected function initializeGatewayIssuerRelations()
    {
        foreach (Gateway::$methodMap[self::NETBANKING] as $gateway)
        {
            $this->initializeBanksForGateway($gateway);
        }

        $this->banks[Downtime\Entity::ALL] = array_keys($this->gateways);

        $this->gateways[Downtime\Entity::ALL] = array_keys($this->banks);
    }

    protected function initializeBanksForGateway(string $gateway)
    {
        $banks = Netbanking::getSupportedBanksForGateway($gateway);

        foreach ($banks as $bank)
        {
            $this->gateways[$gateway][] = $bank;

            $this->banks[$bank][] = $gateway;
        }
    }
}
