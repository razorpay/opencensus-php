<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\AuthType;

class EmandateSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    /**
     * Arrange emandate terminals in the order
     * Direct bank integration first, then through NPCI
     *
     * @param $terminals
     *
     * @return array
     */
    public function gatewaySorter($terminals)
    {
        $method = $this->input['payment']->getMethod();

        $bank = $this->input['payment']->getBank();

        $authType = $this->input['payment']->getAuthType();

        // No need unless doing for emandate
        if (($method !== Method::EMANDATE) or
            ($authType !== AuthType::NETBANKING))
        {
            return $terminals;
        }

        $gateways = $this->getDirectEmandateGatewayIfExists($bank);

        if ($this->isBankSupportedByNpciEmandate($bank) === true)
        {
           $gateways[] = Gateway::ENACH_NPCI_NETBANKING;
        }

        $sortedTerminals = [];

        foreach ($gateways as $gateway)
        {
            foreach ($terminals as $terminal)
            {
                if ($terminal->getGateway() === $gateway)
                {
                    $sortedTerminals[] = $terminal;
                }
            }
        }

        return $sortedTerminals;
    }

    protected function getDirectEmandateGatewayIfExists($bank)
    {
        if (in_array($bank, Gateway::EMANDATE_NB_DIRECT_BANKS) === true)
        {
           return (array) Gateway::$netbankingToGatewayMap[$bank];
        }

        return [];
    }

    protected function isBankSupportedByNpciEmandate($bank)
    {
        $netbankingBanks = array_unique(
            array_merge(
                Gateway::ENACH_NPCI_NB_AUTH_CARD_BANKS,
                Gateway::ENACH_NPCI_NB_AUTH_NETBANKING_BANKS
            )
        );

        return in_array($bank, $netbankingBanks,true);
    }
}
