<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers;
use RZP\Exception\LogicException;

class PaymentLink extends Base
{
    /**
     * @var Invoice\Core
     */
    protected $invoiceCore;

    /**
     * Todo: Fix this hack! Remove this temp variable and logic around it with something better.
     * @var bool
     */
    protected $usesNewPlHeader = false;

    protected $defaultEntries = [
        [
            'Invoice Number'    => '',
            'Customer Name'     => 'Testing',
            'Customer Email'    => 'testing@razorpay.com',
            'Customer Contact'  => '',
            'Amount (In Paise)' => '',
            'Description'       => '',
            'Expire By'         => '',
            'Partial Payment'   => '',
        ]
    ];

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->increaseAllowedSystemLimits();

        $this->invoiceCore = new Invoice\Core;
    }

    protected function processEntry(array & $entry)
    {
        $settings = $this->settingsAccessor->all()->toArray();

        $input = Helpers\PaymentLink::getEntityInput($entry, array_merge($this->params, $settings));

        $invoice = $this->invoiceCore->create($input, $this->merchant, null, $this->batch);

        // Update the entry with output values

        $entry[Header::STATUS]              = $invoice->getStatus();
        $entry[Header::PAYMENT_LINK_ID]     = $invoice->getPublicId();
        $entry[Header::SHORT_URL]           = $invoice->getShortUrl();
    }

    /**
     * Overrides: We don't set amount aggregate as it crosses MySQL limit
     * in case of batch payment link inputs.
     *
     * @param array $entries
     */
    protected function updateBatchPostValidation(array $entries, array $input)
    {
        $totalCount  = count($entries);

        $this->batch->setTotalCount($totalCount);
    }

    //
    // Todo: Fix this hack!
    // Following methods of base are being overridden to support a new header value
    // in backward compatible way for payment link type batch.
    // We need to come up with better approach, product & implementation is being discussed.
    //

    public function getHeadings(): array
    {
        return $this->updateHeaderValuesIfApplies(parent::getHeadings());
    }

    public function getOutputFileHeadings(): array
    {
        return $this->updateHeaderValuesIfApplies(parent::getOutputFileHeadings());
    }

    /**
     * {@inheritDoc}
     */
    protected function parseFileAndCleanEntries(string $filePath): array
    {
        $entries = parent::parseFileAndCleanEntries($filePath);

        $this->setUsesNewPlHeaderFlagIfApplicable(array_keys(current($entries) ?: []));

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    protected function parseTextFile(string $file, string $delimiter = '~')
    {
        //
        // CSV optionally can contain header. When it does we need to set proper header version so that the read
        // associative array is proper. Here, reads the first line to set the header version.
        //
        $this->setUsesNewPlHeaderFlagIfApplicable(explode($delimiter, trim(fgets(fopen($file, 'r')))));

        return parent::parseTextFile($file, $delimiter);
    }

    /**
     * If headings has a new specific value, sets a flag to be used later in below method.
     * @param bool|array $headings
     */
    protected function setUsesNewPlHeaderFlagIfApplicable($headings)
    {
        if (empty($headings) === true)
        {
            return;
        }

        if (in_array(Header::AMOUNT_IN_PAISE, $headings, true) === true)
        {
            $this->usesNewPlHeader = true;
        }
    }

    /**
     * Update the existing header values for specific type in specific case (when a flag is set).
     * @param  array $headings
     * @return array
     */
    protected function updateHeaderValuesIfApplies(array $headings)
    {
        if ($this->usesNewPlHeader === true)
        {
            return array_replace($headings, [4 => Header::AMOUNT_IN_PAISE]);
        }

        return $headings;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(3600);

    }

    protected function updateBatchHeadersIfApplicable(array &$headers, array $entries)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::PL_FIRST_MIN_AMOUNT) === true)
        {
            $headers[] = Batch\Header::FIRST_PAYMENT_MIN_AMOUNT;
        }

        $entry = current($entries);

        if ((empty($entry) === false) and (array_key_exists(Batch\Header::CURRENCY, $entry) === true))
        {
            // Inserting just before amount in paise thingy
            array_splice($headers, 4, 0, Batch\Header::CURRENCY);
        }
    }

    protected function validateInputFileEntries(array $input): array
    {
        if ($this->shouldSkipValidateInputFile())
        {
            //
            // Skips the validation
            // Return the default Entries
            // as it will be used for preview
            //
            $entries = $this->defaultEntries;
        }
        else
        {
            $entries = $this->parseFileAndCleanEntries($this->inputFileLocalPath);

            $this->validateEntries($entries, $input);
        }

        return $entries;
    }
}
