<?php

namespace RZP\Models\Terminal\Sorters;

use Carbon\Carbon;

use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Gateway\Downtime\Entity as Downtime;

/*
 * Documentation here :
 * https://docs.google.com/document/d/1bsx1t21Q_n5cQBnM_REyolGbn92Fzscu0LrRYiKqqsU/
 *
 */
class DowntimeSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway_downtime',
    ];

    const ALL = 'all';

    /**
     * Sorts the terminals wrt the downtimes.
     *
     * The terminals related to the downtimes,
     * will be pushed to the bottom of the list.
     * Scenarios & cases are mentioned in the spec.
     *
     * There is no weightage given to the priority in which the
     * terminals are moved to the end of the list.
     *
     * @param $terminals array of Terminal\Entity
     * @param $input array
     * @return $sortedTerminals array of Terminal\Entity
     */
    public function gatewayDowntimeSorter($terminals, array $input)
    {
        $terminalGateways = $this->getTerminalGateways($terminals);

        $params = $this->buildQueryParams($input['payment'], $terminalGateways);

        $downtimes = $this->getRelevantDowntimes($params);

        $sortedTerminals = $this->sortTerminals($terminals, $downtimes);

        return $sortedTerminals;
    }

    /**
     * Performs sorting on relevant downtimes & given terminals
     *
     * Separates terminals into boosted/nonBoosted
     * Returns after merging boosted with nonBoosted terminals
     *
     * @param $terminals array
     * @param $downtimes array
     * @param array (sorted array of terminals)
     */
    protected function sortTerminals($terminals, $downtimes)
    {
        if (count($downtimes) === 0)
        {
            return $terminals;
        }

        $boostedTerminals = [];

        $nonBoostedTerminals = [];

        foreach ($terminals as $terminal)
        {
            for ($i = 0; $i < count($downtimes); $i++)
            {
                $downtime = $downtimes[$i];

                $boostTerminal = $this->shouldBoostTerminal($terminal, $downtime);

                if ($boostTerminal === false)
                {
                    $nonBoostedTerminals[] = $terminal;
                }

                else
                {
                    $boostedTerminals[] = $terminal;
                }
            }
        }

        return array_merge($boostedTerminals, $nonBoostedTerminals);
    }

    /**
     * Checks if priority of terminal should be kept low
     *
     * Filters on basis on terminal related data
     * 1. Terminal Id
     * 2. Terminal Gateway
     * 3. Downtime Gateway
     *
     * @param $terminal Terminal\Entity
     * @param $downtime Downtime
     * @return bool
     */
    protected function shouldBoostTerminal(Terminal\Entity $terminal, Downtime $downtime)
    {
        if ($terminal->getId() === $downtime->getTerminalId())
        {
            return false;
        }

        if ($terminal->getGateway() === $downtime->getGateway())
        {
            return false;
        }

        if ($downtime->getGateway() === self::ALL)
        {
            return false;
        }

        return true;
    }

    /**
     * Gets the list of gateways from the given terminals
     *
     * Sometimes the list might have duplicates,
     * as two or more terminals can have same gateway
     * Hence, we use `array_unique` before returning
     *
     * @param $terminals array of Terminal\Entity
     * @return $terminalGateways array
     */
    protected function getTerminalGateways($terminals)
    {
        $terminalGateways = [];

        foreach ($terminals as $terminal)
        {
            $terminalGateways[] = $terminal->getGateway();
        }

        return array_unique($terminalGateways);
    }

    /**
     * Gets the list of relevant downtimes from database
     *
     * @param $params array
     * @return $downtimes array of Downtime
     */
    protected function getRelevantDowntimes(array $params)
    {
        $timestamp = Carbon::now('Asia/Kolkata')->timestamp;

        $downtimes = $this->repo->gateway_downtime
                                ->fetchDowntimesForSorter($params, $timestamp);

        return $downtimes->toArray();
    }

    /**
     * Forms the query params for fetching downtimes,
     * based on payment and list of terminal gateways.
     *
     * Common fields :
     * 1. `method`  : netbanking/card/emi
     * 2. `gateway` : list of terminal gateways & `all`
     * 3. `partial` : false (since we do not want to filter
     *                       downtimes with low success rate)
     *
     * Fields for netbanking :
     * 1. `method` : netbanking
     * 2. `issuer` : bank name
     *
     * Fiels for Card/Emi :
     * 1. `method`    : [card, emi]
     * 2. `network`   : card network & `all`
     * 3. `card_type` : card type & `all`
     * 4. `issuer`    : if present & `all`, else, only `all`
     *
     * @param $payment Payment\Entity
     * @param $terminalGateways array
     * @return $params array
     */
    protected function buildQueryParams(Payment\Entity $payment, array $terminalGateways)
    {
        $method = $payment->getMethod();

        if ($method === Payment\Method::NETBANKING)
        {
            $params = [
                Downtime::METHOD => Payment\Method::NETBANKING,
                Downtime::ISSUER => [$payment->getBankName(), self::ALL],
            ];
        }

        else if ($payment->isMethodCardOrEmi() === true)
        {
            $params = [
                Downtime::METHOD    => [Payment\Method::CARD, Payment\Method::EMI],
                Downtime::NETWORK   => [$payment->card->getNetwork(), self::ALL],
                Downtime::CARD_TYPE => [$payment->card->getType(), self::ALL],
            ];

            $issuer = $payment->card->getIssuer();

            if (empty($issuer) === false)
            {
                $params[Downtime::ISSUER] = [$issuer, self::ALL];
            }

            else
            {
                $params[Downtime::ISSUER] = self::ALL;
            }
        }

        $params[Downtime::GATEWAY] = array_merge($terminalGateways, [self::ALL]);

        $params[Downtime::PARTIAL] = false;

        return $params;
    }
}
