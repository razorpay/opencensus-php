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
     * @var Customer\Entity
     */
    protected $customer;

    protected $lineItems = [];

    /**
     * @var LineItem\Core
     */
    protected $lineItemCore;

    protected $bitly;

    const ORDER_CURRENCY = 'INR';
    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

    public function __construct(Merchant\Entity $merchant)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->bitly = $this->app['bitly'];

        $this->lineItemCore = new LineItem\Core;
    }

    public function generate(array $input)
    {
        $invoice = $this->generateInvoiceSkeleton($input);

        try
        {
            $this->repo->transaction(
                function() use ($invoice, $input)
                {
                    $this->buildAndSaveInvoice($input);
                }
            );
        }
        catch (\Exception $e)
        {
            // Check if is Mysql duplicate on unique index error
            if ($e instanceof \Illuminate\Database\QueryException and $e->errorInfo[1] == 1062)
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
            $this->trace->traceException($ex);

            $this->trace->error(
                TraceCode::INVOICE_NOTIFICATION_FAILED,
                [
                    'invoice_id' => $this->invoice->getId()
                ]);
        }

        return $this->invoice;
    }

    protected function generateInvoiceSkeleton(array $input)
    {
        $invoice = new Entity;

        $invoice->build($input);
        $invoice->merchant()->associate($this->merchant);

        //
        // This is being done because dashboard can create an invoice
        // for the merchant even if the merchant has not generated
        // any keys at all.
        //

        $invoice->getValidator()->validateMerchantHasKeys($this->merchant);

        //
        // This is being done so that we can do associations
        // without saving the invoice. Also, to generate a shortUrl,
        // we need the invoice ID.
        //

        $invoice->generateId();

        $this->invoice = $invoice;

        return $invoice;
    }

    protected function buildAndSaveInvoice(array $input)
    {
        $this->customer = $this->associateCustomerWithInvoice($input);

        $this->createLineItemsForInvoice($input);

        $this->createOrderForInvoice();

        $this->setStatus($input);

        $this->setShortUrl();

        // Saving here for the associations
        $this->repo->saveOrFail($this->invoice);

        //
        // This function should be called only after saving the
        // invoice entity and the items entities because the invoice
        // should be created and saved before it can be associated
        // with the items.
        //
        $this->associateLineItemsToInvoice();
    }

    protected function setStatus(array $input)
    {
        $this->invoice->setStatus(Status::ISSUED);

        // // TODO: Needs to be thought about well.
        // if ((isset($input[Entity::DRAFT]) === true) and
        //     ($input[Entity::DRAFT] === 1))
        // {
        //     $this->invoice->setStatus(Status::DRAFT);
        // }
        // else
        // {
        //     $this->invoice->setStatus(Status::ISSUED);
        // }
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

    protected function associateLineItemsToInvoice()
    {
        foreach ($this->lineItems as $lineItem)
        {
            $lineItem->entity()->associate($this->invoice);

            $this->repo->saveOrFail($lineItem);
        }
    }

    protected function createLineItemsForInvoice(array $input)
    {
        $lineItemsDetails = ($input[Entity::LINE_ITEMS]) ?? [];

        if ($lineItemsDetails)
        {
            $this->lineItems = $this->createLineItemsFromInput($lineItemsDetails);

            $invoiceAmount = $this->lineItemCore->getTotalAmountFromLineItems($this->lineItems);

            $this->invoice->setAmount($invoiceAmount);
        }
    }

    protected function createLineItemsFromInput(array $lineItemsDetails)
    {
        $lineItems = [];

        foreach ($lineItemsDetails as $singleLineItem)
        {
            // It also removes the item details from the array.
            $item = $this->getItemForLineItem($singleLineItem);

            $lineItem = $this->lineItemCore->create(
                $singleLineItem,
                $this->merchant,
                $this->invoice,
                $item
            );

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * Get item details if item_id is set.
     * Otherwise create item with item relevant input from line item.
     * If item is created, then item related input in line item needs to
     * be removed from line item. That's why $lineItem is passed by reference.
     *
     * Returns item created for the line item.
     *
     * @param array $lineItem
     *
     * @return Item\Entity
     */
    protected function getItemForLineItem(array & $lineItem)
    {
        if (isset($lineItem[LineItem\Entity::ITEM_ID]) === true)
        {
            $itemId = $lineItem[LineItem\Entity::ITEM_ID];

            $item = $this->getItemFromItemId($itemId);
        }
        else
        {
            $itemDetails = $this->separateItemInputFromLineItemInput($lineItem);

            $item = $this->createItemFromItemDetails($itemDetails);
        }

        return $item;
    }

    protected function getItemFromItemId($itemId)
    {
        $item = $this->repo->item->findByPublicIdAndMerchant($itemId, $this->merchant);

        $this->validateInvoiceAndItemCurrency($item->getCurrency());

        return $item;
    }

    protected function createItemFromItemDetails(array $itemDetails)
    {
        if (isset($itemDetails[Item\Entity::CURRENCY]) === false)
        {
            $itemDetails[Item\Entity::CURRENCY] = $this->invoice->getCurrency();
        }

        $this->validateInvoiceAndItemCurrency($itemDetails[Item\Entity::CURRENCY]);

        $item = (new Item\Core)->create($itemDetails, $this->merchant);

        return $item;
    }

    protected function createOrderForInvoice()
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

        return $order;
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

        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

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

        return $customer;
    }

    /**
     * Request payload contains flattened linesItemDetails,
     * i.e. It has line item attributes (eg. quantity) and
     * the contained item attributes (eg. name, amount etc.).
     *
     * This function separates those payloads for it to be used further.
     *
     * @param array $lineItemDetails
     *
     * @return array
     */
    protected function separateItemInputFromLineItemInput(array & $lineItemDetails)
    {
        $itemDetails = [];

        foreach ($lineItemDetails as $key => $value)
        {
            if (in_array($key, Item\Entity::$allFields, true))
            {
                $itemDetails[$key] = $value;

                unset($lineItemDetails[$key]);
            }
        }

        return $itemDetails;
    }

    /**
     * @param string $itemCurrency
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateInvoiceAndItemCurrency(string $itemCurrency)
    {
        if ($itemCurrency !== $this->invoice->getCurrency())
        {
            throw new BadRequestValidationFailureException(
                'Currency of all items should be same as of the invoice itself'
            );
        }
    }
}
