<?php

namespace RZP\Models\Merchant\Balance;

use App;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Metric extends Base\Core
{
    //Label names
    const LABEL_BALANCE_TYPE            = 'balance_type';
    const LABEL_TRANSACTION_TYPE        = 'transaction_type';
    const LABEL_BALANCE_AMOUNT          = 'balance_amount';
    const LABEL_THRESHOLD_PERCENTAGE    = 'threshold_percentage';
    const LABEL_MERCHANT                = 'merchant_id';
    const LABEL_TXN_ID                  = 'txn_id';

    //Metric names
    const BALANCE_NEGATIVE                = 'balance_is_negative';
    const BALANCE_NEGATIVE_THRESHOLD      = 'balance_negative_crossed_threshold';
    const BALANCE_ALLOWED_NEGATIVE        = 'balance_allowed_negative';

    public function getBalanceNegativeDimensions(string $merchantId, string $balanceType, int $balanceAmount, string $txnType) : array
    {
        $dimensions = [
            self::LABEL_MERCHANT            => $merchantId,
            self::LABEL_BALANCE_TYPE        => $balanceType,
            self::LABEL_BALANCE_AMOUNT      => $balanceAmount,
            self::LABEL_TRANSACTION_TYPE    => $txnType,
        ];

        return $dimensions;
    }

    public function getBalanceNegativeThresholdBreachedDimensions(string $merchantId,
                                                                  int $balance,
                                                                  int $threshold,
                                                                  string $txnType) : array
    {
        $dimensions = [
            self::LABEL_MERCHANT                => $merchantId,
            self::LABEL_BALANCE_AMOUNT          => $balance,
            self::LABEL_THRESHOLD_PERCENTAGE    => $threshold,
            self::LABEL_TRANSACTION_TYPE        => $txnType,
        ];

        return $dimensions;
    }

    public function getBalanceAllowedNegativeDimensions(string $merchantId, string $balanceType, int $balanceAmount, string $txnType, string $txnId) : array
    {
        return [
            self::LABEL_MERCHANT            => $merchantId,
            self::LABEL_BALANCE_TYPE        => $balanceType,
            self::LABEL_BALANCE_AMOUNT      => $balanceAmount,
            self::LABEL_TRANSACTION_TYPE    => $txnType,
            self::LABEL_TXN_ID              => $txnId,
        ];
    }
}
