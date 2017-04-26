<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME             => 'required|string|max:255',
        Entity::DESCRIPTION      => 'sometimes|string|max:255',
        Entity::CATEGORY         => 'required|string|max:255',
        Entity::ASSIGNABLE       => 'sometimes|bool',
        Entity::ORGS             => 'sometimes|array',
        Entity::WORKFLOW_ORGS    => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME             => 'sometimes|string|max:255',
        Entity::DESCRIPTION      => 'sometimes|string|max:255',
        Entity::CATEGORY         => 'sometimes|string|max:255',
        Entity::ASSIGNABLE       => 'sometimes|bool',
        Entity::ORGS             => 'sometimes|array',
        Entity::WORKFLOW_ORGS    => 'sometimes|array',
    ];

    protected static $createValidators = [
        Entity::WORKFLOW_ORGS
    ];

    public function validateWorkflowOrgs(array $input)
    {
        if (empty($input[Entity::WORKFLOW_ORGS]) === true)
        {
            return;
        }

        $orgs = $input[Entity::ORGS];
        $workflowOrgs = $input[Entity::WORKFLOW_ORGS];

        // Orgs which are being assigned to a permission and workflows is
        // enabled for them on this permission
        $diffOrgs = array_intersect($orgs, $workflowOrgs);

        $extraOrgs = array_intersect($workflowOrgs, $orgs);

        if (count($diffOrgs) !== count($workflowOrgs))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot enable workflow for orgs which are unassigned',
                $extraOrgs);
        }
    }
}
