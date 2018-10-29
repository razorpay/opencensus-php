<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant, Customer\Entity $customer): Entity
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_REGISTRATION_CREATE_REQUEST,
            [
                'customer_id'  => $customer->getPublicId(),
                'merchant_id'  => $merchant->getPublicId(),
                'input'        => $input,
            ]
        );

        $this->trace->info(TraceCode::SUBSCRIPTION_REGISTRATION_CREATE_REQUEST, $input);

        $subscriptionRegistration = (new Entity)->build($input);

        $subscriptionRegistration->merchant()->associate($merchant);

        $subscriptionRegistration->customer()->associate($customer);

        $this->repo->saveOrFail($subscriptionRegistration);

        return $subscriptionRegistration;
    }

    public function createAuthLink(
        array $input,
        Merchant\Entity $merchant,
        Batch\Entity $batch = null): Invoice\Entity
    {
        $invoice = $this->repo->transaction(
            function() use ($input, $merchant, $batch)
            {
                $customer = $this->createCustomer($input, $merchant);

                $subscriptionRegistration = $this->createSubscriptionRegistration($input, $merchant, $customer);

                $invoice = $this->createInvoice($input, $merchant, $subscriptionRegistration, $batch);

                return $invoice;
            });

        return $invoice;
    }

    public function createSubscriptionRegistration(array & $input, Merchant\Entity $merchant, Customer\Entity $customer)
    {
        $subrInput = array_pull($input, Constants\Entity::SUBSCRIPTION_REGISTRATION);

        $bankInput = [];

        $bankName = null;

        if (array_key_exists(Constants\Entity::BANK_ACCOUNT, $subrInput))
        {
            $bankInput = array_pull($subrInput, Constants\Entity::BANK_ACCOUNT);

            if (array_key_exists(BankAccount\Entity::BANK_NAME, $bankInput))
            {
                $bankName = array_pull($bankInput, BankAccount\Entity::BANK_NAME);
            }
        }

        $subscriptionRegistration = $this->create($subrInput, $merchant, $customer);

        if (empty($bankInput) === false)
        {
            $this->setDefaultValuesForBank($bankInput, $customer);

            $bankAccountCore = new BankAccount\Core();

            $bankAccount = $bankAccountCore->addOrUpdateBankAccountForCustomer($bankInput, $customer);

            $this->setBankAccountEntity($subscriptionRegistration, $bankAccount);

        }
        if (empty($bankName) === false)
        {
            $subscriptionRegistration->setBank($bankName);
        }

        return $subscriptionRegistration;
    }

    public function createCustomer(array & $input, Merchant\Entity $merchant): Customer\Entity
    {
        $details = array_pull($input, Constants\Entity::CUSTOMER);

        $customer = (new Customer\Core)->createLocalCustomer($details, $merchant, false);

        $input[Entity::CUSTOMER_ID] = $customer->getPublicId();

        return $customer;
    }

    public function createInvoice(
        array & $input,
        Merchant\Entity $merchant,
        Entity $subscriptionRegistration,
        Batch\Entity $batch = null): Invoice\Entity
    {
        $invoiceCore = new Invoice\Core();

        $invoice = $invoiceCore->create(
            $input,
            $merchant,
            null,
            $batch,
            $subscriptionRegistration);

        return $invoice;
    }

    public function chargeToken(string $id, array $input, Merchant\Entity $merchant)
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($id, $merchant);

        $customer = $token->customer;

        $orderCurrency = 'INR';

        if (isset($input[Order\Entity::CURRENCY]) === true)
        {
            $orderCurrency = $input[Order\Entity::CURRENCY];
        }

        $receipt = $input[Order\Entity::RECEIPT];

        $orderInput = [
            Order\Entity::AMOUNT          => $input[Order\Entity::AMOUNT],
            Order\Entity::CURRENCY        => $orderCurrency,
            Order\Entity::RECEIPT         => $receipt,
            Order\Entity::PAYMENT_CAPTURE => true,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_REGISTRATION_CREATE_ORDER_FOR_CHARGE,
            [
                'token_id'     => $id,
                'merchant_id'  => $merchant->getPublicId(),
                'orderInput'   => $orderInput,
            ]
        );

        $orderCore = new Order\Core();

        $order = $orderCore->create($orderInput, $this->merchant);

        $paymentInput = [
            Payment\Entity::TOKEN       => $token->getPublicId(),
            Payment\Entity::AMOUNT      => $input[Order\Entity::AMOUNT],
            Payment\Entity::CURRENCY    => $orderCurrency,
            Payment\Entity::DESCRIPTION => $input[Payment\Entity::DESCRIPTION],
            Payment\Entity::EMAIL       => $customer->getEmail(),
            Payment\Entity::CONTACT     => $customer->getContact(),
            Payment\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Payment\Entity::ORDER_ID    => $order->getPublicId(),
            Payment\Entity::RECURRING   => '1',
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN,
            [
                'token_id'     => $id,
                'merchant_id'  => $merchant->getPublicId(),
                'paymentInput' => $paymentInput,
            ]
        );

        $paymentProcessor = new Payment\Processor\Processor($this->merchant);

        return $paymentProcessor->process($paymentInput);
    }

    public function setBankAccountEntity(Entity $subscriptionRegistration, BankAccount\Entity $bankAccount)
    {
        $subscriptionRegistration->entity()->associate($bankAccount);

        $this->repo->saveOrFail($subscriptionRegistration);
    }

    public function deleteToken(string $id, Merchant\Entity $merchant): array
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_REGISTRATION_DELETE_TOKEN,
            [
                'token_id' => $id,
                'merchant_id'  => $merchant->getPublicId(),
            ]
        );

        $token = $this->repo->token->findByPublicIdAndMerchant($id, $merchant);

        $token = $this->repo->token->deleteOrFail($token);

        if ($token === null)
        {
            return ['deleted' => true];
        }

        return $token->toArrayPublic();
    }

    protected function setDefaultValuesForBank(array & $bankInput, Customer\Entity $customer)
    {
        if (array_key_exists(BankAccount\Entity::BENEFICIARY_EMAIL, $bankInput) == false)
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_EMAIL] = $customer->getEmail();
        }

        if (array_key_exists(BankAccount\Entity::BENEFICIARY_MOBILE, $bankInput) == false)
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_MOBILE] = $customer->getContact();
        }
    }
}
