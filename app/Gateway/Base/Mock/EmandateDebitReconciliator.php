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
        // Payments are from 9 to 9 cycle
        $createdAtStart = Carbon::yesterday(Timezone::IST)->addHours(9)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->addHours(9)->getTimestamp() - 1;

        $entities = $this->repo
                         ->payment
                         ->fetchCreatedPaymentsBetween($this->gateway, $createdAtStart, $createdAtEnd);

        return $entities;
    }
}
