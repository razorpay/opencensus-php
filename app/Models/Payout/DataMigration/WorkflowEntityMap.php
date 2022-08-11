<?php

namespace RZP\Models\Payout\DataMigration;

use App;
use Illuminate\Foundation\Application;

use RZP\Models\Payout\Entity;
use RZP\Base\RepositoryManager;
use RZP\Models\Workflow\Service\EntityMap\Entity as WorkflowEntityMapEntity;

class WorkflowEntityMap
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function getPayoutServiceWorkflowEntityMapForApiPayout(Entity $payout)
    {
        /** @var WorkflowEntityMapEntity $workflowEntityMap */
        $workflowEntityMap = $this->repo->workflow_entity_map->findByEntityIdAndEntityType(Entity::PAYOUT, $payout->getId());

        if ($workflowEntityMap === null)
        {
            return [];
        }

        return [$this->createPayoutServiceWorkflowEntityMap($workflowEntityMap)];
    }

    protected function createPayoutServiceWorkflowEntityMap(WorkflowEntityMapEntity $workflowEntityMap)
    {
        return [
            WorkflowEntityMapEntity::ID          => $workflowEntityMap->getId(),
            WorkflowEntityMapEntity::WORKFLOW_ID => $workflowEntityMap->getWorkflowId(),
            WorkflowEntityMapEntity::ENTITY_ID   => $workflowEntityMap->getEntityId(),
            WorkflowEntityMapEntity::CONFIG_ID   => $workflowEntityMap->getConfigId(),
            WorkflowEntityMapEntity::ENTITY_TYPE => $workflowEntityMap->getEntityType(),
            WorkflowEntityMapEntity::MERCHANT_ID => $workflowEntityMap->getMerchantId(),
            WorkflowEntityMapEntity::ORG_ID      => $workflowEntityMap->getOrgId(),
            WorkflowEntityMapEntity::CREATED_AT  => $workflowEntityMap->getCreatedAt(),
            WorkflowEntityMapEntity::UPDATED_AT  => $workflowEntityMap->getUpdatedAt(),
        ];
    }
}
