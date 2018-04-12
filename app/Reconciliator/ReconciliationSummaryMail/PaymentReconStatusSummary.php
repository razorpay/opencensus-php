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
            $this->addExtraColumns($entry);

            $formattedSummary[$entry['date']][] =  $entry;
        }

        return $formattedSummary;
    }
}