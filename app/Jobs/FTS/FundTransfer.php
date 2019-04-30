<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Exception\RecordAlreadyExists;
use RZP\Models\Settlement\SlackNotification;

class FundTransfer extends Job
{
    const RETRY_PERIOD         = 30;

    const MAX_ALLOWED_ATTEMPTS = 10;

    /**
     * @var string
     */
    protected $ftaId;

    /**
     * @var bool
     */
    protected $isRegistered;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_fund_transfer';

    public function __construct(string $mode, string $id, bool $isRegistered)
    {
        parent::__construct($mode);

        $this->ftaId  = $id;

        $this->isRegistered = $isRegistered;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::FTS_FUND_TRANSFER_INIT,
                [
                    'fta_id' => $this->ftaId,
                ]);

            $ftsResponse = App::getFacadeRoot()['fts_fund_transfer']->requestFundTransfer(
                $this->ftaId,
                $this->isRegistered);

            $this->trace->info(
                TraceCode::FTS_FUND_TRANSFER_COMPLETE,
                $ftsResponse);
        }
        catch (RecordAlreadyExists $e)
        {
            $this->delete();
        }
        catch (\Throwable $e)
        {
            $data = [
                'fta_id' => $this->ftaId,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTS_FUND_TRANSFER_FAILED,
                $data);

            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                $operation = 'fts fund transfer job failed';

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
