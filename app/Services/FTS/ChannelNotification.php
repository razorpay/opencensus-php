<?php

namespace RZP\Services\FTS;

use Mail;
use Razorpay\IFSC\IFSC;
use Razorpay\Trace\Logger as Trace;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Constants\Entity as E;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\PartnerBankHealth\Events;
use RZP\Mail\Payout\DowntimeNotification;
use RZP\Models\Payout\Service as PayoutService;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType;
use RZP\Models\Merchant\MerchantNotificationConfig\Entity as ConfigEntity;

class ChannelNotification
{
    const RX_TEST                          = 'rx-test';
    const RX_LIVE                          = 'rx-live';
    const DEFAULT_LIMIT                    = 100;
    const PROCESS_EVENT_REQUEST_TIMEOUT_MS = 350;

    protected $app;

    protected $trace;

    protected $raven;

    protected $repo;

    protected $configs;

    // TODO: Will change once merchant specific logic is plugged in
    protected $internalContact = [
        '9008516469',
        '7036916099',
        '8976670177',
    ];

    protected $internalEmails = [
        'sagar.gupta@razorpay.com',
    ];

    protected $templateMap = [
        'partner_resolved_sms_template'       => 'sms.payout.partner_downtime_resolved',
        'partner_started_sms_template'        => 'sms.payout.partner_downtime_created',
        'partner_started_email_template'      => 'emails.payout.partner_bank_downtime_email',
        'partner_resolved_email_template'     => 'emails.payout.partner_bank_downtime_resolution_email',
        'beneficiary_resolved_sms_template'   => 'sms.payout.bene_downtime_resolved',
        'beneficiary_started_sms_template'    => 'sms.payout.bene_downtime_created',
        'beneficiary_started_email_template'  => 'emails.payout.bene_bank_downtime_email',
        'beneficiary_resolved_email_template' => 'emails.payout.bene_bank_downtime_resolution_email',
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->raven = $this->app['raven'];

        $this->repo = $this->app['repo'];

        $this->configs = [];
    }

    /**
     *  Here, input contains mode and channel
     *  {'mode': 'IMPS', 'channel': 'ICICI', type: 'partner', status: 'UP'}
     * @param array $input
     */
    public function channelNotify(array $input)
    {
        $this->trace->info(TraceCode::EVENT_NOTIFICATION_FROM_FTS_RECEIVED,
                           [
                               'input' => $input
                           ]);

        (new PayoutService())->processEventNotificationFromFts($input);

        switch($input['payload']['source'])
        {
            case "BENEFICIARY":
                return $this->sendBeneBankHealthUpdateNotification($input);

            case Events::FAIL_FAST_HEALTH:
            case Events::DOWNTIME:
                return ["message" => "FTS partner bank health webhook processed successfully"];
            case Events::PARTNER_BANK_HEALTH:
                return ["message" => "FTS partner bank downtime webhook processed successfully"];
            default:
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                              null,
                                              $input['payload']['source'],
                                              "unknown source");
        }
    }

    protected function sendEmail($result, $toEmailIds)
    {
        if (empty($toEmailIds) === true)
        {
            // this means no email ids available for the mid
            $this->trace->info(TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_SKIPPED,
                               [
                                   ConfigEntity::NOTIFICATION_EMAILS => $toEmailIds
                               ]);
            return;
        }

        $toEmailIds = (is_array($toEmailIds) === true) ? $toEmailIds : explode(',', $toEmailIds);

        $template = $this->getTemplate($result, NotificationMode::MODE_EMAIL);

        $params = $this->getParams($result);

        $subject = $this->getSubject($result);

        // Extract info from result section and fill the data section array accordingly
        $data = [
            'to'       => $toEmailIds, // Will change with merchant logic
            'subject'  => $subject,
            'body'     => $params,
            'template' => $template,
        ];

        $downtimeNotification = new DowntimeNotification($data);

        try
        {
            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_INIT,
                [
                    'request' => $data,
                ]);

            Mail::send($downtimeNotification);

            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_COMPLETE,
                [
                    'response' => $data,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_FAILURE,
                [
                    'data' => $data,
                ]
            );
        }
    }

    protected function getTemplate($result, $mode)
    {
        $source = strtolower($result[Constants::SOURCE]);

        $templateKey = strtolower($source) . '_';

        $templateKey .= ((isset($result['status']) === true) and
            $result['status'] === 'started') ? 'started_' : 'resolved_';

        $templateKey .= strtolower($mode) . '_template';

        $templateName = $this->templateMap[$templateKey];

        $this->trace->info(
            TraceCode::FTS_NOTIFY_TEMPLATE,
            [
                'type'     => $source,
                'key'      => $templateKey,
                'template' => $templateName,
            ]);

        return $templateName;
    }

    protected function processSms($result, $contacts)
    {
        if(empty($contacts) === true)
        {
            // this can only happen if we have no mobile numbers for that mid
            $this->trace->info(TraceCode::FTS_DOWNTIME_NOTIFY_SMS_SKIPPED,
                               [
                                   ConfigEntity::NOTIFICATION_MOBILE_NUMBERS => $contacts
                               ]);

            return;
        }

        $contacts = is_array($contacts) === true ? $contacts : explode(',', $contacts);

        $template = $this->getTemplate($result, NotificationMode::MODE_SMS);

        $params = $this->getParams($result);

        foreach($contacts as $contact)
        {
            $data =  [
                'receiver' => $contact,
                'source'   => Constants::DEFAULT_SOURCE,
                'template' => $template,
                'params'   => $params,
            ];

            $this->sendSms($data);
        }
    }

    protected function getContactList()
    {
        return $this->internalContact;
    }

    protected function sendSms($data)
    {
        try
        {
            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_SMS_INIT,
                [
                    'request' => $data,
                ]);

            $this->raven->sendSms($data, false);

            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_SMS_COMPLETE,
                [
                    'request' => $data,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FTS_DOWNTIME_NOTIFY_SMS_FAILURE,
                $data);
        }
    }

    protected function getParams($data)
    {
        $params = [
            'transfer_mode' => $data['method'][0],
        ];

        if (isset($data[Constants::SOURCE]) === true and $data[Constants::SOURCE] === 'BENEFICIARY')
        {
            $params +=  [
                'bank_name'       => IFSC::getBankName($data['instrument']['bank']),
                'ifsc_short_code' => $data['instrument']['bank'],
            ];
        }

        return $params;
    }

    protected function getSubject($result)
    {
        $subject = '';

        if (isset($result[Constants::SOURCE]) === true and $result[Constants::SOURCE] === 'PARTNER')
        {
            if ((isset($result['status']) === true) and $result['status'] === 'resolved')
            {
                $subject = 'RazorpayX: Service available | You can now process payouts through RazorpayX';
            }
            else
            {
                $subject = 'RazorpayX: Service downtime alert';
            }
        }
        else
        {
            if ((isset($result['status']) === true) and $result['status'] === 'resolved')
            {
                $subject = 'RazorpayX: Service resumed | ' .
                    $result['instrument']['bank'] . '  beneficiaries are now available to accept payouts.';
            }
            else
            {
                $subject = 'RazorpayX: Service downtime | ' .
                    $result['instrument']['bank'] . ' beneficiaries are facing failures in receiving payouts.';
            }
        }

        return $subject;
    }

    protected function sendBeneBankHealthUpdateNotification($result)
    {
        $notificationConfigs = $this->repo
                                    ->merchant_notification_config
                                    ->getEnabledConfigsForNotificationType(NotificationType::BENE_BANK_DOWNTIME,
                                                                           self::DEFAULT_LIMIT);

        $processedResult = $this->preProcessNotification($result);

        $this->sendEmail($processedResult, $this->internalEmails);

        $this->processSms($processedResult, $this->internalContact);

        foreach ($notificationConfigs as $config)
        {
            $this->sendEmail($processedResult, $config->getNotificationEmails());

            $this->processSms($processedResult, $config->getNotificationMobileNumbers());

            $this->processWebhook($result, $config->getMerchantId());
        }

        return [
            'message' => 'FTS channel notification processed successfully',
        ];
    }

    protected function preProcessNotification($input): array
    {
        // since payload has changed we are adding the check for backward compatibility.
        //todo: need to remove if clause once new payload is live
        if (in_array('contains', array_keys($input), true) === true)
        {
            $entity = $input['contains'][0];

            return $input['payload'][$entity]['entity'];
        }
        return $input['payload'];
    }

    // TODO: Needs to be changed entirely with API
    protected function getWebhookRequest($input)
    {
        if (in_array('contains', array_keys($input), true) === true)
        {
            return $this->getWebhookRequestForBackwardCompatibilty($input);
        }

        $webhookPayload = [];

        $webhookPayload['contains'] = [Constants::PAYOUT_DOWNTIME];

        $webhookPayload['entity'] = 'event';

        $webhookPayload['event'] = Constants::PAYOUT_DOWNTIME . '.' . $input['payload']['status'];

        $payload = $input['payload'];

        $webhookPayload['payload'][Constants::PAYOUT_DOWNTIME]['entity'] = $payload;

        $webhookPayload['payload'][Constants::PAYOUT_DOWNTIME]['entity']['entity'] =
            str_replace('bene_health', Constants::PAYOUT_DOWNTIME,
                $webhookPayload['payload'][Constants::PAYOUT_DOWNTIME]['entity']['entity']);

        $webhookPayload['payload'][Constants::PAYOUT_DOWNTIME]['entity']['id'] =
            Constants::PAYOUT_DOWNTIME_PREFIX . $webhookPayload['payload'][Constants::PAYOUT_DOWNTIME]['entity']['id'];

        return $webhookPayload;
    }

    protected function getWebhookRequestForBackwardCompatibilty($input)
    {
        $entity = $input['contains'][0];

        $input['contains'] = [Constants::PAYOUT_DOWNTIME];

        $input['event'] = str_replace('bene_health', Constants::PAYOUT_DOWNTIME, $input['event']);

        $input['payload'][$entity]['entity']['entity'] = Constants::PAYOUT_DOWNTIME;

        $input['payload'][$entity]['entity']['id'] =
            Constants::PAYOUT_DOWNTIME_PREFIX . $input['payload'][$entity]['entity']['id'];

        $input['payload'] = [
            Constants::PAYOUT_DOWNTIME => $input['payload'][$entity],
        ];

        return $input;
    }

    protected function processWebhook($input, string $merchant_id)
    {
        try
        {
            $webhookData = $this->getWebhookRequest($input);

            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_WEBHOOK_INIT,
                [
                    'request' => $webhookData,
                ]);

            $service = $this->app['stork_service'];

            $service->init($this->app['rzp.mode'], Product::BANKING);

            $processEventReq = [
                'event' => [
                    'id'         => UniqueIdEntity::generateUniqueId(),
                    'service'    => $service->service,
                    'owner_id'   => $merchant_id,
                    'owner_type' => E::MERCHANT,
                    'name'       => $webhookData['event'],
                    'payload'    => json_encode($webhookData),
                ],
            ];

            $response = $service->request(
                '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent',
                $processEventReq,
                self::PROCESS_EVENT_REQUEST_TIMEOUT_MS
            );

            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_WEBHOOK_COMPLETE,
                [
                    'request' => $webhookData,
                    'response' => $response
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FTS_DOWNTIME_NOTIFY_WEBHOOK_FAILURE,
                [
                    'data' => $input,
                ]
            );
        }
    }
}
