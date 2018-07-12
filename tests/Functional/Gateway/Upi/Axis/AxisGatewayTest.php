<?php

namespace RZP\Tests\Functional\Gateway\Upi\Axis;

use Cache;
use Closure;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Exception\RuntimeException;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;
}

