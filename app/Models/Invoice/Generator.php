<?php

namespace RZP\Models\Invoice;

use App;
use Mail;
use Config;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Services\Elfin\Service as Elfin;

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

        $this->setElfinServices();

        $this->baseInvoiceUrl = $this->app['config']->get('app.invoice');
    }

    public function generate(array $input)
    {
        $this->generateInvoiceSkeleton($input);

        try
        {
            $this->repo->transaction(
                function() use ($input)
                {
                    $this->preProcessGeneration($input);

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

        //
        // In case notification to the customer throws any kind of exception,
        // we should not fail the invoice creation.
        //
        try
        {
            (new Notifier($this->invoice))->sendNotificationToCustomer();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                null,
                [
                    'invoice_id' => $this->invoice->getId()
                ]);
        }

        return $this->invoice;
    }

    protected function preProcessGeneration(array $input)
    {
        $this->associateCustomerWithInvoice($input);

        $this->createLineItemsFromInputAndSetInvoiceAmount($input);
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

        if (isset($input[Entity::LINE_ITEMS]) === false)
        {
            return;
        }

        $this->lineItemCore->updateLineItems(
            $input[Entity::LINE_ITEMS],
            $this->merchant,
            $this->invoice);

        $totalAmount = $this->lineItemCore->getTotalAmountOfLineItems($this->invoice);

        $this->invoice->setAmount($totalAmount);
    }

    /**
     * Invoice long url is of the following format:
     * <base invoice url>/(t|l)/<Invoice public id>
     * Here t or l is short form for test or live mode.
     *
     * @return string
     */
    protected function getInvoiceLink()
    {
        $shortMode = self::SHORT_MODE_TEST;

        if ($this->mode === Mode::LIVE)
        {
            $shortMode = self::SHORT_MODE_LIVE;
        }

        $invoiceId = $this->invoice->getPublicId();
        $invoiceLink = $this->baseInvoiceUrl . '/' . $shortMode . '/' . $invoiceId;

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

        $invoice->build($input);

        (new Validator)->validateInput(camel_case($operation), $input);

        $invoice->merchant()->associate($this->merchant);

        //
        // This is being done because dashboard can create an invoice
        // for the merchant even if the merchant has not generated
        // any keys at all.
        //

        $invoice->getValidator()->validateMerchantHasKeys();

        //
        // This is being done so that we can do associations
        // without saving the invoice. Also, to generate a shortUrl,
        // we need the invoice ID.
        //

        $invoice->generateId();

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

    protected function setShortUrl()
    {
        $longUrl = $this->getInvoiceLink();

        $shortenedUrl = $this->elfin->shorten($longUrl, false);

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

    protected function createLineItemsFromInputAndSetInvoiceAmount(array $input)
    {
        if (isset($input[Entity::LINE_ITEMS]) === false)
        {
            return;
        }

        $this->lineItemCore->updateLineItems(
            $input[Entity::LINE_ITEMS],
            $this->merchant,
            $this->invoice);

        $totalAmount = $this->lineItemCore->getTotalAmountOfLineItems($this->invoice);

        $this->invoice->setAmount($totalAmount);
    }

    protected function createAndAssociateOrderForInvoice()
    {
        $orderAmount = $this->invoice->getAmount();

        $orderCurrency = $this->invoice->getCurrency();

        $orderReceipt = 'Invoice Order';

        $orderInput = [
            Order\Entity::AMOUNT            => $orderAmount,
            Order\Entity::CURRENCY          => $orderCurrency,
            Order\Entity::RECEIPT           => $orderReceipt,
            Order\Entity::PAYMENT_CAPTURE   => true,
        ];

        $order = (new Order\Core)->create($orderInput, $this->merchant);

        $this->invoice->order()->associate($order);
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

    /**
     * Sets Elfin services.
     *
     * TO BE REMOVED.
     *
     * For testing purposes, it'll set services to:
     * - gimli:       For demo merchant account
     * - gimli,bitly: For test merchant account
     * - bitly:       For others
     */
    protected function setElfinServices()
    {
        switch ($this->merchant->getEmail())
        {
            case 'harshit.marwah@razorpay.com':
                $this->elfin->setServices([Elfin::GIMLI]);
                break;

            case 'harshil@razorpay.com':
                $this->elfin->setServices([Elfin::GIMLI, Elfin::BITLY]);
                break;

            default:
                $this->elfin->setServices([Elfin::BITLY]);
                break;
        }
    }
}
