<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Jitendra\Lqext\TransactionAware;
use RZP\Exception\RecordAlreadyExists;
use RZP\Models\Settlement\SlackNotification;

class FundTransfer extends Job
{
    use TransactionAware;

    const RETRY_PERIOD         = 30;

    const MAX_ALLOWED_ATTEMPTS = 10;

    /**
     * @var string
     */
    protected $ftaId;

    /**
     * @var string
     */
    protected $otp;

    /**
     * @var int
     */
    public $timeout = 60;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_fund_transfer';

    public function __construct(string $mode, string $id , string $otp = null)
    {
        parent::__construct($mode);

        $this->ftaId  = $id;

        $this->otp = $otp;
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

            /** @var \RZP\Services\FTS\FundTransfer $transferService */
            $transferService = App::getFacadeRoot()['fts_fund_transfer'];

            $transferService->initialize($this->ftaId);

            list($initiateTransfers, $reason) = $transferService->shouldAllowTransfersViaFts();

            if ($initiateTransfers === false)
            {
                $addedInitiateAt = $transferService->addInitiateAtIfRequired();

                if ($addedInitiateAt === false) {

                    $this->trace->info(TraceCode::FTS_FUND_TRANSFER_NOT_ALLOWED,
                        [
                            'fta_id' => $this->ftaId,
                            'reason' => $reason,
                        ]);

                    $this->delete();

                    return;
                }
            }

            $ftsResponse = $transferService->requestFundTransfer($this->otp);

            $this->trace->info(
                TraceCode::FTS_FUND_TRANSFER_COMPLETE,
                $ftsResponse);
        }
        catch (RecordAlreadyExists $e)
        {
            $this->delete();

            return;
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
