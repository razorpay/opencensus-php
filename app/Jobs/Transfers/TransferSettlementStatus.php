<?php


namespace RZP\Jobs\Transfers;


use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Transfer\Core;

class TransferSettlementStatus extends Job
{
    /**
     * This job will be terminated after <$timeout> seconds.
     * @var int
     */
    public $timeout = 900;

    protected $queueConfigKey = 'transfer_settlement';

    protected $settlementId;

    public function __construct(string $mode, string $settlementId)
    {
        parent::__construct($mode);

        $this->settlementId = $settlementId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::SETTLEMENT_STATUS_UPDATE_IN_TRANSFERS_REQUEST,
            [
                'settlement_id' => $this->settlementId,
            ]
        );

        try
        {
            (new Core())->updateSettlementStatusInTransfers($this->settlementId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::SETTLEMENT_STATUS_UPDATE_IN_TRANSFER_FAILED,
                [
                    'settlement_id' => $this->settlementId,
                ]
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
