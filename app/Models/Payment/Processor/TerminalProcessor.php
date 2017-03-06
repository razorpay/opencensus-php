<?php

namespace RZP\Models\Payment\Processor;

use App;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Terminal;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Analytics;

class TerminalProcessor extends Base\Core
{
    /**
     * Method to extract the terminals that failed. We first get the past payments
     * for a given payment flow, and get the terminals that were used as part of the
     * payment flow. We will sort the failed terminals as the last in the sorted terminals
     * to increase the payment efficacy
     * @param $payment
     * @return array
     */
    protected function getFailedTerminalIds($payment)
    {
        $metadata = $payment->getMetadata();

        $orderId = $payment->getApiOrderId();

        $pastPayments = [];

        if ($orderId !== null)
        {
            $pastPayments = $this->repo->payment->getCreatedAndFailedPaymentsForOrder($orderId);
        }
        else if (isset($metadata[AnalyticsEntity::CHECKOUT_ID]) === true)
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

            $pastPayments = $this->repo->payment->getRecentMerchantPaymentsForCheckoutId($checkoutId);
        }

        $failedTerminalIds = [];

        foreach ($pastPayments as $pastPayment)
        {
            if (($pastPayment->hasNotBeenAuthorized()) and
                ($pastPayment->getTerminalId() !== null))
            {
                $failedTerminalIds[] = $pastPayment->getTerminalId();
            }
        }

        // $failedTerminals = $this->repo->terminal->findMany($failedTerminals);

        return array_unique($failedTerminalIds);
    }

    /**
     * Get list of terminals for the payment from the Terminal Selection.
     * Note: In case of a reattempt, remove the terminals that were used, since
     * these can be treated as failures from the list of terminals
     * to be selected
     * @param $payment
     * @return Terminal\Entity
     */
    public function getTerminalsForPayment(Payment\Entity $payment)
    {
        $failedTerminalIds = $this->getFailedTerminalIds($payment);

        // add trace to tell that we are excluding terminals
        if (count($failedTerminalIds) > 0)
        {
            $this->trace->info(
                TraceCode::TERMINAL_USED_BEFORE,
                [
                    'failed_terminals'      => $failedTerminalIds,
                    'payment_id'            => $payment->getId(),
                ]);
        }

        $terminalSelector = new Terminal\Selector($payment, $this->mode);

        $opts = [Terminal\Options::FAILED => $failedTerminalIds];

        $terminalsSelected = $terminalSelector->selectTerminals($opts);

        return $terminalsSelected;
    }
}
