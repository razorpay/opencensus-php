<?php

namespace RZP\Models\Payout\DataMigration;

use App;
use Illuminate\Foundation\Application;

use RZP\Base\RepositoryManager;
use RZP\Models\Workflow\Service\StateMap\Entity as WorkflowStateMapEntity;

class WorkflowStateMap
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


    const STATE_STATUS  = 'state_status';
    const ACTOR_ROLE    = 'actor_role';

    const COUNT_OF_APPROVALS_NEEDED   = 'count_of_approvals_needed';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function getPayoutServiceWorkflowStateMapForApiPayout(string $workflowId)
    {
        $payoutServiceWorkflowStateMap = [];

        $workflowStateMaps = $this->repo->workflow_state_map->getByWorkflowId($workflowId);

        if (empty($workflowStateMaps) === false)
        {
            foreach ($workflowStateMaps as $workflowStateMap)
            {
                $payoutServiceWorkflowStateMap[] = $this->createPayoutServiceWorkflowStateMap($workflowStateMap);
            }
        }

        return $payoutServiceWorkflowStateMap;
    }

    protected function createPayoutServiceWorkflowStateMap(WorkflowStateMapEntity $workflowStateMap)
    {
        return [
            // countOfApprovalNeeded -> no mapping for this column of PS in API
            WorkflowStateMapEntity::ID          => $workflowStateMap->getId(),
            WorkflowStateMapEntity::WORKFLOW_ID => $workflowStateMap->getWorkflowId(),
            WorkflowStateMapEntity::STATE_ID    => $workflowStateMap->getStateId(),
            WorkflowStateMapEntity::TYPE        => $workflowStateMap->getType(),
            WorkflowStateMapEntity::GROUP_NAME  => $workflowStateMap->getGroupName(),
            self::STATE_STATUS                  => $workflowStateMap->getStatus(),
            WorkflowStateMapEntity::MERCHANT_ID => $workflowStateMap->getMerchantId(),
            self::ACTOR_ROLE                    => $workflowStateMap->getActorTypeValue(),
            WorkflowStateMapEntity::CREATED_AT  => $workflowStateMap->getCreatedAt(),
            WorkflowStateMapEntity::UPDATED_AT  => $workflowStateMap->getUpdatedAt(),
            self::COUNT_OF_APPROVALS_NEEDED     => 1
        ];
    }
}
