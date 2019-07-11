<?php

namespace RZP\Reconciliator\ReconSummary;

use RZP\Constants\Entity as ConstantEntity;

class PaymentReconStatusSummary extends DailyReconStatusSummary
{
    public function getReconStatusSummary(int $from, int $to): array
    {
        $paymentSummary = $this->repo
                               ->transaction
                               ->fetchPaymentReconStatusSummary(
                                   $from,
                                   $to);

        $formattedSummary = Helpers::getFormattedSummary($paymentSummary);

        return $formattedSummary;
    }
    public function getUnreconStatusSummaryByGateway(array $gatewayWithDates): array
    {
        return $this->repo
                    ->transaction
                    ->fetchPaymentUnreconStatusSummary($gatewayWithDates);
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
                             Constants::PAYMENT_PARAMS
                         );

        foreach ($payments as $entry)
        {
            $date = Helpers::getFormattedDate($entry['created_at']);

            Helpers::formatSheetColumns($entry, ConstantEntity::PAYMENT);

            $formattedPayments[$date][$entry['gateway']][] = $entry;
        }

        $file = [];

        foreach ($formattedPayments as $date => $payment)
        {
            $file[]  =  [
                'url'  => $this->createExcelFile($payment, $date.' - Unreconciled Payments','files/settlement', array_keys($payment)),
                'name' => $date.' - Unreconciled Payments.xlsx'
            ];
        }

        return $file;
    }
}
