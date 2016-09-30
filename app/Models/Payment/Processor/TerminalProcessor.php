<?php

namespace RZP\Models\Payment\Processor;

use App;
use RZP\Models\Payment;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Terminal;
use RZP\Models\Order;
use RZP\Trace\TraceCode;

class TerminalProcessor
{
    protected $repo;

    protected $mode;

    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];
    }

    /**
     * Method to extract the terminals to exclude. We first get the past payments
     * for a given payment flow, and get the terminals that were used as part of the
     * payment flow. We will exclude all the terminals that were already tried before
     * to increase the payment efficacy
     * @param $payment
     * @return array
     */
    protected function getTerminalsToExclude($payment)
    {
        $metadata = $payment->getMetadata();

        $pastPaymentIds = [];

        $orderId = $payment->getApiOrderId();

        //TODO: should we do a join on these queries instead of multiple queries

        if ($orderId !== null)
        {
            $orderId = Order\Entity::getSignedId($orderId);

            $pastPayments = $this->repo->payment->getCreatedPaymentsForOrder($orderId);

            foreach($pastPayments as $pastPayment)
            {
                $pastPaymentIds[] = $pastPayment->getId();
            }
        }
        else if (isset($metadata[AnalyticsEntity::CHECKOUT_ID]) === true)
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

            $checkouts = $this->repo->payment_analytics->getRecentMerchantPaymentsForCheckoutId($checkoutId);

            foreach($checkouts as $checkout)
            {
                $pastPaymentIds[] = $checkout->getPaymentId();
            }
        }

        $usedTerminals = [];

        if (count($pastPaymentIds) > 0)
        {
            $terminalAnalytics = $this->repo->terminal_analytics->fetchTerminalAnalyticsForPaymentIds($pastPaymentIds);

            foreach ($terminalAnalytics as $tAnalytics)
            {
                $usedTerminals[] = $tAnalytics->getTerminalId();
            }
        }

        return $usedTerminals;
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
        $terminalsToExclude = $this->getTerminalsToExclude($payment);

        // add trace to tell that we are excluding terminals
        if (count($terminalsToExclude) > 0)
        {
            $traceData = array(
                'excluded_terminals'       => $terminalsToExclude,
                'payment_id'               => $payment->getId(),
            );

            $this->trace->info(TraceCode::TERMINAL_EXCLUDE, $traceData);
        }

        $terminalSelector = new Terminal\Selector($payment, $this->mode);

        $opts = ['exclude' => $terminalsToExclude];

        $terminalsSelected = $terminalSelector->selectTerminals($opts);

        return $terminalsSelected;
    }
}