<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Trace\TraceCode;
use RZP\Base\ConnectionType;
use RZP\Models\Base;
use RZP\Models\Partner\Metric;

class Repository extends Base\Repository
{
    protected $entity = 'commission_invoice';

    const EXPAND_EACH          = 'expand.*';

    protected $proxyFetchParamRules = [
        Entity::ID          => 'filled|string|size:14',
        Entity::BALANCE_ID  => 'filled|string|size:14',
        Entity::MERCHANT_ID => 'filled|string|size:14',
        Entity::STATUS      => 'filled|string|custom',
        self::EXPAND_EACH   => 'filled|string|in:line_items,line_items.taxes',
    ];

    public function validateStatus($attribute, $status)
    {
        Status::validateStatus($status);
    }

    public function fetchInvoices(string $merchantId, int $month, int $year): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->get();
    }

    public function isProcessedInvoicePresentForPartner(string $partnerId) {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::SLAVE))
            ->where(Entity::MERCHANT_ID, '=', $partnerId)
            ->where(Entity::STATUS, '=', Status::PROCESSED)
            ->exists();
    }

    public function fetchMerchantIdsByInvoiceStatus(string $status, int $from) : array
    {
        $partners = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::SLAVE))
                         ->select(Entity::MERCHANT_ID)
                         ->where(Entity::STATUS, '=', $status)
                         ->where(Entity::CREATED_AT, '>', $from)
                         ->distinct()
                         ->get();

        return $partners->pluck(Entity::MERCHANT_ID)->toArray();
    }

    public function fetchIssuedInvoicesByMerchantId(string $merchantId, int $from) : Base\PublicCollection
    {
        return  $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::SLAVE))
                          ->where(Entity::MERCHANT_ID, '=', $merchantId)
                          ->where(Entity::STATUS, '=', Status::ISSUED)
                          ->where(Entity::CREATED_AT, '>', $from)
                          ->get();
    }

    public function fetchInvoiceIds($limit, $afterId = null)
    {
        $invoiceIdColumn = $this->dbColumn(Entity::ID);

        $query = $this->newQuery()->select($invoiceIdColumn);

        if (empty($limit) === false)
        {
            $query->limit($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where($invoiceIdColumn, '>', $afterId);
        }

        return $query->orderBy($invoiceIdColumn, 'asc')->get()->pluck(Entity::ID)->toArray();
    }

    public function fetchInvoiceIdsFromMerchantsIds(array $merchantIds)
    {
        $invoiceIdColumn = $this->dbColumn(Entity::ID);

        return $this->newQuery()
            ->select($invoiceIdColumn)
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    /**
     * fetches partner sub merchant MTUs count for given month from datalake.
     *
     * @param array  $partnerIds
     * @param string $month
     *
     * @return array
     * @throws \Throwable
     */
    public function fetchPartnerSubMtuCountFromDataLake(array $partnerIds, string $month): array
    {
        $partnerIdsString = implode("', '", $partnerIds);


        $rawQueryBuilder =<<<'EOT'
            SELECT partner_id,mtu_count FROM hive.aggregate_ba.partner_monthly_subM_mtus_count
            WHERE partner_id IN ('%s')
            AND month =('%s')
        EOT;

        $rawQuery = sprintf(
            $rawQueryBuilder,
            $partnerIdsString,
            $month
        );
        try
        {
            $timeStarted = millitime();
            $result = $this->app['datalake.presto']->getDataFromDataLake($rawQuery);
            $timeTaken = millitime()-$timeStarted;
            $this->trace->info(TraceCode::PARTNER_SUB_MTU_COUNT_FETCH_SUCCESS,
                               ['timeTaken'=>$timeTaken, 'result'=>$result, 'partnerIds'=>$partnerIds]);
            $this->trace->histogram(Metric::FETCH_PARTNER_SUB_MTU_COUNT_QUERY_TIME, $timeTaken);
            return $result;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNER_SUB_MTU_COUNT_FETCH_ERROR, ['partnerIds' => $partnerIds]);

            $this->trace->count(Metric::FETCH_PARTNER_SUB_MTU_COUNT_FAILED_TOTAL);

            throw $e;
        }
    }
}
