<?php

namespace RZP\Gateway\Upi\Rbl\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Upi\Rbl;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Rbl\Gateway
{
    use Base\Mock\GatewayTrait;
}
