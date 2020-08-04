<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->checkAndThrowErrorIfTestMode();

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_CREATE_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $merchant->getId(),
            ]
        );

        // validations

        Validator::validateNotificationEmailRules($input);

        Validator::validateAndTranslateAccountNumberForBanking($input, $this->merchant);

        $balanceId = $input[Entity::BALANCE_ID];

        $balance = $this->repo->balance->findOrFailById($balanceId);

        $this->checkAndThrowErrorIfAlreadyExistingConfig($balanceId, $this->merchant->getId());

        // building entity
        $lowBalanceConfig = new Entity();

        $lowBalanceConfig->build($input);

        // associations
        $lowBalanceConfig->merchant()->associate($merchant);

        $lowBalanceConfig->balance()->associate($balance);

        $this->repo->saveOrFail($lowBalanceConfig);

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_CREATE_RESPONSE,
           [
               'low_balance_config' => $lowBalanceConfig->toArray(),
           ]
        );

        return $lowBalanceConfig;
    }

    public function update(Entity $entity, array $input)
    {
        $this->checkAndThrowErrorIfTestMode();

        try
        {
            $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_UPDATE_REQUEST,
                               [
                                   'input'                 => $input,
                                   'low_balance_config_id' => $entity->getId(),
                               ]
            );

            if (isset($input[Entity::NOTIFICATION_EMAILS]) === true)
            {
                Validator::validateNotificationEmailRules($input);
            }

            $updatedLowBalanceConfigEntity = $this->mutex->acquireAndRelease(
                'low_balance_config_' . $entity->getId(),
                function() use($entity, $input)
                {
                    $entity->reload();

                    $entity->edit($input);

                    $this->repo->saveOrFail($entity);

                    return $entity;
                },
                60,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );
        }
        catch (BadRequestException $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ANOTHER_OPERATION_ON_LOW_BALANCE_CONFIG_IS_IN_PROGRESS,
                [
                    'id'      => $entity->getPublicId(),
                    'message' => $e->getMessage(),
                ]);
        }

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_UPDATE_RESPONSE,
           [
               'low_balance_config' => $updatedLowBalanceConfigEntity->toArray(),
           ]
        );

        return $updatedLowBalanceConfigEntity;
    }

    public function delete(Entity $lowBalanceConfig)
    {
        $this->checkAndThrowErrorIfTestMode();

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_DELETE_REQUEST,
            [
                'id' => $lowBalanceConfig->getId(),
            ]
        );

        $this->repo->deleteOrFail($lowBalanceConfig);

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_DELETE_SUCCESSFULL,
           [
               'id' => $lowBalanceConfig->getId(),
           ]
        );

        return $lowBalanceConfig->toArrayDeleted();
    }

    public function disableConfig(Entity $lowBalanceConfig)
    {
        $this->checkAndThrowErrorIfTestMode();

        try
        {
            $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_DISABLE_REQUEST,
               [
                   'low_balance_config_id' => $lowBalanceConfig->getId(),
               ]
            );

            if ($lowBalanceConfig->getStatus() === Status::DISABLED)
            {
                return $lowBalanceConfig;
            }

            $updatedEntity = $this->mutex->acquireAndRelease(
                'low_balance_config_' . $lowBalanceConfig->getId(),
                function() use($lowBalanceConfig)
                {
                    $lowBalanceConfig->reload();

                    $lowBalanceConfig->setStatus(Status::DISABLED);

                    $this->repo->saveOrFail($lowBalanceConfig);

                    return $lowBalanceConfig;
                },
                60,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );
        }
        catch (BadRequestException $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ANOTHER_OPERATION_ON_LOW_BALANCE_CONFIG_IS_IN_PROGRESS,
                [
                    'id'      => $lowBalanceConfig->getPublicId(),
                    'message' => $e->getMessage(),
                ]);
        }

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_DISABLE_SUCCESSFULL,
           [
               'low_balance_config' => $updatedEntity->toArray(),
           ]
        );

        return $updatedEntity;
    }

    public function enableConfig(Entity $lowBalanceConfig)
    {
        $this->checkAndThrowErrorIfTestMode();

        try
        {
            $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_ENABLE_REQUEST,
               [
                   'low_balance_config_id' => $lowBalanceConfig->getId(),
               ]
            );

            if ($lowBalanceConfig->getStatus() === Status::ENABLED)
            {
                return $lowBalanceConfig;
            }

            $updatedLowBalanceConfigEntity = $this->mutex->acquireAndRelease(
                'low_balance_config_' . $lowBalanceConfig->getId(),
                function() use($lowBalanceConfig)
                {
                    $lowBalanceConfig->reload();

                    $lowBalanceConfig->setStatus(Status::ENABLED);

                    $this->repo->saveOrFail($lowBalanceConfig);

                    return $lowBalanceConfig;
                },
                60,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );
        }
        catch (BadRequestException $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ANOTHER_OPERATION_ON_LOW_BALANCE_CONFIG_IS_IN_PROGRESS,
                [
                    'id'      => $lowBalanceConfig->getPublicId(),
                    'message' => $e->getMessage(),
                ]);
        }

        $this->trace->info(TraceCode::LOW_BALANCE_CONFIG_ENABLE_SUCCESSFULL,
           [
               'low_balance_config' => $updatedLowBalanceConfigEntity->toArray(),
           ]
        );

        return $updatedLowBalanceConfigEntity;
    }

    protected function checkAndThrowErrorIfAlreadyExistingConfig($balanceId, $merchantId)
    {
        /** @var  $lowBalanceConfigs Base\PublicCollection*/
        $lowBalanceConfigs = $this->repo->low_balance_config
                                  ->findByBalanceIdAndMerchantId($balanceId, $merchantId);

        if ($lowBalanceConfigs->count() > 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ALREADY_EXISTS_FOR_ACCOUNT_NUMBER,
                null,
                [
                     'low_balance_config_ids' => $lowBalanceConfigs->getQueueableIds(),
                ]
            );
        }
    }

    protected function checkAndThrowErrorIfTestMode()
    {
        if ($this->isTestMode() === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_IS_NOT_SUPPORTED_IN_TEST_MODE
            );
        }
    }
}
