<?php

namespace RZP\Models\Payout\DualWrite;

use RZP\Trace\TraceCode;
use RZP\Models\Workflow\Service\EntityMap\Entity;

class WorkflowEntityMap extends Base
{
    public function dualWritePSWorkflowEntityMap(string $payoutId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_WORKFLOW_ENTITY_MAP_INIT,
            ['payout_id' => $payoutId]
        );

        $psWorkflowEntityMap = $this->getAPIWorkflowEntityMapFromPayoutService($payoutId);

        if (empty($psWorkflowEntityMap) === true)
        {
            return null;
        }

        /** @var Entity $apiWorkflowEntityMap */
        $apiWorkflowEntityMap = $this->repo->workflow_entity_map->findByEntityIdAndEntityType('payout', $payoutId);

        if (empty($apiWorkflowEntityMap) === true)
        {
            $this->repo->workflow_entity_map->saveOrFail($psWorkflowEntityMap);

            return $psWorkflowEntityMap;
        }

        $apiWorkflowEntityMap->setRawAttributes($psWorkflowEntityMap->getAttributes());

        $this->repo->saveOrFail($apiWorkflowEntityMap);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_WORKFLOW_ENTITY_MAP_DONE,
            ['payout_id' => $payoutId]
        );

        return $apiWorkflowEntityMap;
    }

    public function getAPIWorkflowEntityMapFromPayoutService(string $payoutId)
    {
        $payoutServiceWorkflowEntityMap = $this->repo->workflow_entity_map->getPayoutServiceWorkflowEntityMap($payoutId);

        return $this->modifyEntityMapEntity($payoutServiceWorkflowEntityMap);
    }

    public function getAPIWorkflowEntityMapFromPayoutServiceByWorkflowId(string $workflowId)
    {
        $payoutServiceWorkflowEntityMap = $this->repo->workflow_entity_map->
        getPayoutServiceWorkflowEntityMapByWorkflowId($workflowId);

        return $this->modifyEntityMapEntity($payoutServiceWorkflowEntityMap);
    }

    protected function modifyEntityMapEntity($payoutServiceWorkflowEntityMap)
    {
        if (count($payoutServiceWorkflowEntityMap) === 0)
        {
            return null;
        }

        $psWorkflowEntityMap = $payoutServiceWorkflowEntityMap[0];

        // converts the stdClass object into associative array.
        $this->attributes = get_object_vars($psWorkflowEntityMap);

        $this->processModifications();

        $entity = new Entity;

        $entity->setRawAttributes($this->attributes, true);

        // Explicitly setting the connection.
        $entity->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $entity->timestamps = false;

        return $entity;
    }
}
