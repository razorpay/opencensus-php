<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\BadRequestException;
use RZP\Mail\Payout\DowntimeNotification;
use RZP\Models\Admin\Service as AdminService;

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

        if ((empty($input[Entity::NOTIFICATION_TYPE]) === true) or
            ($input[Entity::NOTIFICATION_TYPE] !== NotificationType::FUND_LOADING_DOWNTIME))
        {
            Validator::checkThreshold($input);
        }
        if (array_key_exists(Entity::NOTIFICATION_TYPE, $input) === false)
        {
            $input[Entity::NOTIFICATION_TYPE] = NotificationType::BENE_BANK_DOWNTIME;
        }

        $this->checkIfAlreadyExistingConfig($input[Entity::MODE] ?? 'ALL',
                                             $merchant->getId(),
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
                               'input'                 => $input,
                               'merchant_notification_config_id' => $merchantNotificationConfig->getId(),
                           ]
        );

        $this->implodeContactsForDB($input);

        Validator::checkThreshold($input, $merchantNotificationConfig);

        if (empty($input['mode']) === false)
        {
            $this->checkIfAlreadyExistingConfig($input[Entity::MODE],
                                                $merchantNotificationConfig->getMerchantId(),
                                                $merchantNotificationConfig->getNotificationType());
        }

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

    protected function checkIfAlreadyExistingConfig($mode, $merchantId, $notificationType)
    {
        /** @var  $notificationConfigs Base\PublicCollection*/

        $notificationConfigs = $this->repo->merchant_notification_config
                                          ->findByMerchantIdNotificationTypeAndMode($merchantId, $notificationType, $mode);

        $count = $notificationConfigs->count();

        if ($count > 0)
        {
            $queueableIds = $notificationConfigs->getPublicIds();

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_MODE,
                null,
                [
                    'existing_merchant_notification_config_ids' => $queueableIds,
                ]
            );
        }
    }

    public function processStuckPayoutsAlertsForMerchants()
    {
        $this->trace->info(TraceCode::PROCESSING_STUCK_PAYOUTS_ALERT_INIT);

        $countTotalEnabledConfigs = $this->getTotalEnabledConfigsCount();

        $countOfAlertsSent = 0;

        if ($countTotalEnabledConfigs === 0)
        {
            return [
                'total_enabled_merchant_notification_configs' => 0,
            ];
        }

        $configsProcessed = [];
        $merchantIdsAlerted = [];

        $enabledMerchantNotificationConfigs = $this->getEnabledConfigs();

        foreach($enabledMerchantNotificationConfigs as $config)
        {
            $merchantId = $config->getMerchantId();
            $wasAlertSent = $this->processMerchantNotificationConfigForAlert($config);

            if($wasAlertSent === true)
            {
                $countOfAlertsSent++;
                $merchantIdsAlerted[] = $merchantId;
            }

            $configsProcessed[$config->getPublicId()] = $merchantId;
        }

        $this->trace->info(
            TraceCode::MERCHANT_NOTIFICATION_CONFIG_PROCESSED_FOR_ALERTS,
            [
                'total_merchant_notification_configs_processed' => $countTotalEnabledConfigs,
                'processedConfigs'                              => $configsProcessed,
                'count_of_alerts_sent'                          => $countOfAlertsSent,
                'merchant_ids_alerted'                          => $merchantIdsAlerted,
            ]
        );

        return [
            'total_merchant_notification_configs_processed' => $countTotalEnabledConfigs,
            'count_of_alerts_sent'                          => $countOfAlertsSent,
        ];
    }

    protected function getTotalEnabledConfigsCount()
    {
        return $this->repo->merchant_notification_config->getTotalEnabledConfigsCount();
    }

    // TODO: Implement Pagination (https://jira.corp.razorpay.com/browse/RX-4502)
    protected function getEnabledConfigs()
    {
        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT]);

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT;
        }

        return $this->repo->merchant_notification_config->getEnabledConfigs($limit);
    }

    protected function processMerchantNotificationConfigForAlert(Entity $config)
    {
        $wasAlertSent = false;

        $currentTime = Carbon::now()->timestamp;

        $merchantId = $config->getMerchantId();

        $mode = $config->getMode();

        $merchantStuckPayoutsCount = $this->getCountOfStuckPayouts($merchantId, $mode);

        $notifyAt = $config->getNotifyAt();

        $this->trace->info(
            TraceCode::STUCK_PAYOUTS_ALERT_CONFIG_DEBUG_DATA_BEFORE_PROCESSING,
            [
                'config_id'              => $config->getId(),
                'current_time'           => $currentTime,
                'notify_at'              => $notifyAt,
                'notify_after'           => $config->getNotifyAfter(),
                'count_of_stuck_payouts' => $merchantStuckPayoutsCount,
                'upper_threshold'        => $config->getUpperThreshold(),
                'lower_threshold'        => $config->getLowerThreshold(),
                'config_mode'            => $mode,
            ]
        );

        // If a mail has been set, the timestamp value in notify_at
        // that is, the time at which the next notification is expected
        // will be stored as a negative value.

        // If the value returned by getNotifyAt() is negative, this should mean that a mail was sent before.
        // We should now check if the situation has been resolved.
        // If the issue is not resolved, we send a downtime mail again.
        if ($notifyAt < 0)
        {
            if ($merchantStuckPayoutsCount < $config->getLowerThreshold())
            {
                $wasAlertSent = $this->dispatchEmailAlertForStuckPayoutsResolution($config->getNotificationEmails(), $mode);

                if($wasAlertSent === true)
                {
                    // Change notify_at from negative to positive, to signify that resolution mail has been sent
                    $config->setNotifyAt(-1 * $notifyAt + $config->getNotifyAfter());
                    $this->repo->saveOrFail($config);
                }
            }
            else if ($currentTime > (-1 * $notifyAt))
            {
                $wasAlertSent = $this->dispatchEmailAlertForStuckPayouts(
                    $config->getNotificationEmails(),
                    $mode,
                    $merchantStuckPayoutsCount
                );

                if($wasAlertSent === true)
                {
                    // Update the config to store the next time a reminder downtime mail is supposed to be sent
                    // subtraction used because $notifyAt is negative
                    $config->setNotifyAt($notifyAt - $config->getNotifyAfter());
                    $this->repo->saveOrFail($config);
                }
            }
        }
        else
        {
            if (($currentTime > $notifyAt) and
                ($merchantStuckPayoutsCount > $config->getUpperThreshold()))
            {
                $wasAlertSent = $this->dispatchEmailAlertForStuckPayouts(
                    $config->getNotificationEmails(),
                    $mode,
                    $merchantStuckPayoutsCount
                );

                if($wasAlertSent === true)
                {
                    // Change notify_at to the negative of the next value
                    // Negative value signifies that a downtime email has been already sent
                    if ($notifyAt === 0)
                    {
                        $config->setNotifyAt(-1 * ($currentTime + $config->getNotifyAfter()));
                    }
                    else
                    {
                        $config->setNotifyAt(-1 * ($notifyAt + $config->getNotifyAfter()));
                    }

                    $this->repo->saveOrFail($config);
                }
            }
        }

        $this->trace->info(
            TraceCode::STUCK_PAYOUTS_ALERT_CONFIG_DEBUG_DATA_AFTER_PROCESSING,
            [
                'config_id'              => $config->getId(),
                'current_time'           => $currentTime,
                'old_notify_at'          => $notifyAt,
                'updated_notify_at'      => $config->getNotifyAt(),
                'notify_after'           => $config->getNotifyAfter(),
                'count_of_stuck_payouts' => $merchantStuckPayoutsCount,
                'upper_threshold'        => $config->getUpperThreshold(),
                'lower_threshold'        => $config->getLowerThreshold(),
                'wasAlertSent'           => $wasAlertSent,
            ]
        );

        return $wasAlertSent;
    }

    protected function getCountOfStuckPayouts(string $merchantId, string $mode)
    {
        if ($mode === 'ALL')
        {
            return $this->repo->payout->fetchCountOfPayoutsStuckInInitiatedToday($merchantId);
        }

        return $this->repo->payout->fetchCountOfPayoutsStuckInInitiatedTodayByMode($merchantId, $mode);
    }

    protected function dispatchEmailAlertForStuckPayouts(string $emailIds, string $mode, int $count)
    {
        $template = 'emails.payout.stuck_payouts_email';

        $subject = 'RazorpayX: Issue detected | High number of payouts stuck in initiated state';

        $data = [
            'to'       => $emailIds,
            'subject'  => $subject,
            'body'     => [
                'payout_mode'         => $mode,
                'stuck_payouts_count' => $count,
            ],
            'template' => $template,
        ];

        return $this->sendEmail($data);
    }

    protected function dispatchEmailAlertForStuckPayoutsResolution(string $emailIds, string $mode)
    {
        $template = 'emails.payout.stuck_payouts_resolution_email';

        $subject = 'RazorpayX: Issue resolved | Number of stuck payouts reduced';

        $data = [
            'to'       => $emailIds,
            'subject'  => $subject,
            'body'     => ['payout_mode' => $mode],
            'template' => $template,
        ];

        return $this->sendEmail($data);
    }

    protected function sendEmail($data)
    {
        $notification = new DowntimeNotification($data);

        try
        {
            $this->trace->info(
                TraceCode::STUCK_PAYOUTS_NOTIFY_EMAIL_INIT,
                [
                    'request' => $data,
                ]);

            Mail::send($notification);

            $this->trace->info(
                TraceCode::STUCK_PAYOUTS_NOTIFY_EMAIL_COMPLETE,
                [
                    'response' => $data,
                ]);

            return true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::STUCK_PAYOUTS_NOTIFY_EMAIL_FAILURE,
                [
                    'data' => $data,
                ]
            );

            return false;
        }
    }

    /**
     * The input in the request body stores emails and mobile numbers as an array of strings.
     * This function is used to convert these into a single string consisting of comma-separated values.
     *
     * @param array $input The input from the request body
     */
    protected function implodeContactsForDB(array &$input)
    {
        if(array_key_exists(Entity::NOTIFICATION_EMAILS, $input))
        {
            $input[Entity::NOTIFICATION_EMAILS] = implode(',', $input[Entity::NOTIFICATION_EMAILS]);
        }

        if(array_key_exists(Entity::NOTIFICATION_MOBILE_NUMBERS, $input))
        {
            $input[Entity::NOTIFICATION_MOBILE_NUMBERS] = implode(',', $input[Entity::NOTIFICATION_MOBILE_NUMBERS]);
        }
    }
}
