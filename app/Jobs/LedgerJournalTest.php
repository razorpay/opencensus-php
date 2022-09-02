<?php

namespace RZP\Jobs;

class LedgerJournalTest extends LedgerJournalBase
{
    /**
     * used to map the SQS queue worker to this code
     *
     * @var string
     */
    protected $queueConfigKey = 'ledger_x_journal';

    const MODE = 'test';

    public function __construct(array $payload)
    {
        parent::__construct(self::MODE, $payload);
    }
}
