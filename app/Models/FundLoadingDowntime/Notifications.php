<?php

namespace RZP\Models\FundLoadingDowntime;

use App;
use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Base\RepositoryManager;
use RZP\Mail\Base\Constants as MailConstant;
use RZP\Models\Payout\Notifications\SmsConstants;
use RZP\Models\FundLoadingDowntime\Entity as Entity;
use RZP\Mail\FundLoadingDowntime\FundLoadingDowntimeMail;
use RZP\Models\FundLoadingDowntime\Constants as Constants;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType;

class Notifications
{
    protected $app;

    protected $ba;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected $trace;

    protected $stork;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager $repo
     */
    protected $repo;

    protected $note;

    protected $flowType;

    protected $downtimeInformation;

    protected $sendSMS;

    protected $sendEmail;

    protected $emailSentForEmailIds = [];

    protected $smsSentForMobileNumbers = [];

    /**
     * @var FundLoadingDowntimeMail $mailInstance
     */
    protected $mailInstance;

    protected $templateName = null;


    const SMS        = 'sms';
    const EMAIL      = 'email';
    const SENDER     = 'RZPAYX';
    const FAILURES   = 'failures';
    const SKIPPED    = 'skipped';
    const SEND_SMS   = 'send_sms';
    const SUCCESSES  = 'successes';
    const SEND_EMAIL = 'send_email';
    const SOURCE     = 'fund_loading_downtime';
    const smsDateTimeFormat = 'dM h:i a';
    const emailDateTimeFormat = 'd M h:i a';

    const DEFAULT_CONFIG_FETCH_LIMIT = 1000;

    const YES_BANK_PREFIXES = [
        '787878',
        '456456',
    ];

    const ICICI_BANK_PREFIXES = [
        '3434',
        '5656',
    ];

    const SMS_TEMPLATE_MAP = [
        'creation_1'   => 'sms.fund_loading_downtime.creation_1.v1',
        'creation_2'   => 'sms.fund_loading_downtime.creation_2.v1',
        'creation_3'   => 'sms.fund_loading_downtime.creation_3.v1',
        'updation_1'   => 'sms.fund_loading_downtime.update_1.v1',
        'updation_2'   => 'sms.fund_loading_downtime.update_2.v1',
        'resolution'   => 'sms.fund_loading_downtime.resolution.v1',
        'cancellation' => 'sms.fund_loading_downtime.cancelation.v1',
    ];

    const SMS_TEMPLATE_UPDATE_1_V2   = 'sms.fund_loading_downtime.update_1.v2';
    const SMS_TEMPLATE_CREATION_1_V2 = 'sms.fund_loading_downtime.creation_1.v2';

    public function __construct($input, $flowType)
    {
        $this->app = App::getFacadeRoot();

        $this->ba = $this->app['basicauth'];

        $this->trace = $this->app['trace'];

        $this->stork = $this->app['stork_service'];

        $this->repo = $this->app['repo'];

        $this->downtimeInformation = $input[Constants::DOWNTIME_INFO];

        $this->sendSMS = boolval($input[self::SEND_SMS]);

        $this->sendEmail = boolval($input[self::SEND_EMAIL]);

        $this->flowType = $flowType;

        $this->note = trim($input[Constants::NOTE]);
    }

    public function sendNotifications()
    {
        $response = $this->initializeResponse();
        $response[Constants::DOWNTIME_INFO] = $this->downtimeInformation;

        $bankThatIsDown = $this->downtimeInformation[Entity::CHANNEL];

        $startTime = Carbon::now(Timezone::IST)->getTimestamp();

        $merchantNotificationConfigs = $this->repo->merchant_notification_config
            ->getEnabledConfigsForNotificationType(NotificationType::FUND_LOADING_DOWNTIME,
                                                   self::DEFAULT_CONFIG_FETCH_LIMIT);

        $endTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::TIME_TAKEN_TO_FETCH_DATA_FROM_DB,
                           [
                               'time_in_seconds' => $endTime - $startTime
                           ]);

        $merchantIds = $merchantNotificationConfigs->pluck(Constants::MERCHANT_ID)->toArray();

        // get a mapping of merchant ids with the virtual accounts assigned to them
        $merchantIdsWithVAsAssigned = $this->getChannelsAssignedToMerchants($merchantIds);

        $skippedMerchantIds = array_diff_key(array_flip($merchantIds), $merchantIdsWithVAsAssigned);

        // if merchant ids retrieved as a result of table joins do not match entirely with the enabled
        // merchant_notification_configs merchant ids, we trace it for better debugging
        if (count($skippedMerchantIds) > 0)
        {
            $this->trace->info(TraceCode::BULK_SKIP_FUND_LOADING_DOWNTIME_NOTIFICATION_TO_MERCHANT,
                               [
                                   'expected_count'       => count($merchantIds),
                                   'actual_count'         => count($merchantIdsWithVAsAssigned),
                                   'skipped_merchant_ids' => array_keys($skippedMerchantIds),
                                   'reason'               => 'No active virtual accounts'
                               ]);
        }

        $emailParams = $this->getEmailParams();

        $this->mailInstance = new FundLoadingDowntimeMail($this->flowType, $emailParams);

        $smsPayload  = $this->getSmsPayload();

        foreach ($merchantNotificationConfigs as $notificationConfig)
        {
            $emailIds      = explode(',', $notificationConfig->getNotificationEmails());
            $mobileNumbers = explode(',', $notificationConfig->getNotificationMobileNumbers());
            $merchantId    = $notificationConfig->getMerchantId();

            $smsResponse   = [];
            $emailResponse = [];

            $virtualAccountsAssigned = $merchantIdsWithVAsAssigned[$merchantId] ?? null;

            // if no active virtual account exists for a merchant, continue with next merchant
            if($virtualAccountsAssigned === null)
            {
                continue;
            }

            $this->trace->info(TraceCode::VIRTUAL_ACCOUNTS_ASSIGNED_TO_MERCHANT,
                               [
                                   Constants::MERCHANT_ID => $merchantId,
                                   'channels_assigned'    => $virtualAccountsAssigned,
                                   'channel_down'         => $bankThatIsDown
                               ]);

            if ($bankThatIsDown === Constants::ALL)
            {
                if (($virtualAccountsAssigned[Constants::YES_BANK] === true) or
                    ($virtualAccountsAssigned[Constants::ICICI_BANK] === true))
                {
                    if (($this->sendEmail === true) and (count($emailIds) > 0))
                    {
                        $emailResponse = $this->sendEmail($emailIds, $merchantId, $emailParams);
                    }

                    if (($this->sendSMS === true) and (count($mobileNumbers) > 0))
                    {
                        $smsResponse = $this->sendSms($mobileNumbers, $merchantId, $smsPayload);
                    }
                }
            }
            elseif ($virtualAccountsAssigned[$bankThatIsDown] === true)
            {
                if (($this->sendEmail === true) and (count($emailIds) > 0))
                {
                    $emailResponse = $this->sendEmail($emailIds, $merchantId, $emailParams);
                }

                if (($this->sendSMS === true) and (count($mobileNumbers) > 0))
                {
                    $smsResponse = $this->sendSms($mobileNumbers, $merchantId, $smsPayload);
                }
            }
            else
            {
                $this->trace->info(TraceCode::SKIP_FUND_LOADING_DOWNTIME_NOTIFICATION_TO_MERCHANT,
                                   [
                                       Constants::MERCHANT_ID => $merchantId,
                                       'channel_down'         => $bankThatIsDown,
                                       'channels_assigned'    => $virtualAccountsAssigned,
                                       'reason'               => "No active virtual account in $bankThatIsDown"
                                   ]
                );
            }

            $response[self::SMS][self::SUCCESSES]   += $smsResponse[self::SUCCESSES] ?? 0;
            $response[self::SMS][self::FAILURES]    += $smsResponse[self::FAILURES] ?? 0;
            $response[self::SMS][self::SKIPPED]     += $smsResponse[self::SKIPPED] ?? 0;
            $response[self::EMAIL][self::SUCCESSES] += $emailResponse[self::SUCCESSES] ?? 0;
            $response[self::EMAIL][self::FAILURES]  += $emailResponse[self::FAILURES] ?? 0;
            $response[self::EMAIL][self::SKIPPED]   += $emailResponse[self::SKIPPED] ?? 0;
        }

        return $response;
    }

    public function initializeResponse()
    {
        $response[self::SMS][self::SUCCESSES]   = 0;
        $response[self::SMS][self::FAILURES]    = 0;
        $response[self::SMS][self::SKIPPED]     = 0;
        $response[self::EMAIL][self::SUCCESSES] = 0;
        $response[self::EMAIL][self::FAILURES]  = 0;
        $response[self::EMAIL][self::SKIPPED]   = 0;

        return $response;
    }

    protected function sendEmail($emailIds, $merchantId, $emailParams)
    {
        $response[self::SUCCESSES]        = 0;
        $response[self::FAILURES]         = 0;
        $response[self::SKIPPED]          = 0;
        $response[Constants::MERCHANT_ID] = $merchantId;

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_EMAIL_TO_MERCHANT_INIT,
            [
                'count'                => count($emailIds),
                'params'               => $emailParams,
                Constants::MERCHANT_ID => $merchantId
            ]
        );

        $this->mailInstance->setMerchantId($merchantId);
        $storkResponse = null;

        foreach ($emailIds as $index => $emailId)
        {
            $emailId = trim($emailId);

            if (empty($emailId) === true)
            {
                continue;
            }

            if (in_array($emailId, $this->emailSentForEmailIds))
            {
                $this->trace->info(
                    TraceCode::FUND_LOADING_DOWNTIME_EMAIL_ALREADY_SENT_TO_EMAIL_ID,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        'email_id_index'       => $index,
                    ]
                );
                $response[self::SKIPPED]++;
                continue;
            }

            $this->mailInstance->setMerchantEmailId($emailId);

            try
            {
                $storkResponse = Mail::queue($this->mailInstance);
                $response[self::SUCCESSES]++;
                array_push($this->emailSentForEmailIds, $emailId);

                $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_EMAIL_TO_MERCHANT_SENT,
                                   [
                                       Constants::MERCHANT_ID => $merchantId,
                                       'email_id_index'       => $index,
                                       'email_response'       => $storkResponse
                                   ]
                );
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::FUND_LOADING_DOWNTIME_EMAIL_TO_MERCHANT_FAILED,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        'email_id_index'       => $index,
                        'email_response'       => $storkResponse
                    ]
                );

                $response[self::FAILURES]++;
            }
        }

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_EMAIL_TO_MERCHANT_PROCESSED,
            $response
        );

        return $response;
    }

    protected function sendSms($mobileNumbers, $merchantId, $smsPayload)
    {
        $response[self::SUCCESSES] = 0;
        $response[self::FAILURES]  = 0;
        $response[self::SKIPPED]   = 0;

        $response[Constants::MERCHANT_ID]   = $merchantId;
        $smsPayload[SmsConstants::OWNER_ID] = $merchantId;

        $smsPayload = $this->updateSMSParamsAsPerTRAIRegulations($merchantId, $smsPayload);

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_SMS_TO_MERCHANT_INIT,
            [
                'count'                => count($mobileNumbers),
                'payload'              => $smsPayload,
                Constants::MERCHANT_ID => $merchantId,
            ]
        );

        $storkResponse = null;

        foreach ($mobileNumbers as $key => $mobileNumber)
        {
            $smsPayload[SmsConstants::DESTINATION] = trim($mobileNumber);

            if (empty($smsPayload[SmsConstants::DESTINATION]) === true)
            {
                continue;
            }

            if (in_array($mobileNumber, $this->smsSentForMobileNumbers))
            {
                $this->trace->info(
                    TraceCode::FUND_LOADING_DOWNTIME_SMS_ALREADY_SENT_TO_MOBILE_NUMBER,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        'mobile_number_index'  => $key,
                    ]
                );
                $response[self::SKIPPED]++;
                continue;
            }

            try
            {
                $storkResponse = $this->stork->sendSms($this->ba->getMode(), $smsPayload, false);
                $response[self::SUCCESSES]++;
                array_push($this->smsSentForMobileNumbers, $mobileNumber);

                $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_SMS_TO_MERCHANT_SENT,
                                   [
                                       Constants::MERCHANT_ID => $merchantId,
                                       'mobile_number_index'  => $key,
                                       'stork_response'       => $storkResponse,
                                   ]
                );
            }
            catch (\Throwable $e)
            {
                $response[self::FAILURES]++;

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::FUND_LOADING_DOWNTIME_SMS_TO_MERCHANT_FAILED,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        'mobile_number_index'  => $key,
                        'stork_response'       => $storkResponse,
                    ]
                );
            }
        }

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_SMS_TO_MERCHANT_PROCESSED,
            $response
        );

        return $response;
    }

    public function getSmsPayload()
    {
        return [
            SmsConstants::SOURCE                      => self::SOURCE,
            SmsConstants::OWNER_TYPE                  => 'merchant',
            SmsConstants::ORG_ID                      => $this->ba->getAdmin()->getOrgId() ?? '',
            SmsConstants::TEMPLATE_NAME               => $this->getSMSTemplate(),
            SmsConstants::TEMPLATE_NAMESPACE          => SmsConstants::PAYOUTS_CORE_TEMPLATE_NAMESPACE,
            SmsConstants::LANGUAGE                    => SmsConstants::ENGLISH,
            SmsConstants::SENDER                      => self::SENDER,
            SmsConstants::CONTENT_PARAMS              => $this->getSmsParams(),
            SmsConstants::DELIVERY_CALLBACK_REQUESTED => false
        ];
    }

    public function getSmsParams()
    {
        $channel = $this->downtimeInformation[Entity::CHANNEL];

        switch ($channel)
        {
            case Constants::ICICI_BANK :
                $params[Entity::CHANNEL] = 'ICICI';
                break;
            case Constants::YES_BANK :
                $params[Entity::CHANNEL] = 'YESB';
                break;
            default:
                $params[Entity::CHANNEL] = 'All Banks';
        }

        foreach ($this->downtimeInformation[Constants::DURATIONS_AND_MODES] as $key => $value)
        {
            if ($this->flowType === Constants::RESOLUTION)
            {
                $params[Constants::MODES][] = $value[Constants::MODES];
                continue;
            }

            $start = Carbon::createFromTimestamp($value[Entity::START_TIME], Timezone::IST)->format(self::smsDateTimeFormat);

            if ($value[Entity::END_TIME] !== Constants::DEFAULT_END_TIME)
            {
                $end = 'to ' . Carbon::createFromTimestamp($value[Entity::END_TIME], Timezone::IST)->format(self::smsDateTimeFormat);
            }
            else
            {
                $end = $value[Entity::END_TIME];
            }
            $params['start' . strval($key + 1)] = $start;
            $params['end' . strval($key + 1)]   = $end;
            $params['modes' . strval($key + 1)] = $value[Constants::MODES];

            if($key < (count($this->downtimeInformation[Constants::DURATIONS_AND_MODES]) - 1))
            {
                $params['modes' . strval($key + 1)] .= ' and';
            }
        }

        if ($this->flowType === Constants::RESOLUTION)
        {
            $params[Constants::MODES] = implode(',', $params[Constants::MODES]);
        }

        return $params;
    }



    public function getEmailParams()
    {
        $params[SmsConstants::TEMPLATE_NAME] = $this->getEmailTemplate();
        $params['support_email']             = MailConstant::MAIL_ADDRESSES[MailConstant::X_SUPPORT];
        $params['has_notes']                 = (empty($this->note) === false);

        if($params['has_notes'] === true)
        {
            $params[Constants::NOTE] = $this->note;
        }

        $params[Entity::TYPE] = $this->downtimeInformation[Entity::TYPE];
        $params[Entity::SOURCE] = $this->downtimeInformation[Entity::SOURCE];

        switch ($this->downtimeInformation[Entity::CHANNEL])
        {
            case Constants::ICICI_BANK :
                $params[Entity::CHANNEL]  = 'ICICI';
                break;
            case Constants::YES_BANK :
                $params[Entity::CHANNEL]  = 'Yes Bank';
                break;
            case Constants::ALL:
                $params[Entity::CHANNEL]  = 'All Banks';
        }

        foreach ($this->downtimeInformation[Constants::DURATIONS_AND_MODES] as $duration)
        {
            $start = Carbon::createFromTimestamp($duration[Entity::START_TIME], Timezone::IST)->format(self::emailDateTimeFormat);

            if ($duration[Entity::END_TIME] !== Constants::DEFAULT_END_TIME)
            {
                $end = 'to ' . Carbon::createFromTimestamp($duration[Entity::END_TIME], Timezone::IST)->format(self::emailDateTimeFormat);
            }
            else
            {
                $end = $duration[Entity::END_TIME];
            }

            switch ($this->flowType)
            {
                case Constants::RESOLUTION:

                    $params[Constants::MODES][] = implode(', ', explode(',', $duration[Constants::MODES]));
                    break;

                case Constants::CANCELLATION:

                    $params[Entity::START_TIME] = $start;
                    $params[Entity::END_TIME]   = $end;
                    $params[Constants::MODES]   = implode(', ', explode(',', $duration[Constants::MODES]));
                    break;

                case Constants::CREATION:
                case Constants::UPDATION:

                    $params[Constants::DURATIONS_AND_MODES][] = [
                        Entity::START_TIME => $start,
                        Entity::END_TIME   => $end,
                        Constants::MODES   => implode(', ', explode(',', $duration[Constants::MODES]))
                    ];
            }
        }

        if(($this->flowType === Constants::CREATION ) or ($this->flowType === Constants::UPDATION))
        {
            // this means we will choose the 'fund_loading_downtime.creation' or 'fund_loading_downtime.updation' templates
            if(count($params[Constants::DURATIONS_AND_MODES]) === 1)
            {
                // here we won't be passing an array of start_time, end_time and modes
                // we will pull these params out from the array and unset the array
                $params[Entity::START_TIME] = array_pull($params[Constants::DURATIONS_AND_MODES][0], Entity::START_TIME);
                $params[Entity::END_TIME]   = array_pull($params[Constants::DURATIONS_AND_MODES][0], Entity::END_TIME);
                $params[Constants::MODES]   = array_pull($params[Constants::DURATIONS_AND_MODES][0], Constants::MODES);
                unset($params[Constants::DURATIONS_AND_MODES]);
            }
            // else we will choose 'fund_loading_downtime.creation.multiple' or 'fund_loading_downtime.updation.multiple'
            // which will have an array $durations_and_modes where we will have different {start_time, end_time, modes}
        }

        if($this->flowType === Constants::RESOLUTION)
        {
            $params[Constants::MODES] = implode(', ', $params[Constants::MODES]);
        }

        $this->trace->info(TraceCode::EMAIL_PARAMS_FOR_FUND_LOADING_DOWNTIME_NOTIFICATION, $params);

        return $params;

    }

    public function getEmailTemplate()
    {
        $templateName = self::SOURCE . '.' . $this->flowType;

        if(($this->flowType === 'creation') or ($this->flowType === 'updation'))
        {
            if(count($this->downtimeInformation[Constants::DURATIONS_AND_MODES]) > 1)
            {
                $templateName .= '.' . 'multiple';
            }
        }
        $this->trace->info(TraceCode::EMAIL_TEMPLATE_FOR_FUND_LOADING_DOWNTIME_NOTIFICATION,
                           [
                               'template' => $templateName
                           ]);

        return $templateName;
    }

    public function getSMSTemplate()
    {
        $templateKey = $this->flowType;
        $distinctIntervalCount = count($this->downtimeInformation[Constants::DURATIONS_AND_MODES]);

        if (($this->flowType !== Constants::CANCELLATION) and
            ($this->flowType !== Constants::RESOLUTION))
        {
            $templateKey .= '_' . strval($distinctIntervalCount);
        }

        $templateName = self::SMS_TEMPLATE_MAP[$templateKey];

        $this->trace->info(TraceCode::SMS_TEMPLATE_FOR_FUND_LOADING_DOWNTIME_NOTIFICATION,
                           [
                               'template' => $templateName,
                           ]);

        return $templateName;

    }

    public function updateSMSParamsAsPerTRAIRegulations(string $merchantId, $smsPayload)
    {
        $smsTemplateMerchants = (new Admin\Service)->getConfigKey(['key' => ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS]);

        $templateName = $smsPayload[SmsConstants::TEMPLATE_NAME];

        if (array_key_exists($templateName, $smsTemplateMerchants) === true)
        {
            $merchants = $smsTemplateMerchants[$templateName];

            if (($merchants == "*") or
                (in_array($merchantId, $merchants) == true))
            {
                switch ($templateName)
                {
                    case 'sms.fund_loading_downtime.update_1.v1':
                        $smsPayload[SmsConstants::TEMPLATE_NAME] = self::SMS_TEMPLATE_UPDATE_1_V2;

                        $contentParams = $smsPayload[SmsConstants::CONTENT_PARAMS];
                        $contentParams[SmsConstants::TIMINGS] = $contentParams['start1'] . ' ' . $contentParams['end1'];
                        $contentParams[SmsConstants::TIMINGS] = trim($contentParams[SmsConstants::TIMINGS]);
                        $contentParams[SmsConstants::MODES] = $contentParams['modes1'];
                        unset($contentParams['start1']);
                        unset($contentParams['end1']);
                        unset($contentParams['modes1']);

                        $smsPayload[SmsConstants::CONTENT_PARAMS] = $contentParams;

                        break;

                    case 'sms.fund_loading_downtime.creation_1.v1':
                        $smsPayload[SmsConstants::TEMPLATE_NAME] = self::SMS_TEMPLATE_CREATION_1_V2;

                        $contentParams = $smsPayload[SmsConstants::CONTENT_PARAMS];
                        $contentParams[SmsConstants::TIMINGS] = $contentParams['start1'] . ' ' . $contentParams['end1'];
                        $contentParams[SmsConstants::TIMINGS] = trim($contentParams[SmsConstants::TIMINGS]);
                        $contentParams[SmsConstants::MODES] = $contentParams['modes1'];
                        unset($contentParams['start1']);
                        unset($contentParams['end1']);
                        unset($contentParams['modes1']);

                        $smsPayload[SmsConstants::CONTENT_PARAMS] = $contentParams;

                        break;
                }
            }
        }

        return $smsPayload;
    }

    /** Does a DB call and fetches the active virtual accounts assigned all mid's in the input array
     * Some mid's in the input may not be present in the output if there is no active virtual account for those mids in
     * in the input array
     * @param array $merchantIds
     * @return array
     */
    protected function getChannelsAssignedToMerchants(array $merchantIds)
    {
        // get MID's and bank account numbers of shared virtual accounts which are active
        $startTime = Carbon::now(Timezone::IST)->getTimestamp();

        $merchantIdAndAccountNumberColumns = $this->repo->bank_account->getBankAccountAccountNumbersOfActiveVirtualAccountsFromMerchantIds($merchantIds);

        $endTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::TIME_TAKEN_TO_FETCH_DATA_FROM_DB, ['time_in_seconds' => $endTime - $startTime]);

        $merchantIdsAndAccountNumbersMap = $this->combineMerchantIdsWitChannelsAssigned($merchantIdAndAccountNumberColumns);

        $merchantIdsWithChannelsAssigned = [];

        // finally for each MID, determine which virtual accounts they hold based on the account number prefixes
        foreach ($merchantIdsAndAccountNumbersMap as $merchantId => $accountNumbers )
        {
            $merchantIdsWithChannelsAssigned[$merchantId][Constants::YES_BANK] = false;
            $merchantIdsWithChannelsAssigned[$merchantId][Constants::ICICI_BANK] = false;

            foreach ($accountNumbers as $accountNumber)
            {
                $firstSixDigits  = substr($accountNumber, 0, 6);
                $firstFourDigits = substr($accountNumber, 0, 4);

                if (in_array($firstSixDigits, self::YES_BANK_PREFIXES) == true)
                {
                    $merchantIdsWithChannelsAssigned[$merchantId][Constants::YES_BANK] = true;
                }
                if (in_array($firstFourDigits, self::ICICI_BANK_PREFIXES) === true)
                {
                    $merchantIdsWithChannelsAssigned[$merchantId][Constants::ICICI_BANK] = true;
                }
            }
        }
        // return an array of MID's as key and and array of active virtual accounts assigned as the key's value
        // for example [ '10000000000000' => [ 'yesbank' => true, 'icicibank' => false ] , .... ]

        return $merchantIdsWithChannelsAssigned;
    }

    protected function combineMerchantIdsWitChannelsAssigned($merchantIdAndAccountNumberColumns)
    {
        $merchantIdsWithAccountNumbers = [];

        foreach ($merchantIdAndAccountNumberColumns as $midAndAccountNumber)
        {
            $merchantId    = $midAndAccountNumber->getAttribute(Constants::MERCHANT_ID);
            $accountNumber = $midAndAccountNumber->getAttribute(\RZP\Models\BankAccount\Entity::ACCOUNT_NUMBER);

            $merchantIdsWithAccountNumbers[$merchantId][] = $accountNumber;
        }

        return $merchantIdsWithAccountNumbers;
    }
}
