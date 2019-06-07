<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use App;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\RequestProcessor;
use RZP\Exception\ReconciliationException;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;

class CombinedReconciliate extends Base\Foundation\SubReconciliate
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

    public function __construct(string $gateway = null, Batch\Entity $batch = null)
    {
        parent::__construct($gateway);

        $this->messenger = new Messenger();

        $this->messenger->batch = $batch;
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
                        'gateway'       => $this->gateway
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

            $subReconciliatorObject = new $subReconciliatorClassName($this->gateway);

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
     * @param Batch\Processor\Base $batchProcessor
     * @throws ReconciliationException
     */
    public function startReconciliationV2(array $fileContents, Batch\Processor\Base $batchProcessor)
    {
        $batch = $batchProcessor->batch;

        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        try
        {
            foreach ($fileContents as $row)
            {
                $entityType = $this->getReconciliationTypeForRow($row);

                try
                {
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
                                'trace_code' => TraceCode::RECON_PARSE_ERROR,
                                'message' => $message,
                                'row_details' => $row,
                                'extra_details' => $extraDetails,
                                'gateway' => $this->gateway
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
                            ]);
                    }

                    $subReconciliatorObject = $this->getSubReconciliatorObject($entityType, $batch);

                    // As we are creating subRecon object again here, need to set the source for it
                    $subReconciliatorObject->setSource($this->source);

                    $this->repo->transactionOnLiveAndTest(function() use ($subReconciliatorObject, $row, $extraDetails)
                    {
                        $subReconciliatorObject->setExtraDetails($extraDetails);
                        $subReconciliatorObject->runReconciliate($row);
                    });
                }
                catch (\Exception $ex)
                {
                    //
                    // Increment failure count.
                    // Note : This is needed because sometime when batch faces any exception
                    // (e.g. payment absent) then recon process terminates mid way. If failure
                    // count was 0 at this time, then the batch status is set to 'processed',
                    // which should not happen in such failure cases.
                    //
                    $this->setSummaryCount(self::FAILURES_SUMMARY, head($row));

                    //
                    // Throw the exception because we do not want to process the
                    // remaining file, as something is wrong with this file.
                    //
                    throw $ex;
                }
                finally
                {
                    $batch->incrementProcessedCount();
                }
            }
        }
        finally
        {
            if (count(static::$scroogeReconciliate) > 0)
            {
                //
                // Here we can't use shouldForceUpdate() function because
                // the instance variable $this->extraDetails is set only on
                // subreconciliate objects (payment/refund)
                //

                $forceUpdateFields = $extraDetails[RequestProcessor\Base::INPUT_DETAILS][RequestProcessor\Base::FORCE_UPDATE];

                $forceUpdateArn = in_array(RequestProcessor\Base::REFUND_ARN, $forceUpdateFields, true);

                $batchProcessor->setScroogeDispatchData(
                    [
                        'data'              => static::$scroogeReconciliate,
                        'source'            => $this->source,
                        'force_update_arn'  => $forceUpdateArn,
                    ]
                );

                //
                // Need to reset it now, else few testcases are failing when we run
                // ReconciliationFileTest. Though individually the same test passes.
                // (even the payment recon test, having only payment rows in MIS file
                // also have this scroogeReconciliate data set and thus scrooge dispatch happened)
                //

                static::$scroogeReconciliate = [];
            }

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
     * @param Batch\Entity|null $batch
     * @return Base\Foundation\SubReconciliate
     */
    protected function getSubReconciliatorObject(string $entityType, Batch\Entity $batch = null)
    {
        if (isset($this->subReconciliatorObjects[$entityType]) === true)
        {
            return $this->subReconciliatorObjects[$entityType];
        }

        $subReconciliatorClassName = $this->getSubReconciliatorClassName($entityType);

        $subReconciliatorObject = new $subReconciliatorClassName($this->gateway, $batch);

        $this->subReconciliatorObjects[$entityType] = $subReconciliatorObject;

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
