<?php

namespace RZP\Models\Invoice;

use App;
use Mail;
use Carbon\Carbon;
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

        // TODO: Fix this.
        $invoiceLink = 'invoices.razorpay.com';

        $this->sendNotificationToCustomer($invoiceLink);

        return $this->invoice;
    }

    protected function sendNotificationToCustomer($invoiceLink)
    {
        $scheduledAt = $this->invoice->getScheduledAt();

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        // TODO: Add a cron job to send invoice notifications periodically
        
        // If it's not scheduled for within 5 minutes, do not send
        // the notification. Ideally, scheduled_at would be the same
        // as the current time if scheduled_in is set to 0.
        if ($scheduledAt > ($currentTime + self::FIVE_MINUTES))
        {
            // TODO: trace here
            return;
        }

        if ($this->invoice->getEmailStatus() === Status::PENDING)
        {
            $this->sendEmailNotificationToCustomer($invoiceLink);
        }

        if ($this->invoice->getSmsStatus() === Status::PENDING)
        {
            $this->sendSmsNotificationToCustomer($invoiceLink);
        }

        $this->invoice->saveOrFail();
    }

    protected function sendEmailNotificationToCustomer($invoiceLink)
    {
        (new Core())->sendInvoiceEmail($this->invoice, $invoiceLink);

        $this->invoice->setEmailStatus(Status::SENT);
    }

    protected function sendSmsNotificationToCustomer($invoiceLink)
    {
        $contact = $this->invoice->getCustomerContact();

        $response = (new Core())->sendInvoiceSms($contact, $invoiceLink, $this->merchant);

        if ($response['success'] === true)
        {
            $this->invoice->setSmsStatus(Status::SENT);
        }
        else
        {
            // TODO: Trace an error here
        }
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