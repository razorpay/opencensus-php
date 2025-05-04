<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Admin\Permission;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME              => 'required|string|max:150',
        Entity::ORG_ID            => 'required|string|size:14',
        Entity::PERMISSIONS       => 'required|array',
        Entity::LEVELS            => 'required|array',
        Entity::CANARY_ENABLED    => 'sometimes|boolean',
        Entity::CANARY_PERCENTAGE => 'sometimes|int',
    ];

    protected static $initiateWorkflowRules = [
        Constants::ENTITY_ID       => 'required|string|size:14',
        Constants::ENTITY_TYPE     => 'required|string|max:150',
        Constants::PERMISSION_NAME => 'required|string|max:150',
        Constants::ORIGINAL_DATA   => 'sometimes|array',
        Constants::DIRTY_DATA      => 'required|array',
        Constants::MAKER_ID        => 'required|string|size:14',
        Constants::MAKER_TYPE      => 'required|string|max:150',
        Constants::TARGET          => 'required|array',
        Constants::OPERATION_TYPE  => 'sometimes|string|in:SINGLE,BULK',
        Constants::PAYLOAD         => 'sometimes|array',
    ];

    protected static $initiateWorkflowValidators = [
        'target'
    ];

    protected static $targetRules = [
        Constants::SERVICE                  => 'required|string|max:150',
        Constants::ROUTE                    => 'required|string|max:150',
        Constants::METHOD                   => 'required|string|max:150',
        Constants::RETRY                    => 'required|int',
        Constants::BULK_SIZE                => 'sometimes|int',
        Constants::ENTITY_REFRESH_ROUTE     => 'required|string',
        Constants::PRIMARY_KEY_COLUMN       => 'required|string',
    ];

    protected static $editRules = [
        Entity::NAME                => 'sometimes|string|max:150',
        Entity::PERMISSIONS         => 'sometimes|array',
        Entity::LEVELS              => 'sometimes|array',
        Entity::ORG_ID              => 'sometimes|string|size:14',
        Entity::CANARY_ENABLED      => 'sometimes|boolean',
        Entity::CANARY_PERCENTAGE   => 'sometimes|int',
    ];

    public function validatePermissionHasOneWorkflow(
        string $orgId, array $perms, string $excludeWorkflow = null)
    {
        $workflowIds = (new Repository)->getWorkflowIdsForPermissionsAndOrgId($orgId, $perms);

        $workflowIds = $workflowIds->toArray();

        $workflowIds = array_diff($workflowIds, [$excludeWorkflow]);

        if (empty($workflowIds) === false)
        {
            $data = [
                'workflow_ids'   => $workflowIds,
                'permission_ids' => $perms,
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS,
                null, $data);
        }
    }

    public function validatePermissionsForOrg(
        string $orgId, array $permissions)
    {
        $permsWithWorkflowEnabled = (new Permission\Repository)->getPermissionsWithWorkflowEnabled($orgId);

        $permIds = array_map(function($permission)
        {
            return $permission['id'];
        }, $permsWithWorkflowEnabled->toArray());

        $diffPerms = array_diff($permissions, $permIds);

        if (empty($diffPerms) === false)
        {
            throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PERMISSION_DISABLED_FOR_WORKFLOW,
                        null, $diffPerms);
        }
    }

    protected function validateTarget(array $input)
    {

        $this->validateInput('target', $input[Constants::TARGET]);
    }
}
