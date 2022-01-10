<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function handleCron($input)
    {
        (new Validator())->validateInput("run", $input);

        $processor = Factory::getCronProcessor($input);

        return $processor->process();
    }
}
