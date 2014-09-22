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

    protected $queue;

    public function __construct($mode)
    {
        $this->mode = $mode;

        $this->env = \App::environment();

        $this->queue = Queue::getFacadeRoot();

        $this->initTimestamps();
    }

    public function generateTestMprForToday()
    {
        try
        {
            $data = $this->process();

            $this->queueMprGenerationMail($data);

            $this->queueMprGenerationSlackNotification($data);
        }
        catch (\Exception $e)
        {
            $this->queueMprGenerationFailureMail($e);

            $this->queueMprGenerationFailureSlackNotification($e);

            throw $e;
        }

        return $data['file'];
    }

    protected function process()
    {
        $this->checkMode();

        $txnRepo = new Transaction\Repository;
        $txns = $txnRepo->fetchCapturedForGatewayBetweenTimestamp(
                            self::$fromTimestamp,
                            self::$toTimestamp,
                            $gateway);

        $rfndRepo = new Refund\Repository;
        $refunds = $rfndRepo->findBetweenTimestamps(
                            self::$fromTimestamp,
                            self::$toTimestamp);

        $count = $txns->count();

        if ($count === 0)
        {
            return array('file' => null, 'count' => 0);
        }

        $array = $this->getRelatedEntities($txns);

        list($mprFile, $count) = Gateway::call('generateMpr', $array, 'test');

        return array('file' => $mprFile, 'count' => $count);
    }

    protected function getRelatedEntities($txns)
    {
        $txns->load('merchant', 'merchant.terminal', 'card');

        $array = array();

        foreach($txns->all() as $txn)
        {
            $merchant = $txn->merchant;

            $cols = array(
                'transaction' => $txn->toArray(),
                'merchant'    => $merchant->toArray(),
                'terminal'    => $merchant->terminal->toArray(),
                'card'        => $txn->card->toArray()
            );

            array_push($array, $cols);
        }

        return $array;
    }

    protected function queueMprGenerationMail($data)
    {
        $func = __NAMESPACE__ . '@sendHdfcMprMail';

        $message = 'Hdfc mpr file: ' . $data['mprFile'] .
        ' generated on ' . date('F j, Y, g:i a') . PHP_EOL;

        $message .= 'Number of transactions: ' . $data['count'];

        $data['message'] = $message;
        $this->queue->push($func, $data);
    }

    protected function queueMprGenerationFailureMail($e)
    {
        $func = __NAMESPACE__ . '@sendHdfcMprMail';

        $message = 'Failed to generate Hdfc mpr file on ' . date('F j, Y, g:i a') . PHP_EOL;

        $message .= ' Exception Message: ' . $e->getMessage();
        $message .= ' Exception Trace: ' . $e->getTraceAsString();
        $message .= ' Exception Class: ' . get_class($e);

        $data['message'] = $message;

        $this->queue->push($func, $data);
    }

    public function sendHdfcMprMail($job, $data)
    {
        $job->delete();

        $this->mail->send('hdfc.mpr', $data, function($message) use ($data)
        {
            $message->from('hdfc_mpr_generator@mg.razorpay.com', 'hdfcMprGenerator');

            $message->to('hdfc_mpr_test@mg.razorpay.com')->cc('settlement@razorpay.com');

            if (isset($data['file']))
            {
                $message->attach($data['file']);
            }
        });
    }

    protected function queueMprGenerationSlackNotification($data)
    {
        $message = 'Mpr file generated with ' . $data['count'] . ' transactions on ' . date('F j, Y, g:i a');

        $func = __NAMESPACE__ . '@sendSlackNotification';

        $this->queue->push($func, $message);
    }

    protected function queueMprGenerationFailureSlackNotification($e)
    {
        $message = 'Failed to generate mpr file. Exception class: ' . get_class($e) .
                   ' Exception message: ' . $e->getMessage();

        $func = __NAMESPACE__ . '@sendSlackNotification';

        $this->queue->push($func, $message);
    }

    public function sendSlackNotification($job, $message)
    {
        $channel = '#settlements';
        $username = 'settlements';

        $job->delete();

        (new \Services\Slack)->send($message, $channel, $username);
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

    public static function setTodayTimestamps()
    {
        self::$fromTimestamp = Carbon::today('Asia/Kolkata')->timestamp;
        self::$toTimestamp = time();
    }
}
