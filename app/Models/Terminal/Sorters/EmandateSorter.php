<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class EmandateSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    /**
     * Arrange emandate terminals in the order
     * Direct bank first, next NPCI gateway, finally shared
     * In This order as well use,
     *
     * @param $terminals
     *
     * @return array
     */
    public function gatewaySorter($terminals)
    {
        $method = $this->input['payment']->getMethod();

        // No need unless doing for netbanking
        if ($method !== Method::EMANDATE)
        {
            return $terminals;
        }

        $bank = $this->input['payment']->getBank();

        $authType = $this->input['payment']->getAuthType();

        $gateways = $this->getEmandateGatewayIfExists($bank, $authType);

        if($this->isBankSupportedByNpciEmandate($bank) === true)
        {
            $gateways['direct_npci'] = 'direct' . '_' . Gateway::ENACH_RBL;
            $gateways['shared_npci'] = 'shared' . '_' . Gateway::ENACH_RBL;
        }

        $gateways = $this->getMerchantOrDefaultOrdering($gateways, $this->input['merchant']->getId());

        //this->arrangePriorityByMerchantAndBank($this->input['merchant']->getId(), $bank);

        $sortedTerminals = [];

        foreach ($gateways as $gateway)
        {
            foreach ($terminals as $terminal)
            {
                if ($this->getTerminalAndGatewayName($terminal) === $gateway)
                {
                    $sortedTerminals[] = $terminal;
                }
            }
        }

        return $sortedTerminals;
    }

    protected function getEmandateGatewayIfExists($bank, $authType)
    {
        if(in_array($bank, Gateway::$emandateBanks[$authType]))
        {
            $gateway =  Gateway::$netbankingToGatewayMap[$bank];
        }

        return [
            'direct_gateway' => 'direct' . '_' . $gateway,
            'shared_gateway' => 'shared' . '_' . $gateway
        ];
    }

    protected function isBankSupportedByNpciEmandate($bank)
    {
        return in_array($bank, Gateway::$gatewaysEmandateBanksMap[Gateway::ENACH_RBL]);
    }

    protected function getMerchantOrDefaultOrdering($gateways, $merchantID)     //TODO add logic for merchant selection of routing
    {
        return $this->getDefaultOrdering($gateways);
    }

    protected function getDefaultOrdering($gateways)
    {
        return [
            $gateways['direct_gateway'],
            $gateways['direct_npci'],
            $gateways['shared_npci'],
            $gateways['shared_gateway']
        ];
    }

    protected function getTerminalAndGatewayName($terminal)
    {
        if($terminal->isShared() === true)
        {
            return 'shared' . '_' . $terminal->getGateway();
        }
        else
        {
            return 'direct' . '_' . $terminal->getGateway();
        }
    }

    /**
     * Arrange the priority of gateways based on merchant and bank
     */
    protected function arrangePriorityByMerchantAndBank($merchant, $bank)
    {

        //TODO to be implemented
        /*if ($merchant === '4izmfM9TFCAgFN')
        {
            $index = array_search('ebs', $gatewaysPriority);

            if ($index !== false)
            {
                unset($gatewaysPriority[$index]);

                array_unshift($gatewaysPriority, 'ebs');
            }
        }*/
    }
}
