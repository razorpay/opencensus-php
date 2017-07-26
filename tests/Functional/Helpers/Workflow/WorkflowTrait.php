<?php

namespace RZP\Tests\Functional\Helpers\Workflow;

use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Entity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Permission;

trait WorkflowTrait
{
    public function editWorkflow($workflowId, $orgId, $input)
    {
        $this->ba->adminAuth('test', null, $orgId);

        $defaultValues = $this->getDefaultWorkflowArray();

        // Permissions list should be sent in input.
        $attributes = array_merge($defaultValues, $input);

        $request = [
            'method' => 'PUT',
            'url'    => '/workflows/' . $workflowId,
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getWorkflow($workflowId, $orgId)
    {
        $this->ba->adminAuth('test', null, $orgId);

        $request = [
            'method' => 'GET',
            'url'    => '/workflows/' . $workflowId,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function createWorkflow(array $input)
    {
        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($input, $defaultAttributes);

        $workflow = $this->fixtures->create('workflow', [
            'org_id' => $attributes['org_id'],
            'name'   => $attributes['name']
            ]);

        $permissions = (new Permission\Repository)
            ->retrieveIdsByNames($attributes['permissions']);

        $workflow->permissions()->sync($permissions);

        $this->createWorkflowSteps($workflow->getId(), $attributes['levels']);

        return $workflow;

    }

    private function createWorkflowSteps($workflowId, array $levels)
    {
        foreach ($levels as $level)
        {
            $steps = $level[Entity::STEPS];

            $data = [
                Step\Entity::WORKFLOW_ID => $workflowId,
                Step\Entity::LEVEL       => $level[Step\Entity::LEVEL],
                Step\Entity::OP_TYPE     => $level[Step\Entity::OP_TYPE],
            ];

            foreach ($steps as $step)
            {
                $step = array_merge($data, $step);

                $this->fixtures->create('workflow_step', $step);
            }
        }
    }

    /**
     * getDefaultWorkflowArray default workflow
     *
     * @return array()
     */
    private function getDefaultWorkflowArray()
    {
        //permissions are not included in default array cause
        //only only workflow can be created for a permission.

        return [
            'name'   => 'Test workflow',
            'levels' => [
                [
                    'level'   => 1,
                    'op_type' => 'and',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ];
    }
}

