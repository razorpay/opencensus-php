<?php

namespace RZP\Reconciliator\ReconSummary;

class RefundReconStatusSummary extends DailyReconStatusSummary
{
    public function getReconStatusSummary(int $from, int $to): array
    {
        $formattedSummary = [];

        $refundSummary = $this->repo
                              ->transaction
                              ->fetchRefundReconStatusSummary(
                                  $from,
                                  $to,
                                  Constants::GATEWAYS);

        foreach ($refundSummary as $entry)
        {
            Helpers::addExtraColumns($entry);

            $formattedSummary[$entry['date']][] =  $entry;
        }

        return $formattedSummary;
    }

    public function getUnreconciledDataFile(int $from, int $to): array
    {
        $formattedPayments = [];

        $payments = $this->repo
                         ->transaction
                         ->fetchUnreconciledEntitiesBetweenDates(
                            $from,
                            $to,
                            Constants::GATEWAYS,
                            Constants::LIMIT,
                            Constants::PAYMENT_PARAMS,
                            Constants::REFUND_PARAMS
                        );

        foreach ($payments as $entry)
        {
            $date = Helpers::getFormattedDate($entry['created_at']);

            Helpers::formatSheetColumns($entry);

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