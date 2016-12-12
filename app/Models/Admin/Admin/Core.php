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

        $admin->build($input);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $admin->getId(), $org->getId(), ['roles', 'groups']);

        return $admin;
    }

    public function createAuthToken(Entity $admin, array $input)
    {
        $token = new Token\Entity;

        $token->build($input);

        $token->admin()->associate($admin);

        $token->saveOrFail();

        return $token;
    }

    public function delete(Entity $admin)
    {
        $this->repo->deleteOrFail($admin);

        // @todo: To maintain bc. Remove first two lines later.
        $ret = $admin->toArrayDeleted();
        $ret = array_merge($ret, ['success' => true]);

        return $ret;
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
        $roles = [];
        $groups = [];

        if (isset($input['roles']) === true)
        {
            $roles = Role\Entity::verifyIdAndStripSignMultiple($input['roles']);
        }

        $this->repo->sync($admin, 'roles',  $roles);

        if (isset($input['groups']) === true)
        {
            $groups = Group\Entity::verifyIdAndStripSignMultiple($input['groups']);
        }

        $this->repo->sync($admin, 'groups', $groups);
    }

    public function passwordReset(
        string $orgId,
        array $input,
        bool $forgotPassword = true)
    {
        $validator = new Validator();

        $validator->validateInput('reset', $input);

        $email = $input['email'];

        $admin = $this->repo->admin->findByOrgIdAndEmail(
            $orgId, $email, ['org']);

        $admin->getValidator()->validateOrgSupportsPasswordReset(
            $admin->org->getAuthType());

        $admin->setAuditAction(Action::RESET_PASSWORD);

        // Check if the pwd follows the auth policy guidelines
        $authPolicy = new AuthPolicy\Service;
        $authPolicy->validate($admin, $input['password']);

        // In case of forgotten passwords, oldPassword is not present.
        // In case of voluntary change of password, we would require
        // oldPassword
        if ($forgotPassword === false)
        {
            $oldPassword = $input['old_password'];

            $admin->setAuditAction(
                Action::RESET_PASSWORD_INVALID_OLD_PASSWORD);

            if($admin->matchPassword($oldPassword) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old Password is incorrect');
            }
        }

        $admin->setPassword($input['password']);

        $this->repo->saveOrFail($admin);
    }
}
