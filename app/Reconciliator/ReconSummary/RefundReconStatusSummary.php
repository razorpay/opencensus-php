<?php

namespace RZP\Reconciliator\ReconSummary;

use RZP\Constants\Entity as ConstantEntity;

class RefundReconStatusSummary extends DailyReconStatusSummary
{
    public function getReconStatusSummary(int $from, int $to): array
    {
        $refundSummary = $this->repo
                              ->transaction
                              ->fetchRefundReconStatusSummary(
                                  $from,
                                  $to);

        $formattedSummary = Helpers::getFormattedSummary($refundSummary);

        return $formattedSummary;
    }

    public function getUnreconStatusSummaryByGateway(array $gatewayWithDates): array
    {
        return $this->repo
                   ->transaction
                   ->fetchRefundUnreconStatusSummary($gatewayWithDates);
    }

    public function getUnreconciledDataFile(int $from, int $to): array
    {
        $formattedPayments = [];

        $payments = $this->repo
                         ->transaction
                         ->fetchUnreconciledEntitiesBetweenDates(
                            $from,
                            $to,
                            config('gateway.available'),
                            Constants::LIMIT,
                            Constants::PAYMENT_PARAMS,
                            Constants::REFUND_PARAMS
                        );

        foreach ($payments as $entry)
        {
            $date = Helpers::getFormattedDate($entry['processed_at']);

            Helpers::formatSheetColumns($entry, ConstantEntity::REFUND);

            $formattedPayments[$date][$entry['gateway']][] = $entry;
        }

        $file = [];

        foreach ($formattedPayments as $date => $payment)
        {
            $file[]  =  [
                            'url' => $this->createExcelFile($payment, $date.' - Unreconciled Refunds','files/settlement', array_keys($payment)),
                            'name' => $date.' - Unreconciled Refunds.xlsx'
                        ];
        }

        return $file;
    }
}
