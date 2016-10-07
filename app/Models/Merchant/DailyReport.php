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
    protected $merchantId;

    protected $timeLowerLimit;

    protected $timeUpperLimit;

    protected $date;

    // If the merchant has more payments than this,
    // we'll send aggregates instead of details of
    // every individual payment
    const DETAILED_REPORT_PAYMENT_LIMIT = 80;

    const DAILY_REPORT_EMAIL_TEMPLATE               = 'emails.merchant.daily_report';
    const DAILY_REPORT_HIGH_VOLUME_EMAIL_TEMPLATE   = 'emails.merchant.daily_report_high_volume';

    /**
     * Generates a new daily report
     * @param String $id Merchant Id
     */
    function __construct($id)
    {
        parent::__construct();

        $this->merchantId = $id;

        // 00:00 Yesterday
        $this->timeLowerLimit = Carbon::yesterday("Asia/Kolkata")->timestamp;

        // 00:00 Today
        $this->timeUpperLimit = Carbon::today("Asia/Kolkata")->timestamp;

        // date format = 6th July 2015
        $this->date = Carbon::yesterday("Asia/Kolkata")->format('jS F Y');

        $this->data = $this->fetchDailyDetails();

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

            return $this->merchantId;
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

        $paymentCount = $data['authorized']['payments']['count']
                        + $data['captured']['payments']['count']
                        + $data['refunds']['refunds']['count'];

        // Above a certain threshold, our daily report mails will
        // contain only aggregates, and not actual payment details.
        if ($paymentCount > self::DETAILED_REPORT_PAYMENT_LIMIT)
        {
            $dailyReportView = self::DAILY_REPORT_HIGH_VOLUME_EMAIL_TEMPLATE;
        }
        else
        {
            $dailyReportView = self::DAILY_REPORT_EMAIL_TEMPLATE;
        }

        $view = ['html' => $dailyReportView];

        // Log merchant whose data has been computed
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_DATA,
            array(
                    'merchant_id'   => $this->merchantId,
                    'merchant_name' => $data['billing_label'],
                    'captured'      => $data['captured']['payments']['count'],
                    'authorized'    => $data['authorized']['payments']['count'],
                    'refunds'       => $data['refunds']['refunds']['count'],
                    )
        );

        // This is a debug view only for raising proper errors
        \View::make('emails.merchant.daily_report_debug', $data)->render();

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

    /**
     * Returns an array with the following attributes:
     *     sum: sum of all authorized payments
     *     payments: array containing all authorized payments
     *     order_id: whether any of the payments had an orderId
     *
     * @return array
     */
    protected function getAuthorizedPayments()
    {
        $authorizedCollection = $this->repo->payment->fetch(
            ['status'    => 'authorized'],
            $this->merchantId);

        return $this->summarizePayments($authorizedCollection);
    }

    protected function getCapturedPayments()
    {
        $capturedCollection = $this->repo->payment
            ->fetchCapturedBetweenTimestamp(
                $this->timeLowerLimit,
                $this->timeUpperLimit,
                $this->merchantId
            );

        return $this->summarizePayments($capturedCollection);
    }

    /**
     * This method is called over a collection of payments
     *
     * @return Array
     */
    private function summarizePayments($payments)
    {
        //
        // Remove comment when we start using order id
        //

        // $isOrderIdSet = false;
        // $sum = 0;
        // $payments = [];

        // // This loop makes sure that every member of payments is
        // // an array with all required attribute
        // foreach ($paymentCollection as $payment)
        // {
        //     $isOrderIdSet = (bool) $payment->getOrderId();

        //     $sum += $payment->getAmount();

        //     $paymentArray = $payment->toArray();
        //     $paymentArray['orderId'] = $payment->getOrderId();

        //     $payments[] = $paymentArray;
        // }

        return [
            'payments' => $payments->toArrayDailyReport(),
            'sum'      => $payments->sum('amount'),
        ];
    }

    /**
     * Returns a settlement if found or null
     * Raises exception if more than one settlement was found
     * @return Array|null
     */
    protected function getSettlement()
    {
        $settlements = $this->repo->settlement->fetch([
            'from' => $this->timeLowerLimit,
            'to' => $this->timeUpperLimit
        ], $this->merchantId);

        // Return null if no settlement found
        $settlement = null;

        if (count($settlements) > 1)
        {
            $data = [
                'merchant_id'       => $this->merchantId,
                'settlement_count'  => count($settlements)
            ];

            throw new Exception\RuntimeException(
                'Found more than 1 settlement within one day', $data);
        }
        else if (count($settlements) === 1)
        {
            $settlement = $settlements[0]->toArray();
        }

        return $settlement;
    }

    protected function getRefunds()
    {
        $refunds = $this->repo->refund->fetch([
            'from' => $this->timeLowerLimit,
            'to' => $this->timeUpperLimit
        ], $this->merchantId);

        return [
            'sum'      => $refunds->sum('amount'),
            'refunds'  => ['count' => $refunds->count()],
        ];
    }

    protected function fetchDailyDetails()
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->merchantId);

        $data = [
            'captured'       => $this->getCapturedPayments(),
            'authorized'     => $this->getAuthorizedPayments(),
            'refunds'        => $this->getRefunds(),
            'settlement'     => $this->getSettlement(),
            'billing_label'  => $merchant->getBillingLabelElseName(),
            'account_number' => $merchant->getRedactedAccountNumber(),
            'email'          => $merchant->getTransactionReportEmail(),
            'date'           => $this->date,
        ];

        return $data;
    }

    protected function isBlank()
    {
        $data = $this->data;

        return (($data['captured']['payments']['count'] === 0) and
                ($data['authorized']['payments']['count'] === 0) and
                ($data['refunds']['refunds']['count'] === 0) and
                ($data['settlement'] === null));
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(3000);
    }
}
