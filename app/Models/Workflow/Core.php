<?php

namespace RZP\Models\Workflow;

use stdClass;
use RZP\Error;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Workflow\Action\OperationType;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Base;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Workflow\Repository as WorkflowRep;


class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant = null)
    {
        $workflow = (new Entity)->generateId();

        $orgId       = $input[Entity::ORG_ID];
        $permissions = $input[Entity::PERMISSIONS];

        // Check if the permissions given are enabled to have workflows
        $workflow->getValidator()->validatePermissionsForOrg($orgId, $permissions);

        $isCacEnabled = false;

        if (empty($merchant) === false)
        {
            $isCacEnabled = $merchant->isCACEnabled();
        }

        Step\Entity::setCacStatus($isCacEnabled);


        //
        // Check if passed permissions already have a workflow assigned to them
        // create_payout can have multiple workflows though, skip the validation for that.
        //
        if ($this->requestHasCreatePayoutPermission($permissions, $orgId) === false)
        {
            $workflow->getValidator()->validatePermissionHasOneWorkflow($orgId, $permissions);
        }

        $org = $this->repo->org->findOrFailPublic($orgId);

        $workflow->org()->associate($org);

        $workflow->merchant()->associate($merchant);

        $workflow->build($input);

        $this->repo->transactionOnLiveAndTestAndAsv(function() use ($workflow, $input)
        {
            $this->app['config']['workflow_guard.current_workflow_dml_action'] = 'insert';

            $this->repo->workflow->saveOrFail($workflow);

            $this->repo->permission->validateExists($input[Entity::PERMISSIONS]);

            $this->repo->sync($workflow, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);

            // Create the workflow steps
            foreach ($input[Entity::LEVELS] as $level)
            {
                $step = $this->createStepsForWorkflow($level, $workflow);
            }
        });

        $id = $workflow->getPublicId();
        $orgId = $this->app['basicauth']->getAdminOrgId();

        $this->app['config']['workflow_guard.current_workflow_id'] = $id;
        $this->app['config']['workflow_guard.current_workflow_dml_action'] = 'select';

        $workflow = (new WorkflowRep())
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        return $workflow;
    }

    /**
     * createWorkflowAction will create a workflow action with the details that are specified in the input
     * The input would contain the permission_name, entity_type, entity_id, dirty_data, old_data and target details
     * The target details would contain the route details with which the workflow engine needs to invoke post the workflow approval
     * @param array $input
     * @return mixed
     */
    public function createWorkflowAction(array $input)
    {
        $response = [
            'success' => false,
            'error'   => new stdClass(),
            'data'    => new stdClass(),
        ];
        try
        {
            $operationType = $input[Constants::OPERATION_TYPE] ?? OperationType::SINGLE ;
            $this->app['workflow']
                ->setEntityAndId($input[Constants::ENTITY_TYPE], $input[Constants::ENTITY_ID])
                ->setPermission($input[Constants::PERMISSION_NAME])
                ->setTargetDetails($input[Constants::TARGET])
                ->setOperationType($operationType)
                ->setInput($input[Constants::PAYLOAD]);
            $makerType = $input[Constants::MAKER_TYPE];
            $makerId   = $input[Constants::MAKER_ID];
            if($makerType == MakerType::ADMIN)
            {
                $admin = $this->repo->admin->getAdminFromId($makerId);
                $this->app['workflow']
                    ->setWorkflowMaker($admin)
                    ->setWorkflowMakerType(MakerType::ADMIN);
            }
            if($makerType == MakerType::MERCHANT)
            {
                $merchant = $this->repo->merchant->getMerchant($makerId);
                $this->app['workflow']
                    ->setWorkflowMaker($merchant)
                    ->setWorkflowMakerType(MakerType::MERCHANT);
            }
            $this->app['workflow']->handle($input[Constants::ORIGINAL_DATA], $input[Constants::DIRTY_DATA]);
        }
        catch (Exception\EarlyWorkflowResponse $e)
        {
            $response['success'] = true;
            $workflowActionData  = json_decode($e->getMessage(), true);
            $response['data']    = $workflowActionData;
        }
        catch (\Exception $e)
        {
            $response['error'] = [
                'description' => $e->getMessage() ?? 'error occurred while creating workflow for the given input',
            ];
        }

        return $response;
    }

    protected function createStepsForWorkflow(array $level, Entity $workflow)
    {
        $steps = $level[Entity::STEPS];

        $data = [
            Step\Entity::WORKFLOW_ID => $workflow->getId(),
            Step\Entity::LEVEL       => $level[Step\Entity::LEVEL],
            Step\Entity::OP_TYPE     => $level[Step\Entity::OP_TYPE],
        ];

        foreach ($steps as $step)
        {
            $step = array_merge($step, $data);

            Role\Entity::silentlyStripSign($step[Step\Entity::ROLE_ID]);

            (new Step\Core)->create($step, $workflow);
        }
    }

    public function update(Entity $workflow, array $input)
    {
        if ($this->workflowHasOpenActions($workflow) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED,
                null,
                ['input' => $input, 'id' => $workflow->getId()]);
        }

        /** @var Validator $validator */
        $validator = $workflow->getValidator();

        $orgId = $workflow->getOrgId();

        //
        // Check if selected permissions have workflows enabled
        // for them in the current org
        //
        $validator->validatePermissionsForOrg($orgId, $input[Entity::PERMISSIONS]);

        //
        // Check if passed permissions already have a workflow assigned to them
        // create_payout can have multiple workflows though, skip the validation for that.
        //
        if ($this->requestHasCreatePayoutPermission($input[Entity::PERMISSIONS], $orgId) === false)
        {
            $validator->validatePermissionHasOneWorkflow($orgId, $input[Entity::PERMISSIONS], $workflow->getId());
        }

        $workflow->edit($input);

        if (empty($input[Entity::ORG_ID]) === false)
        {
            $org = $this->repo->org->findOrFailPublic($input[Entity::ORG_ID]);

            $workflow->org()->associate($org);
        }

        $this->repo->transactionOnLiveAndTestAndAsv(function() use ($workflow, $input)
        {
            $this->app['config']['workflow_guard.current_workflow_id']  = $workflow->getId();

            $this->repo->workflow->saveOrFail($workflow);

            $this->repo->permission->validateExists($input[Entity::PERMISSIONS]);

            $this->repo->sync($workflow, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);

            // If levels are passed to the edit function, delete the old steps
            // and create the new ones. Dashboard finds it harder to update the
            // existing entities
            if (empty($input[Entity::LEVELS]) === false)
            {
                $currentWorkflowSteps = $workflow->load(['steps', 'steps.checkers'])->steps;

                // Check if an action (action_checker entry) has ever been performed
                // on any of the steps.
                //
                // If yes, then soft delete all steps
                // If no, then force delete all steps

                $checkerCount = 0;

                foreach ($currentWorkflowSteps as $step)
                {
                    $checkerCount = $step->checkers->count();

                    if ($checkerCount > 0)
                    {
                        break;
                    }
                }

                if ($checkerCount > 0)
                {
                    // Soft delete
                    $workflow->steps()->delete();
                }
                else
                {
                    // Force delete
                    foreach ($currentWorkflowSteps as $step)
                    {
                        $step->forceDelete();
                    }
                }

                // Create its steps
                foreach ($input[Entity::LEVELS] as $level)
                {
                    $step = $this->createStepsForWorkflow($level, $workflow);
                }
            }
        });

        $id = $workflow->getPublicId();

        $orgId = $this->app['basicauth']->getAdminOrgId();

        $workflow = (new WorkflowRep())
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        return $workflow;
    }

    public function delete(Entity $workflow)
    {
        if ($this->workflowHasOpenActions($workflow) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED);
        }

        $this->repo->workflow->deleteOrFail($workflow);

        return $workflow;
    }

    protected function getPermissionIds(Entity $workflow, array $permissions = [])
    {
        $permissionIds = $workflow->permissions->allRelatedIds()->toArray();

        return array_unique(array_merge($permissionIds, $permissions));
    }

    public function workflowHasOpenActions(Entity $workflow)
    {
        $actions = $this->repo
                        ->workflow_action
                        ->fetchOpenActionsByWorkflowId($workflow->getId())
                        ->toArray();

        return (empty($actions) === true);
    }

    protected function requestHasCreatePayoutPermission(array $permissions, string $orgId): bool
    {
        $createPayoutPerm = $this->repo
                                 ->permission
                                 ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, $orgId)
                                 ->first();

        return (in_array($createPayoutPerm, $permissions, true) === true);
    }
}
