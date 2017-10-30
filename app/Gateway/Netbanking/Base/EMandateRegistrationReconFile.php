<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Base\RuntimeManager;
use RZP\Models\Base;

class EMandateRegistrationReconFile extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->increaseAllowedSystemLimits();
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(300);
    }
}