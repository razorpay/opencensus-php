<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Order;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers\RecurringCharge as Helper;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class RecurringCharge extends Base
{
    const RESPONSE_PAYMENT_ID = 'razorpay_payment_id';

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->paymentProcessor = new PaymentProcessor($this->merchant);

        $this->orderCore = new Order\Core;
    }

    protected function processEntry(array & $entry)
    {
        $order = $this->createOrder($entry);

        $response = $this->processPayment($entry, $order);

        $entry[Header::STATUS] = Status::SUCCESS;

        $entry[Header::RECURRING_CHARGE_PAYMENT_ID] = $response[self::RESPONSE_PAYMENT_ID];
    }

    protected function createOrder(array & $entry)
    {
        $orderCreateRequest = Helper::getOrderInput($entry);

        $order = $this->orderCore->create($orderCreateRequest, $this->merchant);

        $entry[Header::RECURRING_CHARGE_ORDER_ID] = $order->getPublicId();

        return $order;
    }

    protected function processPayment(array $entry, Order\Entity $order)
    {
        $recurringPaymentRequest = Helper::getPaymentInput($entry, $order);

        return $this->paymentProcessor->process($recurringPaymentRequest);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
