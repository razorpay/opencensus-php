<?php

namespace RZP\Reconciliator\Base;

use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Messenger;
use App;

class CombinedReconciliate extends Foundation\SubReconciliate
{
    const NA = 'not_applicable';

    protected $messenger;

    protected $app;
    protected $repo;

    public function __construct()
    {
        $this->messenger = new Messenger();
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
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
