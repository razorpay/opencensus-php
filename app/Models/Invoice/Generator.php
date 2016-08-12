<?php

namespace RZP\Models\Invoice;

use RZP\Models\Item;
use RZP\Models\Merchant;
use App;
use RZP\Models\Order;
use RZP\Models\Customer;

class Generator
{
    protected $app;
    protected $trace;
    protected $mode;
    protected $invoice;
    protected $merchant;
    protected $customer;
    protected $items;
    protected $order;
    protected $repo;
    protected $orderRepo;

    public function __construct(Merchant\Entity $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $merchant;

        $this->itemCore = new Item\Core();

        $this->invoiceItemCore = new InvoiceItem\Core();

        $this->repo = $this->app['repo'];
    }

    public function generate(array $input)
    {
        $this->invoice = new Entity();

        $customerDetails = $input[Entity::CUSTOMER_DETAILS];
        $itemsDetails = $input[Entity::ITEMS];

        unset($input[Entity::CUSTOMER_DETAILS]);
        unset($input[Entity::ITEMS]);

        $this->invoice->build($input);

        $this->createAssociatedEntities($itemsDetails, $customerDetails);

        $this->setAssociations();

        // Saving here for the associations
        $this->repo->saveOrFail($this->invoice);

        $this->createMappingBetweenInvoiceAndItems();

        return $this->invoice;
    }
    
    protected function createMappingBetweenInvoiceAndItems()
    {
        foreach ($this->items as $item)
        {
            $this->invoiceItemCore->mapItemToInvoice($this->invoice, $item);
        }
    }

    protected function createAssociatedEntities(array $itemsDetails, array $customerDetails)
    {
        $this->items = $this->createItemsFromInputAndMapToInvoice($itemsDetails);

        $this->order = $this->createOrderForInvoice($this->items);

        $this->customer = $this->getExistingOrCreateCustomerFromInput($customerDetails);
    }

    protected function setAssociations()
    {
        $this->invoice->order()->associate($this->order);

        $this->invoice->customer()->associate($this->customer);
    }

    protected function createItemsFromInputAndMapToInvoice(array $itemsDetails)
    {
        $items = [];

        foreach ($itemsDetails as $itemDetails)
        {
            if (empty($itemDetails['id']) === false)
            {
                $item = $this->repo->item
                             ->findByIdAndMerchantId($itemsDetails[Item\Entity::ID], $this->merchant->getId());

            }
            else
            {
                $item = $this->itemCore->create($itemDetails, $this->merchant);
            }

            $items[] = $item;
        }

        return $items;
    }

    protected function createOrderForInvoice(array $items)
    {
        $orderAmount = $this->itemCore->getTotalAmountFromItems($items);

        // TODO: Add a validation for items that all the
        // items given in the input have the same currency.
        $orderCurrency = $items[0]->getCurrency();

        // TODO: Should we store any specific value here?
        $orderReceipt = 'Invoice Order';

        $orderInput = [
            Order\Entity::AMOUNT    => $orderAmount,
            Order\Entity::CURRENCY  => $orderCurrency,
            Order\Entity::RECEIPT   => $orderReceipt,
        ];

        $order = (new Order\Core())->create($orderInput, $this->merchant);

        return $order;
    }

    protected function getExistingOrCreateCustomerFromInput($customerDetails)
    {
        if (isset($customerDetails['id']) === true)
        {
            $customer = $this->repo->customer
                ->findByIdAndMerchantId($customerDetails['id'], $this->merchant->getId());
        }
        else
        {
            $customer = (new Customer\Core())->createLocalCustomer($customerDetails, $this->merchant, false);
        }

        return $customer;
    }
}