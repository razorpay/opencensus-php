<?php

namespace RZP\Reconciliator\ReconciliationSummaryMail;

class RefundReconStatusSummary extends DailyReconStatusSummary
{
    public function getReconStatusSummary()
    {
        $formattedSummary = [];

        $refundSummary = $this->repo->transaction->fetchRefundReconStatusSummary($this->from, $this->to, self::GATEWAYS);

        foreach ($refundSummary as $entry)
        {
            $this->addExtraColumns($entry);

            $formattedSummary[$entry['date']][] =  $entry;
        }

        return $formattedSummary;
    }
}