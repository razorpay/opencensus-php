<?php

namespace RZP\Models\FundLoadingDowntime;

use App;
use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\RepositoryManager;
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

    protected $flowType;

    protected $downtimeInformation;

    protected $sendSMS;

    protected $sendEmail;


    const SMS        = 'sms';
    const EMAIL      = 'email';
    const SENDER     = 'RZPAYX';
    const FAILURES   = 'failures';
    const SEND_SMS   = 'send_sms';
    const SUCCESSES  = 'successes';
    const SEND_EMAIL = 'send_email';
    const SOURCE     = 'fund_loading_downtime';

    const YES_BANK_PREFIXES = [
        '787878',
        '456456',
    ];

    const ICICI_BANK_PREFIXES = [
        '3434',
        '5656',
    ];

    const SMS_TEMPLATE_MAP = [
        'creation_1'   => 'sms.fund_loading_downtime.creation_1',
        'creation_2'   => 'sms.fund_loading_downtime.creation_2',
        'creation_3'   => 'sms.fund_loading_downtime.creation_3',
        'updation_1'   => 'sms.fund_loading_downtime.update_1',
        'updation_2'   => 'sms.fund_loading_downtime.update_2',
        'resolution'   => 'sms.fund_loading_downtime.resolution',
        'cancellation' => 'sms.fund_loading_downtime.cancellation',
    ];

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
    }

    public function sendNotifications()
    {
        $response = $this->initializeResponse();
        $response[Constants::DOWNTIME_INFO] = $this->downtimeInformation;

        $bankThatIsDown              = $this->downtimeInformation[Entity::CHANNEL];
        $merchantNotificationConfigs = $this->repo->merchant_notification_config->getEnabledConfigsForNotificationType(NotificationType::FUND_LOADING_DOWNTIME);

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
                        $emailResponse = $this->sendEmail($emailIds, $merchantId);
                    }

                    if (($this->sendSMS === true) and (count($mobileNumbers) > 0))
                    {
                        $smsResponse = $this->sendSms($mobileNumbers, $merchantId);
                    }
                }
            }
            elseif ($virtualAccountsAssigned[$bankThatIsDown] === true)
            {
                if (($this->sendEmail === true) and (count($emailIds) > 0))
                {
                    $emailResponse = $this->sendEmail($emailIds, $merchantId);
                }

                if (($this->sendSMS === true) and (count($mobileNumbers) > 0))
                {
                    $smsResponse = $this->sendSms($mobileNumbers, $merchantId);
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
            $response[self::EMAIL][self::SUCCESSES] += $emailResponse[self::SUCCESSES] ?? 0;
            $response[self::EMAIL][self::FAILURES]  += $emailResponse[self::FAILURES] ?? 0;
        }

        return $response;
    }

    public function initializeResponse()
    {
        $response[self::SMS][self::SUCCESSES]   = 0;
        $response[self::SMS][self::FAILURES]    = 0;
        $response[self::EMAIL][self::SUCCESSES] = 0;
        $response[self::EMAIL][self::FAILURES]  = 0;

        return $response;
    }

    protected function sendEmail($emailIds, $merchantId)
    {
        $response[self::SUCCESSES]        = 0;
        $response[self::FAILURES]         = 0;
        $response[Constants::MERCHANT_ID] = $merchantId;
        $emailParams                      = $this->getEmailParams();

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_EMAIL_TO_MERCHANT_INIT,
            [
                'count'                => count($emailIds),
                'params'               => $emailParams,
                Constants::MERCHANT_ID => $merchantId
            ]
        );

        foreach ($emailIds as $index => $emailId)
        {
            $args = [
                'email_id' => trim($emailId),
                'params'   => $emailParams,
            ];

            $email = new FundLoadingDowntimeMail($this->flowType, $args);

            try
            {
                Mail::send($email);
                $response[self::SUCCESSES]++;

                $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_EMAIL_TO_MERCHANT_SENT,
                                   [
                                       Constants::MERCHANT_ID => $merchantId,
                                       'email_id_index'       => $index
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
                        'email_id_index'       => $index
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

    protected function sendSms($mobileNumbers, $merchantId)
    {
        $response[self::SUCCESSES]        = 0;
        $response[self::FAILURES]         = 0;
        $response[Constants::MERCHANT_ID] = $merchantId;

        $smsPayload = $this->getSmsPayload($merchantId);

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
            $smsPayload['destination'] = trim($mobileNumber);

            try
            {
                $storkResponse = $this->stork->sendSms($this->ba->getMode(), $smsPayload, false);
                $response[self::SUCCESSES]++;

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

    public function getSmsPayload($merchantId)
    {
        return [
            SmsConstants::SOURCE                      => self::SOURCE,
            SmsConstants::OWNER_ID                    => $merchantId,
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
                $params[Entity::CHANNEL] = strtoupper(substr($channel, 0, 5));
                break;
            case Constants::YES_BANK :
                $params[Entity::CHANNEL] = strtoupper(substr($channel, 0, 4));
                break;
            default:
                $params[Entity::CHANNEL] = studly_case($channel);
        }

        foreach ($this->downtimeInformation[Constants::DURATIONS_AND_MODES] as $key => $value)
        {
            if ($this->flowType === Constants::RESOLUTION)
            {
                $params[Constants::MODES][] = $value[Constants::MODES];
                continue;
            }

            $start = Carbon::createFromTimestamp($value[Entity::START_TIME], Timezone::IST)->format("dM H:i A");

            if ($value[Entity::END_TIME] !== Constants::DEFAULT_END_TIME)
            {
                $end = 'to ' . Carbon::createFromTimestamp($value[Entity::END_TIME], Timezone::IST)->format("dM H:i A");
            }
            else
            {
                $end = $value[Entity::END_TIME];
            }
            $params['start' . strval($key + 1)] = $start;
            $params['end' . strval($key + 1)]   = $end;
            $params['modes' . strval($key + 1)] = $value[Constants::MODES];
        }

        if ($this->flowType === Constants::RESOLUTION)
        {
            $params[Constants::MODES] = implode(',', $params[Constants::MODES]);
        }

        return $params;
    }

    public function getEmailParams()
    {
        $durationsAndModes = [];
        $channel           = $this->downtimeInformation[Entity::CHANNEL];
        switch ($channel)
        {
            case Constants::ICICI_BANK :
                $channel = 'ICICI Bank';
                break;
            case Constants::YES_BANK :
                $channel = 'YES BANK';
                break;
            case Constants::ALL:
                $channel = 'All';
        }

        foreach ($this->downtimeInformation[Constants::DURATIONS_AND_MODES] as $duration)
        {
            $start = Carbon::createFromTimestamp($duration[Entity::START_TIME], Timezone::IST)->toDayDateTimeString();

            if ($duration[Entity::END_TIME] !== Constants::DEFAULT_END_TIME)
            {
                $end = 'to ' . Carbon::createFromTimestamp($duration[Entity::END_TIME], Timezone::IST)->toDayDateTimeString();
            }
            else
            {
                $end = $duration[Entity::END_TIME];
            }

            $durationsAndModes[] = [
                Entity::START_TIME => $start,
                Entity::END_TIME   => $end,
                Constants::MODES   => $duration[Constants::MODES]
            ];
        }

        return [
            Entity::TYPE                   => $this->downtimeInformation[Entity::TYPE],
            Entity::SOURCE                 => $this->downtimeInformation[Entity::SOURCE],
            Entity::CHANNEL                => $channel,
            Constants::DURATIONS_AND_MODES => $durationsAndModes,
        ];
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

        return self::SMS_TEMPLATE_MAP[$templateKey];
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
        $merchantIdAndAccountNumberColumns = $this->repo->bank_account->getBankAccountAccountNumbersOfActiveVirtualAccountsFromMerchantIds($merchantIds);

        $merchantIdsAndAccountNumbersMap = $this->combineMerchantIdsWitChannelsAssigned($merchantIdAndAccountNumberColumns);

        $merchantIdsWithChannelsAssigned = [];

        // finally for each MID, determine which virtual accounts they hold based on the account number prefixes
        foreach ($merchantIdsAndAccountNumbersMap as $merchantId => $accountNumbers )
        {
            $merchantIdsWithChannelsAssigned[$merchantId][Constants::YES_BANK] = false;
            $merchantIdsWithChannelsAssigned[$merchantId][Constants::ICICI_BANK] = false;

            foreach( $accountNumbers as $accountNumber)
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
