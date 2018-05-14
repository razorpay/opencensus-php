<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate  extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = ['rec_fmt', 'REC FMT'];

    const UNKNOWN_COLUMN_ENTITY_TYPES = ['CDP', 'CBR', 'AMC', 'MCC'];

    protected function getReconciliationTypeForRow($row)
    {
        $entityType = null;

        foreach (self::COLUMN_ENTITY_TYPE as $cet)
        {
            if (isset($row[$cet]) === true)
            {
                $entityType = $row[$cet];

                $entityType = trim($entityType);

                break;
            }
        }

        if ($entityType === 'CVD')
        {
            return BaseReconciliate::REFUND;
        }
        else if ($entityType === 'BAT')
        {
            return BaseReconciliate::PAYMENT;
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

            return self::NA;
        }
        else if (empty($entityType) === true)
        {
            return self::NA;
        }
        else
        {
            return null;
        }
    }
}
