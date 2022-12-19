<?php

namespace RZP\Jobs;

class SyncCommissionToPartnershipService extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    /**
     * @var string
     */
    protected $queueConfigKey = 'partnerships_commission';

    public    $timeout        = 1000;

    public array $data;

    public function __construct(string $mode, array $data)
    {
        parent::__construct($mode);

        $this->data = $data;
    }
}
