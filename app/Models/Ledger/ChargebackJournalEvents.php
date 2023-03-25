<?php

namespace RZP\Models\Ledger;

use RZP\Models\Adjustment\Entity;

class ChargebackJournalEvents extends BaseJournalEvents
{
    public static function createTransactionMessageForRazorpayDisputeDeduct(Entity $adjustment, $disputePublicId): array
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        return array(
            Constants::TRANSACTOR_ID                => $disputePublicId,
            Constants::MERCHANT_ID                  => $adjustment->getMerchantId(),
            Constants::API_TRANSACTION_ID           => $adjustment->getTransactionId(),
            Constants::CURRENCY                     => $adjustment->getCurrency(),
            Constants::TRANSACTOR_EVENT             => Constants::RAZORPAY_DISPUTE_DEDUCT,
            Constants::TRANSACTION_DATE             => $adjustment->getCreatedAt(),
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::GATEWAY_DISPUTE_PAYABLE_AMOUNT    => strval($adjustmentAmount)
            ]
        );
    }

    public static function createTransactionMessageForRazorpayDisputeReversal(Entity $adjustment, $disputePublicId): array
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        return array(
            Constants::TRANSACTOR_ID                => $disputePublicId,
            Constants::MERCHANT_ID                  => $adjustment->getMerchantId(),
            Constants::API_TRANSACTION_ID           => $adjustment->getTransactionId(),
            Constants::CURRENCY                     => $adjustment->getCurrency(),
            Constants::TRANSACTOR_EVENT             => Constants::RAZORPAY_DISPUTE_REVERSAL,
            Constants::TRANSACTION_DATE             => $adjustment->getCreatedAt(),
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::GATEWAY_DISPUTE_PAYABLE_AMOUNT    => strval($adjustmentAmount)
            ]
        );
    }
}
