<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\PaperMandate;
use RZP\Services\UfhService;
use RZP\Constants\Entity as E;
use RZP\Models\Customer\Token;
use RZP\Exception\LogicException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Processor\Processor;

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
        Order\Entity $order = null,
        string $batchId = null): Invoice\Entity
    {
        $invoice = $this->repo->transaction(
            function() use ($input, $merchant, $batch, $order, $batchId)
            {
                $customer = $this->createCustomer($input, $merchant);

                $subscriptionRegistration = $this->createSubscriptionRegistration($input, $merchant, $customer);

                $invoice = $this->createInvoice($input, $merchant, $subscriptionRegistration, $batch, $order, $batchId);

                return $invoice;
            }
        );

        $tokenRegistration = $invoice->entity;

        $this->generateFormIfApplicable($tokenRegistration, $input);

        $invoice->refresh();

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_CREATED,$tokenRegistration->getMetricDimensions());

        return $invoice;
    }

    public function createAuthLinkForOrder(array $tokenRegistrationInput, Order\Entity $order, Customer\Entity $customer)
    {
        $this->populateAuthLinkParamsFromOrder($tokenRegistrationInput, $order);
        $this->populateInvoiceParamsFromOrderAndCustomer($tokenRegistrationInput, $order, $customer);

        $invoice = $this->repo->transaction(
            function() use ($tokenRegistrationInput, $order, $customer)
            {
                $subscriptionRegistration = $this->createSubscriptionRegistration($tokenRegistrationInput, $this->merchant, $customer);

                $invoice = $this->createInvoice($tokenRegistrationInput, $this->merchant, $subscriptionRegistration, null, $order);

                return $invoice;
            }
        );

        $tokenRegistration = $invoice->entity;

        $this->generateFormIfApplicable($tokenRegistration, $tokenRegistrationInput);

        $order->refresh();

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_CREATED,$tokenRegistration->getMetricDimensions());

        return $invoice;
    }

    protected function generateFormIfApplicable(Entity &$tokenRegistration, array $input = [])
    {
        if ($tokenRegistration->getAuthType() !== Payment\AuthType::PHYSICAL)
        {
            return;
        }

        $createForm = $input[E::SUBSCRIPTION_REGISTRATION][Entity::NACH][Entity::CREATE_FORM] ?? true;

        $paperMandate = $tokenRegistration->paperMandate;

        if (($paperMandate !== null) and ($createForm === true))
        {
            (new PaperMandate\Core)->generateMandateForm($paperMandate);
        }
    }

    private function populateAuthLinkParamsFromOrder(array & $input, Order\Entity $order)
    {
        (new Validator)->validateMethodWithOrder($input, $order);

        $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::METHOD] = $order->getMethod();
    }

    private function populateInvoiceParamsFromOrderAndCustomer(array & $input, Order\Entity $order, Customer\Entity $customer)
    {
        $input[Invoice\Entity::TYPE] = Invoice\Type::LINK;

        if (($order->getMethod() === Payment\Method::NACH) and
            (empty($input[E::SUBSCRIPTION_REGISTRATION]) === false) and
            (empty($input[E::SUBSCRIPTION_REGISTRATION][Entity::NACH]) === false) and
            (array_key_exists(Invoice\Entity::DESCRIPTION, $input[E::SUBSCRIPTION_REGISTRATION][Entity::NACH]) === true))
        {
            $input[Invoice\Entity::DESCRIPTION] = array_pull(
                $input[E::SUBSCRIPTION_REGISTRATION][Entity::NACH],
                Invoice\Entity::DESCRIPTION,
                null
            );
        }
        else
        {
            $input[Invoice\Entity::DESCRIPTION] = "Created by order";
        }

        $input[Invoice\Entity::CURRENCY] = $order->getCurrency();

        $input[Invoice\Entity::AMOUNT] = $order->getAmount();

        $input[Invoice\Entity::CUSTOMER_ID] = $customer->getPublicId();

        $input[Invoice\Entity::EMAIL_NOTIFY] = false;

        $input[Invoice\Entity::SMS_NOTIFY] = false;
    }

    public function createSubscriptionRegistration(array & $input, Merchant\Entity $merchant, Customer\Entity $customer)
    {
        $subrInput = array_pull($input, Constants\Entity::SUBSCRIPTION_REGISTRATION);

        $validator = new Validator;

        $validator->validateInput('create_subscription_registration',$subrInput);

        $validator->validateFirstPaymentAmount($subrInput);

        if (isset($input[Entity::NOTES]) === true)
        {
            $subrInput[Entity::NOTES] =  $input[Entity::NOTES];
        }

        $paperMandateInput = [];

        $this->getPaperMandateInput($paperMandateInput, $subrInput);

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

        if (empty($paperMandateInput) === false)
        {
            $maxAmount = $subscriptionRegistration->getMaxAmount();

            if ($maxAmount !== NULL)
            {
                $paperMandateInput[PaperMandate\Entity::AMOUNT] = $maxAmount;
            }

            $paperMandate = (new PaperMandate\Core)->create($paperMandateInput, $customer);

            $this->setPaperMandateEntity($subscriptionRegistration, $paperMandate);
        }

        if (empty($bankName) === false)
        {
            $subscriptionRegistration->setBank($bankName);
        }

        return $subscriptionRegistration;
    }

    protected function getPaperMandateInput(array & $paperMandateInput, array & $subrInput)
    {
        $method = $subrInput[Entity::METHOD] ?? null;

        if ($method !== Method::NACH)
        {
            return;
        }

        $nachArray = array_pull($subrInput, Entity::NACH, []);

        if (array_key_exists(Entity::FORM_REFERENCE1, $nachArray) === true)
        {
            $paperMandateInput[PaperMandate\Entity::REFERENCE_1] = $nachArray[Entity::FORM_REFERENCE1];
        }

        if (array_key_exists(Entity::FORM_REFERENCE2, $nachArray) === true)
        {
            $paperMandateInput[PaperMandate\Entity::REFERENCE_2] = $nachArray[Entity::FORM_REFERENCE2];
        }

        if (empty($subrInput[Entity::EXPIRE_AT]) === false)
        {
            $paperMandateInput[PaperMandate\Entity::END_AT] = $subrInput[Entity::EXPIRE_AT];
        }

        $paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT] = array_pull($subrInput, Entity::BANK_ACCOUNT);

        if (array_key_exists(BankAccount\Entity::BANK_NAME, $paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT]))
        {
            $bankName = array_pull($paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT], BankAccount\Entity::BANK_NAME);

            $subrInput[Entity::BANK_ACCOUNT][BankAccount\Entity::BANK_NAME] = $bankName;
        }
    }

    public function createCustomer(array & $input, Merchant\Entity $merchant): Customer\Entity
    {
        $details = array_pull($input, Constants\Entity::CUSTOMER) ?? [];

        $customer = (new Customer\Core)->createLocalCustomer($details, $merchant, false);

        $input[Entity::CUSTOMER_ID] = $customer->getPublicId();

        return $customer;
    }

    public function createInvoice(
        array & $input,
        Merchant\Entity $merchant,
        Entity $subscriptionRegistration,
        Batch\Entity $batch = null,
        Order\Entity $order = null,
        String $batchId = null): Invoice\Entity
    {
        $invoiceCore = new Invoice\Core();

        $invoice = $invoiceCore->create(
            $input,
            $merchant,
            null,
            $batch,
            $subscriptionRegistration,
            $batchId,
            $order);

        return $invoice;
    }

    // Associate
    public function associateToken(Entity $subr,  Customer\Token\Entity $token)
    {
        if ($subr->getMethod() === Method::NACH)
        {
            $paperMandate = $subr->paperMandate;

            $paperMandate->setStatus(PaperMandate\Status::AUTHENTICATED);

            $this->repo->saveOrFail($paperMandate);
        }

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

    public function chargeToken(string $id, array $input, Merchant\Entity $merchant, String $batchId = null)
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
            Order\Entity::NOTES           => $input[Order\Entity::NOTES] ?? [],
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
            Payment\Entity::NOTES       => $input[Payment\Entity::NOTES] ?? []
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

        $paymentData =  $paymentProcessor->process($paymentInput);

        if(empty($batchId) === false)
        {
            $payment = $paymentProcessor->getPayment();

            $payment->setBatchId($batchId);

            $this->repo->save($payment);
        }

        return $paymentData;
    }

    public function getUploadedFileUrlByPaymentForNachMethod(Payment\Entity $payment)
    {
        if ($payment->isNach() === false)
        {
            return null;
        }

        $this->app['basicauth']->setMerchant($payment->merchant);

        $merchant = $payment->merchant;

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return null;
        }

        $subscriptionRegistration = $this->repo
                                         ->subscription_registration
                                         ->findByTokenIdAndMerchant($token->getId(), $merchant->getId());

        if ($subscriptionRegistration === null)
        {
            return null;
        }

        $paperMandate = $subscriptionRegistration->paperMandate;

        return $paperMandate->getUploadedFormUrl();
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

    private function setPaperMandateEntity(Entity $subscriptionRegistration, PaperMandate\Entity $paperMandate)
    {
        $subscriptionRegistration->entity()->associate($paperMandate);

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

    public function paperMandateAuthenticate(Entity $subscriptionRegistration, array $input): array
    {
        $paperMandate = $subscriptionRegistration->paperMandate;

        $paperMandateUpload = (new PaperMandate\Core)->authenticate($paperMandate, $input);

        return $paperMandateUpload->toArrayPublic();
    }

    public function paperMandateValidate(Entity $subscriptionRegistration, array $input): array
    {
        $paperMandate = $subscriptionRegistration->paperMandate;

        $paperMandateUpload = (new PaperMandate\Core)->validate($paperMandate, $input);

        return $paperMandateUpload->toArrayPublic();
    }

    public function nachRegisterTestPaymentAuthorizeOrFail(Entity $subscriptionRegistration, array $input)
    {
        $token = $subscriptionRegistration->token;

        if ((empty($input[Entity::SUCCEED]) === false) and
            (boolval($input[Entity::SUCCEED]) === true))
        {
            $this->updateTestTokenEntityRegister($token, Token\RecurringStatus::CONFIRMED);
        }
        else
        {
            $this->updateTestTokenEntityRegister(
                $token,
                Token\RecurringStatus::REJECTED,
                'Drawers signature differs'
            );
        }

        $this->authenticate($subscriptionRegistration, $token);

        $payments = $token->nachPayments;

        $payment = $payments->get(0);

        $this->updateTestPaymentRegister($payment);
    }

    protected function updateTestPaymentRegister(Payment\Entity $payment)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token->getRecurringStatus() === Token\RecurringStatus::CONFIRMED)
        {
            return $this->processAuthorizedTestPayment($payment);
        }

        return $this->processFailedTestPayment($payment);
    }

    protected function processFailedTestPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        $e = new Exception\GatewayErrorException(
            $errorCode,
            null,
            null,
            [
                'payment_id' => $payment->getId(),
                'gateway'    => 'mock',
            ]);

        $processor = $processor->setPayment($payment);

        $processor->updatePaymentAuthFailed($e);
    }

    protected function processAuthorizedTestPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        $data = $processor->processAuth($payment);

        if ($payment->hasBeenCaptured() === false)
        {
            $this->captureAuthorizedTestPayment($payment);
        }

        return $data;
    }

    protected function captureAuthorizedTestPayment(Payment\Entity $payment)
    {
        if ($payment->isAuthorized() === false)
        {
            $this->trace->critical(TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                [
                    'status' => $payment->getStatus(),
                    'payment_id' => $payment->getId(),
                ]);

            return;
        }

        $amount = $payment->getAmount();

        // The payment amount is inclusive of fees, so we need to capture with the original amount.
        if ($payment->isFeeBearerCustomer() === true)
        {
            $amount = $amount - $payment->getFee();
        }

        $parameters = [
            Payment\Entity::AMOUNT   => $amount,
            Payment\Entity::CURRENCY => $payment->getCurrency()
        ];

        $paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

        $paymentProcessor->capture($payment, $parameters);
    }

    protected function updateTestTokenEntityRegister(Token\Entity $token, string $newRecurringStatus, string $failureReason = null)
    {
        $gatewayToken = 'dummytoken';

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $newRecurringStatus,
            Token\Entity::GATEWAY_TOKEN             => $gatewayToken,
            Token\Entity::RECURRING_FAILURE_REASON  => $failureReason,
        ];

        (new Token\Core)->updateTokenFromNachGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
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
