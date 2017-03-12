<?php

namespace RZP\Models\Merchant;

use Closure;
use RZP\Constants\Table;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\Pricing;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant';

    protected $sharedMerchant = null;

    protected $appFetchParamRules = array(
        Entity::ACTIVATED               => 'sometimes|boolean',
        Entity::HOLD_FUNDS              => 'sometimes|boolean',
        Entity::LIVE                    => 'sometimes|boolean',
        Entity::EMAIL                   => 'sometimes|string|max:255',
        Entity::PARENT_ID               => 'sometimes|string|size:14',
        Entity::CATEGORY                => 'sometimes|string|max:4',
        Entity::INTERNATIONAL           => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_ENABLED   => 'sometimes|boolean',
        Entity::METHODS                 => 'sometimes|string',
        Entity::PRICING_PLAN_ID         => 'sometimes|string',
        Entity::FEE_BEARER              => 'sometimes|in:platform,customer',
        Entity::FEE_MODEL               => 'sometimes|in:prepaid,postpaid',
        Entity::HOLD_FUNDS              => 'sometimes|in:0,1',
        Entity::RISK_RATING             => 'sometimes|integer|max:5|min:1',
    );

    public function getSharedAccount()
    {
        if ($this->sharedMerchant === null)
        {
            $this->sharedMerchant = $this->newQuery()
                                         ->where(Entity::ID, '=', Account::SHARED_ACCOUNT)
                                         ->firstOrFail();
        }

        return $this->sharedMerchant;
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return (new Pricing\Repository)->getPricingPlanById($pricing, true, true);
    }

    public function fetchMerchantsWithPositiveBalance()
    {
        return $this->newQuery()
                    ->whereHas('balance', function($q)
                    {
                        $q->where('balance', '>', 0);
                    })->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchRecentMerchants()
    {
        // 00:00 Today
        $today = \Carbon\Carbon::today("Asia/Kolkata")->timestamp;

        $start = \Carbon\Carbon::today("Asia/Kolkata")->subWeeks(3);

        return $this->newQuery()->whereBetween(Entity::CREATED_AT, [$start, $today]);
    }

    public function fetchBySettlementScheduleId($settlementScheduleIds)
    {
        if (is_array($settlementScheduleIds) === false)
        {
            $settlementScheduleIds = [$settlementScheduleIds];
        }

        return $this->newQuery()
                    ->whereNotNull(Entity::SETTLEMENT_SCHEDULE_ID)
                    ->whereIn(Entity::SETTLEMENT_SCHEDULE_ID, $settlementScheduleIds)
                    ->get();
    }

    public function getFewMerchantsWithNoCorrespondingMerchantSchedules()
    {
        $mercIds = $this->db->select(
            'SELECT DISTINCT id
             FROM merchants
                WHERE merchants.id NOT IN
                    (SELECT DISTINCT merchant_schedules.merchant_id
                     FROM merchants_schedules)
                LIMIT 1000');

        $mercIds = json_decode(json_encode($mercIds), true);

        $mercIds2  = [];
        foreach ($mercIds as $setlId)
        {
            $mercIds2[] = $setlId['id'];
        }

        return $this->newQuery()
                    ->whereIn(Entity::ID, $mercIds2)
                    ->get();
    }
    public function getCountOfMerchantsActivatedBetween($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::ACTIVATED_AT, [$from, $to])
                    ->count();
    }

    public function addQueryParamMethods($query, $params)
    {
        $query->join(
            $this->manager->methods->getTableName(),
            function ($join) use ($params)
            {
                $merchantId = $this->manager->merchant->getAttributeWithTableName(Merchant\Entity::ID);
                $methodsMerchantId = $this->manager->methods->getAttributeWithTableName(Methods\Entity::MERCHANT_ID);

                $methods = json_decode($params[Entity::METHODS], true);

                $join->on($methodsMerchantId, '=', $merchantId);

                foreach ($methods as $method => $value)
                {
                    $queryValue = null;

                    if ($value === 'true')
                        $queryValue = '1';
                    else if ($value === 'false')
                        $queryValue = '0';

                    $join->where($method, '=', $queryValue);
                }
            });

        $query->select($query->getModel()->getTable().'.*');
    }

    protected function addQueryParamFeeBearer($query, $params)
    {
        $feeBearer = $this->getAttributeWithTableName(Entity::FEE_BEARER);

        $query->where($feeBearer, '=', FeeBearer::getValueForBearerString($params[Entity::FEE_BEARER]));
    }

    protected function addQueryParamFeeModel($query, $params)
    {
        $feeModel = $this->getAttributeWithTableName(Entity::FEE_MODEL);

        $query->where($feeModel, '=', FeeModel::getValueForFeeModelString($params[Entity::FEE_MODEL]));
    }


    /**
     * Returns all the emails and names for all Merchants
     * No limits
     * @return [type] [description]
     */
    public function fetchAllMerchantContacts()
    {
        return $this->newQuery()
                    ->all(['name', 'email', 'transaction_report_email']);
    }

    public function fetchMerchantWhereTestBankIsNull()
    {
        return $this->newQueryWithConnection(Mode::TEST)
                    ->has('bankAccount', '<', 1)
                    ->get();
    }

    public function fetchAllLiveMerchants()
    {
        return $this->newQuery()
                    ->where(Entity::LIVE, '=', 1);
    }

    public function fetchMerchantFromEntity($entity)
    {
        if ($entity->hasRelation('merchant'))
        {
            return $entity->merchant;
        }

        $merchantId = $entity->getMerchantId();

        $merchant = $this->findOrFail($merchantId);

        $entity->merchant()->associate($merchant);

        return $merchant;
    }

    /**
     * Fetches merchant records which have features assigned in chunks of 200
     * records and passes that to the closure argument for processing
     * @param  Closure $processData Function to process the merchant records
     */
    public function fetchMerchantsWithoutFeatureEntries()
    {
        $merchantIds = $this->db->select(
           'SELECT DISTINCT id
            FROM merchants
            WHERE features IS NOT NULL
              AND merchants.id NOT IN
                (SELECT DISTINCT merchants.id
                 FROM merchants
                 JOIN features ON merchants.id = features.entity_id) LIMIT 200');

        $merchantIds = json_decode(json_encode($merchantIds), true);

        $merchantIds = array_map(function ($mid)
        {
            return $mid['id'];
        }, $merchantIds);

        return $this->newQuery()
                    ->whereIn(Entity::ID, $merchantIds)
                    ->get();
    }

    /**
     * Fetches the merchants with its relations (admin, groups)
     */
    public function findManyByIdsWithRelations(array $merchantIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $merchantIds)
                    ->with(['admins'])
                    ->get();
    }

    public function fetchMerchantsByOrgId($orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }

    public function fetchMerchantsByFilter(array $merchantIds, array $input)
    {
        $merchantCreatedAt = $this->manager->merchant->getAttributeWithTableName(Entity::CREATED_AT);
        $merchantUpdatedAt = $this->manager->merchant->getAttributeWithTableName(Entity::CREATED_AT);

        $merchantId = $this->manager
                           ->merchant_detail
                           ->getAttributeWithTableName(Merchant\Detail\Entity::MERCHANT_ID);

        $submittedAt = $this->manager
                            ->merchant_detail
                            ->getAttributeWithTableName(Merchant\Detail\Entity::SUBMITTED_AT);

        $stepsFinished = $this->manager
                              ->merchant_detail
                              ->getAttributeWithTableName(Merchant\Detail\Entity::STEPS_FINISHED);

        $activationProgress = $this->manager
                                   ->merchant_detail
                                   ->getAttributeWithTableName(Merchant\Detail\Entity::ACTIVATION_PROGRESS);

        $submitted = $this->manager
                          ->merchant_detail
                          ->getAttributeWithTableName(Merchant\Detail\Entity::SUBMITTED);

        $updatedAt = $this->manager
                          ->merchant_detail
                          ->getAttributeWithTableName(Merchant\Detail\Entity::UPDATED_AT);

        $query = $this->newQuery()
                      ->with('features')
                      ->select(Entity::ID,
                               Entity::NAME,
                               Entity::EMAIL,
                               Entity::ACTIVATED,
                               Entity::PARENT_ID,
                               $merchantCreatedAt,
                               $merchantUpdatedAt,
                               Entity::ARCHIVED_AT,
                               Entity::SUSPENDED_AT,
                               $stepsFinished,
                               $activationProgress,
                               $submitted,
                               $submittedAt,
                               $updatedAt)
                      ->join(Table::MERCHANT_DETAIL, Entity::ID, '=', $merchantId)
                      ->whereIn(Entity::ID, $merchantIds);

        switch (true)
        {
            case (empty($input['suspended']) === false):
                $query = $query->whereNotNull(Entity::SUSPENDED_AT);
                break;

            case (empty($input['archived']) === false):
                $query = $query->whereNotNull(Entity::ARCHIVED_AT);
                break;

            case (empty($input['activated']) === false):
                $query = $query->whereNotNull(Entity::ACTIVATED_AT)
                               ->whereNull(Entity::SUSPENDED_AT)
                               ->whereNull(Entity::ARCHIVED_AT);
                break;

            case (empty($input['pending']) === false):
                $query = $query->whereNull(Entity::ACTIVATED_AT)
                               ->whereNotNull($submittedAt)
                               ->whereNull(Entity::SUSPENDED_AT)
                               ->whereNull(Entity::ARCHIVED_AT);
                break;

            case (empty($input['dead']) === false):
                $query = $query->where($merchantCreatedAt, '<', time() - 24 * 7 * 3600)
                               ->whereNull($submittedAt)
                               ->whereNull(Entity::SUSPENDED_AT)
                               ->whereNull(Entity::ARCHIVED_AT);
                break;

            default:
                $query = $query->whereNull(Entity::ARCHIVED_AT)
                               ->whereNull(Entity::SUSPENDED_AT);
                break;
        }

        // Marketplace accounts filter
        if (empty($input['sub_accounts']) === false)
        {
            if ($input['sub_accounts'] === '1')
            {
                $query = $query->whereNotNull(Entity::PARENT_ID);
            }
            else
            {
                $query = $query->where(Entity::PARENT_ID, $input['sub_accounts']);
            }
        }

        return $query->get();
    }

    public function fetchByAccountIdAndMerchant(string $accountId, Entity $marketplace)
    {
        AccountEntity::verifyIdAndStripSign($accountId);

        $account =  $this->newQuery()
                         ->where(Entity::PARENT_ID, $marketplace->getId())
                         ->find($accountId);

        if ($account !== null)
        {
            $account->parent()->associate($marketplace);
        }

        return $account;
    }
}
