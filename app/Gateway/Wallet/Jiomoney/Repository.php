<?php

namespace RZP\Gateway\Wallet\Jiomoney;

use RZP\Gateway\Wallet\Base;

class Repository extends Base\Repository
{
    public function findSuccessfulPaymentsByPaymentIdAndAction(string $paymentId, string $action)
    {
        return $this->newQuery()
                    ->where(Base\Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Base\Entity::ACTION, '=', $action)
                    ->whereNotIn(
                        Base\Entity::STATUS_CODE,
                        StatusCode::getFailureStatuses()
                    )
                    ->firstOrFail();
    }
}
