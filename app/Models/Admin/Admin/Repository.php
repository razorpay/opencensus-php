<?php

namespace RZP\Models\Admin\Admin;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;
use RZP\Base\ConnectionType;
use RZP\Models\Admin\Permission;
use Illuminate\Support\Facades\DB;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    protected $appFetchParamRules = [
        Entity::EMAIL => 'sometimes|email',
    ];

    // TODO: Deprecate this function
    // This function shouldn't be used anywhere in the code since 1 email can be used under different orgs
    // use findByOrgIdAndEmail or getAdminFromId based on usecase
    // store admin_id instead of emails for admin identification, in email id based flow, all operations must be done
    // in the context of the org
    public function findByEmail($email)
    {
        $email = strtolower($email);

        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->firstOrFailPublic();
    }

    public function getAdminFromId($id)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::ID, '=', $id)
                    ->firstOrFailPublic();
    }

    public function findByOrgIdAndEmail($orgId, $email, $relations = [])
    {
        $email = strtolower($email);

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::EMAIL, '=', $email)
                    ->with($relations)
                    ->first();
    }

    public function lockUnactivatedAccounts($timestamp)
    {
        return $this->newQuery()
                    ->whereNull(Entity::LAST_LOGIN_AT)
                    ->where(Entity::CREATED_AT, '<=', $timestamp)
                    ->update([
                        Entity::LOCKED => true,
                    ]);
    }

    public function lockUnusedAccounts($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::LAST_LOGIN_AT, '<=', $timestamp)
                    ->update([
                        Entity::LOCKED => true,
                    ]);
    }

    public function fetchByOrgIDAndEmailIDs($orgId, $emails)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
            ->orgId($orgId)
            ->whereIn(Entity::EMAIL, $emails)
            ->get();
    }

    public function adminsUpdateByAdminID(string $orgId, string $adminId, array $updatedFields)
    {
        return $this->newQuery()
            ->orgId($orgId)
            ->where(Entity::ID, '=', $adminId)
            ->update($updatedFields);
    }

    public function getOrgPermissionsList($orgId): array
    {
        return DB::table('permissions as p')
            ->join('permission_map as pm', 'p.id', '=', 'pm.permission_id')
            ->join('orgs as o', 'o.id', '=', 'pm.entity_id')
            ->select('p.id', 'p.name as permission_name', 'p.category', 'pm.entity_id as org_id', 'o.business_name as org_name')
            ->where('pm.entity_id', $orgId)
            ->where('pm.entity_type', 'org')
            ->get()->toArray();
    }

    public  function replicatePermissionsToOrg($insertData): bool
    {
        return DB::table('permission_map')->insert($insertData);
    }

    public function existingPermissionIds($toOrgId): array
    {
        return DB::table('permission_map')
            ->where('entity_type', 'org')
            ->where('entity_id', $toOrgId)
            ->pluck('permission_id')
            ->toArray();
    }
    
    public function fetchMerchantsWithAdminPivot($id)
    {
        $merchantIds = DB::table(Table::MERCHANT_MAP)
                        ->where('entity_id', '=', $id)
                        ->where('entity_type', '=', 'admin')
                        ->pluck('merchant_id')->toArray();

        $merchants =  (new Merchant\Repository)->findMerchantsByIds($merchantIds);
        return $this->addPivot($merchants, $id);
    }

    protected function addPivot(PublicCollection $merchants, string $adminId)
    {
        foreach ($merchants as $merchant) {
            $merchant["pivot"] = [
                "entity_type" => "admin",
                "entity_id" => $adminId,
                "merchant_id" => $merchant->getId()
            ];
        }
        return $merchants;
    }

}
