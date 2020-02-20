<?php

namespace RZP\Reconciliator\HDFC\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = 'rec_fmt';

    const UNKNOWN_COLUMN_ENTITY_TYPES = ['CDP', 'CBR', 'AMC', 'MCC', 'GFC'];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        $entityType = null;

        //
        // Identifies if row type is payment or refund.
        //
        $reconType = null;

        if (isset($row[self::COLUMN_ENTITY_TYPE]) === true)
        {
            $entityType = $row[self::COLUMN_ENTITY_TYPE];

            $entityType = trim($entityType);
        }

        if (blank($entityType) === true)
        {
            $reconType = self::NA;
        }
        else if ($entityType === 'CVD')
        {
            $reconType = BaseReconciliate::REFUND;
        }
        else if ($entityType === 'BAT')
        {
            $reconType = BaseReconciliate::PAYMENT;
        }
        else if (in_array($entityType, self::UNKNOWN_COLUMN_ENTITY_TYPES))
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => 'UNKNOWN_HDFC_ENTITY_TYPE',
                    'message'       => 'This payment has to be authorized and reconciled manually.',
                    'row_details'   => $row,
                    'gateway'       => $this->gateway
                ]);

            $reconType = self::NA;
        }

        return $reconType;
    }

    /**
     * Many of the HDFC MIS files, have been found to contain
     * additional UPI transactions, recently, making the batches partially
     * processed, even after processing all the entries in it.
     *
     * @param $row
     * @return bool
     */
    protected function skipRestOfFile($row)
    {
        return ((isset($row[PaymentReconciliate::COLUMN_MERCHANT_CODE]) === true)  and
            ($row[PaymentReconciliate::COLUMN_MERCHANT_CODE] === 'UPI TRANSACTIONS'));
    }
}
