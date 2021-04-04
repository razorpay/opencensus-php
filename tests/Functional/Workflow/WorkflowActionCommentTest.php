<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\WorkflowAction;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class WorkflowActionCommentTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowActionTestCommentTestData.php';

        parent::setUp();

        $this->fixtures->workflow_action->setUp();

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);
    }

    /**
     * Here comment will be posted on default workflowaction id wfActionId1000
     */
    public function testWorkflowCreateComment()
    {
        $defaultWorkflowAction  = WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'w_action_' . $defaultWorkflowAction);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['action_id'] = 'w_action' . $defaultWorkflowAction;

        $this->startTest();
    }
}
