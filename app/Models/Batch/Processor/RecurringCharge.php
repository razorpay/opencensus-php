<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Order;
use RZP\Models\Customer;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers\RecurringCharge as Helper;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class RecurringCharge extends Base
{
    const RESPONSE_PAYMENT_ID = 'razorpay_payment_id';

    protected $paymentProcessor;

    protected $orderCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->paymentProcessor = new PaymentProcessor($this->merchant);

        $this->orderCore = new Order\Core;
    }

    protected function processEntry(array & $entry)
    {
        $order = $this->createOrder($entry);

        $this->processPayment($entry, $order);

        $entry[Header::STATUS] = Status::SUCCESS;

        $this->paymentProcessor->flushPaymentObjects();
    }

    protected function createOrder(array & $entry): Order\Entity
    {
        $orderCreateRequest = Helper::getOrderInput($entry);

        $order = $this->orderCore->create($orderCreateRequest, $this->merchant);

        $entry[Header::RECURRING_CHARGE_ORDER_ID] = $order->getPublicId();

        return $order;
    }

    protected function getCustomer(array $entry): Customer\Entity
    {
        $customerId = $entry[Header::RECURRING_CHARGE_CUSTOMER_ID];

        return $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
    }

    protected function processPayment(array & $entry, Order\Entity $order)
    {
        $customer = $this->getCustomer($entry);

        $recurringPaymentRequest = Helper::getPaymentInput($entry, $order, $customer);

        $this->paymentProcessor->process($recurringPaymentRequest);

        $payment = $this->paymentProcessor->getPayment();

        $payment->batch()->associate($this->batch);

        $this->repo->saveOrFail($payment);

        $entry[Header::RECURRING_CHARGE_PAYMENT_ID] = $payment->getPublicId();
    }

    protected function postProcessEntries(array & $entries)
    {
        parent::postProcessEntries($entries);

        $processedAmount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Header::STATUS] === Status::SUCCESS)
            {
                $processedAmount += $entry[Header::RECURRING_CHARGE_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
