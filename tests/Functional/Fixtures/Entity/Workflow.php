<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Admin\Permission as AdminPermission;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Models\Workflow\Entity;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class Workflow extends Base
{
    use HeimdallTrait;
    use WorkflowTrait;

    protected $org = null;
    protected $workflowDefaultPermissions = null;

    const DEFAULT_WORKFLOW_ID = 'workflowId1000';

    public function setUp()
    {
        $this->org = (new OrgRepository)->getRazorpayOrg();

        $this->addWorkflowPermissionsToOrg($this->org);

        $this->workflowDefaultPermissions = (new AdminPermission\Repository)
                                                ->retrieveIdsByNames([AdminPermission\Name::EDIT_ADMIN]);

        $this->fixtures->create('org:workflow_users', ['org' => $this->org]);

        $this->fixtures->create('workflow:default_workflow');
    }

    public function createDefaultWorkflow()
    {
        $workflow = $this->fixtures->create('workflow', ['id' => self::DEFAULT_WORKFLOW_ID]);

        // Attach permissions to the created workflow.
        $workflow->permissions()->sync($this->workflowDefaultPermissions);

        $defaultWorkflowAttributes = $this->getDefaultWorkflowArray();

        $this->createWorkflowSteps($workflow->getId(), $defaultWorkflowAttributes[Entity::LEVELS]);

        return $workflow;
    }
}
