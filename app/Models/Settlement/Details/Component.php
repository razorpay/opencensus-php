<?php

namespace RZP\Models\Settlement\Details;

use RZP\Exception;

class Component
{
    const REFUND_DOMESTIC         = 'refund_domestic';
    const REFUND_INTERNATIONAL    = 'refund_international';
    const PAYOUT                  = 'payout';
    const PAYMENT_DOMESTIC        = 'payment_domestic';
    const PAYMENT_INTERNATIONAL   = 'payment_international';
    const ADJUSTMENT              = 'adjustment';
    const FEE                     = 'fee';
    const TAX                     = 'tax';
    const FEE_CREDITS             = 'fee_credits';
    const REFUND_CREDITS          = 'refund_credits';
    const TRANSFER                = 'transfer';
    const REVERSAL                = 'reversal';
    const DISPUTE                 = 'dispute';
    const COMMISSION              = 'commission';
    const FUND_ACCOUNT_VALIDATION = 'fund_account_validation';
    const SETTLEMENT_TRANSFER     = 'settlement_transfer';
    const SETTLEMENT_ONDEMAND     = 'settlement.ondemand';
    const CREDIT_REPAYMENT        = 'credit_repayment';

    public static function validateComponent(string $component)
    {
        if (defined(__CLASS__ . '::' . strtoupper($component)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid settlement component: ' . $component);
        }
    }

    public static function getAllComponents()
    {
        return [
            self::PAYMENT_DOMESTIC,
            self::PAYMENT_INTERNATIONAL,
            self::REFUND_DOMESTIC,
            self::REFUND_INTERNATIONAL,
            self::ADJUSTMENT,
            self::PAYOUT,
            self::FEE_CREDITS,
            self::REFUND_CREDITS,
            self::TRANSFER,
            self::REVERSAL,
            self::DISPUTE,
            self::COMMISSION,
            self::FUND_ACCOUNT_VALIDATION,
            self::SETTLEMENT_TRANSFER,
            self::SETTLEMENT_ONDEMAND,
            self::CREDIT_REPAYMENT,
        ];
    }
}
