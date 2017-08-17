<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\Fixtures\Entity\Workflow;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class WorkflowActionTest extends TestCase
{
    use WorkflowTrait;
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowActionTestData.php';

        parent::setUp();
    }

    public function testCreateWorkflowAction()
    {

    }
}