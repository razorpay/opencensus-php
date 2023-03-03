<?php

namespace RZP\Models\Merchant;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;

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
        Entity::PARTNER_TYPE,
        Entity::ACTIVATED_AT,
        Entity::ARCHIVED_AT,
        Entity::SUSPENDED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
        Entity::ACTIVATION_SOURCE,
    ];

    protected $merchantDetailIndexedFields = [
        DetailEntity::MERCHANT_ID,
        DetailEntity::STEPS_FINISHED,
        DetailEntity::ACTIVATION_PROGRESS,
        DetailEntity::ACTIVATION_STATUS,
        DetailEntity::ARCHIVED_AT,
        DetailEntity::SUBMITTED_AT,
        DetailEntity::UPDATED_AT,
        DetailEntity::REVIEWER_ID,
        DetailEntity::BUSINESS_TYPE,
        DetailEntity::ACTIVATION_FLOW,
    ];

    protected $groupIndexedFields = [
        Common::ID,
    ];

    protected $adminIndexedFields = [
        AdminEntity::ID,
        AdminEntity::NAME,
    ];

    protected $balanceIndexedFields = [
        BalanceEntity::ID,
        BalanceEntity::MERCHANT_ID,
        BalanceEntity::BALANCE,
    ];

    protected $merchantBusinessDetailsIndexedFields = [
        BusinessDetailEntity::MIQ_SHARING_DATE,
        BusinessDetailEntity::TESTING_CREDENTIALS_DATE,
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
        Entity::PARTNER_TYPE,
        DetailEntity::REVIEWER_ID,
        Constants::INSTANT_ACTIVATION,
        Constants::BUSINESS_TYPE_BUCKET,
        Entity::ACTIVATION_SOURCE,
        Constants::TAGS,
        BusinessDetailEntity::MIQ_SHARING_DATE,
        BusinessDetailEntity::TESTING_CREDENTIALS_DATE,
    ];

    /**
     * By default we sort by descending created_at but in merchants listing case
     * if query was done for pending accounts we sort by ascending submitted_at.
     *
     * TODO: This approach can be made better.
     *
     * @var boolean
     */
    protected $sortBySubmittedAtAsc = false;

    /**
     * By default we sort by descending created_at but in instant activation listing case
     * we sort by descending order of balance , followed by ascending submitted_at.
     *
     * @var boolean
     */
    protected $sortByPendingBalance = false;

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

    public function getBalanceIndexedFields()
    {
        return $this->balanceIndexedFields;
    }

    public function getMerchantBusinessDetailsIndexedFields()
    {
        return $this->merchantBusinessDetailsIndexedFields;
    }

    // --------------- Query builders ----------------------

    //
    // Queries for admins and groups are build at once in buildQueryAdditional().
    // It is required for doing ACL filter.
    //
    // Following methods with empty block are here so the default impl of query
    // builder doesn't get called for these fields in input.
    //

    public function buildQueryForTags(array & $query, array $value)
    {
        foreach ($value as $tag)
        {
            $clause = [
                Es::MATCH => [
                    Entity::TAG_LIST    => $tag
                ]
            ];
            $this->addMust($query, $clause);
        }
    }

    public function buildQueryForOrgId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::ORG_ID, $value);
    }

    public function buildQueryForActivationSource(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::ACTIVATION_SOURCE, $value);
    }

    public function buildQueryForPartnerType(array &$query, string $value)
    {
        if ($value === 'all')
        {
            $this->addNotNullFilterForField($query, Entity::PARTNER_TYPE);
        }
        else
        {
            $this->addTermFilter($query, Entity::PARTNER_TYPE, $value);
        }
    }

    public function buildQueryForAdmins(array & $query, array $value)
    {
    }

    public function buildQueryForGroups(array & $query, array $value)
    {
    }

    public function buildQueryForReviewerId(array &$query, string $value)
    {
        $attribute = E::MERCHANT_DETAIL . '.' . DetailEntity::REVIEWER_ID;

        switch ($value)
        {
            case 'none':
                $this->addNullFilterForField($query, $attribute);
                break;

            default:
                $this->addMust($query, $this->getTermQuery($attribute, $value));
        }
    }

    public function buildQueryForAccountStatus(array & $query, string $value)
    {
        $activationStatusAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::ACTIVATION_STATUS;

        $archivedAtAttr       = E::MERCHANT_DETAIL . '.' . DetailEntity::ARCHIVED_AT;

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

            // To be removed; for backward compatibility
            case AccountStatus::ARCHIVED_OLD:

                $this->addNotNullFilterForField($query, Entity::ARCHIVED_AT);

                break;

            case AccountStatus::ARCHIVED:

                $this->addNotNullFilterForField($query, $archivedAtAttr);

                break;

            case AccountStatus::ACTIVATED:

                $this->addTermFilter($query, $activationStatusAttr, DetailStatus::ACTIVATED);

                $this->addNullFilterForField($query, Entity::SUSPENDED_AT);
                $this->addNullFilterForField($query, $archivedAtAttr);

                break;

            // To be removed; for backward compatibility
            case AccountStatus::PENDING_OLD:

                $submittedAtAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::SUBMITTED_AT;

                $this->addNotNullFilterForField($query, $submittedAtAttr);

                $this->addNullFilterForField($query, Entity::ACTIVATED_AT);
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

                $this->sortBySubmittedAtAsc = true;

                break;

            case AccountStatus::PENDING_UNDER_REVIEW:

                $this->addMust($query, $this->getTermQuery($activationStatusAttr, DetailStatus::UNDER_REVIEW));

                $this->sortBySubmittedAtAsc = true;

                break;

            case AccountStatus::PENDING_NEEDS_CLARIFICATION:

                $this->addMust($query, $this->getNeedsClarificationAndUnarchivedQuery());

                $this->sortBySubmittedAtAsc = true;

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

            case AccountStatus::INSTANTLY_ACTIVATED:

                $this->addTermFilter($query, $activationStatusAttr, DetailStatus::INSTANTLY_ACTIVATED);

                break;

            case AccountStatus::REJECTED:

                $this->addTermFilter($query, $activationStatusAttr, DetailStatus::REJECTED);

                $this->addNullFilterForField($query, $archivedAtAttr);

                break;

            default:

                throw new LogicException('Invalid value for account_status.');
        }
    }

    public function buildQueryForSubAccounts(array & $query, string $value)
    {
        if ($value === Repository::SUB_ACCOUNTS_ONLY_VALUE)
        {
            $this->addNotNullFilterForField($query, Entity::PARENT_ID);
        }
        else if ($value === Repository::SUB_ACCOUNTS_EXCLUDED_VALUE)
        {
            $this->addNullFilterForField($query, Entity::PARENT_ID);
        }
        else
        {
            $this->addTermFilter($query, Entity::PARENT_ID, $value);
        }
    }

    public function buildQueryAdditional(array & $query, array $params)
    {
        $this->addQueryForAcl($query, $params);
    }

    public function buildQueryForInstantActivation(array & $query, bool $value)
    {
        $attribute = E::MERCHANT_DETAIL . '.' . DetailEntity::ACTIVATION_FLOW;

        if ($value === true)
        {
            $this->sortByPendingBalance = true;

            $this->addTermFilter($query, $attribute, ActivationFlow::WHITELIST);
        }
        else
        {
            $this->addNegativeTermFilter($query, $attribute, ActivationFlow::WHITELIST);
        }
    }

    public function buildQueryForBusinessTypeBucket(array & $query, string $value)
    {
        $attribute = E::MERCHANT_DETAIL . '.' . DetailEntity::BUSINESS_TYPE;

        $unregisteredBusiness = BusinessType::getIndexForUnregisteredBusiness();

        switch ($value)
        {
            case BusinessType::UNREGISTERED :

                $this->addTermsFilter($query, $attribute, $unregisteredBusiness);

                break;
            default :

                $this->addNegativeTermsFilter($query, $attribute, $unregisteredBusiness);
        }
    }

    public function buildQueryForMIQSharingDate(array & $query, string $value)
    {
        $attribute = E::MERCHANT_BUSINESS_DETAIL . '.' . BusinessDetailEntity::MIQ_SHARING_DATE;

        $this->addTermFilter($query, $attribute, $value);
    }

    public function buildQueryForTestingCredentialsDate(array & $query, string $value)
    {
        $attribute = E::MERCHANT_BUSINESS_DETAIL . '.' . BusinessDetailEntity::TESTING_CREDENTIALS_DATE;

        $this->addTermFilter($query, $attribute, $value);
    }

    /**
     * {@inheritDoc}
     *
     * In case of account_status being sent as any of pending variations, we
     * set sortBySubmittedAtAsc as true and override the sort parameter of
     * query building.
     *
     * In case of instant activation  , we set sortByPendingBalance as true
     * and override the sort parameter of query building.
     *
     * @return array
     */
    public function getSortParameter(): array
    {
        if ($this->sortByPendingBalance === true)
        {
            return $this->getSortParameterForSortByBalance();
        }

        if ($this->sortBySubmittedAtAsc === false)
        {
            return parent::getSortParameter();
        }

        $submittedAtAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::SUBMITTED_AT;

        return [
            Es::_SCORE => [
                Es::ORDER => Es::DESC,
            ],
            $submittedAtAttr => [
                Es::ORDER => Es::ASC,
            ],
        ];
    }

    /**
     * returns sort parameters for instant activation case
     * sort by merchant balance in descending order , followed by ascending order of submitted at
     *
     * @return array
     */
    private function getSortParameterForSortByBalance(): array
    {
        $submittedAtAttr = E::MERCHANT_DETAIL . '.' . DetailEntity::SUBMITTED_AT;
        $balanceAttr     = BalanceEntity::BALANCE;

        return [
            Es::_SCORE       => [
                Es::ORDER => Es::DESC,
            ],
            $balanceAttr     => [
                Es::ORDER => Es::DESC,
            ],
            $submittedAtAttr => [
                Es::ORDER => Es::ASC,
            ],
        ];
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

    /**
     *
     * {@inheritDoc}
     *
     * @return string
     */
    public function getFromAndToQueryAttribute(): string
    {
        return E::MERCHANT_DETAIL . '.' . DetailEntity::SUBMITTED_AT;
    }
}
