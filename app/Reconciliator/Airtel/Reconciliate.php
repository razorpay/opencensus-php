<?php

namespace RZP\Reconciliator\Airtel;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Airtel\SubReconciliator\RefundReconciliate;
use RZP\Reconciliator\Airtel\SubReconciliator\CombinedReconciliate;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /**
     * refund MIS file does not contain RZP refund ID, we get gateway refund id
     * we make request to scrooge api with gateway refund id, which returns RZP refund ID in the response.
     * We set the RZP refund ID in refund_id column.
     *
     * @param array $fileContents
     * @param string $reconciliationType
     */
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        $response = [];

        list($netbankingRequest, $walletRequest) = $this->fetchPaymentsAndGroupOnMethod($fileContents);

        if (count($netbankingRequest) > 0)
        {
            $response += $this->getRefundIdFromScrooge($netbankingRequest, Gateway::NETBANKING_AIRTEL);
        }

        if (count($walletRequest) > 0)
        {
            $response += $this->getRefundIdFromScrooge($walletRequest, Gateway::WALLET_AIRTELMONEY);
        }

        foreach ($fileContents as &$row)
        {
            if ((isset($row[CombinedReconciliate::COLUMN_ENTITY_TYPE]) === true) and
                ($row[CombinedReconciliate::COLUMN_ENTITY_TYPE] === CombinedReconciliate::COLUMN_REFUND))
            {
                $txnId = $row[RefundReconciliate::COLUMN_GATEWAY_PAYMENT_ID];

                $row[RefundReconciliate::COLUMN_RZP_REFUND_ID] = $response[$txnId]['refund_id'];
            }
        }
    }

    private function fetchPaymentsAndGroupOnMethod(array $rows): array
    {
        $netbanking = [];
        $wallet     = [];

        foreach ($rows as $row)
        {
            if ((isset($row[CombinedReconciliate::COLUMN_ENTITY_TYPE]) === true) and
                ($row[CombinedReconciliate::COLUMN_ENTITY_TYPE] === CombinedReconciliate::COLUMN_REFUND))
            {
                $payment = null;

                try
                {
                    $payment = $this->repo->payment->findOrFail($row[RefundReconciliate::COLUMN_PAYMENT_ID]);
                }
                catch (\Throwable $exception){}

                if ($payment->isNetbanking() === true)
                {
                    $netbanking[] = [
                        'payment_id'      => $row[RefundReconciliate::COLUMN_PAYMENT_ID],
                        'reference_value' => $row[RefundReconciliate::COLUMN_GATEWAY_PAYMENT_ID],
                    ];
                }
                else if ($payment->isWallet() === true)
                {
                    $wallet[] = [
                        'payment_id'      => $row[RefundReconciliate::COLUMN_PAYMENT_ID],
                        'reference_value' => $row[RefundReconciliate::COLUMN_GATEWAY_PAYMENT_ID],
                    ];
                }
            }
        }

        return [$netbanking, $wallet];
    }
}
