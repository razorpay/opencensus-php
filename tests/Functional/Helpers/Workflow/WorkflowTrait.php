<?php

namespace RZP\Tests\Functional\Helpers\Workflow;

use RZP\Tests\Functional\Fixtures\Entity\Org;

trait WorkflowTrait
{
    private function createWorkflow(array $input = [])
    {
        $defaultValues = $this->getDefaultWorkflowArray();

        // Permissions list should be sent in input.
        $attributes = array_merge($defaultValues, $input);

        $this->ba->adminAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/workflows',
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function deleteWorkflow($workflowId, $orgId)
    {
        $this->ba->adminAuth('test', null, $orgId);

        $request = [
            'method' => 'DELETE',
            'url'    => '/workflows/' . $workflowId,
            'content' => [],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function getWorkflowPermissions($orgId)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/orgs/'. $orgId .'/permissions?type=workflow',
            'content' => [],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    /**
     * getDefaultWorkflowArray default workflow
     *
     * @return array()
     */
    public function getDefaultWorkflowArray()
    {
        //permissions are not included in default array cause only only workflow can be created for a permission.

        return [
            'name'   => 'Test workflow',
            'org_id' => 'org_'.Org::RZP_ORG,
            'levels' => [
                [
                    'level'   => 1,
                    'op_type' => 'and',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => 'role_' . Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ];
    }
}

