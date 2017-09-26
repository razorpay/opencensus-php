<?php

namespace RZP\Models\Admin\Admin;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(Org\Entity $org, array $input)
    {
        $admin = (new Entity)->generateId();

        $admin->setAuditAction(Action::CREATE_ADMIN);

        $admin->org()->associate($org);

        (new Validator)->validatePasswordAuthType(
            $org->getAuthType(), $input);

        $admin->build($input);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $admin->getId(), $org->getId(), [Entity::ROLES, Entity::GROUPS]);

        return $admin;
    }

    public function createAuthToken(Entity $admin, array $input)
    {
        $token = new Token\Entity;

        $token->generateId();

        $token->build($input);

        $token->admin()->associate($admin);

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function delete(Entity $admin)
    {
        $this->repo->deleteOrFail($admin);

        return $admin->toArrayDeleted();
    }

    public function edit(Entity $admin, array $input)
    {
        $admin->setAuditAction(Action::EDIT_ADMIN);

        $admin->edit($input);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $admin->getId(), $admin['org_id'], ['roles', 'groups']);

        return $admin;
    }

    public function associateRelevantEntitiesToAdmin(Entity $admin, array $input)
    {
        if (isset($input[Entity::ROLES]) === true)
        {
            $this->repo->sync($admin, Entity::ROLES,  $input[Entity::ROLES]);
        }

        if (isset($input[Entity::GROUPS]) === true)
        {
            $this->repo->sync($admin, Entity::GROUPS, $input[Entity::GROUPS]);
        }
    }

    public function updatePassword(
        Entity $admin,
        array $input,
        bool $forgotPassword = true,
        $updateType = 'reset')
    {
        $validator = new Validator();

        $validator->validateInput($updateType, $input);

        $admin->setAuditAction(Action::RESET_PASSWORD);

        // In case of forgotten passwords, oldPassword is not present.
        // In case of voluntary change of password, we would require
        // oldPassword
        if ($forgotPassword === false)
        {
            $oldPassword = $input['old_password'];

            $admin->setAuditAction(
                Action::RESET_PASSWORD_INVALID_OLD_PASSWORD);

            if ($admin->matchPassword($oldPassword) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old Password is incorrect');
            }
        }

        $admin->setPassword($input['password']);
    }
}
