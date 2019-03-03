<?php

namespace RZP\Models\Gateway\MethodDowntime;

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
        if ($gateway === Downtime\Entity::ALL)
        {
            $this->addAllGatewayDowntimeForBank($bank);
        }
        else if ($bank === Downtime\Entity::ALL)
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

        return $unavailableBanks;
    }

    public function getGatewaysSupportingBank(string $bank)
    {
        return $this->banks[$bank];
    }

    // --------------- Helper functions -------------------------------------

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
