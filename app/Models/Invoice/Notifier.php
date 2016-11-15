<?php

namespace RZP\Models\Invoice;

use App;
use Config;
use Mail;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Notifier extends Base\Core
{
    // 300 seconds (5*60)
    const SCHEDULE_TIME_LEEWAY = 300;

    /**
     * @var Entity
     */
    protected $invoice;
    protected $invoiceLink;
    protected $mode;
    protected $raven;
    protected $slack;

    public function __construct($invoice = null)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->mode = Mode::TEST;

        if (isset($this->app['rzp.mode']) === true)
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->raven = $this->app['raven'];

        $this->slack = $this->app['slack'];
    }

    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    public function sendNotificationToCustomer()
    {
        if ($this->canInvoiceBeSentNow() === false)
        {
            return;
        }

        if ($this->invoice->getEmailStatus() === NotifyStatus::PENDING)
        {
            $this->sendEmailNotificationToCustomer();
        }

        if ($this->invoice->getSmsStatus() === NotifyStatus::PENDING)
        {
            $this->sendSmsNotificationToCustomer();
        }

        // Saves the new statuses of email and sms
        $this->repo->saveOrFail($this->invoice);
    }

    public function sendEmailNotificationToCustomer()
    {
        $sent = $this->sendInvoiceEmail();

        if ($sent === true)
        {
            $this->invoice->setEmailStatus(NotifyStatus::SENT);
        }
        else
        {
            $this->trace->warning(
                TraceCode::EMAIL_SENDING_FAILED,
                [
                    'invoice_id' => $this->invoice->getId(),
                ]);
        }

        return $sent;
    }

    public function sendSmsNotificationToCustomer()
    {
        $contact = $this->invoice->getCustomerContact();

        if (empty($contact) === true)
        {
            return false;
        }

        $sent = $this->sendInvoiceSms($contact);

        if ($sent === true)
        {
            $this->invoice->setSmsStatus(NotifyStatus::SENT);
        }
        else
        {
            $this->trace->error(
                TraceCode::SMS_SENDING_FAILED,
                [
                    'invoice_id' => $this->invoice->getId(),
                    'sent_status' => $sent,
                    'contact' => $contact,
                ]
            );
        }

        return $sent;
    }

    protected function sendInvoiceSms($contact)
    {
        try
        {
            $contact = Customer\Validator::validateAndParseContact($contact);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $this->trace->error(
                TraceCode::INVOICE_INVALID_CONTACT_NUMBER,
                [
                    'contact' => $contact,
                    'invoice_id' => $this->invoice->getId(),
                ]
            );

            return false;
        }

        $request = $this->getRavenSendInvoiceRequestInput($contact);

        $response = $this->raven->sendSms($request);

        if (isset($response['sms_id']))
        {
            return true;
        }

        return false;
    }

    protected function sendInvoiceEmail()
    {
        $customerEmail = $this->invoice->getCustomerEmail();

        if (empty($customerEmail) === true)
        {
            return false;
        }

        // TODO: Figure out a proper subject name
        $subject = 'Razorpay | Invoice from ' . $this->invoice->merchant->getBillingLabelElseName();

        $data = [
            'to_email'      => $this->invoice->getCustomerEmail(),
            'date'          => date('d-M-Y H:m:s T'),
            'subject'       => $subject,
            'invoice_link'  => $this->invoice->getShortUrl(),
        ];

        $this->trace->info(
            TraceCode::INVOICE_EMAIL_REQUEST,
            [
                'invoice_id' => $this->invoice->getId(),
                'request' => $data,
            ]);

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

        $this->trace->info(
            TraceCode::INVOICE_BULK_NOTIFICATION_SUMMARY,
            $results
        );

        $this->postSummaryToSlack($results);

        return $results;
    }

    protected function postSummaryToSlack($results)
    {
        $message = 'Invoice Notify result';

        $this->slack->queue($message, $results, ['channel' => Config::get('slack.channels.tech_logs')]);

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

    protected function getRavenSendInvoiceRequestInput($contact)
    {
        $merchant = $this->invoice->merchant;

        $request = [
            'context' => $merchant->getId(),
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
