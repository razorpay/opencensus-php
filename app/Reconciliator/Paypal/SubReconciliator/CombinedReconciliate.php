<?php

namespace RZP\Reconciliator\Paypal\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\WalletPaypal\ReconFields;
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
        else if ($entityType === 'PAYMENT')
        {
            $reconType = BaseReconciliate::PAYMENT;
        }
        else
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => 'UNKNOWN_PAYPAL_TRANSACTION_TYPE',
                    'message'       => 'Transaction should either be payment or refund.',
                    'row_details'   => $row,
                    'gateway'       => $this->gateway
                ]);

            $reconType = self::NA;
        }

        return $reconType;
    }
}
