<?php

namespace RZP\Models\Batch\Processor;

class Payout extends Base
{
    public function addSettingsIfRequired(& $input)
    {
        if (isset($input["config"]) === true) {
            $config = $input["config"];
        }

        $input["config"] = $config;
    }
}
