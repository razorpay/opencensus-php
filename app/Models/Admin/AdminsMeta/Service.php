<?php

namespace RZP\Models\Admin\AdminsMeta;

use Throwable;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\AdminsMeta\Entity as AdminsMetaEntity;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->adminOrgId = $this->app['basicauth']->getAdminOrgId();
    }

    // --------------------- CRUD for Org Admins  -----------------------------------------

    /**
     * method to create org admin
     *
     * @param array $input
     * @return array
     * @throws Throwable
     */
    public function createOrgAdmin(array $input): array
    {

        $this->trace->info(TraceCode::ORG_ADMIN_CREATE_REQUEST, [
            'method_name'       => __FUNCTION__,
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
            'unique_identifier' => $input['unique_identifier'],
        ]);

        // validating input for create request
        (new Validator())->validateInput('org_admin_create', $input);

        // transform input data since bank sending in diff format
        $transformedInput = $this->transformInputData($input);

        // fetch the org with org_id
        $org = $this->repo->org->find($this->adminOrgId);

        // throw exception if org not found
        if ($org === null) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_IDAM_ORG_NOT_FOUND);
        }

        // verify roles and strip sign from roles
        if (empty($input[Entity::ROLES]) === false) {
            Role\Entity::verifyIdAndStripSignMultiple($transformedInput[Entity::ROLES]);
        }

        // start transaction between admin and admins_meta table
        $response = $this->repo->transactionOnLiveAndTest(function () use ($transformedInput, $org) {

            $adminInput = $this->prepareAdminCreateData($transformedInput);

            $admin = $this->core()->create($org, $adminInput);

            $adminId = $admin[Entity::ID];

            $adminsMetaInput = $this->prepareAdminsMetaCreateData($adminId, $transformedInput);

            $adminsMeta = $this->core()->createAdminsMeta($adminsMetaInput);

            return $this->buildResponse($admin, $adminsMeta);
        });

        $this->trace->info(TraceCode::ORG_ADMIN_CREATE_SUCCESS, [
            'unique_identifier' => $input['unique_identifier'],
            'method_name'       => __FUNCTION__,
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
        ]);

        return $response;
    }

    /**
     * method to fetch org admin by unique identifier
     *
     * @param string $id
     * @return array
     * @throws Throwable
     */
    public function getOrgAdmin(string $id): array
    {

        try {

            $this->trace->info(TraceCode::ORG_ADMIN_FETCH_REQUEST, [
                'unique_identifier' => $id,
                'method_name'       => __FUNCTION__,
                'route_name'        => $this->app['api.route']->getCurrentRouteName(),
            ]);

            // fetch admin meta by unique identifier
            $adminsMeta = $this->repo->admins_meta->fetchByUniqueIdentifierOrFail($id);

            $adminId = $adminsMeta[AdminsMetaEntity::ADMIN_ID];

            // fetch admin by id and org_id
            $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
                $adminId, $this->adminOrgId, [Entity::ROLES, Entity::GROUPS]);

            $this->trace->info(TraceCode::ORG_ADMIN_FETCH_SUCCESS, [
                'admin_id'          => $adminId,
                'unique_identifier' => $id,
                'method_name'       => __FUNCTION__,
                'route_name'        => $this->app['api.route']->getCurrentRouteName(),
            ]);

            return $this->buildResponse($admin->toArrayPublic(), $adminsMeta->toArrayPublic());
        } catch (Throwable $e) {

            $this->trace->error(TraceCode::ORG_ADMIN_NOT_FOUND, [
                'unique_identifier' => $id,
                'method_name'       => __FUNCTION__,
                'route_name'        => $this->app['api.route']->getCurrentRouteName(),
                'error_message'     => $e->getMessage()
            ]);

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ADMIN_NOT_FOUND,
                null,
                [
                    'unique_identifier' => $id,
                    'error_message'     => $e->getMessage()
                ]
            );
        }
    }
    // --------------------- END CRUD for Org Admins --------------------------------------

    /**
     * method to transform input data since bank sending in different format
     *
     * @param array $input
     * @return array
     */
    protected function transformInputData(array $input): array
    {
        $input[Constant::NAME]      = $input[Constant::FULL_NAME];
        $input[Constant::ROLES]     = $input[Constant::USER_ROLES];
        $input[Entity::EXPIRED_AT]  = $input[Constant::EXPIRE_AT];

        unset($input[Constant::USER_ROLES]);
        unset($input[Constant::FULL_NAME]);
        unset($input[Constant::EXPIRE_AT]);

        return $input;
    }

    /**
     * method to prepare data for admin create since we need to remove some fields
     * auth_mode and unique_identifier are not required for admin create.
     *
     * @param array $input
     * @return array
     */
    protected function prepareAdminCreateData(array $input): array
    {
        unset($input[Constant::AUTH_MODE]);
        unset($input[Constant::UNIQUE_IDENTIFIER]);
        return $input;
    }

    /**
     * method to prepare data for admins_meta create since we need to remove some fields
     * email, username, name, roles and expired_at are not required for admins_meta create.
     *
     * @param string $adminId
     * @param array $input
     * @return array
     */
    protected function prepareAdminsMetaCreateData(string $adminId, array $input): array
    {

        $input[Constant::ADMIN_ID] = Entity::verifyIdAndStripSign($adminId);

        unset($input[Entity::EMAIL]);
        unset($input[Entity::USERNAME]);
        unset($input[Entity::NAME]);
        unset($input[Entity::ROLES]);
        unset($input[Entity::EXPIRED_AT]);

        return $input;
    }

    /**
     * method to build response
     *
     * @param array $admin
     * @param array $adminsMeta
     * @return array
     */
    protected function buildResponse(array $admin, array $adminsMeta): array
    {
        return [
            Constant::UNIQUE_IDENTIFIER   => $adminsMeta[Constant::UNIQUE_IDENTIFIER] ?? null,
            Constant::FULL_NAME           => $admin[Entity::NAME] ?? null,
            Constant::EMAIL               => $admin[Entity::EMAIL] ?? null,
            Constant::USER_ROLES          => array_map(fn($userRoles) => $userRoles[Constant::ID] ?? null,
                                                                   $admin[Entity::ROLES] ?? []),
            Constant::EXPIRE_AT           => $admin[Entity::EXPIRED_AT] ?? null,
            Constant::ACCOUNT_STATUS      => $admin[Entity::DISABLED] ? Constant::DISABLE : Constant::ENABLE,
            Constant::USER_DISABLED_AT    => $adminsMeta[Constant::USER_DISABLED_AT] ?? null,
            Constant::LAST_LOGIN_AT       => $admin[Entity::LAST_LOGIN_AT] ?? null,
            Constant::UPDATED_AT          => $adminsMeta[Constant::UPDATED_AT] ?? null,
        ];
    }

}
