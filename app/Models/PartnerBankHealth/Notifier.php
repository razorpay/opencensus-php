<?php

namespace RZP\Models\PartnerBankHealth;

use Mail;
use Carbon\Carbon;
use Razorpay\IFSC\IFSC;
use Razorpay\Trace\Logger;

use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Jobs\PartnerBankHealthNotification;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Payout\Notifications\SmsConstants;
use RZP\Mail\PartnerBankHealth\PartnerBankHealthMail;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\MerchantNotificationConfig\Entity as ConfigEntity;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType as Type;

class Notifier extends \RZP\Models\Base\Core
{
    protected $data;
    protected $includeMerchants;
    protected $excludeMerchants;
    protected $validator;

    const DISPLAY_NAME   = 'display_name';
    const DEFAULT_SOURCE = 'RazorpayX';

    // make this redis based fetch/updated
    const DEFAULT_CONFIG_FETCH_LIMIT = 50;

    const CHANNEL_MAPPING_FOR_DB = [
        'YESB' => 'yesbank',
        'ICIC' => 'icici',
        'RATN' => 'rbl',
        'AXIS' => 'axis'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator();
    }

    public static function getPartnerBankHealthNotificationConfigFetchLimit()
    {
        $merchantNotificationConfigFetchLimit = (new Admin\Service)->getConfigKey(
            ['key' => Admin\ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT]
        );

        $partnerBankHealthNotificationConfigFetchLimit = $merchantNotificationConfigFetchLimit[Type::PARTNER_BANK_HEALTH] ?? null;

        if (empty($partnerBankHealthNotificationConfigFetchLimit) === true)
        {
            return self::DEFAULT_CONFIG_FETCH_LIMIT;
        }

        return $partnerBankHealthNotificationConfigFetchLimit;
    }

    public function getSmsPayload($data) : array
    {
        return [
            SmsConstants::SOURCE                      => Type::PARTNER_BANK_HEALTH,
            SmsConstants::OWNER_TYPE                  => MerchantConstants::MERCHANT,
            SmsConstants::ORG_ID                      => $this->app['basicauth']->getOrgId() ?? '',
            SmsConstants::TEMPLATE_NAME               => Type::PARTNER_BANK_HEALTH . '.' . $data[Constants::STATUS],
            SmsConstants::TEMPLATE_NAMESPACE          => SmsConstants::PAYOUTS_CORE_TEMPLATE_NAMESPACE,
            SmsConstants::LANGUAGE                    => SmsConstants::ENGLISH,
            SmsConstants::SENDER                      => SmsConstants::RAZORPAYX_SENDER,
            SmsConstants::CONTENT_PARAMS              => $this->getSmsParams($data),
            SmsConstants::DELIVERY_CALLBACK_REQUESTED => true
        ];
    }

    public function getEmailParams($data) : array
    {
        $params = $this->getSmsParams($data);

        $params[Constants::STATUS] = $data[Constants::STATUS];

        if($data[Constants::STATUS] === Status::UP)
        {
            $params[Constants::END_TIME] = Carbon::createFromTimestamp($data[Constants::BEGIN], Timezone::IST)->format('d M g:i a');
        }

        return $params;
    }

    public function getSmsParams($data)
    {
        $params = [];

        if($data[Constants::INTEGRATION_TYPE] === AccountType::SHARED)
        {
            $params[Constants::SOURCE] = self::DEFAULT_SOURCE;
        }
        else
        {
            $params[Constants::SOURCE] = IFSC::getBankName($data[Constants::CHANNEL]);
        }

        $params[Constants::MODE] = $data[Constants::MODE];

        $eventIdentificationTime = Carbon::createFromTimestamp($data[Constants::BEGIN], Timezone::IST)->format('d M g:i a');

        if ($data[Constants::STATUS] === Status::DOWN)
        {
            $params[Constants::START_TIME] =  $eventIdentificationTime;
        }

        return $params;
    }

    public function sendNotifications()
    {
        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_REQUEST,
                           [
                               'data'              => $this->data,
                               'include_merchants' => $this->includeMerchants,
                               'exclude_merchants' => $this->excludeMerchants,
                           ]);

        $notificationConfigChunks = [];

        $limit = $this->getPartnerBankHealthNotificationConfigFetchLimit();

        $configsCount = $this->repo->merchant_notification_config
            ->getTotalEnabledConfigsCountForGivenNotificationType(Type::PARTNER_BANK_HEALTH);

        $lastFetchedConfig = null;

        if ($configsCount > 0)
        {
            $notificationConfigs = $this->repo->merchant_notification_config
                ->getEnabledConfigsForNotificationTypeUsingLastFetchedConfig(Type::PARTNER_BANK_HEALTH,
                                                                             $limit);

            $lastFetchedConfig = $notificationConfigs->last();

            $filteredConfigMerchantIds = $this->filterConfigsBasedOnIncludeExcludeList($notificationConfigs);

            $notificationConfigChunks['chunk_0'] = $filteredConfigMerchantIds;

            $this->dispatchEmailAndSmsNotifications($filteredConfigMerchantIds);
        }

        for ($counter = 1; $counter < (int) ceil($configsCount/$limit); $counter++)
        {
            $notificationConfigs = $this->repo->merchant_notification_config
                ->getEnabledConfigsForNotificationTypeUsingLastFetchedConfig(Type::PARTNER_BANK_HEALTH,
                                                                             $limit,
                                                                             $lastFetchedConfig);

            $lastFetchedConfig = $notificationConfigs->last();

            $filteredConfigMerchantIds = $this->filterConfigsBasedOnIncludeExcludeList($notificationConfigs);

            $notificationConfigChunks['chunk_' . $counter] = $filteredConfigMerchantIds;

            $this->dispatchEmailAndSmsNotifications($filteredConfigMerchantIds);
        }

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_JOBS_DISPATCHED,
                           [
                               'chunk_wise_configs' => $notificationConfigChunks,
                           ]);

        return [
            'message' => 'FTS partner bank fail_fast_health notification processed successfully',
        ];
    }

    public function filterConfigsBasedOnIncludeExcludeList($configs)
    {
        $configMerchantIds = $configs->pluck(ConfigEntity::MERCHANT_ID)->toArray();

        if (array_first($this->includeMerchants) !== 'ALL')
        {
            return array_intersect($configMerchantIds, $this->includeMerchants);
        }

        return array_diff($configMerchantIds, $this->excludeMerchants);
    }

    public function dispatchEmailAndSmsNotifications(array $configMerchantIds)
    {
        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_JOB_REQUEST,
                           [
                               'config_merchant_ids' => $configMerchantIds
                           ]);

        PartnerBankHealthNotification::dispatch($this->mode,
                                                $this->data,
                                                $configMerchantIds);

    }

    public function extractEligibleConfigsAndSendNotifications(array $merchantIds, array $data)
    {
        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_INIT,
                           [
                               'config_merchant_ids' => $merchantIds,
                               'data'                => $data,
                           ]);

        $eligibleConfigs = $this->extractEligibleMerchantNotificationConfigs($merchantIds, $data);

        $smsPayload  = $this->getSmsPayload($data);
        $emailParams = $this->getEmailParams($data);

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_SMS_EMAIL_PARAMS,
                           [
                               'sms_payload'  => $smsPayload,
                               'email_params' => $emailParams
                           ]);

        foreach ($eligibleConfigs as $config)
        {
            $this->sendEmail($config, $emailParams);

            $this->sendSms($config, $smsPayload);
        }
    }

    public function sendSms(ConfigEntity $config, $smsPayload)
    {
        $storkResponse = null;
        $merchantId    = $config->getMerchantId();
        $mobileNumbers = $config->getNotificationMobileNumbers();

        if (empty($mobileNumbers) === true)
        {
            return;
        }

        $mobileNumbers = explode(',', $mobileNumbers);

        foreach($mobileNumbers as $key => $mobileNumber)
        {
            $smsPayload[SmsConstants::DESTINATION] = $mobileNumber;
            $smsPayload[SmsConstants::OWNER_ID]    = $merchantId;

            try
            {
                $storkResponse = $this->app['stork_service']->sendSms($this->app['basicauth']->getMode(), $smsPayload, false);
                $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_SMS_TO_MERCHANT_SENT,
                                   [
                                       MerchantConstants::MERCHANT_ID => $merchantId,
                                       'mobile_number_index'          => $key,
                                       'stork_response'               => $storkResponse,
                                   ]);
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::PARTNER_BANK_HEALTH_SMS_TO_MERCHANT_FAILED,
                    [
                        MerchantConstants::MERCHANT_ID => $merchantId,
                        'mobile_number_index'          => $key,
                        'stork_response'               => $storkResponse
                    ]);
            }
        }

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_SMS_TO_MERCHANT_PROCESSED,
                           [
                               MerchantConstants::MERCHANT_ID => $merchantId
                           ]);
    }

    public function sendEmail(ConfigEntity $config, $emailParams)
    {
        $storkResponse       = null;
        $merchantId          = $config->getMerchantId();
        $merchantDisplayName = $config->merchant->getDisplayName();
        $notificationEmails  = $config->getNotificationEmails();

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_EMAIL_TO_MERCHANT_INIT, ['merchant_id' => $merchantId]);

        if (empty($notificationEmails) === true)
        {
            return;
        }

        $notificationEmails = explode(',', $notificationEmails);

        $mailInstance = new PartnerBankHealthMail($emailParams);
        $mailInstance->params['merchant_id'] = $merchantId;
        $mailInstance->params['merchant_display_name'] = $merchantDisplayName;

        foreach ($notificationEmails as $key => $emailId)
        {
            $mailInstance->to($emailId, $merchantDisplayName);
        }

        try
        {
            $storkResponse = Mail::send($mailInstance);
            $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_EMAIL_TO_MERCHANT_SENT,
                               [
                                   MerchantConstants::MERCHANT_ID => $merchantId,
                                   'stork_response'               => $storkResponse
                               ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::PARTNER_BANK_HEALTH_EMAIL_TO_MERCHANT_FAILED,
                [
                    MerchantConstants::MERCHANT_ID => $merchantId,
                    'stork_response'               => $storkResponse
                ]);
        }


        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_EMAIL_TO_MERCHANT_PROCESSED,
                           [
                               MerchantConstants::MERCHANT_ID => $merchantId
                           ]);
    }

    public function setNotificationData($input)
    {
        $this->includeMerchants = array_pull($input, Constants::INCLUDE_MERCHANTS);
        $this->excludeMerchants = array_pull($input, Constants::EXCLUDE_MERCHANTS);

        $this->data = $input;
    }

    public static function buildEventTypeFromSourceIntegrationTypeAndMode($source, $integrationType, $mode)
    {
        $key = $source . '.' . $integrationType . '.' . strtolower($mode);

        (new Validator())->validateEventType(Entity::EVENT_TYPE, $key);

        return $key;
    }

    public function extractEligibleMerchantNotificationConfigs($merchantIds, $data)
    {
        $eligibleConfigs = [];
        $configs = $this->repo->merchant_notification_config
            ->getEnabledConfigsFromMerchantIdsAndNotificationType($merchantIds,
                                                                  Type::PARTNER_BANK_HEALTH);

        foreach ($configs as $config)
        {
            $isMerchantEligibleForNotification = $this->checkIfMerchantIsEligibleForNotification($config, $data);

            if ($isMerchantEligibleForNotification === true)
            {
                $eligibleConfigs[$config->getId()] = $config;
            }
        }

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_ELIGIBLE_NOTIFICATION_CONFIGS,
                           [
                               'eligible_config_ids' => array_keys($eligibleConfigs)
                           ]);

        return $eligibleConfigs;
    }

    public function getPartnerBankHealthStatus($bankIfsc, $data)
    {
        $lastUpAt   = $data[$bankIfsc][Entity::LAST_UP_AT];
        $lastDownAt = $data[$bankIfsc][Entity::LAST_DOWN_AT];

        if ((empty($lastUpAt) === true) or
            ($lastDownAt > $lastUpAt))
        {
            return Status::DOWN;
        }

        return Status::UP;
    }

    /**
     * This function checks if a merchant is eligible for communication based on the integration, i.e., pool or direct
     * For direct integration, it checks if the merchant has a banking account in the channel that is affected.
     * For shared integration, it merely checks if the banking account is activated.
     *
     * @param ConfigEntity $config
     * @param array        $data
     *
     * @return bool
     */
    private function checkIfMerchantIsEligibleForNotification(ConfigEntity $config, array $data)
    {
        $accountType           = $data[Constants::INTEGRATION_TYPE];
        $activeBankingAccounts = $this->repo->banking_account
            ->fetchActiveBankingAccountsByMerchantIdAndAccountType($config->getMerchantId(),
                                                                   $accountType);

        if($data[Constants::INTEGRATION_TYPE] === AccountType::DIRECT)
        {
            foreach ($activeBankingAccounts as $bankingAccount)
            {
                if ($bankingAccount->getChannel() === self::CHANNEL_MAPPING_FOR_DB[$data[Constants::CHANNEL]])
                {
                    return true;
                }
            }

            return false;
        }

        if (count($activeBankingAccounts) > 0)
        {
            return true;
        }

        return false;
    }
}
