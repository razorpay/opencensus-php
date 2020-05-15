<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\FirstData\SubReconciliator\RefundReconciliate;
use RZP\Reconciliator\FirstData\SubReconciliator\PaymentReconciliate;
use RZP\Reconciliator\FirstData\SubReconciliator\CombinedReconciliate;

class Reconciliate extends Base\Reconciliate
{
    //
    // FirstData is sending a summary file with name razorpay_templet26_21may180_summary.xls.
    //
    const SUMMARY = 'summary';
    // Const sent as a param to scrooge in preprocess
    // step, to fetch refund id from it's api.
    const RZP_REFERENCE_KEY = 'gateway_transaction_id';

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     *
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /**
     * Some gateways send files which should not be used as part of the
     * reconciliation process. This decides whether a given file should
     * be part of the reconciliation or not.
     *
     * Skipping the summary file as doesn't contain transactions for recon.
     *
     * @param array $fileDetails
     * @return bool Whether the given file is present in the gateway's
     *              exclude list or not.
     */
    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        $fileName = strtolower($fileDetails['file_name']);

        if (str_contains($fileName, self::SUMMARY) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * We want to replace CapsPaymentID by actual
     * payment_id for all rows (in bulk, for better
     * performance) before hand and then proceed to
     * reconcile row by row. For refund rows, we send
     * payment_id and gateway_transaction_id to a
     * scrooge api, which returns us refund ids, which
     * get populated in this column only.
     *
     * @param array $fileContents
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function preProcessFileContents(array &$fileContents)
    {
        $capsPaymentIds = [];

        foreach ($fileContents as $row)
        {
            if ((empty($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID]) === false) and
              (Entity::verifyUniqueId($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID], false) === true))
            {
                $capsPaymentIds[] = $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID];
            }
        }

        $paymentIds = $this->repo->payment->fetchPaymentIdsbyCapsPaymentIds($capsPaymentIds, Gateway::FIRST_DATA);

        $capsKeyPaymentIdValue = [];

        foreach ($paymentIds as $paymentId)
        {
            $capsKeyPaymentIdValue[strtoupper($paymentId)] = $paymentId;
        }

        foreach ($fileContents as &$row)
        {
            if (empty($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID]) === false)
            {
                $capsPaymentId = $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID];
                $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID] = $capsKeyPaymentIdValue[$capsPaymentId] ?? $capsPaymentId;
            }
        }

        $refundsArray = [];

        foreach ($fileContents as &$row)
        {
            if ($this->getReconTypeForRow($row) === Base\Reconciliate::REFUND)
            {
                $txnId = $row[RefundReconciliate::GATEWAY_TRANSACTION_ID];
                $refundsArray[$txnId] = $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID];
            }
        }

        $request = $this->buildRequestForScrooge($refundsArray);

        if (count($request) > 0)
        {
            $response = $this->getRefundIdFromScrooge($request, Gateway::FIRST_DATA, self::RZP_REFERENCE_KEY);

            foreach ($fileContents as &$row)
            {
                if ($this->getReconTypeForRow($row) === Base\Reconciliate::REFUND)
                {
                    $txnId = ltrim($row[RefundReconciliate::GATEWAY_TRANSACTION_ID], '0');

                    if (empty($response[$txnId]) === false)
                    {
                        $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID] = $response[$txnId]['refund_id'];
                    }
                }
            }
        }
    }

    private function getReconTypeForRow($row)
    {
        if (isset($row[CombinedReconciliate::COLUMN_TXN_TYPE]) === false)
        {
            return null;
        }

        $txnType = $row[CombinedReconciliate::COLUMN_TXN_TYPE];

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
