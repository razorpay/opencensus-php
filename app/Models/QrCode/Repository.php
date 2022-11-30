<?php

namespace RZP\Models\QrCode;

use Database\Connection;
use RZP\Constants\Environment;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Base\PublicCollection;
use Rzp\Models\Merchant;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;


class Repository extends Base\Repository
{
    protected $entity = 'qr_code';

    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::REFERENCE, '=', $merchantReference)
                    ->first();
    }

    public function determineLiveOrTestModeByMerchantReference($merchantReference)
    {
        $obj = $this->connection(Mode::LIVE)->findByMerchantReference($merchantReference);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $this->connection(Mode::TEST)->findByMerchantReference($merchantReference);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        //
        // We need to set connection to null
        // because it will be set to test if the
        // id is not found in any of the database.
        // So even if the db connection is later set
        // to live, query connection will be set to
        // test.
        //
        $this->connection(null);

        return null;
    }

    public function fetchQrCodesForMpanTokenization($count)
    {
        $mpanTokenized = $this->dbColumn(Entity::MPANS_TOKENIZED);

        $qrString = $this->dbColumn(Entity::QR_STRING);

        $provider = $this->dbColumn(Entity::PROVIDER);

        $createdAt = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
                     ->take($count)
                     ->where($provider, '=', 'bharat_qr')
                     ->whereNotNull($qrString)
                     ->whereNull($mpanTokenized)
                     ->where($createdAt, '>', 1552500000) // picking only after 13 mar 2019, as before this date 16 digit mc mpan was stored. https://github.com/razorpay/api/commit/34e9256fc94dc9c61e75f60b993f30a48ef48186
                     ->get();
    }

    public function findbyPublicIdAndMerchantAlsoWithTrash(
        string $id,
        Merchant\Entity $merchant,
        $withTrashed = true): PublicEntity
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        $query = $this->newQuery()
                      ->where(Entity::ID, $id)
                      ->where(Entity::MERCHANT_ID, $merchant->getId());

        $entity = $query->first();

        if (method_exists($entity, 'merchant') === true)
        {
            $entity->merchant()->associate($merchant);
        }

        return $entity;
    }

     public function fetchQrCodes(array $params,
                           string $merchantId = null,
                           string $connectionType = null): PublicCollection
     {
         // Process params (sanitization, validation, modification, etc.)
         $startTimeMs = round(microtime(true) * 1000);

         $this->processFetchParams($params);

         $expands = $this->getExpandsForQueryFromInput($params);

         $this->attachRoleBasedQueryParams($params);

         $query = $this->newQuery();

         if ($this->baseQuery !== null)
         {
             $query = $this->baseQuery;
         }

         $connection = null;

         $endTimeMs = round(microtime(true) * 1000);

         $queryDuration = $endTimeMs - $startTimeMs;

         if($queryDuration > 500) {
             $this->trace->info(TraceCode::BUILD_QUERY_RESPONSE_DURATION, [
                 'duration_ms' => $queryDuration,
             ]);
         }

         $startTimeMs = round(microtime(true) * 1000);

         if ((is_null($connectionType) === false) and
             ($this->app['env'] !== Environment::TESTING))
         {
             $connection = $this->getConnectionFromType($connectionType);

             $query = $this->newQueryWithConnection($connection);
         }

         $query = $query->with($expands);

         $this->addCommonQueryParamMerchantId($query, $merchantId);

         $endTimeMs = round(microtime(true) * 1000);

         $queryDuration = $endTimeMs - $startTimeMs;

         if($queryDuration > 500) {
             $this->trace->info(TraceCode::REPLICA_LAG_RESPONSE_DURATION, [
                 'duration_ms' => $queryDuration,
             ]);
         }
         $startTimeMs = round(microtime(true) * 1000);

         //Temporary fix for getting data from mysql instead of ES for QR v1 flow
         $mysqlParams = $params;

         $esParams = [];

         $startTimeMs = round(microtime(true) * 1000);

         // If above doesn't happen we build query for mysql fetch and return the
         // result.
         $query = $this->buildFetchQuery($query, $mysqlParams);

         //
         // For now, we want to expose this only for proxy auth.
         // We would want to expose this to private auth as well
         // in the future, but need a little bit though around
         // how we want to expose it. Pagination has lot of standards
         // generally and we might want to follow those when
         // exposing on private auth. SDKs _might_ have to fixed too.
         //

         if ($this->auth->isProxyAuth() === true)
         {
             $paginatedResult = $this->getPaginated($query, $params);

             $endTimeMs = round(microtime(true) * 1000);

             $queryDuration = $endTimeMs - $startTimeMs;

             if($queryDuration > 100) {
                 $this->trace->info(TraceCode::PAGINATED_RESPONSE_DURATION, [
                     'duration_ms'       => $queryDuration,
                     'query'             => $query->toSql(),
                     'merchantId'        => $merchantId,
                 ]);
             }

             return $paginatedResult;
         }

         $startTimeMs = round(microtime(true) * 1000);

         $entities = $query->get();

         $endTimeMs = round(microtime(true) * 1000);

         $queryDuration = $endTimeMs - $startTimeMs;

         if ($queryDuration > 500)
         {
             $this->trace->info(TraceCode::DATA_WAREHOUSE_RESPONSE_DURATION, [
                 'data_warehouse' => in_array($connection , Connection::DATA_WAREHOUSE_CONNECTIONS),
                 'connection'     => $connection,
                 'query_ctx'      => is_null($merchantId) ? 'admin' : 'merchant',
                 'duration_ms'    => $queryDuration,
                 'query'          => $query->toSql(),
                 'merchantId'     => $merchantId,
             ]);
         }

         return $entities;
     }
}
