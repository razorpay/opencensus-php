<?php

namespace RZP\Services\FTS;

use Mail;
use Razorpay\IFSC\IFSC;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Mail\Payout\DowntimeNotification;

class ChannelNotification
{
    const DEFAULT_LIMIT = 100;

    protected $app;

    protected $trace;

    protected $raven;

    protected $repo;

    protected $configs;

    // TODO: Will change once merchant specific logic is plugged in
    protected $internalContact = [
        '9980755411', // Pawan
        '9845404807', // Anshuman
        '9130522794', // Likhit
        '8050408646', // Lokesh
        '8976670177', // Sagar
        '8861655100', // Karna
    ];

    protected $internalEmails = [
        'sagar.gupta@razorpay.com',
        'anshuman.p@razorpay.com',
        'pawan.murarka@razorpay.com'
    ];

    protected $templateMap = [
        'partner_resolved_sms_template'   => 'sms.payout.partner_downtime_resolved',
        'partner_downtime_sms_template'   => 'sms.payout.partner_downtime_created',
        'partner_downtime_email_template' => 'emails.payout.partner_bank_downtime_email',
        'partner_resolved_email_template' => 'emails.payout.partner_bank_downtime_resolution_email',
        'bene_resolved_sms_template'      => 'sms.payout.bene_downtime_resolved',
        'bene_downtime_sms_template'      => 'sms.payout.bene_downtime_created',
        'bene_downtime_email_template'    => 'emails.payout.bene_bank_downtime_email',
        'bene_resolved_email_template'    => 'emails.payout.bene_bank_downtime_resolution_email',
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
        // TODO: Add notification specific logic here and fill the data section accordingly
        $result = $input;

        $this->getConfigAndSendNotification($result);
    }

    protected function sendEmail($result, $toEmailIds)
    {
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
        $type = ((isset($result[Constants::TYPE]) === true) and
            $result[Constants::TYPE] === 'partner') ? 'partner' : 'bene';

        $templateKey = strtolower($type) . '_';

        $templateKey .= ((isset($result['status']) === true) and
            $result['status'] === 'UP') ? 'resolved_' : 'downtime_';

        $templateKey .= strtolower($mode) . '_template';

        $templateName = $this->templateMap[$templateKey];

        $this->trace->info(
            TraceCode::FTS_NOTIFY_TEMPLATE,
            [
                'type'     => $type,
                'key'      => $templateKey,
                'template' => $templateName,
            ]);

        return $templateName;
    }

    protected function processSms($result, $contacts)
    {
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
            'transfer_mode' => $data['mode'],
        ];

        if (isset($data[Constants::TYPE]) === true and $data[Constants::TYPE] === 'bene')
        {
            $params +=  [
                'bank_name'       => IFSC::getBankName($data['channel']),
                'ifsc_short_code' => $data['channel'],
            ];
        }

        return $params;
    }

    protected function getSubject($result)
    {
        $subject = '';

        if (isset($result[Constants::TYPE]) === true and $result[Constants::TYPE] === 'partner')
        {
            if ((isset($result['status']) === true) and $result['status'] === 'UP')
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
            if ((isset($result['status']) === true) and $result['status'] === 'UP')
            {
                $subject = 'RazorpayX: Service resumed | ' .
                    $result['channel'] . '  beneficiaries are now available to accept payouts.';
            }
            else
            {
                $subject = 'RazorpayX: Service downtime | ' .
                    $result['channel'] . ' beneficiaries are facing failures in receiving payouts.';
            }
        }

        return $subject;
    }

    protected function getConfigAndSendNotification($result)
    {
        $notificationConfigs = $this->repo
                                    ->merchant_notification_config
                                    ->getEnabledConfigs(self::DEFAULT_LIMIT);

        $this->sendEmail($result, $this->internalEmails);

        $this->processSms($result, $this->internalContact);

        foreach ($notificationConfigs as $config)
        {
            $this->sendEmail($result, $config->getNotificationEmails());

            $contactList = explode(',', $config->getNotificationMobileNumbers());

            $this->processSms($result, $contactList);
        }
    }
}
