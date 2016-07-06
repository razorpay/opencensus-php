<?php

namespace Reconciliator\HDFC;

use Reconciliator\Base;
use RZP\Trace\TraceCode;
use Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate  extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = 'rec_fmt';

    protected function getReconciliationTypeForRow($row)
    {
        $entityType = trim($row[self::COLUMN_ENTITY_TYPE]);

        if ($entityType === 'CVD')
        {
            return BaseReconciliate::REFUND;
        }
        else if ($entityType === 'BAT')
        {
            return BaseReconciliate::PAYMENT;
        }
        else if (($entityType === 'CDP') or ($entityType === 'CBR'))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_MISMATCH,
                    'message'       => 'This payment has to be authorized and reconciled manually.',
                    'row_details'   => $row,
                    'gateway'       => get_called_class()
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