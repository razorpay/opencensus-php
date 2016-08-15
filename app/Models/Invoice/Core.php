<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Customer;

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

        $invoice = $this->generateInvoice($input);

        return $invoice;
    }

    public function sendInvoiceSms($contact, $invoiceLink, Merchant\Entity $merchant)
    {
        $contact = Customer\Validator::validateAndParseContact($contact);

        $request = $this->getRavenSendInvoiceRequestInput($contact, $invoiceLink, $merchant);

        $response = $this->app['raven']->sendInvoice($request);

        if (isset($response['sms_id']))
        {
            return ['success' => true];
        }

        return ['success' => false];
    }

    protected function getRavenSendInvoiceRequestInput($contact, $invoiceLink, $merchant)
    {
        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $contact,
            'source' => 'api',
            'params' => [
                'merchant_name' => $merchant->getBillingLabelElseName(),
                'invoice_link'  => $invoiceLink,
            ]
        );

        return $request;
    }

    public function sendInvoiceEmail(Entity $invoice, $invoiceLink)
    {
        // TODO: Figure out a proper subject name
        $subject = 'Razorpay | Invoice from ' . $invoice->merchant->getBillingLabelElseName();

        $data = [
            'to_email'      => $invoice->getCustomerEmail(),
            'date'          => date('d-M-Y H:m:s T'),
            'subject'       => $subject,
            'mode'          => $this->mode,
            'invoice_link'  => $invoiceLink,
        ];

        Mail::queue('emails.invoice.generated', $data, function($message) use ($data)
        {
            $message->from('invoices@razorpay.com', 'Razorpay Invoices');

            $message->replyTo('support@razorpay.com', 'Razorpay Support');

            $message->subject($data['subject']);

            $message->to($data['to_email']);
        });
    }

    protected function generateInvoice(array $input)
    {
        $invoice = (new Generator($this->merchant))->generate($input);

        return $invoice;
    }

    protected function validateRequest(array $input)
    {
        assert(isset($input[Entity::CUSTOMER_DETAILS]));

        assert((isset($input[Entity::ITEMS])) and
               (count($input[Entity::ITEMS]) > 0));
    }
}