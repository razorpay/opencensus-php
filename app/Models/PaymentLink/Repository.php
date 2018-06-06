<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Timezone;

class Repository extends Base\Repository
{
    protected $entity = 'payment_link';

    /**
     * Gets all ACTIVE status payment links which are past EXPIRE_BY.
     * Payment links which are in INACTIVE status are not affected.
     *
     * @return Base\PublicCollection
     */
    public function getActiveAndPastExpireByPaymentLinks(): Base\PublicCollection
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->get();
    }

    /**
     * Returns counts of payment which are succeeding (i.e. either
     * created, authorized) for given invoice.
     *
     * This method gets used in determining if enough slots are
     * available to initiate a payment
     *
     * @param Entity $paymentLink
     *
     * @return int
     */
    public function getSucceedingPaymentsCount(Entity $paymentLink): int
    {
        return $paymentLink->payments()
                           ->whereIn(
                               Payment\Entity::STATUS,
                               [
                                   Payment\Status::CREATED,
                                   Payment\Status::AUTHORIZED,
                               ])
                           ->count();
    }
}
