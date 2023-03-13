<?php


namespace RZP\Models\TrustedBadge;

use Carbon\Carbon;
use Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'trusted_badge';

    public const STANDARD_CHECKOUT_ELIGIBLE_QUERY = <<<'EOT'
SELECT
  merchant_id
FROM
  hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1
WHERE
  count_successful >= 100
EOT;


    public const LOW_TRANSACTIONS_MERCHANTS_QUERY = <<<'EOT'
SELECT
  merchant_id,
  CAST(count_refunds AS double) / CAST(count_successful AS double) AS refund_rate
FROM
  hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1
WHERE
  count_successful > 50
  AND count_successful < 100
EOT;


    public const HIGH_TRANSACTING_VOLUME_MERCHANTS_QUERY = <<<'EOT'
SELECT
  merchant_id
FROM
  hive.aggregate_pa.rtb_eligibility_merchants_transactions_v1
WHERE
  gmv_in_lakhs >= 20
  AND count_successful < 100
EOT;

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query): void
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function fetchByMerchantId($merchantId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->merchantId($merchantId)->get()->first();
    }

    public function updateByMerchantId($merchantId, array $params): void
    {
        $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->update($params);
    }

    public function isMerchantLiveOnRTB($merchantId): bool
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->merchantId($merchantId)
            ->where(static function ($query)
            {
                $query->where(Entity::STATUS,'=', Entity::ELIGIBLE)
                    ->orWhere(Entity::STATUS, '=', Entity::WHITELIST);
            })
            ->where(Entity::MERCHANT_STATUS,'!=', Entity::OPTOUT);

        $merchantLiveOnRTB = $query->get()->first();

        return isset($merchantLiveOnRTB);
    }

    /**
     * This query is used to fetch merchants who are blacklisted or whitelisted. we need these merchants to
     * exclude them from the cron run so that their status doesn't get override by cron
     *
     * @return array
     */
    public function fetchRTBBlacklistedOrWhitelistedMerchantIds(): array
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::MERCHANT_ID)
            ->where(Entity::STATUS, Entity::BLACKLIST)
            ->orWhere(Entity::STATUS, Entity::WHITELIST)
            ->get();

        return $query->pluck(Entity::MERCHANT_ID)->toArray();
    }

    /**
     * This method fetches all merchants having >= 100 txns on standard checkout in last 4 months
     *
     * @param int $retryCount
     * @return array
     * @throws Exception
     */
    public function getStandardCheckoutEligibleMerchantsList(int $retryCount = 0): array
    {
        try
        {
            $rawQuery = self::STANDARD_CHECKOUT_ELIGIBLE_QUERY;

            $queryResult = $this->app['datalake.presto']->getDataFromDataLake($rawQuery);

            return array_column($queryResult,Entity::MERCHANT_ID);
        }
        catch(Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_DATALAKE_QUERY_FAILURE, [
                'query'      => 'standard_checkout_eligible_merchants_query',
                'retryCount' => $retryCount,
            ]);

            if($retryCount < 2)
            {
                return $this->getStandardCheckoutEligibleMerchantsList($retryCount+1);
            }
            throw $ex;
        }
    }

    /** This method fetches all mids and refund rate of merchants having txns greater than 25 and less than 100
     *
     * @param int $retryCount
     * @return mixed
     * @throws Exception
     */
    public function getLowTransactingMerchantsData(int $retryCount = 0)
    {
        try
        {
            $rawQuery = self::LOW_TRANSACTIONS_MERCHANTS_QUERY;

            return $this->app['datalake.presto']->getDataFromDataLake($rawQuery);
        }
        catch(Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_DATALAKE_QUERY_FAILURE, [
                'query'      => 'low_transacting_merchants_data_query',
                'retryCount' => $retryCount,
            ]);

            if($retryCount < 2)
            {
                return $this->getLowTransactingMerchantsData($retryCount+1);
            }
            throw $ex;
        }
    }

    /**
     * This method fetches merchants having less than 100 txns but have transaction volume greater than 20 lakhs
     *
     * @param int $retryCount
     * @return array
     * @throws Exception
     */
    public function getHighTransactingVolumeMerchantsList(int $retryCount=0): array
    {
        try
        {
            $rawQuery = self::HIGH_TRANSACTING_VOLUME_MERCHANTS_QUERY;

            $queryResult = $this->app['datalake.presto']->getDataFromDataLake($rawQuery);

            return array_column($queryResult, Entity::MERCHANT_ID);
        }
        catch(Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_DATALAKE_QUERY_FAILURE, [
                'query'      => 'high_transacting_volume_merchants_query',
                'retryCount' => $retryCount,
            ]);

            if($retryCount < 2)
            {
                return $this->getHighTransactingVolumeMerchantsList($retryCount+1);
            }
            throw $ex;
        }
    }
}
