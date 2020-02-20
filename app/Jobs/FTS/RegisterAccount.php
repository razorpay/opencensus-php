<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\SlackNotification;

class RegisterAccount extends Job
{
    const RETRY_PERIOD         = 30;

    const MAX_ALLOWED_ATTEMPTS = 10;

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var string
     */
    protected $channel;

    /**
     * @var int
     */
    public $timeout = 60;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_register_account';

    public function __construct(string $mode, string $channel, array $ftsAccountIds)
    {
        parent::__construct($mode);

        $this->ids  = $ftsAccountIds;

        $this->channel = $channel;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::FTS_REGISTER_ACCOUNT_INIT,
                [
                    'ids'     => $this->ids,
                    'channel' => $this->channel,
                ]);

            $ftsResponse = App::getFacadeRoot()['fts_register_account']->registerFundAccount(
                $this->channel,
                $this->ids);

            $this->trace->info(
                TraceCode::FTS_REGISTER_ACCOUNT_COMPLETE,
                $ftsResponse);
        }
        catch (\Throwable $e)
        {
            $data = [
                'ids'     => $this->ids,
                'channel' => $this->channel,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTS_REGISTER_ACCOUNT_FAILED,
                $data);

            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                $operation = 'fts register account job failed';

                (new SlackNotification)->send($operation, $data, null, 1, 'fts_alerts');

                return;
            }
            else
            {
                $this->release(self::RETRY_PERIOD);
            }
        }
    }
}