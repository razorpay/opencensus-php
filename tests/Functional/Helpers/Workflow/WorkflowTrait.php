<?php

namespace RZP\Tests\Functional\Helpers\Workflow;

use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Entity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Permission;

trait WorkflowTrait
{
    private function createWorkflow(array $input, string $mode = 'test')
    {
        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $input);

        $workflow = $this->fixtures->on($mode)->create('workflow', [
            'org_id' => $attributes['org_id'],
            'name'   => $attributes['name']
            ]);

        $permissions = (new Permission\Repository)->retrieveIdsByNames($attributes['permissions']);

        $workflow->permissions()->sync($permissions);

        $this->createWorkflowSteps($workflow->getId(), $attributes['levels'], $mode);

        return $workflow;

    }

    private function createWorkflowSteps($workflowId, array $levels, string $mode = 'test')
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

                $this->fixtures->on($mode)->create('workflow_step', $step);
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
                    'op_type' => 'or',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::ADMIN_ROLE,
                        ],
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::CHECKER_ROLE,
                        ]
                    ],
                ],
                [
                    'level'   => 2,
                    'op_type' => 'and',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::MAKER_ROLE,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Workflow action approve neeeded for workflow execution.
     *
     * @param array  $workflowActionId
     * @param string $token
     * @return mixed
     */
    private function approveWorkflowAction($workflowActionId, $mode = 'test')
    {
        $this->ba->adminAuth($mode, Org::CHECKER_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $request = [
            'method'    => 'POST',
            'url'       => '/w-actions/' . $workflowActionId . '/checkers',
            'content'   => [
                'approved'  => 1,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    public function performWorkflowAction($workflowActionId, bool $shouldApprove = true)
    {
        $this->ba->adminAuth('test');

        $request = [
            'method' => 'POST',
            'url' => '/w-actions/' . $workflowActionId . '/checkers',
            'content' => [
                'approved' => $shouldApprove,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }
}
