<?php

namespace RZP\Models\Admin\AdminsMeta;

use Throwable;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Role;
use RZP\Models\BankingConfig;
use RZP\Models\Admin\Admin\Entity;
use RZP\Services\Dcs\Configurations;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
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

        if (str_ends_with($input[Constant::UNIQUE_IDENTIFIER], Constant::AXIS_BANK_EMAIL_DOMAIN) === false)
        {
            $input[Constant::UNIQUE_IDENTIFIER] .= Constant::AXIS_BANK_EMAIL_DOMAIN;
        }

        // transform input data since bank sending in diff format
        $transformedInput = $this->transformInputData($input);

        // fetch the org with org_id
        $org = $this->repo->org->find($this->adminOrgId);

        // throw exception if org not found
        if ($org === null) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_IDAM_ORG_NOT_FOUND);
        }

        // Check if unique_identifier already exists in admins_meta table
        $existingAdminMeta = $this->repo->admins_meta->fetchAdminIDByUniqueIdentifier($input[Constant::UNIQUE_IDENTIFIER]);
        if ($existingAdminMeta !== null) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_UNIQUE_IDENTIFIER);
        }

        // verify roles and strip sign from roles
        if (empty($input[Entity::ROLES]) === false) {
            Role\Entity::verifyIdAndStripSignMultiple($transformedInput[Entity::ROLES]);
        }

        // start transaction between admin and admins_meta table
        $response = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($transformedInput, $org) {

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
     * @throws BadRequestException
     */
    public function updateOrgAdmin(string $uniqueIdentifier, array $input)
    {
        (new Validator())->validateInput('get_update_admins', $input);

        try {

            if (str_ends_with($uniqueIdentifier, Constant::AXIS_BANK_EMAIL_DOMAIN) === false)
            {
                $uniqueIdentifier .= Constant::AXIS_BANK_EMAIL_DOMAIN;
            }

            $adminsMeta = $this->repo->admins_meta->fetchByUniqueIdentifierOrFail($uniqueIdentifier);

            $adminDetails = $this->repo->admin->findByIdAndOrgIdWithRelations(
                $adminsMeta->admin_id, $this->adminOrgId, [Entity::ROLES, Entity::GROUPS]);

            if($adminDetails === null){
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ADMIN_NOT_FOUND);
            }

            $adminId = $adminDetails->getId();
            $adminName = $adminDetails[Entity::NAME];

            // Roles Map Update
            if ($input[Constant::USER_ROLES] !== null && count($input[Constant::USER_ROLES]) > 0) {
                $this->rolesMapUpdate($input[Constant::USER_ROLES], $adminId);
            }

            // Expire Date Update
            if ($input[Constant::EXPIRE_AT] !== null) {
                $this->expireDateUpdate($input[Constant::EXPIRE_AT], $adminId);
            }

            // Admins Table Update
            $dataArr = array();
            if (isset($input[Constant::ACCOUNT_STATUS]) and !empty($input[Constant::ACCOUNT_STATUS])) {
                if ($input[Constant::ACCOUNT_STATUS] == Constant::DISABLE) {
                    $dataArr[Entity::DISABLED] = '1';
                    $this->repo->admins_meta->updateUserDisabledAtField($uniqueIdentifier);
                } else if ($input[Constant::ACCOUNT_STATUS] == Constant::ENABLE) {
                    $dataArr[Entity::DISABLED] = '0';
                }
            }

            $dataArr[Entity::NAME] = ($input[Constant::FULL_NAME] !== null) ? $input[Constant::FULL_NAME] : $adminName;

            $this->repo->admin->adminsUpdateByAdminID($this->adminOrgId, $adminId, $dataArr);

            return $this->core()->getOrgAdminDataRespByUID($uniqueIdentifier, $this->adminOrgId);

        } catch (Throwable $e) {
            $this->trace->error(TraceCode::ORG_ADMIN_NOT_FOUND, [
                'unique_identifier' => $uniqueIdentifier,
                'method_name'       => __FUNCTION__,
                'route_name'        => $this->app['api.route']->getCurrentRouteName(),
                'error_message'     => $e->getMessage()
            ]);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ADMIN_NOT_FOUND,
                null,
                [
                    'unique_identifier' => $uniqueIdentifier,
                    'error_message' => $e->getMessage()
                ]
            );
        }
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

            // Append @axisbank.com to unique_identifier as that is the format of unique_identifier in admins_meta table
            if (str_ends_with($id, Constant::AXIS_BANK_EMAIL_DOMAIN) === false)
            {
                $id .= Constant::AXIS_BANK_EMAIL_DOMAIN;
            }

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

    /**
     * method to disable admins based on their dormancy period.
     *
     * @return array
     * @throws BadRequestException
     */

    public function disableDormantOrgAdmins(): array
    {
        $dormancyPeriod = null;

        try {
            // Get dormancy period for axis IDAM org.
            $orgId = ORG_ENTITY::AXIS_ORG_ID;

            $dormancyPeriod = $this->getDormancyPeriodFromDCSConfig($orgId);

            // disable admins and update meta based on the dormancy period of axis org.
            [$adminDisabled, $adminNotDisabled] = $this->repo->admins_meta->disableDormantAdminsAndUpdateMeta($dormancyPeriod);

            // return response
            return [
                'success'       => $adminDisabled,
                'failure'       => $adminNotDisabled
            ];
        }
        catch (\Exception $e)
        {
            $failedDormantAdmins = [];

            if(isset($dormancyPeriod) === true)
            {
                $failedDormantAdmins = $this->repo->admins_meta->fetchDormantAdmins($dormancyPeriod);
            }

            $this->trace->error(
            TraceCode::BAD_REQUEST_ADMIN_DISABLED_CRON_FAILED,
                [
                    'failedDormantAdmins'   => $failedDormantAdmins,
                    'method_name'           => __FUNCTION__,
                    'route_name'            => $this->app['api.route']->getCurrentRouteName(),
                    'error_message'         => $e->getMessage()
                ]
            );

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ADMIN_DISABLED_CRON_FAILED,
                null,
                [
                    'error_message'         => $e->getMessage(),
                    'failedDormantAdmins'   => count($failedDormantAdmins)
                ]
            );
        }
    }

    public function getMultipleOrgAdmins(array $input)
    {
        (new Validator())->validateInput('get_multiple_admins', $input);

        $startDay = $input[Constant::START_DATE];
        $endDay   = $input[Constant::END_DATE];

        try {
            $fetchData = $this->repo->admins_meta->getMultipleDataByOrgIDAndTimestamp($startDay,$endDay,$this->adminOrgId);

            if(count($fetchData) === 0){
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_DATE_RANGE);
            }

            foreach ($fetchData as $key=>$val){
                $rolesFetch = $this->repo->admins_meta->rolesFetchByOrgID($val->org_admin_id);
                if(count($rolesFetch)>0){
                    foreach ($rolesFetch as $value){
                        $fetchData[$key]->user_roles[] =  "role_".$value->role_id;
                    }
                }
                $fetchData[$key]->account_status =  ($val->account_status === 1) ? Constant::DISABLE : Constant::ENABLE;
                unset($fetchData[$key]->org_admin_id);
            }

            return $fetchData;

        } catch (\Throwable $e){
            $this->trace->error(TraceCode::ORG_ADMIN_FETCH_REQUEST, [
                'start_date'    => $startDay,
                'end_date'      => $endDay,
                'method_name'   => __FUNCTION__,
                'route_name'    => $this->app['api.route']->getCurrentRouteName(),
                'error_message' => $e->getMessage()
            ]);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_DATE_RANGE,
                null,
                [
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
    private function rolesMapUpdate(array $userRoles, string $adminId):bool
    {
        // Roles Map Update
        $arr = [];
        $dbArr = $this->repo->admins_meta->rolesFetchByOrgID($adminId);
        foreach ($dbArr as $val){
            $arr[] = "role_".$val->role_id;
        }
        $collection = collect($arr);
        $diff = $collection->diff($userRoles);
        $getArrIndex = array_map(function($val) { return substr($val, 5); }, array_values($diff->all()));
        if(count($getArrIndex) > 0){
            $this->repo->admins_meta->deleteRoleMap($getArrIndex);
        }

        foreach ($userRoles as $value) {
            $roleId = substr($value,5);
            //update query on roles_map against entity_id(admin table id)
            $this->core()->insertOrUpdateRoleMap($adminId,$roleId);
        }
        return true;
    }
    public function expireDateUpdate(int $expireAt, string $adminId):bool
    {
        // Expire Date Update
        $date = Carbon::parse($expireAt);
        if ($date->isFuture() === true) {
            $this->repo->admins_meta->getAdminExpUpdateByAdminID($adminId,$expireAt);
            return true;
        } else {
            return false;
        }
    }


    /**
     * Retrieves the Dormancy period from DCS config stored at org level.
     *
     * @param $orgId
     * @return int|null Dormancy Period of User in days.
     */
    public function getDormancyPeriodFromDCSConfig($orgId): ?int
    {
        $field_name = Configurations\Constants::DormancyPeriod;

        $bankingConfigInput = [
            BankingConfig\Constants::FIELDS         => [$field_name],
            BankingConfig\Constants::ENTITY_ID      => $orgId,
            BankingConfig\Constants::KEY            => Configurations\Constants::$configurationsToDCSKeyMapping[$field_name],
            BankingConfig\Constants::SHORT_KEY      => $field_name
        ];

        $dormancyPeriod =  (new BankingConfig\Service())->getBankingConfig($bankingConfigInput);

        $response  = $dormancyPeriod[Configurations\Constants::DormancyPeriod] ?? null;

        if(isset($response) === false)
        {
            throw new \RuntimeException("Dormancy period not found for org ID: {$orgId}");
        }

        return $response;
    }

}
