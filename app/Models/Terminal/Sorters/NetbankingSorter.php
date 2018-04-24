<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Priority as GatewayPriority;

class NetbankingSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    /**
     * Arrange netbanking terminals in the order
     * Direct bank first, next Direct gateway, finally shared
     * In This order as well use,
     *
     * @param $terminals
     *
     * @return array
     */
    public function gatewaySorter($terminals, bool $verbose = false)
    {
        $method = $this->input['payment']->getMethod();

        // No need unless doing for netbanking
        if ($method !== Method::NETBANKING)
        {
            return $terminals;
        }

        $bank = $this->input['payment']->getBank();

        $gatewaysForBank = Gateway::getGatewaysForNetbankingBankIndexed($bank);

        $gatewaysPriority = (new GatewayPriority\Core)
                            ->getGatewaysForMethod($method);

        if ($verbose === true)
        {
            $this->trace->info(TraceCode::NETBANKING_GATEWAY_PRIORITY, $gatewaysPriority);
        }

        $this->arrangePriorityByMerchantAndBank($gatewaysPriority, $this->input['merchant']->getId(), $bank);

        $sortedTerminals = [];

        foreach ($gatewaysPriority as $gatewayType)
        {
            // First use the direct terminal
            $gateway = $this->getGatewayToMatch($gatewayType, $gatewaysForBank);

            // As the terminals are from the priority list
            // append to the terminal
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

    /**
     * Get gateway name to match with based on gateway type.
     *
     * @param string $gatewayType Gateway type, i.e direct or gatewayName
     * @param array $gatewaysForBank gateways that support bank
     *
     * @return string gateway
     */
    protected function getGatewayToMatch($gatewayType, $gatewaysForBank)
    {
        if (($gatewayType === 'direct') and
            (isset($gatewaysForBank['direct'])))
        {
            $gateway = $gatewaysForBank['direct'];
        }
        else
        {
            $gateway = $gatewayType;
        }

        return $gateway;
    }

    /**
     * Arrange the priority of netbanking gateways based on merchant and bank.
     * This is used for testing out new gateways.
     *
     * @param  array &$gatewaysPriority
     * @param  string $merchant
     * @param  string $bank
     * @return void
     */
    protected function arrangePriorityByMerchantAndBank(&$gatewaysPriority, $merchant, $bank)
    {
        //TODO Remove this extra code after testing
        if ($merchant === '4izmfM9TFCAgFN')
        {
            $index = array_search('ebs', $gatewaysPriority);

            if ($index !== false)
            {
                unset($gatewaysPriority[$index]);

                array_unshift($gatewaysPriority, 'ebs');
            }
        }
    }
}
