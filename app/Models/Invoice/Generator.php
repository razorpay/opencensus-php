<?php

namespace RZP\Models\Invoice;

use Config;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Options;
use RZP\Constants\Mode;
use RZP\Models\Address;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Plan\Subscription;
use RZP\Models\SubscriptionRegistration;

class Generator extends Base\Core
{
    /**
     * @var Entity
     */
    protected $invoice;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var LineItem\Core
     */
    protected $lineItemCore;

    /**
     * Elfin: Url shortener service
     */
    protected $elfin;

    /**
     * Base invoice url from which invoice link is generated.
     * @var string
     */
    protected $baseInvoiceUrl;

    /**
     * The subscription associated for the invoice.
     *
     * @var Subscription\Entity
     */
    protected $subscription;
    protected $subscriptionId;

    /**
     * The batch entity using which invoice was created.
     *
     * @var Batch\Entity
     */
    protected $batch;
    protected $batchId;

    /**
     * Flag to check if duplicate invoice creation with same internal_ref is allowed
     *
     * @var boolean
     */
    protected $shouldFailOnDuplicateInternalRef;

    /**
     * The mandate entity used for auth links.
     *
     * @var Base\Entity
     */
    protected $externalEntity;

    /**
     * @var Order\Entity
     */
    protected $order;

    protected $options;

    const ORDER_CURRENCY = 'INR';
    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

    const INVOICE_IDEMPOTENCY_KEY_CACHE_TTL = 30 * 60;  // 30 min
    const INVOICE_IDEMPOTENCY_REDIS_KEY     = 'invoice_idempotency_key_';

    public function __construct(Merchant\Entity $merchant, Entity $invoice = null)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->invoice  = $invoice;

        $this->lineItemCore = new LineItem\Core;

        $this->elfin = $this->app['elfin'];

        $this->baseInvoiceUrl = $this->app['config']->get('app.invoice');
    }

    /**
     * Argument can be subscription object (for internal API usage) or
     * a signed subcription id (in case of a request from SubServ)
     *
     * @param null|Subscription\Entity|string $subscription
     *
     * @return Generator
     */
    public function setSubscription($subscription = null)
    {
        if (($subscription instanceof Subscription\Entity) === true)
        {
            $this->subscription = $subscription;
        }
        else if (is_string($subscription) === true)
        {
            $this->subscriptionId = Subscription\Entity::verifyIdAndStripSign($subscription);
        }

        return $this;
    }

    public function setExternalEntity($externalEntity = null)
    {
        $this->externalEntity = $externalEntity;

        return $this;
    }

    /**
     * @param Order\Entity $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Order\Entity or null
     */
    public function getOrder()
    {
        return $this->order;
    }


    /**
     * @param null $batch
     *
     * @return $this
     */
    public function setBatch($batch = null)
    {
        if (($batch instanceof Batch\Entity) === true)
        {
            $this->batch = $batch;
        }
        else if (is_string($batch) === true)
        {
            $this->batchId = $batch;
        }

        return $this;
    }

    public function setShouldFailOnDuplicateInternalRef(bool $shouldFailOnDuplicateInternalRef)
    {
        $this->shouldFailOnDuplicateInternalRef = $shouldFailOnDuplicateInternalRef;

        return $this;
    }

    public function generate(array $input): Entity
    {
        $subscriptionOffers = null;

        if (isset($input['subscription_offers']) === true)
        {
            $subscriptionOffers = array_pull($input, 'subscription_offers');
        }

        $this->generateInvoiceSkeleton($input);

        $this->autoEnableRemindersIfApplicable($input);

        $this->checkDuplicateInternalRef($input);

        $this->copyOptions($input);

        if ($this->invoice->exists === true)
        {
            return $this->invoice;
        }

        // retries the database transaction for 1 time when there is a deadlock error.
        $maxAttempts = 2;

        $this->repo->transaction(
            function() use ($input, $subscriptionOffers)
            {
                $this->preProcessGeneration($input);

                (new Core)->calculateAndSetAmountsOfInvoice($this->invoice);

                if ($this->invoice->getStatus() === Status::ISSUED)
                {
                    $this->issueInvoice($subscriptionOffers);
                }

                $this->repo->saveOrFail($this->invoice);

                $this->setReminderForInvoice($input, $this->invoice);

            }, $maxAttempts);

        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $key = self::INVOICE_IDEMPOTENCY_REDIS_KEY . $this->merchant->getId() . '_' . $input[Entity::IDEMPOTENCY_KEY];

            $this->app['cache']->put($key, $this->invoice->getId(), self::INVOICE_IDEMPOTENCY_KEY_CACHE_TTL);
        }

        return $this->invoice;
    }

    /**
     * Pre-processes invoice creation.
     * - Creates and associate customers
     * - Associates subscription or batch relations if applicable
     * - Creates and associates line items
     *
     * @param array $input
     */
    protected function preProcessGeneration(array $input)
    {
        $this->associateCustomerWithInvoice($input);

        if ($this->subscription !== null)
        {
            $this->invoice->subscription()->associate($this->subscription);

            if ($this->subscription->getStatus() === Subscription\Status::HALTED)
            {
                $this->invoice->setSubscriptionStatus(Status::HALTED);
            }
        }
        else if ($this->subscriptionId !== null)
        {
            $this->invoice->setSubscriptionId($this->subscriptionId);
        }

        if ($this->batchId != null)
        {
            $this->invoice->setBatchId($this->batchId);
        }
        else if ($this->batch !== null)
        {
            $this->invoice->batch()->associate($this->batch);
        }

        $this->createLineItems($input);
    }

    /**
     * This method updates all associations of invoice in update request.
     * Eg. In case of draft invoice, one can update customer details.
     *
     * @param array $input
     *
     * @return null
     */
    public function updateDraftInvoice(array $input)
    {
        $this->associateCustomerWithInvoice($input);

        if (isset($input[Entity::LINE_ITEMS]) === true)
        {
            $this->lineItemCore->updateLineItemsAsPut(
                $input[Entity::LINE_ITEMS],
                $this->merchant,
                $this->invoice);
        }

        (new Core)->calculateAndSetAmountsOfInvoice($this->invoice);

        if ($this->invoice->getStatus() === Status::ISSUED)
        {
            $this->issueInvoice();
        }
    }

    /**
     * Invoice long url is of the following format:
     * <base invoice url>/(t|l)/<Invoice public id>
     * Here t or l is short form for test or live mode.
     *
     * @return string
     *
     * @throws LogicException
     */
    public function getInvoiceLink(): string
    {
        $invoiceId = $this->invoice->getId();

        //
        // This is required here because this piece of code is a little prone to bugs.
        // Invoice ID may not be generated at this point due to which we will
        // get a wrong url. Bitly won't throw an exception because it still gets
        // a valid url. The url would end up being something like 'invoices.razorpay.com/i/inv_'.
        //
        if (empty($invoiceId) === true)
        {
            throw new LogicException(
                'Invoice ID is empty. Should not have reached here',
                 ErrorCode::SERVER_ERROR_INVOICE_ID_EMPTY);
        }

        $shortMode = self::SHORT_MODE_TEST;

        if ($this->mode === Mode::LIVE)
        {
            $shortMode = self::SHORT_MODE_LIVE;
        }

        $invoicePublicId = $this->invoice->getPublicId();

        $invoiceLink = $this->baseInvoiceUrl . '/' . $shortMode . '/' . $invoicePublicId;

        return $invoiceLink;
    }

    protected function  generateInvoiceSkeleton(array $input)
    {
        //
        // If draft=1 in input, validate against createDraftRules else createIssuedRules.
        //

        $operation = Validator::CREATE_ISSUED;

        if ((isset($input[Entity::DRAFT])) and
            ($input[Entity::DRAFT]) === '1')
        {
            $operation = Validator::CREATE_DRAFT;
        }

        $invoice = new Entity;

        // Merchant should get associated before calling build()
        // as invoice's validator uses merchant relation.
        $invoice->merchant()->associate($this->merchant);

        // Associating External entity here. We are doing this because, we are using this in invoice validator
        // to validate amount
        $invoice->entity()->associate($this->externalEntity);

        $invoice->build($input);

        $validator = $invoice->getValidator();

        $validator->validateInput(camel_case($operation), $input);

        //
        // This is being done because dashboard can create an invoice
        // for the merchant even if the merchant has not generated
        // any keys at all.
        //

        $validator->validateMerchantSpecificData();

        if ($this->externalEntity !== null)
        {
            $validator->validateExternalEntity();
        }

        //
        // Separate here as build did not have any context on subscription
        // Max Line item Count Considers Subscription ID to give max
        //
        $validator->validateLineItemsCount();


        // Validate Offer Amount for product type
        $validator->validateOfferAmountIfSubscription($this->subscriptionId);

        //
        // This is being done so that we can do associations
        // without saving the invoice. Also, to generate a shortUrl,
        // we need the invoice ID.
        //
        $invoice->generateId();

        // Capture dashboard user id from dashboard headers if applies
        $this->setInvoiceCreator($invoice);

        // Saves merchant specific details in invoice as copy e.g. merchant label & gstin to use

        if ($invoice->isGSTTaxationApplicable($this->merchant) === true)
        {
            $invoice->setMerchantGstin($this->merchant->getGstin());
        }

        $invoice->setMerchantLabel($this->merchant->getLabelForInvoice());

        $this->invoice = $invoice;
    }

    protected function checkDuplicateInternalRef(array $input)
    {
        if ($this->invoice->getInternalRef() === null)
        {
            return;
        }

        $existingInvoiceWithInternalRef = $this->repo
                                               ->invoice
                                               ->findDuplicateInvoiceByInternalRefForMerchant(
                                                    $this->invoice,
                                                    $this->invoice->getMerchantId());

        if ($existingInvoiceWithInternalRef === null)
        {
            return;
        }

        if ($this->shouldFailOnDuplicateInternalRef === true)
        {
            throw new LogicException('internal_ref must be unique for each invoice',
                ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
                [
                    'invoice_id'          => $this->invoice->getId(),
                    'existing_invoice_id' => $existingInvoiceWithInternalRef->getId(),
                    'internal_ref'        => $this->invoice->getInternalRef()
                ]);
        }

        $this->invoice = $existingInvoiceWithInternalRef;
    }

    /**
     * This method does following:
     * - Validates if invoice can be issued
     * - Create it's order
     * - Set the short URL
     * - Update invoice status
     * - Save the invoice
     */
    public function issueInvoice(array $subscriptionOffers = null)
    {
        $this->invoice->getValidator()
                      ->validateInvoiceIssue();

        $this->invoice->setStatus(Status::ISSUED);

        $this->setOrderForInvoice($subscriptionOffers);

        $this->associateOrderForInvoice();

        $this->setShortUrl();
    }

    private function setReminderForInvoice(array $input, Entity $invoice)
    {
        $reminderCore = new Reminder\Core();

        if(isset($input[Entity::REMINDER_ENABLE]) === true)
        {
            $reminderEnable = boolval($input[Entity::REMINDER_ENABLE]);

            $reminderStatus = ($reminderEnable === true) ? Reminder\Status::PENDING : Reminder\Status::DISABLED;

            $reminderInput[Reminder\Entity::REMINDER_STATUS] = $reminderStatus;

            $reminderCore->create($reminderInput, $invoice);
        }
    }

    /**
     * @return void
     */
    private function setOrderForInvoice(array $subscriptionOffers = null)
    {
        $order = $this->getOrder();

        if (empty($order) === true)
        {
            $orderAmount    = $this->invoice->getAmount();
            $orderCurrency  = $this->invoice->getCurrency();
            $orderReceipt   = $this->invoice->getReceipt();
            $firstMinAmount = $this->invoice->getFirstPaymentMinAmount();

            $orderInput = [
                Order\Entity::AMOUNT                   => $orderAmount,
                Order\Entity::CURRENCY                 => $orderCurrency,
                Order\Entity::RECEIPT                  => $orderReceipt,
                Order\Entity::PAYMENT_CAPTURE          => true,
                Order\Entity::FIRST_PAYMENT_MIN_AMOUNT => $firstMinAmount,
            ];

            if ($this->invoice->isTypeInvoice() === true) {
                $orderInput[Order\Entity::PRODUCT_ID] = $this->invoice->getId();
                $orderInput[Order\Entity::PRODUCT_TYPE] = Order\ProductType::INVOICE;
            }

            if ($this->invoice->isTypeLink() === true) {
                $orderInput[Order\Entity::PRODUCT_ID] = $this->invoice->getId();
                $orderInput[Order\Entity::PRODUCT_TYPE] = Order\ProductType::PAYMENT_LINK;
            }

            if ($this->invoice->isOfSubscription() === true)
            {
                $orderInput[Order\Entity::PRODUCT_ID] = $this->invoice->getSubscriptionId();
                $orderInput[Order\Entity::PRODUCT_TYPE] = Order\ProductType::SUBSCRIPTION;

                if ($subscriptionOffers !== null)
                {
                    $orderInput[Order\Entity::OFFERS] = $subscriptionOffers[Order\Entity::OFFER_ID];

                    $orderInput[Order\Entity::FORCE_OFFER] = $subscriptionOffers[Order\Entity::FORCE_OFFER];
                }
            }

            if (($this->externalEntity !== null) and
                ($this->invoice->isTypeOfSubscriptionRegistration() === true))
            {
                $orderInput[Order\Entity::PRODUCT_ID] = $this->invoice->getId();
                $orderInput[Order\Entity::PRODUCT_TYPE] = Order\ProductType::AUTH_LINK;

                if (($this->externalEntity->getMethod() === SubscriptionRegistration\Method::EMANDATE) or
                    ($this->externalEntity->getMethod() === SubscriptionRegistration\Method::NACH))
                {
                    $orderInput[Order\Entity::METHOD] = $this->externalEntity->getMethod();
                }

                if ($this->externalEntity->getBank() !== null)
                {
                    $orderInput[Order\Entity::BANK] = $this->externalEntity->getBank();
                }
            }

            $partialPayment = $this->invoice->isPartialPaymentAllowed();

            // order creation flow via options requested for TPV
            if( ($this->invoice->isTypeLink() === true) and (empty($this->options) === false) )
            {
                // update order using order keys sent in the options
                if(isset($this->options[Options\Entity::ORDER]) === true)
                {
                    $optionsOrder = $this->options[Options\Entity::ORDER] ?? [];

                    $orderInput = array_merge($orderInput, $optionsOrder);

                    $order = (new Order\Service())->createOrderFromOptionsForPaymentLinks($orderInput, $partialPayment);

                    $this->invoice->order()->associate($order);

                    return;
                }
            }

            // else execute existing code flow
            $order = (new Order\Core)->create($orderInput, $this->merchant, $partialPayment);

            $this->invoice->order()->associate($order);
        }

        return;
    }

    protected function associateOrderForInvoice()
    {
        $order = $this->getOrder();

        if (empty($order) === false)
        {
            $this->invoice->order()->associate($order);

            assertTrue($this->invoice->getAmount() === $order->getAmount());
        }
    }

    protected function setInvoiceCreator(Entity $invoice)
    {
        // If the creation is via batch get the batch creator and associate conditionally.
        if ($this->batch !== null)
        {
            if ($this->batch->isCreatorTypeUser() === true)
            {
                $invoice->user()->associate($this->batch->creator);
            }
        }
        // Else just associates the authenticated user.
        else
        {
            $invoice->user()->associate($this->app->basicauth->getUser());
        }
    }

    protected function setShortUrl()
    {
        $longUrl = $this->getInvoiceLink();

        $shortenedUrl = $this->elfin->shorten($longUrl);

        $this->trace->info(
            TraceCode::INVOICE_LINKS,
            [
                'invoice_id'     => $this->invoice->getId(),
                'invoice_status' => $this->invoice->getStatus(),
                'short_url'      => $shortenedUrl,
                'long_url'       => $longUrl,
            ]);

        $this->invoice->setShortUrl($shortenedUrl);
    }

    protected function createLineItems(array $input)
    {
        if (isset($input[Entity::LINE_ITEMS]) === false)
        {
            return;
        }

        $this->lineItemCore->updateLineItemsAsPut(
            $input[Entity::LINE_ITEMS],
            $this->merchant,
            $this->invoice);
    }

    /**
     * Consumes customer related attributes of $input. Gets called in both create/
     * update flow. Works as follows:
     * - If customer_id is passed, use that and update invoice's copy of attributes
     *   ELSE
     * - If customer is passed, override invoice copy of attributes with those details
     *
     * @param array $input
     */
    protected function associateCustomerWithInvoice(array $input)
    {
        if (array_key_exists(Entity::CUSTOMER_ID, $input) === true)
        {
            $this->associateCustomerWithInvoiceById($input[Entity::CUSTOMER_ID]);
        }
        else if (array_key_exists(Entity::CUSTOMER, $input) === true)
        {
            $this->associateCustomerWithInvoiceByDetails($input[Entity::CUSTOMER]);
        }
    }

    protected function associateCustomerWithInvoiceById(string $id = null)
    {
        if (empty($id) === true)
        {
            $this->invoice->unsetCustomerDetails();

            return;
        }

        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $this->invoice->associateAndSetCustomerDetails($customer);
    }

    protected function associateCustomerWithInvoiceByDetails(array $details)
    {
        $invoiceHasCustomer = $this->invoice->hasCustomer();
        $inputHasCustomerId = array_key_exists(Customer\Entity::ID, $details);

        //
        // If the `customer_details` array has the `id` key defined, we first associate the customer and set invoice
        // level customer attributes. Any additional attributes (like `name`, `contact`, etc) sent in customer_details
        // will override the invoice-level attributes.
        //
        if ($inputHasCustomerId === true)
        {
            $customerId = array_pull($details, Customer\Entity::ID);

            $this->associateCustomerWithInvoiceById($customerId);

            //
            // If a null customer_id was sent, the customer (and all attributes) have been removed from the invoice, in
            // the above function call `associateCustomerWithInvoiceById()`. Hence, just return.
            //
            if (empty($customerId) === true)
            {
                return;
            }
        }

        if (($invoiceHasCustomer === true) or ($inputHasCustomerId === true))
        {
            $this->overrideCustomerOfInvoiceWithDetails($details, $this->merchant);
        }
        else
        {
            $customer = (new Customer\Core)->createLocalCustomer($details, $this->merchant, false);

            $this->invoice->associateAndSetCustomerDetails($customer);
        }
    }

    /**
     * Overrides invoice's copy of customer attributes with one provided in
     * input as $details.
     *
     * @param array $details
     */
    protected function overrideCustomerOfInvoiceWithDetails(array $details, Merchant\Entity $merchant)
    {
        $this->invoice->getValidator()->validateInput('editCustomerDetails', $details);

        $customerDetails = array_except(
            $details,
            [
                Customer\Entity::BILLING_ADDRESS_ID,
                Customer\Entity::SHIPPING_ADDRESS_ID,
            ]);

        $this->processCustomerAddressDetails($details);

        foreach ($customerDetails as $attribute => $value)
        {
            $setter = 'setCustomer' . studly_case($attribute);

            $this->invoice->$setter($value, $merchant);
        }
    }

    protected function processCustomerAddressDetails(array $details)
    {
        if (array_key_exists(Customer\Entity::BILLING_ADDRESS_ID, $details) === true)
        {
            $billingAddressId = $details[Customer\Entity::BILLING_ADDRESS_ID];

            $this->associateCustomerAddressById(Address\Type::BILLING_ADDRESS, $billingAddressId);
        }

        if (array_key_exists(Customer\Entity::SHIPPING_ADDRESS_ID, $details) === true)
        {
            $shippingAddressId = $details[Customer\Entity::SHIPPING_ADDRESS_ID];

            $this->associateCustomerAddressById(Address\Type::SHIPPING_ADDRESS, $shippingAddressId);
        }
    }

    protected function associateCustomerAddressById(string $type, string $id = null)
    {
        // customerBillingAddress() or customerShippingAddress()
        $relation = camel_case('customer_' . $type);

        if ($id === null)
        {
            $this->invoice->$relation()->dissociate();

            return;
        }

        //
        // Note: Currently address has to be of type - billing or shipping. In invoice, an address(be billing or
        // shipping) can be used for any purpose without type restriction.
        //
        $address = $this->repo
                        ->address
                        ->findByPublicIdEntityAndTypeOrFail($id, $this->invoice->customer);

        $this->invoice->$relation()->associate($address);
    }

    private function copyOptions(array $input)
    {
        if(isset($input[Options\Entity::OPTIONS]) == true)
        {
            $this->options = $input[Options\Entity::OPTIONS];
        }
    }

    /**
     * Sets Reminder enable to true in input paramters when reminder settings
     * are enabled for the particular entity.
     *
     * @param array $input
     */
    private function autoEnableRemindersIfApplicable(array &$input)
    {
        // Now enabling for only payment links which comes via API.
        // The type should be link and external entity (used by auth links) should be empty.
        // This below thing will be cleanedup while enabling for authlinks.
        if ((array_key_exists(Entity::REMINDER_ENABLE, $input) === false) and
            (empty($input[Entity::TYPE]) === false) and
            ($input[Entity::TYPE] === 'link') and
            (empty($this->externalEntity) === true) and
            (empty($this->invoice->user) === true))
        {
            $reminderSettingsInput = $this->fetchReminderSettingsInput($input);

            $reminderSettings = (new Reminder\Core)->fetchReminderSettings($reminderSettingsInput);

            if (empty($reminderSettings['items']) === false)
            {
                $input[Entity::REMINDER_ENABLE] = 1;
            }
        }
    }

    /**
     * Refactor this later to accomdate for auth links invoices etc.
     *
     * @param array $input
     *
     * @return array
     */
    private function fetchReminderSettingsInput(array $input)
    {
        $reminderSettingsInput = [
            'namespace' => 'payment_link',
            'active'    => 'true',
        ];

        return $reminderSettingsInput;
    }
}
