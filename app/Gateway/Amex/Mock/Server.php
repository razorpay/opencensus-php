<?php

namespace RZP\Gateway\Amex\Mock;

use RZP\Gateway\AxisMigs;
use RZP\Gateway\Amex;

class Server extends AxisMigs\Mock\Server
{
	protected function addVpcCard(array & $content, $input)
    {
        switch ($input['vpc_CardNum'])
        {
            case '345678000000007':
                $content['vpc_3DSstatus'] = 'N';
                break;
            default:
                $content['vpc_3DSstatus'] = 'Y';
        }
    }
}
