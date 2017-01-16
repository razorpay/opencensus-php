<?php

namespace RZP\Gateway\Netbanking\Kotak;

use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Base;

class DailyFiles extends Base\DailyFiles
{
    protected function getClaimsData($from, $to)
    {
        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        // Payments made yesterday are reconciled today,
        // so forwarding time stamps by 1 day
        list($from, $to) = $this->updateTimeStamps($from, $to);

        $claims= $this->repo->payment->fetchReconciledPaymentsForGateway($from,
                                                                         $to,
                                                                         $this->gateway,
                                                                         $status);

        if ($claims->count() === 0)
        {
            return [0, ''];
        }

        $data = [];

        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;

        $gateway = $claim->terminal->getGateway();

        $action = 'generateClaims';

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function updateTimeStamps($from, $to)
    {
        $tsDifference = self::SECONDS_PER_DAY;

        return [$from + $tsDifference, $to + $tsDifference];
    }
}
