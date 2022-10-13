<?php

namespace RZP\Reconciliator\FirstData;

use App;

use RZP\Trace\TraceCode;
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
     * @param string $reconciliationType
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        $capsPaymentIds = [];

        foreach ($fileContents as $row)
        {
            if ((empty($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID]) === false) and
              (Entity::verifyCapsId($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID], false) === true))
            {
                $capsPaymentIds[] = $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID];

            }

        }

        if (count($capsPaymentIds) > 0)
        {
            $this->trace->info(TraceCode::RECON_FIRST_DATA_CAPS_PID,
                [
                    'message'   => 'Possible caps pids case for firstdata',
                    'capsPaymentIds'  =>  $capsPaymentIds,
                ]);

            // Get distinct entities, there will be multiple entries in case of a payment and
            // refund row are encountered, since Firstdata sends caps pids for refunds as well.
            $capsPaymentIds = array_unique($capsPaymentIds);

            $responseFromCps = App::getFacadeRoot()['card.payments']->fetchPaymentIdFromCapsPIDs($capsPaymentIds);

            $this->trace->info(TraceCode::RECON_INFO_ALERT,
                [
                    'message' => 'Response received from cps for request to fetch pid from caps pid',
                    'response' => $responseFromCps,
                ]);

            foreach ($capsPaymentIds as $key => $value)
            {
                if (empty($responseFromCps[$value]) === false)
                {
                    unset($capsPaymentIds[$key]);
                }
            }

            $pIdsFromPaymentsRepoFd = [];
            $pIdsFromPaymentsRepoMpgs = [];

            if (count($capsPaymentIds) > 0)
            {
                $this->trace->info(TraceCode::RECON_INFO_ALERT,
                    [
                        'message'   => 'Successful response not received for some caps pids',
                        'capsPids'  =>  $capsPaymentIds,
                    ]);

                $pIdsFromPaymentsRepoFd = $this->repo->payment->fetchPaymentIdsbyCapsPaymentIds($capsPaymentIds, Gateway::FIRST_DATA);

                if (count($pIdsFromPaymentsRepoFd) !== count($capsPaymentIds))
                {
                    $capsPIdsFromPaymentsRepoFD = array_map('strtoupper', $pIdsFromPaymentsRepoFd);
                    $capsPIdsForMpgs = array_diff($capsPaymentIds, $capsPIdsFromPaymentsRepoFD);

                    $pIdsFromPaymentsRepoMpgs = $this->repo->payment->fetchPaymentIdsbyCapsPaymentIds($capsPIdsForMpgs, Gateway::MPGS);
                }
            }

            $pIdsFromPaymentsRepo = array_merge($pIdsFromPaymentsRepoFd, $pIdsFromPaymentsRepoMpgs);

            foreach ($pIdsFromPaymentsRepo as $paymentId)
            {
                $responseFromCps[strtoupper($paymentId)]['authorization']['payment_id'] = $paymentId;
            }

            foreach ($fileContents as &$row)
            {
                if (empty($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID]) === false)
                {
                    $capsPaymentId = $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID];
                    $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID] = $responseFromCps[$capsPaymentId]['authorization']['payment_id'] ?? $capsPaymentId;
                }
            }
        }

        $refundsArray = [];

        foreach ($fileContents as &$row)
        {
            if (($this->getReconTypeForRow($row) === Base\Reconciliate::REFUND) and
                (Entity::verifyUniqueId($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID], false) === true))
            {
                $txnId = $row[RefundReconciliate::GATEWAY_TRANSACTION_ID];
                $refundsArray[$txnId] = $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID];
            }
        }

        $requestForScrooge = $this->buildRequestForScrooge($refundsArray);

        if (count($requestForScrooge) > 0)
        {
            $responseFD = $this->getRefundIdFromScrooge($requestForScrooge, Gateway::FIRST_DATA);
            $responseMpgs = $this->getRefundIdFromScrooge($requestForScrooge, Gateway::MPGS);

            foreach ($fileContents as &$row)
            {
                if (($this->getReconTypeForRow($row) === Base\Reconciliate::REFUND) and
                    (Entity::verifyUniqueId($row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID], false) === true))
                {
                    $txnId = ltrim($row[RefundReconciliate::GATEWAY_TRANSACTION_ID], '0');

                    if (empty($responseFD[$txnId]) === false)
                    {
                        $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID] = $responseFD[$txnId]['refund_id'];
                    }
                    elseif (empty($responseMpgs[$txnId]) === false)
                    {
                        $row[PaymentReconciliate::COLUMN_RZP_ENTITY_ID] = $responseMpgs[$txnId]['refund_id'];
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
