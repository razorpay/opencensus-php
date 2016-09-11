<?php

namespace RZP\Models\Invoice;

use App;
use Config;
use Mail;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Customer;

class Notifier
{
    // 300 seconds (5*60)
    const SCHEDULE_TIME_LEEWAY = 300;

    protected $invoice;
    protected $app;
    protected $repo;
    protected $mode;

    public function __construct($invoice = null)
    {
        $this->invoice = $invoice;

        if (empty($invoice) === false)
        {
            $this->invoiceLink = $this->getInvoiceLink();
        }

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->mode = Mode::TEST;

        if (isset($this->app['rzp.mode']) === true)
        {
            $this->mode = $this->app['rzp.mode'];
        }
    }

    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;

        $this->invoiceLink = $this->getInvoiceLink();
    }

    public function sendNotificationToCustomer()
    {
        if ($this->canInvoiceBeSentNow() === false)
        {
            return;
        }

        if ($this->invoice->getEmailStatus() === Status::PENDING)
        {
            $this->sendEmailNotificationToCustomer();
        }

        if ($this->invoice->getSmsStatus() === Status::PENDING)
        {
            $this->sendSmsNotificationToCustomer();
        }

        // Saves the new statuses of email and sms
        $this->repo->saveOrFail($this->invoice);
    }

    public function sendEmailNotificationToCustomer()
    {
        $sent = $this->sendInvoiceEmail();

        $this->invoice->setEmailStatus(Status::SENT);

        return $sent;
    }

    public function sendSmsNotificationToCustomer()
    {
        $contact = $this->invoice->getCustomerContact();

        $sent = $this->sendInvoiceSms($contact);

        if ($sent === true)
        {
            $this->invoice->setSmsStatus(Status::SENT);
        }
        else
        {
            // TODO: Trace an error here
        }

        return $sent;
    }

    protected function sendInvoiceSms($contact)
    {
        $contact = Customer\Validator::validateAndParseContact($contact);

        $request = $this->getRavenSendInvoiceRequestInput($contact);

        $response = $this->app['raven']->sendInvoice($request);

        if (isset($response['sms_id']))
        {
            return true;
        }

        return false;
    }

    protected function sendInvoiceEmail()
    {
        // TODO: Figure out a proper subject name
        $subject = 'Razorpay | Invoice from ' . $this->invoice->merchant->getBillingLabelElseName();

        $data = [
            'to_email'      => $this->invoice->getCustomerEmail(),
            'date'          => date('d-M-Y H:m:s T'),
            'subject'       => $subject,
            'mode'          => $this->mode,
            'invoice_link'  => $this->invoiceLink,
        ];

        Mail::queue('emails.invoice.generated', $data, function($message) use ($data)
        {
            $message->from('invoices@razorpay.com', 'Razorpay Invoices');

            $message->replyTo('support@razorpay.com', 'Razorpay Support');

            $message->subject($data['subject']);

            $message->to($data['to_email']);
        });

        return true;
    }

    public function sendNotificationsInBulk()
    {
        $smsInvoices = $this->repo->invoice->getInvoicesForNotification(Entity::SMS);

        $emailInvoices = $this->repo->invoice->getInvoicesForNotification(Entity::EMAIL);

        $sentSmsCount = $this->sendSmsInvoicesInBulk($smsInvoices);

        $sentEmailCount = $this->sendEmailInvoicesInBulk($emailInvoices);

        $results = [
            'sms_pending'   => count($smsInvoices),
            'email_pending' => count($emailInvoices),
            'sms_sent'      => $sentSmsCount,
            'email_sent'    => $sentEmailCount,
        ];

        $this->postSummaryToSlack($results);

        return $results;
    }

    protected function postSummaryToSlack($results)
    {
        $message = 'Invoice Notify result';

        $this->app['slack']->queue($message, $results, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $results;
    }

    protected function sendSmsInvoicesInBulk(array $smsInvoices)
    {
        $totalSent = 0;

        foreach ($smsInvoices as $smsInvoice)
        {
            $this->setInvoice($smsInvoice);

            $sent = $this->sendSmsNotificationToCustomer();

            if ($sent === true)
            {
                $totalSent += 1;
            }

            // Saves the new status of sms
            $this->repo->saveOrFail($this->invoice);
        }

        return $totalSent;
    }

    protected function sendEmailInvoicesInBulk(array $emailInvoices)
    {
        $totalSent = 0;

        foreach ($emailInvoices as $emailInvoice)
        {
            $this->setInvoice($emailInvoice);

            $sent = $this->sendEmailNotificationToCustomer();

            if ($sent === true)
            {
                $totalSent += 1;
            }

            // Saves the new status of email
            $this->repo->saveOrFail($this->invoice);
        }

        return $totalSent;
    }

    protected function canInvoiceBeSentNow()
    {
        $scheduledAt = $this->invoice->getScheduledAt();

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        // If it's not scheduled for within 5 minutes, do not send
        // the notification. Ideally, scheduled_at would be the same
        // as the current time if scheduled_in is set to 0.
        if ($scheduledAt > ($currentTime + self::SCHEDULE_TIME_LEEWAY))
        {
            return false;
        }

        return true;
    }

    protected function getRavenSendInvoiceRequestInput($contact)
    {
        $merchant = $this->invoice->merchant;

        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $contact,
            'source' => 'api',
            'params' => [
                'merchant_name' => $merchant->getBillingLabelElseName(),
                'invoice_link'  => $this->invoiceLink,
            ]
        );

        return $request;
    }

    protected function getInvoiceLink()
    {
        $context = Config::get('app.context');

        $baseInvoiceUrl = Config::get('url.invoice')[$context];

        $invoiceLink = $baseInvoiceUrl . '/' . $this->invoice->getId();

        return $invoiceLink;
    }
}