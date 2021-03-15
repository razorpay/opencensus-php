<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers;
use RZP\Models\Batch\Constants;
use RZP\Models\SubscriptionRegistration;

class AuthLink extends Base
{
    const AMOUNT_AS_RUPEE_CONFIG = 'amount_as_rupee';

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

    public function storeAndValidateInputFile(array $input): array
    {
        $precision = ini_get('precision');

        ini_set('precision', 15);

        $response = parent::storeAndValidateInputFile($input);

        ini_set('precision', $precision);

        return $response;
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

        $tokenRegistration = $this->invoice->tokenRegistration;
        if (($tokenRegistration !== null) and
            ($tokenRegistration->getMethod() === SubscriptionRegistration\Method::NACH))
        {
            $paperMandate = $tokenRegistration->paperMandate;

            if ($paperMandate !== null)
            {
                $entry[Header::AUTH_LINK_NACH_PRI_FILLED_FORM] = $paperMandate->getGeneratedFormUrl($this->invoice);
            }
        }
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

    public function addSettingsIfRequired(& $input)
    {
        $batchType = $this->batch->getType();

        $batchSetting = Settings\Accessor::for($this->merchant, Settings\Module::BATCH)
            ->get($batchType);

        if (empty($batchSetting))
        {
            return;
        }

        if ((isset($batchSetting[self::AMOUNT_AS_RUPEE_CONFIG]) === true) and
            ($batchSetting[self::AMOUNT_AS_RUPEE_CONFIG] === '1')) {

            $config = [];

            if (isset($input["config"]) === true) {
                $config = $input["config"];
            }

            $config[self::AMOUNT_AS_RUPEE_CONFIG] = true;

            $input["config"] = $config;
        }
    }

    protected function updateBatchHeadersIfApplicable(array &$headers, array $entries)
    {
        $entry = current($entries);

        if (empty($entry) === false)
        {
            if (array_key_exists(Header::AUTH_LINK_NACH_REFERENCE1, $entry) === true)
            {
                array_splice($headers, count($headers), 0, Header::AUTH_LINK_NACH_REFERENCE1);
            }

            if (array_key_exists(Header::AUTH_LINK_NACH_REFERENCE2, $entry) === true)
            {
                array_splice($headers, count($headers), 0, Header::AUTH_LINK_NACH_REFERENCE2);
            }

            if (array_key_exists(Header::AUTH_LINK_NACH_CREATE_FORM, $entry) === true)
            {
                array_splice($headers, count($headers), 0, Header::AUTH_LINK_NACH_CREATE_FORM);
            }
        }
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Header::AUTH_LINK_ACCOUNT_NUMBER]);
    }
}
