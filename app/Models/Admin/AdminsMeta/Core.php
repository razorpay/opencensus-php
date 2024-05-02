<?php

namespace RZP\Models\Admin\AdminsMeta;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Action;
use RZP\Exception\RuntimeException;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{

    /**
     * create admin for the org
     *
     * @param OrgEntity $org
     * @param array $input
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws RuntimeException
     */
    public function create(Org\Entity $org, array $input): array
    {

        $admin = (new AdminEntity())->generateId();

        $admin->setAuditAction(Action::CREATE_ADMIN);

        $admin->org()->associate($org);

        $admin->build($input);

        // Validate the admin email should be unique for the org
        $existingAdmin = $this->repo->admin->findByOrgIdAndEmail($org->getId(), $admin->getEmail());
        if ($existingAdmin !== null) {
            throw new Exception\BadRequestValidationFailureException(
                "admin email should be unique value");
        }

        // order of arg is important for diff to be stored in ES
        // This is done to apply eloquent casts to input
        $dirtyData = array_merge($input, $admin->toArray());

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $admin->getId(), $org->getId(), [AdminEntity::ROLES, AdminEntity::GROUPS]);

        return $admin->toArrayPublic();
    }

    /**
     * create admins meta
     *
     * @param array $input
     * @return array
     */
    public function createAdminsMeta(array $input): array
    {

        $adminsMeta = (new Entity())->generateId();

        $adminsMeta->build($input);

        $this->repo->saveOrFail($adminsMeta);

        // bank's employee id is used as unique identifier
        $id = $adminsMeta[Entity::UNIQUE_IDENTIFIER];

        $adminsMeta = $this->repo->admins_meta->fetchByUniqueIdentifierOrFail($id);

        return $adminsMeta->toArrayPublic();
    }

    /**
     * method to associate relevant entities to admin
     *
     * @param AdminEntity $admin
     * @param array $input
     * @return void
     * @throws RuntimeException
     */
    protected function associateRelevantEntitiesToAdmin(AdminEntity $admin, array $input): void
    {
        if (isset($input[AdminEntity::ROLES]) === true) {
            $this->repo->role->validateExists($input[AdminEntity::ROLES]);

            $this->repo->sync($admin, AdminEntity::ROLES, $input[AdminEntity::ROLES]);
        }
    }

}
