<?php

namespace RZP\Models\SubscriptionRegistration;

use mysql_xdevapi\Exception;
use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;


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

        $validator = new Validator();

        $validator->validateMethodAndFirstPaymentAmount($input);

        $subscriptionRegistration = (new Entity)->build($input);

        $subscriptionRegistration->merchant()->associate($merchant);

        $subscriptionRegistration->customer()->associate($customer);

        $this->repo->saveOrFail($subscriptionRegistration);

        return $subscriptionRegistration;
    }

    public function createAuthLink(
        array $input,
        Merchant\Entity $merchant,
        Batch\Entity $batch = null,
        Order\Entity $order = null): Invoice\Entity
    {
        $invoice = $this->repo->transaction(
            function() use ($input, $merchant, $batch, $order)
            {
                $customer = $this->createCustomer($input, $merchant);

                $subscriptionRegistration = $this->createSubscriptionRegistration($input, $merchant, $customer);

                $invoice = $this->createInvoice($input, $merchant, $subscriptionRegistration, $batch, $order);

                return $invoice;
            }
        );

        $tokenRegistration = $invoice->entity;

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_CREATED,$tokenRegistration->getMetricDimensions());

        return $invoice;
    }

    public function createAuthLinkForOrder(array $tokenRegistrationInput, Order\Entity $order, Customer\Entity $customer)
    {
        $this->populateAuthLinkParamsFromOrder($tokenRegistrationInput, $order);
        $this->populateInvoiceParamsFromOrder($tokenRegistrationInput, $order);

        $invoice = $this->repo->transaction(
            function() use ($tokenRegistrationInput, $order, $customer)
            {
                $subscriptionRegistration = $this->createSubscriptionRegistration($tokenRegistrationInput, $this->merchant, $customer);

                $invoice = $this->createInvoice($tokenRegistrationInput, $this->merchant, $subscriptionRegistration, null, $order);

                return $invoice;
            }
        );

        $tokenRegistration = $invoice->entity;

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_CREATED,$tokenRegistration->getMetricDimensions());
        
        return $invoice;
    }

    private function populateAuthLinkParamsFromOrder(array & $input, Order\Entity $order)
    {
        (new Validator)->validateMethodWithOrder($input, $order);

        $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::METHOD] = $order->getMethod();
    }

    private function populateInvoiceParamsFromOrder(array & $input, Order\Entity $order)
    {
        $input[Invoice\Entity::TYPE] = Invoice\Type::LINK;

        $input[Invoice\Entity::DESCRIPTION] = "Created by order";

        $input[Invoice\Entity::CURRENCY] = $order->getCurrency();

        $input[Invoice\Entity::AMOUNT] = $order->getAmount();
    }

    public function createSubscriptionRegistration(array & $input, Merchant\Entity $merchant, Customer\Entity $customer)
    {
        $subrInput = array_pull($input, Constants\Entity::SUBSCRIPTION_REGISTRATION);

        if (isset($input[Entity::NOTES]) === true)
        {
            $subrInput[Entity::NOTES] =  $input[Entity::NOTES];
        }

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
        Batch\Entity $batch = null,
        Order\Entity $order = null): Invoice\Entity
    {
        $invoiceCore = new Invoice\Core();

        $invoice = $invoiceCore->create(
            $input,
            $merchant,
            null,
            $batch,
            $subscriptionRegistration,
            null,
            $order);

        return $invoice;
    }

    // Associate
    public function associateToken(Entity $subr,  Customer\Token\Entity $token)
    {
        $this->repo->reload($subr);

        $subr->token()->associate($token);

        $this->repo->saveOrFail($subr);

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_TOKEN_ASSOCIATED, $subr->getMetricDimensions());
    }

    public function authenticate(Entity $subr, Customer\Token\Entity $token)
    {
        $this->repo->reload($subr);

        $subr->setStatus(Status::AUTHENTICATED);

        if (($token->getRecurringStatus() === Customer\Token\RecurringStatus::REJECTED) or
            ($subr->getAmount() === 0))
        {
            $subr->setStatus(Status::COMPLETED);
        }

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTHENTICATED, $subr->getMetricDimensions());

        $this->repo->saveOrFail($subr);
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

        $receipt = isset($input[Order\Entity::RECEIPT]) ? $input[Order\Entity::RECEIPT] : "";

        $description = isset($input[Payment\Entity::DESCRIPTION]) ? $input[Payment\Entity::DESCRIPTION] : "";

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
            Payment\Entity::DESCRIPTION => $description,
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

    private function isValidForAutoCharge(Entity $tokenRegistration)
    {
        $firstChargeNeeded    = ($tokenRegistration->getAmount() > 0 ) === true;

        $authenticatedStatus  = ($tokenRegistration->getStatus() === Status::AUTHENTICATED);

        $noAttempts  = ($tokenRegistration->getAttempts() === 0 );

        return ($firstChargeNeeded and $authenticatedStatus and $noAttempts);

    }

    public function processAutoCharge(Entity $tokenRegistration)
    {
        if ($this->isValidForAutoCharge($tokenRegistration) === false)
        {
            $this->trace->info(TraceCode::TOKEN_REGISTRATION_NOT_VALID_FOR_AUTO_CHARGE,
                [
                    'token.registration_id' =>$tokenRegistration->getId(),
                    'amount'   => $tokenRegistration->getAmount(),
                    'status'   => $tokenRegistration->getStatus(),
                    'attempts' => $tokenRegistration->getAttempts()
                ]);

            return [];
        }

        $tokenRegistration->incrementAttempts();

        $this->repo->saveOrFail($tokenRegistration);

        $order = $this->createOrder($tokenRegistration);

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_ORDER_CREATED, $tokenRegistration->getMetricDimensions());

        $paymentSuccess = true;

        try{
            $payment = $this->createPayment($tokenRegistration, $order);
        }
        catch(Exception $ex)
        {
            $paymentSuccess = false;

            $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_FAILED, $tokenRegistration->getMetricDimensions());

            $this->trace->traceException(
                $e,
                null,
                TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_FAILED,
                [
                    'token_registration_id' => $tokenRegistration->getPublicId(),
                ]
            );
        }

        if ($paymentSuccess === true)
        {
            $tokenRegistration->setStatus(Status::COMPLETED);

            $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_SUCCESSFUL, $tokenRegistration->getMetricDimensions());
        }

        $this->repo->saveOrFail($tokenRegistration);
    }

    private function createPayment(Entity $tokenRegistration, Order\Entity $order)
    {
        $token = $tokenRegistration->token;

        $customer =  $token->customer;

        $paymentInput = [
            Payment\Entity::TOKEN       => $token->getPublicId(),
            Payment\Entity::AMOUNT      => $tokenRegistration->getAmount(),
            Payment\Entity::CURRENCY    => $order->getCurrency(),
            Payment\Entity::DESCRIPTION => "",
            Payment\Entity::EMAIL       => $customer->getEmail(),
            Payment\Entity::CONTACT     => $customer->getContact(),
            Payment\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Payment\Entity::ORDER_ID    => $order->getPublicId(),
            Payment\Entity::RECURRING   => '1',
        ];

        $paymentProcessor = new Payment\Processor\Processor($tokenRegistration->merchant);

        $processedPayment = $paymentProcessor->process($paymentInput);

        return $processedPayment;
    }

    private function createOrder(Entity $tokenRegistration)
    {
        $token = $tokenRegistration->token;

        $invoice = $this->repo->invoice->findByMerchantAndTokenRegistration(
            $tokenRegistration->merchant,
            $tokenRegistration
        );

        if (isset($invoice) === false)
        {
            throw new LogicException(
                'invoice can\'t be null',
                null,
                [
                    'token.registration_id' => $tokenRegistration->getPublicId()
                ]
            );
        }

        $previousOrder = $invoice->order;

        if (isset($previousOrder) === false)
        {
            throw new LogicException(
                'order can\'t be null',
                null,
                [
                    'token.registration_id' => $tokenRegistration->getPublicId(),
                    'invoice_id'            => $invoice->getPublicId(),
                ]
            );
        }

        $orderInput = [
            Order\Entity::AMOUNT           => $tokenRegistration->getAmount(),
            Order\Entity::CURRENCY         => $tokenRegistration->getCurrency(),
            Order\Entity::PAYMENT_CAPTURE  => true,
            Order\Entity::METHOD           => $tokenRegistration->getMethod(),
            Order\Entity::NOTES            => $previousOrder->getNotes()->toArray(),
            Order\Entity::RECEIPT          => 'auto_crg_' . Base\UniqueIdEntity::generateUniqueId(),
        ];

        $this->trace->info(
            TraceCode::TOKEN_REGISTRATION_CREATE_ORDER_FOR_AUTO_CHARGE,
            [
                'token_id'    => $token->getId(),
                'orderInput'  => $orderInput
            ]
        );

        $orderCore = new Order\Core();

        $order = $orderCore->create($orderInput, $tokenRegistration->merchant);

        return $order;

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

    public function validateTokenInput(array $input)
    {
        $validator = new Validator();

        $validator->setStrictFalse();

        $validator->validateInput('create', $input);
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

    public function cancelAuthLink(Invoice\Entity $invoice): Invoice\Entity
    {
        $this->trace->info(
            TraceCode::CANCEL_AUTH_LINK,
            [
                'id' => $invoice->getId()
            ]
        );

        $subscriptionRegistration = $invoice->entity;

        if ($subscriptionRegistration === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $order = $invoice->order;

        $order->getValidator()->validateOrderNotPaid();

        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdateAndReload($invoice);

                (new Invoice\Core())->validateIfInvoiceCanBeCancelled($invoice);

                $invoice->setStatus(Invoice\Status::CANCELLED);

                $this->repo->saveOrFail($invoice);
            });

        return $invoice;
    }
}
