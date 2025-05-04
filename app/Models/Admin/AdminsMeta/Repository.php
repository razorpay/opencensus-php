<?php

namespace RZP\Models\Admin\AdminsMeta;

use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Org;
use RZP\Constants\Timezone;
use Illuminate\Support\Facades\DB;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{

    protected $entity = 'admins_meta';

    protected array $appFetchParamRules = [
        Entity::UNIQUE_IDENTIFIER => 'sometimes|string',
    ];

    public function fetchByUniqueIdentifierOrFail(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::UNIQUE_IDENTIFIER, '=', $id)
                    ->firstOrFailPublic();
    }
    public function fetchAdminIDByUniqueIdentifier(string $uniqueIdentifier)
    {
        return $this->newQuery()
            ->where(Entity::UNIQUE_IDENTIFIER, '=', $uniqueIdentifier)
            ->select(Entity::ADMIN_ID)->first();
    }

    public function updateUserDisabledAtField(string $uniqueIdentifier)
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();
        return $this->newQuery()
            ->where(Entity::UNIQUE_IDENTIFIER, '=', $uniqueIdentifier)
            ->update(array(Entity::USER_DISABLED_AT => $now));
    }

    public function getMultipleDataByOrgIDAndTimestamp($startDay, $endDay, $adminOrgId): array
    {

        return DB::table('admins')
            ->join('admins_meta', 'admins.id', '=', 'admins_meta.admin_id')
            ->where('admins.updated_at', '>=', $startDay)
            ->where('admins.updated_at', '<=', $endDay)
            ->where('admins.org_id', $adminOrgId)
            ->select('admins_meta.unique_identifier','admins_meta.admin_id as org_admin_id','admins.name as full_name','admins.email','admins.disabled as account_status','admins.expired_at as expire_at','admins.last_login_at','admins.updated_at','admins_meta.user_disabled_at')
            ->get()->toArray();

    }

    public function rolesFetchByOrgID($orgAdminId):array
    {
        return DB::table('role_map')
        ->where('role_map.entity_id', $orgAdminId)
        ->select('role_map.role_id')
        ->get()->toArray();
    }
    public function getAdminDetailsByUID(string $adminId, string $orgId)
    {
        return DB::table('admins')
        ->where('id', $adminId)
        ->where('org_id', $orgId)
        ->select('id as admin_id','name','email','expired_at')->first();
    }
    public function getAdminExpUpdateByAdminID(string $adminId, int $expireAt):int
    {
        return DB::table('admins')
        ->where('id', $adminId)
        ->update(array('expired_at' => $expireAt));
    }

    public function getAdminDisabledUpdateByAdminID(string $adminId, bool $status):int
    {
        return DB::table(Table::ADMIN)
            ->where(AdminEntity::ID, $adminId)
            ->update(array(
                AdminEntity::DISABLED => $status
            ));
    }

    public function getMetadataUpdateByAdminID(string $adminId, string $disabledReason, int $currentTimestamp):int
    {
        return $this->newQuery()
            ->where(Entity::ADMIN_ID, $adminId)
            ->update(array(
                Entity::USER_DISABLED_AT    => $currentTimestamp,
                Entity::DISABLED_REASON     => $disabledReason // Update reason
            ));
    }

    public function adminsUpdateByAdminID(string $adminId, array $dataArr):int
    {
        return DB::table('admins')->where('id', $adminId)->update($dataArr);
    }

    public function getOrgAdminData(string $uniqueIdentifier, $orgId)
    {
        return DB::table('admins_meta')
        ->join('admins', 'admins_meta.admin_id', '=', 'admins.id')
        ->where('admins_meta.unique_identifier', $uniqueIdentifier)
        ->where('admins.org_id', $orgId)
        ->select('admins_meta.unique_identifier','admins_meta.admin_id as org_admin_id','admins.name as full_name','admins.email','admins.disabled as account_status','admins.expired_at as expire_at','admins.last_login_at','admins.updated_at','admins_meta.user_disabled_at','admins.failed_attempts')
        ->first();
    }

    public function rolesFetchFirstByAdminId(string $adminId, string $roleId)
    {
        return DB::table('role_map')
            ->where('role_id', $roleId)
            ->where('entity_id', $adminId)
            ->first();
    }

    public function insertRoleMap(string $roleId, string $adminId):bool
    {
        return DB::table('role_map')->insert(array('role_id' => $roleId,'entity_id' => $adminId, 'entity_type'=>'admin'));
    }
    public function deleteRoleMap(array $arrIndex):bool
    {
        return DB::table('role_map')->whereIn('role_id', $arrIndex)->delete();
    }

    public function fetchByAdminIdAndUniqueIdentifier(string $id, string $uid)
    {
        return $this->newQuery()
            ->where(Entity::ADMIN_ID, '=', $id)
            ->where(Entity::UNIQUE_IDENTIFIER, '=', $uid)
            ->first();
    }

    public function fetchAxisAdminMetaData($admin)
    {
        $adminsMetaAdminId = $this->dbColumn(Entity::ADMIN_ID);
        $adminsMetaAuthMode = $this->dbColumn(Entity::AUTH_MODE);
        $disabledReason = $this->dbColumn(Entity::DISABLED_REASON);

        return $this->newQuery()
            ->where($adminsMetaAdminId, '=', $admin[Entity::ID])
            ->get([$adminsMetaAuthMode, $disabledReason])
            ->first();
    }

    public function disableDormantAdminsAndUpdateMeta($dormancyPeriod): array
    {
        $adminsUpdated = [];

        $adminsNotUpdated = [];

        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        // Fetch admin ids eligible for disabling based on dormancy period
        $adminIds = $this->fetchDormantAdmins($dormancyPeriod);

        $this->trace->info(TraceCode::ORG_ADMIN_UPDATE_REQUEST,
            [
                'admins_ids'               => $adminIds,
                'dormancy_period'          => $dormancyPeriod,
                'deactivation_reason'      => Constant::IDAM_DORMANCY,
                'deactivation_time'        => $currentTimestamp,
                'account_status_disabled'  => true,
            ]
        );

        foreach ($adminIds as $adminId)
        {
            $affectedRow = null;

            try
            {
                // Execute updates inside a database transaction to ensure atomicity
                $affectedRow = DB::transaction(function () use ($adminId, $currentTimestamp)
                {
                    // Add expiry reason to identify users deactivated based on dormancy
                    $disabledReason = Constant::IDAM_DORMANCY;

                    return $this->getMetadataUpdateByAdminID($adminId, $disabledReason, $currentTimestamp) and
                        $this->getAdminDisabledUpdateByAdminID($adminId, true);
                });

                if(isset($affectedRow) === true)
                {
                    $adminsUpdated[] = $adminId;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->info(TraceCode::ORG_ADMIN_UPDATE_FAILURE,
                    [
                        'admin_id'          => $adminId,
                        'exception'         => $ex->getMessage(),
                    ]
                );

                $adminsNotUpdated[] = $adminId;
            }

        }

        $this->trace->info(TraceCode::ORG_ADMIN_UPDATE_RESPONSE,
            [
                'success'          => $adminsUpdated,
                'failure'          => $adminsNotUpdated,
            ]
        );

        // return the count of updated or non-updated admins
        return [count($adminsUpdated), count($adminsNotUpdated)];
    }

    public function fetchDormantAdmins(int $dormancyPeriod): array
    {
        // Define the necessary table and column names

        $admin = $this->repo->admin;

        $timestamp = Carbon::now()->subDays($dormancyPeriod)->timestamp;

        $adminTable = $admin->getTableName();

        $adminId = $admin->dbColumn(AdminEntity::ID);

        $adminLastLoginAt = $admin->dbColumn(AdminEntity::LAST_LOGIN_AT);

        $adminCreatedAt = $admin->dbColumn(AdminEntity::CREATED_AT);

        $adminDeletedAt = $admin->dbColumn(AdminEntity::DELETED_AT);

        $adminDisabled = $admin->dbColumn(AdminEntity::DISABLED);

        $orgId = $admin->dbColumn(Entity::ORG_ID);

        $adminsMetaAdminId = $this->dbColumn(Entity::ADMIN_ID);

        $authMode = $this->dbColumn(Entity::AUTH_MODE);

        // Create the query to join tables and get records
        return $this->newQuery()
            ->join($adminTable, $adminId, '=', $adminsMetaAdminId)
            ->where($orgId, '=', Org\Entity::AXIS_ORG_ID)
            ->where($authMode, '=', Org\AuthType::ADFS)
            ->where($adminDisabled, '=', false)
            ->whereNull($adminDeletedAt)
            ->where(function ($query) use ($adminLastLoginAt, $adminCreatedAt, $timestamp)
            {
                $query->whereRaw("($adminLastLoginAt IS NOT NULL AND $adminLastLoginAt <= ?)
                                    OR ($adminLastLoginAt IS NULL AND $adminCreatedAt <= ?)",
                                    [$timestamp, $timestamp]);
            })
            ->pluck(Entity::ADMIN_ID)
            ->toArray();
    }
}
