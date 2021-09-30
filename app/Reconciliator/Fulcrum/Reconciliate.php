<?php

namespace RZP\Reconciliator\Fulcrum;

use App;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Fulcrum\SubReconciliator\PaymentReconciliate;
use RZP\Reconciliator\Fulcrum\SubReconciliator\CombinedReconciliate;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /**
     * As Invoice number column dont have actual payment id
     * We want to fill Invoice number column by actual
     * payment_id for all rows (in bulk, for better
     * performance) before hand and then proceed to
     * reconcile row by row. For refund rows, we send
     * payment_id and gateway_transaction_id to a
     * scrooge api, which returns us refund ids, which
     * get populated in this column only.
     *
     * @param array $fileContents
     * @param string $reconciliationType
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        $rrns = [];

        foreach ($fileContents as $row)
        {
            if ((isset($row[PaymentReconciliate::COLUMN_RRN]) === true) and
                (empty($row[PaymentReconciliate::COLUMN_RRN]) === false))
            {
                $rrns[] = $row[PaymentReconciliate::COLUMN_RRN];
            }
        }

        if (count($rrns) === 0)
        {
            return;
        }

        // Get distinct rrn, we use rrn to fetch payIds from cps service
        $rrns = array_unique($rrns);

        $responseFromCps = App::getFacadeRoot()['card.payments']->fetchPaymentIdFromCapsPIDs($rrns);

        $this->trace->info(TraceCode::RECON_INFO_ALERT,
            [
                'message'   => 'Response received from cps for request to fetch pid from rrns',
                'response'  =>  $responseFromCps,
            ]);

        foreach ($rrns as $key => $value)
        {
            if (empty($responseFromCps[$value]) === false)
            {
                unset($rrns[$key]);
            }
        }

        if (count($rrns) > 0)
        {
            $this->trace->info(TraceCode::RECON_INFO_ALERT,
                [
                    'message'   => 'Successful response not received for some rrns',
                    'rrns'      =>  $rrns,
                ]);
        }

        foreach ($fileContents as &$row)
        {
            if (($this->getReconTypeForRow($row) === Base\Reconciliate::PAYMENT) and
                (isset($row[PaymentReconciliate::COLUMN_RRN]) === true) and
                (empty($row[PaymentReconciliate::COLUMN_RRN]) === false))
            {
                $rrn = $row[PaymentReconciliate::COLUMN_RRN];

                $row[PaymentReconciliate::COLUMN_PAYMENT_ID] = $responseFromCps[$rrn]['authorization']['payment_id'];
            }
        }
    }

    private function getReconTypeForRow($row)
    {
        //
        // If the "transaction_type" column is not present
        // in the parsed row, not processing the row
        if (isset($row[CombinedReconciliate::COLUMN_TRANSACTION_TYPE]) === false)
        {
            return null;
        }

        $txnType = $row[CombinedReconciliate::COLUMN_TRANSACTION_TYPE];

        return CombinedReconciliate::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? CombinedReconciliate::NA;
    }

    private function buildRequestForScrooge(array $input)
    {
        $request = [];

        foreach ($input as $key => $value)
        {
            $request[] = [
                'payment_id'      => $value,
                'reference_value' => ltrim($key, '0'),
            ];
        }

        return $request;
    }
}
