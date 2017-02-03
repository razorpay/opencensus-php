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
    protected $issuedPdfPath;
    protected $mode;
    protected $raven;
    protected $slack;

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
        if ($this->canCustomerBeNotifiedNow() === false)
        {
            return;
        }

        if ($this->invoice->getEmailStatus() === NotifyStatus::PENDING)
        {
            $this->emailInvoiceIssuedToCustomer();
        }

        if ($this->invoice->getSmsStatus() === NotifyStatus::PENDING)
        {
            $this->smsInvoiceIssuedToCustomer();
        }

        $this->repo->saveOrFail($this->invoice);
    }

    public function notifyInvoiceExpiredToCustomer()
    {
        assert($this->invoice->isExpired() === true);

        $this->emailInvoiceExpiredToCustomer();
    }

    //  -------------------------------------------------------------------

    public function emailInvoiceIssuedToCustomer()
    {
        if (empty($this->invoice->getCustomerEmail()) === true) return false;

        $data = $this->getInvoiceIssuedMailPayload();

        $this->dispatchMail(
            'emails.invoice.generated',
            $data,
            function($message)
            {
                if ($this->issuedPdfPath !== null)
                {
                    $pdfDisplayName = $this->invoice->getPdfDisplayName();

                    $message->attach(
                        $this->issuedPdfPath,
                        ['as' => $pdfDisplayName, 'mime' => 'application/pdf']);
                }
            });

        $this->invoice->setEmailStatus(NotifyStatus::SENT);

        return true;
    }

    public function emailInvoiceExpiredToCustomer()
    {
        if (empty($this->invoice->getCustomerEmail()) === true) return false;

        $data = $this->getInvoiceExpiredMailPayload();

        $this->dispatchMail('emails.invoice.customer.expired', $data);

        return true;
    }

    public function smsInvoiceIssuedToCustomer()
    {
        $contact = $this->invoice->getCustomerContact();

        if (empty($contact) === true) return false;

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
        if (empty($this->invoice->getCustomerEmail()) === true) return false;

        $data = $this->getInvoiceExpiringMailPayload();

        $this->dispatchMail('emails.invoice.customer.expiring', $data);

        return true;
    }

    // -------------------------------------------------------------------

    protected function dispatchMail(string $template, array $data, $callback = null)
    {
        Mail::send($template, $data, function($message) use ($data, $callback)
        {
            $message->from('invoices@razorpay.com', $data['name']);

            $message->replyTo('support@razorpay.com', 'Razorpay Support');

            $message->subject($data['subject']);

            $message->to($data['email']);

            if ($callback !== null) call_user_func($callback, $message);
        });
    }

    protected function getInvoiceIssuedMailPayload()
    {
        $merchant     = $this->invoice->merchant;
        $merchantName = $merchant->getBillingLabelElseName();

        switch ($this->invoice->getType())
        {
            case Type::LINK:
            case Type::ECOD:
                $subject = 'Payment requested by ' . $merchantName;
                break;

            case Type::INVOICE:
                $subject = 'Invoice from ' . $merchantName;
                break;

            default:
                $subject = 'Payment requested by ' . $merchantName;
        }

        $subject = $subject . ' | Razorpay';

        return [
            'email'   => $this->invoice->getCustomerEmail(),
            'date'    => date('d-M-Y H:m:s T'),
            'subject' => $subject,
            'link'    => $this->invoice->getShortUrl(),
            'name'    => $merchantName,
            'amount'  => $this->invoice->getAmount() / 100,
        ];
    }

    protected function getInvoiceExpiredMailPayload()
    {
        $merchant     = $this->invoice->merchant;
        $merchantName = $merchant->getBillingLabelElseName();

        switch ($this->invoice->getType())
        {
            case Type::LINK:
            case Type::ECOD:
                $subject = "Payment request from $merchantName has expired";
                break;

            case Type::INVOICE:
                $subject = "Invoice from $merchantName has expired";
                break;

            default:
                $subject = "Payment from $merchantName has expired";
        }

        $subject = $subject . ' | Razorpay';

        return [
            'email'   => $this->invoice->getCustomerEmail(),
            'date'    => date('d-M-Y H:m:s T'),
            'subject' => $subject,
            'link'    => $this->invoice->getShortUrl(),
            'name'    => $merchantName,
        ];
    }

    protected function getInvoiceExpiringMailPayload()
    {
        $merchant     = $this->invoice->merchant;
        $merchantName = $merchant->getBillingLabelElseName();

        $now      = Carbon::now('Asia/Kolkata');
        $expireBy = Carbon::createFromTimestamp($invoice->getExpireBy(), 'Asia/Kolkata');
        $diff     = $expireBy->diffForHumans($now);

        switch ($this->invoice->getType())
        {
            case Type::LINK:
            case Type::ECOD:
                $subject = "Payment request from $merchantName will expire $diff";
                break;

            case Type::INVOICE:
                $subject = "Invoice from $merchantName will expire $diff";
                break;

            default:
                $subject = "Payment from $merchantName will expire $diff";
        }

        $subject = $subject . ' | Razorpay';

        return [
            'email'   => $this->invoice->getCustomerEmail(),
            'date'    => date('d-M-Y H:m:s T'),
            'subject' => $subject,
            'link'    => $this->invoice->getShortUrl(),
            'name'    => $merchantName,
        ];
    }

    public function sendNotificationsInBulk()
    {
        //
        // Following two are not required just now.
        // We will introduce these when we implement scheduled_by, due_by stuff.
        //

        // $smsIssuedInvoices   = $this->repo
        //                             ->invoice
        //                             ->getInvoicesForIssuedNotificationToCustomer(Entity::SMS);
        // $emailIssuedInvoices = $this->repo
        //                             ->invoice
        //                             ->getInvoicesForIssuedNotificationToCustomer(Entity::EMAIL);

        // $sentSmsCount = $this->smsInvoiceIssuedToCustomerInBulk($smsIssuedInvoices);

        // $sentEmailCount = $this->emailInvoiceIssuedToCustomerInBulk($emailIssuedInvoices);

        $expiringInvoices = $this->repo
                                 ->invoice
                                 ->getInvoicesForExpiringNotificationToCustomer();

        $expiringEmailsSentCount = $this->emailInvoiceExpiringToCustomerInBulk($expiringInvoices);

        $results = [
            // 'sms_issued_pending'   => count($smsIssuedInvoices),
            // 'email_issued_pending' => count($emailIssuedInvoices),
            // 'sms_issued_sent'      => $sentSmsCount,
            // 'email_issued_sent'    => $sentEmailCount,
            'email_expiring_pending'  => $expiringInvoices->count(),
            'email_expiring_sent'     => $expiringEmailsSentCount,
        ];

        $this->trace->info(
            TraceCode::INVOICE_BULK_NOTIFICATION_SUMMARY,
            $results
        );

        // Post summary to slack
        $message = 'Invoice Notify result';
        $meta    = ['channel' => Config::get('slack.channels.tech_logs')];

        $this->slack->queue($message, $results, $meta);

        return $results;
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
                $totalSent += 1;
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
                $totalSent += 1;
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

            if ($sent === true) ++$totalSent;
        }

        return $totalSent;
    }

    protected function canCustomerBeNotifiedNow()
    {
        if ($this->invoice->isDraft())
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
