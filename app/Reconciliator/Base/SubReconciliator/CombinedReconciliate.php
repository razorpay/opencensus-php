<?php

namespace RZP\Reconciliator\Base;

use App;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Orchestrator;
use RZP\Exception\ReconciliationException;

class CombinedReconciliate extends Foundation\SubReconciliate
{
    const NA = 'not_applicable';

    protected $messenger;

    protected $app;
    protected $repo;

    /**
     * In case of combined reconciliation, we need to  call the payment / refund
     * reconciliators depending on the row. This map keeps track of the reconciliator
     * objects created for each type so that they can be reused
     *
     * @var array
     */
    protected $subReconciliatorObjects = [];

    public function __construct()
    {
        parent::__construct();

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
     * @return array
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
                $message = 'Did not get the reconciliation type for the row in combined reconciliation.';

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_PARSE_ERROR,
                        'message'       => $message,
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

            $this->repo->transactionOnLiveAndTest(function() use ($subReconciliatorObject, $row, $extraDetails)
            {
                $subReconciliatorObject->setExtraDetails($extraDetails);
                $subReconciliatorObject->runReconciliate($row);
            });
        }

        //
        // Ideally, we should be returning the response of getSummary() here. But, with
        // the way that this has been implemented, that is not possible.
        //
        return [
            'message' => 'All payments and refunds have been reconciled successfully!'
        ];
    }

    /**
     * This is the start of reconciliation for a combined report.
     * Ones which have both payments and refunds in the same file.
     * Here, we get the reconciliation type for each row, instead of for
     * each file as being done in payment and refund reconciliations.
     * We run the respective reconciliation function for payments and refunds
     * from the gateway's sub reconciliator classes itself. We reuse the same
     * subreconciliate objects and  at the end of the reconciliation, we update the
     * summary count to the batch
     *
     * @param array $fileContents input file contents
     * @param Batch\Entity $batch batch entity for reconciliation
     * @throws ReconciliationException
     */
    public function startReconciliationV2(array $fileContents, Batch\Entity $batch)
    {
        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        try
        {
            foreach ($fileContents as $row)
            {
                $entityType = $this->getReconciliationTypeForRow($row);

                if ($entityType === self::NA)
                {
                    //
                    // This row probably doesn't have a payment and hence is not applicable for
                    // reconciliation. We mark it as a success row, as we are not doing any
                    // processing on the row here
                    //
                    $this->successes[] = $row;

                    continue;
                }

                if ($entityType === null)
                {
                    $message = 'Did not get the reconciliation type for the row in combined reconciliation.';

                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'    => TraceCode::RECON_PARSE_ERROR,
                            'message'       => $message,
                            'row_details'   => $row,
                            'extra_details' => $extraDetails,
                            'gateway'       => get_called_class()
                        ]);

                    //
                    // We add the invalid row to list of failures, so that batch entity
                    // failure_count is updated accordingly
                    //
                    $this->failures[] = $row;

                    throw new ReconciliationException(
                        'Did not get the reconciliation type for the row in combined reconciliation.',
                        [
                            'row' => $row,
                        ]
                    );
                }

                $subReconciliatorObject = $this->getSubReconciliatorObject($entityType);

                $this->repo->transactionOnLiveAndTest(function() use ($subReconciliatorObject, $row, $extraDetails)
                {
                    $subReconciliatorObject->setExtraDetails($extraDetails);
                    $subReconciliatorObject->runReconciliate($row);
                });
            }
        }
        finally
        {
            $this->updateCombinedSummaryCount();

            $this->updateBatchWithSummary($batch);
        }
    }

    /**
     * For the given reconciliation request for the gateway, we maintain a map of
     * the subreconciliator objects created for a given type so that they can be reused.
     * This is required because in combined reconciliate, we call the individual
     * payment / refund reconciliators for each row. Hence we need to preserve these
     * objects to get the success and failure count at the end of processing.
     *
     * @param  string $entityType Recon entity type
     * @return Foundation\SubReconciliate
     */
    protected function getSubReconciliatorObject(string $entityType)
    {
        if (isset($this->subReconciliatorObjects[$entityType]) === true)
        {
            return $this->subReconciliatorObjects[$entityType];
        }

        $subReconciliatorClassName = $this->getSubReconciliatorClassName($entityType);

        $subReconciliatorObject = new $subReconciliatorClassName;

        $this->subReconciliatorObjects[$entityType] = $subReconciliatorObject;

        $subReconciliatorObject->resetProcessingAttributes();

        return $subReconciliatorObject;
    }

    /**
     * Post reconciliation, we update the summary count of the combined reconciliator
     * object with that of the inividual payment / refund subreconciliator
     *
     */
    protected function updateCombinedSummaryCount()
    {
        foreach ($this->subReconciliatorObjects as $subReconciliatorObject)
        {
            $this->successes = array_merge($this->successes, $subReconciliatorObject->getSuccesses());

            $this->failures = array_merge($this->failures, $subReconciliatorObject->getFailures());
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
