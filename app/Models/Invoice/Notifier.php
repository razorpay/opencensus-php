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
use RZP\Constants\MailTags;
use RZP\Exception;

class Notifier extends Base\Core
{
    // 300 seconds (5*60)
    const SCHEDULE_TIME_LEEWAY = 300;

    const MAIL_TAG_MAP = [
        Type::ECOD    => MailTags::ECOD,
        Type::INVOICE => MailTags::INVOICE,
    ];

    /**
     * @var Entity
     */
    protected $invoice;
    protected $issuedPdfPath;
    protected $mode;
    protected $raven;
    protected $slack;
    protected $slackTechLogsChannel;
    protected $mailSubjectTemplates;
    protected $dashboardUrl;

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

        $this->dashboardUrl = Config::get('applications.dashboard.url');

        $this->setMailSubjectTemplates();
    }

    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    //
    // Methods to notify (via sms|email) events (issued|expired) of invoice.
    //

    public function notifyInvoiceIssuedToCustomer(): bool
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

    public function notifyInvoiceExpiredToCustomer(): bool
    {
        if ($this->invoice->isExpired() === false)
        {
            return false;
        }

        return $this->emailInvoiceExpiredToCustomer();
    }

    //  -------------------------------------------------------------------

    public function canNotifyInvoiceIssuedToCustomer(): bool
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

    public function emailInvoiceIssuedToCustomer(): bool
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

        $data = $this->getInvoiceIssuedMailPayload();

        $this->dispatchMail(
            'emails.invoice.customer.notification',
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

    public function emailInvoiceExpiredToCustomer(): bool
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

        $data = $this->getInvoiceExpiredMailPayload();

        $this->dispatchMail('emails.invoice.customer.notification', $data);

        return true;
    }

    public function smsInvoiceIssuedToCustomer(): bool
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

    protected function emailInvoiceExpiringToCustomer(): bool
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

        $data = $this->getInvoiceExpiringMailPayload();

        $this->dispatchMail('emails.invoice.customer.expiring', $data);

        return true;
    }

    // -------------------------------------------------------------------

    protected function dispatchMail(string $template, array $data, $callback = null)
    {
        Mail::send($template, $data, function($message) use ($data, $callback)
        {
            $message->from('invoices@razorpay.com', $data['merchant']['name']);

            $message->replyTo('support@razorpay.com', 'Razorpay Support');

            $message->subject($data['subject']);

            $message->to($data['invoice']['customer']['email']);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $data['invoice']['id']);

            $headers->addTextHeader(MailTags::HEADER, $data['label']);

            if ($callback !== null) call_user_func($callback, $message);
        });
    }

    protected function getInvoiceIssuedMailPayload(): array
    {
        return $this->getInvoiceMailPayload(__FUNCTION__);
    }

    protected function getInvoiceExpiredMailPayload(): array
    {
        return $this->getInvoiceMailPayload(__FUNCTION__);
    }

    protected function getInvoiceExpiringMailPayload(): array
    {
        return $this->getInvoiceMailPayload(__FUNCTION__);
    }

    public function getInvoicePaidMailPayload(): array
    {
        return $this->getInvoiceMailPayload();
    }

    /**
     * Gets invoice payload common to all above events: issued, expired, paid etc.
     *
     * @param string|null $callee - Callee method name, used to construct subject of the mail.
     *
     * @return array
     */
    protected function getInvoiceMailPayload(string $callee = null): array
    {
        $id = $this->invoice->getPublicId();

        $viewPayload = (new ViewDataSerializer($this->invoice))->get();

        $invoiceDashboardPath = $this->invoice->getDashboardPath();

        $extraInvoicePayload = [
            'type_label'    => $this->invoice->getTypeLabel(),
            'pdf_url'       => url("v1/invoices/$id/pdf"),
            'dashboard_url' => $this->dashboardUrl . $invoiceDashboardPath,
        ];

        $viewPayload['invoice'] += $extraInvoicePayload;

        $label = $this->getLabel($this->invoice->getType());
        $viewPayload['label'] = $label;

        //
        // In one of the case callee is null - getInvoicePaidMailPayload.
        // That method is used from Notify.php's flow. And subject construction
        // is done there in this particular flow. We might(later) consider
        // moving invoice's payment notifications here too.
        //
        if ($callee !== null)
        {
            $subject = $this->getInvoiceMailSubject($callee, $viewPayload['merchant']['name']);

            $viewPayload['subject'] = $subject;
        }

        return $viewPayload;
    }

    protected function getInvoiceMailSubject(
        string $callee,
        string $merchantName): string
    {
        if (in_array($callee, array_keys($this->mailSubjectTemplates), true) === false)
        {
            throw new Exception\LogicException("No templates found for callee: $callee");
        }

        $type = $this->invoice->getType();

        return sprintf($this->mailSubjectTemplates[$callee][$type], $merchantName);
    }

    protected function getLabel(string $type): string
    {
        return self::MAIL_TAG_MAP[$type] ?? MailTags::INVOICE;
    }

    public function sendNotificationsInBulk(): array
    {
        $smsIssuedInvoices   = $this->repo
                                    ->invoice
                                    ->getInvoicesForIssuedNotificationToCustomer(Entity::SMS);

        $emailIssuedInvoices = $this->repo
                                    ->invoice
                                    ->getInvoicesForIssuedNotificationToCustomer(Entity::EMAIL);

        $expiringInvoices    = $this->repo
                                    ->invoice
                                     ->getInvoicesForExpiringNotificationToCustomer();

        $sentSmsCount            = $this->smsInvoiceIssuedToCustomerInBulk($smsIssuedInvoices);
        $sentEmailCount          = $this->emailInvoiceIssuedToCustomerInBulk($emailIssuedInvoices);
        $expiringEmailsSentCount = $this->emailInvoiceExpiringToCustomerInBulk($expiringInvoices);

        $results = [
            'sms_issued_pending'     => count($smsIssuedInvoices),
            'email_issued_pending'   => count($emailIssuedInvoices),
            'sms_issued_sent'        => $sentSmsCount,
            'email_issued_sent'      => $sentEmailCount,
            'email_expiring_pending' => $expiringInvoices->count(),
            'email_expiring_sent'    => $expiringEmailsSentCount,
        ];

        $this->trace->info(TraceCode::INVOICE_BULK_NOTIFICATION_SUMMARY, $results);

        // Post summary to slack
        $message = 'Invoice Notify result';
        $meta    = ['channel' => $this->slackTechLogsChannel];

        $this->slack->queue($message, $results, $meta);

        return $results;
    }

    protected function smsInvoiceIssuedToCustomerInBulk(array $invoices): int
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

    protected function emailInvoiceIssuedToCustomerInBulk(array $invoices): int
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

    protected function emailInvoiceExpiringToCustomerInBulk(array $invoices): int
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

    protected function getRavenSendInvoiceRequestInput(string $contact): array
    {
        $merchant = $this->invoice->merchant;

        $request = [
            'receiver' => $contact,
            'source' => 'api.invoice',
            'template' => 'sms.invoice',
            'params' => [
                'merchant_name' => $merchant->getBillingLabelElseName(),
                'invoice_link'  => $this->invoice->getShortUrl(),
                // @todo: Correct this - Should be using net amount now.
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

    protected function setMailSubjectTemplates()
    {
        $this->mailSubjectTemplates = [
            'getInvoiceIssuedMailPayload' => [
                Type::LINK    => ' Payment requested by %s',
                Type::ECOD    => ' Payment requested by %s',
                Type::INVOICE => ' Invoice from %s',
            ],
            'getInvoiceExpiredMailPayload' => [
                Type::LINK    => ' Payment requested from %s has expired',
                Type::ECOD    => ' Payment requested from %s has expired',
                Type::INVOICE => ' Invoice from %s has expired',
            ],
            'getInvoiceExpiringMailPayload' => [
                Type::LINK    => ' Payment request from %s is expiring',
                Type::ECOD    => ' Payment request from %s is expiring',
                Type::INVOICE => ' Invoice from %s is expiring',
            ],
        ];
    }
}
