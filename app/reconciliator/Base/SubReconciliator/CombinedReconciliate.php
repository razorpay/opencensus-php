<?php

namespace Reconciliator\Base;

use Trace\TraceCode;

use Reconciliator\Orchestrator;


class CombinedReconciliate extends Foundation\SubReconciliate
{
    public function startReconciliation($fileContents)
    {
        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $entityType = $this->getReconciliationTypeForRow($row);

            if ($entityType === null)
            {
                // TODO: Raise an alert about not being able to figure out the row's
                // reconciliation type.
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