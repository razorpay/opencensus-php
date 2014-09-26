<?php

namespace Models\Settlement;

use Carbon\Carbon;
use EE\Exception;
use Models\Gateway;
use Models\Transaction;
use Models\Merchant;
use Models\Payment;
use Models\Payment\Refund;
use Queue;

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

    public function __construct($mode = '')
    {
        $this->mode = $mode;

        $this->env = \App::environment();

        $this->queue = Queue::getFacadeRoot();

        $this->mail = \Mail::getFacadeRoot();
    }

    public function generateTestMpr($input)
    {
        $this->initTimestamps($input);

        try
        {
            $data = $this->process();

            $this->queueMprGenerationMail($data);
        }
        catch (\Exception $e)
        {
            $this->queueMprGenerationFailureMail($e);

            (new SlackNotification)->queueOperationFailure('mpr_generation', $e);

            throw $e;
        }

        (new SlackNotification)->queueOperationSuccess('mpr_generation', $data['count']);

        return $data['file'];
    }

    protected function process()
    {
        $this->checkMode();

        $gateway = 'hdfc';

        $paymentRepo = new Payment\Repository;
        $payments = $paymentRepo->fetchCapturedForGatewayBetweenTimestamp(
                            self::$fromTimestamp,
                            self::$toTimestamp,
                            $gateway);

        $rfndRepo = new Refund\Repository;
        $refunds = $rfndRepo->findBetweenTimestamps(
                            self::$fromTimestamp,
                            self::$toTimestamp);

        $count = $payments->count();

        if ($count === 0)
        {
            return array('file' => null, 'count' => 0);
        }

        $array = $this->getRelatedEntities($payments);

        $mprFile = Gateway::call('generateMpr', $array, 'test');

        return array('file' => $mprFile, 'count' => $count);
    }

    protected function getRelatedEntities($payments)
    {
        $payments->load('merchant', 'merchant.terminal', 'card');

        $array = array();

        foreach($payments->all() as $payment)
        {
            $merchant = $payment->merchant;

            $cols = array(
                'payment' => $payment->toArray(),
                'merchant'    => $merchant->toArray(),
                'terminal'    => $merchant->terminal->toArray(),
                'card'        => $payment->card->toArray()
            );

            array_push($array, $cols);
        }

        return $array;
    }

    protected function queueMprGenerationMail($data)
    {
        if ($data['count'] === 0)
            return;

        $func = __CLASS__ . '@sendHdfcMprMail';

        $message = 'Hdfc mpr file: ' . $data['file'] .
        ' generated ' . PHP_EOL;

        $message .= 'Number of payments: ' . $data['count'];

        $message .= ' Env: ' . $this->env;

        $data['message'] = $message;
        $data['env'] = $this->env;
        $data['mode'] = $this->mode;

        $this->queue->push($func, $data);
    }

    protected function queueMprGenerationFailureMail($e)
    {
        $func = __CLASS__ . '@sendHdfcMprMail';

        $message = 'Failed to generate Hdfc mpr file' . PHP_EOL;

        $message .= ' Exception Message: ' . $e->getMessage();
        $message .= ' Exception Trace: ' . $e->getTraceAsString();
        $message .= ' Exception Class: ' . get_class($e);

        $message .- ' Env: ' . $this->env;

        $data['message'] = $message;

        $this->queue->push($func, $data);
    }

    public function sendHdfcMprMail($job, $data)
    {
        $job->delete();

        $this->mail->send('hdfc.mpr', $data, function($message) use ($data)
        {
            $email = 'hdfc_mpr_' . $data['env'] . '_' . $data['mode'] . '@mg.razorpay.com';

            $message->from('hdfc_mpr_generator@mg.razorpay.com', 'hdfcMprGenerator');

            $message->to($email)->cc('settlement@razorpay.com');

            if (isset($data['file']))
            {
                $message->attach($data['file']);
            }
        });
    }

    protected function checkMode()
    {
        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Not in test mode');
        }
    }

    protected function initTimestamps($input)
    {
        if ((isset($input['today'])) and
            ($input['today'] === '1'))
        {
            self::setTodayTimestamps();
            return;
        }

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
