<?php

namespace Models\Settlement;

use Carbon\Carbon;
use Models\Gateway;
use Models\Ledger;
use Models\Merchant;
use Models\Transaction;
use Models\Transaction\Refund;

class MprGenerator
{
    /**
     * It's set to yesterday's timestamp if default is null.
     *
     * @var int
     */
    public static $fromTimestamp = null;

    /**
     * It's set to today's timestamp -1 if default is null
     *
     * @var int
     */
    public static $toTimestamp = null;

    public function __construct($mode)
    {
        $this->mode = $mode;

        $this->initTimestamps();
    }

    public function generateTestMprForToday()
    {
        $this->checkMode();

        $gateway = 'hdfc';

        $txnRepo = new Transaction\Repository;
        $txns = $txnRepo->fetchCapturedForGatewayBetweenTimestamp(
                            self::$fromTimestamp,
                            self::$toTimestamp,
                            $gateway);

        $rfndRepo = new Refund\Repository;
        $refunds = $rfndRepo->findBetweenTimestamps(
                            self::$fromTimestamp,
                            self::$toTimestamp);

        if ($txns->count() === 0)
        {
            return 'no new transactions! Lets wrap up!';
        }

        $array = $this->getRelatedEntities($txns);
        $mprFile = Gateway::call('generateMpr', $array, 'test');

        $this->sendMprMail($mprFile);

        return $mprFile;
    }

    protected function getRelatedEntities($txns)
    {
        $txns->load('merchant', 'merchant.terminal', 'card');

        $array = array();

        foreach($txns->all() as $txn)
        {
            $cols = array(
                'transaction' => $txn->toArray(),
                'merchant'    => $txn->merchant->toArray(),
                'terminal'    => $txn->merchant->terminal->toArray(),
                'card'        => $txn->card->toArray()
            );

            array_push($array, $cols);
        }

        return $array;
    }

    protected function sendMprMail($mprFile)
    {
        \Mail::send('hdfc.mpr', array(), function($message) use ($mprFile)
        {
            $message->from('hdfc_mpr_generator@mg.razorpay.com', 'hdfcMprGenerator');

            $message->to('testmpr@sandboxf697ec003a374fb798a36a45d622d5f6.mailgun.org')->cc('settlement@razorpay.com');

            $message->attach($mprFile);
        });
    }

    protected function checkMode()
    {
        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Not in test mode');
        }
    }

    protected function initTimestamps()
    {
        if (self::$fromTimestamp === null)
        {
            // Get the timestamp on T-1 day 12 am for IST
            self::$fromTimestamp = Carbon::yesterday('Asia/Kolkata')->timestamp;
        }

        if (self::$toTimestamp === null)
        {
            self::$toTimestamp = Carbon::today('Asia/Kolkata')->subSecond(1)->timestamp;
        }
    }
}
