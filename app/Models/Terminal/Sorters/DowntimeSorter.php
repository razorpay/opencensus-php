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

    public function gatewayDowntimeSorter($terminals, array $input)
    {
        $terminalGateways = $this->getTerminalGateways($terminals);

        $params = $this->buildQueryParams($input['payment'], $terminalGateways);

        $downtimes = $this->getRelevantDowntimes($params);

        $sortedTerminals = $this->sortTerminals($terminals, $downtimes);

        return $sortedTerminals;
    }

    protected function sortTerminals($terminals, $downtimes)
    {
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
     * @param $terminal
     * @param $downtime
     * @return bool
     */
    protected function shouldBoostTerminal($terminal, $downtime)
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

    protected function getTerminalGateways($terminals)
    {
        $terminalGateways = [];

        foreach ($terminals as $terminal)
        {
            $terminalGateways[] = $terminal->getGateway();
        }

        return array_unique($terminalGateways);
    }

    protected function getRelevantDowntimes(array $params)
    {
        $timestamp = Carbon::now('Asia/Kolkata')->timestamp;

        $downtimes = $this->repo->gateway_downtime
                                ->fetchDowntimesForSorter($params, $timestamp);

        return $downtimes;
    }

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

        if ($terminalGateways)
        {
            $params[Downtime::GATEWAY] = [$terminalGateways, self::ALL];
        }

        else
        {
            $params[Downtime::GATEWAY] = self::ALL;
        }

        $params[Downtime::PARTIAL] = false;

        return $params;
    }
}
