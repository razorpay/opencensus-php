<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class Workflow extends Base
{
    use HeimdallTrait;
    use WorkflowTrait;

    protected $org = null;
    protected $workflowDefaultPermissions = null;

    public function setUp()
    {
        $this->org = (new OrgRepository)->getRazorpayOrg();

        $this->addWorkflowPermissionsToOrg($this->org);

        $this->workflowDefaultPermissions = (new Permission\Repository)
                                                ->retrieveIdsByNames([Permission\Name::EDIT_ADMIN]);

        $this->fixtures->create('workflow:default_workflow');
    }

    public function createDefaultWorkflow()
    {
        $workflow = $this->fixtures->create('workflow', ['id' => 'workflowId1000']);

        $workflow->permissions()->sync($this->workflowDefaultPermissions);

        return $workflow;

    }
}
