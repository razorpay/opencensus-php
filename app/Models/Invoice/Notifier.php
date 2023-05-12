<?php

namespace RZP\Models\Invoice;

use Mail;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Services\Reminders;
use RZP\Models\Base\Utility;
use RZP\Models\Invoice\Reminder;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Invoice as InvoiceMail;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;

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
    /**
     * @var Reminders
     */
    protected $reminders;

    public function __construct($invoice = null, string $issuedPdfPath = null)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->issuedPdfPath = $issuedPdfPath;

        $this->mode = $this->app['rzp.mode'];

        $this->raven = $this->app['raven'];

        $this->reminders = $this->app['reminders'];

        $this->ba = $this->app['basicauth'];
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
        $this->invoice->getValidator()->validateOperation('notifyInvoiceExpired');

        return $this->emailInvoiceExpiredToCustomer();
    }

    public function createOrUpdateReminder(): bool
    {
        $this->invoice->reload();

        $reminderEntity = $this->repo->invoice_reminder->getByInvoiceId($this->invoice->getId());

        $merchantId = $this->invoice->getMerchantId();

        if((empty($reminderEntity) === false) and
            ($reminderEntity->getReminderStatus() === Reminder\Status::PENDING) and
            (empty($reminderEntity->getReminderId()) === true))
        {
            $request = $this->getRemindersCreateReminderInput();

            $response = [];

            try {
                $response = $this->reminders->createReminder($request, $merchantId);
            }
            catch (Exception\BadRequestException $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::REMINDERS_RESPONSE,
                    [
                        'data'        => $request,
                        'merchant_id' => $merchantId,
                    ]);
            }

            $this->setReminderResponse($response, $reminderEntity);
        }
        elseif ((empty($reminderEntity) === false) and
                ($reminderEntity->getReminderStatus() === Reminder\Status::PENDING) and
                (empty($reminderEntity->getReminderId()) === false))
        {
            $reminderId = $reminderEntity->getReminderId();

            $request = $this->getRemindersUpdateReminderInput();

            $response = [];

            try {
                $response = $this->reminders->updateReminder($request, $reminderId, $merchantId);
            }
            catch (Exception\BadRequestException $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::REMINDERS_RESPONSE,
                    [
                        'data'        => $request,
                        'merchant_id' => $merchantId,
                    ]);
            }

            if(empty($response['id']) === true)
            {
                return false;
            }

            $reminderEntity->setReminderStatus(Reminder\Status::IN_PROGRESS);

            $this->repo->saveOrFail($reminderEntity);
        }

        return true;
    }

    public function setReminderResponse($response, Reminder\Entity $reminder): bool
    {
        if(empty($response['id']) === false)
        {
            $reminder->setReminderStatus(Reminder\Status::IN_PROGRESS);

            $reminder->setReminderId($response['id']);
        }
        else
        {
            $reminder->setReminderStatus(Reminder\Status::FAILED);
        }

        $this->repo->saveOrFail($reminder);

        return true;
    }

    //  -------------------------------------------------------------------

    public function canNotifyInvoiceIssuedToCustomer(): bool
    {
        $this->invoice->getValidator()->validateOperation('notifyInvoiceIssued');

        $scheduledAt = $this->invoice->getScheduledAt();

        $currentTime = Carbon::now()->getTimestamp();

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

    public function emailInvoiceIssuedToCustomer($reminder = false, $newShortUrl = null): bool
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

        $dimensions = $this->invoice->getMetricDimensions(['email_type' => 'issued', 'merchant_country_code' => (string) $this->invoice->merchant->getCountry()]);
        $this->trace->count(Metric::INVOICE_EMAIL_NOTIFY_TOTAL, $dimensions);

        $viewPayload = (new ViewDataSerializer($this->invoice))->serializeForInternal();

        if (($reminder === true) and (empty($newShortUrl) === false))
        {
            $viewPayload['reminder'] = true;
            $viewPayload['invoice']['short_url'] = $newShortUrl;
        }

        if ($this->invoice->isPaymentPageInvoice() === true)
        {
            $viewPayload['pp_invoice'] = true;
        }

        $fileData = [
            'name' => $this->invoice->getPdfDisplayName(),
            'path' => $this->issuedPdfPath,
        ];

        $viewPayload = $this->addEmailSpecificParams($viewPayload);

        $invoiceIssuedMail = new InvoiceMail\Issued($viewPayload, $fileData);

        Mail::send($invoiceIssuedMail);

        $this->invoice->setEmailStatus(NotifyStatus::SENT);

        return true;
    }

    public function emailInvoiceExpiredToCustomer(): bool
    {
        $merchant = $this->invoice->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::INVOICE_NO_EXPIRY_EMAIL) === true)
        {
            return false;
        }

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

        $viewPayload = (new ViewDataSerializer($this->invoice))->serializeForInternal();

        $invoiceExpiredMail = new InvoiceMail\Expired($viewPayload);

        Mail::send($invoiceExpiredMail);

        return true;
    }

    public function smsInvoiceIssuedToCustomer($reminders = false, $newShortUrl = null): bool
    {
        $contact = $this->invoice->getCustomerContact();

        if (empty($contact) === true)
        {
            return false;
        }

        $dimensions = $this->invoice->getMetricDimensions(['sms_type' => 'issued', 'merchant_country_code' => (string) $this->invoice->merchant->getCountry()]);
        $this->trace->count(Metric::INVOICE_SMS_NOTIFY_TOTAL, $dimensions);

        $request = $this->getRavenSendInvoiceRequestInput($contact, $reminders, $newShortUrl);

        try
        {
            $response = $this->raven->sendSms($request, false);
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

        $viewPayload = (new ViewDataSerializer($this->invoice))->serializeForInternal();

        $invoiceExpiringMail = new InvoiceMail\Expiring($viewPayload);

        Mail::send($invoiceExpiringMail);

        return true;
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
                $totalSent++;
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
                $totalSent++;
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

    protected function getRemindersCreateReminderInput(): array
    {
        $reminderData = [
            'issued_at' => $this->invoice->getIssuedAt(),
        ];

        if($this->invoice->getExpireBy() !== null)
        {
            $reminderData['expire_by'] = $this->invoice->getExpireBy();
            unset($reminderData['issued_at']);
        }

        $request = [
            'namespace'     => 'payment_link',
            'entity_id'     => $this->invoice->getId(),
            'entity_type'   => $this->invoice->getEntityName(),
            'reminder_data' => $reminderData,
            'callback_url'  => $this->getCallbackUrlForReminder(),
        ];

        return $request;
    }

    protected function getRemindersUpdateReminderInput(): array
    {

        $reminderData = [
            'issued_at'  => $this->invoice->getIssuedAt(),
        ];

        $expireBy = $this->invoice->getExpireBy();

        if(empty($expireBy) === false)
        {
            $reminderData['expire_by'] = $expireBy;
            unset($reminderData['issued_at']);
        }

        $request = [
            'reminder_data' => $reminderData
        ];

        return $request;
    }

    protected function getCallbackUrlForReminder()
    {
        $baseUrl = 'reminders/send';

        $mode = $this->mode;

        $entity = $this->invoice->getEntityName();

        $namespace = 'payment_link';

        $invoiceId = $this->invoice->getId();

        $callbackURL = sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $invoiceId);

        return $callbackURL;
    }

    protected function getRavenSendInvoiceRequestInput(string $contact, $reminder = false, $newShortUrl = null): array
    {
        $merchant = $this->invoice->merchant;

        $invoiceLink = $this->invoice->getShortUrl();

        if(($reminder === true) and (empty($newShortUrl) === false))
        {
            $invoiceLink = $newShortUrl;
        }

        $defaultTemplate = $reminder ? 'sms.reminder_invoice' : 'sms.invoice';

        $defaultParams   = [
            'merchant_name' => str_limit($merchant->getBillingLabel(),30),
            'invoice_link'  => $invoiceLink,
            'currency'      => $this->invoice->getCurrency() ?? "INR",
            'amount'        => $this->invoice->getAmount() / 100,
        ];

        if ($this->invoice->isTypeOfSubscriptionRegistration() === true)
        {
            $custom = $this->getCustomTemplateAndParamsForSubscriptionRegistration($merchant);
        }
        else
        {
            $custom = $this->getCustomRavenTemplateAndParams($merchant, $reminder, $newShortUrl);
        }

        $customTemplate = $custom['template'];
        $customParams   = $custom['params'];
        $customSender   = $custom['sender'];

        $request = [
            'receiver' => $contact,
            'source'   => $reminder ? "api.{$this->mode}.reminder_invoice" : "api.{$this->mode}.invoice",
            'template' => $customTemplate ?? $defaultTemplate,
            'params'   => $customParams ?? $defaultParams,
        ];

        $orgId = $merchant->getMerchantOrgId();

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        if (empty($orgId) === false)
        {
            $request['stork']['context']['org_id'] = $orgId;
        }

        if ($customSender !== null)
        {
            $request['sender'] = $customSender;
        }

        $this->trace->info(
            TraceCode::INVOICE_RAVEN_REQUEST,
            [
                'invoice_id' => $this->invoice->getId(),
                'request'    => $request,
            ]);

        return $request;
    }

    protected function getCustomRavenTemplateAndParams(Merchant\Entity $merchant, $reminder = false, $newShortUrl = null): array
    {
        $template = $params = $sender = null;

        $receipt = $this->invoice->getReceipt();

        $invoiceLink = $this->invoice->getShortUrl();

        try
        {
            $leastBucketAmount = $this->invoice->getNotes()['least_bucket_amount'] ?? 0;

            if (isset($leastBucketAmount) === false || is_numeric($leastBucketAmount) === false)
            {
                $leastBucketAmount = 0;
            }
        }
        catch (\Exception $e)
        {
            $leastBucketAmount = 0;
        }

        if(($reminder === true) and (empty($newShortUrl)) === false)
        {
            $invoiceLink = $newShortUrl;
        }

        $expireBy = $this->invoice->getExpireBy();

        if (empty($expireBy) === false)
        {
            $expireBy = Carbon::createFromTimestamp($expireBy, Timezone::IST)->format('d/m/Y');
        }

        switch ($merchant->getId())
        {
            case Preferences::MID_AMIT_RBLCARD:

                $template = 'sms.custom_invoice.rbl_card';
                $sender   = 'RBLCRD';
                $params   = [
                    'receipt'      => $receipt,
                    'invoice_link' => $invoiceLink,
                    'amount'       => $this->invoice->getAmount() / 100,
                ];

                break;

            case Preferences::MID_RBLLOAN:
            case Preferences::MID_DELINQUENT_LOANS:
            case Preferences::MID_AMIT_RBLLOAN:

                $template = 'sms.custom_invoice.rbl_loan';
                $sender   = 'RBLBNK';
                $params   = [
                    'receipt'      => $receipt,
                    'invoice_link' => $invoiceLink,
                    'amount'       => $this->invoice->getAmount() / 100,
                ];

                break;

            case Preferences::MID_RBLBFL:
                $template = 'sms.custom_invoice.rbl_bfl';
                $sender   = 'SPRCRD';
                $params   = [
                    'receipt'        => $receipt,
                    'invoice_link'   => $invoiceLink,
                    'amount'         => $this->invoice->getAmount() / 100,
                    'min_amount_due' => $leastBucketAmount / 100,
                ];

                break;

            case Preferences::MID_RBL_LENDING:
                $template = 'sms.custom_invoice.rbl_lending';
                $sender   = 'RBLBNK';
                $params = [
                    'receipt'                 => $receipt,
                    'invoice_link'            => $invoiceLink,
                ];

                break;

            case Preferences::MID_VARTHANA_FINANCE:

                $template = 'sms.custom_invoice.varthana_finance';
                $params = [
                    'invoice_link' => $invoiceLink,
                ];

                break;

            case Preferences::MID_APOLLO_MUNICH:
                $sender = 'HDFCHI';

                break;

            case Preferences::MID_SWIGGY_DROPPT:
                $sender = 'DROPPT';
                $template = 'sms.custom_invoice.swiggy_droppt';
                $params = [
                    'amount'       => $this->invoice->getAmount() / 100,
                    'invoice_link' => $invoiceLink,
                ];

                break;

            case Preferences::MID_SURYODAY_BANK:
                $sender = 'SSFBNK';
                $template = 'sms.custom_invoice.suryoday_bank';
                $params = [
                    'amount'       => $this->invoice->getAmount() / 100,
                    'invoice_link' => $invoiceLink,
                ];

                break;

            case Preferences::MID_INDIABULLS_FINANCE:
                $sender = 'IDHANI';
                $template = 'sms.custom_invoice.indiabull_custom';
                $params = [
                    'amount'        => $this->invoice->getAmount() / 100,
                    'invoice_link'  => $invoiceLink,
                    'notes_charges' => $this->invoice->getNotes()->charges ?? '',
                ];

                break;

            case Preferences::MID_RBL_PDD_BANK:
                $sender = 'RBLCRD';
                $template = 'sms.custom_invoice.rbl_pdd_bank';
                $params = [
                    'amount'         => $this->invoice->getAmount() / 100,
                    'invoice_link'   => $invoiceLink,
                    'receipt'        => $receipt,
                    'expiry_date'    => $expireBy ?? '',
                    'min_amount_due' => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                    'due_date'       => $this->invoice->getNotes()['due_date'] ?? '',
                ];

                break;

            case Preferences::MID_RBL_PDD_CREDIT:
                $sender = 'RBLCRD';
                $template = 'sms.custom_invoice.rbl_pdd_credit';
                $params = [
                    'amount'         => $this->invoice->getAmount() / 100,
                    'invoice_link'   => $invoiceLink,
                    'receipt'        => $receipt,
                    'expiry_date'    => $expireBy ?? '',
                    'min_amount_due' => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                ];

                break;

            case Preferences::MID_BFL_BANK:
                $sender = 'SPRCRD';
                $template = 'sms.custom_invoice.bfl_bank';
                $params = [
                    'amount'         => $this->invoice->getAmount() / 100,
                    'invoice_link'   => $invoiceLink,
                    'receipt'        => $receipt,
                    'min_amount_due' => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                ];

                break;

            case Preferences::MID_BFL_CARD:
                $sender = 'SPRCRD';
                $template = 'sms.custom_invoice.bfl_card';
                $params = [
                    'amount'         => $this->invoice->getAmount() / 100,
                    'invoice_link'   => $invoiceLink,
                    'receipt'        => $receipt,
                    'min_amount_due' => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                ];

                break;

            case Preferences::MID_RBL_LAPOD:
                $sender = 'RBLBNK';
                $template = 'sms.custom_invoice.rbl_lapod';
                $params = [
                    'amount'         => $this->invoice->getAmount() / 100,
                    'invoice_link'   => $invoiceLink,
                    'receipt'        => $receipt,
                    'expiry_date'    => $expireBy ?? '',
                ];

                break;

            case Preferences::MID_RBL_PL_NON_DEL_CUST:
                $sender = 'RBLBNK';
                $template = 'sms.custom_invoice.rbl_pl_non_del_cust';
                $params = [
                    'invoice_link'   => $invoiceLink,
                    'receipt'        => $receipt,
                    'expiry_date'    => $expireBy ?? '',
                    'min_amount_due' => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                ];

                break;

            case Preferences::MID_RBLCARD:
                $sender = 'RBLCRD';
                $template = 'sms.custom_invoice.rbl_card_del_coll';
                $params = [
                    'receipt'      			  => $receipt,
                    'invoice_link'   		  => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                    'expiry_date'             => $expireBy ?? '',
                    'min_amount_due'          => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                    'current_statement_date'  => $this->invoice->getNotes()['current_statement_date'] ?? '',
                ];

                break;

            case Preferences::MID_BOB:
                $sender = 'BOBFIN';
                $template = 'sms.custom_invoice.bob';
                $params = [
                    'receipt'       => $receipt,
                    'invoice_link'  => $invoiceLink,
                    'expiry_date'   => $expireBy ?? '',
                ];

                break;
            case Preferences::MID_BOB_2:
                $sender = 'BOBFIN';
                $template = 'sms.custom_invoice.bob_2';
                $params = [
                    'invoice_link' => $invoiceLink,
                ];

                break;
            case Preferences::MID_BOB_3:
                $sender = 'BOBFIN';
                $template = 'sms.custom_invoice.bob_3';
                $params = [
                    'receipt'       => $receipt,
                    'invoice_link' => $invoiceLink,
                ];

                break;
            case Preferences::MID_BAGIC:
                $sender = 'BAGICZ';
                $template = 'sms.custom_invoice.bagic_pl';
                $params = [
                    'invoice_link'  => $invoiceLink,
                ];

                break;
            case Preferences::MID_BAGIC_2:
                $sender = 'BJAZGI';
                $template = 'sms.custom_invoice.bagic_revised';
                $params = [
                    'first_name'    => $this->invoice->getCustomerName() ?? 'Customer',
                    'policy_number' => $this->invoice->getNotes()['policy_number'] ?? '',
                    'amount'        => $this->invoice->getAmount() / 100,
                    'lob'           => $this->invoice->getNotes()['lob'] ?? '',
                    'invoice_link'  => $invoiceLink,
                ];

                break;
            case Preferences::MID_RBL_AGRI_LOAN:
                $template = 'sms.custom_invoice.rbl_agri_loan';
                $sender   = 'RBLBNK';
                $params = [
                    'receipt'       => $receipt,
                    'amount'        => $this->invoice->getAmount() / 100,
                    'invoice_link'  => $invoiceLink,
                ];

                break;

            case Preferences::MID_RBL_HEMANT:
                $template = 'sms.custom_invoice.rbl_hemant';
                $sender   = 'RBLCRD';
                $params = [
                    'receipt'       	      => $receipt,
                    'invoice_link'            => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                    'expiry_date'             => $expireBy ?? '',
                    'min_amount_due'          => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                    'current_statement_date'  => $this->invoice->getNotes()['current_statement_date'] ?? '',
                ];

                break;

            case Preferences::MID_RBL_BANK:
                $template = 'sms.custom_invoice.rbl_bank';
                $sender   = 'RBLCRD';
                $params = [
                    'receipt'       	      => $receipt,
                    'invoice_link'            => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                    'min_amount_due'          => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                    'due_date'                => $this->invoice->getNotes()['due_date'] ?? '',
                ];

                break;

            case Preferences::MID_RBL_BANK_LTD:
                $template = 'sms.custom_invoice.rbl_bank_ltd';
                $sender   = 'RBLCRD';
                $params = [
                    'receipt'       	      => $receipt,
                    'invoice_link'            => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                    'min_amount_due'          => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                ];

                break;

            case Preferences::MID_RBL_BANK_1:
                $template = 'sms.custom_invoice.rbl_bank_1';
                $sender   = 'RBLCRD';
                $params = [
                    'receipt'       	      => $receipt,
                    'invoice_link'            => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                    'min_amount_due'          => ($this->invoice->getFirstPaymentMinAmount() ?? 0) / 100,
                    'due_date'                => $this->invoice->getNotes()['due_date'] ?? '',
                ];

                break;

            case Preferences::MID_RBL_BANK_2:
                $template = 'sms.custom_invoice.rbl_bank_2';
                $sender   = 'RBLBNK';
                $params = [
                    'receipt'       	      => $receipt,
                    'invoice_link'            => $invoiceLink,
                ];

                break;

            case Preferences::MID_STASHFIN:
                $template = 'sms.custom_invoice.stashfin_eqx';
                $params = [
                    'invoice_link'            => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                ];

                break;

            case Preferences::MID_CLIX_CAPITAL:
            case Preferences::MID_CLIX_CAPITAL_SERVICES:
            case Preferences::MID_CLIX_CAPITAL_SERVICES_1:
            case Preferences::MID_CLIX_CAPITAL_SERVICES_2:
            case Preferences::MID_CLIX_HOUSING_FINANCE:
            case Preferences::MID_CLIX_FINANCE:
            case Preferences::MID_CLIX_FINANCE_1:
                $template = 'sms.custom_invoice.clix_capital';
                $sender   = 'RZRPAY';
                $params = [
                    'invoice_link'            => $invoiceLink,
                    'amount'                  => $this->invoice->getAmount() / 100,
                    'billing_label'           => $this->invoice->merchant->getBillingLabel(),
                ];

                break;

            case Preferences::MID_LENDING_KART:
                $sender = 'LDKART';

                break;

            case Preferences::MID_BFL:
                $sender = 'SPRCRD';

                break;

        }

        // TODO: Make this generic later. Keep a list of requiredParams[] and trace/fail if those params are not set
        if ($params !== null and $receipt === null)
        {
            $this->trace->info(
                TraceCode::INVOICE_SMS_CUSTOM_PARAMETER_NOT_SET,
                [
                    'parameter' => Entity::RECEIPT,
                ]);
        }

        return ['template' => $template, 'params' => $params, 'sender' => $sender];
    }

    protected function getCustomTemplateAndParamsForSubscriptionRegistration(Merchant\Entity $merchant): array
    {
        $template = $params = $sender = null;

        $receipt = $this->invoice->getReceipt();

        if (($merchant->getId() === Preferences::MID_RBL_RETAIL_ASSETS) or
            ($merchant->getId() === Preferences::MID_RBL_INTERIM_PROCESS2) or
            ($merchant->getId() === Preferences::MID_RBL_RETAIL_CUSTOMER) or
            ($merchant->getId() === Preferences::MID_RBL_RETAIL_PRODUCT))
        {
            $receipt = $this->invoice->getNotes()['loan_number'] ?? $receipt;
        }

        $merchantName = $merchant->getBillingLabel();

        $merchantName = substr($merchantName, 0, 30);

        $params   = [
            'merchant_name' => $merchantName,
            'invoice_link'  => $this->invoice->getShortUrl(),
            'amount'        => $this->invoice->getAmount() / 100,
        ];

        $subscriptionRegistration = $this->invoice->entity;

        if ($subscriptionRegistration->isMethodCard() === true)
        {
            $template = 'sms.custom_invoice.subr_card';
        }

        if ($subscriptionRegistration->isMethodEmandate() === true)
        {
            $template = 'sms.custom_invoice.subr_emandate';
        }

        if ($subscriptionRegistration->isMethodNach() === true)
        {
            $template = 'sms.custom_invoice.subr_emandate';
        }

        $invoiceLink = $this->invoice->getShortUrl();

        $notes = $this->invoice->getNotes();

        switch ($merchant->getId())
        {
            case Preferences::MID_DMI_FINANCE:

                $template = 'sms.custom_invoice.dmi_finance';

                $params   = [
                    'receipt'      => $receipt,
                    'invoice_link' => $invoiceLink,
                ];

                break;

            case Preferences::MID_INDIABULLS_FINANCE:

                $sender = 'IDHANI';
                $template = 'sms.custom_invoice.indiabulls_finance';
                $params   = [
                    'receipt'      => $receipt,
                    'invoice_link' => $invoiceLink,
                ];

                break;

            case Preferences::MID_RBL_RETAIL_ASSETS:

                $template = 'sms.custom_invoice.rbl_retail_assets';

                $sender = 'RBLBNK';

                $params   = [
                    'customer_name'    => (empty($this->invoice->getCustomerName()) === false) ? $this->invoice->getCustomerName() : 'Customer',
                    'receipt'          => $receipt ?? '',
                    'invoice_link'     => $invoiceLink,
                ];

                break;

            case Preferences::MID_RBL_RETAIL_CUSTOMER:

                $template = 'sms.custom_invoice.rbl_retail_customer';

                $sender = 'RBLBNK';

                $params   = [
                    'receipt'          => $receipt,
                    'invoice_link'     => $invoiceLink,
                ];

                break;

            case Preferences::MID_RBL_RETAIL_PRODUCT:

                $template = 'sms.custom_invoice.rbl_retail_product';

                $sender = 'RBLBNK';

                $params   = [
                    'receipt'          => $receipt,
                    'invoice_link'     => $invoiceLink,
                ];

                break;

            case Preferences::MID_ICICI_PRUDENTIAL:

                $template = 'sms.custom_invoice.icici_prudential';

                $sender = 'ICICIP';

                $params   = [
                    'invoice_link'     => $invoiceLink,
                ];

                break;

            case Preferences::MID_BAGIC:

                $template = 'sms.custom_invoice.bagic_sub';

                $sender = 'BAGICZ';

                $params = [
                    'invoice_link'    => $invoiceLink
                ];

                break;

            case Preferences::MID_BOB_FIN:

                $template = 'sms.custom_invoice.bobfin';

                $sender = 'BOBFIN';

                $params = [
                    'invoice_link'    => $invoiceLink,
                    'card_last_four'  => $this->invoice->getDescription(),
                ];

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS2:

                $subscriptionRegistration = $this->invoice->entity;

                if ($subscriptionRegistration->isMethodCard() === true)
                {
                    $template = 'sms.custom_invoice.subr_card';

                    $merchantName = $merchant->getBillingLabel();

                    $merchantName = substr($merchantName, 0, 30);

                    $params   = [
                        'merchant_name' => $merchantName,
                        'invoice_link'  => $this->invoice->getShortUrl(),
                        'amount'        => $this->invoice->getAmount() / 100,
                    ];
                }

                if ($subscriptionRegistration->isMethodEmandate() === true)
                {
                    $sender = 'RBLBNK';

                    $template = 'sms.custom_invoice.rbl_interim_process2';

                    $params = [
                        'receipt'           => $receipt,
                        'invoice_link'      => $invoiceLink,
                    ];
                }

                break;

            case Preferences::MID_LENDING_KART:

                $sender = 'LDKART';

                break;

            case Preferences::MID_EDELWEISS_ECL:

                $sender = 'EDELHS';

                if ($subscriptionRegistration->isMethodEmandate() === true or
                    $subscriptionRegistration->isMethodNach() === true)
                {
                    $template = 'sms.custom_invoice.ecl';

                    $params = [
                        'invoice_link'      => $invoiceLink,
                    ];
                }

                break;

            case Preferences::MID_EDELWEISS_EHFL:

                $sender = 'EDELHS';

                if ($subscriptionRegistration->isMethodEmandate() === true or
                    $subscriptionRegistration->isMethodNach() === true)
                {
                    $template = 'sms.custom_invoice.ehfl';

                    $params = [
                        'invoice_link'      => $invoiceLink,
                    ];
                }

                break;

            case Preferences::MID_EDELWEISS_ERFL:

                $sender = 'EDELHS';

                if ($subscriptionRegistration->isMethodEmandate() === true or
                    $subscriptionRegistration->isMethodNach() === true)
                {
                    $template = 'sms.custom_invoice.erfl';

                    $params = [
                        'invoice_link'      => $invoiceLink,
                    ];
                }

                break;

            case Preferences::MID_ADITYA_BIRLA_HEALTH:
            case Preferences::MID_ADITYA_BIRLA_HEALTH_1:
            case Preferences::MID_ADITYA_BIRLA_HEALTH_2:
            case Preferences::MID_ADITYA_BIRLA_HEALTH_3:
            case Preferences::MID_ADITYA_BIRLA_HEALTH_4:
            case Preferences::MID_ADITYA_BIRLA_HEALTH_5:

                $sender = 'ABCABH';
                $template = 'sms.custom_invoice.adityabirla_health';
                $params   = [
                    'first_name'    => $this->invoice->getCustomerName() ?? 'Customer',
                    'policy_number' => $receipt,
                    'invoice_link'  => $invoiceLink,
                ];

                break;
        }

        return ['template' => $template, 'params' => $params, 'sender' => $sender];
    }

    public function emailInvoiceIssuedToMerchant(): bool
    {
        $merchant = $this->invoice->merchant;

        $merchantEmails = $merchant->getTransactionReportEmail();

        $this->trace->info(
            TraceCode::INVOICE_MERCHANT_EMAIL_ISSUED_REQUEST,
            [
                'invoice_id'     => $this->invoice->getId(),
                'merchant_email' => $merchantEmails,
            ]);

        if (empty($merchantEmails) === true)
        {
            return false;
        }

        $viewPayload = (new ViewDataSerializer($this->invoice))->serializeForInternal();

        $viewPayload['to'] = $merchantEmails;

        if ($this->invoice->isPaymentPageInvoice() === true)
        {
            $viewPayload['pp_invoice'] = true;
        }

        $fileData = [
            'name' => $this->invoice->getPdfDisplayName(),
            'path' => $this->issuedPdfPath,
        ];

        $invoiceIssuedMail = new InvoiceMail\MerchantIssued($viewPayload, $fileData);

        Mail::send($invoiceIssuedMail);

        return true;
    }

    private function addEmailSpecificParams($viewPayload): array
    {
        if (isset($viewPayload['invoice']['expire_by']) === true)
        {
            $viewPayload['invoice']['expire_by_formatted'] = Utility::getTimestampFormattedByTimeZone(
                $viewPayload['invoice']['expire_by'],
                'jS M, Y', $this->invoice->merchant->getTimeZone());
        }

        $viewPayload['org'] = $this->invoice->merchant->org->toArrayPublic();

        $org = $this->invoice->merchant->org;

        $branding = [
            'show_rzp_logo' => true,
            'branding_logo' => '',
        ];

        if($this->invoice->merchant->shouldShowCustomOrgBranding() === true)
        {
            if(ORG_ENTITY::isOrgCurlec($org->getId()) === true){
                $branding = array_merge((new Core)->getCurlecBrandingConfig(), $branding);
            }else{
                $branding['show_rzp_logo'] = false;
            }
            $branding['branding_logo'] = $org->getInvoiceLogo() ?: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.svg';
        }

        $viewPayload['org']['branding'] = $branding;

        $viewPayload['invoice']['amount_spread'] = $this->invoice->getAmountComponents();

        if ($viewPayload['invoice']['partial_payment'] === true)
        {
            $invoice = $viewPayload['invoice'];

            $currency = $invoice['currency'];

            $amountDue = $invoice['amount_due'];

            $amountPaid = $invoice['amount_paid'];

            $amountPaidComponents = Utility::getAmountComponents($amountPaid, $currency);

            $amountDueComponents = Utility::getAmountComponents($amountDue, $currency);

            $viewPayload['invoice']['amount_due_spread'] = $amountDueComponents;

            $viewPayload['invoice']['amount_paid_spread'] = $amountPaidComponents;
        }

        return $viewPayload;
    }
}
