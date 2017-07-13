<?php

namespace RZP\Models\Merchant;

use Carbon\Carbon;

use RZP\Models\Base;
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
        DetailEntity::SUBMITTED,
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
            $filter = $this->getExistsQuery('parent_id');
        }
        else
        {
            $filter = ['term' => ['parent_id' => $value]];
        }

        $this->addFilter($query, $filter);
    }

    public function buildQueryForSuspended(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, 'suspended_at');
    }

    public function buildQueryForArchived(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, 'archived_at');
    }

    public function buildQueryForActivated(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, 'activated_at');
    }

    public function buildQueryForPending(array & $query, string $value)
    {
        $this->addNotNullFilterForField($query, 'merchant_details.submitted_at');
        $this->addNullFilterForField($query, 'activated_at');
    }

    public function buildQueryForDead(array & $query, string $value)
    {
        $this->addNullFilterForField($query, 'merchant_details.submitted_at');

        $dayBefore = Carbon::now()->subDays(1)->timestamp;

        $filter = [
            'range' => [
                'created_at' => [
                    'lt' => $dayBefore,
                ],
            ],
        ];

        $this->addFilter($query, $filter);
    }

    public function buildQueryAdditional(array & $query, array $params)
    {
        $this->addQueryForAcl($query, $params);

        // TODO: Add comment

        $inactiveFilters = ['suspended', 'archived'];

        if (empty(array_intersect_key($inactiveFilters, array_keys($params))) === false)
        {
            $this->addQueryForActiveOnlyMerchants($query, $params);
        }
    }

    /**
     * TODO: Add comment and correct annot.!
     *
     * @param  [type] $query  [description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    protected function addQueryForAcl(array & $query, array $params)
    {
        // Build new bool.should clause for admins and groups and add
        // that to existing filter.bool.must clause.

        $admins = $params[Entity::ADMINS] ?? null;
        $groups = $params[Entity::GROUPS] ?? [];

        $aclQuery = [];

        if (empty($admins) === false)
        {
            $this->addShould($aclQuery, ['term' => ['admins' => $admins]]);
        }

        if (empty($groups) === false)
        {
            $this->addShould($aclQuery, ['terms' => ['groups' => $groups]]);
        }

        if (empty($aclQuery) === false)
        {
            $this->addFilter($query, $aclQuery);
        }
    }

    protected function addQueryForActiveOnlyMerchants(array & $query)
    {
        $this->addNullFilterForField($query, 'suspended_at');
        $this->addNullFilterForField($query, 'archived_at');
    }
}
