<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Terminal\Absence;

class Core extends Base\Core
{
    public function create($input, $gateway)
    {
        $input['gateway'] = $gateway;

        $down_window = (new Absence\Entity)->build($input);

        //$this->validateExistingAction($action);

        $this->repo->terminal_absence->saveOrFail($down_window);

        return $down_window;
    }
}
