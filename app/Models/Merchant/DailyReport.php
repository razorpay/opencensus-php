<?php

namespace RZP\Models\Merchant;

use Config;
use Carbon\Carbon;
use Mail;

use RZP\Base\RuntimeManager;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Settlement;
use RZP\Trace\TraceCode;

class DailyReport extends Base\Core
{
    protected $merchant;

    protected $timeLowerLimit;

    protected $timeUpperLimit;

    protected $date;

    protected $data;

    const DAILY_REPORT_EMAIL_TEMPLATE   = 'emails.merchant.daily_report';

    /**
     * Generates a new daily report
     * @param String $id Merchant Id
     */
    function __construct($merchant, $data)
    {
        parent::__construct();

        $this->merchant = $merchant;

        // date format = 6th July 2015
        $this->date = Carbon::yesterday("Asia/Kolkata")->format('jS F Y');

        $this->data = array_merge($data, $this->getMerchantData());

        // 00:00 Yesterday
        $this->timeLowerLimit = Carbon::yesterday("Asia/Kolkata")->timestamp;

        // 00:00 Today
        $this->timeUpperLimit = Carbon::today("Asia/Kolkata")->timestamp;

        $this->increaseAllowedSystemLimits();
    }

    /**
     * Sends the daily report
     * @return array of summary data
     * array is empty if mail wasn't sent
     */
    public function send()
    {
        if ($this->isBlank() === false)
        {
            $this->sendDailyReport();

            return $this->merchant->getId();
        }

        return null;
    }

    /**
     * Sends daily transaction report over email to the given merchant
     * @param  String $id merchant id
     * @return null
     */
    protected function sendDailyReport()
    {
        $data = $this->data;

        $view = ['html' => self::DAILY_REPORT_EMAIL_TEMPLATE];

        // Log merchant whose data has been computed
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_DATA,
            array(
                    'merchant_id'   => $this->merchant->getId(),
                    'merchant_name' => $this->merchant->getBillingLabelElseName(),
                    'captured'      => $data['captured']['count'],
                    'authorized'    => $data['authorized']['count'],
                    'refunds'       => $data['refunds']['count'],
                    'settlement'    => $data['settlements']['sum'],
                    'setl_count'    => $data['settlements']['count'],
                    )
        );

        Mail::queue($view, $data, function($message) use ($data)
        {
            $to = $data['email'];

            // to might be an array
            if (is_array($to))
            {
                foreach ($to as $email)
                {
                    $message->to($email);
                }
            }
            else
            {
                // This should not be getting called
                // But just for fallback
                $message->to($to);
            }

            $message->from('reports@razorpay.com');

            $message->replyTo('support@razorpay.com', 'Razorpay Support');

            $message->cc('notifications@razorpay.com');

            $message->subject('Razorpay | Daily Transaction Report for ' . $data['date']);
        });
    }

    protected function getMerchantData()
    {
        return [
            'billing_label'  => $this->merchant->getBillingLabelElseName(),
            'account_number' => $this->merchant->getRedactedAccountNumber(),
            'email'          => $this->merchant->getTransactionReportEmail(),
            'date'           => $this->date,
        ];
    }

    protected function isBlank()
    {
        $data = $this->data;

        return (($data['captured']['count'] === 0) and
                ($data['authorized']['count'] === 0) and
                ($data['refunds']['count'] === 0) and
                ($data['settlements']['count'] === 0));
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(3000);
    }
}
