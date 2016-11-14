<?php

namespace RZP\Models\Invoice;

use App;
use Mail;
use Config;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Order;

class Generator extends Base\Core
{
    protected $invoice;
    protected $merchant;
    protected $customer;
    protected $lineItems;
    protected $lineItemCore;
    protected $bitly;

    const ORDER_CURRENCY = 'INR';
    const JUST_CREATED_TIME = 604800;

    public function __construct(Merchant\Entity $merchant)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->bitly = $this->app['bitly'];

        $this->lineItemCore = new LineItem\Core();
    }

    public function generate(array $input)
    {
        $this->invoice = new Entity();

        $customerDetails = $input[Entity::CUSTOMER];
        $lineItemsDetails = $input[Entity::LINE_ITEMS];

        $this->invoice->build($input);

        $this->repo->transaction(
            function() use ($lineItemsDetails, $customerDetails, $input)
            {
                $this->createAndSetAssociatedEntities($lineItemsDetails, $customerDetails);

                $this->setCustomerDetailsAttributes();

                $this->setStatus($input);

                // Saving here for the associations
                $this->repo->saveOrFail($this->invoice);

                // This function should be called only after saving the invoice entity and the items entities
                // because the invoiceItems entity requires the invoice and items to be created first.
                $this->associateLineItemsToInvoice();
            }
        );

        // This needs to be done after saving the invoice since it requires the invoice ID
        $this->setShortUrl();

        (new Notifier($this->invoice))->sendNotificationToCustomer();

        $this->repo->saveOrFail($this->invoice);

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
        $longUrl = $this->getInvoiceLink($this->invoice->getId());

        $shortenedUrl = $this->bitly->shortenUrl($longUrl);

        $this->invoice->setShortUrl($shortenedUrl);
    }

    public static function getInvoiceLink($invoiceId)
    {
        $app = App::getFacadeRoot();

        $baseInvoiceUrl = $app['config']->get('app.invoice');

        $invoiceLink = $baseInvoiceUrl . '/i/' . Entity::getSignedId($invoiceId);

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

    protected function createAndSetAssociatedEntities(array $lineItemsDetails, array $customerDetails)
    {
        $this->lineItems = $this->createLineItemsFromInput($lineItemsDetails);

        $invoiceAmount = $this->lineItemCore->getTotalAmountFromLineItems($this->lineItems);
        $this->invoice->setAmount($invoiceAmount);

        $order = $this->createOrderForInvoice();
        $this->invoice->order()->associate($order);

        $this->customer = $this->getExistingOrCreateCustomerFromInput($customerDetails);
        $this->invoice->customer()->associate($this->customer);

        $this->invoice->merchant()->associate($this->merchant);
    }

    protected function createLineItemsFromInput(array $lineItemsDetails)
    {
        $lineItems = [];

        foreach ($lineItemsDetails as $lineItemDetails)
        {
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

        $order = (new Order\Core())->create($orderInput, $this->merchant);

        return $order;
    }

    protected function getExistingOrCreateCustomerFromInput($customerDetails)
    {
        if (isset($customerDetails[Customer\Entity::ID]) === true)
        {
            $customerId = $customerDetails[Customer\Entity::ID];

            Customer\Entity::verifyIdAndStripSign($customerId);

            $customer = $this->repo->customer->findByIdAndMerchantId($customerId, $this->merchant->getId());
        }
        else
        {
            $customer = (new Customer\Core())->createLocalCustomer($customerDetails, $this->merchant, false);
        }

        return $customer;
    }
}
