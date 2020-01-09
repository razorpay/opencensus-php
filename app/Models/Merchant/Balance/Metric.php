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
    const LABEL_MERCHANT                 = 'merchant';

    //Metric names
    const BALANCE_NEGATIVE                = 'balance_is_negative';
    const BALANCE_NEGATIVE_THRESHOLD     = 'balance_negative_crossed_threshold';

    public function getBalanceNegativeDimensions(Merchant\Entity $merchant, Entity $balance, string $txnType) : array
    {
        $dimensions = [
            self::LABEL_MERCHANT                => $merchant,
            self::LABEL_BALANCE_TYPE        => $balance->getType(),
            self::LABEL_BALANCE_AMOUNT      => $balance->getBalance(),
            self::LABEL_TRANSACTION_TYPE    => $txnType,
        ];

        return $dimensions;
    }

    public function getBalanceNegativeThresholdBreachedDimensions(Merchant\Entity $merchant,
                                                                  int $balance,
                                                                  int $threshold,
                                                                  string $txnType) : array
    {
        $dimensions = [
            self::LABEL_MERCHANT                => $merchant,
            self::LABEL_BALANCE_AMOUNT          => $balance,
            self::LABEL_THRESHOLD_PERCENTAGE    => $threshold,
            self::LABEL_TRANSACTION_TYPE        => $txnType,
        ];

        return $dimensions;
    }
}
