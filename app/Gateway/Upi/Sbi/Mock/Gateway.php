<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Upi\Sbi;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Sbi\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;
}
