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
    protected $payment;

    /**
     * Get list of terminals for the payment from the Terminal Selection.
     * Note: In case of a reattempt, remove the terminals that were used, since
     * these can be treated as failures from the list of terminals
     * to be selected
     * @return Terminal\Entity
     */
    public function getTerminalsForPayment(Payment\Entity $payment)
    {
        $this->payment = $payment;

        // Bank transfers have no terminal
        if ($this->payment->isBankTransfer() === true)
        {
            return [];
        }

        $options = $this->getTerminalSelectionOptions();

        $input = [
            'payment' => $this->payment,
            'merchant' => $this->payment->merchant,
        ];

        $terminalSelector = new Terminal\Selector($input, $options);

        $terminalsSelected = $terminalSelector->select();

        if ($options->getMultiple() === false)
        {
            return [head($terminalsSelected)];
        }

        return $terminalsSelected;
    }

    protected function getTerminalSelectionOptions()
    {
        $options = new Terminal\Options;

        if ($this->payment->isMethodCardOrEmi() === true)
        {
            $failedTerminalIds = $this->getFailedTerminalIds();

            if (empty($failedTerminalIds) === false)
            {
                $this->trace->info(
                    TraceCode::TERMINAL_USED_BEFORE,
                    [
                        'failed_terminals'      => $failedTerminalIds,
                        'payment_id'            => $this->payment->getId(),
                    ]);

                $options->setFailedTerminals($failedTerminalIds);
            }
        }
        else
        {
            $options->setMultiple(false);
        }

        return $options;
    }

    /**
     * Method to extract the terminals that failed. We first get the past payments
     * for a given payment flow, and get the terminals that were used as part of the
     * payment flow. We will sort the failed terminals as the last in the sorted terminals
     * to increase the payment efficacy
     * @return array
     */
    protected function getFailedTerminalIds()
    {
        $metadata = $this->payment->getMetadata();

        $orderId = $this->payment->getApiOrderId();

        $pastPayments = [];

        $failedTerminalIds = [];

        if ($orderId !== null)
        {
            $pastPayments = $this->repo->payment->getCreatedAndFailedPaymentsForOrder($orderId);
        }
        else if (isset($metadata[AnalyticsEntity::CHECKOUT_ID]) === true)
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

            $pastPayments = $this->repo->payment->getRecentMerchantPaymentsForCheckoutId($checkoutId);
        }

        foreach ($pastPayments as $pastPayment)
        {
            if (($pastPayment->hasNotBeenAuthorized()) and
                ($pastPayment->getTerminalId() !== null))
            {
                $failedTerminalIds[] = $pastPayment->getTerminalId();
            }
        }

        return array_values(array_unique($failedTerminalIds));
    }
}
