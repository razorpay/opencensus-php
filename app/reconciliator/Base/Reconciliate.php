<?php

namespace Reconciliator\Base;

use Reconciliator\FileProcessor;
use Reconciliator\Orchestrator;
use Reconciliator\Messenger;

use EE\Exception;
use Trace\TraceCode;
use DB;

class Reconciliate
{
    /***********************
     * Reconciliation Types
     ***********************/

    const NODAL    = 'nodal';
    const PAYMENT  = 'payment';
    const REFUND   = 'refund';
    const COMBINED = 'combined';

    const VALID_RECONCILIATION_TYPES = [self::NODAL, self::PAYMENT, self::REFUND, self::COMBINED];

    /*************************
     * Internal Header Names
     *************************/

    const PAYMENT_ID          = 'payment_id';
    const REFUND_ID           = 'refund_id';
    const CARD_TYPE           = 'card_type';
    const CARD_LOCALE         = 'card_locale';
    const GATEWAY_SERVICE_TAX = 'gateway_service_tax';
    const GATEWAY_FEE         = 'gateway_fee';

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

    public function __construct()
    {
        $this->messenger = new Messenger();
    }

    /**
     * This is the start of the reconciliation. This is executed from the orchestrator.
     * For each file, it figures out which type of reconciliation is it (nodal, payment, refund, combined)
     * and calls the startReconciliation of the respective reconciliation type.
     *
     * @param array $allFilesContents
     */
    public function startReconciliation(array $allFilesContents)
    {
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

            DB::transaction(function() use ($fileContents)
            {
                $this->subReconciliator->startReconciliation($fileContents);
            });
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
     */
    public function inExcludeList($fileDetails)
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

    /**
     * Gets the reconciliation type by either the sheet name in case of excel files
     * or by the file name in case of csv files.
     *
     * @param $extraDetails
     * @return mixed
     */
    protected function getReconciliationType($extraDetails)
    {
        if (isset($extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME]) === true)
        {
            $fileName = $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME];
        }
        else
        {
            $fileName = $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
        }

        $fileName = strtolower($fileName);

        // The method is present in child class since different gateways have
        // different sheet names/file names for reconciliation types.
        $reconciliationType = $this->getTypeName($fileName);

        // Ideally, should never come here.
        if ((in_array($reconciliationType, self::VALID_RECONCILIATION_TYPES) === false) or
            ($reconciliationType === null))
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
}