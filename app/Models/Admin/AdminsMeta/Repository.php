<?php

namespace RZP\Models\Admin\AdminsMeta;

use Carbon\Carbon;
use RZP\Models\Admin\Base;
use RZP\Constants\Timezone;
use Illuminate\Support\Facades\DB;
use RZP\Models\Admin\Admin\Entity as AdminEntity;

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

}
