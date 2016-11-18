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

    public function __construct(
        Merchant\Entity $merchant,
        Entity          $invoice = null)
    {
        parent::__construct();

        $this->merchant = $merchant;
        $this->invoice  = $invoice;

        $this->bitly = $this->app['bitly'];

        $this->lineItemCore = new LineItem\Core;
    }

    public function generate(array $input)
    {
        $this->invoice = new Entity;

        $this->invoice->build($input);
        // This is being done so that we can do associations without saving the invoice.
        // Also, to generate a shortUrl, we need the invoice ID.
        $this->invoice->generateId();

        try
        {
            $this->repo->transaction(
                function() use ($input)
                {
                    $this->ensureDependentEntitiesCreated($input);

                    // Set any other attributes, if required
                    $this->setShortUrl();

                    $this->repo->saveOrFail($this->invoice);
                }
            );
        }
        catch (\Exception $e)
        {
            // TODO: Have better alternatives, need to discuss and implement that.
            //       For now, this is the quickest

            // Check if is Mysql duplicate on unique index error
            if ($e instanceof \Illuminate\Database\QueryException and $e->errorInfo[1] == 1062)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_INVOICE_REF_NUM);
            }

            throw $e;
        }

        (new Notifier($this->invoice))->sendNotificationToCustomer();

        return $this->invoice;
    }

    protected function setShortUrl()
    {
        $longUrl = $this->getInvoiceLink($this->invoice->getId(), $this->mode);

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

    protected function setAmount()
    {
        $totalAmount = 0;

        foreach ($this->invoice->lineItems()->get() as $lineItem) {

            $totalAmount += ($lineItem->getQuantity() * $lineItem->item->getAmount());
        }

        $this->invoice->setAmount($totalAmount);
    }

    public static function getInvoiceLink($invoiceId, $mode)
    {
        // This is required here because this piece of code is a little prone to bugs.
        // Invoice ID may not be generated at this point due to which we will
        // get a wrong url. Bitly won't throw an exception because it still gets
        // a valid url. The url would end up being something like 'invoices.razorpay.com/i/inv_'.
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

    protected function ensureDependentEntitiesCreated(array $input)
    {
        $this->ensureLineItemsCreated($input[Entity::LINE_ITEMS]);
        $this->setAmount();
        // $invoiceAmount = $this->lineItemCore->getTotalAmountFromLineItems($this->lineItems);
        // $this->invoice->setAmount(1000);

        $order = $this->createOrderForInvoice();
        $this->invoice->order()->associate($order);

        $this->ensureCustomerAssociation($input);

        $this->invoice->merchant()->associate($this->merchant);
    }

    protected function ensureLineItemsCreated(array $lineItemsDetails)
    {
        foreach ($lineItemsDetails as $lineItemDetails)
        {
            $lineItem = $this->lineItemCore->create($lineItemDetails, $this->invoice);

            $this->invoice->lineItems()->save($lineItem);
        }
    }

    protected function createOrderForInvoice()
    {
        $orderAmount = $this->invoice->getAmount();

        $orderCurrency = self::ORDER_CURRENCY;

        // TODO: Should we store any specific value here?
        $orderReceipt = 'Invoice Order';

        $orderInput = [
            Order\Entity::AMOUNT            => $orderAmount,
            Order\Entity::CURRENCY          => $orderCurrency,
            Order\Entity::RECEIPT           => $orderReceipt,
            Order\Entity::PAYMENT_CAPTURE   => true,
        ];

        $order = (new Order\Core)->create($orderInput, $this->merchant);

        return $order;
    }

    public function ensureCustomerAssociation(array $input)
    {
        // Attach existing customer with given CUSTOMER_ID if exists
        // Create customer from input details
        // If not customer already associated throw error (Case: CREATE)

        $customer        = null;
        $customerDetails = [];
        if (isset($input[Entity::CUSTOMER]))
        {
            $customerDetails = $input[Entity::CUSTOMER];
        }

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);

            $this->trace->info(
                TraceCode::INVOICE_EXISTING_CUSTOMER,
                [
                    'invoice_id' => $this->invoice->getId(),
                    'customer_id' => $customer->getId(),
                    'customer_input_details' => $customerDetails,
                ]);
        }
        elseif ($customerDetails)
        {
            $customer = (new Customer\Core)->createLocalCustomer($customerDetails, $this->merchant, false);
        }

        if ($customer)
        {
            $this->invoice->customer()->associate($customer);

            // Set other customer's attributes in invoices
            $this->invoice->setCustomerName($customer->getName());
            $this->invoice->setCustomerContact($customer->getContact());
            $this->invoice->setCustomerEmail($customer->getEmail());
            $this->invoice->setCustomerAddress($customer->getCurrentShippingAddressId());
        }
        elseif (empty($this->invoice->customer))
        {

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_INPUT_CUSTOMER_ABSENT,
                $input
            );
        }
    }
}
