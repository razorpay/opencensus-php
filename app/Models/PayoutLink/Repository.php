<?php

namespace RZP\Models\PayoutLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\PaymentPageItem;

class Repository extends Base\Repository
{
    protected $entity = 'payout_link';

    protected $expands = [
    ];

}
