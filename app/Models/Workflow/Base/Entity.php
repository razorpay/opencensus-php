<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Comment;
use RZP\Models\Workflow;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action;
use RZP\Models\Base as BaseModel;
use RZP\Models\Admin\Permission;

use RZP\Constants\Entity as E;

class Entity extends BaseModel\PublicEntity
{
    const ORG_ID            = 'org_id';
    const STEP_ID           = 'step_id';
    const PERMISSION_ID     = 'permission_id';

    public function setPublicOrgIdAttribute(array &$attributes)
    {
        $orgId = $this->getAttribute(static::ORG_ID);

        if ($orgId !== null)
        {
            $attributes[static::ORG_ID] = Org\Entity::getSignedId($orgId);
        }
    }

    public function setPublicOwnerIdAttribute(array &$attributes)
    {
        $ownerId = $this->getAttribute(Action\Entity::OWNER_ID);

        if (empty($ownerId) == false)
        {
            $attributes[Action\Entity::OWNER_ID] = Admin\Entity::getSignedId($ownerId);
        }
    }

    public function setPublicRoleIdAttribute(array &$attributes)
    {
        $roleId = $this->getAttribute(Step\Entity::ROLE_ID);

        if ($roleId !== null)
        {
            $attributes[Step\Entity::ROLE_ID] = Role\Entity::getSignedId($roleId);
        }
    }

    public function setPublicStepIdAttribute(array &$attributes)
    {
        $stepId = $this->getAttribute(static::STEP_ID);

        if ($stepId !== null)
        {
            $attributes[static::STEP_ID] = Step\Entity::getSignedId($stepId);
        }
    }

    public function setPublicWorkflowIdAttribute(array &$attributes)
    {
        $workflowId = $this->getAttribute(Step\Entity::WORKFLOW_ID);

        if ($workflowId !== null)
        {
            $attributes[Step\Entity::WORKFLOW_ID] = Workflow\Entity::getSignedId($workflowId);
        }
    }

    public function setPublicAdminIdAttribute(array &$attributes)
    {
        $adminId = $this->getAttribute(Action\Checker\Entity::ADMIN_ID);

        if ($adminId !== null)
        {
            $attributes[Action\Checker\Entity::ADMIN_ID] = Admin\Entity::getSignedId($adminId);
        }
    }

    public function setPublicStateChangerIdAttribute(array &$attributes)
    {
        $id = $this->getAttribute(Action\Entity::STATE_CHANGER_ID);

        if ($id !== null)
        {
            $attributes[Action\Entity::STATE_CHANGER_ID] = Admin\Entity::getSignedId($id);
        }
    }

    public function setPublicMakerIdAttribute(array & $attributes)
    {
        $makerId = $this->getAttribute(Action\Entity::MAKER_ID);

        $makerType = $this->getAttribute(Action\Entity::MAKER_TYPE);

        if ($makerId !== null and $makerType !== null)
        {
            $makerClass = E::getEntityClass($makerType);

            $attributes[Action\Entity::MAKER_ID] = $makerClass::getSignedId($makerId);
        }
    }

    public function setPublicActionIdAttribute(array &$attributes)
    {
        $actionId = $this->getAttribute(Comment\Entity::ACTION_ID);

        if ($actionId !== null)
        {
            $attributes[Comment\Entity::ACTION_ID] = Action\Entity::getSignedId($actionId);
        }
    }

    public function setPublicPermissionIdAttribute(array &$attributes)
    {
        $permissionId = $this->getAttribute(static::PERMISSION_ID);

        if ($permissionId !== null)
        {
            $attributes[static::PERMISSION_ID] = Permission\Entity::getSignedId($permissionId);
        }
    }
}
