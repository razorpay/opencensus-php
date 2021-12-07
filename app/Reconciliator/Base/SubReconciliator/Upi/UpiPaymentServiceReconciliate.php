<?php

namespace RZP\Reconciliator\Base\SubReconciliator\Upi;

use RZP\Reconciliator\Base\SubReconciliator;

class UpiPaymentServiceReconciliate extends SubReconciliator\PaymentReconciliate
{
    /**
     * Returns null if the payment is routed through UPS.
     * In case of UPS payments, the entity is updated through different
     * flow
     *
     * @return void
     */
    protected function updateAndFetchGatewayPayment()
    {
        if ($this->payment->isRoutedThroughUpiPaymentService() === true)
        {
            return null;
        }

        parent::updateAndFetchGatewayPayment();
    }

    /**
     * runs pre-recon checks and updates gateway entity on UPS
     *
     * @return void
     */
    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        parent::runPreReconciledAtCheckRecon($rowDetails);

        // TODO : update gateway entity on UPS.
    }
}
