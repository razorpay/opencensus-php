<?php

namespace RZP\Reconciliator\Getsimpl\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\GetSimpl\ReconFields;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        $entityType = null;

        //
        // Identifies if row type is payment or refund.
        //
        $reconType = null;

        if (isset($row[ReconFields::TYPE]) === true)
        {
            $entityType = $row[ReconFields::TYPE];

            $entityType = trim($entityType);
        }

        if (blank($entityType) === true)
        {
            $reconType = self::NA;
        }
        else if ($entityType === 'REFUND')
        {
            $reconType = BaseReconciliate::REFUND;
        }
        else if ($entityType === 'CLAIMED' or $entityType === 'CHARGED')
        {
            $reconType = BaseReconciliate::PAYMENT;
        }
        else
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => 'UNKNOWN_GETSIMPL_TRANSACTION_TYPE',
                    'message'       => 'Transaction should either be claimed, charged or refund.',
                    'row_details'   => $row,
                    'gateway'       => $this->gateway
                ]);

            $reconType = self::NA;
        }

        return $reconType;
    }
}
