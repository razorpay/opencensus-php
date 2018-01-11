<?php

namespace RZP\Gateway\Base\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class RefundReconciliator extends Reconciliator
{
    protected function getEntitiesToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->repo->refund->fetch(
            [
                'from'    => $createdAtStart,
                'to'      => $createdAtEnd,
                'gateway' => $this->gateway,
            ]);
    }
}
