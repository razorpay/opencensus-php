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
    protected $subscriptionRegistration;

    protected $customer;

    protected $invoice;

    protected $batch;

    protected $bankAccount;

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

    public function createAuthLink(array $input, Merchant\Entity $merchant, Batch\Entity $batch = null): Invoice\Entity
    {
        $this->merchant = $merchant;

        $this->repo->transaction(
            function() use ($input, $batch)
            {
                $this->batch = $batch;

                $this->createCustomer($input);

                $this->createSubscriptionRegistration($input);

                $this->createInvoice($input);
            });

        return $this->invoice;
    }

    public function createSubscriptionRegistration(array & $input)
    {
        if (isset($input[Constants\Entity::SUBSCRIPTION_REGISTRATION]) === true)
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

            $this->subscriptionRegistration = $this->create($subrInput, $this->merchant, $this->customer);

            if (empty($bankInput) === false)
            {
                $this->setDefaultValuesForBank($bankInput);

                $bankAccountCore = new BankAccount\Core();

                $bankAccount = $bankAccountCore->addOrUpdateBankAccountForCustomer($bankInput, $this->customer);

                $this->bankAccount = $bankAccount;

                $this->setBankAccountEntity($this->subscriptionRegistration, $bankAccount);

            }
            if (empty($bankName) === false)
            {
                $this->subscriptionRegistration->setBank($bankName);
            }
        }
    }

    public function createCustomer(array & $input)
    {
        $details = array_pull($input, Constants\Entity::CUSTOMER);

        $this->customer = (new Customer\Core)->createLocalCustomer($details, $this->merchant, false);

        $input[Entity::CUSTOMER_ID] = $this->customer->getPublicId();
    }

    public function createInvoice(array & $input)
    {
        $invoiceCore = new Invoice\Core();

        $this->invoice = $invoiceCore->create(
            $input,
            $this->merchant,
            null,
            $this->batch,
            $this->subscriptionRegistration);
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

        $orderInput = [
            Order\Entity::AMOUNT          => $input[Order\Entity::AMOUNT],
            Order\Entity::CURRENCY        => $orderCurrency,
            Order\Entity::RECEIPT         => $input[Order\Entity::RECEIPT],
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

    protected function setDefaultValuesForBank(array & $bankInput)
    {
        if (array_key_exists(BankAccount\Entity::BENEFICIARY_EMAIL, $bankInput) == false)
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_EMAIL] = $this->customer->getEmail();
        }

        if (array_key_exists(BankAccount\Entity::BENEFICIARY_MOBILE, $bankInput) == false)
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_MOBILE] = $this->customer->getContact();
        }
    }
}
