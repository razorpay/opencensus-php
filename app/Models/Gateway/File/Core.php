<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    const HALT = 'halt';

    public function create(array $input, bool $halt = false)
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_CREATE_REQUEST, $input);

        $gatewayFile = (new Entity)->build($input);

        $this->repo->saveOrFail($gatewayFile);

        if ($halt !== true)
        {
            $this->process($gatewayFile);
        }

        return $gatewayFile;
    }

    public function process(Entity $gatewayFile)
    {
        $procesor = ProcessorFactory::getProcessor($gatewayFile);

        $procesor->process();
    }
}
