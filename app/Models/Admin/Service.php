<?php

namespace RZP\Models\Admin;

use Cache;
use Carbon\Carbon;

use RZP\Jobs;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Constants\AdminFetch;
use RZP\Models\GeoIP\Service as GeoIP;
use RZP\Models\Base\QueryCache\Constants as QueryCacheConstants;
use RZP\Reconciliator\ReconSummary\DailyReconStatusSummary;

class Service extends Base\Service
{
    public function getAllEntities($input)
    {
        $fields = AdminFetch::fields();
        $entities = AdminFetch::entities();
        $externalEntities = AdminFetch::externalEntities();

        // Fetching all entities and fill them with null
        $allEntities = array_fill_keys(Entity::getAllEntities(), null);

        $mergedEntities = array_merge($allEntities, $entities, $externalEntities);

        return [
            'version'   => 1,
            'fields'    => $fields,
            'entities'  => $mergedEntities
        ];
    }

    public function fetchEntityById(string $entity, string $id, array $input = []): array
    {
        if (Entity::validateExternalServiceEntity($entity) === true)
        {
            $class = Entity::getExternalServiceClass($entity);

            $entityName = Entity::getExternalEntityName($entity);

            return $class->fetch($entityName, $id, $input);
        }

        $entity = $this->fetchEntityByNameAndId($entity, $id, $input);

        return $entity->toArrayAdmin();
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
        if (Entity::validateExternalServiceEntity($entity) === true)
        {
            $class = Entity::getExternalServiceClass($entity);

            $entityName = Entity::getExternalEntityName($entity);

            return $class->fetchMultiple($entityName, $input);
        }

        Entity::validateEntityOrFailPublic($entity);

        $entities = $this->repo->$entity->fetch($input);

        return $entities->toArrayAdmin();
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

                Cache::put($key, $pricing, $defaultExpiry);

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
     * @throws Exception\ServerErrorException
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
}
