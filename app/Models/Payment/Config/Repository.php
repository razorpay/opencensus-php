<?php


namespace RZP\Models\Payment\Config;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Feature\Metric;
use RZP\Trace\TraceCode;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;
use Illuminate\Database\Eloquent\Builder;

class Repository extends Base\Repository
{
    protected $entity = 'config';

    protected $table = 'payment_configs';

    protected $entityFetch = [
        Entity::TYPE,
    ];

    public function fetchConfigByMerchantIdAndType($merchantId, $type, $input = [])
    {
           $query =  $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::IS_DELETED, false)
                    ->where(Entity::TYPE, $type);

           $this->buildQueryWithParams($query, $input);

           $this->addQueryOrder($query);

           return $query->get();
    }

    public function findByPublicIdAndMerchantAndType($id, $merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::IS_DELETED, false)
                    ->first();
    }

    public function fetchDefaultConfigByMerchantIdAndType($merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::IS_DEFAULT, true)
                    ->where(Entity::IS_DELETED, false)
                    ->when($type === Type::CHECKOUT, static function (Builder $query) {
                        return $query->orderBy(Entity::UPDATED_AT, 'desc');
                    })
                    ->first();
    }

    public function deletePaymentConfig($merchantId, $type)
    {
        $this->newQuery()
             ->where(Entity::MERCHANT_ID, $merchantId)
             ->where(Entity::TYPE, $type)
             ->update([Entity::IS_DELETED => true]);
    }

    public function fetchMultipleByParam($input){
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::IS_DELETED, false);

        if ((isset($input['is_default']) === true) and (($input['is_default'] === 'true') or (strval($input['is_default']) === '1'))) {
            $query->where(Entity::IS_DEFAULT, true);
            unset($input['is_default']);
        }

        $this->buildQueryWithParams($query, $input);

        $this->addQueryOrder($query);

        return $query->get();
    }

    public function fetchByIdAndNotDeleted($id){
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::IS_DELETED, false);

        return $query->findOrFailPublic($id);
    }

    /**
     * Override saveOrFail to sync default late_auth configs to DCS
     */
    public function save($entity, array $options = array())
    {
        // Sync to DCS if conditions are met
        $this->syncConfigToDcsIfApplicableSafely($entity);

        // Call parent save
        parent::save($entity, $options);
    }

    /**
     * Override saveOrFail to sync default late_auth configs to DCS
     */
    public function saveOrFail($entity, array $options = array())
    {
        // Sync to DCS if conditions are met
        $this->syncConfigToDcsIfApplicableSafely($entity);

        // Call parent saveOrFail
        parent::saveOrFail($entity, $options);
    }

    /**
     * Synchronizes payment configuration to DCS if applicable
     * Only syncs late_auth configs that are marked as default
     *
     * @param Entity $configEntity
     * @return void
     */
    private function syncConfigToDcsIfApplicableSafely(Entity $configEntity): void
    {
        try
        {
            if ($this->isDcsConfigSyncApplicable($configEntity) === false)
            {
                return;
            }

            $this->syncConfigToDcs($configEntity);
        }
        catch (\Throwable $exception)
        {
            $merchantId = $configEntity->getAttribute('merchant_id') ?? 'unknown';

            $this->trace->error(TraceCode::DCS_LATE_AUTH_CONFIG_SYNC_FAIL,[
                'merchant_id' => $merchantId,
                'config_entity_id' => $configEntity->getId(),
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                "error" => $exception->getTraceAsString(),
            ]);


            $this->trace->count(Metric::DCS_LATE_AUTH_CONFIG_SYNC_FAIL, [
                "action" => "assign",
                "mode" => $this->getAppMode(),
            ]);
        }
    }

    /**
     * Determines if DCS sync is required for the given config entity
     *
     * @param Entity|null $configEntity
     * @return bool
     */
    private function isDcsConfigSyncApplicable(?Entity $configEntity): bool
    {
        if ($configEntity === null) {
            return false;
        }

        if ($configEntity->getType() !== Type::LATE_AUTH) {
            return false;
        }

        if ($configEntity->getAttribute(Entity::IS_DEFAULT) !== true) {
            return false;
        }

        if ($this->isDcsSyncExperimentEnabled($configEntity) === false) {
            return false;
        }

        return true;
    }

    /**
     * Executes the actual DCS configuration sync
     *
     * @param Entity $configEntity
     * @throws \Exception
     */
    private function syncConfigToDcs(Entity $configEntity): void
    {
        $dcsService = $this->getDcsConfigService();

        $syncData = $this->prepareDcsSyncData($configEntity);

        $this->app['trace']->info(TraceCode::DCS_LATE_AUTH_CONFIG_SYNC_REQUEST, [
            'merchant_id' => $syncData['merchant_id'],
            'config_data' => $syncData['config_data'],
            'transformed_config' => $syncData['transformed_config'],
            'mode' => $syncData['mode'],
        ]);

        $dcsService->createConfiguration(
            $syncData['key'],
            $syncData['merchant_id'],
            $syncData['transformed_config'],
            $syncData['mode']
        );

        $this->app['trace']->info(TraceCode::DCS_LATE_AUTH_CONFIG_SYNC_SUCCESS, [
            'merchant_id' => $syncData['merchant_id'],
        ]);

    }

    /**
     * Prepares all data required for DCS sync
     *
     * @param Entity $configEntity
     * @return array
     * @throws \InvalidArgumentException
     */
    private function prepareDcsSyncData(Entity $configEntity): array
    {
        $merchantId = $configEntity->getAttribute(Entity::MERCHANT_ID);

        $rawConfig = $configEntity->getAttribute(Entity::CONFIG);

        if (empty($merchantId)) {
            throw new \InvalidArgumentException('Merchant ID is required for DCS sync');
        }

        if (empty($rawConfig)) {
            throw new \InvalidArgumentException('Config data is required for DCS sync');
        }

        $configData = $this->parseConfigData($rawConfig);

        $transformedConfig = $this->transformLateAuthConfigDataForDcs($configData);

        return [
            'key' => DcsConstants::LateAuthConfig,
            'merchant_id' => $merchantId,
            'config_data' => $configData,
            'transformed_config' => $transformedConfig,
            'mode' => $this->getAppMode(),
        ];
    }

    /**
     * Safely parses JSON config data
     *
     * @param string|null $rawConfig
     * @return array
     * @throws \InvalidArgumentException
     */
    private function parseConfigData(?string $rawConfig): array
    {
        if (empty($rawConfig)) {
            throw new \InvalidArgumentException('Raw config data cannot be empty');
        }

        $configData = json_decode($rawConfig, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'Invalid JSON in config data: ' . json_last_error_msg()
            );
        }

        if (!is_array($configData)) {
            throw new \InvalidArgumentException('Config data must be a valid array');
        }

        return $configData;
    }


    /**
     * Transforms late auth config data for DCS compatibility
     *
     * @param array $configData
     * @return array
     * @throws \InvalidArgumentException
     */
    private function transformLateAuthConfigDataForDcs(array $configData): array
    {
        return [
            Entity::DCS_FIELD_CAPTURE => $configData[Entity::CONFIG_FIELD_CAPTURE] ?? null,
            Entity::DCS_FIELD_AUTO_EXPIRY => $configData[Entity::CONFIG_FIELD_CAPTURE_OPTIONS][Entity::CONFIG_FIELD_AUTO_EXPIRY] ?? null,
            Entity::DCS_FIELD_MANUAL_EXPIRY => $configData[Entity::CONFIG_FIELD_CAPTURE_OPTIONS][Entity::CONFIG_FIELD_MANUAL_EXPIRY] ?? null,
        ];
    }

    /**
     * Gets the DCS config service instance
     *
     * @return mixed
     * @throws \RuntimeException
     */
    private function getDcsConfigService()
    {
        $service = app('dcs_config_service');

        if ($service === null) {
            throw new \RuntimeException('DCS config service is not available');
        }

        return $service;
    }

    /**
     * Increments failure metrics for monitoring
     *
     * @param string $merchantId
     * @param \Exception $exception
     */
    private function incrementDcsSyncFailureMetric(string $merchantId, \Exception $exception): void
    {
        // Add your metrics collection logic here if needed
        // Example: $this->app['metrics']->increment('dcs_sync_failures', ['merchant_id' => $merchantId]);
    }

    private function getAppMode() {
        if(isset($this->app['rzp.mode']) === true)
        {
            return $this->app['rzp.mode'];
        }

        return Mode::TEST;
    }

    /**
     * Determines if DCS sync Experiment is enabled for the given config entity
     *
     * @param Entity|null $configEntity
     * @return bool
     */
    public function isDcsSyncExperimentEnabled($configEntity) : bool
    {
        try
        {
            $experimentId = $this->app['config']->get('app.late_auth_config_dcs_sync_experiment');

            $properties = [
                "id" => $configEntity->getMerchantId(),
                "experiment_id" => $experimentId,
                'request_data'  => json_encode(['merchant' => $configEntity->getMerchantId()]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = 'control';

            if(!empty($response['response']['variant']) && isset($response['response']['variant']['name']))
            {
                $variant = $response['response']['variant']['name'];
            }

            $this->trace->info(TraceCode::LATE_AUTH_CONFIG_DCS_SYNC_SPLITZ_EXPRIMENT_RESPONSE, [
                'variant' => $variant,
                'experiment_id' => $experimentId
            ]);

            return $variant === 'enable';
        }
        catch( \Throwable $ex)
        {
            $this->trace->error(TraceCode::LATE_AUTH_CONFIG_DCS_SYNC_SPLITZ_EXPRIMENT_FAILURE, [
                'message' => $ex->getMessage(),
            ]);

            return false;
        }
    }
}
