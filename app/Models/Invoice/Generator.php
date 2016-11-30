<?php

namespace RZP\Models\Invoice;

use App;
use Mail;
use Config;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Trace\TraceCode;

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

    protected $bitly;

    const ORDER_CURRENCY = 'INR';
    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

    public function __construct(Merchant\Entity $merchant, Entity $invoice = null)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->invoice  = $invoice;

        $this->bitly = $this->app['bitly'];

        $this->lineItemCore = new LineItem\Core;
    }

    public function generate(array $input, string $operation)
    {
        $this->generateInvoiceSkeleton($input, $operation);

        try
        {
            if ($operation === Validator::CREATE_DRAFT)
            {
                $this->generateDraft($input);
            }
            else
            {
                $this->generateIssued($input);
            }
        }
        catch (\Exception $e)
        {
            // Check if is Mysql duplicate on unique index error
            if (($e instanceof \Illuminate\Database\QueryException) and
                ($e->errorInfo[1] === 1062))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_DUPLICATE_INVOICE_RECEIPT,
                    null,
                    [
                        'invoice_id'    => $this->invoice->getId(),
                        'input'         => $input,
                    ]);
            }

            throw $e;
        }

        return $this->invoice;
    }

    /**
     * Updates associations of existing invoice
     *
     * @param array $input
     */
    public function update(array $input)
    {
        $this->associateCustomerWithInvoice($input);
    }

    public static function getInvoiceLink(string $invoiceId, string $mode)
    {
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
                ErrorCode::SERVER_ERROR_INVOICE_ID_EMPTY
            );
        }

        $app = App::getFacadeRoot();

        $baseInvoiceUrl = $app['config']->get('app.invoice');

        $shortMode = self::SHORT_MODE_TEST;

        if ($mode === Mode::LIVE)
        {
            $shortMode = self::SHORT_MODE_LIVE;
        }

        $invoiceLink = $baseInvoiceUrl . '/' . $shortMode . '/' . Entity::getSignedId($invoiceId);

        return $invoiceLink;
    }

    protected function generateInvoiceSkeleton(array $input, string $operation)
    {
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

    protected function generateDraft(array $input)
    {
        $this->repo->transaction(
            function() use ($input)
            {
                $this->associateCustomerWithInvoice($input);

                $this->createLineItemsFromInputAndSetInvoiceTotalAmount($input);

                // In draft, we don't create short url and order

                $this->repo->saveOrFail($this->invoice);
            }
        );
    }

    protected function generateIssued(array $input)
    {
        $this->repo->transaction(
            function() use ($input)
            {
                $this->associateCustomerWithInvoice($input);

                $this->createLineItemsFromInputAndSetInvoiceTotalAmount($input);

                $this->issueInvoiceAndSave();
            }
        );

        (new Notifier($this->invoice))->sendNotificationToCustomer();
    }

    /**
     * At the time when invoice is to be issued:
     * - validate if it can be issued
     * - create it's order and set the short URL
     * - save the invoice
     */
    public function issueInvoiceAndSave()
    {
        $this->invoice->getValidator()
                      ->validateInvoiceIssue();

        $this->invoice->setStatus(Status::ISSUED);

        $this->createAndAssociateOrderForInvoice();

        $this->setShortUrl();

        $this->repo->saveOrFail($this->invoice);
    }

    protected function setShortUrl()
    {
        $longUrl = self::getInvoiceLink($this->invoice->getId(), $this->mode);

        $shortenedUrl = $this->bitly->shortenUrl($longUrl);

        $this->trace->info(
            TraceCode::INVOICE_LINKS,
            [
                'invoice_id' => $this->invoice->getId(),
                'short_url' => $shortenedUrl,
                'long_url' => $longUrl,
            ]
        );

        $this->invoice->setShortUrl($shortenedUrl);
    }

    protected function createLineItemsFromInputAndSetInvoiceTotalAmount(array $input)
    {
        $lineItemsDetails = ($input[Entity::LINE_ITEMS]) ?? [];

        if (empty($lineItemsDetails))
        {
            return;
        }

        foreach ($lineItemsDetails as $lineItemDetails)
        {
            $lineItem = $this->lineItemCore->create(
                $lineItemDetails,
                $this->merchant,
                $this->invoice
            );

            $this->invoice->lineItems()->save($lineItem);
        }

        $totalAmount = $this->lineItemCore->getInvoiceAmountForLineItems($this->invoice->lineItems()->get());

        $this->invoice->setAmount($totalAmount);
    }

    protected function createAndAssociateOrderForInvoice()
    {
        $orderAmount = $this->invoice->getAmount();

        $orderCurrency = $this->invoice->getCurrency();

        // TODO: Should we store any specific value here?
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
