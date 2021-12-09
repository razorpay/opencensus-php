<?php

namespace RZP\Models\FundLoadingDowntime;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Mail;

use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use RZP\Models\FundLoadingDowntime\Entity as Entity;
use RZP\Mail\FundLoadingDowntime\FundLoadingDowntimeMail;
use RZP\Models\FundLoadingDowntime\Constants as Constants;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType;
use RZP\Models\Merchant\MerchantNotificationConfig\Entity as ConfigsEntity;

class Notifications
{
    protected $app;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected $trace;

    protected $raven;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager $repo
     */
    protected $repo;

    protected $flowType;

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
        'updation_1'   => 'sms.fund_loading_downtime.updation_1',
        'updation_2'   => 'sms.fund_loading_downtime.updation_2',
        'resolution'   => 'sms.fund_loading_downtime.resolution',
        'cancellation' => 'sms.fund_loading_downtime.cancellation',
    ];

    public function __construct($flowType)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->raven = $this->app['raven'];

        $this->repo = $this->app['repo'];

        $this->flowType = $flowType;

    }

    public function sendNotifications($downtimeInformation, $sendSMS, $sendEmail)
    {

        $response[Constants::DOWNTIME_INFO] = $downtimeInformation;

        $bankThatIsDown = $downtimeInformation[Entity::CHANNEL];

        $merchantContactDetails = $this->repo->merchant_notification_config
                                             ->getEnabledConfigsForNotificationType(NotificationType::FUND_LOADING_DOWNTIME);

        $response[self::SMS][self::SUCCESSES]   = 0;
        $response[self::SMS][self::FAILURES]    = 0;
        $response[self::EMAIL][self::SUCCESSES] = 0;
        $response[self::EMAIL][self::FAILURES]  = 0;

        foreach ($merchantContactDetails as $merchantContacts)
        {
            $emailIds      = explode(',', $merchantContacts->getNotificationEmails());
            $mobileNumbers = explode(',', $merchantContacts->getNotificationMobileNumbers());
            $merchantId    = $merchantContacts->getMerchantId();
            $smsResponse   = [];
            $emailResponse = [];

            $channelsAssigned = $this->getChannelsAssignedToMerchant($merchantId);

            if ($bankThatIsDown === Constants::ALL)
            {
                if ($channelsAssigned[Constants::YES_BANK] === true or
                    $channelsAssigned[Constants::ICICI_BANK] === true)
                {
                    if ((boolval($sendEmail) === true) and (count($emailIds) > 0))
                    {
                        $emailResponse = $this->sendEmail($downtimeInformation, $emailIds, $merchantId);
                    }

                    if ((boolval($sendSMS) === true) and (count($mobileNumbers) > 0))
                    {
                        $smsResponse = $this->sendSms($downtimeInformation, $mobileNumbers, $merchantId);
                    }
                }
            }
            elseif ($channelsAssigned[$bankThatIsDown] === true)
            {
                if ((boolval($sendEmail) === true) and (count($emailIds) > 0))
                {
                    $emailResponse = $this->sendEmail($downtimeInformation, $emailIds, $merchantId);
                }

                if ((boolval($sendSMS) === true) and (count($mobileNumbers) > 0))
                {
                    $smsResponse = $this->sendSms($downtimeInformation, $mobileNumbers, $merchantId);
                }
            }

            $response[self::SMS][self::SUCCESSES]   += $smsResponse[self::SUCCESSES] ?? 0;
            $response[self::SMS][self::FAILURES]    += $smsResponse[self::FAILURES] ?? 0;
            $response[self::EMAIL][self::SUCCESSES] += $emailResponse[self::SUCCESSES] ?? 0;
            $response[self::EMAIL][self::FAILURES]  += $emailResponse[self::FAILURES] ?? 0;
        }

        return $response;
    }


    protected function sendEmail($downtimeInformation, $emailIds, $merchantId)
    {
        $response[self::SUCCESSES] = 0;
        $response[self::FAILURES]  = 0;
        $response["merchant_id"]   = $merchantId;
        $emailParams               = $this->getEmailParams($downtimeInformation);

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_EMAIL_INIT,
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
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::FAILED_TO_SEND_FUND_LOADING_DOWNTIME_EMAIL,
                    [
                        Constants::MERCHANT_ID    => $merchantId,
                        'email_id_index' => $index
                    ]
                );

                $response[self::FAILURES]++;
            }
        }

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_EMAIL_COMPLETE,
            $response
        );

        return $response;
    }


    protected function sendSms($downtimeInformation, $mobileNumbers, $merchantId)
    {
        $response[self::SUCCESSES]        = 0;
        $response[self::FAILURES]         = 0;
        $response[Constants::MERCHANT_ID] = $merchantId;

        $smsPayload = [
            'source'   => self::SOURCE,
            'template' => $this->getSMSTemplate($downtimeInformation[Constants::DURATIONS_AND_MODES]),
            'sender'   => self::SENDER,
            'params'   => $this->getSmsParams($downtimeInformation),
        ];

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_SMS_INIT,
            [
                'count'                => count($mobileNumbers),
                'payload'              => $smsPayload,
                Constants::MERCHANT_ID => $merchantId,
            ]
        );

        foreach ($mobileNumbers as $key => $mobileNumber)
        {
            $smsPayload['receiver'] = trim($mobileNumber);

            try
            {
                $this->raven->sendSms($smsPayload, false);
                $response[self::SUCCESSES]++;
            }
            catch (\Throwable $e)
            {
                $response[self::FAILURES]++;

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::FAILED_TO_SEND_FUND_LOADING_DOWNTIME_SMS,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        'mobile_number_index'  => $key
                    ]
                );
            }
        }

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_SMS_COMPLETE,
            $response
        );

        return $response;
    }

    public function getSmsParams($downtime)
    {
        $channel = $downtime[Entity::CHANNEL];

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

        foreach ($downtime[Constants::DURATIONS_AND_MODES] as $key => $value)
        {
            if ($this->flowType === Constants::RESOLUTION)
            {
                $params[Constants::MODES][] = $value[Constants::MODES];
                continue;
            }

            $start = Carbon::createFromTimestamp($value[Entity::START_TIME], 'IST')->format("dM H:i A");

            if ($value[Entity::END_TIME] !== Constants::DEFAULT_END_TIME)
            {
                $end = 'to ' . Carbon::createFromTimestamp($value[Entity::END_TIME], 'IST')->format("dM H:i A");
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

    public function getEmailParams($downtime)
    {
        $durationsAndModes = [];
        $channel           = $downtime[Entity::CHANNEL];
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

        foreach ($downtime[Constants::DURATIONS_AND_MODES] as $duration)
        {
            $start = Carbon::createFromTimestamp($duration[Entity::START_TIME], 'IST')->toDayDateTimeString();

            if ($duration[Entity::END_TIME] !== Constants::DEFAULT_END_TIME)
            {
                $end = 'to ' . Carbon::createFromTimestamp($duration[Entity::END_TIME], 'IST')->toDayDateTimeString();
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
            Entity::TYPE                   => $downtime[Entity::TYPE],
            Entity::SOURCE                 => $downtime[Entity::SOURCE],
            Entity::CHANNEL                => $channel,
            Constants::DURATIONS_AND_MODES => $durationsAndModes,
        ];
    }

    public function getSMSTemplate($durationsAndModes)
    {
        $templateKey = $this->flowType;

        if (($this->flowType !== Constants::CANCELLATION) and
            ($this->flowType !== Constants::RESOLUTION))
        {
            $templateKey .= '_' . strval(count($durationsAndModes));
        }

        return self::SMS_TEMPLATE_MAP[$templateKey];
    }

    protected function getChannelsAssignedToMerchant($merchantId)
    {
        $bankAccountNumbers = $this->repo->bank_account->getBankAccountAccountNumbersOfActiveVirtualAccountsFromMerchantId($merchantId);

        $channels[Constants::YES_BANK]   = false;
        $channels[Constants::ICICI_BANK] = false;

        foreach ($bankAccountNumbers as $accountNumberColumn)
        {
            $accountNumber   = $accountNumberColumn->getAccountNumber();
            $firstSixDigits  = substr($accountNumber, 0, 6);
            $firstFourDigits = substr($accountNumber, 0, 4);

            if (in_array($firstSixDigits, self::YES_BANK_PREFIXES) == true)
            {
                $channels[Constants::YES_BANK] = true;
            }
            if (in_array($firstFourDigits, self::ICICI_BANK_PREFIXES) === true)
            {
                $channels[Constants::ICICI_BANK] = true;
            }
            if (($channels[Constants::YES_BANK] === true) and
                ($channels[Constants::ICICI_BANK] === true))
            {
                return $channels;
            }
        }

        return $channels;
    }
}
