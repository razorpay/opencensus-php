<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
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

        $this->itemService = new LineItem\Service();

        $this->itemRepository = $this->repo->line_item;
        $this->customerRepository = $this->repo->customer;

        $this->itemCore = new LineItem\Core();
        $this->orderCore = new Order\Core();
        $this->customerCore = new Customer\Core();
    }

    public function create(array $input)
    {
        // TODO: Should we move this to validator?
        $this->validateRequest($input);

        $invoice = (new Generator($this->merchant))->generate($input);

        return $invoice;
    }

    public function sendNotification(Entity $invoice, $medium)
    {
        $commFunc = 'send' . studly_case($medium) . 'NotificationToCustomer';

        $response = (new Notifier($invoice))->$commFunc();

        $this->repo->saveOrFail($invoice);

        if ($response === true)
        {
            return ['success' => true];
        }
        else
        {
            return ['success' => false];
        }
    }

    public function expireInvoices()
    {
        $expiredInvoices = $this->repo->invoice->getExpiredInvoices();

        // TODO: Ensure that when the payment is being made, the invoice is in `issued` state only.
        foreach ($expiredInvoices as $expiredInvoice)
        {
            $expiredInvoice->setStatus(Status::EXPIRED);
            $this->repo->saveOrFail($expiredInvoice);
        }

        $summary = [
            'total'         => $expiredInvoices->count(),
            'invoice_ids'   => $expiredInvoices->getIds(),
        ];

        $this->trace->info(
            TraceCode::EXPIRE_INVOICES,
            $summary
        );

        return $summary;
    }

    protected function validateRequest(array $input)
    {
        assert(isset($input[Entity::CUSTOMER]));

        assert((isset($input[Entity::LINE_ITEMS])) and
               (count($input[Entity::LINE_ITEMS]) > 0));
    }
}
