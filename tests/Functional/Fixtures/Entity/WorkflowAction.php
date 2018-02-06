<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Admin\Permission as AdminPermission;

class WorkflowAction extends Base
{
    protected $defaultWorkflowPermission;

    const DEFAULT_WORKFLOW_ACTION_ID = 'wfActionId1000';
    const DEFAULT_WORKFLOW_CLOSED_ACTION_ID = 'wfAcClsdId1000';

    public function setUp()
    {
        $this->defaultWorkflowPermission = (new AdminPermission\Repository)
            ->retrieveIdsByNames([AdminPermission\Name::EDIT_ADMIN])[0];

        $this->fixtures->create('workflow_action:default_workflow_action');
    }

    public function createDefaultWorkflowAction()
    {
        $action = $this->fixtures->create('workflow_action', [
            'id'            => self::DEFAULT_WORKFLOW_ACTION_ID,
            'maker_id'      => Org::SUPER_ADMIN,
            'maker_type'    => MakerType::ADMIN,
            'permission_id' => $this->defaultWorkflowPermission->getId(),
        ]);

        $this->fixtures->create('state', [
            'action_id'     => $action->getId(),
            'admin_id'      => Org::SUPER_ADMIN,
        ]);
    }

    public function createClosedWorkflowAction()
    {
        $action = $this->fixtures->create('workflow_action', [
            'id'            => self::DEFAULT_WORKFLOW_CLOSED_ACTION_ID,
            'maker_id'      => Org::SUPER_ADMIN,
            'maker_type'    => MakerType::ADMIN,
            'permission_id' => $this->defaultWorkflowPermission->getId(),
            'state'         => \RZP\Models\State\Name::CLOSED,
        ]);

        $this->fixtures->create('state', [
            'action_id'     => $action->getId(),
            'admin_id'      => Org::SUPER_ADMIN,
            'name'          => \RZP\Models\State\Name::CLOSED,
        ]);
    }
}
