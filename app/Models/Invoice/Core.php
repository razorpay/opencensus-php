<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Error\ErrorCode;
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

    protected $itemCore;
    protected $orderCore;
    protected $customerCore;

    protected $invoiceGenerator;

    public function __construct()
    {
        parent::__construct();

        $this->itemService = new LineItem\Service();

        $this->itemCore = new LineItem\Core();
        $this->orderCore = new Order\Core();
        $this->customerCore = new Customer\Core();
    }

    public function create(array $input)
    {
        $invoice = (new Generator($this->merchant))->generate($input);

        return $invoice;
    }

    public function sendNotification(Entity $invoice, $medium)
    {
        $commFunc = 'send' . studly_case($medium) . 'NotificationToCustomer';

        $response = (new Notifier($invoice))->$commFunc();

        $this->repo->saveOrFail($invoice);

        return ['success' => $response];
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

    public function fetchStatus(Entity $invoice)
    {
        $paymentId = $invoice->getPaymentId();

        return [
            Entity::STATUS  => $invoice->getStatus(),
            'payment_id'    => $paymentId,
        ];
    }

    public function getFormattedInvoiceData(Merchant\Entity $merchant, $invoiceId)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $merchant);

        $orderId = $invoice->getOrderId();

        $customer = $invoice->customer;

        $data['invoice'] = [
            'order_id'  => Order\Entity::getSignedId($orderId),
            'url'       => $invoice->getShortUrl()
        ];

        $data['customer'] = [
            $customer->toArrayPublic()
        ];

        return $data;
    }
}
