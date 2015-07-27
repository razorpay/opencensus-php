<?php

namespace Models\Merchant;

use Config;
use Carbon\Carbon;
use EE\Exception;
use Mail;
use Models\Payment;
use Models\Settlement;

class DailyReport
{
    /**
     * Generates a new daily report
     * @param String $id Merchant Id
     */
    function __construct($id)
    {
        $this->merchantId = $id;

        // 00:00 Yesterday
        $this->timeLowerLimit = Carbon::yesterday("Asia/Kolkata")->timestamp;

        // 00:00 Today
        $this->timeUpperLimit = Carbon::today("Asia/Kolkata")->timestamp;

        // date format = 6th July 2015
        $this->date = Carbon::yesterday("Asia/Kolkata")->format('jS F Y');

        $this->data = $this->fetchDailyDetails();
    }

    /**
     * Sends the daily report
     * @return boolean Whether the daily report was sent or not
     */
    public function send()
    {
        if ($this->isBlank() === false)
        {
            $this->sendDailyReport();
            return true;
        }
        return false;
    }

    /**
     * Sends daily transaction report over email to the given merchant
     * @param  String $id merchant id
     * @return null
     */
    protected function sendDailyReport()
    {
        $config = Config::get('applications.mailgun');

        $view = ['html'=>'emails.merchant.daily_report'];

        $data = $this->data;

        Mail::queue($view, $this->data, function($message) use ($config, $data)
        {
            $message->to($data['merchant']['transaction_report_email']);

            $message->from($config['from_email'], $config['from_name']);

            $message->cc('notifications@razorpay.com');

            $message->subject('Daily Transaction Report for ' . $data['date']);
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
        $authorizedCollection = (new Payment\Repository)->fetch(
            [
                // Note: This is not correct and uses CREATED_AT
                // instead of AUTHORIZED_AT

                'from'      => $this->timeLowerLimit,
                'to'        => $this->timeUpperLimit,
                'status'    => 'authorized',
            ],
            $this->merchantId);

        return $this->summarizePayments($authorizedCollection);
    }

    protected function getCapturedPayments()
    {
        $capturedCollection = (new Payment\Repository)
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
            'payments' => $payments->toArrayAdmin(),
            'sum'      => $payments->sum('amount'),
            'orderId'  => false
        ];
    }

    /**
     * Returns a settlement if found or null
     * Raises exception if more than one settlement was found
     * @return Array|null
     */
    protected function getSettlement()
    {
        $settlements = (new Settlement\Repository)->fetch([
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
        $refunds = (new Payment\Refund\Repository)->fetch([
            'from' => $this->timeLowerLimit,
            'to' => $this->timeUpperLimit
        ], $this->merchantId);

        return [
            'sum'      => $refunds->sum('amount'),
            'refunds'  => $refunds->toArrayPublic(),
        ];
    }

    protected function fetchDailyDetails()
    {
        $merchant = (new Repository)->findOrFailPublic($this->merchantId);

        $data = [
            'captured'       => $this->getCapturedPayments(),
            'authorized'     => $this->getAuthorizedPayments(),
            'refunds'        => $this->getRefunds(),
            'settlement'     => $this->getSettlement(),
            'merchant'       => $merchant->toArray(),
            'account_number' => $merchant->getRedactedAccountNumber(),
            'date'           => $this->date,
        ];

        return $data;
    }

    protected function isBlank()
    {
        $data = $this->data;

        return (($data['captured']['sum'] === 0) and
                ($data['authorized']['sum'] === 0) and
                ($data['refunds']['sum'] === 0) and
                ($data['settlement'] === null));
    }
}
