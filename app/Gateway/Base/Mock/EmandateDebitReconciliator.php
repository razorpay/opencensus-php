<?php

namespace RZP\Gateway\Base\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class EmandateDebitReconciliator extends Reconciliator
{
    /**
     * Different gateways have different criteria for sending payments in the recon file.
     * To send payments do not match the criteria below, override this method in child class.
     *
     * @return PublicCollection
     */
    protected function getEntitiesToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        $entities = $this->repo
                         ->payment
                         ->fetchPaymentsCreatedBetween($this->gateway, $createdAtStart, $createdAtEnd);

        return $entities;
    }
}
