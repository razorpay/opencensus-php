<?php

namespace RZP\Models\Admin\Org;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Hostname;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Permission;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'org';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
        Entity::ALLOW_SIGN_UP         => 'sometimes|boolean',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
        Entity::ALLOW_SIGN_UP         => 'sometimes|boolean',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function getRazorpayOrg()
    {
        return $this->newQuery()
                    ->where(Entity::ID, '=', Entity::RAZORPAY_ORG_ID)
                    ->firstOrFail();
    }

    /**
     * Checks if any org with given org id exists in database.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function isValidOrg(string $id)
    {
        return $this->newQuery()->findOrFailPublic($id);
    }

    public function findOrFailByHostname(string $hostname)
    {
        // Collect different table names, and their columns to query on
        $hostname = mb_strtolower($hostname);
        // if org host has the pattern dashboard-.*.dev.razorpay.in -> dashboard.dev.razorpay.in
        //This is for the feature in devstack where the host name will be appended with label as a preview URL
        $isMatched = preg_match('/dashboard-(.*).dev.razorpay.in/', $hostname, $matches);

        if ($isMatched === 1)
        {
            $hostname = \RZP\Models\Admin\Org\Constants::DEVSERVE_HOST_URL ;
        }
        $orgId = $this->dbColumn(Entity::ID);
        $orgColumnNames = $this->dbColumn('*');

        $orgHostName = $this->repo->org_hostname;

        $orgHostnamesTable = $orgHostName->getTableName();

        $hostnameOrgId = $orgHostName->dbColumn(Hostname\Entity::ORG_ID);
        $hostnameAttr = $orgHostName->dbColumn(Hostname\Entity::HOSTNAME);

        // Join the orgs, and org_hostname table to get the org with the given hostname
        return $this->newQuery()
                    ->select($orgColumnNames)
                    ->join($orgHostnamesTable, $orgId, '=', $hostnameOrgId)
                    ->where($hostnameAttr, '=', $hostname)
                    ->firstOrFailPublic();
    }

    /**
     * Get all the orgs with enable_workflow=1 for the permission
     */
    public function getOrgsWithWorkflowEnabled(string $permissionId)
    {
        $orgAttrs = $this->dbColumn('*');
        $orgId = $this->dbColumn(Entity::ID);

        $pmTable = Table::PERMISSION_MAP;

        /*
            SELECT o.*
            FROM orgs o
            JOIN permission_map pm ON o.id = pm.entity_id
            WHERE pm.permission_id = $permissionId
                AND pm.entity_type = 'org'
                AND pm.enable_workflow = 1
        */

        return $this->newQuery()
                    ->select($orgAttrs)
                    ->join($pmTable, $orgId, '=', $pmTable . '.entity_id')
                    ->where($pmTable . '.permission_id', '=', $permissionId)
                    ->where($pmTable . '.entity_type', '=', 'org')
                    ->where($pmTable . '.enable_workflow', '=', 1)
                    ->get();
    }

    public function hasAnyOrgEnforced2Fa(array $orgIds): bool
    {
        if (empty($orgIds) === true)
        {
            return true;
        }

        return $this->newQuery()
                    ->whereIn(Entity::ID, $orgIds)
                    ->where(Entity::MERCHANT_SECOND_FACTOR_AUTH, '=', true)
                    ->limit(1)
                    ->count() > 0;
    }
}
