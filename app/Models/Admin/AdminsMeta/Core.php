<?php

namespace RZP\Models\Admin\AdminsMeta;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Action;
use RZP\Exception\RuntimeException;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    protected static array $allowedEmailHosts = ['razorpay.com','axis.com', 'axisbank.com'];

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
        $email = $input[AdminEntity::EMAIL] ?? null;

        // Validate email host
        $this->validateEmailHost($email);

        $admin = (new AdminEntity())->generateId();

        $admin->setAuditAction(Action::CREATE_ADMIN);

        $admin->org()->associate($org);

        $admin->adminEntityBuildWithoutValidator($input);

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

    public function insertOrUpdateRoleMap(string $adminID, string $roleId)
    {
        $record = $this->repo->admins_meta->rolesFetchFirstByAdminId($adminID,$roleId);
        if ($record === null) {
            return $this->repo->admins_meta->insertRoleMap($roleId,$adminID);
        }
    }

    public function getOrgAdminDataRespByUID(string $uniqueIdentifier, string $orgId)
    {
        try{
            $fetchData = $this->repo->admins_meta->getOrgAdminData($uniqueIdentifier,$orgId);
            if($fetchData !== null){
                $rolesFetch = $this->repo->admins_meta->rolesFetchByOrgID($fetchData->org_admin_id);
                if(count($rolesFetch)>0){
                    foreach ($rolesFetch as $value){
                        $fetchData->user_roles[] =  "role_".$value->role_id;
                    }
                }
                $fetchData->account_status =  ($fetchData->account_status == 1) ? Constant::DISABLE : Constant::ENABLE;
                $this->trace->info(TraceCode::BANKING_ADMIN_UPDATE_REQUEST_ORG_ID,
                    [
                        'method' => 'banking_admin_update_request_log',
                        'ORG_ID' => $orgId,
                        'AD_ID'  => $uniqueIdentifier,
                        'User_Name' => $fetchData->full_name,
                        //'User_Email' => $fetchData->email,
                        'User_Roles' => $fetchData->user_roles,
                        'Account_Status' => $fetchData->account_status,
                        'Last_Login_Details' => $fetchData->last_login_at,
                        'Last_Modified_Details' => $fetchData->updated_at,
                        'Failed_Attempts' => $fetchData->failed_attempts,
                        'Last_Modified_Action' => 'Update',
                    ]);
                unset($fetchData->org_admin_id);
                unset($fetchData->failed_attempts);
                return $fetchData;
            } else{
                return throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ADMIN_NOT_FOUND);
            }
        }
        catch (\Throwable $e){
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'error_message'     => $e->getMessage()
                ]
            );
        }

    }

    /**
     * Validates the email host from the provided email address.
     *
     * @param string|null $email
     * @throws BadRequestValidationFailureException
     */
    protected function validateEmailHost(?string $email): void
    {
        if ($email !== null) {
            $emailHost = substr(strrchr($email, "@"), 1);

            if (!in_array($emailHost, static::$allowedEmailHosts)) {
                throw new BadRequestValidationFailureException(
                    'The email must be a valid email address from an allowed host.',
                    AdminEntity::EMAIL
                );
            }
        }
    }

}
