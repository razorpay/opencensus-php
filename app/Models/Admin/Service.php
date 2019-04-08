<?php

namespace RZP\Models\Admin;

use Cache;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Constants\AdminFetch;
use RZP\Models\GeoIP\Service as GeoIP;
use RZP\Models\{Base, Batch, Admin\Org};
use RZP\Reconciliator\ReconSummary\DailyReconStatusSummary;
use RZP\Models\Base\QueryCache\Constants as QueryCacheConstants;

class Service extends Base\Service
{
    public function getAllEntities($input)
    {
        $fields = AdminFetch::fields();

        $entities = AdminFetch::entities();

        // Fetching all entities and fill them with null
        $allEntities = array_fill_keys(Entity::getAllEntities(), null);

        $externalEntities = [];

        if ($this->app['basicauth']->getOrgType() === Org\Entity::RESTRICTED)
        {
            $entities = $this->getRestrictedEntities($entities);

            $allEntities = array_only($allEntities, AdminFetch::$restrictedEntities);
        }
        else
        {
            $externalEntities = AdminFetch::externalEntities();
        }

        $mergedEntities = array_merge($allEntities, $entities, $externalEntities);

        return [
            'version'   => 1,
            'fields'    => $fields,
            'entities'  => $mergedEntities
        ];
    }

    /**
     * Filter through the entities and return only the allowed entities
     * and their allowed attributes for restricted orgs
     *
     * @param  array $entities
     * @return array
     */
    protected function getRestrictedEntities(array $entities): array
    {
        // Only allow entities that are open to restricted orgs
        $entities = array_only($entities, AdminFetch::$restrictedEntities);

        // Filter select entity attributes open to restricted orgs
        array_walk($entities, function (&$entity, $name)
        {
            // Get allowed attributes from respective Fetch class
            $fetchClass = Entity::getEntityNamespace($name) . '\\' . 'Fetch';

            $accesses = constant($fetchClass . '::ADMIN_RESTRICTED_ACCESSES');

            $entity = array_only($entity, $accesses);
        });

        return $entities;
    }

    public function fetchEntityById(string $entity, string $id, array $input = []): array
    {
        $this->validateEntityTypeForRestrictedOrg($entity);

        $retEntity = $this->handleExternalEntity($entity, $input, $id);

        if (empty($retEntity) === false)
        {
            return $retEntity;
        }

        $entity = $this->fetchEntityByNameAndId($entity, $id, $input);

        return $entity->toArrayAdmin();
    }

    /**
     * Handle external entity fetch post validating for non-restricted org
     *
     * @param  string $entity
     * @param  array $input
     * @param  string|null $id
     *
     * @return null
     */
    protected function handleExternalEntity(string $entity, array $input, string $id = null)
    {
        // Check and handle external entities if non-restricted orgs
        if ($this->app['basicauth']->getOrgType() !== Org\Entity::RESTRICTED)
        {
            if (Entity::validateExternalServiceEntity($entity) === true)
            {
                $class = Entity::getExternalServiceClass($entity);

                $entityName = Entity::getExternalEntityName($entity);

                if (empty($id) === true)
                {
                    return $class->fetchMultiple($entityName, $input);
                }
                else
                {
                    return $class->fetch($entityName, $id, $input);
                }
            }
        }

        return null;
    }

    public function fetchTerminalEntityByIdWithFlag($entity, $id, $subMerchantFlag = false)
    {
        $entity = $this->fetchEntityByNameAndId($entity, $id);

        return $entity->toArrayAdmin($subMerchantFlag);
    }

    protected function fetchEntityByNameAndId(
        string $entity,
        string $id,
        array $input = []): Base\PublicEntity
    {
        Entity::validateEntityOrFailPublic($entity);

        $entityClass = Entity::getEntityClass($entity);

        $entityObject = new $entityClass;

        if ($entityObject->getIncrementing() === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

        $entity = $this->repo->$entity->findOrFailByPublicIdWithParams($id, $input);

        return $entity;
    }

    public function fetchMultipleEntities($entity, $input)
    {
        $this->validateEntityTypeForRestrictedOrg($entity);

        $entities = $this->handleExternalEntity($entity, $input);

        if (empty($entities) === false)
        {
            return $entities;
        }

        Entity::validateEntityOrFailPublic($entity);

        $entities = $this->repo->$entity->fetch($input);

        return $entities->toArrayAdmin();
    }

    protected function validateEntityTypeForRestrictedOrg(string $entity)
    {
        if ($this->app['basicauth']->getOrgType() === Org\Entity::RESTRICTED)
        {
            // Only allow entities that are open to restricted orgs
            if (in_array($entity, AdminFetch::$restrictedEntities, true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
            }
        }
    }

    public function sendTestNewsletter($input)
    {
        (new Validator)->validateInput('send_test_newsletter', $input);

        $mailer = new Newsletter(
            $input['subject'],
            $input['msg'],
            $input['template']
        );

        $mailer->setTestEmail($input['email']);

        return $mailer->send();
    }

    public function sendNewsletter($input)
    {
        (new Validator)->validateInput('send_newsletter', $input);

        $mailer = new Newsletter(
            $input['subject'],
            $input['msg'],
            $input['template']
        );

        $mailer->setRecipient($input['lists']);

        return $mailer->send();
    }

    /**
     * @param array $input
     * Eg. {"mid1" => {"on_demand": "14", "scheduled" : "15"}, "mid2" => {"on_demand": "12"}}
     *
     * @return array
     */
    public function setEarlySettlementPricingKeys(array $input): array
    {
        $this->trace->info(TraceCode::ES_PRICING_KEY_SET, $input);

        $mids = array_keys($input);

        $merchants = $this->repo->merchant->findMany($mids);

        $successMids = [];

        // 31st Dec 2018 end of day
        $defaultExpiry = Carbon::now(Timezone::IST)->endOfYear()->getTimestamp();

        $validator = (new Validator);

        foreach ($merchants as $merchant)
        {
            $mid = $merchant->getId();

            $data = $input[$mid];

            $validator->validateInput('set_es_pricing_key', $data);

            foreach ($data as $pricingType => $pricingValue)
            {
                $key = $mid . '_' . $pricingType . '_es_pricing';

                $pricing = round($pricingValue / 100, 2);

                Cache::put('espricing:' . $key, $pricing, $defaultExpiry);

                $this->trace->info(
                    TraceCode::ES_PRICING_MERCHANT_KEY_SET,
                    [
                        'mid'           => $mid,
                        'key'           => $key,
                        'value'         => $pricing,
                    ]);
            }

            $successMids[] = $mid;
        }

        $failedMids = array_diff($mids, $successMids);

        return ['success_mids' => $successMids, 'failed_mids' => $failedMids];
    }

    public function setConfigKeys(array $input): array
    {
        (new Validator)->validateInput('set_config_keys', $input);

        $result = [];

        foreach ($input as $key => $value)
        {
            $result[] = $this->setConfigKey($key, $value);
        }

        return $result;
    }

    /**
     * @param array $input
     * @return array
     *
     * Set a single redis key.
     *
     * Sample input:
     *      key=merchant_enach_configs
     *      path=auth_gateway.8XGbgY6OnlIm6z
     *      value=esigner_legaldesk
     *
     * Currently it supports the below key:
     *  - `merchant_enach_configs`
     *       - This is to set the merchant specific rule to select
     *         the esigner gateway for enach.
     *       - Supported values are `esigner_legaldesk` and `esigner_digio`
     *       - Sample content of this:
     *          {
     *              "auth_gateway": {
     *                  "override": "esigner_legaldesk",
     *                  "8XGbgY6OnlIm6z": "esigner_legaldesk"
     *              }
     *          }
     *       - Here, when "override" is set, all the merchants would be forcefully
     *         redirected to that specific gateway, by overriring the merchant specific
     *         configurations.
     */
    public function updateConfigKey(array $input): array
    {
        (new Validator)->validateInput('update_config_key', $input);

        $currentConfig = null;

        $currentConfig = $this->app['cache']->get($input['key'], []);

        $oldConfig = $currentConfig;

        array_set($currentConfig, $input['path'], $input['value']);

        $data = [
            'key'       => $input['key'],
            'old_value' => $oldConfig,
            'new_value' => $currentConfig,
        ];

        $this->app['cache']->forever($input['key'], $currentConfig);

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    /**
     * @param string $key
     * @param mixed $newValue
     *
     * @return array
     */
    protected function setConfigKey(string $key, $newValue): array
    {
        $oldValue = Cache::get($key);

        Cache::forever($key, $newValue);

        $data = [
            'key'       => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ];

        if (ConfigKey::isSensitive($key) === false)
        {
            $this->trace->info(TraceCode::REDIS_KEY_SET, $data);
        }

        return $data;
    }

    public function getConfigKeys(): array
    {
        $result = [];

        foreach (ConfigKey::PUBLIC_KEYS as $key)
        {
            $result[$key] = Cache::get($key);
        }

        return $result;
    }

    public function getConfigKey($input): array
    {
        (new Validator)->validateInput('get_config_key', $input);

        $key = $input['key'];

        $config = $this->app['cache']->get($key, []);

        return $config;
    }

    public function deleteConfigKey($input): array
    {
        (new Validator)->validateInput('delete_config_key', $input);

        $currentConfig = null;

        $currentConfig = $this->app['cache']->get($input['key'], []);

        $oldConfig = $currentConfig;

        array_forget($currentConfig, $input['path']);

        $data = [
            'key'       => $input['key'],
            'old_value' => $oldConfig,
            'new_value' => $currentConfig,
        ];

        $this->app['cache']->forever($input['key'], $currentConfig);

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    public function getQueryCacheCounts(): array
    {
        $result = [];

        $cacheEvents = [
            QueryCacheConstants::CACHE_HITS,
            QueryCacheConstants::CACHE_MISSES,
            QueryCacheConstants::CACHE_WRITES,
            QueryCacheConstants::CACHE_FLUSHES,
        ];

        foreach (Entity::CACHED_ENTITIES as $entity => $_)
        {
            $result[$entity] = [];

            foreach ($cacheEvents as $event)
            {
                $cacheKey = $entity . '_' . $event;

                $result[$entity][$event] = (intval(Cache::get($cacheKey)) ?? 0);
            }
        }

        return $result;
    }

    public function generateScorecard(array $input)
    {
        $data = (new Scorecard)->generateScorecard($input);

        return $data;
    }

    public function processMailgunCallback($type, $input)
    {
        $validator = new Validator;

        $validator->setStrictFalse();

        $validator->validateInput('mailgun_webhook', $input);

        return (new Mailgun)->processCallback($type, $input);
    }

    public function processSetCronJobCallback(array $input)
    {
        $this->trace->info(TraceCode::SETCRONJOB_CALLBACK, $input);
    }

    public function updateTaxColumnValue(string $entity, int $limit = 10000)
    {
        if (in_array($entity, [Entity::PAYMENT, Entity::TRANSACTION]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid entity: ' . $entity);
        }

        $count = $this->repo->$entity->updateTax($limit);

        return ['count' => $count];
    }

    public function updateGeoIps(array $input)
    {
        return (new GeoIP)->updateGeoIps($input);
    }

    public function updateMdr()
    {
        Jobs\MdrBackFill::dispatch($this->mode);

        return [
            'success' => true,
        ];
    }

    public function dbMetaDataQuery(array $input): array
    {
        return (new Query\Core)->dbMetaDataQuery($input);
    }

    public function fetchReconciliationSummary(array $input)
    {
        $data = (new DailyReconStatusSummary)->generateReconSummary($input);

        return $data;
    }

    public function createBatch(array $input)
    {
        $batchCore = new Batch\Core;

        $sharedMerchant = $this->repo->merchant->getSharedAccount();

        $batch = $batchCore->create($input, $sharedMerchant);

        return $batch->toArrayPublic();
    }

    public function uploadFile(string $type, array $input)
    {
        $fileCore = new File\Core;

        $admin = $this->auth->getAdmin();

        $fileCore->uploadFile($admin, $type, $input);

        return ['success' => true];
    }

    public function updateEntityBalanceIdInBulk(string $entity, array $input): array
    {
        assertTrue(
            in_array(strtolower($entity), Entity::ENTITIES_WITH_BALANCE_ID_COLUMN),
            "Entity not whitelisted for this bulk operation - $entity");

        $limit            = (int) ($input['limit'] ?? 10000);
        $merchantIds      = $input['merchant_ids'] ?? [];
        $merchantIdsLimit = (int) ($input['merchant_limit'] ?? 5);

        // If no merchant ids provided in input, query in limit set of unique mids where balance_id is null.
        if (empty($merchantIds) === true)
        {
            $merchantIds = $this->repo->$entity->getUniqueMerchantIdsWhereBalanceIdIsNull($merchantIdsLimit);
        }

        // If still no merchant ids, this means no rows pending updatation.
        if (empty($merchantIds) === true)
        {
            return [];
        }

        $merchants = $this->repo->merchant->findMany($merchantIds);

        if ($merchants->count() === 0)
        {
            return [];
        }

        $this->trace->info(
            TraceCode::ENTITY_BULK_UPDATE_BALANCE_ID_REQUEST,
            compact('entity', 'limit', 'merchantIds', 'merchantIdsLimit'));

        $failedMerchantIds           = [];
        $totalUpdatedRowCounts       = 0;
        $perMerchantUpdatedRowCounts = [];

        foreach ($merchants as $merchant)
        {
            $merchantId = $merchant->getId();
            $balanceId  = $merchant->primaryBalance->getId();

            try
            {
                $updatedRowCounts = $this->repo->$entity->bulkUpdateBalanceId($merchantId, $balanceId, $limit);

                $totalUpdatedRowCounts += $updatedRowCounts;
                $perMerchantUpdatedRowCounts[$merchantId] = $updatedRowCounts;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ENTITY_BULK_UPDATE_BALANCE_ID_ERROR,
                    compact('entity', 'merchantId', 'balanceId'));

                $failedMerchantIds[] = $merchantId;
            }
        }

        return compact(
            'merchantIds',
            'failedMerchantIds',
            'totalUpdatedRowCounts',
            'perMerchantUpdatedRowCounts');
    }

    public function setRedisKeys(array $input): array
    {
        (new Validator)->validateInput('set_redis_keys', $input);

        $redis = $this->app['redis']->connection('redis_labs');

        $result = [];

        foreach ($input as $key => $value)
        {
            $values = array_map(function($val) {
                return strtolower($val);
            }, $value);

            $values = array_change_key_case($values, CASE_LOWER);

            $result[] = $this->setRedisKey($redis, $key, $values);
        }

        return $result;
    }

    public function setRedisKey($redis, string $key, array $values): array
    {
        if(empty($values) === false)
        {
            $redis->HMSET($key, $values);
        }

        $data = [
            'key'   => $key,
            'value' => $values,
        ];

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    public function getRedisKey(array $input): array
    {
        (new Validator)->validateInput('get_redis_key', $input);

        $key = $input['key'];

        $redis = $this->app['redis']->connection('redis_labs');

        $values = $redis->HGETALL($key);

        $this->trace->info(TraceCode::REDIS_KEY_FETCH, $values);

        return $values;
    }

    public function updateRedisKeys($input): array
    {
        (new Validator)->validateInput('update_redis_keys', $input);

        $redis = $this->app['redis']->connection('redis_labs');

        $key = $input['key'];

        $values = array_map(function($val) {
            return strtolower($val);
        }, $input['value']);

        $values = array_change_key_case($values, CASE_LOWER);

        $existingValues = $redis->HGETALL($key);

        foreach ($existingValues as $existingKey => $existingValue)
        {
            $exists = false;

            foreach ($values as $inputKey => $inputValue)
            {
                if(strcasecmp($existingKey, $inputKey) === 0)
                {
                    $exists = true;

                    break;
                }
            }

            if($exists === false)
            {
                $redis->HDEL($key, $existingKey);
            }
        }

        $redis->HMSET($key, $values);

        $data = [
            'key'       => $key,
            'old_value' => $existingValues,
            'new_value' => $values,
        ];

        $this->trace->info(TraceCode::REDIS_KEY_UPDATE, $data);

        return $data;
    }
}
