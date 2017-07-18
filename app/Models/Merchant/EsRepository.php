<?php

namespace RZP\Models\Merchant;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Constants\Es as EsConst;
use RZP\Exception\LogicException;
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
        Entity::BILLING_LABEL,
        Entity::WEBSITE,
        Entity::PARENT_ID,
        Entity::ACTIVATED,
        Entity::ACTIVATED_AT,
        Entity::ARCHIVED_AT,
        Entity::SUSPENDED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $queryFields = [
        Entity::NAME,
        Entity::EMAIL,
        Entity::BILLING_LABEL,
        Entity::WEBSITE,
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

    protected $esOnlyFetchParams = [
        self::QUERY,
        self::SEARCH_HITS,
        Entity::GROUPS,
        Entity::ADMINS,
        Entity::ACCOUNT_STATUS,
        Entity::SUB_ACCOUNTS,
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

    public function buildQueryForAccountStatus(array & $query, string $value)
    {
        switch ($value)
        {
            case 'suspended':
            case 'archived':
            case 'activated':
                $this->addNotNullFilterForField($query, "{$value}_at");

                break;

            case 'pending':
                $this->addNotNullFilterForField($query, 'merchant_details.submitted_at');
                $this->addNullFilterForField($query, Entity::ACTIVATED_AT);

                break;

            case 'dead':
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

                break;

            default:

                throw new \LogicException();
        }
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

    public function buildQueryAdditional(array & $query, array $params)
    {
        $this->addQueryForAcl($query, $params);

        $this->addQueryForActiveOnlyMerchantsIfApplies($query, $params);
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

    protected function addQueryForActiveOnlyMerchantsIfApplies(
        array & $query,
        array $params)
    {
        // If account_status in query parameter is either of 'suspended' or
        // 'archived' then return else in all cases add filter to send
        // only active merchants.

        $accountStatus = $params[Repository::ACCOUNT_STATUS] ?? null;

        if (in_array($accountStatus, ['suspended', 'archived'], true) === true)
        {
            return;
        }

        $this->addNullFilterForField($query, Entity::SUSPENDED_AT);
        $this->addNullFilterForField($query, Entity::ARCHIVED_AT);
    }
}
