<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Payment;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Terminal;
use RZP\Models\Order;

class TerminalSelector
{
    protected $terminalSelector;

    protected $repo;

    protected $mode;

    public function __construct($repo, $mode)
    {
        $this->repo = $repo;

        $this->mode = $mode;
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

        $pastPaymentIds = array();

        $orderId = $payment->getApiOrderId();

        //TODO: should we do a join on these queries instead of multiple queries

        if ($orderId != null)
        {
            $orderId = Order\Entity::getIdPrefix() . $orderId;

            $fetchParams = array(Payment\Entity::ORDER_ID => $orderId);

            $pastPayments = $this->repo->payment->fetch($fetchParams, $payment->merchant->getId());

            foreach($pastPayments as $p)
            {
                $pastPaymentIds[] = $p->getId();
            }
        }
        elseif (isset($metadata[AnalyticsEntity::CHECKOUT_ID]) === true)
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

            $checkouts = $this->repo->payment_analytics->getRecentMerchantPaymentsForCheckoutId($checkoutId);

            foreach($checkouts as $c)
            {
                $pastPaymentIds[] = $c->getPaymentId();
            }
        }
        $usedTerminals = array();

        if (count($pastPaymentIds) > 0)
        {
            $terminalAnalytics = $this->repo->terminal_analytics->fetchTerminalAnalyticsForPaymentIds($pastPaymentIds);

            foreach($terminalAnalytics as $tAnalytics)
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
    public function getTerminalsForPayment($payment)
    {
        $terminalsToExclude = $this->getTerminalsToExclude($payment);

        $this->terminalSelector = new Terminal\Selector($payment, $this->mode);

        // TODO: add trace here to say that we are excluding these terminals
        $opts = array('exclude' => $terminalsToExclude);

        $this->terminalsSelected = $this->terminalSelector->selectTerminals($opts);

        return $this->terminalsSelected;
    }
}