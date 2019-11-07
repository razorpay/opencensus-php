<?php

namespace RZP\Jobs;

class FundAccountValidation extends Job
{
    protected $queueConfigKey = 'fund_account_validation';

    protected $fundAccountValidationId;

    public function __construct(string $mode, string $fundAccountValidationId)
    {
        parent::__construct($mode);

        $this->fundAccountValidationId = $fundAccountValidationId;
    }

    public function handle()
    {
        parent::handle();

        $this->delete();
    }
}
