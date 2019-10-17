<?php

namespace RZP\Reconciliator\Amazonpay\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_ENTITY_TYPE  = 'transactiontype';
    const COLUMN_PAYMENT      = 'Capture';
    const COLUMN_REFUND       = 'Refund';
    const COLUMN_RESERVE      = 'Reserve';
    const COLUMN_TRANSFER     = 'Transfer';
    const COLUMN_CLAIM        = 'A to Z Guarantee Claim';

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_ENTITY_TYPE]) === false)
        {
            return null;
        }

        switch ($row[self::COLUMN_ENTITY_TYPE])
        {
            case self::COLUMN_PAYMENT:
                return BaseReconciliate::PAYMENT;

            case self::COLUMN_REFUND:
                return BaseReconciliate::REFUND;

            case self::COLUMN_RESERVE:
            case self::COLUMN_TRANSFER:
            case self::COLUMN_CLAIM:
                return self::NA;

            default:
                $this->app['trace']->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'     => Base\InfoCode::UNKNOWN_RECON_TYPE,
                        'row_details'   => $row,
                        'gateway'       => $this->gateway
                    ]);

                return self::NA;
        }
    }
}
