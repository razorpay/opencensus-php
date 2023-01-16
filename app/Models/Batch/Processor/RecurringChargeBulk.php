<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Order;
use RZP\Models\Settings;
use RZP\Models\Customer;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Constants;
use RZP\Models\Batch\Helpers\RecurringCharge as Helper;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class RecurringChargeBulk extends Base
{
    const RESPONSE_PAYMENT_ID = 'razorpay_payment_id';

    const AMOUNT_AS_RUPEE_CONFIG = 'amount_as_rupee';


    protected $paymentProcessor;

    protected $orderCore;

    protected $conversionMap = [
        Header::RECURRING_CHARGE_CURRENCY => Constants::TO_UPPER_CASE,
    ];

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->paymentProcessor = new PaymentProcessor($this->merchant);

        $this->orderCore = new Order\Core;
    }

    protected function processEntry(array & $entry)
    {
        try{
            $this->trimEntry($entry);

            $this->processConvertCase($entry, $this->conversionMap);

            $this->paymentProcessor->flushPaymentObjects();

            $this->processCurrencyAndAmount($entry);

            $order = $this->createOrder($entry);

            $this->processPayment($entry, $order);

            $entry[Header::STATUS] = Status::SUCCESS;
        }
        finally
        {
            $this->processCurrencyAndRevertAmountIfNecessary($entry);
        }
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
        if (empty($entry[Header::RECURRING_CHARGE_CUSTOMER_ID]) === true)
        {
            $tokenId = $entry[Header::RECURRING_CHARGE_TOKEN];

            $token = $this->repo->token->findByPublicIdAndMerchant($tokenId, $this->merchant);

            $customerId = $token->getCustomerId();

            return $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);
        }

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

    protected function processCurrencyAndAmount(array & $entry)
    {
        $config = Settings\Accessor::for($this->merchant, Settings\Module::BATCH)
            ->get(Type::RECURRING_CHARGE);

        if (empty($config))
        {
            return;
        }

        if ((isset($config[self::AMOUNT_AS_RUPEE_CONFIG]) === true) and
            ($config[self::AMOUNT_AS_RUPEE_CONFIG] === '1'))
        {
            $amount = $entry[Header::RECURRING_CHARGE_AMOUNT];

            $amount = (int) $amount;

            $amount = $amount * 100;

            $entry[Header::RECURRING_CHARGE_AMOUNT] = $amount;
        }
    }

    public function processCurrencyAndRevertAmountIfNecessary(array & $entry)
    {
        $config = Settings\Accessor::for($this->merchant, Settings\Module::BATCH)
            ->get(Type::RECURRING_CHARGE);

        if (empty($config))
        {
            return;
        }

        if ((isset($config[self::AMOUNT_AS_RUPEE_CONFIG]) === true) and
            ($config[self::AMOUNT_AS_RUPEE_CONFIG] === '1'))
        {
            $amount = $entry[Header::RECURRING_CHARGE_AMOUNT];

            $amount = (int) $amount;

            $amount = $amount / 100;

            $entry[Header::RECURRING_CHARGE_AMOUNT] = $amount;
        }
    }

    public function addSettingsIfRequired(& $input)
    {
        $batchType = $this->batch->getType();

        $batchSetting = Settings\Accessor::for($this->merchant, Settings\Module::BATCH)
            ->get($batchType);

        if (empty($batchSetting))
        {
            return;
        }

        if ((isset($batchSetting[self::AMOUNT_AS_RUPEE_CONFIG]) === true) and
            ($batchSetting[self::AMOUNT_AS_RUPEE_CONFIG] === '1')) {

            $config = [];

            if (isset($input["config"]) === true) {
                $config = $input["config"];
            }

            $config[self::AMOUNT_AS_RUPEE_CONFIG] = true;

            $input["config"] = $config;
        }
    }
}
