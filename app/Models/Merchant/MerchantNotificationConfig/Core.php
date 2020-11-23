<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\Service as AdminService;

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
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_CREATE_REQUEST,
                           [
                               'input'       => $input,
                               'merchant_id' => $merchant->getId(),
                           ]
        );

        // TODO: [Refactor] Move these validators into a single place

        Validator::validateNotificationEmailRules($input);

        Validator::validateNotificationMobileNumbersRules($input);

        if (empty($input['mode']) === false)
        {
            Validator::validateMode($input);
        }

        Validator::checkThreshold($input);

        $this->checkIfAlreadyExistingConfig(($input[Entity::MODE] ?? 'ALL'), $merchant->getId());

        $merchantNotificationConfig = new Entity();

        $merchantNotificationConfig->build($input);

        // associations
        $merchantNotificationConfig->merchant()->associate($merchant);

        $this->repo->saveOrFail($merchantNotificationConfig);

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_CREATE_RESPONSE,
                           [
                               'merchant_notification_config' => $merchantNotificationConfig->toArray(),
                           ]
        );

        return $merchantNotificationConfig;
    }

    public function update(Entity $merchantNotificationConfig, array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_UPDATE_REQUEST,
                           [
                               'input'                 => $input,
                               'merchant_notification_config_id' => $merchantNotificationConfig->getId(),
                           ]
        );

        // TODO: [Refactor] Move these validators into a single place

        if (isset($input[Entity::NOTIFICATION_EMAILS]) === true)
        {
            Validator::validateNotificationEmailRules($input);
        }

        if (isset($input[Entity::NOTIFICATION_MOBILE_NUMBERS]) === true)
        {
            Validator::validateNotificationMobileNumbersRules($input);
        }

        // No validation of mode field in input, because changing mode via update is not allowed.

        Validator::checkThreshold($input, $merchantNotificationConfig);

        $updatedMerchantNotificationConfigEntity = $this->mutex->acquireAndRelease(
            'merchant_notification_config_' . $merchantNotificationConfig->getId(),
            function() use($merchantNotificationConfig, $input)
            {
                $merchantNotificationConfig->reload();

                $merchantNotificationConfig->edit($input);

                $this->repo->saveOrFail($merchantNotificationConfig);

                return $merchantNotificationConfig;
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_UPDATE_RESPONSE,
                           [
                               'merchant_notification_config' => $updatedMerchantNotificationConfigEntity->toArray(),
                           ]
        );

        return $updatedMerchantNotificationConfigEntity;
    }

    public function delete(Entity $merchantNotificationConfig)
    {
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_DELETE_REQUEST,
                           [
                               'id' => $merchantNotificationConfig->getId(),
                           ]
        );

        $this->repo->deleteOrFail($merchantNotificationConfig);

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_DELETE_SUCCESSFUL,
                           [
                               'id' => $merchantNotificationConfig->getId(),
                           ]
        );

        return $merchantNotificationConfig->toArrayDeleted();
    }

    public function disableConfig(Entity $merchantNotificationConfig)
    {
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_DISABLE_REQUEST,
                           [
                               'merchant_notification_config_id' => $merchantNotificationConfig->getId(),
                           ]
        );

        $updatedEntity = $this->mutex->acquireAndRelease(
            'merchant_notification_config_' . $merchantNotificationConfig->getId(),
            function() use($merchantNotificationConfig)
            {
                $merchantNotificationConfig->reload();

                if ($merchantNotificationConfig->getConfigStatus() === Status::DISABLED)
                {
                    return $merchantNotificationConfig;
                }

                $merchantNotificationConfig->setConfigStatus(Status::DISABLED);

                $this->repo->saveOrFail($merchantNotificationConfig);

                return $merchantNotificationConfig;
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_DISABLE_SUCCESSFUL,
                           [
                               'merchant_notification_config' => $updatedEntity->toArray(),
                           ]
        );

        return $updatedEntity;
    }

    public function enableConfig(Entity $merchantNotificationConfig)
    {
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_ENABLE_REQUEST,
                           [
                               'merchant_notification_config_id' => $merchantNotificationConfig->getId(),
                           ]
        );

        $updatedEntity = $this->mutex->acquireAndRelease(
            'merchant_notification_config_' . $merchantNotificationConfig->getId(),
            function() use($merchantNotificationConfig)
            {
                $merchantNotificationConfig->reload();

                if ($merchantNotificationConfig->getConfigStatus() === Status::ENABLED)
                {
                    return $merchantNotificationConfig;
                }

                $merchantNotificationConfig->setConfigStatus(Status::ENABLED);

                $this->repo->saveOrFail($merchantNotificationConfig);

                return $merchantNotificationConfig;
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_ENABLE_SUCCESSFUL,
                           [
                               'merchant_notification_config' => $updatedEntity->toArray(),
                           ]
        );

        return $updatedEntity;
    }

    protected function checkIfAlreadyExistingConfig($mode, $merchantId)
    {
        /** @var  $notificationConfigs Base\PublicCollection*/
        $notificationConfigs = $this->repo->merchant_notification_config
            ->findByMerchantIdAndMode($merchantId, $mode);

        if ($notificationConfigs->count() > 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_MODE,
                null,
                [
                    'merchant_notification_config_ids' => $notificationConfigs->getQueueableIds(),
                ]
            );
        }
    }
}
