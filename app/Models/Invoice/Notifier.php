<?php

namespace RZP\Models\Invoice;

use App;
use Carbon\Carbon;
use Config;
use Mail;
use RZP\Constants\MailTags;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Mail\Invoice as InvoiceMail;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Invoice\ViewDataSerializer;
use RZP\Trace\TraceCode;

class Notifier extends Base\Core
{
    // 300 seconds (5*60)
    const SCHEDULE_TIME_LEEWAY = 300;

    /**
     * @var Entity
     */
    protected $invoice;
    protected $issuedPdfPath;
    protected $mode;
    protected $raven;
    protected $slack;
    protected $slackTechLogsChannel;

    public function __construct($invoice = null, string $issuedPdfPath = null)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->issuedPdfPath = $issuedPdfPath;

        $this->mode = Mode::TEST;

        if (isset($this->app['rzp.mode']) === true)
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->raven = $this->app['raven'];

        $this->slack = $this->app['slack'];

        $this->slackTechLogsChannel = Config::get('slack.channels.tech_logs');
    }

    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    //
    // Methods to notify (via sms|email) events (issued|expired) of invoice.
    //

    public function notifyInvoiceIssuedToCustomer()
    {
        if ($this->canNotifyInvoiceIssuedToCustomer() === false)
        {
            return false;
        }

        if ($this->invoice->getEmailStatus() !== null)
        {
            $this->emailInvoiceIssuedToCustomer();
        }

        if ($this->invoice->getSmsStatus() !== null)
        {
            $this->smsInvoiceIssuedToCustomer();
        }

        $this->repo->saveOrFail($this->invoice);

        return true;
    }

    public function notifyInvoiceExpiredToCustomer()
    {
        if ($this->invoice->isExpired() === false)
        {
            return false;
        }

        return $this->emailInvoiceExpiredToCustomer();
    }

    //  -------------------------------------------------------------------

    public function canNotifyInvoiceIssuedToCustomer()
    {
        if ($this->invoice->isIssued() === false)
        {
            return false;
        }

        $scheduledAt = $this->invoice->getScheduledAt();

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        // If it's not scheduled for within 5 minutes, do not send
        // the notification. Ideally, scheduled_at would be the same
        // as the current time if scheduled_in is set to 0.
        // Since there was some confusion,
        // this condition basically means, that if the invoice
        // needs to be sent within the NEXT 5 minutes, send it now itself.
        // No need to wait for 5 minutes before sending it.

        if ($scheduledAt > ($currentTime + self::SCHEDULE_TIME_LEEWAY))
        {
            return false;
        }

        return true;
    }

    public function emailInvoiceIssuedToCustomer()
    {
        $customerEmail = $this->invoice->getCustomerEmail();

        $this->trace->info(
            TraceCode::INVOICE_EMAIL_ISSUED_REQUEST,
            [
                'invoice_id'     => $this->invoice->getId(),
                'customer_email' => $customerEmail,
            ]);

        if (empty($customerEmail) === true)
        {
            return false;
        }

        $invoiceData = (new ViewDataSerializer($this->invoice))->get();

        $fileData = [
            'name' => $this->invoice->getPdfDisplayName(),
            'path' => $this->issuedPdfPath,
        ];

        $invoiceIssuedMail = new InvoiceMail\Issued(
                                    $invoiceData,
                                    $fileData);

        Mail::send($invoiceIssuedMail);

        $this->invoice->setEmailStatus(NotifyStatus::SENT);

        return true;
    }

    public function emailInvoiceExpiredToCustomer()
    {
        $customerEmail = $this->invoice->getCustomerEmail();

        $this->trace->info(
            TraceCode::INVOICE_EMAIL_EXPIRED_REQUEST,
            [
                'invoice_id'     => $this->invoice->getId(),
                'customer_email' => $customerEmail,
            ]);

        if (empty($customerEmail) === true)
        {
            return false;
        }

        $invoiceData = (new ViewDataSerializer($this->invoice))->get();

        $invoiceExpiredMail = new InvoiceMail\Expired($invoiceData);

        Mail::send($invoiceExpiredMail);

        return true;
    }

    public function smsInvoiceIssuedToCustomer()
    {
        $contact = $this->invoice->getCustomerContact();

        if (empty($contact) === true)
        {
            return false;
        }

        $request = $this->getRavenSendInvoiceRequestInput($contact);

        try
        {
            $response = $this->raven->sendSms($request);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                null,
                [
                    'contact' => $contact,
                    'invoice_id' => $this->invoice->getId(),
                ]);

            return false;
        }

        if (isset($response['sms_id']))
        {
            $this->invoice->setSmsStatus(NotifyStatus::SENT);

            return true;
        }

        return false;
    }

    protected function emailInvoiceExpiringToCustomer()
    {
        $customerEmail = $this->invoice->getCustomerEmail();

        $this->trace->info(
            TraceCode::INVOICE_EMAIL_EXPIRING_REQUEST,
            [
                'invoice_id'     => $this->invoice->getId(),
                'customer_email' => $customerEmail,
            ]);

        if (empty($customerEmail) === true)
        {
            return false;
        }

        $invoiceData = (new ViewDataSerializer($this->invoice))->get();

        $invoiceExpiringMail = new InvoiceMail\Expiring($invoiceData);

        Mail::send($invoiceExpiringMail);

        return true;
    }

    protected function smsInvoiceIssuedToCustomerInBulk(array $invoices)
    {
        $totalSent = 0;

        foreach ($invoices as $invoice)
        {
            $this->setInvoice($invoice);

            $sent = $this->smsInvoiceIssuedToCustomer();

            if ($sent === true)
            {
                $totalSent++;
            }

            $this->repo->saveOrFail($this->invoice);
        }

        return $totalSent;
    }

    protected function emailInvoiceIssuedToCustomerInBulk(array $invoices)
    {
        $totalSent = 0;

        foreach ($invoices as $invoice)
        {
            $this->setInvoice($invoice);

            $sent = $this->emailInvoiceIssuedToCustomer();

            if ($sent === true)
            {
                $totalSent++;
            }

            $this->repo->saveOrFail($this->invoice);
        }

        return $totalSent;
    }

    protected function emailInvoiceExpiringToCustomerInBulk(array $invoices)
    {
        $totalSent = 0;

        foreach ($invoices as $invoice)
        {
            $this->setInvoice($invoice);

            $sent = $this->emailInvoiceExpiringToCustomer();

            if ($sent === true)
            {
                $totalSent++;
            }
        }

        return $totalSent;
    }

    protected function getRavenSendInvoiceRequestInput($contact)
    {
        $merchant = $this->invoice->merchant;

        $request = [
            'receiver' => $contact,
            'source' => 'api.invoice',
            'template' => 'sms.invoice',
            'params' => [
                'merchant_name' => $merchant->getBillingLabelElseName(),
                'invoice_link'  => $this->invoice->getShortUrl(),
                'amount'        => $this->invoice->getAmount() / 100,
            ]
        ];

        $this->trace->info(
            TraceCode::INVOICE_RAVEN_REQUEST,
            [
                'invoice_id' => $this->invoice->getId(),
                'request' => $request,
            ]);

        return $request;
    }
}
