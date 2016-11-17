<?php

namespace RZP\Models\Invoice;

use App;
use Mail;
use Config;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\LineItem;
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

    protected $lineItems;

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
        $this->invoice = new Entity;

        $customerDetails = [];

        $this->invoice->build($input);
        // This is being done so that we can do associations without saving the invoice.
        // Also, to generate a shortUrl, we need the invoice ID.
        $this->invoice->generateId();

        if (isset($input[Entity::CUSTOMER]))
        {
            $customerDetails = $input[Entity::CUSTOMER];
        }

        $lineItemsDetails = $input[Entity::LINE_ITEMS];

        $this->repo->transaction(
            function() use ($lineItemsDetails, $customerDetails, $input)
            {
                $this->createAndSetAssociatedEntities($lineItemsDetails, $customerDetails, $input);

                $this->setCustomerDetailsAttributes();

                $this->setStatus($input);

                $this->setShortUrl();

                // Saving here for the associations
                $this->repo->saveOrFail($this->invoice);

                // This function should be called only after saving the invoice entity and the items entities
                // because the invoice should be created and saved before it can be associated with the items.
                $this->associateLineItemsToInvoice();
            }
        );

        (new Notifier($this->invoice))->sendNotificationToCustomer();

        return $this->invoice;
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

    protected function setCustomerDetailsAttributes()
    {
        $this->invoice->setCustomerName($this->customer->getName());
        $this->invoice->setCustomerContact($this->customer->getContact());
        $this->invoice->setCustomerEmail($this->customer->getEmail());
        $this->invoice->setCustomerAddress($this->customer->getCurrentShippingAddressId());
    }

    protected function associateLineItemsToInvoice()
    {
        foreach ($this->lineItems as $lineItem)
        {
            $lineItem->invoice()->associate($this->invoice);

            $this->repo->saveOrFail($lineItem);
        }
    }

    protected function createAndSetAssociatedEntities(array $lineItemsDetails, array $customerDetails, array $input)
    {
        $this->lineItems = $this->createLineItemsFromInput($lineItemsDetails);

        $invoiceAmount = $this->lineItemCore->getTotalAmountFromLineItems($this->lineItems);
        $this->invoice->setAmount($invoiceAmount);

        $order = $this->createOrderForInvoice();
        $this->invoice->order()->associate($order);

        $this->customer = $this->getExistingOrCreateCustomerFromInput($customerDetails, $input);
        $this->invoice->customer()->associate($this->customer);

        $this->invoice->merchant()->associate($this->merchant);
    }

    protected function createLineItemsFromInput(array $lineItemsDetails)
    {
        $lineItems = [];

        foreach ($lineItemsDetails as $lineItemDetails)
        {
            $lineItemsDetails[LineItem\Entity::CURRENCY] = $this->invoice->getCurrency();

            // Not supporting creating new line items from existing line items, currently.
            $lineItem = $this->lineItemCore->create($lineItemDetails, $this->merchant);

            // // TODO: We can remove the if block because line_item and invoice and have a one-to-one mapping.
            // if (empty($lineItemDetails[LineItem\Entity::ID]) === false)
            // {
            //     $lineItemId = $lineItemDetails[LineItem\Entity::ID];
            //
            //     LineItem\Entity::verifyIdAndStripSign($lineItemId);
            //
            //     $lineItem = $this->repo->line_item->findByIdAndMerchantId($lineItemId, $this->merchant->getId());
            // }
            // else
            // {
            //     $lineItem = $this->lineItemCore->create($lineItemDetails, $this->merchant);
            // }

            $lineItems[] = $lineItem;
        }

        return $lineItems;
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

    protected function getExistingOrCreateCustomerFromInput(array $customerDetails, array $input)
    {
        // This is just for robustness. It would any way fail later in the flow.
        if ((empty($customerDetails) === true) and
            (empty($input[Entity::CUSTOMER_ID]) === true))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_INPUT_CUSTOMER_ABSENT,
                $input
            );
        }

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            Customer\Entity::verifyIdAndStripSign($customerId);

            $customer = $this->repo->customer->findByIdAndMerchantId($customerId, $this->merchant->getId());

            $this->trace->info(
                TraceCode::INVOICE_EXISTING_CUSTOMER,
                [
                    'invoice_id' => $this->invoice->getId(),
                    'customer_id' => $customer->getId(),
                    'customer_input_details' => $customerDetails,
                ]);
        }
        else
        {
            $customer = (new Customer\Core)->createLocalCustomer($customerDetails, $this->merchant, false);
        }

        return $customer;
    }
}
