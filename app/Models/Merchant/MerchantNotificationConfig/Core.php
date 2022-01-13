<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    protected $mutex;

    // This is the default limit for the number of configs to be fetched from the DB.
    const DEFAULT_MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT = 500;

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

        $this->implodeContactsForDB($input);

        if (array_key_exists(Entity::NOTIFICATION_TYPE, $input) === false)
        {
            $input[Entity::NOTIFICATION_TYPE] = NotificationType::BENE_BANK_DOWNTIME;
        }

        $this->checkIfAlreadyExistingConfig($merchant->getId(),
                                            $input[Entity::NOTIFICATION_TYPE]);

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
                               'input'                           => $input,
                               'merchant_notification_config_id' => $merchantNotificationConfig->getId(),
                           ]
        );

        $this->implodeContactsForDB($input);

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

    protected function checkIfAlreadyExistingConfig($merchantId, $notificationType)
    {
        /** @var  $notificationConfigs Base\PublicCollection*/

        $notificationConfigs = $this->repo->merchant_notification_config
                                          ->findByMerchantIdAndNotificationType($merchantId, $notificationType);

        $count = $notificationConfigs->count();

        if ($count > 0)
        {
            $queueableIds = $notificationConfigs->getPublicIds();

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_NOTIFICATION_TYPE,
                null,
                [
                    'existing_merchant_notification_config_ids' => $queueableIds,
                ]
            );
        }
    }

    /**
     * The input in the request body stores emails and mobile numbers as an array of strings.
     * This function is used to convert these into a single string consisting of comma-separated values.
     *
     * @param array $input The input from the request body
     * @throws BadRequestValidationFailureException in case there are duplicate email ids or mobile numbers in request
     */
    protected function implodeContactsForDB(array &$input)
    {
        if (array_key_exists(Entity::NOTIFICATION_EMAILS, $input))
        {
            $inputEmailIds    = $input[Entity::NOTIFICATION_EMAILS];
            $distinctEmailIds = array_unique($inputEmailIds);

            if (count($distinctEmailIds) !== count($inputEmailIds))
            {
                throw new BadRequestValidationFailureException('DUPLICATE_EMAIL_ID',
                                                               null,
                                                               [
                                                                   'input_email_ids' => $inputEmailIds
                                                               ]
                );
            }
            $input[Entity::NOTIFICATION_EMAILS] = implode(',', $input[Entity::NOTIFICATION_EMAILS]);
        }

        if (array_key_exists(Entity::NOTIFICATION_MOBILE_NUMBERS, $input))
        {
            $inputMobileNumbers    = $input[Entity::NOTIFICATION_MOBILE_NUMBERS];
            $distinctMobileNumbers = array_unique($inputMobileNumbers);

            if (count($inputMobileNumbers) !== count($distinctMobileNumbers))
            {
                throw new BadRequestValidationFailureException('DUPLICATE_MOBILE_NUMBER',
                                                               null,
                                                               [
                                                                   'input_mobile_numbers' => $inputMobileNumbers
                                                               ]
                );
            }

            $input[Entity::NOTIFICATION_MOBILE_NUMBERS] = implode(',', $input[Entity::NOTIFICATION_MOBILE_NUMBERS]);
        }
    }
}
