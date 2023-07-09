<?php

namespace RZP\Models\SubscriptionRegistration;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Trace\Tracer;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\UpiMandate;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\CardMandate;
use RZP\Models\PaperMandate;
use RZP\Constants\HyperTrace;
use RZP\Constants\Entity as E;
use RZP\Models\Customer\Token;
use RZP\Exception\LogicException;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\PaperMandate\PaperMandateUpload;
use RZP\Models\Order\Product\Core as ProductCore;
use RZP\Models\Payment\Processor\Upi as UpiPayment;
use \RZP\Models\UpiMandate\Frequency as UpiFrequency;
use \RZP\Models\UpiMandate\Validator as UpiValidator;
use RZP\Models\Customer\GatewayToken\Core as GatewayToken;

class Core extends Base\Core
{
    const TOKEN_CHARGE_IDEMPOTENCY_CACHE_KEY = 'subr_charge_token_batch_row_';

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

        $validator->validateMaxAmount($input, $this->merchant->getCountry());

        if (($input[Entity::METHOD] !== Method::NACH) and
            ($input[Entity::METHOD] !== Method::UPI))
        {
            $validator->validateTokenExpiryDate($input);
        }

        $subscriptionRegistration = (new Entity)->build($input);

        if (($subscriptionRegistration->getMethod() === Payment\Method::CARD) or
            ($subscriptionRegistration->getMethod() === null))
        {
            $variant = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::CARD_MANDATE_ENABLE_MULTIPLE_FREQUENCIES,
                $this->mode
            );

            if ($variant === 'on')
            {
                $validator->validateFrequencyAndMaxAmountCardRecurring($input);
                $subscriptionRegistration->setFrequency($input[Entity::FREQUENCY] ?? Entity::AS_PRESENTED);
            }
        }

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
                $productsArray = array_pull($input, 'products');

                $customer = $this->createCustomer($input, $merchant);

                if(isset($input['subscription_registration']['method'])  and
                    $input['subscription_registration']['method'] == Method::UPI and $order === null)
                {
                    $maxAmountVariant = $this->app->razorx->getTreatment(
                        $this->merchant->getId(),
                        Merchant\RazorxTreatment::UPI_AUTOPAY_DISABLE_MAX_AMOUNT_BLACKLIST,
                        $this->mode
                    );

                    if((strtolower($maxAmountVariant) === 'on') and
                        (isset($input['subscription_registration']['max_amount']) === false))
                    {
                        $input['subscription_registration']['max_amount'] = ($merchant->isBFSIMerchantCategory() === true) ? UpiValidator::BFSI_MAX_AMOUNT_LIMIT : UpiValidator::NON_BFSI_MAX_AMOUNT_LIMIT;
                    }
                    $order = $this->createOrderForUPI($input, $customer);
                }

                $subscriptionRegistration = $this->createSubscriptionRegistration($input, $merchant, $customer);

                $invoice = $this->createInvoice($input, $merchant, $subscriptionRegistration, $batch, $order, $batchId);

                if($productsArray!==null)
                {
                    $newOrder = $invoice->order;
                    $this->associateProducts($newOrder, $productsArray);
                }

                return $invoice;
            }
        );

        $tokenRegistration = $invoice->entity;

        $this->generateFormIfApplicable($tokenRegistration, $input);

        $invoice->refresh();

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_CREATED,$tokenRegistration->getMetricDimensions());

        return $invoice;
    }

    protected function associateProducts($order, array $productsArray)
    {
        (new ProductCore)->createMany($order, $productsArray);
    }

    public function migrateNach(
        array $input,
        string $batchId = null): Customer\Token\Entity
    {
        $merchantCore = new Merchant\Core();
        $merchant     = $merchantCore->get($input['merchant_id']);

        $token = $this->repo->transaction(
            function () use ($input, $merchant, $batchId) {
                $customer = $this->createCustomer($input, $merchant);

                $subscriptionRegistration = $this->createSubscriptionRegistration($input, $merchant, $customer);

                $token = $this->createMigratedToken($customer, $input, $subscriptionRegistration, $merchant, $batchId);

                return $token;
            }
        );

        $token->refresh();

        // ToDo: for metrics
        // $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_MIGRATED,$token->getMetricDimensions());

        return $token;
    }

    public function createOrderForUPI(& $input, $customer)
    {

        // Set default values for frequency and max amount for upi
        $frequency = $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::FREQUENCY] ?? UpiFrequency::MONTHLY;
        $maxAmount = $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::MAX_AMOUNT] ?? null;

        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::UPI_AUTH_LINK_FREQUENCY_AS_PRESENTED_DEFAULT,
            $this->mode
        );

        if ($variant === 'on')
        {
            $frequency = $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::FREQUENCY] ?? UpiFrequency::AS_PRESENTED;
        }


        // re arrange the input
        $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::FREQUENCY]  = $frequency;
        $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::MAX_AMOUNT] = $maxAmount;

        $orderPayLoad =
            [
                Order\Entity::RECEIPT         => $input[Order\Entity::RECEIPT] ?? null,
                Order\Entity::AMOUNT          => $input[Order\Entity::AMOUNT],
                Order\Entity::CURRENCY        => 'INR',
                Order\Entity::METHOD          => Method::UPI,
                Order\Entity::CUSTOMER_ID     => $input[Entity::CUSTOMER_ID],
                Order\Entity::PAYMENT_CAPTURE => 1,
                Order\Entity::TOKEN           =>
                    [
                        UpiMandate\Entity::MAX_AMOUNT      => $maxAmount,
                        UpiMandate\Entity::FREQUENCY       => $frequency,
                        UpiMandate\Entity::START_TIME      => Carbon::now()->addDay(1)->getTimestamp(),
                        UpiMandate\Entity::END_TIME        => isset($input[Constants\Entity::SUBSCRIPTION_REGISTRATION]['expire_at'])
                                                                ? $input[Constants\Entity::SUBSCRIPTION_REGISTRATION]['expire_at']
                                                                : Carbon::now()->addYear(10)->getTimestamp(),
                    ]
            ];

        if (array_key_exists($frequency, UpiMandate\Frequency::$frequencyToRecurringValueMap) === true)
        {
            $orderPayLoad[Order\Entity::TOKEN][UpiMandate\Entity::RECURRING_TYPE]   =  $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][UpiMandate\Entity::RECURRING_TYPE] ??
                UpiMandate\RecurringType::BEFORE;
            $orderPayLoad[Order\Entity::TOKEN][UpiMandate\Entity::RECURRING_VALUE]  = $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][UpiMandate\Entity::RECURRING_VALUE] ??
                UpiMandate\Frequency::$frequencyToRecurringValueMap[$frequency];
        }

        // Add TPV Bank Details, if present in payload
        if (isset($input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::BANK_ACCOUNT]) === true)
        {
            $bankDetails = array_pull($input[Constants\Entity::SUBSCRIPTION_REGISTRATION], Entity::BANK_ACCOUNT);

            if (empty($bankDetails[BankAccount\Entity::IFSC_CODE]) === false and
                empty($bankDetails[BankAccount\Entity::ACCOUNT_NUMBER]) === false)
            {
                $bankDetails[BankAccount\Entity::IFSC] = $bankDetails[BankAccount\Entity::IFSC_CODE];

                unset($bankDetails[BankAccount\Entity::IFSC_CODE]);

                $orderPayLoad[Order\Entity::BANK_ACCOUNT] = $bankDetails;
            }
        }

        $orderService = new Order\Service();

        $order = $orderService->createOrder($orderPayLoad);

        return $order;
    }

    public function createAuthLinkForOrder(array $tokenRegistrationInput, Order\Entity $order, Customer\Entity $customer)
    {
        $this->populateAuthLinkParamsFromOrder($tokenRegistrationInput, $order);
        $this->populateInvoiceParamsFromOrderAndCustomer($tokenRegistrationInput, $order, $customer);
        $this->validateCustomerForAuthLink($customer);

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

    protected function validateCustomerForAuthLink(Customer\Entity $customer)
    {
        $customerDetails = [];

        $customerDetails[Customer\Entity::EMAIL] = $customer->getEmail();

        $customerDetails[Customer\Entity::CONTACT] = $customer->getContact();

        (new Validator)->validateCustomerDetailsForAuthLink($customerDetails, $this->merchant);
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

        $input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::CURRENCY] = $order->getCurrency();
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

        $this->handleBankMerger($subrInput);

        if (isset($input[Order\Entity::CURRENCY]) === true and
            isset($subrInput[Entity::CURRENCY]) === false)
        {
            $subrInput[Entity::CURRENCY] = $input[Order\Entity::CURRENCY];
        }

        $validator = new Validator;

        $validator->validateInput('create_subscription_registration',$subrInput);

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

            $validator->validateBankAccountBeforeCreation($subrInput[Entity::METHOD], $bankInput, $merchant);
        }

        $subscriptionRegistration = $this->create($subrInput, $merchant, $customer);

        if (isset($subrInput[Entity::METHOD]) === true && $subrInput[Entity::METHOD] === Method::UPI)
        {
            return $subscriptionRegistration;
        }

        if (empty($bankInput) === false)
        {
            $this->setDefaultValuesForBank($bankInput, $customer);

            $this->parseBankAccountDetails($bankInput);

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

    protected function handleBankMerger(array & $subrInput = null)
    {
        if ($subrInput !== null and array_key_exists(Constants\Entity::BANK_ACCOUNT, $subrInput) === true)
        {
            array_walk($subrInput[Constants\Entity::BANK_ACCOUNT], function (&$value, $key) {
                if ($key === BankAccount\Entity::IFSC_CODE)
                {
                    $firstFourOfIFSC = substr($value, 0, 4);

                    $mergedBanks = SubscriptionRegistrationConstants::getMergedBanksPaperNach();

                    if (array_key_exists($firstFourOfIFSC, $mergedBanks) === true)
                    {
                        $value = $mergedBanks[$firstFourOfIFSC];
                    }
                }
            });
        }
    }

    protected function getPaperMandateInput(array & $paperMandateInput, array & $subrInput)
    {
        $method   = $subrInput[Entity::METHOD] ?? null;
        $authType = $subrInput[Entity::AUTH_TYPE] ?? null;

        if ($method !== Method::NACH || $authType === Payment\AuthType::MIGRATED)
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

        $paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT] = $subrInput[Entity::BANK_ACCOUNT];

        $this->parseBankAccountDetails($paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT]);

        if (array_key_exists(BankAccount\Entity::BANK_NAME, $paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT]))
        {
            $bankName = array_pull($paperMandateInput[PaperMandate\Entity::BANK_ACCOUNT], BankAccount\Entity::BANK_NAME);

            $subrInput[Entity::BANK_ACCOUNT][BankAccount\Entity::BANK_NAME] = $bankName;
        }
    }

    public function createCustomer(array & $input, Merchant\Entity $merchant): Customer\Entity
    {
        $details = array_pull($input, Constants\Entity::CUSTOMER) ?? [];

        (new Validator)->validateCustomerDetailsForAuthLink($details, $this->merchant);

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

    /*
     * Specific method only to be called by migrate Nach Batch to create token
     */
    public function createMigratedToken(
        $customer,
        array &$input,
        Entity $subscriptionRegistration,
        Merchant\Entity $merchant,
        String $batchId = null): Customer\Token\Entity
    {
        $tokenCore = new Token\Core();

        // remove not-required $input fields
        $removeFields = ['description', 'currency', 'notes', 'customer_id', 'merchant_id'];
        foreach ($removeFields as $f)
        {
            if (isset($input[$f]))
            {
                unset($input[$f]);
            }
        }

        $token = $tokenCore->create(
            $customer,
            $input
        );
        $token->setRecurring(true);
        $token->setRecurringStatus(Token\RecurringStatus::CONFIRMED);
        $this->associateToken($subscriptionRegistration, $token);
        $subscriptionRegistration->setStatus(Status::COMPLETED);

        $gatewayTokenCore = new GatewayToken();
        $gatewayTokenCore->migrate($token, $merchant, $token->terminal);

        $this->repo->saveOrFail($token);
        $this->repo->saveOrFail($subscriptionRegistration);


        $this->trace->info(
            TraceCode::TOKEN_BEING_MIGRATED,
            [
                'token_id' => $token->getId(),
                'batchId'  => $batchId
            ]
        );

        // call webhook
        $event = 'api.token.' . Token\RecurringStatus::CONFIRMED;

        $eventPayload = [
            ApiEventSubscriber::MAIN => $token,
        ];

        $this->app['events']->dispatch($event, $eventPayload);

        return $token;
    }

    // Associate
    public function associateToken(Entity $subr,  Customer\Token\Entity $token)
    {
        if ($subr->getMethod() === Method::NACH && $subr->getAuthType() !== Payment\AuthType::MIGRATED)
        {
            $paperMandate = $subr->paperMandate;

            $paperMandate->setStatus(PaperMandate\Status::AUTHENTICATED);

            $this->repo->saveOrFail($paperMandate);
        }

        $this->repo->reload($subr);

        $subr->token()->associate($token);

        $this->repo->saveOrFail($subr);

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_TOKEN_ASSOCIATED, $subr->getMetricDimensions());

        $this->trace->info(
            TraceCode::SUBSCRIPTION_REGISTRATION_TOKEN_ASSOCIATION,
            [
                'token_id'                      => $token->getId(),
                'subscription_registration_id'  => $subr->getId(),
            ]
        );
    }

    public function authenticate(Entity $subr, Customer\Token\Entity $token)
    {
        $this->repo->reload($subr);

        $subr->setStatus(Status::AUTHENTICATED);

        if (($token->getRecurringStatus() === Customer\Token\RecurringStatus::REJECTED) or
            ($subr->getAmount() === null) or
            ($subr->getAmount() === 0))
        {
            $subr->setStatus(Status::COMPLETED);
        }

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTHENTICATED, $subr->getMetricDimensions());

        $this->repo->saveOrFail($subr);
    }

    public function chargeToken(string $id, array $input, Merchant\Entity $merchant, String $batchId = null, string $idemPotentKey = null)
    {
        $token = null;

        $idemPotentResponse = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_CHECK_IDEMPOTENCY], function () use ($idemPotentKey){
            return $this->checkAndProcessForIdempotencyKeyForTokenCharge($idemPotentKey);
        });

        if ($idemPotentResponse !== null)
        {
            return $idemPotentResponse;
        }

        if ($merchant->isFeatureEnabled(Feature::RECURRING_DEBIT_UMRN) === true)
        {
            $token = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_FETCH_TOKEN_BY_GATEWAY_TOKEN], function () use ($id, $merchant){
                return $this->repo->token->getByGatewayTokenAndMerchantIdWithForceIndex($id, $merchant->getId(),
                    $this->mode);
            });
        }

        if (empty($token) === true)
        {
            $token = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_FETCH_TOKEN], function () use ($id, $merchant){
                return $this->repo->token->findByPublicIdAndMerchant($id, $merchant);
            });
        }

        $customer = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_FETCH_CUSTOMER], function () use ($token){
            return $token->customer;
        });

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
            Order\Entity::PRODUCTS        => $input[Order\Entity::PRODUCTS] ?? [],
        ];

        if(($this->merchant->isTPVRequired() === true) and
           ($token->isUpiRecurringToken() === true))
        {
            $orderInput[Order\Entity::BANK_ACCOUNT] = [Order\Entity::ACCOUNT_NUMBER => $token->getAccountNumber() ?? null,
                                                       BankAccount\Entity::NAME     => '',
                                                       BankAccount\Entity::IFSC     => $token->getIfsc() ?? null];
        }

        $this->trace->info(
            TraceCode::SUBSCRIPTION_REGISTRATION_CREATE_ORDER_FOR_CHARGE,
            [
                'token_id'     => $id,
                'merchant_id'  => $merchant->getPublicId(),
                'orderInput'   => $orderInput,
            ]
        );

        $orderCore = new Order\Core();
        $order = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_CREATE_ORDER], function () use ($orderCore, $orderInput, $merchant){
            return $orderCore->create($orderInput, $merchant);
        });

        if (empty($idemPotentKey) === false)
        {
            // Multiplying by 60, since set accepts ttl in secs
            $this->app['cache']->set($cacheKey = self::TOKEN_CHARGE_IDEMPOTENCY_CACHE_KEY.$idemPotentKey, $order->getId(), 600 * 60);
        }

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

        $paymentProcessor = new Payment\Processor\Processor($merchant);

        $paymentData = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE_PROCESS_PAYMENT], function () use ($paymentProcessor, $paymentInput){
            return $paymentProcessor->process($paymentInput);
        });

        if(empty($batchId) === false)
        {
            $payment = $paymentProcessor->getPayment();
            $payment->setBatchId($batchId);
            $this->repo->save($payment);
        }

        return $paymentData;
    }

    protected function checkAndProcessForIdempotencyKeyForTokenCharge($idemPotentKey)
    {
        if (empty($idemPotentKey) === true)
        {
            return null;
        }

        $cacheKey = self::TOKEN_CHARGE_IDEMPOTENCY_CACHE_KEY.$idemPotentKey;

        $cacheOrderId = $this->app['cache']->get($cacheKey);

        if ($cacheOrderId !== null)
        {
            $cachedOrder = $this->repo->order->findByIdAndMerchant($cacheOrderId, $this->merchant);

            if ($cachedOrder === null)
            {
                return null;
            }

            $payments = $cachedOrder->payments;

            if ($payments->count() > 1)
            {
                // There should only be a single payment for this recurring token charge order.
                // If there are multiple payments, might need to investigate.
                // returning order id
                return [
                    'order_id'            => $cachedOrder->getPublicId(),
                    'razorpay_payment_id' => '',
                ];
            }

            if ($payments->count() === 0)
            {
                // the transaction might be still going on. we will just return order id in these cases.
                // Merchant can get the payment data from reports section.
                return [
                    'order_id'            => $cachedOrder->getPublicId(),
                    'razorpay_payment_id' => '',
                ];
            }

            // One payment has been made and we are able to fetch it. we will return it for now.
            // payment id's existence does not mean the payment is successful.
            // failed payments etc will be found when merchant downloads the report.

            $existingPayment = $payments->get(0);

            return [
                'razorpay_payment_id' => $existingPayment->getPublicId()
            ];
        }

        return null;
    }

    public function getUploadedFileUrlByPaymentForNachMethod(Payment\Entity $payment): array
    {
        if ($payment->isNach() === false)
        {
            return [null, null];
        }

        $this->app['basicauth']->setMerchant($payment->merchant);

        $merchant = $payment->merchant;

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return [null, null];
        }

        $subscriptionRegistration = $this->repo
                                         ->subscription_registration
                                         ->findByTokenIdAndMerchant($token->getId(), $merchant->getId());

        if ($subscriptionRegistration === null)
        {
            return [null, null];
        }

        $paperMandate = $subscriptionRegistration->paperMandate;

        return [$paperMandate->getUploadedFormUrl(), $paperMandate->getCreatedAt()] ;
    }

    private function isValidForAutoCharge(Entity $tokenRegistration)
    {
        $firstChargeNeeded    = ($tokenRegistration->getAmount() > 0 ) === true;

        $authenticatedStatus  = ($tokenRegistration->getStatus() === Status::AUTHENTICATED);

        $noAttempts  = ($tokenRegistration->getAttempts() === 0 );

        $token = $tokenRegistration->token;

        // if tokenRegistration doesn't have token means either it is still not authenticated
        // or token has been deleted and it is not able to fetch deleted token
        if ($token === null)
        {
            return false;
        }

        $tokenConfirmedAt = $token->getConfirmedAt();

        $midDay = Carbon::now(Timezone::IST)->midDay()->getTimestamp();

        $confirmedBeforeCutoff = $tokenConfirmedAt < $midDay ? true : false;

        return ($firstChargeNeeded and $authenticatedStatus and $noAttempts and $confirmedBeforeCutoff);

    }

    public function processAutoCharge(Entity $tokenRegistration)
    {
        if (($tokenRegistration->getStatus() === Status::AUTHENTICATED) and
            (($tokenRegistration->getAmount() === null) or
            ($tokenRegistration->getAmount() === 0)))
        {
            $tokenRegistration->setStatus(Status::COMPLETED);

            $this->repo->saveOrFail($tokenRegistration);

            $this->trace->info(
                TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_PAYMENT,
                [
                    'token.registration_id' => $tokenRegistration->getId(),
                    'status'                => 'completed'
                ]
            );

            return [];
        }

        $isValidForCharge = $this->isValidForAutoCharge($tokenRegistration);

        if ($isValidForCharge === false)
        {
            $tokenRegistration->incrementAttempts();

            $tokenRegistration->setFailureReason('BAD_REQUEST_TOKEN_REGISTRATION_NOT_VALID_FOR_AUTO_CHARGE');

            $this->repo->saveOrFail($tokenRegistration);

            $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_FAILED,
                                $tokenRegistration->getMetricDimensions(
                                    ['failure_reason' => $tokenRegistration->getFailureReason()]));

            $this->trace->info(TraceCode::TOKEN_REGISTRATION_NOT_VALID_FOR_AUTO_CHARGE,
                [
                    'token.registration_id' =>$tokenRegistration->getId(),
                    'amount'   => $tokenRegistration->getAmount(),
                    'status'   => $tokenRegistration->getStatus(),
                    'attempts' => $tokenRegistration->getAttempts()
                ]);

            return [];
        }

        $this->trace->info(
            TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_PAYMENT,
            [
                'token.registration_id' => $tokenRegistration->getId(),
                'status'                => 'before order creation'
            ]
        );

        $order = $this->createOrder($tokenRegistration);

        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_ORDER_CREATED, $tokenRegistration->getMetricDimensions());

        $paymentSuccess = true;

        $tokenRegistration->incrementAttempts();

        $this->trace->info(
            TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_PAYMENT,
            [
                'token.registration_id' => $tokenRegistration->getId(),
                'status'                => 'increment attempts'
            ]
        );

        $this->repo->saveOrFail($tokenRegistration);

        try{
            $payment = $this->createPayment($tokenRegistration, $order);
        }
        catch(Exception $ex)
        {
            $paymentSuccess = false;

            $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_FAILED,
                                $tokenRegistration->getMetricDimensions(['failure_reason' => $ex->getCode()]));

            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_FAILED,
                [
                    'token_registration_id' => $tokenRegistration->getPublicId(),
                ]
            );

            $tokenRegistration->setFailureReason($ex->getCode());

            $this->repo->saveOrFail($tokenRegistration);
        }

        if ($paymentSuccess === true)
        {
            $tokenRegistration->setStatus(Status::COMPLETED);

            $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_SUCCESSFUL, $tokenRegistration->getMetricDimensions());
        }

        $this->repo->saveOrFail($tokenRegistration);
    }

    /**
     * Processes a nach initial payment
     *
     * @param Order\Entity $order
     *
     * @return PaperMandate\Entity
     * @throws Exception\BadRequestException
     */
    public function validateAndGetPaperMandateForNachOrder(Order\Entity $order): PaperMandate\Entity
    {
        $paperMandate = $this->getPaperMandateForOrderIfExists($order);

        if ($paperMandate === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Entity::ORDER_ID);
        }

        return $paperMandate;
    }

    public function getPaperMandateForOrderIfExists(Order\Entity $order)
    {
        if (($order->getMethod() !== Payment\Method::NACH) or
            ($order->invoice === null) or
            ($order->invoice->tokenRegistration === null))
        {
            return null;
        }

        $tokenRegistration = $order->invoice->tokenRegistration;

        if ($tokenRegistration->paperMandate === null)
        {
            throw new LogicException('token registration should have paper mandate for nach method');
        }

        return $tokenRegistration->paperMandate;
    }

    public function uploadNachFormIfApplicableForPayment(array $input, Order\Entity $order)
    {
        $paperMandate = $this->getPaperMandateForOrderIfExists($order);

        if ((empty($input[Payment\Entity::SIGNED_FORM]) === false) and
            ($paperMandate !== null))
        {
            $signedForm = $input[Payment\Entity::SIGNED_FORM];

            (new PaperMandate\Core)->uploadNachFormForPayment(
                $paperMandate,
                [PaperMandate\Entity::FORM_UPLOADED => $signedForm]);
        }
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
            Order\Entity::PRODUCTS         => $previousOrder->products->toArrayPublic()['items'],
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

        if ($token->hasCardMandate() === true)
        {
            (new CardMandate\Core)->cancelMandateBeforeTokenDeletion($token->cardMandate);
        }

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

    public function paperMandateValidate(Entity $subscriptionRegistration, array $input): PaperMandateUpload\Entity
    {
        $paperMandate = $subscriptionRegistration->paperMandate;

        $paperMandateUpload = (new PaperMandate\Core)->validate($paperMandate, $input);

        return $paperMandateUpload;
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

    /**
     * @param array $bankInput
     * Replaces special characters with space
     * Replaces muiltiple spaces with one
     */
    public function parseBankAccountDetails(array & $bankInput): void
    {
        if (array_key_exists(BankAccount\Entity::BENEFICIARY_NAME, $bankInput) === true)
        {
            $beneficiaryName = $bankInput[BankAccount\Entity::BENEFICIARY_NAME];

            //replace special chars with spaces
            $beneficiaryName = preg_replace('/[^A-Za-z0-9 ]/', ' ', $beneficiaryName);

            // remove multiple spaces
            $beneficiaryName = preg_replace('/ +/', ' ', $beneficiaryName);

            $bankInput[BankAccount\Entity::BENEFICIARY_NAME] = $beneficiaryName;
        }
    }
}
