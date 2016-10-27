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
    function __construct()
    {
        parent::__construct();

        // date format = 6th July 2015
        $this->date = Carbon::yesterday("Asia/Kolkata")->format('jS F Y');

        // 00:00 Yesterday
        $this->timeLowerLimit = Carbon::yesterday("Asia/Kolkata")->timestamp;

        // 00:00 Today
        $this->timeUpperLimit = Carbon::today("Asia/Kolkata")->timestamp;

        $this->increaseAllowedSystemLimits();
    }

    public function sendReportForAllMerchants($input)
    {
        $from = Carbon::yesterday("Asia/Kolkata")->timestamp;

        $to = Carbon::today("Asia/Kolkata")->timestamp;

        // Trace to indicate start of mailing
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_MAILING,
            [$from, $to]
        );

        $authMerchants = $this->repo->payment
                                ->fetchAuthorizedSummary()
                                ->getStringAttributesByKey('merchant_id');

        $captureMerchants = $this->repo->payment
                                ->fetchCapturedSummaryBetweenTimestamp($from, $to)
                                ->getStringAttributesByKey('merchant_id');

        $refundMerchants = $this->repo->refund
                                ->fetchRefundSummaryBetweenTimestamp($from, $to)
                                ->getStringAttributesByKey('merchant_id');

        $setlMerchants = $this->repo->settlement
                                ->fetchSettlementSummaryBetweenTimestamp($from, $to)
                                ->getStringAttributesByKey('merchant_id');

        if (isset($input[Entity::ID]) === true)
        {
            $merchantIds = $input[Entity::ID];
        }
        else
        {
            $merchantIds = array_unique(
                array_merge(
                    array_keys($captureMerchants),
                    array_keys($authMerchants),
                    array_keys($refundMerchants),
                    array_keys($setlMerchants)
                )
            );
        }

        // Summary of merchants mailed
        $mailedMerchantsSummary = [
            'sentIds'    => [],
            'skippedIds' => 0,
            'failedIds'  => []
        ];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $zeroArray = array_fill_keys(['sum', 'count'], 0);

                $data = [
                    'authorized' => $authMerchants[$merchantId]    ?? $zeroArray,
                    'authorized' => $authMerchants[$merchantId]    ?? $zeroArray,
                    'captured'   => $captureMerchants[$merchantId] ?? $zeroArray,
                    'refunds'    => $refundMerchants[$merchantId]  ?? $zeroArray,
                    'settlements'=> $setlMerchants[$merchantId]    ?? $zeroArray,
                ];

                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $data = array_merge($data, $this->getMerchantData($merchant));

                $sentId = $this->send($merchant, $data);

                if (is_null($sentId))
                {
                    $mailedMerchantsSummary['skippedIds']++;
                }
                else
                {
                    $mailedMerchantsSummary['sentIds'][] = $sentId;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex, Trace::WARNING, TraceCode::SETTLEMENT_DAILY_REPORT_FAILURE);

                $mailedMerchantsSummary['failedIds'][] = $merchantId;
            }
        }

        // Log just the result of the settlement reports
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_RESULT,
            $mailedMerchantsSummary
        );

        return $mailedMerchantsSummary;
    }

    /**
     * Sends the daily report
     * @return array of summary data
     * array is empty if mail wasn't sent
     */
    public function send($merchant, $data)
    {
        if ($this->isBlank($data) === false)
        {
            $this->sendDailyReport($merchant, $data);

            return $merchant->getId();
        }

        return null;
    }

    /**
     * Sends daily transaction report over email to the given merchant
     * @param  String $id merchant id
     * @return null
     */
    protected function sendDailyReport($merchant, $data)
    {
        $view = ['html' => self::DAILY_REPORT_EMAIL_TEMPLATE];

        // Log merchant whose data has been computed
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_DATA,
            array(
                    'merchant_id'   => $merchant->getId(),
                    'merchant_name' => $merchant->getBillingLabelElseName(),
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

    protected function getMerchantData($merchant)
    {
        return [
            'billing_label'  => $merchant->getBillingLabelElseName(),
            'account_number' => $merchant->getRedactedAccountNumber(),
            'email'          => $merchant->getTransactionReportEmail(),
            'date'           => $this->date,
        ];
    }

    protected function isBlank($data)
    {
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
