<?php

namespace RZP\Reconciliator\Ebs\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const REFUND_TXN_CLM     = 'refunded';
    const CAPTURE_TXN_CLM    = 'captured';

    const TXN_TYPE_COLUMN    = 'particular';
    const TXN_REFUND         = 'refunded';              // refund
    const TXN_PAYMENT        = 'captured';              // payment

    const BLACKLISTED_COLUMNS = [];

    /**
     * We get two types of excels for EBS recon
     * Since we do not know what kind of file it would be
     * we will have to run checks for both kind of files
     *
     * @param $row array
     * @return string
     */
    protected function getReconciliationTypeForRow($row)
    {
        // This indicates the file is frome email
        if (isset($row[self::TXN_TYPE_COLUMN]) === true)
        {
            return $this->getTypeForRowEmail($row);
        }

        return $this->getTypeForRowDashboard($row);
    }

    /**
     * For the file generated from EBS website
     *
     * We need to check whether the refund column === 0.0
     * In case, refund column is not null, it is a refund recon
     * In all other cases, it is a payment recon
     *
     * In a refund-type txn, the capture_txn_clm is not null
     * Hence, we cannot have a check on 'capture_txn_clm'
     *
     * @param $row array
     * @return string
     */
    protected function getTypeForRowDashboard(array $row)
    {
        if ((array_key_exists(self::REFUND_TXN_CLM, $row) === false) and
            (array_key_exists(self::CAPTURE_TXN_CLM, $row) === false))
        {
            return null;
        }

        if ($row[self::REFUND_TXN_CLM] !== 0.0)
        {
            return self::NA;
        }

        if ($row[self::CAPTURE_TXN_CLM] !== 0.0)
        {
            return BaseReconciliate::PAYMENT;
        }

        return null;
    }

    /**
     * For the file generated from email.
     *
     * Here, we get a column 'particular',
     * whose value is 'captured' or 'refunded'
     *
     * Captured => Payment
     * Refunded => Refund
     *
     * @param $row array
     * @return string
     */
    protected function getTypeForRowEmail(array $row)
    {
        $txnType = strtolower($row[self::TXN_TYPE_COLUMN]);

        switch ($txnType)
        {
            case self::TXN_PAYMENT:

                return BaseReconciliate::PAYMENT;

            case self::TXN_REFUND:

                return BaseReconciliate::REFUND;

            default:

                return null;
        }
    }
}
