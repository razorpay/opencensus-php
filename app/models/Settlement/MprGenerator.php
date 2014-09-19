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
    public function __construct($mode)
    {
        $this->mode = $mode;
    }

    public function generateTestMprForToday()
    {
        $this->checkMode();

        $gateway = 'hdfc';

        // Get the timestamp on T-1 day 12 am for IST
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->subSecond(1)->timestamp;

        $txnRepo = new Transaction\Repository;
        $txns = $txnRepo->fetchCapturedForGatewayBetweenTimestamp(
                            $from, $to, $gateway);

        $rfndRepo = new Refund\Repository;
        $refunds = $rfndRepo->findBetweenTimestamps($from, $to);

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
            $message->from('shashankkumar.me@gmail.com', 'shk');

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
}
