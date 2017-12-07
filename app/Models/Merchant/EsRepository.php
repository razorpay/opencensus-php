<?php

namespace RZP\Models\Merchant;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;

class EsRepository extends Base\EsRepository
{
    // --------------- Fields ------------------------------

    protected $indexedFields = [
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

    protected $merchantDetailIndexedFields = [
        DetailEntity::MERCHANT_ID,
        DetailEntity::STEPS_FINISHED,
        DetailEntity::ACTIVATION_PROGRESS,
        DetailEntity::ACTIVATION_STATUS,
        DetailEntity::ARCHIVED_AT,
        DetailEntity::SUBMITTED_AT,
        DetailEntity::UPDATED_AT,
    ];

    protected $groupIndexedFields = [
        Common::ID,
    ];

    protected $adminIndexedFields = [
        AdminEntity::ID,
        AdminEntity::NAME,
    ];

    protected $queryFields = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::BILLING_LABEL,
        Entity::WEBSITE,
        Entity::TAG_LIST,
        Entity::REFERRER,
    ];

    protected $esFetchParams = [
        self::QUERY,
        self::SEARCH_HITS,
        Entity::ORG_ID,
        Entity::GROUPS,
        Entity::ADMINS,
        Entity::ACCOUNT_STATUS,
        Entity::SUB_ACCOUNTS,
    ];

    // --------------- Getters -----------------------------

    public function getMerchantDetailIndexedFields()
    {
        return $this->merchantDetailIndexedFields;
    }

    public function getGroupIndexedFields()
    {
        return $this->groupIndexedFields;
    }

    public function getAdminIndexedFields()
    {
        return $this->adminIndexedFields;
    }

    // --------------- Query builders ----------------------

    //
    // Queries for admins and groups are build at once in buildQueryAdditional().
    // It is required for doing ACL filter.
    //
    // Following methods with empty block are here so the default impl of query
    // builder doesn't get called for these fields in input.
    //

    public function buildQueryForOrgId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::ORG_ID, $value);
    }

    public function buildQueryForAdmins(array & $query, array $value)
    {
    }

    public function buildQueryForGroups(array & $query, array $value)
    {
    }

    public function buildQueryForAccountStatus(array & $query, string $value)
    {
        $activationStatusAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::ACTIVATION_STATUS;

        //
        // For different value of account status (Refer AccountStatus.php)
        // we need to build query accordingly.
        //
        // E.g. for pending the logic is:
        //      (activation_status = under_review) OR
        //      (activation_status = needs_clarification AND archived_at IS NULL)
        //
        switch ($value)
        {
            case AccountStatus::ALL:

                break;

            case AccountStatus::SUSPENDED:

                $this->addNotNullFilterForField($query, Entity::SUSPENDED_AT);

                break;

            case AccountStatus::ARCHIVED:

                $this->addNotNullFilterForField($query, Entity::ARCHIVED_AT);

                break;

            case AccountStatus::ACTIVATED:

                $this->addNotNullFilterForField($query, Entity::ACTIVATED_AT);

                $this->addNullFilterForField($query, Entity::SUSPENDED_AT);
                $this->addNullFilterForField($query, Entity::ARCHIVED_AT);

                break;

            case AccountStatus::PENDING:

                $pendingQuery = [];

                $clause1 = $this->getTermQuery($activationStatusAttr, DetailStatus::UNDER_REVIEW);
                $clause2 = $this->getNeedsClarificationAndUnarchivedQuery();

                $this->addShould($pendingQuery, $clause1);
                $this->addShould($pendingQuery, $clause2);

                $this->addMust($query, $pendingQuery);

                break;

            case AccountStatus::PENDING_UNDER_REVIEW:

                $this->addMust($query, $this->getTermQuery($activationStatusAttr, DetailStatus::UNDER_REVIEW));

                break;

            case AccountStatus::PENDING_NEEDS_CLARIFICATION:

                $this->addMust($query, $this->getNeedsClarificationAndUnarchivedQuery());

                break;

            case AccountStatus::DEAD:

                $submittedAtAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::SUBMITTED_AT;

                $this->addNullFilterForField($query, $submittedAtAttr);
                $this->addNullFilterForField($query, Entity::SUSPENDED_AT);
                $this->addNullFilterForField($query, Entity::ARCHIVED_AT);

                // Additionally, Adds filter to get only merchants
                // which were created before yesterday.

                $dayBefore = Carbon::now(Timezone::IST)->subDays(1)->timestamp;

                $filter = [Es::RANGE => [Common::CREATED_AT => [Es::LT => $dayBefore]]];

                $this->addFilter($query, $filter);

                break;

            default:

                throw new \LogicException('Invalid value for account_status.');
        }
    }

    public function buildQueryForSubAccounts(array & $query, string $value)
    {
        if ($value === Repository::SUB_ACCOUNTS_ONLY_VALUE)
        {
            $filter = $this->getExistsQueryForField(Entity::PARENT_ID);
        }
        else
        {
            $filter = [Es::TERM => [Entity::PARENT_ID => $value]];
        }

        $this->addFilter($query, $filter);
    }

    public function buildQueryAdditional(array & $query, array $params)
    {
        $this->addQueryForAcl($query, $params);
    }

    /**
     * Adds filter query using GROUPS and ADMINS value in $params.
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
        $admins = $params[Entity::ADMINS] ?? [];
        $groups = $params[Entity::GROUPS] ?? [];

        $aclQuery = [];

        if (empty($admins) === false)
        {
            $this->addShould($aclQuery, [Es::TERMS => [Entity::ADMINS => $admins]]);
        }

        if (empty($groups) === false)
        {
            $this->addShould($aclQuery, [Es::TERMS => [Entity::GROUPS => $groups]]);
        }

        if (empty($aclQuery) === false)
        {
            $this->addFilter($query, $aclQuery);
        }
    }

    /**
     * Gets used in buildQueryForAccountStatus() method. Serves as query for
     * account_status=pending_needs_clarification and one clause for
     * account_status=pending.
     *
     * @return array
     */
    protected function getNeedsClarificationAndUnarchivedQuery(): array
    {
        $archivedAtAttr       = E::MERCHANT_DETAIL . '.' . DetailEntity::ARCHIVED_AT;
        $activationStatusAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::ACTIVATION_STATUS;

        $query = [];

        $clause1 = $this->getTermQuery($activationStatusAttr, DetailStatus::NEEDS_CLARIFICATION);
        $clause2 = $this->getExistsQueryForField($archivedAtAttr);

        $this->addMust($query, $clause1);
        $this->addMustNot($query, $clause2);

        return $query;
    }
}
