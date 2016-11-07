<?php

namespace RZP\Models\Invoice;

use App;
use Mail;

use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Order;

class Generator
{
    protected $app;
    protected $trace;
    protected $mode;
    protected $invoice;
    protected $merchant;
    protected $customer;
    protected $lineItems;
    protected $lineItemCore;
    protected $order;
    protected $repo;
    protected $orderRepo;

    const ORDER_CURRENCY = 'INR';

    public function __construct(Merchant\Entity $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $merchant;

        $this->lineItemCore = new LineItem\Core();

        $this->repo = $this->app['repo'];
    }

    public function generate(array $input)
    {
        $this->invoice = new Entity();

        $customerDetails = $input[Entity::CUSTOMER];
        $lineItemsDetails = $input[Entity::LINE_ITEMS];

        $this->invoice->build($input);

        $this->repo->transaction(
            function() use($lineItemsDetails, $customerDetails)
            {
                $this->createAndSetAssociatedEntities($lineItemsDetails, $customerDetails);

                $this->setCustomerDetailsAttributes();

                // Saving here for the associations
                $this->repo->saveOrFail($this->invoice);

                // This function should be called only after saving the invoice entity and the items entities
                // because the invoiceItems entity requires the invoice and items to be created first.
                $this->associateLineItemsToInvoice();
            }
        );

        (new Notifier($this->invoice))->sendNotificationToCustomer();

        return $this->invoice;
    }

    protected function setCustomerDetailsAttributes()
    {
        $this->invoice->setCustomerName($this->customer->getName());
        $this->invoice->setCustomerContact($this->customer->getContact());
        $this->invoice->setCustomerEmail($this->customer->getEmail());
        $this->invoice->setCustomerAddress($this->customer->getCurrentShippingAddress()->getId());
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

        $order = $this->createOrderForInvoice($this->lineItems);
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
            // TODO: We can remove the if block because line_item and invoice and have a one-to-one mapping.
            if (empty($lineItemDetails[LineItem\Entity::ID]) === false)
            {
                $lineItemId = $lineItemDetails[LineItem\Entity::ID];

                LineItem\Entity::verifyIdAndStripSign($lineItemId);

                $lineItem = $this->repo->line_item
                             ->findByIdAndMerchantId($lineItemId, $this->merchant->getId());
            }
            else
            {
                $lineItem = $this->lineItemCore->create($lineItemDetails, $this->merchant);
            }

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    protected function createOrderForInvoice(array $lineItems)
    {
        $orderAmount = $this->lineItemCore->getTotalAmountFromLineItems($lineItems);

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

            $customer = $this->repo->customer
                ->findByIdAndMerchantId($customerId, $this->merchant->getId());
        }
        else
        {
            $customer = (new Customer\Core())->createLocalCustomer($customerDetails, $this->merchant, false);
        }

        return $customer;
    }
}
