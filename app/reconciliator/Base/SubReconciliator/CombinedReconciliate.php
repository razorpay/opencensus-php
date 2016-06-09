<?php

namespace Reconciliator\Base;

use Trace\TraceCode;

use Reconciliator\Orchestrator;
use Reconciliator\Messenger;

class CombinedReconciliate extends Foundation\SubReconciliate
{
    protected $messenger;

    public function __construct()
    {
        $this->messenger = new Messenger();
    }

    /**
     * This is the start of reconciliation for a combined report.
     * Ones which have both payments and refunds in the same file.
     * Here, we get the reconciliation type for each row, instead of for
     * each file as being done in payment and refund reconciliations.
     * We run the respective reconciliation function for payments and refunds
     * from the gateway's sub reconciliator classes itself.
     *
     * @param array $fileContents
     */
    public function startReconciliation($fileContents)
    {
        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $entityType = $this->getReconciliationTypeForRow($row);

            if ($entityType === null)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_PARSE_ERROR,
                        'message'       => 'Did not get the reconciliation type for the row in combined reconciliation.',
                        'row_details'   => $row,
                        'extra_details' => $extraDetails,
                        'gateway'       => get_called_class()
                    ]);

                continue;
            }

            $subReconciliatorClassName = $this->getSubReconciliatorClassName($entityType);
            $subReconciliatorClass = new $subReconciliatorClassName;

            $subReconciliatorClass->runReconciliate($row, $extraDetails);
        }
    }

    protected function getSubReconciliatorClassName($reconciliationType)
    {
        $parentNamespace = $this->getParentNamespace();

        $subReconciliatorClassName = $parentNamespace . '\\'
            . ucfirst($reconciliationType)
            . 'Reconciliate';

        return $subReconciliatorClassName;
    }

    protected function getParentNamespace()
    {
        return join('\\', explode('\\', get_called_class(), -1));
    }
}