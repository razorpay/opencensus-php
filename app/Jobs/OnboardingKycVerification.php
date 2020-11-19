<?php

namespace RZP\Jobs;

class OnboardingKycVerification extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    protected $queueConfigKey = 'onboarding_kyc_verification';

    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();
    }
}
