<?php

namespace RZP\Models\Invoice;

use Config;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Plan\Subscription;
use RZP\Services\Elfin\Service as Elfin;
use RZP\Exception\BadRequestValidationFailureException;

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

    /**
     * The batch entity using which invoice was created.
     *
     * @var Batch\Entity
     */
    protected $batch;

    const ORDER_CURRENCY = 'INR';
    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

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
     * @param null|Subscription\Entity $subscription
     *
     * @return Generator
     */
    public function setSubscription(Subscription\Entity $subscription = null)
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * @param null|Batch\Entity $batch
     *
     * @return Generator
     */
    public function setBatch(Batch\Entity $batch = null)
    {
        $this->batch = $batch;

        return $this;
    }

    public function generate(array $input): Entity
    {
        $this->generateInvoiceSkeleton($input);

        try
        {
            $this->repo->transaction(
                function() use ($input)
                {
                    $this->preProcessGeneration($input);

                    (new Core)->calculateAndSetAmountsOfInvoice($this->invoice);

                    if ($this->invoice->getStatus() === Status::ISSUED)
                    {
                        $this->issueInvoice();
                    }

                    $this->repo->saveOrFail($this->invoice);
                });
        }
        catch (\Exception $e)
        {
            ExceptionHandler::handleMySqlUniqueError($e, $this->invoice, $input);
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
     *
     * @throws BadRequestValidationFailureException
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

        if ($this->batch !== null)
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
    protected function getInvoiceLink(): string
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

    protected function generateInvoiceSkeleton(array $input)
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

        $invoice->build($input);

        $validator = $invoice->getValidator();

        $validator->validateInput(camel_case($operation), $input);

        //
        // This is being done because dashboard can create an invoice
        // for the merchant even if the merchant has not generated
        // any keys at all.
        //

        $validator->validateMerchantSpecificData();

        //
        // This is being done so that we can do associations
        // without saving the invoice. Also, to generate a shortUrl,
        // we need the invoice ID.
        //
        $invoice->generateId();

        // Capture dashboard user id from dashboard headers if applies
        $this->setInvoiceUserIdFromDashboardHeadersIfAvailable($invoice);

        $this->invoice = $invoice;
    }

    /**
     * This method does following:
     * - Validates if invoice can be issued
     * - Create it's order
     * - Set the short URL
     * - Update invoice status
     * - Save the invoice
     */
    public function issueInvoice()
    {
        $this->invoice->getValidator()
                      ->validateInvoiceIssue();

        $this->invoice->setStatus(Status::ISSUED);

        $this->createAndAssociateOrderForInvoice();

        $this->setShortUrl();
    }

    protected function setInvoiceUserIdFromDashboardHeadersIfAvailable(Entity $invoice)
    {
        $headers = $this->app['basicauth']->getDashboardHeaders();

        if (array_key_exists(Entity::USER_ID, $headers) === true)
        {
            $invoice->setUserId($headers[Entity::USER_ID]);
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

        //
        // TODO: Currently, since we are not exposing the invoice
        // to the customer at all, should we NOT generate
        // a short_url at all? We can start exposing it when
        // we start exposing the invoices to the customer.
        // This might create issues because the merchant, when
        // he sees a short_url, he might send the link to the
        // customer and the customer might try paying it.
        // We will have to make changes in the invoice
        // template to remove the pay link.
        //
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

    protected function createAndAssociateOrderForInvoice()
    {
        $orderAmount   = $this->invoice->getAmount();
        $orderCurrency = $this->invoice->getCurrency();
        $orderReceipt  = 'Invoice Order';

        $orderInput = [
            Order\Entity::AMOUNT          => $orderAmount,
            Order\Entity::CURRENCY        => $orderCurrency,
            Order\Entity::RECEIPT         => $orderReceipt,
            Order\Entity::PAYMENT_CAPTURE => true,
        ];

        $partialPayment = $this->invoice->isPartialPaymentAllowed();

        $order = (new Order\Core)->create(
                                    $orderInput,
                                    $this->merchant,
                                    $partialPayment);

        $this->invoice->order()->associate($order);

        assertTrue($this->invoice->getAmount() === $order->getAmount());
    }

    /**
     * Invoice can be created via passing customer_id which already exists
     * or providing customer details in 'customer' array in input POST details.
     *
     * This function creates customer if it doesn't exist.
     * It associates customer with invoice.
     *
     * @param array $input
     *
     * @return null|Customer\Entity
     * @throws BadRequestValidationFailureException
     */
    protected function associateCustomerWithInvoice(array $input)
    {
        $customerDetails = ($input[Entity::CUSTOMER]) ?? [];

        $customerId = ($input[Entity::CUSTOMER_ID]) ?? null;

        if ($customerId and $customerDetails)
        {
            throw new BadRequestValidationFailureException(
                'Expecting either customer_id or customer details'
            );
        }

        $customer = null;

        if ($customerId)
        {
            $customer = $this->repo->customer->findByPublicIdAndMerchant(
                                                $customerId, $this->merchant);

            $this->trace->info(
                TraceCode::INVOICE_EXISTING_CUSTOMER,
                [
                    'invoice_id' => $this->invoice->getId(),
                    'customer_id' => $customer->getId(),
                ]);
        }
        else if ($customerDetails)
        {
            $core = new Customer\Core;
            $customer = $core->createLocalCustomer(
                            $customerDetails, $this->merchant, false);
        }

        if ($customer)
        {
            $this->invoice->customer()->associate($customer);
            $this->invoice->setCustomerDetails($customer);
        }
    }
}
