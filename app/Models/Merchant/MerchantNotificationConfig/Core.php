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

        // validations

        Validator::validateNotificationEmailRules($input);

        Validator::validateNotificationMobileNumbersRules($input);

        if (empty($input['mode']) === false)
        {
            Validator::validateMode($input);
        }

        $this->checkAndThrowErrorIfLowerThresholdGreaterThanUpperThreshold($input);

        $this->checkAndThrowErrorIfAlreadyExistingConfig(($input[Entity::MODE] ?? 'ALL'), $this->merchant->getId());

        // building entity
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

        if (isset($input[Entity::NOTIFICATION_EMAILS]) === true)
        {
            Validator::validateNotificationEmailRules($input);
        }

        if (isset($input[Entity::NOTIFICATION_MOBILE_NUMBERS]) === true)
        {
            Validator::validateNotificationMobileNumbersRules($input);
        }

        // No validation of mode field in input, because changing mode via update is not allowed.

        $this->checkAndThrowErrorIfLowerThresholdGreaterThanUpperThreshold($input, $merchantNotificationConfig);

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

        if ($merchantNotificationConfig->getConfigStatus() === Status::DISABLED)
        {
            return $merchantNotificationConfig;
        }

        $updatedEntity = $this->mutex->acquireAndRelease(
            'merchant_notification_config_' . $merchantNotificationConfig->getId(),
            function() use($merchantNotificationConfig)
            {
                $merchantNotificationConfig->reload();

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

        if ($merchantNotificationConfig->getConfigStatus() === Status::ENABLED)
        {
            return $merchantNotificationConfig;
        }

        $updatedEntity = $this->mutex->acquireAndRelease(
            'merchant_notification_config_' . $merchantNotificationConfig->getId(),
            function() use($merchantNotificationConfig)
            {
                $merchantNotificationConfig->reload();

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

    protected function checkAndThrowErrorIfAlreadyExistingConfig($mode, $merchantId)
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

    protected function checkAndThrowErrorIfLowerThresholdGreaterThanUpperThreshold(array $input, Entity $entity = null)
    {
        $errorCode = null;
        $data = null;

        if((empty($input['lower_threshold']) === false) and
           (empty($input['upper_threshold']) === true) and
           (is_null($entity) === false) and
           ($entity->getUpperThreshold() < $input['lower_threshold']))
        {
            $data = [
                'existing_lower_threshold'     => $entity->getLowerThreshold(),
                'existing_upper_threshold'     => $entity->getUpperThreshold(),
                'new_lower_threshold_received' => $input['lower_threshold'],
            ];

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD;

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD,
                null,
                $data
            );
        }

        if((empty($input['upper_threshold']) === false) and
           (empty($input['lower_threshold']) === true) and
           (is_null($entity) === false) and
           ($entity->getLowerThreshold() > $input['upper_threshold']))
        {
            $data = [
                'existing_upper_threshold'     => $entity->getUpperThreshold(),
                'existing_lower_threshold'     => $entity->getLowerThreshold(),
                'new_upper_threshold_received' => $input['upper_threshold'],
            ];

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD;
        }

        if((empty($input['upper_threshold']) === false) and
           (empty($input['lower_threshold']) === false) and
           ($input['lower_threshold'] > $input['upper_threshold']))
        {
            $data = [
                'new_upper_threshold_received' => $input['upper_threshold'],
                'new_lower_threshold_received' => $input['lower_threshold'],
            ];

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_LOWER_THRESHOLD_GREATER_THAN_UPPER_THRESHOLD;
        }

        if((is_null($errorCode) === false) and
           (is_null($data) === false))
        {
            throw new BadRequestException(
                $errorCode,
                null,
                $data
            );
        }
    }
}
