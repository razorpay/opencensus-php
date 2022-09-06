<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Adjustment;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Jobs\LowBalanceConfigAlert;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\Service as AdminService;
use RZP\Mail\Banking\LowBalanceAlert as LowBalanceAlertMailable;

class Core extends Base\Core
{
    protected $mutex;

    const DEFAULT_LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH = 20;

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

        Validator::validateAndTranslateAccountNumberForBanking($input, $merchant);

        $balanceId = $input[Entity::BALANCE_ID];

        $balance = $this->repo->balance->findOrFailById($balanceId);

        $type = $input[Entity::TYPE] ?? Entity::NOTIFICATION;

        $this->checkAndThrowErrorIfAlreadyExistingConfig($balanceId, $merchant->getId(), $type);

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
               'low_balance_config' => $entity->toArray(),
           ]
        );

        return $updatedLowBalanceConfigEntity ?? $entity;
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

    protected function checkAndThrowErrorIfAlreadyExistingConfig(string $balanceId,
                                                                 string $merchantId,
                                                                 string $type)
    {
        if ($type === Entity::AUTOLOAD_BALANCE)
        {
            /** @var $autoloadBalanceLowBalanceConfigs Base\PublicCollection */
            $autoloadBalanceLowBalanceConfigs = $this->repo->low_balance_config
                ->findByBalanceIdMerchantIdAndType(
                    $balanceId,
                    $merchantId,
                    Entity::AUTOLOAD_BALANCE);

            if ($autoloadBalanceLowBalanceConfigs->count() > 0)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ALREADY_EXISTS_FOR_ACCOUNT_NUMBER,
                    null,
                    [
                        'low_balance_config_ids' => $autoloadBalanceLowBalanceConfigs->getQueueableIds(),
                    ]
                );
            }
        }
        else
        {
            /** @var $notificationLowBalanceConfigs Base\PublicCollection */
            $notificationLowBalanceConfigs = $this->repo->low_balance_config
                ->findByBalanceIdMerchantIdAndType(
                    $balanceId,
                    $merchantId,
                    Entity::NOTIFICATION);

            if ($notificationLowBalanceConfigs->count() > 0)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ALREADY_EXISTS_FOR_ACCOUNT_NUMBER,
                    null,
                    [
                        'low_balance_config_ids' => $notificationLowBalanceConfigs->getQueueableIds(),
                    ]
                );
            }
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

    public function processLowBalanceAlertsForMerchants()
    {
        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH]);

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH;
        }

        $this->trace->info(
            TraceCode::PROCESS_LOW_BALANCE_CONFIG_ALERTS_FOR_MERCHANTS_REQUEST,
            [
                'limit' => $limit,
            ]
        );

        // idea is to  get total count of configs and not to fetch all of them at once to decrease load,
        // but use limit and offsets . since offset make it slow ,using seek method described in following link
        // https://blog.jooq.org/2013/10/26/faster-sql-paging-with-jooq-using-the-seek-method/
        // https://www.eversql.com/faster-pagination-in-mysql-why-order-by-with-limit-and-offset-is-slow/
        //
        // In one cron run , all config ids should be checked for alert notification. Don't wanna send 1 message to
        // queue per config id ,instead in 1 message to queue ,we will be sending multiple config_ids,
        //
        // Approach:
        // get number of configs to send in 1 message from redis ($limit)
        // get total configs count
        // get total batch as total_config_count/limit (total counters)
        // for first batch we just need limit and no offset (counter 0 run)
        // for other batch we need last fetched record of previous batch as an offset (check seek method)

        $totalConfigsCount = $this->repo->low_balance_config->getTotalEnabledConfigsCount();

        $lowBalanceConfigIdsBatchWise = [];

        // counter 0 run
        if ($totalConfigsCount > 0)
        {
            $lowBalanceConfigs = $this->repo->low_balance_config->getEnabledBalanceConfigsForAlert($limit);

            $lowBalanceConfigIds = $lowBalanceConfigs->getQueueableIds();

            $lastFetchedConfig = $lowBalanceConfigs->last();

            $lowBalanceConfigIdsBatchWise['batch_' . '0'] = $lowBalanceConfigIds;

            $this->dispatchLowBalanceAlertsForMerchants($lowBalanceConfigIds);
        }

        for ($counter = 1; $counter < (int) ceil($totalConfigsCount / $limit); $counter++)
        {
            $lowBalanceConfigs = $this->repo->low_balance_config
                                      ->getBalanceConfigsForAlertUsingLastFetchedConfig($limit, $lastFetchedConfig);

            $lowBalanceConfigIds = $lowBalanceConfigs->getQueueableIds();

            $lastFetchedConfig = $lowBalanceConfigs->last();

            $lowBalanceConfigIdsBatchWise['batch_' . $counter] = $lowBalanceConfigIds;

            $this->dispatchLowBalanceAlertsForMerchants($lowBalanceConfigIds);
        }

        $this->trace->info(
            TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_DISPATCHED,
            [
                'total_enabled_low_balance_configs' => $totalConfigsCount,
                'low_balance_config_ids_batch_wise' => $lowBalanceConfigIdsBatchWise,
            ]);

        return $lowBalanceConfigIdsBatchWise;
    }

    public function dispatchLowBalanceAlertsForMerchants(array $lowBalanceConfigIds)
    {
        $this->trace->info(
            TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_REQUEST,
            [
                'low_balance_config_ids' => $lowBalanceConfigIds,
            ]);

        LowBalanceConfigAlert::dispatch($this->mode, $lowBalanceConfigIds);
    }

    public function checkLowBalanceConfigsForAlert($lowBalanceConfigIds)
    {
        $balanceIdsForWhichEmailWasSent = [];
        $balanceIdsForWhichBalanceWasIncremented = [];

        foreach ($lowBalanceConfigIds as $lowBalanceConfigId)
        {
            /** @var Entity $lowBalanceConfig */
            $lowBalanceConfig = $this->repo->low_balance_config->findOrFail($lowBalanceConfigId);

            [$isEmailSent, $isBalanceAutoIncremented] =  $this->checkConfigForNotification($lowBalanceConfig);

            if ($isEmailSent === true)
            {
                $balanceIdsForWhichEmailWasSent[] = $lowBalanceConfig->getBalanceId();
            }
            if ($isBalanceAutoIncremented === true)
            {
                $balanceIdsForWhichBalanceWasIncremented[] = $lowBalanceConfig->getBalanceId();
            }
        }

        return [$balanceIdsForWhichEmailWasSent, $balanceIdsForWhichBalanceWasIncremented];
    }

    /**
     * @param Entity $lowBalanceConfigEntity
     * @return bool[]|false[]
     * @throws Exception\LogicException
     */
    public function checkConfigForNotification(Entity $lowBalanceConfigEntity)
    {
        $isEmailSent = false;

        $isBalanceAutoIncremented = false;

        $currentTime = Carbon::now()->getTimestamp();

        $balanceEntity      = $lowBalanceConfigEntity->balance;
        $balanceType        = $balanceEntity->getType();
        $balanceAccountType = $balanceEntity->getAccountType();
        $channel            = $balanceEntity->getChannel();

        $thresholdAmount    = $lowBalanceConfigEntity->getThresholdAmount();

        $balanceAmount = $this->getBalanceDependingUponProductAccountTypeAndChannel($balanceEntity,
                                                                                    $balanceAccountType,
                                                                                    $channel,
                                                                                    $balanceType);

        $this->trace->info(
            TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_DEBUG_DATA,
            [
                'current_time'     => $currentTime,
                'balance_amount'   => $balanceAmount,
                'threshold_amount' => $thresholdAmount,
                'balance_id'       => $balanceEntity->getId(),
                'notify_at'        => $lowBalanceConfigEntity->getNotifyAt(),
                'type'             => $lowBalanceConfigEntity->getType(),
                'autoload_amount'  => $lowBalanceConfigEntity->getAutoloadAmount(),
            ]
        );

        if ($balanceAmount > $thresholdAmount)
        {
            $lowBalanceConfigEntity->setNotifyAt(0);

            $lowBalanceConfigEntity->saveOrFail();

            return [$isEmailSent, $isBalanceAutoIncremented];
        }

        if ($lowBalanceConfigEntity->getNotifyAt() > $currentTime)
        {
            return [$isEmailSent, $isBalanceAutoIncremented];
        }

        if ($lowBalanceConfigEntity->getType() === Entity::AUTOLOAD_BALANCE)
        {
            $this->autoLoadBalance($lowBalanceConfigEntity, $balanceEntity, $balanceAmount, $thresholdAmount);

            $isBalanceAutoIncremented = true;
        }
        else
        {
            $this->sendLowBalanceNotificationEmails($lowBalanceConfigEntity,
                $balanceEntity,
                $balanceAmount,
                $thresholdAmount);

            $isEmailSent = true;
        }

        $nextNotifyAt = $currentTime + $lowBalanceConfigEntity->getNotifyAfter();

        $lowBalanceConfigEntity->setNotifyAt($nextNotifyAt);

        $lowBalanceConfigEntity->saveOrFail();

        return [$isEmailSent, $isBalanceAutoIncremented];
    }

    public function getBalanceDependingUponProductAccountTypeAndChannel(Balance\Entity $balanceEntity,
                                                                        $accountType,
                                                                        $channel,
                                                                        $product)
    {
        $balanceAmount = $balanceEntity->getBalanceWithLockedBalanceFromLedger();

        if ($product === Balance\Type::BANKING)
        {
            $getBalanceAmountMethod = 'getBalanceAmountFor' . $channel . $accountType . 'Account';

            if (method_exists($this, $getBalanceAmountMethod) === true)
            {
                $balanceAmount = call_user_func_array([$this, $getBalanceAmountMethod], [$balanceEntity]);
            }
        }

        return $balanceAmount;
    }

    public function getBalanceAmountForRblDirectAccount(Balance\Entity $balanceEntity)
    {
        $balanceAmount = $balanceEntity->getBalanceWithLockedBalance();

        $basDetails = $balanceEntity->bankingAccountStatementDetails;

        $variant = $this->app->razorx->getTreatment(
            $basDetails->getId(),
            Merchant\RazorxTreatment::USE_GATEWAY_BALANCE,
            $this->mode
        );

        if ($variant === 'on')
        {
            $balanceAmount = $basDetails->getGatewayBalance();
        }
        else
        {
            if ($basDetails->isGatewayBalanceFetchCronMoreUpdated() === true)
            {
                $balanceAmount = $basDetails->getGatewayBalance();
            }
        }

        return $balanceAmount;
    }

    protected function autoLoadBalance(Entity $lowBalanceConfigEntity,
                                       Balance\Entity $balanceEntity,
                                       $balanceAmount,
                                       $thresholdAmount)
    {
        $data = [
            'masked_account_number' => mask_except_last4($balanceEntity->getAccountNumber()),
            'available_balance'     => (float) $balanceAmount / 100,
            'threshold'             => (float) $thresholdAmount / 100,
            'autoload_amount'       => $lowBalanceConfigEntity->getAutoloadAmount(),
            'merchant_id'           => $lowBalanceConfigEntity->getMerchantId(),
            'business_name'         => $lowBalanceConfigEntity->merchant->merchantDetail->getBusinessName(),
        ];

        $this->trace->info(
            TraceCode::LOW_BALANCE_CONFIG_AUTOLOAD_BALANCE_DATA,
            [
                'data'               => $data,
                'merchant_id'        => $lowBalanceConfigEntity->getMerchantId(),
                'low_balance_config' => $lowBalanceConfigEntity->getPublicId(),
            ]);

        $inputForAdjustment = [
            'amount'        => $lowBalanceConfigEntity->getAutoloadAmount(),
            'description'   => Entity::AUTOLOAD_BALANCE_ADJUSTMENT_DESCRIPTION,
            'currency'      => 'INR',
            'type'          => 'banking',
        ];

        $adjustment = (new Adjustment\Core)->createAdjustment($inputForAdjustment, $lowBalanceConfigEntity->merchant);

        $this->trace->info(
            TraceCode::LOW_BALANCE_CONFIG_AUTOLOAD_BALANCE_ADJUSTMENT_CREATED,
            [
                'merchant_id'        => $lowBalanceConfigEntity->getMerchantId(),
                'low_balance_config' => $lowBalanceConfigEntity->getPublicId(),
                'adjustment_id'      => $adjustment->getId(),
            ]);
    }

    protected function sendLowBalanceNotificationEmails(Entity $lowBalanceConfigEntity,
                                                        Balance\Entity $balanceEntity,
                                                        $balanceAmount,
                                                        $thresholdAmount)
    {
        $notificationEmails = $lowBalanceConfigEntity->getNotificationEmails();

        // mailable emails are sent to multiple email addresses if passed an array.
        // hence converting comma separated emails to array
        $notificationEmails = explode(',', $notificationEmails);

        $data = [
            'emails'                => $notificationEmails,
            'masked_account_number' => mask_except_last4($balanceEntity->getAccountNumber()),
            'available_balance'     => (float) $balanceAmount / 100,
            'threshold'             => (float) $thresholdAmount / 100,
            'merchant_id'           => $lowBalanceConfigEntity->getMerchantId(),
            'business_name'         => $lowBalanceConfigEntity->merchant->merchantDetail->getBusinessName(),
        ];

        $this->trace->info(
            TraceCode::LOW_BALANCE_CONFIG_ALERTS_EMAIL_DATA,
            [
                'data'               => $data,
                'merchant_id'        => $lowBalanceConfigEntity->getMerchantId(),
                'low_balance_config' => $lowBalanceConfigEntity->getPublicId(),
            ]);

        $lowBalanceEmail = new LowBalanceAlertMailable($lowBalanceConfigEntity->merchant, $data);

        Mail::queue($lowBalanceEmail);
    }
}
