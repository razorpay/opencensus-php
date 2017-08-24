<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Admin\Permission as AdminPermission;

class WorkflowAction extends Base
{
    protected $defaultWorkflowPermission;

    const DEFAULT_WORKFLOW_ACTION_ID = 'wfActionId1000';

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
            'admin_id'      => Org::SUPER_ADMIN,
            'permission_id' => $this->defaultWorkflowPermission->getId(),
        ]);

        $this->fixtures->create('action_state', [
            'action_id'     => $action->getId(),
            'admin_id'      => Org::SUPER_ADMIN,
        ]);
    }
}