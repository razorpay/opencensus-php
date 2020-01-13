<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\SourceUpdater;

class PayoutSourceUpdaterJob extends Job
{

    protected $payoutPublicId;

    protected $previousPayoutStatus;

    protected $expectedCurrentStatus;

    public function __construct(string $mode, string $payoutPublicId, $previousPayoutStatus, $expectedCurrentStatus)
    {
        parent::__construct($mode);

        $this->payoutPublicId = $payoutPublicId;

        $this->previousPayoutStatus = $previousPayoutStatus;

        $this->expectedCurrentStatus = $expectedCurrentStatus;
    }

    public function handle()
    {
        parent::handle();

        $payout = $this->repoManager->payout->findByPublicId($this->payoutPublicId);

        $context = [
            'payout_id'               => $this->payoutPublicId,
            'current_status'          => $payout->getStatus(),
            'previous_status'         => $this->previousPayoutStatus,
            'expected_current_status' => $this->expectedCurrentStatus
        ];

        $this->trace->info(
            TraceCode::PAYOUT_SOURCE_UPDATER_JOB,
            $context
        );

        if ($this->expectedCurrentStatus !== $payout->getStatus())
        {
            // todo, pl add a slack push here
            $this->trace->warning(TraceCode::PAYOUT_SOURCE_UPDATER_MISMATCH_EXPECTED_STATUS,
                                  $context);
            return;
        }

        SourceUpdater::update($payout, $this->previousPayoutStatus);
    }
}
