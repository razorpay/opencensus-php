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
        $params = $this->buildQueryParams($input['payment']);

        $downtimes = $this->getRelevantDowntimes($params);

        // todo filtering
    }

    protected function getRelevantDowntimes(array $params)
    {
        $timestamp = Carbon::now('Asia/Kolkata')->timestamp;

        $downtimes = $this->repo->gateway_downtime
                                ->fetchDowntimesForSorter($params, $timestamp);

        return $downtimes;
    }

    protected function buildQueryParams(Payment\Entity $payment)
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

        $params[Downtime::PARTIAL] = false;

        return $params;
    }
}
