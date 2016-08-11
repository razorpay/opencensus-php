<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Customer;

class Core extends Base\Core
{
    const CURRENCY = 'INR';

    protected $itemService;

    protected $itemRepository;
    protected $customerRepository;

    protected $itemCore;
    protected $orderCore;
    protected $customerCore;

    protected $invoiceGenerator;

    public function __construct()
    {
        parent::__construct();

        $this->itemService = new Item\Service();

        $this->itemRepository = $this->repo->item;
        $this->customerRepository = $this->repo->customer;

        $this->itemCore = new Item\Core();
        $this->orderCore = new Order\Core();
        $this->customerCore = new Customer\Core();
    }

    // TODO: The whole create process should be in a transaction
    public function create(array $input)
    {
        // TODO: Should we move this to validator?
        $this->validateRequest($input);

        $itemsDetails = $input[Entity::ITEMS];

        $customerDetails = $input[Entity::CUSTOMER_DETAILS];

        $items = $this->getItemsFromInput($itemsDetails);

        $invoiceOrder = $this->createOrderForInvoice($items);

        $customer = $this->getExistingOrCreateCustomerFromInput($customerDetails);

        $this->invoiceGenerator = new Generator($this->merchant, $invoiceOrder, $customer);

        $invoice = $this->generateInvoice($input, $items);

        return $invoice;
    }

    protected function generateInvoice(array $input, array $items)
    {
        $invoice = $this->invoiceGenerator->generate($input, $items);

        return $invoice;
    }

    protected function getExistingOrCreateCustomerFromInput($customerDetails)
    {
        if (isset($customerDetails['id']) === true)
        {
            $customer = $this->customerRepository
                             ->findByIdAndMerchantId($customerDetails['id'], $this->merchant->getId());
        }
        else
        {
            $customer = $this->customerCore->createLocalCustomer($customerDetails, $this->merchant, false);
        }

        return $customer;
    }

    protected function validateRequest(array $input)
    {
        assert(isset($input[Entity::CUSTOMER_DETAILS]));

        assert((isset($input[Entity::ITEMS])) and
               (count($input[Entity::ITEMS]) > 0));
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

        $order = $this->orderCore->create($orderInput, $this->merchant);

        return $order;
    }

    // TODO: Should we move this function to Item\Service?
    protected function getItemsFromInput(array $itemsDetails)
    {
        $items = [];

        foreach ($itemsDetails as $itemDetails)
        {
            if (empty($itemDetails['id']) === false)
            {
                $items[] = $this->itemRepository
                                ->findByIdAndMerchantId($itemDetails['id'], $this->merchant->getId());
            }
            else
            {
                $items[] = $this->itemCore->create($itemDetails, $this->merchant);
            }
        }

        return $items;
    }
}