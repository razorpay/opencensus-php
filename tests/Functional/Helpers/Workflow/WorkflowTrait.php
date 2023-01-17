<?php

namespace RZP\Tests\Functional\Helpers\Workflow;

use DB;
use RZP\Models\Base\EsDao;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Entity;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Org\Repository as OrgRepository;

trait WorkflowTrait
{
    protected $esClient;

    protected $esDao;

    private function createWorkflow(array $input,string $mode = 'test', $template = [])
    {
        $defaultAttributes = empty($template) ? $this->getDefaultWorkflowArray() : $template;

        $attributes = array_merge($defaultAttributes, $input);

        $workflow = $this->fixtures->on($mode)->create('workflow', [
            'org_id' => $attributes['org_id'],
            'name'   => $attributes['name'],
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

    public function performWorkflowAction($workflowActionId, bool $shouldApprove = true, $mode= 'test')
    {
        $this->refreshEsIndices();

        $this->ba->adminAuth($mode);

        $this->addPermissionToBaAdmin(Permission\Name::EDIT_ACTION);

        $request = [
            'method' => 'POST',
            'url' => '/w-actions/' . $workflowActionId . '/checkers',
            'content' => [
                'approved' => $shouldApprove,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    public function addComments($workflowActionId, string $comments, $mode = 'test')
    {
        $this->ba->adminAuth($mode);

        $request = [
            'method' => 'POST',
            'url' => '/w-actions/' . $workflowActionId . '/comments',
            'content' => [
                "comment" => $comments,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    public function updateObserverData($workflowActionId, array $observerData, $mode = 'test')
    {
        $this->ba->adminAuth($mode);

        $request = [
            'method' => 'PUT',
            'url' => '/workflows/' . $workflowActionId . '/observer_data',
            'content' => $observerData,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getWorkflowData($mode = 'test')
    {
        $workflowAction = $this->getLastEntity('workflow_action', true, $mode);

        $this->refreshEsIndices();

        return $this->esDao->searchByIndexTypeAndActionId('workflow_action_'.$mode.'_testing', 'action',
            substr($workflowAction['id'], 9))[0]['_source'];
    }

    protected function setupWorkflow(string $workflowName, string $permissionName): void
    {
        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $this->createWorkflow([
            'org_id' => '100000razorpay',
            'name' => $workflowName,
            'permissions' => [$permissionName],
            'levels' => [
                [
                    'level' => 1,
                    'op_type' => 'or',
                    'steps' => [
                        [
                            'reviewer_count' => 1,
                            'role_id' => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ]);
    }

    protected function addPermissionToBaAdmin(string $permissionName): void
    {
        $admin = $this->ba->getAdmin();

        if ($admin->hasPermission($permissionName) === true)
        {
            return;
        }

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }

    protected function addPermissionToBaAdminForToken(string $permissionName, $token = null): void
    {
        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $admin = (new \RZP\Models\Admin\Admin\Token\Repository)
            ->findOrFailToken($token)->admin;

        $role = $admin->roles()->get()[0];

        $permissionId = $perm->getId();

        $finalPermissions = [Permission\Entity::verifyIdAndSilentlyStripSign($permissionId)];

        foreach ($admin->roles()->get() as $role)
        {
            $permissions = $role->permissions()->get();

            foreach ($permissions as $permission)
            {
                $permissionId = $permission->getId();

                array_push($finalPermissions, Permission\Entity::verifyIdAndSilentlyStripSign($permissionId));
            }
        }

        $finalPermissions = array_unique($finalPermissions);

        $role->permissions()->sync($finalPermissions);
    }

    protected function refreshEsIndices()
    {
        if ($this->esDao === null)
        {
            $this->esDao = (new EsDao);
        }

        if ($this->esClient === null)
        {
            $this->esClient = $this->esDao->getEsClient()->getClient();
        }

        $this->esClient->indices()->refresh();
    }
}
