<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\Mode as RZPMode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Entity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use AesTrait;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    const MODE_ECB = 1;

    protected $map = [

    ];

    public function authorize($input)
    {

    }

    public function callback($input)
    {

    }

    public function verify($input)
    {

    }
}
