<?php

namespace Reconciliator\Foundation;


use Reconciliator\Orchestrator;
use Models\Payment;

class SubReconciliate
{
    


    /**
     * This is the start of the actual reconciliation.
     * Reconciliation is done for each row in the file content.
     * Validates payment status.
     * Records gateway fees.
     * Records gateway service tax.
     * Sets card type (debit/credit).
     * Records rrn.
     *
     * @param array $fileContents
     */
    public function startReconciliation($fileContents)
    {
        //$extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            // Every gateway has its own headers which have different meanings.
            // Hence, this is present in the gateway sub reconciliate class.
            $rowDetails = $this->getRowDetailsStructured($row);

            if (empty($rowDetails) === true)
            {
                continue;
            }

            try
            {
                // Validates that the payment status is not failed.
                $this->validatePaymentStatus();

                // Stores the gateway fees
                $this->recordGatewayFees();

                // Stores the gateway service tax
                $this->recordGatewayServiceTax();

                $this->setCardTypeIfAbsent($rowDetails);

                $this->recordRrn();
            }
            catch (\Exception $ex)
            {
                // Raise a critical alert for not being able to perform one of the reconciliation actions.
                continue;
            }
        }
    }
}