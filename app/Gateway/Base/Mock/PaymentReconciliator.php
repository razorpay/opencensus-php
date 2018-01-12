<?php

namespace RZP\Gateway\Base\Mock;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Timezone;

class PaymentReconciliator extends Reconciliator
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

        $statuses = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        return $this->repo
                    ->payment
                    ->fetchPaymentsWithStatus($createdAtStart, $createdAtEnd, $this->gateway, $statuses);
    }
}
