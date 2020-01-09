<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\SourceUpdater;

class PayoutSourceUpdaterJob extends Job
{

    protected $payoutPublicId;

    protected $previousPayoutStatus;

    public function __construct(string $mode, string $payoutPublicId, $previousPayoutStatus)
    {
        parent::__construct($mode);

        $this->payoutPublicId = $payoutPublicId;

        $this->previousPayoutStatus = $previousPayoutStatus;
    }

    public function handle()
    {
        parent::handle();

        $payout = $this->repoManager->payout->findByPublicId($this->payoutPublicId);

        $this->trace->info(
            TraceCode::PAYOUT_SOURCE_UPDATER_JOB,
            [
                'payout_id'      => $this->payoutPublicId,
                'current_status' => $payout->getStatus(),
                'previous_statu' => $this->previousPayoutStatus
            ]
        );

        SourceUpdater::update($payout, $this->previousPayoutStatus);
    }
}
