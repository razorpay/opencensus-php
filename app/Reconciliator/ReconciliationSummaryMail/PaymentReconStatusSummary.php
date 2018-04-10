<?php

namespace RZP\Reconciliator\ReconciliationSummaryMail;

class PaymentReconStatusSummary extends DailyReconStatusSummary
{
    public function getReconStatusSummary()
    {
        $formattedSummary = [];

        $paymentSummary = $this->repo->transaction->fetchPaymentReconStatusSummary($this->from, $this->to, self::GATEWAYS);

        foreach ($paymentSummary as $entry)
        {
            $date = $this->getFormattedDate($entry['date']);

            $formattedSummary[$date][] =  $entry;
        }

        return $formattedSummary;
    }
}