<?php

namespace Reconciliator\Base;

use Trace\TraceCode;
use EE\Exception\ReconciliationException;

use Reconciliator\Orchestrator;
use Reconciliator\Messenger;

class CombinedReconciliate extends Foundation\SubReconciliate
{
    const NA = 'not_applicable';

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
     * @throws ReconciliationException
     */
    public function startReconciliation(array $fileContents)
    {
        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $entityType = $this->getReconciliationTypeForRow($row);

            if ($entityType === self::NA)
            {
                // This row probably doesn't have a payment and hence is not applicable for
                // reconciliation.
                continue;
            }

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

                throw new ReconciliationException(
                    'Did not get the reconciliation type for the row in combined reconciliation.',
                    [
                        'row' => $row,
                    ]
                );

                //continue;
            }

            $subReconciliatorClassName = $this->getSubReconciliatorClassName($entityType);
            $subReconciliatorObject = new $subReconciliatorClassName;

            $subReconciliatorObject->runReconciliate($row, $extraDetails);
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


    /**
     * This function needs to be present in this class only
     * because get_called_class will be different, if present in
     * some other class.
     * TODO: create getParentNamespace method which takes get_called_class as an argument.
     *
     * @return string
     */
    protected function getParentNamespace()
    {
        return join('\\', explode('\\', get_called_class(), -1));
    }
}