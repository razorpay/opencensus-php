<?php

namespace RZP\Models\Ledger;

use RZP\Models\Adjustment\Entity as AdjustmentEntity;

class AdjustmentJournalEvents extends BaseJournalEvents
{

    public static function createTransactionMessageForManualAdjustment(AdjustmentEntity $adjustment): array
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        $transactorEvent = Constants::POSITIVE_ADJUSTMENT;

        if ($adjustment->getAmount() < 0)
        {
            $transactorEvent =  Constants::NEGATIVE_ADJUSTMENT;
        }

        return array(
            Constants::TRANSACTOR_ID                => $adjustment->getEntityId(),
            Constants::MERCHANT_ID                  => $adjustment->getMerchantId(),
            Constants::API_TRANSACTION_ID           => $adjustment->getTransactionId(),
            Constants::CURRENCY                     => $adjustment->getCurrency(),
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTION_DATE             => $adjustment->getCreatedAt(),
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::ADJUSTMENT_AMOUNT                 => strval($adjustmentAmount)
            ]
        );
    }

}
