<?php

namespace RZP\Reconciliator\Base;

use DB;
use App;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Messenger;

class Reconciliate
{
    /***********************
     * Reconciliation Types
     ***********************/

    const NODAL    = 'nodal';
    const PAYMENT  = 'payment';
    const REFUND   = 'refund';
    const COMBINED = 'combined';

    const VALID_RECON_TYPES = [self::NODAL, self::PAYMENT, self::REFUND, self::COMBINED];

    //
    // Used to define start_row for the MIS files.
    // Some of them have some random crap at the start of the file.
    //
    const DEFAULT_START_ROW = 1;

    /*************************
     * Internal Header Names
     *************************/

    const PAYMENT_ID            = 'payment_id';
    const REFUND_ID             = 'refund_id';
    const CARD_TYPE             = 'card_type';
    const CARD_LOCALE           = 'card_locale';
    const CARD_TRIVIA           = 'card_trivia';
    const CARD_DETAILS          = 'card_details';
    const GATEWAY_SERVICE_TAX   = 'gateway_service_tax';
    const GATEWAY_FEE           = 'gateway_fee';
    const GATEWAY_SETTLED_AT    = 'gateway_settled_at';
    const ISSUER                = 'issuer';
    const REFERENCE_NUMBER      = 'reference_number';
    const CUSTOMER_DETAILS      = 'customer_details';
    const CUSTOMER_ID           = 'customer_id';
    const CUSTOMER_NAME         = 'customer_name';
    const GATEWAY_PAYMENT_DATE  = 'gateway_payment_date';
    const ARN                   = 'arn';
    const ACCOUNT_DETAILS       = 'account_details';
    const ACCOUNT_NUMBER        = 'account_number';
    const ACCOUNT_TYPE          = 'account_type';
    const ACCOUNT_SUBTYPE       = 'account_subtype';
    const ACCOUNT_BRANCHCODE    = 'account_branchcode';
    const CREDIT_ACCOUNT_NUMBER = 'credit_account_number';
    const AUTH_CODE             = 'auth_code';

    /*************************
     * Card types
     *************************/

    const CREDIT        = 'credit';
    const DEBIT         = 'debit';
    const DOMESTIC      = 'domestic';
    const INTERNATIONAL = 'international';

    /*********************
     * Instance objects
     *********************/

    protected $subReconciliator;
    protected $messenger;
    protected $app;
    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->messenger = new Messenger;
    }

    /**
     * This is the start of the reconciliation. This is executed from the orchestrator.
     * For each file, it figures out which type of reconciliation is it (nodal, payment, refund, combined)
     * and calls the startReconciliation of the respective reconciliation type.
     *
     * @param array $allFilesContents
     * @return array $allSummaries Returns the summary of each file that has been reconciled.
     */
    public function startReconciliation(array $allFilesContents)
    {
        $allSummaries = [];

        foreach ($allFilesContents as $fileContents)
        {
            $reconciliationType = $this->getReconciliationType($fileContents[Orchestrator::EXTRA_DETAILS]);

            // If unable to get the reconciliation type, just continue on to the next file.
            // An alert is raised in the function getReconciliationType in case of this.
            if ($reconciliationType === null)
            {
                continue;
            }

            $this->setSubReconciliator($reconciliationType);

            $summary = $this->subReconciliator->startReconciliation($fileContents);

            $allSummaries[] = $summary;
        }

        $this->app['trace']->info(
            TraceCode::RECON_INFO_SUMMARY,
            $allSummaries
        );

        return $allSummaries;
    }

    /**
     * Processing reconciliation via batch
     */
    public function startReconciliationV2(array $allFilesContents, Batch\Entity $batch)
    {
        foreach ($allFilesContents as $fileContents)
        {
            $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];

            $reconciliationType = $this->getReconciliationType($fileContents[Orchestrator::EXTRA_DETAILS]);

            // If unable to get the reconciliation type, just continue on to the next file.
            // An alert is raised in the function getReconciliationType in case of this.
            if ($reconciliationType === null)
            {
                continue;
            }

            $this->updateBatchWithReconCiliationType($batch, $reconciliationType, $extraDetails);

            $this->setSubReconciliator($reconciliationType);

            $this->subReconciliator->startReconciliationV2($fileContents, $batch);
        }
    }

    /**
     * This should be implemented in the child class if the gateway needs to
     * look at only certain sheets present in the excel file and not all of them.
     */
    public function getSheetNames()
    {
        return null;
    }

    /**
     * This should be implemented in the child class if the gateway requires certain
     * files to be excluded from doing the reconciliation.
     *
     * @param array $fileDetails
     * @return bool
     */
    public function inExcludeList(array $fileDetails)
    {
        return false;
    }

    /**
     * This should be implemented in the child class if the gateway sends zip files
     * which are password protected.
     *
     * @param array $fileDetails
     * @return null
     */
    public function getReconPassword($fileDetails)
    {
        return null;
    }

    public function shouldUse7z($zipFileDetails)
    {
        return false;
    }

    public function getStartRow($fileDetails)
    {
        return self::DEFAULT_START_ROW;
    }

    /**
     * This should be implemented in the child class if the gateway
     * sends CSV files which has a delimiter other than `,`
     *
     * @return string
     */
    public function getDelimiter()
    {
        return ',';
    }

    /**
     * Gets the reconciliation type by either the sheet name in case of excel files
     * or by the file name in case of csv files.
     *
     * @param $extraDetails
     * @return mixed
     */
    protected function getReconciliationType($extraDetails)
    {
        $fileName = $this->getFileName($extraDetails);

        $fileName = strtolower($fileName);

        // The method is present in child class since different gateways have
        // different sheet names/file names for reconciliation types.
        $reconciliationType = $this->getTypeName($fileName);

        // Ideally, should never come here.
        if ((in_array($reconciliationType, self::VALID_RECON_TYPES, true) === false))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_PARSE_ERROR,
                    'message' => 'Unable to figure out the reconciliation type. Skipping this file.',
                    'reconciliation_type' => $reconciliationType,
                    'extra_details' => $extraDetails,
                    'gateway' => get_called_class()
                ]);

            return null;
        }

        return $reconciliationType;
    }

    /**
     * Sets the subtype for the batch based on the reconciliation sub type. However if
     * the file is an excel, we always set the sub_type to combined, as excel can have
     * multiple sheets for payment / refund recon etc, however we process it in a single
     * batch. Later we can do something like split different sheets into separate files.
     * TBD - if this is alright
     *
     * @param  Batch\Entity $batch              Batch entity for recon
     * @param  string       $reconciliationType Recon type determined for the file
     * @param  array        $extraDetails       Extra file metadata
     */
    protected function updateBatchWithReconCiliationType(
                            Batch\Entity $batch,
                            string $reconciliationType,
                            array $extraDetails)
    {
        if ($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_TYPE] === FileProcessor::EXCEL)
        {
            $batch->setSubType(self::COMBINED);
        }
        else
        {
            $batch->setSubType($reconciliationType);
        }
    }

    protected function getFileName(array $fileDetails): string
    {
        if (isset($fileDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME]) === true)
        {
            return $fileDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME];
        }

        return $fileDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    public function getReconciliationTypeFromFileName($fileName)
    {
        $fileName = strtolower($fileName);

        return $this->getTypeName($fileName);
    }

    protected function setSubReconciliator($reconciliationType)
    {
        $subReconciliatorClassName = $this->getSubReconciliatorClassName($reconciliationType);

        $this->subReconciliator = new $subReconciliatorClassName;
    }

    protected function getSubReconciliatorClassName($reconciliationType)
    {
        // Parent namespace should be something like - Reconciliator/Axis
        $parentNamespace = $this->getParentNamespace();

        // SubReconciliator class name should be something like - Reconciliator/Axis/PaymentReconciliate
        $subReconciliatorClassName = $parentNamespace . '\\'
                                    . ucfirst($reconciliationType)
                                    . 'Reconciliate';

        return $subReconciliatorClassName;
    }

    protected function getParentNamespace()
    {
        // Gets the namespace from the called class, by removing the last part of the FQCN.
        return join('\\', explode('\\', get_called_class(), -1));
    }

    public function getColumnHeadersForType($type)
    {
        return [];
    }

    /**
     * This method returns the number of lines to be skipped from end or from
     * beginning while reading csv files. Should be overriden by any gateway
     * specific child classes.
     *
     * @param array $fileDetails
     *
     * @return int number of lines to skip from end
     */
    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 0,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}
