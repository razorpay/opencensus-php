<?php

namespace RZP\Models\Reminders;

use RZP\Exception;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice\Entity;
use RZP\Models\Invoice\Status;
use RZP\Exception\BadRequestException;

class InvoiceReminderProcessor extends ReminderProcessor
{
    protected $baseInvoiceUrl;

    protected $elfin;

    protected $paymentlinkservice;

    public function __construct()
    {
        parent::__construct();

        $this->baseInvoiceUrl = $this->app['config']->get('app.invoice');

        $this->elfin = $this->app['elfin'];

        $this->paymentlinkservice = $this->app['paymentlinkservice'];
    }

    public function process(string $entity, string $namespace, string $id, array $input)
    {
        $invoice = null;

        try {
            $invoice = $this->repo->$entity->findOrFail($id);
        }
        catch(\Throwable $e)
        {
            // sending request to pl service since, id cannot be found here
            $response =  $this->paymentlinkservice->sendRequest($this->app->request);

            if ($response['status_code'] === 200)
            {
                return $response['response'];
            }

            $this->handleInvalidReminder();
        }

        $reminderEntity = $this->repo->invoice_reminder->getByInvoiceId($invoice->getId());

        $this->validate($invoice, $input, $reminderEntity);

        $reminderCount = $input['reminder_count'];

        $channels = $input['channels'];

        $notifier = new Invoice\Notifier($invoice);

        $shortUrl = $this->getShortUrl($invoice, $reminderCount);

        $emailStatus = false;

        $smsStatus = false;

        if(in_array('email', $channels))
        {
            $emailStatus = $notifier->emailInvoiceIssuedToCustomer(true, $shortUrl);
        }

        if(in_array('sms', $channels))
        {
            $smsStatus = $notifier->smsInvoiceIssuedToCustomer(true, $shortUrl);
        }

        if(($emailStatus !== true) and ($smsStatus != true))
        {
            $this->handleInvalidReminder();
        }
        return ['success' => true];
    }

    public function nextRunAt($entity, $id)
    {
        $merchant = $this->auth->getMerchant();

        $invoice = $this->repo->$entity->findByPublicIdAndMerchantAndUser($id, $merchant);

        $reminderEntity = $this->repo->invoice_reminder->getByInvoiceId($invoice->getId());

        $namespace = $this->getNamespace($invoice);

        $input = $this->getNextRunAtInput($invoice, $namespace, $reminderEntity);

        $response = $this->reminders->nextRunAt($input);

        return $response;
    }

    protected function getNamespace(Entity $invoice)
    {
        $type = $invoice->getType();

        if($type === 'link')
        {
            return 'payment_link';
        }
        return $type;
    }

    protected function getNextRunAtInput(Entity $invoice,
                                         string $namespace,
                                         $reminderEntity = null)
    {
        $input = [
            'issued_at' => $invoice->getIssuedAt(),
            'namespace' => $namespace
        ];

        $expireBy = $invoice->getExpireBy();

        if(empty($expireBy) === false)
        {
            $input['expire_by'] = $expireBy;

            unset($input['issued_at']);
        }

        $reminderID = optional($reminderEntity)->getReminderId();

        if(empty($reminderID) === false)
        {
            $input['reminder_id'] = $reminderID;
        }

        return $input;
    }

    protected function validate($invoice, $input, Invoice\Reminder\Entity $reminderEntity)
    {
        if($invoice->getStatus() !== Status::ISSUED and $invoice->getStatus() !== Status::PARTIALLY_PAID)
        {
            $this->handleInvalidReminder();
        }

        if($reminderEntity->getReminderStatus() !== Invoice\Reminder\Status::IN_PROGRESS)
        {
            $this->handleInvalidReminder();
        }

        $this->validateInput($input);

        if(($this->validateEmail($invoice) === false ) and
            ($this->validateCustomerContact($invoice) === false))
        {
            $this->handleInvalidReminder();
        }

    }

    protected function validateInput ($input)
    {
        if(empty($input['reminder_count']) or empty($input['channels']))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }
    }

    protected function validateEmail(Entity $invoice)
    {
        if(empty($invoice->getCustomerEmail()) === true)
        {
            return false;
        }
        return true;
    }

    protected function validateCustomerContact(Entity $invoice)
    {
        if(empty($invoice->getCustomerContact()) === true)
        {
            return false;
        }
        return true;
    }

    protected function getShortUrl(Entity $invoice, int $reminderCount)
    {

        $longUrl = (new Invoice\Generator($invoice->merchant, $invoice))->getInvoiceLink();

        $longUrl = $longUrl . '?utm_reminder=' . $reminderCount;

        $shortenedUrl = $this->elfin->shorten($longUrl);

        $this->trace->info(
            TraceCode::INVOICE_LINKS,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'short_url'      => $shortenedUrl,
                'long_url'       => $longUrl,
            ]);

        return $shortenedUrl;
    }

}
