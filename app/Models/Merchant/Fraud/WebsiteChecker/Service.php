<?php

namespace RZP\Models\Merchant\Fraud\WebsiteChecker;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function isLive(array $input): array
    {
        $url = $input['url'];

        return (new Job())->isLive($url);
    }

    public function periodicCron(): array
    {
        return $this->core()->periodicCron();
    }

    public function milestoneCron(): array
    {
        return $this->core()->milestoneCron();
    }

    public function riskScoreCron(): array
    {
        return $this->core()->riskScoreCron();
    }

    public function retryCron(): array
    {
        return $this->core()->retryCron();
    }

    public function reminderCron(): array
    {
        return $this->core()->reminderCron();
    }
}
