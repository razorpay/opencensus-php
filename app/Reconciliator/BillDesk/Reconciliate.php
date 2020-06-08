<?php

namespace RZP\Reconciliator\BillDesk;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Batch\Processor\Reconciliation;
use RZP\Reconciliator\BillDesk\SubReconciliator\RefundReconciliate;
use RZP\Reconciliator\BillDesk\SubReconciliator\PaymentReconciliate;

class Reconciliate extends Base\Reconciliate
{
    const SUCCESS = 'success';

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::SUCCESS) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else if (strpos($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }
        else
        {
            return null;
        }

        return $typeName;
    }

    /**
     * Some gateways send files which should not be used as part of the
     * reconciliation process. This decides whether a given file should
     * be part of the reconciliation or not.
     *
     * @param array $fileDetails
     * @param array $inputDetails
     * @return bool Whether the given file is present in the gateway's
     *              exclude list or not.
     */
    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        $fileName = strtolower($fileDetails['file_name']);

        if ((strpos($fileName, self::SUCCESS) === false) and
            (strpos($fileName, self::REFUND) === false))
        {
            return true;
        }

        return false;
    }

    /**
     * For refund MIS file, we send payment_id and gateway refund id
     * to scrooge api, which returns refund id in the response.
     * We set the RZP refund ID in ref_3 column (which is currently
     * unused and contains NA, always). ref_1, ref_2 contains paymentID.
     *
     * @param array $fileContents
     * @param string $reconciliationType
     */
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        if ($reconciliationType === Base\Reconciliate::PAYMENT)
        {
            return;
        }

        $refundsArray = [];

        foreach ($fileContents as $index => &$row)
        {
            if ($index === Reconciliation::EXTRA_DETAILS)
            {
                continue;
            }

            $txnId = $row[RefundReconciliate::COLUMN_REFUND_ID];
            $refundsArray[$txnId] = $row[PaymentReconciliate::COLUMN_PAYMENT_ID];
        }

        $request = $this->buildRequestForScrooge($refundsArray);

        if (count($request) > 0)
        {
            $response = $this->getRefundIdFromScrooge($request, Gateway::BILLDESK);

            foreach ($fileContents as $index => &$row)
            {
                if ($index === Reconciliation::EXTRA_DETAILS)
                {
                    continue;
                }

                $txnId = $row[RefundReconciliate::COLUMN_REFUND_ID];

                if (empty($response[$txnId]) === false)
                {
                    $row[RefundReconciliate::COLUMN_RZP_REFUND_ID] = $response[$txnId]['refund_id'];
                }
            }
        }
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
