<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\Models\Merchant\Detail\CompanyPanStatus;

class CompanyPanVerifier implements Verifier
{
    use PanVerifier;

    protected function getIncorrectDetailsStatus()
    {
        return CompanyPanStatus::INCORRECT_DETAILS;
    }

    protected function getFailedStatus()
    {
        return CompanyPanStatus::FAILED;
    }

    protected function getNotMatchedStatus()
    {
        return CompanyPanStatus::NOT_MATCHED;
    }

    protected function getVerifiedStatus()
    {
        return CompanyPanStatus::VERIFIED;
    }

    protected function getExpectedMatchPercentage()
    {
        return CompanyPanStatus::VERIFICATION_THRESHOLD;
    }
}
