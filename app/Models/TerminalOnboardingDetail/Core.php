<?php

namespace RZP\Models\TerminalOnboardingDetail;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input, $terminal)
    {
        $terminalOnboardingDetail = (new Entity)->build($input);

        $terminalOnboardingDetail->terminal()->associate($terminal);

        $this->repo->saveOrFail($terminalOnboardingDetail);

        return $terminalOnboardingDetail;
    }
}
