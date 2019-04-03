<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers;
use RZP\Models\Batch\Constants;
use RZP\Models\SubscriptionRegistration;

class AuthLink extends Base
{
    /**
     * @var SubscriptionRegistration\Core
     */
    protected $subrCore;

    protected $conversionMap = [
        Header::AUTH_LINK_CURRENCY      =>  Constants::TO_UPPER_CASE,
        Header::AUTH_LINK_METHOD        =>  Constants::TO_LOWER_CASE,
        Header::AUTH_LINK_AUTH_TYPE     =>  Constants::TO_LOWER_CASE,
        Header::AUTH_LINK_BANK          =>  Constants::TO_UPPER_CASE,
        Header::AUTH_LINK_IFSC          =>  Constants::TO_UPPER_CASE,
        Header::AUTH_LINK_ACCOUNT_TYPE  =>  Constants::TO_LOWER_CASE,
    ];

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->subrCore = new SubscriptionRegistration\Core;

    }

    protected function processEntry(array & $entry)
    {
        $this->trimEntry($entry);

        $this->processConvertCase($entry, $this->conversionMap);

        try {
            $this->invoice = $this->createAuthLink($entry);
        }
        finally {
            $this->processDatesForExcel($entry);
        }

        $entry[Header::STATUS]                  = Status::SUCCESS;

        $entry[Header::AUTH_LINK_ID]            = $this->invoice->getPublicId();

        $entry[Header::AUTH_LINK_SHORT_URL]     = $this->invoice->getShortUrl();

        $entry[HEADER::AUTH_LINK_STATUS]        = $this->invoice->getStatus();

        $entry[HEADER::AUTH_LINK_CREATED_AT]    = $this->invoice->getCreatedAt();


    }

    protected function createAuthLink(array & $entry) : Invoice\Entity
    {
        $settings = $this->settingsAccessor->all()->toArray();

        $input = Helpers\AuthLink::getAuthLinkInput($entry, $settings);

        $this->trace->info(
            TraceCode::AUTH_LINK_BATCH_INPUT,
            [
                'input'         => $input,
                'batch_entry'   => $entry
            ]
        );

        $invoice = $this->subrCore->createAuthLink($input, $this->merchant, $this->batch);

        return $invoice;
    }

    protected function processDatesForExcel(array & $entry)
    {
        $authLinkTokenExpiry = $entry[Header::AUTH_LINK_TOKEN_EXPIRE_BY];

        if (is_numeric($authLinkTokenExpiry) === true) {

            $authLinkTokenExpiry = Helpers\AuthLink::fromExcelToEpoch($authLinkTokenExpiry);

            $entry[Header::AUTH_LINK_TOKEN_EXPIRE_BY] = date('d/m/Y', $authLinkTokenExpiry);
        }

        $authLinkExpiry = $entry[Header::AUTH_LINK_EXPIRE_BY];

        if (is_numeric($authLinkExpiry) === true) {

            $authLinkExpiry = Helpers\AuthLink::fromExcelToEpoch($authLinkExpiry);

            $entry[Header::AUTH_LINK_EXPIRE_BY] = date('d/m/Y', $authLinkExpiry);
        }
    }
}
