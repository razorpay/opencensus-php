<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers;
use RZP\Models\SubscriptionRegistration;

class AuthLink extends Base
{
    /**
     * @var SubscriptionRegistration\Core
     */
    protected $subrCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->subrCore = new SubscriptionRegistration\Core;

    }

    protected function processEntry(array & $entry)
    {
        $this->invoice = $this->createAuthLink($entry);

        $entry[Header::STATUS]                  = Status::SUCCESS;

        $entry[Header::AUTH_LINK_ID]            = $this->invoice->getPublicId();

        $entry[Header::AUTH_LINK_SHORT_URL]     = $this->invoice->getShortUrl();

        $entry[HEADER::AUTH_LINK_STATUS]        = $this->invoice->getStatus();

        $entry[HEADER::AUTH_LINK_MAIL_SENT]     = $this->invoice->getEmailStatus();

        $entry[HEADER::AUTH_LINK_SMS_SENT]      = $this->invoice->getSmsStatus();

        $entry[HEADER::AUTH_LINK_CREATED_AT]    = $this->invoice->getCreatedAt();
    }

    protected function createAuthLink(array &$entry)
    {
        $settings = $this->settingsAccessor->all()->toArray();

        $input = Helpers\AuthLink::getInvoiceEntityInput($entry, array_merge($this->params, $settings));

        $invoice = $this->subrCore->createAuthLink($input, $this->merchant, $this->batch);

        return $invoice;
    }
}