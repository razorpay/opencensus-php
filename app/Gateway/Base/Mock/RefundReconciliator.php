<?php

namespace RZP\Gateway\Base\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;

class RefundReconciliator extends Reconciliator
{
    protected function getEntitiesToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'getEntitiesToReconcile'
        ]);

        return $this->repo->refund->fetch(
            [
                'from'    => $createdAtStart,
                'to'      => $createdAtEnd,
                'gateway' => $this->gateway,
            ]);
    }
}
