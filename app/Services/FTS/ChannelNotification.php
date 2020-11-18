<?php

namespace RZP\Services\FTS;

use Mail;
use Razorpay\IFSC\IFSC;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Mail\Payout\DowntimeNotification;

class ChannelNotification
{

    protected $app;

    protected $trace;

    protected $raven;


    // TODO: Will change once merchant specific logic is plugged in
    protected $internalContact = [
        '9980755411', // Pawan
        '9845404807', // Anshuman
        '9130522794', // Likhit
        '8050408646', // Lokesh
        '8976670177', // Sagar
        '8861655100', // Karna
    ];

    protected $templateMap = [
        'partner_resolved_sms_template'   => 'sms.payout.partner_downtime_resolved',
        'partner_downtime_sms_template'   => 'sms.payout.partner_downtime_created',
        'partner_downtime_email_template' => 'emails.payout.partner_downtime_created',
        'partner_resolved_email_template' => 'emails.payout.partner_downtime_resolved',
        'bene_resolved_sms_template'      => 'sms.payout.bene_downtime_resolved',
        'bene_downtime_sms_template'      => 'sms.payout.bene_downtime_created',
        'bene_downtime_email_template'    => 'emails.payout.bene_downtime_created',
        'bene_resolved_email_template'    => 'emails.payout.bene_downtime_resolved',
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->raven = $this->app['raven'];
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

        $this->sendEmail($result);

        $this->processSms($result);
    }

    protected function sendEmail($result)
    {
        $template = $this->getTemplate($result, NotificationMode::MODE_EMAIL);

        $params = $this->getParams($result);

        $subject = $this->getSubject($result);

        // Extract info from result section and fill the data section array accordingly
        $data = [
            'to'       => 'pawan.murarka@razorpay.com', // Will change with merchant logic
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

    protected function processSms($result)
    {
        $template = $this->getTemplate($result, NotificationMode::MODE_SMS);

        $contacts  = $this->getContactList();

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
                $subject = 'You can now process transactions to vendors through RazorpayX.';
            }
            else
            {
                $subject = 'High failure rates observed for transactions on RazorpayX';
            }
        }
        else
        {
            if ((isset($result['status']) === true) and $result['status'] === 'UP')
            {
                $subject = 'You can now process transactions to vendors with ' .
                    $result['channel'] . '  account.';
            }
            else
            {
                $subject = 'Issue with payments to vendor with ' .
                    $result['channel'] . ' account due to high failure rates';
            }
        }

        return $subject;
    }
}
