<?php

namespace RZP\Models\Merchant\Fraud\HealthChecker;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function isLive(array $input, $checkerType): array
    {
        $url = $input['url'];
        return (new Job())->isLive($url);
    }

    public function periodicCron($checkerType): array
    {
        return $this->core()->periodicCron($checkerType);
    }

    public function milestoneCron($checkerType): array
    {
        return $this->core()->milestoneCron($checkerType);
    }

    public function riskScoreCron($checkerType): array
    {
        return $this->core()->riskScoreCron($checkerType);
    }

    public function retryCron($checkerType): array
    {
        return $this->core()->retryCron($checkerType);
    }

    public function reminderCron($checkerType): array
    {
        return $this->core()->reminderCron($checkerType);
    }
}
