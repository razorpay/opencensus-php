<?php

namespace RZP\Models\Invoice;

use App;
use Mail;

use RZP\Models\Customer;
use RZP\Models\Item;
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
    protected $items;
    protected $order;
    protected $repo;
    protected $orderRepo;

    // 300 seconds (5*60)
    const FIVE_MINUTES = 300;

    public function __construct(Merchant\Entity $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $merchant;

        $this->itemCore = new Item\Core();

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

        // This function should be called only after createAssociatedEntities since
        // it uses $this->customer which is set in createAssociatedEntities.
        $this->setCustomerDetailsAttributes();

        // Saving here for the associations
        $this->repo->saveOrFail($this->invoice);

        // This function should be called only after saving the invoice entity and the items entities
        // because the invoiceItems entity requires the invoice and items to be created first.
        $this->createMappingBetweenInvoiceAndItems();

        (new Notifier($this->invoice))->sendNotificationToCustomer();

        return $this->invoice;
    }

    protected function setCustomerDetailsAttributes()
    {
        $this->invoice->setCustomerName($this->customer->getName());
        $this->invoice->setCustomerContact($this->customer->getContact());
        $this->invoice->setCustomerEmail($this->customer->getEmail());
        $this->invoice->setCustomerAddress($this->customer->getAddress());
    }

    protected function createMappingBetweenInvoiceAndItems()
    {
        $itemIds = $this->itemCore->getIdsFromItems($this->items);

        // attach can be used when a relation is defined as belongsToMany()
        $this->invoice->items()->attach($itemIds);
    }

    protected function createAssociatedEntities(array $itemsDetails, array $customerDetails)
    {
        $this->items = $this->createItemsFromInput($itemsDetails);

        $this->order = $this->createOrderForInvoice($this->items);

        $this->customer = $this->getExistingOrCreateCustomerFromInput($customerDetails);
    }

    protected function setAssociations()
    {
        $this->invoice->order()->associate($this->order);

        $this->invoice->customer()->associate($this->customer);

        $this->invoice->merchant()->associate($this->merchant);
    }

    protected function createItemsFromInput(array $itemsDetails)
    {
        $items = [];

        foreach ($itemsDetails as $itemDetails)
        {
            if (empty($itemDetails['id']) === false)
            {
                $item = $this->repo->item
                             ->findByIdAndMerchantId($itemDetails[Item\Entity::ID], $this->merchant->getId());

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