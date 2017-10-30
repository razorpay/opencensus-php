<?php

namespace RZP\Models\Merchant;

use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Base\RuntimeManager;
use RZP\Exception;
use RZP\Mail\Merchant\DailyReport as DailyReportMail;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Settlement;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;
use Razorpay\Trace\Logger as Trace;

class DailyReport extends Base\Core
{
    protected $merchant;

    protected $timeLowerLimit;

    protected $timeUpperLimit;

    protected $date;

    protected $data;

    /**
     * Generates a new daily report
     * @param String $id Merchant Id
     */
    function __construct()
    {
        parent::__construct();

        $this->increaseAllowedSystemLimits();
    }

    public function sendReportForAllMerchants($input)
    {
        $this->setTimestamps($input);

        $from = $this->timeLowerLimit;

        $to = $this->timeUpperLimit;

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

        // To allow manual report generation for specific merchants
        if (isset($input['ids']) === true)
        {
            $merchantIds = $input['ids'];
        }
        else
        {
            $merchantIds = array_keys($captureMerchants + $authMerchants
                                    + $refundMerchants + $setlMerchants);

            // if it's integer like string keys, then array_keys will convert
            // those to integers. Let's re-map to string
            $merchantIds = array_map('strval', $merchantIds);
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
                    'authorized'  => $authMerchants[$merchantId] ?? $zeroArray,
                    'captured'    => $captureMerchants[$merchantId] ?? $zeroArray,
                    'refunds'     => $refundMerchants[$merchantId] ?? $zeroArray,
                    'settlements' => $setlMerchants[$merchantId] ?? $zeroArray,
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
        if ($merchant->isLinkedAccount() === true)
        {
            return null;
        }

        if (($this->isBlank($data) === false) and
            (empty($data['email']) === false))
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
        // Log merchant whose data has been computed
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_DATA,
            [
                'merchant_id'    => $merchant->getId(),
                'merchant_name'  => $merchant->getBillingLabel(),
                'captured'       => $data['captured']['count'],
                'authorized'     => $data['authorized']['count'],
                'refunds'        => $data['refunds']['count'],
                'settlement'     => $data['settlements']['sum'],
                'setl_count'     => $data['settlements']['count'],
            ]
        );

        $merchant = $merchant->toArrayPublic();

        $dailyReportMail = new DailyReportMail($data, $merchant);

        Mail::queue($dailyReportMail);
    }

    protected function getMerchantData($merchant)
    {
        return [
            'billing_label'  => $merchant->getBillingLabel(),
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

    protected function setTimestamps($input)
    {
        if (isset($input['on']) === true)
        {
            $on = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST);
        }
        else
        {
            $on = Carbon::yesterday(Timezone::IST);
        }

        // date format = 6th July 2015
        $this->date = $on->format('jS F Y');

        $this->timeLowerLimit = $on->getTimestamp();

        $this->timeUpperLimit = $on->addDay()->getTimestamp();
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(3000);
    }
}
