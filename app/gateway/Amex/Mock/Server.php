<?php

namespace Gateway\Amex\Mock;

use Gateway\AxisMigs;
use Gateway\Amex;

class Server extends AxisMigs\Mock\Server
{
	protected function addVpcCard(array & $content, $input)
    {
        switch ($input['vpc_CardNum'])
        {
            case '345678000000007':
                $content['vpc_3DSstatus'] = 'U';
                break;
            default:
                $content['vpc_3DSstatus'] = 'Y';
        }
    }
}
