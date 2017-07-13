<?php

namespace RZP\Models\Merchant;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Es as EsConst;
use RZP\Base\Common;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class EsRepository extends Base\EsRepository
{
    // --------------- Fields ------------------------------

    protected $fields = [
        Entity::ID,
        Entity::ORG_ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::PARENT_ID,
        Entity::ACTIVATED,
        Entity::ACTIVATED_AT,
        Entity::ARCHIVED_AT,
        Entity::SUSPENDED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $queryFields = [
        // TODO: Decide this.
    ];

    protected $merchantDetailFields = [
        DetailEntity::MERCHANT_ID,
        DetailEntity::STEPS_FINISHED,
        DetailEntity::ACTIVATION_PROGRESS,
        DetailEntity::SUBMITTED_AT,
        DetailEntity::UPDATED_AT,
    ];

    protected $groupFields = [
        Common::ID,
    ];

    protected $adminFields = [
        AdminEntity::ID,
        AdminEntity::NAME,
    ];

    // --------------- Getters -----------------------------

    public function getMerchantDetailFields()
    {
        return $this->merchantDetailFields;
    }

    public function getGroupFields()
    {
        return $this->groupFields;
    }

    public function getAdminFields()
    {
        return $this->adminFields;
    }

    // --------------- Query builders ----------------------

    //
    // Queries for admins and groups are build at once in buildQueryAdditional().
    // It is required for doing ACL filter.
    // Following methods with empty block are here so the default impl of query
    // builder doesn't get called for these fields in input.
    //

    public function buildQueryForAdmins(array & $query, string $value)
    {
    }

    public function buildQueryForGroups(array & $query, array $value)
    {
    }

    public function buildQueryForSubAccounts(array & $query, string $value)
    {
        if ($value === '1')
        {
            $filter = $this->getExistsQuery(Entity::PARENT_ID);
        }
        else
        {
            $filter = [EsConst::TERM => [Entity::PARENT_ID => $value]];
        }

        $this->addFilter($query, $filter);
    }

    public function buildQueryForSuspended(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, Entity::SUSPENDED_AT);
    }

    public function buildQueryForArchived(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, Entity::ARCHIVED_AT);
    }

    public function buildQueryForActivated(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, Entity::ACTIVATED_AT);
    }

    public function buildQueryForPending(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, 'merchant_details.submitted_at');

        $this->addNullFilterForField($query, Entity::ACTIVATED_AT);
    }

    public function buildQueryForDead(array & $query, string $value)
    {
        $this->addNullFilterForField($query, 'merchant_details.submitted_at');

        $dayBefore = Carbon::now()->subDays(1)->timestamp;

        $filter = [
            EsConst::RANGE => [
                Common::CREATED_AT => [
                    EsConst::LT => $dayBefore,
                ],
            ],
        ];

        $this->addFilter($query, $filter);
    }

    public function buildQueryAdditional(array & $query, array $params)
    {
        // Adds ACL filter using admins and groups values in $params.

        $this->addQueryForAcl($query, $params);

        // If $params doesn't have following two filters add filter
        // to only pick non-suspended and non-archived merchants always.

        $inactiveFilters = ['suspended', 'archived'];

        if (empty(array_intersect_key($inactiveFilters, array_keys($params))) === false)
        {
            $this->addQueryForActiveOnlyMerchants($query, $params);
        }
    }

    /**
     * Adds filter query using groups and admins value in $params.
     *
     * Builds new bool.should clause for matching either admins and
     * groups against document and then add this to existing filter.bool.must
     * list of clauses.
     *
     * @param array $query
     * @param array $params
     */
    protected function addQueryForAcl(array & $query, array $params)
    {
        $admins = $params[Entity::ADMINS] ?? null;
        $groups = $params[Entity::GROUPS] ?? [];

        $aclQuery = [];

        if (empty($admins) === false)
        {
            $this->addShould($aclQuery, [EsConst::TERM => [Entity::ADMINS => $admins]]);
        }

        if (empty($groups) === false)
        {
            $this->addShould($aclQuery, [EsConst::TERMS => [Entity::GROUPS => $groups]]);
        }

        if (empty($aclQuery) === false)
        {
            $this->addFilter($query, $aclQuery);
        }
    }

    protected function addQueryForActiveOnlyMerchants(array & $query)
    {
        $this->addNullFilterForField($query, Entity::SUSPENDED_AT);
        $this->addNullFilterForField($query, Entity::ARCHIVED_AT);
    }
}
