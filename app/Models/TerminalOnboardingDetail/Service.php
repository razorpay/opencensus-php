<?php

namespace RZP\Models\TerminalOnboardingDetail;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createTerminalOnboardingDetail($terminalId, $input)
    {
        $terminal = $this->repo->terminal->findOrFailPublic($terminalId);

        $terminal_onboarding_detail = $this->core()->create($input, $terminal);

        return $terminal_onboarding_detail;
    }
}